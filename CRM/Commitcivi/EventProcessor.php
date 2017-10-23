<?php

class CRM_Commitcivi_EventProcessor {
  public static function run(CRM_Commitcivi_Model_Event $event) {
    $params = [
      'action_name' => $event->actionName,
      'external_identifier' => $event->externalIdentifier,
      'campaign_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Campaign_BAO_Campaign', 'campaign_type_id', 'Fundraising'),
    ];
    $result = civicrm_api3('WemoveCampaign', 'set', $params);
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
    ];
    $result = civicrm_api3('WemoveContact', 'set', $params);
    $contactId = $result['id'];

    switch ($event->donation->paymentProcessor) {
      case 'sepa':
        // todo create sepa mandate
        break;

      default:
        // todo create contribution (for stripe)
        break;
    }
  }

}
