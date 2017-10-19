<?php

class CRM_Commitcivi_EventProcessor {
  public static function run(CRM_Commitcivi_Model_Event $event) {
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
      'external_id' => $event->externalId,
    ];
    $result = civicrm_api3('WemoveContact', 'create', $params);
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
