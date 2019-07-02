<?php

require_once 'CRM/Core/Page.php';

/**
 * Endpoint to add consent activity to a contact - accept
 * The consent id is determined from the campaign, which is given as a request parameter.
 * The user is then redirected to a thank you page, also determined from the campaign.
 */
class CRM_Commitcivi_Page_Accept extends CRM_Core_Page {

  /**
   * @return null|void
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public function run() {
    $consent = new CRM_Commitcivi_Logic_Consent();
    $consent->setValues();
    $campaign = new CRM_Speakcivi_Logic_Campaign($consent->campaignId);
    $contactParams = $consent->getContactConsentParams($campaign);
    CRM_Speakcivi_Logic_Contact::set($consent->contactId, $contactParams);
    $consent->createConsentActivities($campaign);
    $consent->redirect($campaign);
  }

}
