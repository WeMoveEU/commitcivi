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
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_wemove_consent_request(&$params) {
  $contactId = $params['contact_id'];
  $campaignId = $params['campaign_id'];
  $utmSource = $params['utm_source'];
  $utmMedium = $params['utm_medium'];
  $utmCampaign = $params['utm_campaign'];

  $campaignObj = new CRM_Speakcivi_Logic_Campaign($campaignId);
  $locale = $campaignObj->getLanguage();
  $params['from'] = $campaignObj->getSenderMail();
  $params['format'] = NULL;
  // todo change!
  $params['subject'] = $campaignObj->getSubjectNew();
  $message = $campaignObj->getMessageNew();
  if (!$message) {
    $message = CRM_Speakcivi_Tools_Dictionary::getMessageNew($locale);
    $campaignObj->setCustomFieldBySQL($campaignId, $campaignObj->fieldMessageNew, $message);
  }

  $contact = [];
  $paramsContact = [
    'id' => $contactId,
    'sequential' => 1,
    'return' => ["id", "display_name", "first_name", "last_name", "hash", "email", "email_greeting"],
  ];
  $result = civicrm_api3('Contact', 'get', $paramsContact);
  if ($result['count'] == 1) {
    $contact = $result['values'][0];
    $contact['checksum'] = CRM_Contact_BAO_Contact_Utils::generateChecksum($contactId, NULL, NULL, $contact['hash']);
    $params['toEmail'] = $contact['email'];
  }

  $hash = sha1(CIVICRM_SITE_KEY . $contactId);
  $baseConfirmUrl = 'civicrm/consent/accept';
  $baseOptoutUrl = 'civicrm/consent/reject';

  $url_confirm_and_keep = CRM_Utils_System::url($baseConfirmUrl,
    "id=$contactId&cid=$campaignId&hash=$hash&utm_source=$utmSource&utm_medium=$utmMedium&utm_campaign=$utmCampaign", TRUE);
  $url_confirm_and_not_receive = CRM_Utils_System::url($baseOptoutUrl,
    "id=$contactId&cid=$campaignId&hash=$hash&utm_source=$utmSource&utm_medium=$utmMedium&utm_campaign=$utmCampaign", TRUE);

  $template = CRM_Core_Smarty::singleton();
  $template->assign('url_confirm_and_keep', $url_confirm_and_keep);
  $template->assign('url_confirm_and_not_receive', $url_confirm_and_not_receive);
  $template->assign('contact', $contact);

  $params['subject'] = $template->fetch('string:' . $params['subject']);
  $locales = getLocale($locale);
  $confirmationBlockHtml = $template->fetch('../templates/CRM/Commitcivi/Confirmation/ConfirmationBlock.' . $locales['html'] . '.html.tpl');
  $confirmationBlockText = $template->fetch('../templates/CRM/Commitcivi/Confirmation/ConfirmationBlock.' . $locales['text'] . '.text.tpl');
  $privacyBlock = $template->fetch('../templates/CRM/Commitcivi/Privacy/PrivacyBlock.' . $locales['html'] . '.tpl');
  $message = $template->fetch('string:' . $message);

  $messageHtml = str_replace("#CONFIRMATION_BLOCK", $confirmationBlockHtml, $message);
  $messageText = str_replace("#CONFIRMATION_BLOCK", $confirmationBlockText, $message);
  $messageHtml = str_replace("#PRIVACY_BLOCK", $privacyBlock, $messageHtml);
  $messageText = str_replace("#PRIVACY_BLOCK", $privacyBlock, $messageText);

  $params['html'] = html_entity_decode($messageHtml);
  $params['text'] = html_entity_decode(convertHtmlToText($messageText));
  $params['groupName'] = 'WemoveConsent.request';
  $params['custom-campaign-id'] = $campaignId;
  try {
    $sent = CRM_Utils_Mail::send($params);
    return civicrm_api3_create_success($sent, $params);
  }
  catch (CiviCRM_API3_Exception $exception) {
    $data = array(
      'params' => $params,
      'exception' => $exception,
    );
    return civicrm_api3_create_error('Problem with send email in sendconfirm', $data);
  }
}


/**
 * todo refactor!
 * Get locale version for locale from params. Default is a english version.
 *
 * @param string $locale Locale, so format is xx_YY (language_COUNTRY), ex. en_GB
 *
 * @return array
 */
function getLocale($locale) {
  $localeTab = array(
    'html' => 'en_GB',
    'text' => 'en_GB',
  );
  foreach ($localeTab as $type => $localeType) {
    if (file_exists(dirname(__FILE__) . '/../../templates/CRM/Commitcivi/Confirmation/ConfirmationBlock.' . $locale . '.' . $type . '.tpl')) {
      $localeTab[$type] = $locale;
    }
  }
  return $localeTab;
}


/**
 * todo refactor
 * @param $html
 *
 * @return string
 */
function convertHtmlToText($html) {
  $html = str_ireplace(array('<br>', '<br/>', '<br />'), "\n", $html);
  $html = strip_tags($html, '<a>');
  $re = '/<a href="(.*)">(.*)<\/a>/';
  if (preg_match_all($re, $html, $matches)) {
    foreach ($matches[0] as $id => $tag) {
      $html = str_replace($tag, $matches[2][$id] . "\n" . str_replace(' ', '+', $matches[1][$id]), $html);
    }
  }
  return $html;
}
