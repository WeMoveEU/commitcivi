<?php

require_once 'BaseTest.php';

/**
 * @group e2e
 */
class CRM_Commitcivi_DonationTest extends CRM_Commitcivi_BaseTest {

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testSingleStripe() {
    $event = new CRM_Commitcivi_Model_Event($this->singleStripeEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $result = $processor->process($event);
    $this->assertEquals(1, $result);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testRecurringStripe() {
    $event = new CRM_Commitcivi_Model_Event($this->recurringStripeEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $result = $processor->process($event);
    $this->assertEquals(1, $result);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testRecurringStripeSecond() {
    $event = new CRM_Commitcivi_Model_Event($this->recurringStripeEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $result = $processor->process($event);
    $this->assertEquals(1, $result);
    $event = new CRM_Commitcivi_Model_Event($this->recurringStripeSecondEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $result = $processor->process($event);
    $this->assertEquals(1, $result);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testSingleSepa() {
    $event = new CRM_Commitcivi_Model_Event($this->singleSepaEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $result = $processor->process($event);
    $this->assertEquals(1, $result);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testRecurringSepa() {
    $event = new CRM_Commitcivi_Model_Event($this->recurringSepaEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $result = $processor->process($event);
    $this->assertEquals(1, $result);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testSepaDetails() {
    $event = new CRM_Commitcivi_Model_Event($this->recurringSepaEvent());
    $contact = civicrm_api3('Contact', 'get', array(
      'sequential' => 1,
      'options' => array('limit' => 1, 'sort' => "id DESC"),
    ));
    $contactId = $contact['id'];
    $campaign = civicrm_api3('Campaign', 'get', array(
      'sequential' => 1,
      'options' => array('limit' => 1, 'sort' => "id DESC"),
    ));
    $campaignId = $campaign['id'];

    $donation = new CRM_Commitcivi_Logic_DonationSepa();
    $mandate = $donation->sepa($event, $contactId, $campaignId);
    $mnd = $mandate['values'][0];
    $contributionRecur = civicrm_api3('ContributionRecur', 'get', array(
      'sequential' => 1,
      'id' => $mnd['entity_id'],
    ));
    $conrec = $contributionRecur['values'][0];

    $this->assertArrayHasKey('id', $mandate);
    $this->assertEquals('civicrm_contribution_recur', $mnd['entity_table']);
    $this->assertEquals($event->actionName, $mnd['source']);
    $this->assertEquals($event->donation->iban, $mnd['iban']);
    $this->assertEquals($event->donation->bic, $mnd['bic']);
    $this->assertEquals('RCUR', $mnd['type']);
    $this->assertEquals('FRST', $mnd['status']);
    $this->assertEquals($event->donation->amount, $conrec['amount']);
    $this->assertEquals($event->donation->currency, $conrec['currency']);
    $this->assertEquals('month', $conrec['frequency_unit']);
    $this->assertEquals(1, $conrec['frequency_interval']);
    $this->assertEquals(2, $conrec['contribution_status_id']);
    $this->assertEquals(CRM_Commitcivi_Logic_DonationSepa::CYCLE_DAY_FIRST, $conrec['cycle_day']);
    $this->assertEquals($campaignId, $conrec['campaign_id']);
  }

  public function testCycleDates() {
    $sepa = new CRM_Commitcivi_Logic_DonationSepa();
    $dates = [
      '2017-12-01T19:07:26Z' => '2017-12-06',
      '2017-12-03T19:07:26Z' => '2017-12-06',
      '2017-12-05T19:07:26Z' => '2017-12-06',
      '2017-12-06T19:07:26Z' => '2017-12-21',
      '2017-12-13T19:07:26Z' => '2017-12-21',
      '2017-12-20T19:07:26Z' => '2017-12-21',
      '2017-12-21T19:07:26Z' => '2018-01-06',
      '2017-12-22T19:07:26Z' => '2018-01-06',
      '2017-12-31T19:07:26Z' => '2018-01-06',
    ];
    foreach ($dates as $full => $expectedDate) {
      $calculatedDate = $sepa->cycleDate($full);
      $this->assertEquals($expectedDate, $calculatedDate);
      $calculatedDay = $sepa->cycleDay($full);
      $this->assertEquals((int) substr($expectedDate, 8, 2), $calculatedDay);
    }
  }

}
