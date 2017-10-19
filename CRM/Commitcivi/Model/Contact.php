<?php

class CRM_Commitcivi_Model_Contact {
  public $firstname = '';
  public $lastname = '';
  public $email = '';
  public $postalCode = '';
  public $country = '';

  public function __construct($params) {
    $this->firstname = $params->cons_hash->firstname;
    $this->lastname = $params->cons_hash->lastname;
    $this->email = $params->cons_hash->emails[0]->email;
    $this->postalCode = $params->cons_hash->addresses[0]->zip;
    $this->country = $params->cons_hash->addresses[0]->country;
  }

}
