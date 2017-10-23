<?php

function debug($msg) {
  echo time(), ': ', $msg, "\n";
}

//Load amqp-lib
require_once __DIR__ . '/vendor/autoload.php';

session_start();

//Load the site settings for CiviCRM, with a few overrides
$settingsFile = trim(implode('', file('sitepath.inc'))).'/civicrm.settings.php';
define('CIVICRM_SETTINGS_PATH', $settingsFile);
define('CIVICRM_CLEANURL', 1);
define('CIVICRM_MAILER_TRANSIENT', 1);

$included = @include_once( $settingsFile );
if (!$included) {
  debug("Could not load the settings file at: {$settingsFile}");
  exit( );
}


// Load class loader
global $civicrm_root;
require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();
require_once 'CRM/Core/Config.php';
$civicrm_config = CRM_Core_Config::singleton();
//Load CMS with user id 1
CRM_Utils_System::loadBootStrap(array('uid' => 1), TRUE, FALSE);

//User errors normally don't block the execution, but in this case we do want 
//the event processing to fail completely so that it goes to the error queue
//and we are aware of the problem
set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
  if (0 === error_reporting()) { return false; }
  switch ($err_severity) {
    case E_USER_ERROR:
      debug("Uncaught E_USER_ERROR: forcing exception");
      throw new Exception($err_msg);
  }
  return false;
});

//Cmd line arguments
//Only the name of the queue to consume is mandatory
$arguments = getopt('q:e:r:');
$queue_name = $arguments['q'];
$error_queue = CRM_Utils_Array::value('e', $arguments, NULL);
$retry_exchange = CRM_Utils_Array::value('r', $arguments, NULL);

//Start consuming the queue
$consumer = new CRM_Commitcivi_Consumer($queue_name, $error_queue, $retry_exchange);
$consumer->start();
