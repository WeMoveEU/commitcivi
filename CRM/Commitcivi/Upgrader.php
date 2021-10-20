<?php

/**
 * Collection of upgrade steps.
 */
class CRM_Commitcivi_Upgrader extends CRM_Commitcivi_Upgrader_Base {

  /**
   * @return bool
   */
  public function upgrade_011_payment_processor() {
    // comment because of change of method definition
    // CRM_Commitcivi_Utils_PaymentProcessor::set(0);
    // CRM_Commitcivi_Utils_PaymentProcessor::set(1);
    return TRUE;
  }

  /**
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public function upgrade_012_payment_processors() {
    CRM_Commitcivi_Utils_PaymentProcessor::set(CRM_Commitcivi_Logic_Settings::PAYMENT_PROCESSOR_SEPA, 0);
    CRM_Commitcivi_Utils_PaymentProcessor::set(CRM_Commitcivi_Logic_Settings::PAYMENT_PROCESSOR_SEPA, 1);
    CRM_Commitcivi_Utils_PaymentProcessor::set(CRM_Commitcivi_Logic_Settings::PAYMENT_PROCESSOR_CARD, 0);
    CRM_Commitcivi_Utils_PaymentProcessor::set(CRM_Commitcivi_Logic_Settings::PAYMENT_PROCESSOR_CARD, 1);
    return TRUE;
  }
  
  /**
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public function upgrade_013_weekly_custom_fields() {
    // create custom field for marking a recurring donation weekly
    $this->executeCustomDataFile("xml/weekly_custom_fields.xml");
    return TRUE;
  }

}
