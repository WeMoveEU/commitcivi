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
  public $email = '';
  public $zip = '';
  public $country = '';
  public $amount = 0;
  public $amountCharged = 0;
  public $currency = '';
  public $cardType = '';
  public $paymentProcessor = '';
  public $transactionId = '';
  public $customerId = '';
  public $status = '';
  public $iban = '';
  public $bic = '';
  public $accountHolder = '';
  public $bank = '';
  public $utmSource = '';
  public $utmMedium = '';
  public $utmCampaign = '';
  public $utmContent = '';

  public function __construct($params) {
    $this->params = $params;
    $this->parse();
  }

  private function parse() {
    $this->actionType = $this->params->action_type;
    $this->actionTechnicalType = $this->params->action_technical_type;
    $this->actionName = $this->params->action_name;
    $this->createDate = $this->params->create_dt;
    $this->externalId = $this->params->external_id;
    $this->firstname = $this->params->cons_hash->firstname;
    $this->lastname = $this->params->cons_hash->lastname;
    $this->email = $this->params->cons_hash->emails[0]->email;
    $this->zip = $this->params->cons_hash->addresses[0]->zip;
    $this->country = $this->params->cons_hash->addresses[0]->country;
    $this->amount = $this->params->metadata->amount;
    $this->amountCharged = $this->params->metadata->amount_charged;
    $this->currency = $this->params->metadata->currency;
    $this->cardType = $this->params->metadata->card_type;
    $this->paymentProcessor = $this->params->metadata->payment_processor;
    $this->transactionId = $this->params->metadata->transaction_id;
    $this->customerId = $this->params->metadata->customer_id;
    $this->status = $this->params->metadata->status;
    $this->iban = $this->params->metadata->iban;
    $this->bic = $this->params->metadata->bic;
    $this->accountHolder = $this->params->metadata->account_holder;
    $this->bank = $this->params->metadata->bank;
    $this->utmSource = $this->params->source->source;
    $this->utmMedium = $this->params->source->medium;
    $this->utmCampaign = $this->params->source->campaign;
    $this->utmContent = $this->params->source->content;
  }

}
