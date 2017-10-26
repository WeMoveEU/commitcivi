<?php

require_once 'BaseTest.php';

require_once __DIR__ . '/../../../../amqp/vendor/autoload.php';
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Tests related to the processing of incoming AMQP messages.
 * They are mostly concerned with checking that the error cases are handled correctly
 * since in the regular case most of the work is done by the event processor.
 *
 * N.B.: Because the tests are related to appropriate handling of exceptions,
 * the test may interfere with the tested code when an assertion fails. For this reason,
 * assertions on method calls checks that the call happens at least one,
 * rather than exactly once, as it would otherwise make the test results confusing.
 *
 * @group headless
 */
class CRM_Commitcivi_ConsumerTest extends CRM_Commitcivi_BaseTest {

  public function setUp() {
    parent::setUp();
    $this->queue = 'source';
    $this->error_queue = 'errors';
    $this->retry_exchange = 'retry';
    $this->delivery_tag = 'fake_tag';
    $this->consumer = new CRM_Commitcivi_Consumer(
        $this->queue, $this->error_queue, $this->retry_exchange);
    $this->consumer->dieOnError = FALSE;
  }

  /**
   * A malformed JSON should be pushed back to error queue
   */
  public function testProcessMessage_wrongJson() {
    $json = '{"wrong": json';
    $amqp_msg = $this->mockMessage($json);
    $this->assertIsNackedWithoutRequeue($amqp_msg);
    $this->assertIsPublishedToErrorQueue($amqp_msg);

    $this->consumer->processMessage($amqp_msg);
  }

  protected function mockMessage($json) {
    $msg = new AMQPMessage($json);
    $mockChannel = $this->getMockBuilder('\PhpAmqpLib\Channel\AMQPChannel')
        ->setMethods(array('basic_nack', 'basic_publish'))
        ->disableOriginalConstructor()
        ->getMock();
    $msg->delivery_info['channel'] = $mockChannel;
    $msg->delivery_info['delivery_tag'] = $this->delivery_tag;
    return $msg;
  }

  /* Custom assertions */

  protected function assertIsNackedWithoutRequeue($amqp_msg) {
    $amqp_msg->delivery_info['channel']
             ->expects($this->atLeastOnce())
             ->method('basic_nack')->with($this->delivery_tag, FALSE, FALSE);
  }

  protected function assertIsPublishedToErrorQueue($amqp_msg) {
    $amqp_msg->delivery_info['channel']
             ->expects($this->atLeastOnce())
             ->method('basic_publish')->with($amqp_msg, '', $this->error_queue);
  }

}
