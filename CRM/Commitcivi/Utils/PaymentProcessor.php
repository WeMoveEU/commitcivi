<?php

class CRM_Commitcivi_Utils_PaymentProcessor {

  /**
   * @param int $isTest
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function set($isTest = 0) {
    $params = [
      'sequential' => 1,
      'name' => "CommitChange",
      'domain_id' => 1,
      'is_test' => $isTest,
    ];
    $result = civicrm_api3('PaymentProcessor', 'get', $params);
    if ($result['count'] == 0) {
      $params = [
        'sequential' => 1,
        'payment_processor_type_id' => "Dummy",
        'domain_id' => 1,
        'name' => "CommitChange",
        'description' => "Dummy processor for CommitChange",
        'is_active' => 1,
        'is_default' => 0,
        'is_test' => $isTest,
        'user_name' => "cc",
        'payment_instrument_id' => "Credit Card",
      ];
      $result = civicrm_api3('PaymentProcessor', 'create', $params);
    }
    return $result['values'][0]['id'];
  }

}
