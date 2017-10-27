<?php

class CRM_Commitcivi_Logic_Donation {

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
      'campaign_id' => $campaignId,
    ];
    return civicrm_api3('SepaMandate', 'createfull', $params_mandate);
  }

  /**
   * @param \CRM_Commitcivi_Model_Event $event
   * @param int $contactId
   * @param int $campaignId
   *
   * @return array
   */
  public function create(CRM_Commitcivi_Model_Event $event, $contactId, $campaignId) {
    $financialTypeId = 1;
    $paymentInstrumentId = "Credit Card";
    $params = [
      'sequential' => 1,
      'source_contact_id' => $contactId,
      'contact_id' => $contactId,
      'contribution_campaign_id' => $campaignId,
      'financial_type_id' => $financialTypeId,
      'payment_instrument_id' => $paymentInstrumentId,
      'receive_date' => $event->createDate,
      'total_amount' => $event->donation->amount,
      'fee_amount' => $event->donation->amountCharged,
      'net_amount' => ($event->donation->amount - $event->donation->amountCharged),
      'trxn_id' => $event->donation->transactionId,
      'contribution_status' => $this->status($event->donation->status),
      'currency' => $event->donation->currency,
      'subject' => $event->actionName,
      'location' => $event->actionTechnicalType,
    ];
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
