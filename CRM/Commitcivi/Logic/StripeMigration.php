<?php

class CRM_Commitcivi_Logic_StripeMigration {
  /**
   * @param \CRM_Commitcivi_Model_Event $event
   *
   * @return integer
   * @throws \CiviCRM_API3_Exception
   */
  public function migrate(CRM_Commitcivi_Model_Event $event) {
    $contact = $event->contact;
    $donation = $event->donation;
    $recurringId = $event->donation->recurringId;
    $event_as_json = json_encode($event);

    CRM_Core_Error::debug_log_message("Event received : {$event_as_json}");

    $civicrm_recurring = civicrm_api3(
      'ContributionRecur',
      'get',
      [
        'sequential' => 1,
        'trxn_id' => "cc_{$recurringId}",
      ]
    );

    if ($civicrm_recurring['count'] == 0) {
      CRM_Core_Error::debug_log_message(
        "[This is ok, just letting you know] " .
          "Couldn't find a recurring donation for recurringId {$recurringId} {$event_as_json}"
      );
    }

    if ($civicrm_recurring['count'] > 0) {
      $recurring_donation = $civicrm_recurring['values'][0];
      $contactId = $recurring_donation['contact_id'];
    }

    if (!$contactId) {
      $contactId = $this->find_contact_by_email($contact->email, $contact->firstname, $contact->lastname);
    }

    CRM_Core_Error::debug_log_message(
      "Importing Stripe subscription {$donation->stripeSubscriptionId} for civicrm_contact {$contactId}"
    );

    # Handling the customer here is a workaround for a bug in the StripeSubscription.import
    # API - the customer isn't created if it doesn't exist. The subscription is still
    # created but later payments aren't attached.
    $customerParams = [
      'customer_id' => $donation->stripeCustomerId,
      'contact_id' => $contactId,
      'processor_id' => 1, # Live Stripe Account
    ];
    $customer = civicrm_api3('StripeCustomer', 'get', $customerParams);
    if ($customer['count'] == 0) {
      $customer = civicrm_api3('StripeCustomer', 'create', $customerParams);
    }

    try {
      $date = date('Y-m-d');
      $result = civicrm_api3(
        'StripeSubscription',
        'import',
        [
          'subscription_id' => $donation->stripeSubscriptionId,
          'contact_id' => $contactId,
          'payment_processor_id' => 1, # Live Stripe Account
          'payment_instrument' => 'Debit Card',  # Name of payment instrument with label Stripe...
          'contribution_source' => "Migrated from CommitChange {$date}",
        ]
      );

      $migrated_id = $result['values']['recur_id'];

      $this->copyUTMS($recurring_donation['id'], $migrated_id);

      $debug_results = json_encode($result);
      CRM_Core_Error::debug_log_message("Migrated recurring donation to {$migrated_id} {$debug_results}");

      if ($donation->isWeekly) {
        $this->setWeekly($event, $migrated_id, $donation->weeklyAmount);
      }
    } catch (CiviCRM_API3_Exception $e) {
      if (preg_match('/Found matching recurring contribution/', $e->getMessage())) {
        CRM_Core_Error::debug_log_message("[This is OK, just letting you know] {$e->getMessage()}");
        # keep going to make sure the previous recurring donation is cancelled
      } else {
        throw $e;
      }
    }

    if ($recurring_donation['id']) {
      CRM_Core_Error::debug_log_message("Cancelling {$recurring_donation['id']}");

      civicrm_api3(
        'ContributionRecur',
        'cancel',
        [
          'id' => $recurring_donation['id'],
          'cancel_reason' => "Migrated to Stripe {$donation->stripeSubscriptionId}"
        ]
      );
    }

    return 1;
  }

  private function find_contact_by_email($email, $first, $last) {
    CRM_Core_Error::debug_log_message("Looking for contact using email: {$email}");
    # "contact":{"language":"","firstname":"","lastname":"","emails":[{"email":"user@example.com"}]
    $contact = civicrm_api3(
      'Email',
      'get',
      [
        'sequential' => 1,
        array('sort' => "id DESC"),
        'is_primary' => 1,
        'email' => $email
      ]
    );

    if ($contact['count'] == 0) {
      $contact = civicrm_api3(
        'Contact',
        'create',
        [
          "first_name" => $first,
          "last_name" => $last,
          "contact_type" => "Individual",
          "email" => $email,
        ]
      );
      civicrm_api3(
        'Email',
        'create',
        ["contact_id" => $contact['id'], "email" => $email, "is_primary" => 1]
      );
      return $contact['id'];
    }

    return $contact['values'][0]['contact_id'];
  }

  private static function getWeeklyCustomFieldId($name) {
    $result = civicrm_api3('CustomField', 'get', [
      'sequential' => 1,
      'custom_group_id' => 'recur_weekly',
      'name' => $name,
    ]);
    return 'custom_' . $result['id'];
  }

  private function setWeekly(CRM_Commitcivi_Model_Event $event, $recurring_id, $weekly_amount) {
    $params = [
      'sequential' => 1,
      'id' => $recurring_id,
    ];

    $params[$this->getWeeklyCustomFieldId('is_weekly')] = true;
    $params[$this->getWeeklyCustomFieldId('weekly_amount')] = $weekly_amount;

    CRM_Core_Error::debug_log_message(
      "calling ContributionRecur::create with ${params}"
    );
    civicrm_api3('ContributionRecur', 'create', $params);
  }

  /**
   * Set UTM fields for recurring contribution
   * @param \CRM_Commitcivi_Model_Event $event
   * @param $recurId
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function copyUTMS($canceled, $migrated) {
    CRM_Core_Error::debug_log_message(
      "Copying UTMs from $canceled to $migrated"
    );
    CRM_Core_DAO::executeQuery(
    "INSERT INTO civicrm_value_recur_utm
     (entity_id, utm_source, utm_campaign, utm_medium, utm_content)
     SELECT ${migrated}, utm_source, utm_campaign, utm_medium, utm_content
     FROM civicrm_value_recur_utm cancelled
     WHERE cancelled.entity_id = ${canceled}"
    );
  }
}
