<?php

require_once 'BaseTest.php';

/**
 * @group e2e
 */
class CRM_Commitcivi_ContactTest extends CRM_Commitcivi_BaseTest {

  public function testCreateAnonymous() {
    $event = new CRM_Commitcivi_Model_Event($this->anonymousOneOffEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $result = $processor->process($event);
    $this->assertEquals(1, $result);
  }

}
