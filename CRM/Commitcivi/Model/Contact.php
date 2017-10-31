<?php

class CRM_Commitcivi_Model_Contact {
  public $firstname = '';
  public $lastname = '';
  public $email = '';
  public $postalCode = '';

  /**
   * @var string Country ISO code
   */
  public $country = '';

  public function __construct($params) {
    $this->firstname = $params->contact->firstname;
    $this->lastname = $params->contact->lastname;
    $this->email = $params->contact->emails[0]->email;
    $this->postalCode = $params->contact->addresses[0]->zip;
    $this->country = strtoupper($params->contact->addresses[0]->country);
  }

  /**
   * Check if contact is anonymous (without email).
   *
   * @param string $email
   *
   * @return bool
   */
  public static function isAnonymous($email) {
    return !$email;
  }

}
