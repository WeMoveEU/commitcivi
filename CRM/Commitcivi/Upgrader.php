<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Commitcivi_Upgrader extends CRM_Commitcivi_Upgrader_Base {

  /**
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public function upgrade_011_payment_processor() {
    CRM_Commitcivi_Utils_PaymentProcessor::set(0);
    CRM_Commitcivi_Utils_PaymentProcessor::set(1);
    return TRUE;
  }

}
