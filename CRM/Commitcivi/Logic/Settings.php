<?php

class CRM_Commitcivi_Logic_Settings {

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

}
