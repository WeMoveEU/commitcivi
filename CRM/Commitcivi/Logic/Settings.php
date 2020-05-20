<?php

class CRM_Commitcivi_Logic_Settings {

  const CACHE_PREFIX = 'eu.wemove.commitcharge';
  const PAYMENT_PROCESSOR_CARD = 'CommitChange-card';
  const PAYMENT_PROCESSOR_SEPA = 'CommitChange-sepa';

  /**
   * Get id of Members group.
   *
   * @return mixed
   */
  public static function groupId() {
    return Civi::settings()->get('group_id');
  }

  /**
   * Get mapping array between country and language (locale).
   *
   * @return mixed
   */
  public static function countryLanguageMapping() {
    return Civi::settings()->get('country_lang_mapping');
  }

  /**
   * Get default language (locale).
   *
   * @return mixed
   */
  public static function defaultLanguage() {
    return Civi::settings()->get('default_language');
  }

  /**
   * Get custom field name for language (locale).
   *
   * @return mixed
   */
  public static function fieldLanguage() {
    return Civi::settings()->get('field_language');
  }

  /**
   * Get anonymous contact id.
   *
   * @return mixed
   */
  public static function anonymousId() {
    return Civi::settings()->get('anonymous_id');
  }

  /**
   * Language tag name prefix.
   *
   * @return mixed
   */
  public static function languageTagNamePrefix() {
    return Civi::settings()->get('language_tag_name_prefix');
  }

  /**
   * Get payment processor id for Sepa.
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function paymentProcessorIdSepa() {
    $key = self::CACHE_PREFIX . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = CRM_Commitcivi_Utils_PaymentProcessor::set(self::PAYMENT_PROCESSOR_SEPA);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get payment processor id for Stripe(Card)
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function paymentProcessorIdCard() {
    $key = self::CACHE_PREFIX . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = CRM_Commitcivi_Utils_PaymentProcessor::set(self::PAYMENT_PROCESSOR_CARD);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

}
