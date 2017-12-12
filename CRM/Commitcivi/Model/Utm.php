<?php

class CRM_Commitcivi_Model_Utm {
  public $Source = '';
  public $Medium = '';
  public $Campaign = '';
  public $Content = '';

  public function __construct($params) {
    if (property_exists($params, 'source')) {
      $this->Source = property_exists($params->source, 'source') ? $params->source->source : $params->source->source;
      $this->Medium = property_exists($params->source, 'medium') ? $params->source->medium : $params->source->medium;
      $this->Campaign = property_exists($params->source, 'campaign') ? $params->source->campaign : $params->source->campaign;
      $this->Content = property_exists($params->source, 'content') ? $params->source->content : $this->Content;
    }
  }

}
