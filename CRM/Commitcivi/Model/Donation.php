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
    $this->amount = $params->donation->amount;
    $this->amountCharged = $params->donation->amount_charged;
    $this->currency = $params->donation->currency;
    $this->cardType = $params->donation->card_type;
    $this->paymentProcessor = $params->donation->payment_processor;
    $this->transactionId = $params->donation->transaction_id;
    $this->customerId = $params->donation->customer_id;
    $this->status = $params->donation->status;
    $this->iban = $params->donation->iban;
    $this->bic = $params->donation->bic;
    $this->accountHolder = $params->donation->account_holder;
    $this->bank = $params->donation->bank;
  }

}
