<?php

require_once 'BaseTest.php';

/**
 * This is a lightweight unit-tested based on PHPUnit_Framework_TestCase.
 *
 * PHPUnit_Framework_TestCase is suitable for any of these:
 *  - Running tests which don't require any database.
 *  - Running tests on the main/live database.
 *  - Customizing the setup/teardown processes.
 *
 * @group headless
 */
class CRM_Commitcivi_DonationTest extends CRM_Commitcivi_BaseTest {

  public function testCreateDefaultDonation() {
    $event = new CRM_Commitcivi_Model_Event($this->paramsOneOffStripe);
    $processor = new CRM_Commitcivi_EventProcessor();
    $result = $processor->process($event);
    $this->assertEquals(1, $result);
  }

}
