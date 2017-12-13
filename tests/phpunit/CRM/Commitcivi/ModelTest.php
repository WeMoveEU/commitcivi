<?php

require_once 'BaseTest.php';

/**
 * @group e2e
 */
class CRM_Commitcivi_ModelTest extends CRM_Commitcivi_BaseTest {

  public function testContactWithAllFields() {
    $event = new CRM_Commitcivi_Model_Event($this->singleStripeEvent());
    $this->assertEquals('Test', $event->contact->firstname);
    $this->assertEquals('Testowski', $event->contact->lastname);
    $this->assertEquals('test+t1@example.com', $event->contact->email);
    $this->assertEquals('01-234', $event->contact->postalCode);
    $this->assertEquals('PL', $event->contact->country);
  }

  public function testContactWithAllFieldsInOldStyle() {
    $event = new CRM_Commitcivi_Model_Event($this->singleStripeOldStyleEvent());
    $this->assertEquals('Test', $event->contact->firstname);
    $this->assertEquals('Testowski', $event->contact->lastname);
    $this->assertEquals('test+t1@example.com', $event->contact->email);
    $this->assertEquals('01-234', $event->contact->postalCode);
    $this->assertEquals('PL', $event->contact->country);
  }

  public function testSingleStripe() {
    $event = new CRM_Commitcivi_Model_Event($this->singleStripeEvent());
    $this->assertEquals(15.67, $event->donation->amount, '', 0.001);
    $this->assertEquals(0.17, $event->donation->amountCharged, '', 0.001);
    $this->assertEquals('EUR', $event->donation->currency);
    $this->assertEquals('Visa', $event->donation->cardType);
    $this->assertEquals('stripe', $event->donation->paymentProcessor);
    $this->assertEquals('single', $event->donation->type);
    $this->assertEquals('ch_1NHwmdLnnERTfiJAMNHyFjV4', $event->donation->transactionId);
    $this->assertEquals('cus_Bb94Wds2n3xCVB', $event->donation->customerId);
    $this->assertEquals('success', $event->donation->status);
  }

  public function testDonationOneOffInOldStyle() {
    $event = new CRM_Commitcivi_Model_Event($this->singleStripeOldStyleEvent());
    $this->assertEquals(15.67, $event->donation->amount, '', 0.001);
    $this->assertEquals(0.17, $event->donation->amountCharged, '', 0.001);
    $this->assertEquals('EUR', $event->donation->currency);
    $this->assertEquals('Visa', $event->donation->cardType);
    $this->assertEquals('stripe', $event->donation->paymentProcessor);
    $this->assertEquals('ch_1NHwmdLnnERTfiJAMNHyFjV4', $event->donation->transactionId);
    $this->assertEquals('cus_Bb94Wds2n3xCVB', $event->donation->customerId);
    $this->assertEquals('success', $event->donation->status);
  }

  public function testDonationRecurring() {
    $event = new CRM_Commitcivi_Model_Event($this->recurringSepaEvent());
    $this->assertEquals(42, $event->donation->amount, '', 0.001);
    $this->assertEquals(0, $event->donation->amountCharged, '', 0.001);
    $this->assertEquals('EUR', $event->donation->currency);
    $this->assertEquals('sepa', $event->donation->paymentProcessor);
    $this->assertEquals('PL83101010230000261395100000', $event->donation->iban);
    $this->assertEquals('NBPLPLPWXXX', $event->donation->bic);
    $this->assertEquals('Holder name', $event->donation->accountHolder);
    $this->assertEquals('Narodowy Bank Polski, O/Okr. w Warszawie', $event->donation->bank);
    $this->assertEquals('success', $event->donation->status);
  }

}
