<?php

class CRM_Commitcivi_Logic_StripeMigration
{
    /**
     * @param \CRM_Commitcivi_Model_Event $event
     *
     * @return integer
     * @throws \CiviCRM_API3_Exception
     */
    public function migrate(CRM_Commitcivi_Model_Event $event)
    {
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

        civicrm_api3(
            'StripeSubscription',
            'import',
            [
                'subscription_id' => $donation->stripeSubscriptionId,
                'contact_id' => $civicrm_recurring['values'][0]['contact_id'],
                'payment_processor_id' => 1, # Live Stripe Account
            ]
        );

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
