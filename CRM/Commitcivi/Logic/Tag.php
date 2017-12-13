<?php

class CRM_Commitcivi_Logic_Tag {

  /**
   * Set language tag for contact based on language of campaign
   *
   * @param int $contactId
   * @param string $language Language in format en, fr, de, pl etc.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function setLanguageTag($contactId, $language) {
    if ($language) {
      $languageTagNamePrefix = CRM_Commitcivi_Logic_Settings::languageTagNamePrefix();
      $tagName = $languageTagNamePrefix . $language;
      if (!($tagId = $this->getLanguageTagId($tagName))) {
        $tagId = $this->createLanguageTag($tagName);
      }
      if ($tagId) {
        $this->addLanguageTag($contactId, $tagId);
      }
    }
  }

  /**
   * Get language tag id
   *
   * @param string $tagName
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function getLanguageTagId($tagName) {
    $params = array(
      'sequential' => 1,
      'name' => $tagName,
    );
    $result = civicrm_api3('Tag', 'get', $params);
    if ($result['count'] == 1) {
      return (int) $result['id'];
    }
    return 0;
  }

  /**
   * Create new language tag
   *
   * @param string $tagName
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function createLanguageTag($tagName) {
    $params = array(
      'sequential' => 1,
      'used_for' => 'civicrm_contact',
      'name' => $tagName,
      'description' => $tagName,
    );
    $result = civicrm_api3('Tag', 'create', $params);
    if ($result['count'] == 1) {
      return (int) $result['id'];
    }
    return 0;
  }

  /**
   * Add tag to contact
   *
   * @param int $contactId
   * @param int $tagId
   *
   * @throws \CiviCRM_API3_Exception
   */
  private function addLanguageTag($contactId, $tagId) {
    $params = array(
      'sequential' => 1,
      'entity_table' => "civicrm_contact",
      'entity_id' => $contactId,
      'tag_id' => $tagId,
    );
    $result = civicrm_api3('EntityTag', 'get', $params);
    if ($result['count'] == 0) {
      civicrm_api3('EntityTag', 'create', $params);
    }
  }

}
