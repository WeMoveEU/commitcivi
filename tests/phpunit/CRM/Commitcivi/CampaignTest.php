<?php

require_once 'BaseTest.php';

/**
 * @group e2e
 */
class CRM_Commitcivi_CampaignTest extends CRM_Commitcivi_BaseTest {

  public function testExistingCampaign() {
    $event = new CRM_Commitcivi_Model_Event($this->anonymousOneOffEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $campaignId = $processor->campaign($event);
    $this->assertGreaterThan(0, $campaignId);
  }

  public function testNewCampaign() {
    $event = new CRM_Commitcivi_Model_Event($this->oneOffStripeEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $campaignId = $processor->campaign($event);
    $this->assertGreaterThan(0, $campaignId);
  }

}
