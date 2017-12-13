<?php

class CRM_Commitcivi_Logic_Country extends CRM_Commitcivi_Logic_Cache {

  const TYPE_COUNTRY = 'country';

  /**
   * Get country id by iso code.
   *
   * @param int $isoCode ISO code from civicrm_campaign.iso_code
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function getId($isoCode) {
    if ($cache = self::get(self::TYPE_COUNTRY, 0)) {
      return CRM_Utils_Array::value($isoCode, $cache[self::TYPE_COUNTRY], 0);
    }
    $params = array(
      'sequential' => 1,
      'return' => 'id,iso_code',
      'options' => array('limit' => 0),
    );
    $result = civicrm_api3('Country', 'get', $params);
    $countries = array();
    foreach ($result['values'] as $country) {
      $countries[$country['iso_code']] = $country['id'];
    }
    self::set(self::TYPE_COUNTRY, 0, $countries);
    return CRM_Utils_Array::value($isoCode, $countries, 0);
  }

}
