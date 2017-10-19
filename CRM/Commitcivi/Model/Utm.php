<?php

class CRM_Commitcivi_Model_Utm {
  public $Source = '';
  public $Medium = '';
  public $Campaign = '';
  public $Content = '';

  public function __construct($params) {
    $this->Source = $params->source->source;
    $this->Medium = $params->source->medium;
    $this->Campaign = $params->source->campaign;
    $this->Content = $params->source->content;
  }

}
