<?php

function _civicrm_api3_commitcivi_process_donation_spec(&$spec) {
  $spec['message'] = [
    'name' => 'message',
    'title' => "Houdini message",
    'description' => "JSON message received from Houdini about the donation to process",
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
}

function civicrm_api3_commitcivi_process_donation($params) {
  $json_msg = json_decode($params['message']);
  if ($json_msg) {
    if (property_exists($json_msg, 'migrated')) {
      return _civicrm_api3_commitcivi_handle_stripe_migration($json_msg->migrated);
    }
    $event = new CRM_Commitcivi_Model_Event($json_msg);
    $processor = new CRM_Commitcivi_EventProcessor();
    $result_code = $processor->process($event);
    if ($result_code == -1) {
      return civicrm_api3_create_error("unsupported action type: $json_msg->action_type", ['retry_later' => FALSE]);
    }
    else if ($result_code != 1) {
      $session = CRM_Core_Session::singleton();
      $retry = _commitcivi_isConnectionLostError($session->getStatus());
      return civicrm_api3_create_error("Commitcivi event processor returned error code $result_code", ['retry_later' => $retry]);
    }
    else {
      return civicrm_api3_create_success();
    }
  }
  else {
    return civicrm_api3_create_error("Could not decode {$params['message']}", ['retry_later' => FALSE]);
  }
}

function _civicrm_api3_commitcivi_handle_stripe_migration(&$migrated) {
  
  # find the current houdini recurring donation using email, processor, amount and start_date
  $query = ""
    . "SELECT recur.id recurring_id FROM civicrm_contribution_recur recur "
    . " JOIN civicrm_contact contact ON (contact.id=recur.contact_id) "
    . " JOIN civicrm_email email ON (contact.id=email.contact_id AND email.is_primary)"
    . "WHERE email.email = %1 "
    . "AND payment_processor_id = 11 "
    . "AND amount * 100 = %2 "  # units are 1s in Civi (3 euros) and 100ths (300) in Houdini 
    . "AND left(start_date, 10) = %3 "
    ;

  $query_params = [
    '1' => [$migrated->email, 'String'],
    '2' => [$migrated->amount, 'Float'],
    '3' => [$migrated->start_date, 'String']
  ];

  $result = CRM_Core_DAO::executeQuery($query, $query_params);
  // var_dump([$query, $query_params]);

  while ($result->fetch()) {
    civicrm_api3('ContributionRecur', 'cancel', [
      'id' => $result->recurring_id, 
      'cancel_reason' => "Migrated to Stripe {$migrated->stripe_subscription_id}"]
    );
  }

  return civicrm_api3_create_success();
}

function _civicrm_api3_commitcivi_update_major_donors_spec(&$spec) {
  $spec['group_id'] = [
    'name' => 'group_id',
    'title' => ts('Group id'),
    'description' => 'Id of the group of major donors',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['yearly_threshold_amount'] = [
    'name' => 'yearly_threshold_amount',
    'title' => ts('Yearly threshold amount'),
    'description' => 'Yearly donation amount defining major donors',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'api.default' => 5000,
  ];
}

function civicrm_api3_commitcivi_update_major_donors($params) {
  $donors_result = civicrm_api3('GroupContact', 'get', ['group_id' => $params['group_id'], 'status' => 'Added']);
  $donor_ids = array_map(function($d) { return $d['contact_id']; }, $donors_result['values']);

  $added = [];
  $query = ""
  . "SELECT contact_id, year(receive_date) AS year, sum(total_amount) AS amount "
  . "FROM civicrm_contribution "
  . "WHERE contribution_status_id=1 "
  . "GROUP BY contact_id, year "
  . "HAVING amount > %1"
  ;
  $query_params = ['1' => [$params['yearly_threshold_amount'], 'Integer']];
  $result = CRM_Core_DAO::executeQuery($query, $query_params);
  while ($result->fetch()) {
    if (array_search($result->contact_id, $donor_ids) === FALSE
        && array_search($result->contact_id, $added) === FALSE) {
      $gc_params = ['contact_id' => $result->contact_id, 'group_id' => $params['group_id'], 'status' => 'Added'];
      $gc_result = civicrm_api3('GroupContact', 'create', $gc_params);
      if ($gc_result['added']) {
        $added[] = $result->contact_id;
      }
    }
  }

  $returnResult = ['added' => $added, 'added_count' => count($added)];
  return civicrm_api3_create_success($returnResult, $params);
}

function _commitcivi_isConnectionLostError($sessionStatus) {
  if (is_array($sessionStatus) && array_key_exists('title', $sessionStatus[0]) && $sessionStatus[0]['title'] == 'Mailing Error') {
    return !!strpos($sessionStatus[0]['text'], 'Connection lost to authentication server');
  }
  return FALSE;
}
