<?php

class CRM_Commitcivi_Model_Donation {
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

  public function __construct($params) {
    $this->amount = $params->metadata->amount;
    $this->amountCharged = $params->metadata->amount_charged;
    $this->currency = $params->metadata->currency;
    $this->cardType = $params->metadata->card_type;
    $this->paymentProcessor = $params->metadata->payment_processor;
    $this->transactionId = $params->metadata->transaction_id;
    $this->customerId = $params->metadata->customer_id;
    $this->status = $params->metadata->status;
    $this->iban = $params->metadata->iban;
    $this->bic = $params->metadata->bic;
    $this->accountHolder = $params->metadata->account_holder;
    $this->bank = $params->metadata->bank;
  }

}
