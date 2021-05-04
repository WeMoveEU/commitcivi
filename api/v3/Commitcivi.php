<?php

function _civicrm_api3_commitcivi_process_donation_spec(&$spec) {
  $spec['message'] = [
    'name' => 'message',
    'title' => "Houdini message",
    'description' => "JSON message received from Houdini about the donation to process",
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
}

function civicrm_api3_commitcivi_process_donation($params) {
  $json_msg = json_decode($params['message']);
  if ($json_msg) {
    $event = new CRM_Commitcivi_Model_Event($json_msg);
    $processor = new CRM_Commitcivi_EventProcessor();
    $result_code = $processor->process($event);
    if ($result_code == -1) {
      return civicrm_api3_create_error("unsupported action type: $json_msg->action_type", ['retry_later' => FALSE]);
    }
    else if ($result_code != 1) {
      $session = CRM_Core_Session::singleton();
      $retry = _commitcivi_isConnectionLostError($session->getStatus());
      return civicrm_api3_create_error("Commitcivi event processor returned error code $result_code", ['retry_later' => $retry]);
    }
    else {
      return civicrm_api3_create_success();
    }
  }
  else {
    return civicrm_api3_create_error("Could not decode {$params['message']}", ['retry_later' => FALSE]);
  }
}

function _civicrm_api3_commitcivi_update_major_donors_spec(&$spec) {
  $spec['group_id'] = [
    'name' => 'group_id',
    'title' => ts('Group id'),
    'description' => 'Id of the group of major donors',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['yearly_threshold_amount'] = [
    'name' => 'yearly_threshold_amount',
    'title' => ts('Yearly threshold amount'),
    'description' => 'Yearly donation amount defining major donors',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'api.default' => 5000,
  ];
}

function civicrm_api3_commitcivi_update_major_donors($params) {
  $donors_result = civicrm_api3('GroupContact', 'get', ['group_id' => $params['group_id'], 'status' => 'Added']);
  $donor_ids = array_map(function($d) { return $d['contact_id']; }, $donors_result['values']);

  $added = [];
  $query = ""
  . "SELECT contact_id, year(receive_date) AS year, sum(total_amount) AS amount "
  . "FROM civicrm_contribution "
  . "WHERE contribution_status_id=1 "
  . "GROUP BY contact_id, year "
  . "HAVING amount > %1"
  ;
  $query_params = ['1' => [$params['yearly_threshold_amount'], 'Integer']];
  $result = CRM_Core_DAO::executeQuery($query, $query_params);
  while ($result->fetch()) {
    if (array_search($result->contact_id, $donor_ids) === FALSE
        && array_search($result->contact_id, $added) === FALSE) {
      $gc_params = ['contact_id' => $result->contact_id, 'group_id' => $params['group_id'], 'status' => 'Added'];
      $gc_result = civicrm_api3('GroupContact', 'create', $gc_params);
      if ($gc_result['added']) {
        $added[] = $result->contact_id;
      }
    }
  }

  $returnResult = ['added' => $added, 'added_count' => count($added)];
  return civicrm_api3_create_success($returnResult, $params);
}

function _civicrm_api3_commitcivi_new_recurring_donors_spec(&$spec) {
  $spec['days'] = [
    'name' => 'days',
    'title' => ts('Days Ago'),
    'description' => 'New recurring donor in the last N days',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'api.default' => 7,
  ];
}

function civicrm_api3_commitcivi_new_recurring_donors($params) {
  #
  # create a new group of new recurring donors since N days ago
  #

  $now = new DateTime();

  $date = new DateTime();
  $date->modify("-{$params['days']} day");

  $group_name = "new-recurring-donors-from-{$date->format('Y-m-d')}-to-{$now->format('Y-m-d')}";

  CRM_Core_DAO::executeQuery(
    "INSERT IGNORE INTO civicrm_group (name, title, refresh_date, is_active, group_type) " . 
    " VALUES ( " .
    "  '{$group_name}', " .
    "  'New recurring donors from {$date->format('Y-m-d')} to {$now->format('Y-m-d')}', " .
    "  NOW(), 1, 2 " .
    " )"
  );

  $result = CRM_Core_DAO::executeQuery(
    "SELECT * from civicrm_group WHERE name = '{$group_name}'"
  );
  $result->fetch();
  $group = $result->id;

  # add new recurring donors for the week to the group
  $outfile = "/var/lib/mysql-files/new-recurring-donors-{$date->format('U')}.csv";

  CRM_Core_DAO::executeQuery(
    "SELECT DISTINCT contact_id, {$group}, 'Added'" .
    " INTO OUTFILE '{$outfile}' " .
    " FROM civicrm_contribution_recur " .
    " WHERE create_date >= '{$date->format('Y-m-d 00:00:00')}'"
  );

  CRM_Core_DAO::executeQuery(
    "LOAD DATA INFILE '{$outfile}' " .
    "INTO TABLE " . 
    "civicrm_group_contact (contact_id, group_id, status)"
  );
}


function civicrm_api3_commitcivi_clean_new_recurring_donor_groups() {
  # find groups to clear out
  $date = new DateTime();
  $date->modify('-4 weeks');

  $result = CRM_Core_DAO::executeQuery(
    "SELECT id FROM civicrm_group " . 
    "WHERE name like 'new-recurring-donors-%' " .
    "AND refresh_date < '{$date->format('Y-m-d')}' "
  );

  while ($result->fetch()) {
    CRM_Core_DAO::executeQuery(
      "DELETE FROM civicrm_group_contact " .
      "WHERE group_id = {$result->id}"
    );
    CRM_Core_DAO::executeQuery(
      "DELETE FROM civicrm_group " .
      "WHERE id = {$result->id}"
    );
  }
}

function _commitcivi_isConnectionLostError($sessionStatus) {
  if (is_array($sessionStatus) && array_key_exists('title', $sessionStatus[0]) && $sessionStatus[0]['title'] == 'Mailing Error') {
    return !!strpos($sessionStatus[0]['text'], 'Connection lost to authentication server');
  }
  return FALSE;
}
