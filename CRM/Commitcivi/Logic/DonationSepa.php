<?php

class CRM_Commitcivi_Logic_DonationSepa extends CRM_Commitcivi_Logic_Donation {

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
    $event->donation->recurringId;
    if ($this->isRecurring($event->donation->type)) {
      return $this->setRecurring($event, $contactId, $campaignId);
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
    $paymentProcessorId = CRM_Commitcivi_Logic_Settings::paymentProcessorId();
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
    $paymentProcessorId = CRM_Commitcivi_Logic_Settings::paymentProcessorId();
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
