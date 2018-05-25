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
   * Get opt_in setting.
   *
   * @return mixed
   */
  public static function optIn() {
    return Civi::settings()->get('opt_in');
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

  public static function fieldConsentIds() {
    return CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_campaign_consent_ids');
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
   * Get activity type id for Join.
   *
   * @return mixed
   */
  public static function joinActivityTypeId() {
    return Civi::settings()->get('activity_type_join');
  }

  public static function dpaActivityTypeId() {
    return CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SLA Acceptance');
  }

  /**
   * Language group name suffix.
   *
   * @return mixed
   */
  public static function languageGroupNameSuffix() {
    return Civi::settings()->get('language_group_name_suffix');
  }

  /**
   * Default language group id.
   *
   * @return mixed
   */
  public static function defaultLanguageGroupId() {
    return (int) Civi::settings()->get('default_language_group_id');
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
