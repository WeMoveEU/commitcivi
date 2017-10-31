<?php

require_once 'BaseTest.php';

/**
 * @group e2e
 */
class CRM_Commitcivi_ModelTest extends CRM_Commitcivi_BaseTest {

  public function testContactWithAllFields() {
    $event = new CRM_Commitcivi_Model_Event($this->oneOffStripeEvent());
    $this->assertEquals('Test', $event->contact->firstname);
    $this->assertEquals('Testowski', $event->contact->lastname);
    $this->assertEquals('test+t1@example.com', $event->contact->email);
    $this->assertEquals('01-234', $event->contact->postalCode);
    $this->assertEquals('PL', $event->contact->country);
  }

  public function testContactWithAllFieldsInOldStyle() {
    $event = new CRM_Commitcivi_Model_Event($this->oneOffStripeOldStyleEvent());
    $this->assertEquals('Test', $event->contact->firstname);
    $this->assertEquals('Testowski', $event->contact->lastname);
    $this->assertEquals('test+t1@example.com', $event->contact->email);
    $this->assertEquals('01-234', $event->contact->postalCode);
    $this->assertEquals('PL', $event->contact->country);
  }

}
