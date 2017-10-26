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
    $this->cardType = property_exists($params->donation, 'card_type') ? $params->donation->card_type : $this->cardType;
    $this->paymentProcessor = $params->donation->payment_processor;
    $this->transactionId = property_exists($params->donation, 'transaction_id') ? $params->donation->transaction_id : $this->transactionId;
    $this->customerId = property_exists($params->donation, 'customer_id') ? $params->donation->customer_id : $this->customerId;
    $this->status = $params->donation->status;
    $this->iban = property_exists($params->donation, 'iban') ? $params->donation->iban : $this->iban;
    $this->bic = property_exists($params->donation, 'bic') ? $params->donation->bic : $this->bic;
    $this->accountHolder = property_exists($params->donation, 'account_holder') ? $params->donation->account_holder : $this->accountHolder;
    $this->bank = property_exists($params->donation, 'bank') ? $params->donation->bank : $this->bank;
  }

}
