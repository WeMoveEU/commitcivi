<?php

const ERR_EMAIL = 1;

function _civicrm_api3_paypal_recover_spec(&$spec) {
  $spec['start'] = [
    'name' => 'start',
    'title' => ts('Start date'),
    'description' => 'Date from which the recovery should start in format ISO 8601',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['end'] = [
    'name' => 'end',
    'title' => ts('End date'),
    'description' => 'Date at which the recovery should end in format ISO 8601',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['pp_id'] = [
    'name' => 'pp_id',
    'title' => ts('Payment processor id'),
    'description' => 'Payment processor id',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['mode'] = [
    'name' => 'mode',
    'title' => ts('Payment mode'),
    'description' => 'Payment mode: live or test',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => 'live',
  ];
  $spec['limit'] = [
    'name' => 'limit',
    'title' => ts('Transactions limit'),
    'description' => 'Maximum number of transactions to recover',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'api.default' => NULL,
  ];
}

function civicrm_api3_paypal_recover(&$params) {
  $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($params['pp_id'], $params['mode']);
  $paypal = new CRM_Core_Payment_PayPalImpl($params['mode'], $paymentProcessor);

  $recovery_result = ['found' => [], 'not_processed' => [], 'processed' => [], 'count' => 0];
  $limit = $params['limit'];
  do {
    try {
      $search_result = _paypal_search_transactions($params['start'], $params['end'], $paypal);
    } catch(Exception $e) {
      return civicrm_api3_create_error("Error while retrieving list of transactions: [" . $e->getCode() . "] " . $e->getMessage(), $params);
    }
    $iteration_result = _paypal_recover_transactions($search_result, $paypal, $params['pp_id'], $limit);

    foreach ($iteration_result as $key => $result) {
      $recovery_result['count'] += count($result);
      $recovery_result[$key] = array_merge($recovery_result[$key], $result);
      if ($limit !== NULL && $key === 'processed') {
        $limit -= count($result);
      }
    }

    // Paypal returns max 100 rows, and warns with an error code if more rows are available
    // If so, we search again from latest timestamp (this assumes they are ordered by timestamp!)
    if ($search_result['l_errorcode0'] == '11002') {
      $params['end'] = $search_result['l_timestamp99'];
    }
  } while ($search_result['l_errorcode0'] == '11002');
  
  return civicrm_api3_create_success($recovery_result, $params);
}

function _paypal_search_transactions($start, $end, $paypal) {
  $search_args = [];
  $paypal->initialize($search_args, 'TransactionSearch');
  $search_args['startdate'] = $start;
  $search_args['enddate'] = $end;
  return $paypal->invokeAPI($search_args);
}

function _paypal_recover_transactions($search_result, $paypal, $pp_id, $limit) {
  $row = 0;
  $result = [];
  $processed_count = 0;
  while (array_key_exists("l_transactionid$row", $search_result) && ($limit === NULL || $processed_count < $limit)) {
    $trxn_id = $search_result["l_transactionid$row"];
    $trxn_type = $search_result["l_type$row"];
    $dao = new CRM_Contribute_DAO_Contribution();
    $dao->trxn_id = $trxn_id;
    if ($dao->find(TRUE)) {
      $result['found'][] = "$trxn_type $trxn_id ({$search_result['l_timestamp'.$row]})";
    } else if (in_array($trxn_type, ['Fee Reversal', 'Transfer', 'Refund'])
               || strpos($trxn_id, 'I-') === 0) { //This is a subscription being cancelled, there is no actual transaction
      //TODO Cancel recurring donation if can be found?
      $result['not_processed'][] = "$trxn_type $trxn_id ({$search_result['l_timestamp'.$row]})";
    } else {
      $trxn_args = [];
      $paypal->initialize($trxn_args, 'GetTransactionDetails');
      $trxn_args['transactionId'] = $trxn_id;
      try {
        $trxn = $paypal->invokeAPI($trxn_args);
        $recovery = _paypal_recover_transaction($trxn, $search_result, $row, $pp_id);
        $result[$recovery['status']][] = $recovery['details'];
        if ($recovery['status'] === 'processed') {
          $processed_count++;
        }
      } catch(Exception $e) {
        $result['not_processed'][] = "Errored (" . $e->getMessage() . ") $trxn_type $trxn_id";
      }
    }
    $row++;
  }
  return $result;
}

function _paypal_recover_transaction($trxn, &$search_result, $row, $pp_id) {
  if (isset($trxn['invnum'])) {
    return [ 'status' => 'not_processed', 'details' => "Transaction {$trxn['transactionid']} has an invoice" ];
  } else if ($trxn['transactiontype'] != 'recurring_payment') {
    return [
      'status' => 'not_processed',
      'details' => [
        'trxn' => $trxn, 
        'search_result' => array_filter($search_result, function($key) use ($row) { return (substr($key, -strlen("$row")) == "$row"); }, ARRAY_FILTER_USE_KEY)
      ]
    ];
  } else if ($trxn['paymentstatus'] != 'Completed') {
    return [ 'status' => 'not_processed', 'details' => "Recurring payment {$trxn['transactionid']} has status {$trxn['paymentstatus']}" ];
  } else {
    try {
      $recurring_contrib = _paypal_repeat_params_from_email($trxn, $pp_id);
    } catch (Exception $e) {
      if ($e->getCode() === ERR_EMAIL) {
        try {
          $recurring_contrib = _paypal_repeat_params_from_name($trxn, $pp_id);
        } catch (Exception $e) {
          return [ 'status' => 'not_processed', 'details' => "{$e->getMessage()} and missing email {$trxn['email']} for transaction {$trxn['transactionid']}" ];
        }
      } else {
        return [ 'status' => 'not_processed', 'details' => $e->getMessage() ];
      }
      //Let's record the not found email as billing email
			civicrm_api3('Email', 'create', [
				'contact_id' => $recurring_contrib['contact_id'],
				'email' => $trxn['email'],
				'is_billing' => 1, 'is_primary' => 0, 'location_type_id' => "Billing",
			]);
    }

    $repeat_params = [
      'contribution_recur_id' => $recurring_contrib['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => $trxn['transactionid'],
      'receive_date' => $trxn['ordertime'],
      'is_email_receipt' => FALSE,
    ];
    $repeat_result = civicrm_api3('Contribution', 'repeattransaction', $repeat_params);
    if ($repeat_result['is_error']) {
      return [ 'status' => 'not_processed', 'details' => $repeat_result ];
    } else {
      return [ 'status' => 'processed', 'details' => [ 
          'contact_id' => $recurring_contrib['contact_id'],
          'rc_id' => $recurring_contrib['id'],
          'id' => $repeat_result['id'],
          'trxn_id' => $trxn['transactionid'],
        ]
      ];
    }
  }
}

function _paypal_repeat_params_from_email($trxn, $pp_id) {
	$email = civicrm_api3('Email', 'get', [ 'email' => $trxn['email'], 'is_primary' => 1, 'sequential' => 1]);
	if ($email['count'] != 1) {
		throw new Exception("Missing or duped email {$trxn['email']} for {$trxn['transactionid']}", ERR_EMAIL);
	}

	$email = $email['values'][0];
	$recurring_contrib = civicrm_api3('ContributionRecur', 'get', [
		'contact_id' => $email['contact_id'],
		'amount' => $trxn['amt'],
		'payment_processor_id' => $pp_id,
		'sequential' => 1,
	]);
	if ($recurring_contrib['count'] != 1) {
		throw new Exception("Missing or duped contrib for contact id {$email['contact_id']} and transaction {$trxn['transactionid']}");
	}

  return $recurring_contrib['values'][0];
}

function _paypal_repeat_params_from_name($trxn, $pp_id) {
	$recurring_contrib = civicrm_api3('ContributionRecur', 'get', [
		'sequential' => 1,
		'amount' => $trxn['amt'],
		'payment_processor_id' => $pp_id,
		'contact_id.first_name' => $trxn["firstname"],
		'contact_id.last_name' => $trxn["lastname"],
	]);
	if ($recurring_contrib['count'] != 1) {
		throw new Exception("Missing or duped name");
	}

  return $recurring_contrib['values'][0];
}

function _civicrm_api3_paypal_query_spec(&$spec) {
  $spec['verb'] = [
    'name' => 'verb',
    'title' => ts('API verb'),
    'description' => 'Name of the Paypal NVP API function to call',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['pp_id'] = [
    'name' => 'pp_id',
    'title' => ts('Payment processor id'),
    'description' => 'Payment processor id',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['mode'] = [
    'name' => 'mode',
    'title' => ts('Payment mode'),
    'description' => 'Payment mode: live or test',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => 'live',
  ];
}

function civicrm_api3_paypal_query($params) {
  $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($params['pp_id'], $params['mode']);
  $paypal = new CRM_Core_Payment_PayPalImpl($params['mode'], $paymentProcessor);

  $paypal_args = [];
  $paypal->initialize($paypal_args, $params['verb']);
  foreach ($params as $param => $value) {
    if (!in_array($param, ['pp_id', 'mode', 'verb'])) {
      $paypal_args[$param] = $value;
    }
  }

  try {
    $response = $paypal->invokeAPI($paypal_args);
    return civicrm_api3_create_success($response, $paypal_args);
  } catch(Exception $e) {
    $str_args = print_r($paypal_args, TRUE);
    return civicrm_api3_create_error("$str_args: " . $e->getMessage());
  }
}
