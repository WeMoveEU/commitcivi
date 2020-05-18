<?php

class CRM_Commitcivi_Logic_Activity {

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

}
