<?php

class CRM_Commitcivi_EventProcessor {
  public static function run(CRM_Commitcivi_Model_Event $event) {
    // todo create contact
    $contactId = 0;

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
