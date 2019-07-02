<?php

require_once 'CRM/Core/Page.php';

class CRM_Commitcivi_Logic_Consent {

  public $contactId = 0;

  public $campaignId = 0;

  public $utmSource = '';

  public $utmMedium = '';

  public $utmCampaign = '';

  /**
   * Set values from request.
   *
   * @throws Exception
   */
  public function setValues() {
    $this->contactId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $this->campaignId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->utmSource= CRM_Utils_Request::retrieve('utm_source', 'String', $this, FALSE);
    $this->utmMedium = CRM_Utils_Request::retrieve('utm_medium', 'String', $this, FALSE);
    $this->utmCampaign = CRM_Utils_Request::retrieve('utm_campaign', 'String', $this, FALSE);
    $hash = CRM_Utils_Request::retrieve('hash', 'String', $this, TRUE);
    $hash1 = sha1(CIVICRM_SITE_KEY . $this->contactId);
    if ($hash !== $hash1) {
      CRM_Core_Error::fatal("hash not matching");
    }
  }

  /**
   * Get consent ids from definition of campaign.
   *
   * @param int $campaignId
   *
   * @return array
   */
  public function getConsentIds($campaignId) {
    $campaign = new CRM_Speakcivi_Logic_Campaign($campaignId);
    $c = $campaign->getConsentIds();
    if ($c) {
      return explode(',', $c);
    }

    return [];
  }

  /**
   * Based on given campaign and request parameters,
   * determine the consent-related values to apply to the contact.
   *
   * @param \CRM_Speakcivi_Logic_Campaign $campaign
   *
   * @return array
   */
  public function getContactConsentParams(CRM_Speakcivi_Logic_Campaign $campaign) {
    $locale = $campaign->getLanguage();
    $language = substr($locale, 0, 2);
    $consentIds = explode(',', $campaign->getConsentIds());
    if ($consentIds) {
      list($consentVersion, $language) = explode('-', $consentIds[0]);
    }
    else {
      $consentVersion = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'gdpr_privacy_pack_version');
    }

    $contactParams = array(
      'is_opt_out' => 0,
      'do_not_email' => 0,
      $this->fieldName('consent_date') => date('Y-m-d'),
      $this->fieldName('consent_version') => $consentVersion,
      $this->fieldName('consent_language') => strtoupper($language),
      $this->fieldName('consent_utm_source') => $this->utmSource,
      $this->fieldName('consent_utm_medium') => $this->utmMedium,
      $this->fieldName('consent_utm_campaign') => $this->utmCampaign,
      $this->fieldName('consent_campaign_id') => $this->campaignId,
    );

    return $contactParams;
  }

  /**
   * Based on give campaign and request parameters,
   * create an activity for all the consents implied by the request
   *
   * @param \CRM_Speakcivi_Logic_Campaign $campaign
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function createConsentActivities(CRM_Speakcivi_Logic_Campaign $campaign) {
    $consent = new CRM_Speakcivi_Logic_Consent();
    $consent->createDate = date('YmdHis');
    $consent->utmSource = $this->utmSource;
    $consent->utmMedium = $this->utmMedium;
    $consent->utmCampaign = $this->utmCampaign;

    $consentIds = explode(',', $campaign->getConsentIds());
    if ($consentIds) {
      foreach ($consentIds as $id) {
        list($consentVersion, $language) = explode('-', $id);
        $consent->version = $consentVersion;
        $consent->language = $language;
        CRM_Speakcivi_Logic_Activity::dpa($consent, $this->contactId, $this->campaignId, 'Completed');
      }
    }
    else {
      $consent->version = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'gdpr_privacy_pack_version');
      $consent->language = substr($campaign->getLanguage(), 0, 2);
      CRM_Speakcivi_Logic_Activity::dpa($consent, $this->contactId, $this->campaignId, 'Completed');
    }
  }


  /**
   * Build the post-confirmation URL
   * TODO: use a proper token mecanism
   *
   * @param $page
   * @param $country
   * @param $redirect
   * @param null $context
   *
   * @return mixed|string
   */
  public function determineRedirectUrl($page, $country, $redirect, $context = NULL) {
    if ($context != NULL) {
      $lang = $context['drupal_language'];
      $cid = $context['contact_id'];
      $checksum = $context['contact_checksum'];
    }
    else {
      $lang = $country;
      $cid = NULL;
      $checksum = NULL;
    }
    if ($redirect) {
      if ($cid) {
        $redirect = str_replace('{$contact_id}', $cid, $redirect);
      }
      if ($checksum) {
        $redirect = str_replace('{$contact.checksum}', $checksum, $redirect);
      }
      return str_replace('{$language}', $lang, $redirect);
    }
    if ($lang) {
      return "/{$lang}/{$page}";
    }
    return "/{$page}";
  }

  /**
   * @param \CRM_Speakcivi_Logic_Campaign $campaign
   * @param string $defaultPage
   */
  public function redirect(CRM_Speakcivi_Logic_Campaign $campaign, $defaultPage = 'thank-you-for-your-confirmation') {
    $language = substr($campaign->getLanguage(), 0, 2);
    $redirect = $campaign->getRedirectConfirm();
    $context = array(
      'drupal_language' => $language,
      'contact_id' => $this->contactId,
      'contact_checksum' => CRM_Contact_BAO_Contact_Utils::generateChecksum($this->contactId),
    );
    $url = $this->determineRedirectUrl($defaultPage, $language, $redirect, $context);
    CRM_Utils_System::redirect($url);
  }

  public function fieldName($name) {
    return CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_' . $name);
  }

}
