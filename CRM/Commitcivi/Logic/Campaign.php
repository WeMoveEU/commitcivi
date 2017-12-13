<?php

class CRM_Commitcivi_Logic_Campaign {

  /**
   * Get campaign by external identifier or CiviCRM Id.
   *
   * @param int $id External identifier (default) or local civicrm_campaign.id
   * @param bool $useLocalId Use local id or external id (default)
   * @param bool $countActivities
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function get($id, $useLocalId = FALSE, $countActivities = TRUE) {
    if ($id) {
      if ($useLocalId) {
        $field = 'id';
      }
      else {
        $field = 'external_identifier';
      }
      $params = array(
        'sequential' => 1,
        $field => $id,
      );
      if ($countActivities) {
        $params['api.Activity.getcount'] = array(
          'campaign_id' => '$value.id',
        );
      }
      $result = civicrm_api3('Campaign', 'get', $params);
      if ($result['count'] == 1) {
        return $result['values'][0];
      }
    }
    return array();
  }

  /**
   * Setting up new campaign in CiviCRM.
   *
   * @param $params
   *
   * @return array
   * @throws CiviCRM_API3_Exception
   */
  public function set($params) {
    $params = array(
      'sequential' => 1,
      'name' => $params['action_name'],
      'title' => $params['action_name'],
      'description' => $params['action_name'],
      'external_identifier' => $params['external_identifier'],
      'campaign_type_id' => $params['campaign_type_id'],
      'start_date' => date('Y-m-d H:i:s'),
      CRM_Commitcivi_Logic_Settings::fieldLanguage() => $this->determineLanguage($params['action_name']),
    );
    $result = civicrm_api3('Campaign', 'create', $params);
    return $result['values'][0];
  }

  /**
   * Determine whether $campaign array has a valid structure.
   *
   * @param array $campaign
   *
   * @return bool
   */
  public function isValidCampaign($campaign) {
    if (
      is_array($campaign) &&
      array_key_exists('id', $campaign) &&
      $campaign['id'] > 0
    ) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine language (locale) based on campaign name which have to include country on the end, ex. *_EN.
   *
   * @param $campaignName
   *
   * @return string
   */
  public function determineLanguage($campaignName) {
    $re = "/(.*)[_\\- ]([a-zA-Z]{2})$/";
    if (preg_match($re, $campaignName, $matches)) {
      $country = strtoupper($matches[2]);
      $countryLangMapping = CRM_Commitcivi_Logic_Settings::countryLanguageMapping();
      if (array_key_exists($country, $countryLangMapping)) {
        return $countryLangMapping[$country];
      }
    }
    return CRM_Commitcivi_Logic_Settings::defaultLanguage();
  }

}
