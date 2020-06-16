<?php

class CRM_Commitcivi_Logic_DonationStripe extends CRM_Commitcivi_Logic_Donation {

  /**
   * @param \CRM_Commitcivi_Model_Event $event
   * @param int $contactId
   * @param int $campaignId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function stripe(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
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
    if (!$recur = $this->findRecurring($event->donation->recurringId)) {
      $recur = $this->recurring($event, $contactId, $campaignId);
    }
    return $recur['id'];
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
      $result = $this->single($event, $contactId, $campaignId, $recurId);
      $this->singleUtm($event, $result['id']);
      return $result;
    }
    return $contrib;
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
    $paymentProcessorId = CRM_Commitcivi_Logic_Settings::paymentProcessorIdCard();
    $params = [
      'sequential' => 1,
      'source_contact_id' => $contactId,
      'contact_id' => $contactId,
      'contribution_campaign_id' => $campaignId,
      'financial_type_id' => $this->financialTypeId,
      'payment_instrument_id' => $this->paymentInstrumentId,
      'payment_processor_id' => $paymentProcessorId,
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
    return civicrm_api3('Contribution', 'create', $params);
  }

  /**
   * @param \CRM_Commitcivi_Model_Event $event
   * @param int $id
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function singleUtm(CRM_Commitcivi_Model_Event $event, $id) {
    $params = array(
      'sequential' => 1,
      'id' => $id,
    );
    $params = $this->setSourceFields($params, $event->utm);
    if (count($params) > 2) {
      civicrm_api3('Contribution', 'create', $params);
    }
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
    $paymentProcessorId = CRM_Commitcivi_Logic_Settings::paymentProcessorIdCard();
    $params = [
      'sequential' => 1,
      'contact_id' => $contactId,
      'amount' => $event->donation->amount,
      'currency' => $event->donation->currency,
      'frequency_unit' => $this->frequencyUnit,
      'frequency_interval' => $this->frequencyInterval,
      'start_date' => $event->createDate,
      'create_date' => $event->createDate,
      'trxn_id' => $event->donation->recurringId,
      'contribution_status_id' => $this->recurringStatus($event->donation->status),
      'financial_type_id' => $this->financialTypeId,
      'payment_instrument_id' => $this->paymentInstrumentId,
      'payment_processor_id' => $paymentProcessorId,
      'campaign_id' => $campaignId,
    ];
    if ($event->donation->status == 'destroy') {
      $params['cancel_date'] = $event->createDate;
    }
    $params = $this->setRecurSourceFields($params, $event->utm);
    return civicrm_api3('ContributionRecur', 'create', $params);
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

  /**
   * Determine contribution status for recurring based on status from param.
   *
   * @param string $status
   *
   * @return mixed
   */
  private function recurringStatus($status) {
    return $this->mapRecurringStatus[$status];
  }

}
