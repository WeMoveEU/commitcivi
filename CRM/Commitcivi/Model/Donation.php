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
    $donation = $this->get($params);
    $this->amount = $donation->amount;
    $this->amountCharged = $donation->amount_charged;
    $this->currency = $donation->currency;
    $this->cardType = property_exists($donation, 'card_type') ? $donation->card_type : $this->cardType;
    $this->paymentProcessor = $donation->payment_processor;
    $this->transactionId = property_exists($donation, 'transaction_id') ? $donation->transaction_id : $this->transactionId;
    $this->customerId = property_exists($donation, 'customer_id') ? $donation->customer_id : $this->customerId;
    $this->status = $donation->status;
    $this->iban = property_exists($donation, 'iban') ? $donation->iban : $this->iban;
    $this->bic = property_exists($donation, 'bic') ? $donation->bic : $this->bic;
    $this->accountHolder = property_exists($donation, 'account_holder') ? $donation->account_holder : $this->accountHolder;
    $this->bank = property_exists($donation, 'bank') ? $donation->bank : $this->bank;
  }

  /**
   * Get donation object.
   *
   * @param object $params
   *
   * @return mixed
   */
  private function get($params) {
    if (property_exists($params, 'donation')) {
      return $params->donation;
    }
    return $params->metadata;
  }

}
