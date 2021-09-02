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

function civicrm_api3_commitcivi_new_recurring_donors($params) {

  # Create a new group of new recurring donors over the last N months

  $now = new DateTime();
  $previous = new DateTime(); 
  $previous->modify("-{$params['months']} month");

  # preload the mapping of languages to groups

  $base_group_name = "new-recurring-donors-monthly-{$previous->format('Y-m')}-";
  $base_group_title = ucwords(str_replace('-', ' ', $base_group_name));

  $results = CRM_Core_DAO::executeQuery(
    'SELECT UPPER(SUBSTRING(name FROM 1 FOR instr(name, "-language") -1)) iso, ' .
    'id ' .
    'FROM civicrm_group ' .
    'WHERE name LIKE "%language-activists" '
  );
  $groups = $results->fetchMap("iso", "id");

  #
  # find new recurring donors
  # for each donor
  #     find their language group
  #     add to the language to_add[language code].push(donor.id)
  #  for each language 
  #     create a new group "New recurring donors month - {previous-year-month} {language code}"
  #     insert contacts into the new group
  #

  $result = CRM_Core_DAO::executeQuery(
    "SELECT DISTINCT contact_id " .
    " FROM civicrm_contribution_recur " .
    " WHERE LEFT(create_date, 7) =  LEFT(NOW() - INTERVAL 1 MONTH, 7)"
  );
  $donors = [];
  while ($result->fetch()) {
     array_push($donors, $result->contact_id);
  }

  $to_add = [];
  foreach ($donors as $donor_id) {
    $result = CRM_Core_DAO::executeQuery(
      "SELECT " .
      " UPPER(SUBSTRING(name FROM 1 FOR instr(name, '-language') - 1)) iso " .
      "FROM civicrm_group_contact " .
      " JOIN civicrm_group ON (group_id=civicrm_group.id) " .
      "WHERE name LIKE '%-language-activists' AND contact_id = {$donor_id} "
    );
    $iso = $result->fetchValue();
    if (array_key_exists($iso, $to_add)) {
      array_push($to_add[$iso], $donor_id);
    } else {
     $to_add[$iso] = [$donor_id];
    }
  }

  var_dump($to_add);

  foreach (array_keys($to_add) as $iso) {
    $group_name = $base_group_name . "{$iso}";
    $group_title = $base_group_title  . " $iso";
    CRM_Core_Error::debug_log_message(
      "INSERT IGNORE INTO civicrm_group (name, title, refresh_date, is_active, group_type) " . 
      " VALUES ( " .
      "  '{$group_name}', '{$group_title}', NOW(), 1, 2 " .
      " )"
    );
    CRM_Core_DAO::executeQuery(
      "INSERT IGNORE INTO civicrm_group (name, title, refresh_date, is_active, group_type) " . 
      " VALUES ( " .
      "  '{$group_name}', '{$group_title}', NOW(), 1, 2 " .
      " )"
    );
    // CRM_Core_DAO::executeQuery("COMMIT");

    CRM_Core_Error::debug_log_message(
        "SELECT id FROM civicrm_group WHERE name = '{$group_name}'"
    );

    $result = CRM_Core_DAO::executeQuery(
        "SELECT id FROM civicrm_group WHERE name = '{$group_name}'"
    );
    $group_id = $result->fetchValue();
    CRM_Core_Error::debug_log_message("Found {$group_id} for {$group_name} in {$iso}");

    $values = [];
    foreach ($to_add[$iso] as $contact_id) {
        CRM_Core_Error::debug_log_message("({$contact_id}, {$group_id}, 'Added')");
        array_push($values, "({$contact_id}, {$group_id}, 'Added')");
    }

    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_group_contact (contact_id, group_id, status) " .
      "VALUES " . join(", ", $values) 
    );
  }

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
