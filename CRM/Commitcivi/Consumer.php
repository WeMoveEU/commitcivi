<?php

use PhpAmqpLib\Connection\AMQPSSLConnection;
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
  public $dieOnError = TRUE;

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
   * @param $msg - AMQPMessage instance
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
          }
          else {
            $this->handleErrorCode($msg, $result, $json_msg);
          }
        }
        catch (Exception $ex) {
          $this->handleException($msg, $ex);
        }
      }
      else {
        $this->handleError($msg, "Could not decode " . $msg->body);
      }
    }
    catch (Exception $ex) {
      $this->handleException($msg, $ex);
    }
    finally {
      $this->msg_since_check++;
    }
  }

  /**
   * Connect to RabbitMQ and enters an infinite loop waiting for incoming messages.
   * Regularly check the server load, and pauses the consumption when the load is too high
   */
  public function start($callbackFunction = 'processMessage') {
    try {
      $connection = $this->connect();
      $channel = $connection->channel();
      $channel->basic_qos(NULL, $this->loadCheckPeriod, NULL);
    } catch (Exception $ex) {
      // If an exception occurs while waiting for a message, the CMS custom error handler will catch it and the process will exit with status 0,
      // which would prevent the systemd service from automatically restarting. Using handleError prevents this behaviour.
      $this->handleError(NULL, CRM_Core_Error::formatTextException($ex));
    }

    while (TRUE) {
      while (count($channel->callbacks)) {
        if ($this->msg_since_check >= $this->loadCheckPeriod) {
          if ($this->isLoadTooHigh()) {
            //Stop consumption and redeliver all pre-fetched messages
            $channel->basic_cancel($cb_name);
            $channel->basic_recover(TRUE);
            continue;
          }
          else {
            $this->msg_since_check = 0;
          }
        }
        try {
          $channel->wait();
        } catch (Exception $ex) {
          // If an exception occurs while waiting for a message, the CMS custom error handler will catch it and the process will exit with status 0,
          // which would prevent the systemd service from automatically restarting. Using handleError prevents this behaviour.
          $this->handleError(NULL, CRM_Core_Error::formatTextException($ex));
        }
      }

      if ($this->isLoadTooHigh()) {
        $channel->close();
        $connection->close();
        sleep($this->coolingPeriod);
      }
      else {
        if (!$connection->isConnected()) {
          try {
            $connection = $this->connect();
            $channel = $connection->channel();
            //Never pre-fetch more than `loadCheckPeriod` messages from the queue
            $channel->basic_qos(NULL, $this->loadCheckPeriod, NULL);
          } catch (Exception $ex) {
            // If an exception occurs while waiting for a message, the CMS custom error handler will catch it and the process will exit with status 0,
            // which would prevent the systemd service from automatically restarting. Using handleError prevents this behaviour.
            $this->handleError(NULL, CRM_Core_Error::formatTextException($ex));
          }
        }
        //Register callback for incoming messages
        $cb_name = $channel->basic_consume($this->queue, '', FALSE, FALSE, FALSE, FALSE, array($this, $callbackFunction));
      }
    }
  }

  /**
   * Tell whether the system load is above the configured threshold
   */
  protected function isLoadTooHigh() {
    $load = sys_getloadavg()[$this->loadAverageIndex];
    return $load > $this->maxLoad;
  }

  /**
   * Determine proper error message based on error code
   */
  protected function handleErrorCode($amqp_msg, $code, $json_msg) {
    if ($code == -1) {
      $this->handleError($amqp_msg, "runParams unsupported action type: " . $json_msg->action_type);
    }
    else {
      $session = CRM_Core_Session::singleton();
      $retry = $this->isConnectionLostError($session->getStatus());
      $this->handleError($amqp_msg, "runParams returned error code $code", $retry);
    }
  }

  /**
   * Handle an exception thrown while processing an incoming message.
   * Depending on the exception type and message, and depending on the runtime
   * configuration, the incoming message is published to the error queue,
   * retry exchange, or simpled NACKed.
   * For a specific error code, the message is ACKed???
   */
  protected function handleException($amqp_msg, $ex) {
    $retry = FALSE;
    if ($ex instanceof CiviCRM_API3_Exception) {
      $retry = $this->retry($ex->getExtraParams());
    }
    elseif ($ex instanceof CRM_Commitcivi_Exception) {
      if ($ex->getErrorCode() == 1) {
        CRM_Core_Error::debug_log_message('COMMITCIVI AMQP ' . $ex->getMessage());
        $amqp_msg->delivery_info['channel']->basic_ack($amqp_msg->delivery_info['delivery_tag']);
        return;
      }
    }
    $this->handleError($amqp_msg, CRM_Core_Error::formatTextException($ex), $retry);
  }

  /**
   * If $retry is trueish, nack the message without re-queue and send it to the retry exchange.
   * Otherwise if an error queue is defined, send it to that queue through the direct exchange.
   * Otherwise nack and re-deliver the message to the originating queue.
   * If no message is provided, simply log the error and die.
   */
  protected function handleError($msg, $error, $retry = FALSE) {
    CRM_Core_Error::debug_var("COMMITCIVI AMQP", $error, TRUE, TRUE);

    if ($msg) {
      $channel = $msg->delivery_info['channel'];
      if ($retry && $this->retry_exchange != NULL) {
        $channel->basic_nack($msg->delivery_info['delivery_tag']);
        $new_msg = new AMQPMessage($msg->body);
        $headers = new AMQPTable(array('x-delay' => $this->retryDelay));
        $new_msg->set('application_headers', $headers);
        $channel->basic_publish($new_msg, $this->retry_exchange, $msg->delivery_info['routing_key']);
      }
      elseif ($this->error_queue != NULL) {
        $channel->basic_nack($msg->delivery_info['delivery_tag']);
        $channel->basic_publish($msg, '', $this->error_queue);
      }
      else {
        $channel->basic_nack($msg->delivery_info['delivery_tag'], FALSE, TRUE);
      }
    }

    //In some cases (e.g. a lost connection), dying and respawning can solve the problem
    if ($this->dieOnError) {
      die(1);
    }
  }

  /**
   * Check whether error is linked with lost connection to smtp server.
   *
   * @param $sessionStatus
   *
   * @return bool
   */
  protected function isConnectionLostError($sessionStatus) {
    if (is_array($sessionStatus) && array_key_exists('title', $sessionStatus[0]) && $sessionStatus[0]['title'] == 'Mailing Error') {
      return !!strpos($sessionStatus[0]['text'], 'Connection lost to authentication server');
    }
    return FALSE;
  }

  /**
   * Check for known bug.
   *
   * @param $extraInfo
   *
   * @return bool
   */
  protected function retry($extraInfo) {
    $debugInformation = [
      'debug_information' => 'try restarting transaction',
      'error_message' => 'DB Error: no database selected',
    ];
    foreach ($debugInformation as $key => $information) {
      if (strpos(CRM_Utils_Array::value($key, $extraInfo), $information) !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  protected function connect() {
    return new AMQPSSLConnection(
      CIVICRM_AMQP_HOST, CIVICRM_AMQP_PORT,
      CIVICRM_AMQP_USER, CIVICRM_AMQP_PASSWORD, CIVICRM_AMQP_VHOST,
      array(
        'local_cert' => CIVICRM_SSL_CERT,
        'local_pk' => CIVICRM_SSL_KEY,
      )
    );
  }

}
