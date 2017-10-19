<?php

class CRM_Commitcivi_Runner {
  public static function run(CRM_Commitcivi_Logic_Event $event) {
    // todo create contact
    $contactId = 0;

    switch ($event->paymentProcessor) {
      case 'sepa':
        // todo create sepa mandate
        break;

      default:
        // todo create contribution (for stripe)
        break;
    }
  }

}
