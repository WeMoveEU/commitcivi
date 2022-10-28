<?php

function civicrm_api3_find_abandoned_carts($params) {

  $start_date = $params['start_date'];
  if (! $start_date) {
    throw new Exception("missing param: start_date!");
  }

  $group_id = CRM_Core_DAO::singleValueQuery(
    "SELECT id FROM civicrm_group " .
    "WHERE name = '2022-EOY-abandoned-cart'"
  );

  if (! $group_id ) {
    CRM_Core_DAO::executeQuery(
      "INSERT IGNORE INTO civicrm_group (name, title, description)" .
      " VALUES ('2022-EOY-abandoned-cart', '2022 EOY Abandoned Cart', 'Clicked, but did not donate.')"
    );

    $group_id = CRM_Core_DAO::singleValueQuery(
      "SELECT id FROM civicrm_group " .
      "WHERE name = '2022-EOY-abandoned-cart'"
    );

  }

  CRM_Core_DAO::executeQuery(
    "DELETE FROM civicrm_group_contact " .
    "WHERE group_id = {$group_id}"
  );

  $query_params = ['1' => [$params['start_date'], 'String']];

  $dao = CRM_Core_DAO::executeQuery(<<<SQL
    SELECT queue.contact_id
    FROM civicrm_mailing_event_trackable_url_open click
        JOIN civicrm_mailing_trackable_url url ON (click.trackable_url_id = url.id)
        JOIN civicrm_mailing_event_queue queue ON (queue.id = click.event_queue_id)
        JOIN civicrm_mailing_job job ON (job.id = queue.job_id)

        JOIN civicrm_mailing mailing ON (job.mailing_id = mailing.id)
        JOIN civicrm_campaign campaign ON (
            mailing.campaign_id = campaign.id
            and campaign.title like 'Fundraising-%'
        )
        LEFT JOIN civicrm_contribution donation ON (
            donation.contact_id = queue.contact_id
            AND donation.receive_date > %1
        )
    WHERE mailing.is_completed = 1
        AND mailing.scheduled_date > %1
        AND donation.id IS NULL
SQL
    , $query_params
);

  $abandoners = [];

  while ($dao->fetch()) {
    array_push($abandoners, $dao->contact_id);
  }

  _insert_group_contacts($group_id, $abandoners);

  $results['status'] = True;
  $results['carts'] = count($abandoners);
  $results['group_id'] = $group_id;

  return $results;
}