<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class CRM_Commitcivi_Consumer {
  /* Public config, overridable by caller */
  public $loadAverageIndex = 1; // index of the avg [1m,5m,15m]
  public $maxLoad = 3;
  public $loadCheckPeriod = 100; //Number of messages
  public $coolingPeriod = 20; //Seconds
  public $retryDelay = 60000; //Milliseconds

  /* Queue and exchange names, given at construction */
  private $queue = NULL;
  private $error_queue = NULL;
  private $retry_exchange = NULL;

  /* Other instance variables */
  private $msg_since_check = 0;

  public function __construct($queue, $error_queue = NULL, $retry_exchange = NULL) {
    $this->queue = $queue;
    $this->error_queue = $error_queue;
    $this->retry_exchange = $retry_exchange;
    $this->processor = new CRM_Commitcivi_EventProcessor();
  }

  /**
   * Callback that processes each RabbitMQ message.
   * It extracts the JSON event and gives it to the event processor.
   * Depending on the result, acknowledge the processed message or handle
   * appropriately the error.
   * @param msg - AMQPMessage instance
   */
  public function processMessage($msg) {
    try {
      $json_msg = json_decode($msg->body);
      if ($json_msg) {
        try {
          $event = new CRM_Commitcivi_Model_Event($json_msg);
          $result = $this->processor->process($event);
          if ($result == 1) {
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
          } elseif ($result == -1) {
            $this->handleError($msg, "runParams unsupported action type: " . $json_msg->action_type);
          } else {
            $session = CRM_Core_Session::singleton();
            $retry = $this->isConnectionLostError($session->getStatus());
            $this->handleError($msg, "runParams returned error code $result", $retry);
          }
        } catch (CiviCRM_API3_Exception $ex) {
          $extraInfo = $ex->getExtraParams();
          $retry = strpos(CRM_Utils_Array::value('debug_information', $extraInfo), "try restarting transaction");
          $this->handleError($msg, CRM_Core_Error::formatTextException($ex), $retry);
        } catch (CRM_Speakcivi_Exception $ex) {
          if ($ex->getErrorCode() == 1) {
            CRM_Core_Error::debug_log_message('SPEAKCIVI AMQP ' . $ex->getMessage());
            CRM_Core_Error::debug_var("SPEAKCIVI AMQP", $json_msg, true, true);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
          } else {
            $this->handleError($msg, CRM_Core_Error::formatTextException($ex));
          }
        } catch (Exception $ex) {
          $this->handleError($msg, CRM_Core_Error::formatTextException($ex));
        }
      } else {
        $this->handleError($msg, "Could not decode " . $msg->body);
      }
    } catch (Exception $ex) {
      $this->handleError($msg, CRM_Core_Error::formatTextException($ex));
    } finally {
      $this->msg_since_check++;
    }
  }

  /**
   * Connects to RabbitMQ and enters an infinite loop waiting for incoming messages.
   * Regularly checks the server load, and pauses the consumption when the load is too high
   */
  public function start() {
    $connection = $this->connect();
    $channel = $connection->channel();
    $channel->basic_qos(null, $this->loadCheckPeriod, null);
    while (true) {
      while (count($channel->callbacks)) {
        if ($this->msg_since_check >= $this->loadCheckPeriod) {
          $load = sys_getloadavg()[$this->loadAverageIndex];
          if ($load > $this->maxLoad) {
            $channel->basic_cancel($cb_name);
            $channel->basic_recover(true);
            continue;
          } else {
            $this->msg_since_check = 0;
          }
        }
        $channel->wait();
      }

      $load = sys_getloadavg()[$this->loadAverageIndex];
      if ($load > $this->maxLoad) {
        $channel->close();
        $connection->close();
        sleep($this->coolingPeriod);
      } else {
        if (!$connection->isConnected()) {
          $connection = connect();
          $channel = $connection->channel();
          $channel->basic_qos(null, $this->loadCheckPeriod, null);
        }
        $cb_name = $channel->basic_consume($this->queue, '', false, false, false, false, array($this, 'processMessage'));
      }
    }
  }

  /**
   * If $retry is trueish, nack the message without re-queue and send it to the retry exchange.
   * Otherwise if an error queue is defined, send it to that queue through the direct exchange.
   * Otherwise nack and re-deliver the message to the originating queue.
   */
  function handleError($msg, $error, $retry=false) {
    CRM_Core_Error::debug_var("SPEAKCIVI AMQP", $error, true, true);
    $channel = $msg->delivery_info['channel'];

    if ($retry && $this->retry_exchange != NULL) {
      $channel->basic_nack($msg->delivery_info['delivery_tag']);
      $new_msg = new AMQPMessage($msg->body);
      $headers = new AMQPTable(array('x-delay' => $this->retryDelay));
      $new_msg->set('application_headers', $headers);
      $channel->basic_publish($new_msg, $this->retry_exchange, $msg->delivery_info['routing_key']);
    } else if ($this->error_queue != NULL) {
      $channel->basic_nack($msg->delivery_info['delivery_tag']);
      $channel->basic_publish($msg, '', $this->error_queue);
    } else {
      $channel->basic_nack($msg->delivery_info['delivery_tag'], false, true);
    }
    
    //In some cases (e.g. a lost connection), dying and respawning can solve the problem
    die(1);
  }

  /**
   * Check whether error is linked with lost connection to smtp server.
   *
   * @param $sessionStatus
   *
   * @return bool
   */
  protected function isConnectionLostError($sessionStatus) {
    if (is_array($sessionStatus) && array_key_exists('title', $sessionStatus[0]) && $sessionStatus['title'] == 'Mailing Error') {
      return !!strpos($sessionStatus['text'], 'Connection lost to authentication server');
    }
    return false;
  }

  protected function connect() {
    return new AMQPStreamConnection(
      CIVICRM_AMQP_HOST, CIVICRM_AMQP_PORT,
      CIVICRM_AMQP_USER, CIVICRM_AMQP_PASSWORD, CIVICRM_AMQP_VHOST);
  }

}
