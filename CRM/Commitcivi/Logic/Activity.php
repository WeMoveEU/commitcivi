<?php

class CRM_Commitcivi_Logic_Activity {

  /**
   * Add Join activity to contact
   *
   * @param int $contactId
   * @param string $subject
   * @param int $campaignId
   * @param array $customFields
   * @param int $parentActivityId
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public function join($contactId, $subject = '', $campaignId = 0, $customFields = [], $parentActivityId = 0) {
    $activityTypeId = CRM_Commitcivi_Logic_Settings::joinActivityTypeId();
    $result = $this->create($contactId, $activityTypeId, $subject, $campaignId, $customFields, $parentActivityId);
    return $result['id'];
  }

  /**
   * Create a Data Policy Acceptance activity to the given contact, with the data from the given consent
   *
   * @param int $contactId
   * @param \CRM_Commitcivi_Model_Consent $consent
   * @param string $activityStatus
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public function dpa($contactId, CRM_Commitcivi_Model_Consent $consent, $activityStatus = 'Completed') {
    $activityTypeId = CRM_Commitcivi_Logic_Settings::dpaActivityTypeId();
    $activityStatusId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', $activityStatus);
    $params = [
      'sequential' => 1,
      'source_contact_id' => $contactId,
      'campaign_id' => $consent->campaignId,
      'activity_type_id' => $activityTypeId,
      'activity_date_time' => $consent->createDate,
      'subject' => $consent->version,
      'location' => $consent->language,
      'status_id' => $activityStatusId,
      CRM_Commitcivi_Logic_Settings::fieldActivitySource() => $consent->utmSource,
      CRM_Commitcivi_Logic_Settings::fieldActivityMedium() => $consent->utmMedium,
      CRM_Commitcivi_Logic_Settings::fieldActivityCampaign() => $consent->utmCampaign,
    ];
    $result = civicrm_api3('Activity', 'create', $params);
    return $result['id'];
  }

  /**
   * Create activity for contact.
   *
   * @param int $contactId
   * @param int $typeId
   * @param string $subject
   * @param int $campaignId
   * @param array $customFields
   * @param int $parentActivityId
   * @param string $activity_date_time
   * @param string $location
   * @param string $status
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private function create($contactId, $typeId, $subject = '', $campaignId = 0, $customFields = [], $parentActivityId = 0, $activity_date_time = '', $location = '', $status = 'Completed') {
    $statusId = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', $status);
    $params = array(
      'sequential' => 1,
      'activity_type_id' => $typeId,
      'activity_date_time' => date('Y-m-d H:i:s'),
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
    $params = array_merge($params, $customFields);

    return civicrm_api3('Activity', 'create', $params);
  }


  /**
   * Prepare source fields in custom fields and return as a param array to Activity
   * api action
   *
   * @param CRM_Commitcivi_Logic_Consent $consent
   *
   * @return array
   */
  public function prepareSourceFields(CRM_Commitcivi_Logic_Consent $consent) {
    $params = [];
    if ($consent->utmSource) {
      $params[CRM_Commitcivi_Logic_Settings::fieldActivitySource()] = $consent->utmSource;
    }
    if ($consent->utmMedium) {
      $params[CRM_Commitcivi_Logic_Settings::fieldActivityMedium()] = $consent->utmMedium;
    }
    if ($consent->utmCampaign) {
      $params[CRM_Commitcivi_Logic_Settings::fieldActivityCampaign()] = $consent->utmCampaign;
    }

    return $params;
  }

}
