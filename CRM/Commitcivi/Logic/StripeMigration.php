<?php

class CRM_Commitcivi_Logic_StripeMigration {
    /**
     * @param \CRM_Commitcivi_Model_Event $event
     *
     * @return integer
     * @throws \CiviCRM_API3_Exception
     */
    public function migrate(CRM_Commitcivi_Model_Event $event) {
        // $contact = $event->contact;
        $donation = $event->donation;
        $recurringId = $event->donation->recurringId;

        $civicrm_recurring = civicrm_api3(
            'ContributionRecur',
            'get',
            [
                'sequential' => 1,
                'trxn_id' => "cc_{$recurringId}",
            ]
        );

        if (!$civicrm_recurring) {
            $event_as_json = json_encode($event);
            CRM_Core_Error::fatal("Couldn't find a recurring donation for recurringId {$recurringId} {$event_as_json}");
        }

        $contactId = $civicrm_recurring['values'][0]['contact_id'];
        $date = date('Y-m-d');

        CRM_Core_Error::debug_log_message(
            "Importing Stripe subscription {$donation->stripeSubscriptionId} for civcrm_contact {$contactId}"
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

        $results = civicrm_api3(
            'StripeSubscription',
            'import',
            [
                'subscription_id' => $donation->stripeSubscriptionId,
                'contact_id' => $contactId,
                'payment_processor_id' => 1, # Live Stripe Account
                'contribution_source' => "Migrated from CommitChange {$date}",

            ]
        );

        $debug_results = json_encode($results);
        CRM_Core_Error::debug_log_message("Migrated recurring donation to {$debug_results}");

        civicrm_api3(
            'ContributionRecur',
            'cancel',
            [
                'id' => $civicrm_recurring['id'],
                'cancel_reason' => "Migrated to Stripe {$donation->stripeSubscriptionId}"
            ]
        );

        return 1;
    }
}
