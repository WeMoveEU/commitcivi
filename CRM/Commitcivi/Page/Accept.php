<?php

require_once 'CRM/Core/Page.php';

/**
 * Endpoint to add consent activity to a contact - accept
 * The consent id is determined from the campaign, which is given as a request parameter.
 * The user is then redirected to a thank you page, also determined from the campaign.
 */
class CRM_Commitcivi_Page_Accept extends CRM_Commitcivi_Logic_Consent {

  /**
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public function run() {
    $this->setValues();
    $campaign = new CRM_Speakcivi_Logic_Campaign($this->campaignId);
    $contactParams = $this->getContactConsentParams($campaign);
    CRM_Speakcivi_Logic_Contact::set($this->contactId, $contactParams);
    $this->accept($campaign);
    $this->redirect($campaign);
  }

}
