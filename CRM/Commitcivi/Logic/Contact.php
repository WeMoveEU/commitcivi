<?php

class CRM_Commitcivi_Logic_Contact {

  const API_GROUPCONTACT_GET = 'api.GroupContact.get';
  const API_GROUPCONTACT_CREATE = 'api.GroupContact.create';

  /**
   * Get contact id (or ids) by using Email API
   *
   * @param $email
   *
   * @return array
   */
  public function getByEmail($email) {
    $query = "SELECT e.contact_id
              FROM civicrm_email e
                JOIN civicrm_contact c ON e.contact_id = c.id
              WHERE email = %1 AND c.is_deleted = 0
              ORDER BY e.contact_id ";
    $params = [
      1 => [$email, 'String'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $ids = [];
    while ($dao->fetch()) {
      $ids[$dao->contact_id] = $dao->contact_id;
    }
    return $ids;
  }

  /**
   * Set contact params
   *
   * @param int $contactId
   * @param array $contactParams
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function set($contactId, $contactParams) {
    $params = array(
      'sequential' => 1,
      'id' => $contactId,
    );
    $params = $params + $contactParams;
    if (count($params) > 2) {
      civicrm_api3('Contact', 'create', $params);
    }
  }

  /**
   * Preparing params for API Contact.create based on retrieved result.
   *
   * @param array $params
   * @param array $contact
   * @param array $options
   * @param array $result
   * @param int $basedOnContactId
   *
   * @return mixed
   */
  public function prepareParamsContact($params, $contact, $options, $result = array(), $basedOnContactId = 0) {
    $locale = $options['locale'];

    unset($contact['return']);
    unset($contact['api.Address.get']);
    unset($contact['api.GroupContact.get']);

    $existingContact = array();
    if ($basedOnContactId > 0) {
      foreach ($result['values'] as $id => $res) {
        if ($res['id'] == $basedOnContactId) {
          $existingContact = $res;
          break;
        }
      }
    }

    $address = new CRM_Commitcivi_Logic_Address();
    $params['country_id'] = CRM_Commitcivi_Logic_Country::getId($params['country']);

    if (is_array($existingContact) && count($existingContact) > 0) {
      $contact['id'] = $existingContact['id'];
      if ($existingContact['first_name'] == '' && $params['firstname']) {
        $contact['first_name'] = $params['firstname'];
      }
      if ($existingContact['last_name'] == '' && $params['lastname']) {
        $lastname = $this->cleanLastname($params['lastname']);
        if ($lastname) {
          $contact['last_name'] = $lastname;
        }
      }
      $contact = $address->prepareParamsAddress($contact, $existingContact, $params);
    }
    else {
      $genderId = $this->getGenderId($params['lastname']);
      $genderShortcut = $this->getGenderShortcut($params['lastname']);
      $lastname = $this->cleanLastname($params['lastname']);
      $contact['first_name'] = $params['firstname'];
      $contact['last_name'] = $lastname;
      $contact['gender_id'] = $genderId;
      $contact['prefix_id'] = $this->getPrefix($genderShortcut);
      $emailGreetingIds = $this->parseGroupEmailGreeting();
      $emailGreetingId = $this->getEmailGreetingId($locale, $genderShortcut, $emailGreetingIds);
      if ($emailGreetingId) {
        $contact['email_greeting_id'] = $emailGreetingId;
      }
      $contact['preferred_language'] = $locale;
      $contact['source'] = $this->determineSource($params);
      $contact = $address->prepareParamsAddressDefault($contact, $params);
    }
    $contact = $address->removeNullAddress($contact);
    return $contact;
  }

  /**
   * Clean lastname from gender
   *
   * @param $lastname
   *
   * @return mixed
   */
  public function cleanLastname($lastname) {
    $re = "/(.*)(\\[.*\\])$/";
    return trim(preg_replace($re, '${1}', $lastname));
  }

  /**
   * Get gender id based on lastname. Format: Lastname [?], M -> Male, F -> Femail, others -> Unspecific
   *
   * @param $lastname
   *
   * @return int
   */
  private function getGenderId($lastname) {
    $genderFemaleValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', 'gender_id', 'Female');
    $genderMaleValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', 'gender_id', 'Male');
    $genderUnspecifiedValue = CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Contact', 'gender_id', 'unspecified');
    $re = '/.*\[([FM])\]$/';
    if (preg_match($re, $lastname, $matches)) {
      switch ($matches[1]) {
        case 'F':
          return $genderFemaleValue;

        case 'M':
          return $genderMaleValue;

        default:
          return $genderUnspecifiedValue;
      }
    }
    return $genderUnspecifiedValue;
  }

  /**
   * Get gender shortcut based on lastname. Format: Lastname [?], M -> Male, F -> Femail, others -> Unspecific
   *
   * @param $lastname
   *
   * @return string
   */
  private function getGenderShortcut($lastname) {
    $re = '/.*\[([FM])\]$/';
    if (preg_match($re, $lastname, $matches)) {
      return $matches[1];
    }
    return '';
  }

  /**
   * Get prefix based on gender (F or M)
   * @param string $genderShortcut
   *
   * @return string
   */
  private function getPrefix($genderShortcut) {
    $array = array(
      'F' => 'Mrs.',
      'M' => 'Mr.',
    );
    return CRM_Utils_Array::value($genderShortcut, $array, '');
  }

  /**
   * Calculate and glue similarity between new contact and all retrieved from database
   *
   * @param array $newContact
   * @param array $contacts Array from API.Contact.get, key 'values'
   *
   * @return array
   */
  public function glueSimilarity($newContact, $contacts) {
    $similarity = array();
    foreach ($contacts as $k => $c) {
      $similarity[$c['id']] = $this->calculateSimilarity($newContact, $c);
    }
    return $similarity;
  }

  /**
   * Calculate similarity between two contacts based on defined keys
   *
   * @param $contact1
   * @param $contact2
   *
   * @return int
   */
  private function calculateSimilarity($contact1, $contact2) {
    $keys = array(
      'first_name',
      'last_name',
      'email',
    );
    $points = 0;
    foreach ($keys as $key) {
      if ($contact1[$key] == $contact2[$key]) {
        $points++;
      }
    }
    return $points;
  }

  /**
   * Choose the best contact based on similarity. If similarity is the same, choose the oldest one.
   *
   * @param $similarity
   *
   * @return mixed
   */
  public function chooseBestContact($similarity) {
    $max = max($similarity);
    $contactIds = array();
    foreach ($similarity as $k => $v) {
      if ($max == $v) {
        $contactIds[$k] = $k;
      }
    }
    return min(array_keys($contactIds));
  }

  /**
   * Check if updating of contact if it's necessary.
   *
   * @param array $params Array of params for API contact
   *
   * @return bool
   */
  public function needUpdate($params) {
    unset($params['sequential']);
    unset($params['contact_type']);
    unset($params['email']);
    unset($params['id']);
    return (bool) count($params);
  }


  /**
   * Determine source for new contact.
   *
   * @param array $params
   *
   * @return string
   */
  private function determineSource($params) {
    $prefix = 'speakout ';
    if (strpos($params['action_technical_type'], 'cc.wemove.eu') !== FALSE) {
      $prefix = 'commitchange ';
    }
    return $prefix . $params['action_type'] . ' ' . $params['external_identifier'];
  }


  /**
   * Parse all email greeting types in array of locale and gender shortcut
   */
  public function parseGroupEmailGreeting() {
    $emailGreetingIds = [];
    CRM_Core_OptionGroup::getAssoc('email_greeting', $group, FALSE, 'name');
    foreach ($group['description'] as $id => $description) {
      $tab = $this->parseLocaleGenderShortcut($description);
      if (is_array($tab) && count($tab) == 2) {
        $emailGreetingIds[$tab['locale']][$tab['genderShortcut']] = $id;
      }
    }
    return $emailGreetingIds;
  }
  /**
   * Parse description of email greeting type in array of locale and gender shortcut
   * @param string $description description of email greeting type in format [locale]:[genderShortcut] ex. fr_FR:M
   *
   * @return array
   */
  public function parseLocaleGenderShortcut($description) {
    $re = '/^([a-z]{2,3}_[A-Z]{2})\:(.{0,1})/';
    if (preg_match($re, $description, $matches)) {
      return [
        'locale' => $matches[1],
        'genderShortcut' => $matches[2],
      ];
    }
    return [];
  }


  /**
   * Get email greeting Id for locale and gender shortcut
   *
   * @param string $locale
   * @param string $genderShortcut
   * @param array $emailGreetingIds
   *
   * @return int
   */
  public function getEmailGreetingId($locale, $genderShortcut, $emailGreetingIds) {
    if (array_key_exists($locale, $emailGreetingIds)) {
      if (
        array_key_exists($genderShortcut, $emailGreetingIds[$locale]) &&
        $emailGreetingIds[$locale][$genderShortcut] > 0
      ) {
        return $emailGreetingIds[$locale][$genderShortcut];
      }
      return $emailGreetingIds[$locale][''];
    }
    return 0;
  }

}
