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
      return $this->single($event, $contactId, $campaignId, $recurId);
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
  private function determineRecurringStatus($status) {
    return $this->mapRecurringStatus[$status];
  }

}
