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
    // fixme gdpr custom fields is still used for Speakcivi.leave action
    $contactParams = $this->getContactConsentParams($campaign);
    CRM_Speakcivi_Logic_Contact::set($this->contactId, $contactParams);
    $this->accept($campaign);
    $activity = new CRM_Commitcivi_Logic_Activity();
    $activity->join($this->contactId, 'request-consent-accept', $this->campaignId);
    $groupId = CRM_Commitcivi_Logic_Settings::groupId();
    $group = new CRM_Commitcivi_Logic_Group();
    $group->setGroupContactAdded($this->contactId, $groupId);
    $this->redirect($campaign);
  }

}
