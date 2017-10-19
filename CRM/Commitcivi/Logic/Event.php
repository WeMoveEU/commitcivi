<?php

class CRM_Commitcivi_Logic_Event {

  /**
   * @var object Whole event just after decoded from json format.
   */
  private $params;

  public $actionType = '';
  public $actionTechnicalType = '';
  public $actionName = '';
  public $createDate = '';
  public $externalId = '';
  public $firstname = '';
  public $lastname = '';

  public function __construct($params) {
    $this->params = $params;
    $this->parse();
  }

  private function parse() {
    // todo add rest of fields
    $this->actionType = $this->params->action_type;
    $this->actionTechnicalType = $this->params->action_technical_type;
    $this->actionName = $this->params->action_name;
    $this->createDate = $this->params->create_dt;
    $this->externalId = $this->params->external_id;
    $this->firstname = $this->params->cons_hash->firstname;
    $this->lastname = $this->params->cons_hash->lastname;
  }

}
