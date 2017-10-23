<?php

class CRM_Commitcivi_Model_Event {
  public $actionType = '';
  public $actionTechnicalType = '';
  public $actionName = '';
  public $createDate = '';
  public $externalIdentifier = '';
  public $contact;
  public $donation;
  public $utm;

  public function __construct($params) {
    $this->actionType = $params->action_type;
    $this->actionTechnicalType = $params->action_technical_type;
    $this->actionName = $params->action_name;
    $this->createDate = $params->create_dt;
    $this->externalIdentifier = $params->external_id;
    $this->contact = new CRM_Commitcivi_Model_Contact($params);
    $this->donation = new CRM_Commitcivi_Model_Donation($params);
    $this->utm = new CRM_Commitcivi_Model_Utm($params);
  }

}
