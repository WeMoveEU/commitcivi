<?php
function _civicrm_api3_wemove_contact_create_spec(&$spec) {
  $spec['firstname'] = [
    'name' => 'firstname',
    'title' => ts('First name'),
    'description' => ts('First name'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
  $spec['lastname'] = [
    'name' => 'lastname',
    'title' => ts('Last name'),
    'description' => ts('Last name'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
  $spec['email'] = [
    'name' => 'email',
    'title' => ts('E-mail'),
    'description' => ts('E-mail'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
  $spec['postal_code'] = [
    'name' => 'postal_code',
    'title' => ts('Postal code'),
    'description' => ts('Postal code'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
  $spec['country'] = [
    'name' => 'country',
    'title' => ts('Country'),
    'description' => 'Country ISO code',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
  $spec['create_dt'] = [
    'name' => 'create_dt',
    'title' => ts('Create date'),
    'description' => ts('Create date of event'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['action_name'] = [
    'name' => 'action_name',
    'title' => 'Action name',
    'description' => 'Action name where last "-XX" chars determines language of campaign',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['action_type'] = [
    'name' => 'action_type',
    'title' => 'Action type',
    'description' => 'Action type, example: donate, petition, share',
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
  $spec['campaign_id'] = [
    'name' => 'campaign_id',
    'title' => ts('Campaign ID'),
    'description' => 'CiviCRM Campaign ID',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
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
 * @param $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_wemove_contact_create($params) {
  if (CRM_Commitcivi_Model_Contact::isAnonymous($params['email'])) {
    $params = [
      'id' => CRM_Commitcivi_Logic_Settings::anonymousId(),
    ];
    $result = civicrm_api3('Contact', 'get', $params);
    $contactId = $result['id'];
    $contactResult = $result['values'][$contactId];
    $returnResult = [$contactId => $contactResult];
    return civicrm_api3_create_success($returnResult, $params);
  }

  $groupId = CRM_Commitcivi_Logic_Settings::groupId();
  $campaign = new CRM_Commitcivi_Logic_Campaign();
  $locale = $campaign->determineLanguage($params['action_name']);
  $contactObj = new CRM_Commitcivi_Logic_Contact();
  $optIn = $contactObj->determineOptIn(CRM_Commitcivi_Logic_Settings::optIn(), $params['country']);
  $options = [
    'group_id' => $groupId,
    'locale' => $locale,
    'opt_in' => $optIn,
  ];

  $contact = array(
    'contact_type' => 'Individual',
    'email' => $params['email'],
    'api.Address.get' => array(
      'id' => '$value.address_id',
      'contact_id' => '$value.id',
    ),
    'api.GroupContact.get' => array(
      'group_id' => $groupId,
      'contact_id' => '$value.id',
      'status' => 'Added',
    ),
    'return' => 'id,email,first_name,last_name,preferred_language,is_opt_out',
  );

  $contacIds = $contactObj->getByEmail($params['email']);
  $updateContact = TRUE;
  $contactId = 0;
  $contactResult = [];
  if (is_array($contacIds) && count($contacIds) > 0) {
    $contactParam = $contact;
    $contactParam['id'] = array('IN' => array_keys($contacIds));
    unset($contactParam['email']); // getting by email (pseudoconstant) sometimes doesn't work
    $result = civicrm_api3('Contact', 'get', $contactParam);
    if ($result['count'] == 1) {
      $contact = $contactObj->prepareParamsContact($params, $contact, $options, $result, $result['id']);
      if (!$contactObj->needUpdate($contact)) {
        $updateContact = FALSE;
        $contactId = $result['id'];
        $contactResult = $result['values'][$contactId];
      }
    }
    elseif ($result['count'] > 1) {
      $lastname = $contactObj->cleanLastname($params['lastname']);
      $newContact = $contact;
      $newContact['first_name'] = $params['firstname'];
      $newContact['last_name'] = $lastname;
      $similarity = $contactObj->glueSimilarity($newContact, $result['values']);
      unset($newContact);
      $contactIdBest = $contactObj->chooseBestContact($similarity);
      $contact = $contactObj->prepareParamsContact($params, $contact, $options, $result, $contactIdBest);
      if (!$contactObj->needUpdate($contact)) {
        $updateContact = FALSE;
        $contactId = $contactIdBest;
        $contactResult = $result['values'][$contactIdBest];
      }
    }
  }
  else {
    $contact = $contactObj->prepareParamsContact($params, $contact, $options);
  }

  if ($updateContact) {
    $result = civicrm_api3('Contact', 'create', $contact);
    $contactId = $result['id'];
    $contactResult = $result['values'][$contactId];
  }
  $returnResult = [$contactId => $contactResult];

  $language = substr($locale, 0, 2);
  $group = new CRM_Commitcivi_Logic_Group();
  $rlg = $group->setLanguageGroup($contactId, $language);
  $tag = new CRM_Commitcivi_Logic_Tag();
  $tag->setLanguageTag($contactId, $language);
  if ($contactObj->needJoinActivity($contact)) {
    $activity = new CRM_Commitcivi_Logic_Activity();
    // todo add utms to join activity
    $activity->join($contactId, 'donation', $params['campaign_id']);

    $campaign = CRM_Commitcivi_Logic_CampaignCache::getByLocalId($params['campaign_id']);
    $consent = new CRM_Commitcivi_Model_Consent();
    $consent->version = $campaign[CRM_Commitcivi_Logic_Settings::fieldConsentIds()];
    $consent->language = $language;
    $consent->createDate = $params['create_dt'];
    $consent->campaignId = $params['campaign_id'];
    $consent->utmSource = $params['utm_source'];
    $consent->utmMedium = $params['utm_medium'];
    $consent->utmCampaign = $params['utm_campaign'];
    $activity->dpa($contactId, $consent);
    $contactObj->setGDPRFields($contactId, $consent);
  }
  else {
    // send a request for consent
    $requestParams = [
      'contact_id' => $contactId,
      'campaign_id' => $params['campaign_id'],
      'utm_source' => $params['utm_source'],
      'utm_medium' => $params['utm_medium'],
      'utm_campaign' => $params['utm_campaign'],
    ];
    $result = civicrm_api3('WemoveConsent', 'request', $requestParams);
  }
  if ($contactResult['preferred_language'] != $locale && $rlg == 1) {
    $contactObj->set($contactId, ['preferred_language' => $locale]);
  }
  return civicrm_api3_create_success($returnResult, $params);
}
