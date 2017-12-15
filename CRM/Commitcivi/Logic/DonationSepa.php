<?php

class CRM_Commitcivi_Logic_DonationSepa extends CRM_Commitcivi_Logic_Donation {

  const CYCLE_DAY = 21;

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
    if ($this->isRecurring($event->donation->type)) {
      $sepaMandate = $this->setRecurring($event, $contactId, $campaignId);
      $recurId = $sepaMandate['values'][0]['entity_id'];
      $first = $this->setFirst($recurId, $event, $contactId, $campaignId);
      $this->match($first['id'], $sepaMandate['id']);
      return $sepaMandate;
    }
    else {
      return $this->setSingle($event, $contactId, $campaignId);
    }
  }

  /**
   * @param \CRM_Commitcivi_Model_Event $event
   * @param $contactId
   * @param $campaignId
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function setRecurring(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
    if (!$recur = $this->findRecurring($event->donation->recurringId)) {
      $recur = $this->recurring($event, $contactId, $campaignId);
    }
    return $recur;
  }

  /**
   * @param \CRM_Commitcivi_Model_Event $event
   * @param $contactId
   * @param $campaignId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function recurring(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
    $paymentProcessorId = CRM_Commitcivi_Logic_Settings::paymentProcessorIdSepa();
    $mandateType = 'RCUR';
    $params_mandate = [
      'sequential' => 1,
      'contact_id' => $contactId,
      'type' => $mandateType,
      'iban' => $event->donation->iban,
      'bic' => $event->donation->bic,
      'start_date' => $event->createDate,
      'create_date' => $event->createDate,
      'cycle_day' => self::CYCLE_DAY,
      'amount' => $event->donation->amount,
      'currency' => $event->donation->currency,
      'frequency_interval' => $this->frequencyInterval,
      'financial_type_id' => $this->financialTypeId,
      'payment_processor_id' => $paymentProcessorId,
      'campaign_id' => $campaignId,
      'trxn_id' => $event->donation->recurringId,
      'source' => $event->actionName,
    ];
    return civicrm_api3('SepaMandate', 'createfull', $params_mandate);
  }

  /**
   * @param $recurId
   * @param \CRM_Commitcivi_Model_Event $event
   * @param $contactId
   * @param $campaignId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function setFirst($recurId, CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
    $params = [
      'sequential' => 1,
      'source_contact_id' => $contactId,
      'contact_id' => $contactId,
      'contribution_recur_id' => $recurId,
      'contribution_campaign_id' => $campaignId,
      'financial_type_id' => $this->financialTypeId,
      'payment_instrument_id' => $this->paymentInstrumentId,
      'receive_date' => substr($event->createDate, 0, 7) . '-' . self::CYCLE_DAY,
      'total_amount' => $event->donation->amount,
      'fee_amount' => $event->donation->amountCharged,
      'net_amount' => ($event->donation->amount - $event->donation->amountCharged),
      'trxn_id' => $event->donation->transactionId,
      'contribution_status_id' => 'Pending',
      'currency' => $event->donation->currency,
      'subject' => $event->actionName,
      'source' => $event->actionName,
      'location' => $event->actionTechnicalType,
    ];
    $params = $this->setSourceFields($params, $event->utm);
    return civicrm_api3('Contribution', 'create', $params);
  }

  /**
   * @param $contributionFirstId
   * @param $sepaMandateId
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function match($contributionFirstId, $sepaMandateId) {
    $params = [
      'sequential' => 1,
      'id' => $sepaMandateId,
      'first_contribution_id' => $contributionFirstId,
    ];
    civicrm_api3('SepaMandate', 'create', $params);
  }

  /**
   * @param \CRM_Commitcivi_Model_Event $event
   * @param $contactId
   * @param $campaignId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function setSingle(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
    if (!$contrib = $this->find($event->donation->transactionId)) {
      return $this->single($event, $contactId, $campaignId);
    }
    return $contrib;
  }

  /**
   * @param \CRM_Commitcivi_Model_Event $event
   * @param $contactId
   * @param $campaignId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function single(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
    $paymentProcessorId = CRM_Commitcivi_Logic_Settings::paymentProcessorIdSepa();
    $mandateType = 'OOFF';
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
      'frequency_interval' => $this->frequencyInterval,
      'financial_type_id' => $this->financialTypeId,
      'payment_processor_id' => $paymentProcessorId,
      'campaign_id' => $campaignId,
      'trxn_id' => $event->donation->transactionId,
      'source' => $event->actionName,
    ];
    return civicrm_api3('SepaMandate', 'createfull', $params_mandate);
  }

}
