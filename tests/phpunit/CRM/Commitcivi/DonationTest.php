<?php

require_once 'BaseTest.php';

/**
 * @group e2e
 */
class CRM_Commitcivi_DonationTest extends CRM_Commitcivi_BaseTest {

  public function testCreateDefaultDonation() {
    $event = new CRM_Commitcivi_Model_Event($this->oneOffStripeEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $result = $processor->process($event);
    $this->assertEquals(1, $result);
  }

  public function testCreateSepaDonation() {
    $event = new CRM_Commitcivi_Model_Event($this->recurringSepaEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $result = $processor->process($event);
    $this->assertEquals(1, $result);
  }

}
