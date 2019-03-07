<?php

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