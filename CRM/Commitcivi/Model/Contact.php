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
    $this->firstname = $this->get($params)->firstname;
    $this->lastname = $this->get($params)->lastname;
    $this->email = $this->get($params)->emails[0]->email;
    $this->postalCode = $this->get($params)->addresses[0]->zip;
    $this->country = strtoupper($this->get($params)->addresses[0]->country);
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

  /**
   * Get contact object.
   *
   * @param object $params
   *
   * @return mixed
   */
  private function get($params) {
    if (property_exists($params, 'contact')) {
      return $params->contact;
    }
    return $params->cons_hash;
  }

}
