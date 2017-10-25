<?php
function _civicrm_api3_wemove_campaign_create_spec(&$spec) {
  $spec['action_name'] = [
    'name' => 'action_name',
    'title' => 'Action name',
    'description' => 'Action name where last "-XX" chars determines language of campaign',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['external_identifier'] = [
    'name' => 'external_identifier',
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

function civicrm_api3_wemove_campaign_create($params) {
  $campaign = CRM_Commitcivi_Logic_CampaignCache::getByExternalId($params);
  return civicrm_api3_create_success([$campaign['id'] => $campaign], $params);
}
