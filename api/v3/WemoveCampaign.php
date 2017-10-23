<?php
function _civicrm_api3_wemove_campaign_set_spec(&$spec) {
  $spec['action_name'] = [
    'name' => 'action_name',
    'title' => 'Action name',
    'description' => 'Action name where last "-XX" chars determines language of campaign',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['external_id'] = [
    'name' => 'external_id',
    'title' => ts('Campaign External ID'),
    'description' => 'Unique trusted external ID',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['campaign_type_id'] = [
    'name' => 'campaign_type_id',
    'title' => ts('Campaign Type'),
    'description' => ts('Campaign Type'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
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
