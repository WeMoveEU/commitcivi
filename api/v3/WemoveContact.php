<?php
function _civicrm_api3_wemove_contact_set_spec(&$spec) {
  $spec['firstname'] = [
    'name' => 'firstname',
    'title' => ts('First name'),
    'description' => ts('First name'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['lastname'] = [
    'name' => 'lastname',
    'title' => ts('Last name'),
    'description' => ts('Last name'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
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
}

function civicrm_api3_wemove_contact_set($params) {
  $groupId = CRM_Commitcivi_Logic_Settings::groupId();
  $campaign = new CRM_Commitcivi_Logic_Campaign();
  $locale = $campaign->determineLanguage($params['action_name']);
  $options = [
    'group_id' => $groupId,
    'locale' => $locale,
    'opt_in' => CRM_Commitcivi_Logic_Settings::optIn(),
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

  $contactObj = new CRM_Commitcivi_Logic_Contact();
  $contacIds = $contactObj->getContactByEmail($params['email']);
  if (is_array($contacIds) && count($contacIds) > 0) {
    $contactParam = $contact;
    $contactParam['id'] = array('IN' => array_keys($contacIds));
    unset($contactParam['email']); // getting by email (pseudoconstant) sometimes doesn't work
    $result = civicrm_api3('Contact', 'get', $contactParam);
    if ($result['count'] == 1) {
      $contact = $contactObj->prepareParamsContact($params, $contact, $options, $result, $result['id']);
      if (!$contactObj->needUpdate($contact)) {
        return civicrm_api3_create_success([$result['id'] => $result['values'][$result['id']]], $params);
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
        return civicrm_api3_create_success([$contactIdBest => $result['values'][$contactIdBest]], $params);
      }
    }
  }
  else {
    // $speakcivi->newContact = TRUE;
    $contact = $contactObj->prepareParamsContact($params, $contact, $options);
  }

  $result = civicrm_api3('Contact', 'create', $contact);
  $contactId = $result['id'];
  $contactResult = $result['values'][$contactId];

  $language = substr($locale, 0, 2);
  $pagePost = new CRM_Speakcivi_Page_Post();
  $rlg = $pagePost->setLanguageGroup($contactId, $language);
  $pagePost->setLanguageTag($contactId, $language);
  // todo addJoinActivity
  // if ($speakcivi->addJoinActivity) {
  //  CRM_Speakcivi_Logic_Activity::join($contactId, 'donation', $speakcivi->campaignId);
  //}
  // todo where move this?
  if ($contactResult['preferred_language'] != $locale && $rlg == 1) {
    CRM_Speakcivi_Logic_Contact::set($contactId, array('preferred_language' => $locale));
  }
  return civicrm_api3_create_success([$contactId => $contactResult], $params);
}
