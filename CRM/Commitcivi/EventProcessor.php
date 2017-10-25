<?php

class CRM_Commitcivi_EventProcessor {

  public function process(CRM_Commitcivi_Model_Event $event) {
    $params = [
      'action_name' => $event->actionName,
      'external_identifier' => $event->externalIdentifier,
      'campaign_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Campaign_BAO_Campaign', 'campaign_type_id', 'Fundraising'),
    ];
    $result = civicrm_api3('WemoveCampaign', 'create', $params);
    $campaignId = $result['id'];

    $params = [
      'firstname' => $event->contact->firstname,
      'lastname' => $event->contact->lastname,
      'email' => $event->contact->email,
      'postal_code' => $event->contact->postalCode,
      'country' => $event->contact->country,
      'action_technical_type' => $event->actionTechnicalType,
      'action_type' => $event->actionType,
      'action_name' => $event->actionName,
      'create_dt' => $event->createDate,
      'external_identifier' => $event->externalIdentifier,
      'campaign_id' => $campaignId,
    ];
    $result = civicrm_api3('WemoveContact', 'create', $params);
    $contactId = $result['id'];

    $donation = new CRM_Commitcivi_Logic_Donation();
    switch ($event->donation->paymentProcessor) {
      case 'sepa':
        $result = $donation->sepa($event, $contactId, $campaignId);
        break;

      default:
        $result = $donation->create($event, $contactId, $campaignId);
        break;
    }
  }

}
