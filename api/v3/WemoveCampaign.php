<?php
function _civicrm_api3_wemove_campaign_set_spec($params) {
}

function civicrm_api3_wemove_campaign_set($params) {
  // todo move to cache
  $campaignObj = new CRM_Commitcivi_Logic_Campaign();
  $campaign = $campaignObj->get($params['external_id'], FALSE, FALSE);
  if ($campaignObj->isValidCampaign($campaign)) {
    civicrm_api3_create_success($campaign, $params);
  }
  else {
    $campaign = $campaignObj->set($params);
    civicrm_api3_create_success($campaign, $params);
  }
}
