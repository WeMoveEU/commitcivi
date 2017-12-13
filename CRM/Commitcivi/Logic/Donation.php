<?php

class CRM_Commitcivi_Logic_Donation {

  private $frequencyInterval = 1;

  private $frequencyUnit = 'month';

  private $financialTypeId = 1;

  private $paymentInstrumentId = "Credit Card";

  private static $mapRecurringStatus = [
    'success' => 5, // in progress
    'destroy' => 3, // cancelled
  ];

  /**
   * Check if donation is recurring.
   *
   * @param string $donationType
   *
   * @return bool
   */
  private function isRecurring($donationType) {
    return $donationType == CRM_Commitcivi_Model_Donation::TYPE_RECURRING;
  }

  /**
   * Create a mandate for contact.
   *
   * @param \CRM_Commitcivi_Model_Event $event
   * @param int $contactId
   * @param int $campaignId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function sepa(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
    $financialTypeId = 1; // Donation
    $frequencyInterval = 1;
    $paymentProcessorId = CRM_Commitcivi_Settings::paymentProcessorId();
    $mandateType = 'RCUR';
    $params_mandate = [
      'sequential' => 1,
      'contact_id' => $contactId,
      'type' => $mandateType,
      'iban' => $event->donation->iban,
      'bic' => $event->donation->bic,
      'start_date' => $event->createDate,
      'create_date' => date('Y-m-d'),
      'amount' => $event->donation->amount,
      'currency' => $event->donation->currency,
      'frequency_interval' => $frequencyInterval,
      'financial_type_id' => $financialTypeId,
      'payment_processor_id' => $paymentProcessorId,
      'campaign_id' => $campaignId,
      'source' => $event->actionName,
    ];
    return civicrm_api3('SepaMandate', 'createfull', $params_mandate);
  }

  /**
   * @param \CRM_Commitcivi_Model_Event $event
   * @param int $contactId
   * @param int $campaignId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function create(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
    if ($this->isRecurring($event->donation->type)) {
      $recurId = $this->setRecurring($event, $contactId, $campaignId);
      return $this->setSingle($event, $contactId, $campaignId, $recurId);
    }
    else {
      return $this->setSingle($event, $contactId, $campaignId);
    }
  }

  /**
   * Set recurring contribution.
   *
   * @param \CRM_Commitcivi_Model_Event $event
   * @param int $contactId
   * @param int $campaignId
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function setRecurring(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
    if (!$recur = self::findRecurring($event->donation->transactionId)) {
      $recur = self::recurring($event, $contactId, $campaignId);
    }
    return $recur['id'];
  }

  /**
   * Find recurring contribution by unique transaction id.
   *
   * @param string $recurringId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function findRecurring($recurringId) {
    $params = [
      'sequential' => 1,
      'trxn_id' => $recurringId,
    ];
    $result = civicrm_api3('ContributionRecur', 'get', $params);
    if ($result['count'] == 1) {
      return $result;
    }
    return [];
  }

  /**
   * Create new recurring contribution.
   *
   * @param \CRM_Commitcivi_Model_Event $event
   * @param int $contactId
   * @param int $campaignId
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function recurring(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
    $params = [
      'sequential' => 1,
      'contact_id' => $contactId,
      'amount' => $event->donation->amount,
      'currency' => $event->donation->currency,
      'frequency_unit' => $this->frequencyUnit,
      'frequency_interval' => $this->frequencyInterval,
      'start_date' => $event->createDate,
      'create_date' => $event->createDate,
      'trxn_id' => $event->donation->transactionId,
      'contribution_status_id' => $this->determineRecurringStatus($event->donation->status),
      'financial_type_id' => $this->financialTypeId,
      'payment_instrument_id' => $this->paymentInstrumentId,
      'campaign_id' => $campaignId,
    ];
    if ($event->donation->status == 'destroy') {
      $params['cancel_date'] = $event->createDate;
    }
    return civicrm_api3('ContributionRecur', 'create', $params);
  }

  /**
   * Determine contribution status for recurring based on status from param.
   *
   * @param string $status
   *
   * @return mixed
   */
  private function determineRecurringStatus($status) {
    return self::$mapRecurringStatus[$status];
  }

  /**
   * @param \CRM_Commitcivi_Model_Event $event
   * @param $contactId
   * @param $campaignId
   * @param int $recurId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function setSingle(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId, $recurId = 0) {
    if (!$contrib = $this->find($event->donation->transactionId)) {
      return $this->single($event, $contactId, $campaignId, $recurId);
    }
    return $contrib;
  }

  /**
   * Find contribution by unique transaction id.
   *
   * @param string $transactionId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function find($transactionId) {
    $params = [
      'sequential' => 1,
      'trxn_id' => $transactionId,
    ];
    $result = civicrm_api3('Contribution', 'get', $params);
    if ($result['count'] == 1) {
      return $result;
    }
    return [];
  }

  /**
   * @param \CRM_Commitcivi_Model_Event $event
   * @param $contactId
   * @param $campaignId
   * @param int $recurId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function single(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId, $recurId = 0) {
    $params = [
      'sequential' => 1,
      'source_contact_id' => $contactId,
      'contact_id' => $contactId,
      'contribution_campaign_id' => $campaignId,
      'financial_type_id' => $this->financialTypeId,
      'payment_instrument_id' => $this->paymentInstrumentId,
      'receive_date' => $event->createDate,
      'total_amount' => $event->donation->amount,
      'fee_amount' => $event->donation->amountCharged,
      'net_amount' => ($event->donation->amount - $event->donation->amountCharged),
      'trxn_id' => $event->donation->transactionId,
      'contribution_status' => $this->status($event->donation->status),
      'currency' => $event->donation->currency,
      'subject' => $event->actionName,
      'source' => $event->actionName,
      'location' => $event->actionTechnicalType,
    ];
    if ($recurId) {
      $params['contribution_recur_id'] = $recurId;
    }
    $params = $this->setSourceFields($params, $event->utm);
    return civicrm_api3('Contribution', 'create', $params);
  }

  /**
   * Add UTM field values from event to $params as custom contribution fields
   *
   * @param array $params
   * @param \CRM_Commitcivi_Model_Utm $utm
   *
   * @return mixed
   */
  public function setSourceFields($params, CRM_Commitcivi_Model_Utm $utm) {
    $mapping = [
      'Source' => 'field_contribution_source',
      'Medium' => 'field_contribution_medium',
      'Campaign' => 'field_contribution_campaign',
      'Content' => 'field_contribution_content',
    ];
    foreach ($mapping as $field => $setting) {
      if ($utm->$field) {
        $params[Civi::settings()->get($setting)] = $utm->$field;
      }
    }
    return $params;
  }

  /**
   * Return contribution status based on status from event.
   *
   * @param string $donationStatus
   *
   * @return mixed
   */
  private function status($donationStatus) {
    $mapping = [
      'success' => 'Completed',
      'failed' => 'Failed',
    ];
    return CRM_Utils_Array::value($donationStatus, $mapping, 'Pending');
  }

}
