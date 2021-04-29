<?php

class CRM_Commitcivi_Logic_StripeMigration {
    

    /**
     * @param \CRM_Commitcivi_Model_Event $event
     *
     * @return integer
     * @throws \CiviCRM_API3_Exception
     */
    public function migrate(CRM_Commitcivi_Model_Event $event)
    {
        $contact = $event->contact;
        $donation = $event->donation;
        
        # find the current houdini recurring donation using email, processor, amount and start_date
        $query = ""
            . "SELECT recur.id recurring_id, contact.id contact_id "
            . " FROM civicrm_contribution_recur recur "
            . " JOIN civicrm_contact contact ON (contact.id=recur.contact_id) "
            . " JOIN civicrm_email email ON (contact.id=email.contact_id AND email.is_primary)"
            . "WHERE email.email = %1 "
            . "AND payment_processor_id = 11 "
            . "AND amount * 100 = %2 "  # units are 1s in Civi (3 euros) and 100ths (300) in Houdini 
            . "AND left(start_date, 10) = %3 ";

        $query_params = [
            '1' => [$contact->email, 'String'],
            '2' => [$donation->amount, 'Float'],
            '3' => [$donation->stripeRecurringStart, 'String']
        ];

        $result = CRM_Core_DAO::executeQuery($query, $query_params);
        $migrated = 0;

        while ($result->fetch()) {
            civicrm_api3('StripeSubscription', 'import', [
                'subscription_id' => $donation->stripeSubscriptionId,
                'contact_id' => $result->contact_id,
                'payment_processor_id' => 1  # NOTE: Verify!
            ]);
            civicrm_api3(
                'ContributionRecur',
                'cancel',
                [
                    'id' => $result->recurring_id,
                    'cancel_reason' => "Migrated to Stripe {$donation->stripeSubscriptionId}"
                ]
            );

            ++$migrated;
        }

        //
        // How do we report a warning without exiting the consumer?
        //
        return $migrated == 0 ? "Didn't find any recurring donations to migrate." : 1;
    }
}
