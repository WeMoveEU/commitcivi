<?php

class CRM_Commitcivi_Model_Donation {

  const PAYMENT_PROCESSOR_SEPA = 'sepa';
  const PAYMENT_PROCESSOR_STRIPE = 'stripe';
  const TYPE_SINGLE = 'single';
  const TYPE_RECURRING = 'recurring';
  const TYPE_STRIPE_MIGRATION = 'migrated_to_stripe';

  public $amount = 0;
  public $amountCharged = 0;
  public $currency = '';
  public $cardType = '';
  public $paymentProcessor = '';

  /**
   * @var string Type of donation: single or recurring
   */
  public $type = '';
  public $transactionId = '';
  public $recurringId = '';
  public $customerId = '';
  public $status = '';
  public $iban = '';
  public $bic = '';
  public $accountHolder = '';
  public $bank = '';
  public $stripeSubscriptionId = '';
  public $stripeCustomerId = '';
  public $stripeRecurringStart = '';
  public $isWeekly = false;
  public $weeklyAmount = 0;

  public function __construct($params) {
    $donation = $this->get($params);
    $this->amount = $donation->amount;
    $this->amountCharged = $donation->amount_charged;
    $this->currency = strtoupper($donation->currency);
    $this->cardType = property_exists($donation, 'card_type') ? $donation->card_type : $this->cardType;
    $this->paymentProcessor = $donation->payment_processor;
    $this->type = $donation->type;
    $this->transactionId = property_exists($donation, 'transaction_id') ? $donation->transaction_id : $this->transactionId;
    $this->recurringId = property_exists($donation, 'recurring_id') ? $donation->recurring_id : $this->recurringId;
    $this->customerId = property_exists($donation, 'customer_id') ? $donation->customer_id : $this->customerId;
    $this->status = $donation->status;
    $this->iban = property_exists($donation, 'iban') ? $donation->iban : $this->iban;
    $this->iban = str_replace([' ', '-', "\t"], '', $this->iban);
    $this->bic = property_exists($donation, 'bic') ? $donation->bic : $this->bic;
    $this->accountHolder = property_exists($donation, 'account_holder') ? $donation->account_holder : $this->accountHolder;
    $this->bank = property_exists($donation, 'bank') ? $donation->bank : $this->bank;
    $this->stripeSubscriptionId = property_exists($donation, 'stripe_subscription_id') ? $donation->stripe_subscription_id : $this->stripeSubscriptionId;
    $this->stripeCustomerId = property_exists($donation, 'stripe_customer_id') ? $donation->stripe_customer_id : $this->stripeCustomerId;
    $this->stripeRecurringStart = property_exists($donation, 'stripe_recurring_start') ? $donation->stripe_recurring_start : $this->stripeRecurringStart;
    $this->isWeekly = property_exists($params, 'recurring') ? $params->recurring->is_weekly : $this->isWeekly;
    $this->weeklyAmount = property_exists($params, 'recurring') ? $params->recurring->weekly_amount : $this->weeklyAmount;
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
