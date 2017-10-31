<?php

class CRM_Commitcivi_Logic_Group {

  /**
   * Check If contact is member of group on given status
   *
   * @param $contactId
   * @param $groupId
   * @param $status
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function isGroupContact($contactId, $groupId, $status = "Added") {
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'group_id' => $groupId,
      'status' => $status,
    ));
    return (int) $result['count'];
  }


  /**
   * Check If contact is member of group on Added status
   *
   * @param $contactId
   * @param $groupId
   *
   * @return int
   */
  public function isGroupContactAdded($contactId, $groupId) {
    return $this->isGroupContact($contactId, $groupId, "Added");
  }


  /**
   * Check If contact is member of group on Removed status
   *
   * @param $contactId
   * @param $groupId
   *
   * @return int
   */
  public function isGroupContactRemoved($contactId, $groupId) {
    return $this->isGroupContact($contactId, $groupId, "Removed");
  }


  /**
   * Set given status for group
   *
   * @param $contactId
   * @param $groupId
   * @param $status
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function setGroupContact($contactId, $groupId, $status = "Added") {
    $params = array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'group_id' => $groupId,
      'status' => $status,
    );
    civicrm_api3('GroupContact', 'create', $params);
  }


  /**
   * Set Added status for group
   *
   * @param $contactId
   * @param $groupId
   */
  public function setGroupContactAdded($contactId, $groupId) {
    $this->setGroupContact($contactId, $groupId, "Added");
  }


  /**
   * Set Removed status for group
   *
   * @param $contactId
   * @param $groupId
   */
  public function setGroupContactRemoved($contactId, $groupId) {
    $this->setGroupContact($contactId, $groupId, "Removed");
  }


  /**
   * Set Added status for group. If group is not assigned to contact, It is added.
   *
   * @param int $contactId
   * @param int $groupId
   *
   * @throws CiviCRM_API3_Exception
   */
  public function setGroupStatus($contactId, $groupId) {
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'contact_id' => $contactId,
      'group_id' => $groupId,
      'status' => "Pending",
    ));

    if ($result['count'] == 1) {
      $params = array(
        'id' => $result["id"],
        'status' => "Added",
      );
    }
    else {
      $params = array(
        'sequential' => 1,
        'contact_id' => $contactId,
        'group_id' => $groupId,
        'status' => "Added",
      );
    }
    civicrm_api3('GroupContact', 'create', $params);
  }


  /**
   * Set language group for contact based on language of campaign
   *
   * @param int $contactId
   * @param string $language Language in format en, fr, de, pl etc.
   * @return int 1: set given language group, 2: set default language group, 0: no changes
   */
  public function setLanguageGroup($contactId, $language) {
    if ($language) {
      $languageGroupNameSuffix = CRM_Commitcivi_Logic_Settings::languageGroupNameSuffix();
      $defaultLanguageGroupId = CRM_Commitcivi_Logic_Settings::defaultLanguageGroupId();
      if (!$this->checkLanguageGroup($contactId, $defaultLanguageGroupId, $languageGroupNameSuffix)) {
        $languageGroupId = $this->findLanguageGroupId($language, $languageGroupNameSuffix);
        if ($languageGroupId) {
          $this->setGroupStatus($contactId, $languageGroupId);
          $this->deleteLanguageGroup($contactId, $defaultLanguageGroupId);
          return 1;
        }
        else {
          $this->setGroupStatus($contactId, $defaultLanguageGroupId);
          return 2;
        }
      }
    }
    return 0;
  }


  /**
   * Get language group id based on language shortcut
   *
   * @param string $language Example: en, es, fr...
   * @param string $languageGroupNameSuffix
   *
   * @return int
   */
  public function findLanguageGroupId($language, $languageGroupNameSuffix) {
    $result = civicrm_api3('Group', 'get', array(
      'sequential' => 1,
      'name' => $language . $languageGroupNameSuffix,
      'return' => 'id',
    ));
    if ($result['count'] == 1) {
      return $result['id'];
    }
    return 0;
  }


  /**
   * Check if contact has already at least one language group. Default group is skipping.
   *
   * @param int $contactId
   * @param int $defaultLanguageGroupId
   * @param string $languageGroupNameSuffix
   *
   * @return bool
   */
  public function checkLanguageGroup($contactId, $defaultLanguageGroupId, $languageGroupNameSuffix) {
    $query = "SELECT count(gc.id) group_count
              FROM civicrm_group_contact gc JOIN civicrm_group g ON gc.group_id = g.id AND gc.status = 'Added'
              WHERE gc.contact_id = %1 AND g.id <> %2 AND g.name LIKE %3";
    $params = array(
      1 => array($contactId, 'Integer'),
      2 => array($defaultLanguageGroupId, 'Integer'),
      3 => array('%' . $languageGroupNameSuffix, 'String'),
    );
    $results = CRM_Core_DAO::executeQuery($query, $params);
    $results->fetch();
    return (bool) $results->group_count;
  }


  /**
   * Delete language group from contact
   *
   * @param $contactId
   * @param $groupId
   */
  public function deleteLanguageGroup($contactId, $groupId) {
    $query = "DELETE FROM civicrm_group_contact
              WHERE contact_id = %1 AND group_id = %2";
    $params = array(
      1 => array($contactId, 'Integer'),
      2 => array($groupId, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($query, $params);
  }

}
