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
    $this->setDb();
  }

  public function tearDown() {
    parent::tearDown();
  }

  protected function oneOffStripeJson() {
    return
<<<JSON
    {
      "action_type":"donate",
      "action_technical_type":"cc.wemove.eu:donate",
      "create_dt":"2017-10-25T12:34:56.531Z",
      "action_name":"campaign-PL",
      "external_id":50001,
      "contact":{
        "firstname":"Test",
        "lastname":"Testowski",
        "emails":[{"email":"test+t1@example.com"}],
        "addresses":[
          {
            "zip":"01-234",
            "country":"pl"
          }
        ]
      },
      "donation":{
        "amount":15.67,
        "amount_charged":0.17,
        "currency":"EUR",
        "card_type":"Visa",
        "payment_processor":"stripe",
        "transaction_id":"ch_1NHwmdLnnERTfiJAMNHyFjV4",
        "customer_id":"cus_Bb94Wds2n3xCVB",
        "status":"success"
      },
      "source":{
        "source":"phpunit",
        "medium":"phpstorm",
        "campaign":"testing"
      }
    }
JSON;
  }

  protected function oneOffStripeEvent() {
    return json_decode($this->oneOffStripeJson());
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

  private function setDb() {
    civicrm_api3('OptionValue', 'create', [
      'sequential' => 1,
      'option_group_id' => "campaign_type",
      'name' => 'Fundraising',
      'title' => 'Fundraising',
      'weight' => 1
    ]);

    $params = [
      'sequential' => 1,
      'name' => 'members',
      'title' => 'Members',
      'options' => ['limit' => 1],
    ];
    $result = civicrm_api3('Group', 'get', $params);
    if (!$result['count']) {
      $result = civicrm_api3('Group', 'create', $params);
    }
    $groupId = $result['id'];

    $languageGroups = [
      'da-language-members' => 'Danish language Members',
      'de-language-members' => 'German language Members',
      'en-language-members' => 'English language Members',
      'es-language-members' => 'Spanish language Members',
      'fr-language-members' => 'French language Members',
      'el-language-members' => 'Greek language Members',
      'nl-language-members' => 'Dutch language Members',
      'it-language-members' => 'Italian language Members',
      'pl-language-members' => 'Polish language Members',
      'pt-language-members' => 'Portuguese language Members',
      'ro-language-members' => 'Romanian language Members',
    ];
    foreach ($languageGroups as $name => $title) {
      civicrm_api3('Group', 'create', [
        'sequential' => 1,
        'name' => $name,
        'title' => $title,
      ]);
    }

    // todo custom fields for campaign
    // todo Join & Leave
    // todo anonymous contact
    // todo default language group (other)
    // todo prefix of language tag name


    Civi::settings()->set('opt_in', 1);
    Civi::settings()->set('group_id', $groupId);
    Civi::settings()->set('default_language', 'en_GB');
    Civi::settings()->set('country_lang_mapping', array(
      'DE' => 'de_DE',
      'DK' => 'da_DK',
      'EN' => 'en_GB',
      'ES' => 'es_ES',
      'FR' => 'fr_FR',
      'GR' => 'el_GR',
      'NL' => 'nl_NL',
      'IT' => 'it_IT',
      'PL' => 'pl_PL',
      'PT' => 'pt_PT',
    ));

  }
}
