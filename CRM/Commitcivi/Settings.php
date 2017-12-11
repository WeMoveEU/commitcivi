<?php

class CRM_Commitcivi_Settings {

  const CACHE_PREFIX = 'eu.wemove.commitcharge';

  /**
   * Get payment processor id.
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function paymentProcessorId() {
    $key = self::CACHE_PREFIX . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = CRM_Commitcivi_Utils_PaymentProcessor::set();
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

}
