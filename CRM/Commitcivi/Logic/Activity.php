<?php

class CRM_Commitcivi_Logic_Activity {

  /**
   * Add Join activity to contact
   *
   * @param int $contactId
   * @param string $subject
   * @param int $campaignId
   * @param int $parentActivityId
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public function join($contactId, $subject = '', $campaignId = 0, $parentActivityId = 0) {
    $activityTypeId = CRM_Commitcivi_Logic_Settings::joinActivityTypeId();
    $result = $this->create($contactId, $activityTypeId, $subject, $campaignId, $parentActivityId);
    return $result['id'];
  }

  /**
   * Create a Data Policy Acceptance activity to the given contact, with the data from the given consent
   */
  public function dpa($contactId, CRM_Commitcivi_Model_Consent $consent, $activityStatus = 'Completed') {
    $activityTypeId = CRM_Commitcivi_Logic_Settings::dpaActivityTypeId();
    $result = $this->create($contactId, $activityTypeId, $consent->version, $consent->campaignId, 0,
                            $consent->createDate, $consent->language, $activityStatus);
    return $result['id'];
  }

  /**
   * Create activity for contact.
   *
   * @param int $contactId
   * @param int $typeId
   * @param string $subject
   * @param int $campaignId
   * @param int $parentActivityId
   * @param string $activity_date_time
   * @param string $location
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function create($contactId, $typeId, $subject = '', $campaignId = 0, $parentActivityId = 0, $activity_date_time = '', $location = '', $status = 'Completed') {
    $statusId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', $status);
    $params = array(
      'sequential' => 1,
      'activity_type_id' => $typeId,
      'activity_date_time' => date('Y-m-d H:i:s'),
      'status_id' => 'Completed',
      'subject' => $subject,
      'source_contact_id' => $contactId,
      'status_id' => $statusId,
    );
    if ($campaignId) {
      $params['campaign_id'] = $campaignId;
    }
    if ($parentActivityId) {
      $params['parent_id'] = $parentActivityId;
    }
    if ($activity_date_time) {
      $params['activity_date_time'] = $activity_date_time;
    }
    if ($location) {
      $params['location'] = $location;
    }
    return civicrm_api3('Activity', 'create', $params);
  }

}
