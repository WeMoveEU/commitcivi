<?php

class CRM_Commitcivi_Logic_CampaignCache extends CRM_Commitcivi_Logic_Cache {

  const TYPE_CAMPAIGN_LOCAL = 'campaign-local';

  const TYPE_CAMPAIGN_EXTERNAL = 'campaign-external';


  /**
   * Get campaign by local id.
   *
   * @param int $id civicrm_campaign.id
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function getByLocalId($id) {
    if ($cache = self::get(self::TYPE_CAMPAIGN_LOCAL, $id)) {
      return $cache[self::TYPE_CAMPAIGN_LOCAL];
    }
    $campaignObj = new CRM_Commitcivi_Logic_Campaign();
    $campaign = $campaignObj->get($id, TRUE);
    self::set(self::TYPE_CAMPAIGN_LOCAL, $id, $campaign);
    return $campaign;
  }


  /**
   * Get campaign by external identifier.
   *
   * @param array $params
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function getByExternalId($params) {
    if ($cache = self::get(self::TYPE_CAMPAIGN_EXTERNAL, $params['external_identifier'])) {
      return $cache[self::TYPE_CAMPAIGN_EXTERNAL];
    }
    $campaignObj = new CRM_Commitcivi_Logic_Campaign();
    $campaign = $campaignObj->get($params['external_identifier']);
    if (!$campaignObj->isValidCampaign($campaign)) {
      $campaign = $campaignObj->set($params);
      CRM_Core_PseudoConstant::flush();
    }
    // todo move limit to settings
    $limit = 20;
    if (CRM_Utils_Array::value('api.Activity.getcount', $campaign, 0) >= $limit) {
      self::set(self::TYPE_CAMPAIGN_EXTERNAL, $params['external_identifier'], $campaign);
    }
    return $campaign;
  }

}
