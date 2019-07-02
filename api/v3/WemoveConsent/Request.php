<?php

use CRM_Commitcivi_ExtensionUtil as E;

function _civicrm_api3_wemove_consent_request_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => E::ts('Contact Id'),
    'description' => E::ts('Contact Id'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['campaign_id'] = [
    'name' => 'campaign_id',
    'title' => E::ts('Campaign Id'),
    'description' => E::ts('Campaign Id from which will be consent id, language and others variables'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['utm_source'] = [
    'name' => 'utm_source',
    'title' => ts('utm source'),
    'description' => 'utm source',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
  $spec['utm_medium'] = [
    'name' => 'utm_medium',
    'title' => ts('utm medium'),
    'description' => 'utm medium',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
  $spec['utm_campaign'] = [
    'name' => 'utm_campaign',
    'title' => ts('utm campaign'),
    'description' => 'utm campaign',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
}

/**
 * Send a request to contact for accepting or rejecting consent.
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_wemove_consent_request(&$params) {
  $start = microtime(TRUE);
  $contactId = $params['contact_id'];
  $campaignId = $params['campaign_id'];
  $utmSource = $params['utm_source'];
  $utmMedium = $params['utm_medium'];
  $utmCampaign = $params['utm_campaign'];

  $values = [];

  $extraReturnValues = ['time' => microtime(TRUE) - $start];
  return civicrm_api3_create_success($values, $params, 'wemove_consent', 'request', $blank, $extraReturnValues);
}
