<?php

class CRM_Commitcivi_Logic_DonationSepa extends CRM_Commitcivi_Logic_Donation {

  const CYCLE_DAY_FIRST = 6;
  const CYCLE_DAY_SECOND = 21;

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
    $paymentProcessorId = CRM_Commitcivi_Logic_Settings::paymentProcessorIdSepa();
    $mandateType = 'RCUR';
    $params_mandate = [
      'sequential' => 1,
      'contact_id' => $contactId,
      'type' => $mandateType,
      'iban' => $event->donation->iban,
      'bic' => $event->donation->bic,
      'start_date' => $event->createDate,
      'creation_date' => $event->createDate,
      'cycle_day' => $this->cycleDay($event->createDate),
      'amount' => $event->donation->amount,
      'currency' => $event->donation->currency,
      'frequency_interval' => $this->frequencyInterval,
      'financial_type_id' => $this->financialTypeId,
      'payment_processor_id' => $paymentProcessorId,
      'campaign_id' => $campaignId,
      'trxn_id' => $event->donation->recurringId,
      'source' => $event->actionName,
    ];
    $result = civicrm_api3('SepaMandate', 'createfull', $params_mandate);
    $this->setRecurUtms($event, $result['values'][0]['entity_id']);
    return $result;
  }

  /**
   * @param $date
   *
   * @return string
   */
  public function cycleDay($date) {
    $dt = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date);
    $day = $dt->format('d');
    if ($day >= self::CYCLE_DAY_SECOND || $day < self::CYCLE_DAY_FIRST) {
      return self::CYCLE_DAY_FIRST;
    }
    return self::CYCLE_DAY_SECOND;
  }

  /**
   * @param $date
   *
   * @return string
   */
  public function cycleDate($date) {
    $dt = DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $date);
    $day = $dt->format('d');
    if ($day >= self::CYCLE_DAY_SECOND) {
      return $dt->modify('+1 month')->format('Y-m') . '-' . sprintf('%02d', self::CYCLE_DAY_FIRST);
    }
    elseif ($day >= self::CYCLE_DAY_FIRST) {
      return $dt->format('Y-m') . '-' . sprintf('%02d', self::CYCLE_DAY_SECOND);
    }
    return $dt->format('Y-m') . '-' . sprintf('%02d', self::CYCLE_DAY_FIRST);
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
      'creation_date' => $event->createDate,
      'amount' => $event->donation->amount,
      'currency' => $event->donation->currency,
      'frequency_interval' => $this->frequencyInterval,
      'financial_type_id' => $this->financialTypeId,
      'payment_processor_id' => $paymentProcessorId,
      'campaign_id' => $campaignId,
      'trxn_id' => $event->donation->transactionId,
      'source' => $event->actionName,
    ];
    $result = civicrm_api3('SepaMandate', 'createfull', $params_mandate);
    $this->setUtms($event, $result['values'][0]['entity_id']);
    return $result;
  }

}
