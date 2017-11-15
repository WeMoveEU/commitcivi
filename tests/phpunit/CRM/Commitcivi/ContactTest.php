<?php

require_once 'BaseTest.php';

/**
 * @group e2e
 */
class CRM_Commitcivi_ContactTest extends CRM_Commitcivi_BaseTest {

  public function testCreateAnonymous() {
    $event = new CRM_Commitcivi_Model_Event($this->anonymousOneOffEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $campaignId = $processor->campaign($event);
    $contactId = $processor->contact($event, $campaignId);
    $this->assertGreaterThan(0, $contactId);
  }

  public function testCreateContactWithOldStyle() {
    $event = new CRM_Commitcivi_Model_Event($this->oneOffStripeOldStyleEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $campaignId = $processor->campaign($event);
    $contactId = $processor->contact($event, $campaignId);
    $this->assertGreaterThan(0, $contactId);
  }

  public function testCreateNew() {
    $event = new CRM_Commitcivi_Model_Event($this->oneOffStripeOldStyleEvent());
    $event->contact->email = time() . '@speakcivi.com';
    $processor = new CRM_Commitcivi_EventProcessor();
    $campaignId = $processor->campaign($event);
    $contactId = $processor->contact($event, $campaignId);
    $this->assertGreaterThan(0, $contactId);
  }

}
