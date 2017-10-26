<?php

use \Civi\Test\HookInterface;
use \Civi\Test\HeadlessInterface;
use \Civi\Test\TransactionalInterface;


/**
 * Common methods for Commitcivi tests
 */
abstract class CRM_Commitcivi_BaseTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  protected function oneOffStripeEvent() {
    return (object) [
      'action_type' => 'donate',
      'action_technical_type' => 'cc.wemove.eu:donate',
      'create_dt' => '2017-10-25T12:34:56.531Z',
      'action_name' => 'campaign-PL',
      'external_id' => 50001,
      'contact' => (object) [
        'firstname' => 'Test',
        'lastname' => 'Testowski',
        'emails' => [
          0 => (object) [
            'email' => 'test+t1@example.com',
          ]
        ],
        'addresses' => [
          0 => (object) [
            'zip' => '01-234',
            'country' => 'pl',
          ],
        ],
      ],
      'donation' => (object) [
        "amount" => 15.67,
        "amount_charged" => 0.17,
        "currency" => "EUR",
        "card_type" => "Visa",
        "payment_processor" => "stripe",
        "transaction_id" => "ch_1NHwmdLnnERTfiJAMNHyFjV4",
        "customer_id" => "cus_Bb94Wds2n3xCVB",
        "status" => "success",
      ],
      'source' => (object) [
        "source" => "phpunit",
        "medium" => "phpstorm",
        "campaign" => "testing",
      ],
    ];
  }

  protected function recurringSepaEvent() {
    return (object) [
      'action_type' => 'donate',
      'action_technical_type' => 'cc.wemove.eu:donate',
      'create_dt' => '2017-10-25T13:45:56.531Z',
      'action_name' => 'campaign-PL',
      'external_id' => 50001,
      'contact' => (object) [
        'firstname' => 'Test',
        'lastname' => 'Testowski',
        'emails' => [
          0 => (object) [
            'email' => 'test+t1@example.com',
          ]
        ],
        'addresses' => [
          0 => (object) [
            'zip' => '01-234',
            'country' => 'pl',
          ],
        ],
      ],
      'donation' => (object) [
        "amount" => 42,
        "amount_charged" => 0,
        "currency" => "EUR",
        "payment_processor" => "sepa",
        "iban" => "FULLIBANHERE",
        "bic" => "CODEXXYY",
        "account_holder" => "Holder name",
        "bank" => "Bank name (free text)",
        "status" => "success",
      ],
      'source' => (object) [
        "source" => "phpunit",
        "medium" => "phpstorm",
        "campaign" => "testing",
      ],
    ];
  }

}
