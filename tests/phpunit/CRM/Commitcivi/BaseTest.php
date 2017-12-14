<?php

use \Civi\Test\EndToEndInterface;
use \Civi\Test\TransactionalInterface;


/**
 * Common methods for Commitcivi tests
 */
abstract class CRM_Commitcivi_BaseTest extends \PHPUnit_Framework_TestCase implements EndToEndInterface, TransactionalInterface {

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

  protected function singleStripeJson() {
    return <<<JSON
    {
      "action_type":"donate",
      "action_technical_type":"cc.wemove.eu:donate",
      "create_dt":"2017-10-25T12:34:56.531Z",
      "action_name":"campaign-PL",
      "external_id":50002,
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
        "type":"single",
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

  protected function singleStripeEvent() {
    return json_decode($this->singleStripeJson());
  }

  private function recurringStripeJson() {
    return <<<JSON
    {
      "action_type":"donate",
      "action_technical_type":"cc.wemove.eu:donate",
      "create_dt":"2017-12-13T11:47:56.531Z",
      "action_name":"campaign-PL",
      "external_id":50002,
      "contact":{
        "firstname":"Test2",
        "lastname":"Testowski2",
        "emails":[{"email":"test+t2@example.com"}],
        "addresses":[
          {
            "zip":"01-234",
            "country":"pl"
          }
        ]
      },
      "donation":{
        "amount":23,
        "amount_charged":0,
        "currency":"EUR",
        "card_type":"Visa",
        "payment_processor":"stripe",
        "type":"recurring",
        "recurring_id":"cc_1",
        "transaction_id":"ch_1NHwmdLnnERTfiJAMNHyFjAB",
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

  protected function recurringStripeEvent() {
    return json_decode($this->recurringStripeJson());
  }

  private function recurringStripeSecondJson() {
    return <<<JSON
    {
      "action_type":"donate",
      "action_technical_type":"cc.wemove.eu:donate",
      "create_dt":"2018-01-13T11:47:56.531Z",
      "action_name":"campaign-PL",
      "external_id":50002,
      "contact":{
        "firstname":"Test2",
        "lastname":"Testowski2",
        "emails":[{"email":"test+t2@example.com"}],
        "addresses":[
          {
            "zip":"01-234",
            "country":"pl"
          }
        ]
      },
      "donation":{
        "amount":23,
        "amount_charged":0,
        "currency":"EUR",
        "card_type":"Visa",
        "payment_processor":"stripe",
        "type":"recurring",
        "recurring_id":"cc_1",
        "transaction_id":"cc_2",
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

  protected function recurringStripeSecondEvent() {
    return json_decode($this->recurringStripeSecondJson());
  }

  private function singleStripeExistingContactJson() {
    return <<<JSON
    {
      "action_type":"donate",
      "action_technical_type":"cc.wemove.eu:donate",
      "create_dt":"2017-11-15T12:34:56.531Z",
      "action_name":"campaign-PL",
      "external_id":50001,
      "contact":{
        "firstname":"Test",
        "lastname":"Testowski",
        "emails":[{"email":"test+existing@example.com"}],
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
        "type":"single",
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

  protected function singleStripeExistingContactEvent() {
    return json_decode($this->singleStripeExistingContactJson());
  }

  private function singleStripeOldStyleJson() {
    return <<<JSON
    {
      "action_type":"donate",
      "action_technical_type":"cc.wemove.eu:donate",
      "create_dt":"2017-10-25T12:34:56.531Z",
      "action_name":"campaign-PL",
      "external_id":50001,
      "cons_hash":{
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
      "metadata":{
        "amount":15.67,
        "amount_charged":0.17,
        "currency":"EUR",
        "card_type":"Visa",
        "payment_processor":"stripe",
        "type":"single",
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

  protected function singleStripeOldStyleEvent() {
    return json_decode($this->singleStripeOldStyleJson());
  }

  private function singleSepaJson() {
    return <<<JSON
    {
      "action_type":"donate",
      "action_technical_type":"cc.wemove.eu:donate",
      "create_dt":"2017-12-13T14:16:56.531Z",
      "action_name":"campaign-PL",
      "external_id":50002,
      "contact":{
        "firstname":"Test3",
        "lastname":"Testowski3",
        "emails":[{"email":"test+t3@example.com"}],
        "addresses":[
          {
            "zip":"01-234",
            "country":"pl"
          }
        ]
      },
      "donation":{
        "amount":15,
        "currency":"eur",
        "type":"single",
        "payment_processor":"sepa",
        "amount_charged":0,
        "transaction_id":"cc_63",
        "iban":"PL83101010230000261395100000",
        "bic":"NOTPROVIDED",
        "account_holder":"test",
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

  protected function singleSepaEvent() {
    return json_decode($this->singleSepaJson());
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
        "type" => "recurring",
        "recurring_id" => "ccr_100001",
        "transaction_id" => "cc_100002",
        "iban" => "PL83101010230000261395100000",
        "bic" => "NBPLPLPWXXX",
        "account_holder" => "Holder name",
        "bank" => "Narodowy Bank Polski, O/Okr. w Warszawie",
        "status" => "success",
      ],
      'source' => (object) [
        "source" => "phpunit",
        "medium" => "phpstorm",
        "campaign" => "testing",
      ],
    ];
  }

  /**
   * JSON without email.
   *
   * @return string
   */
  protected function anonymousJson() {
    return <<<JSON
    {
      "action_type":"donate",
      "action_technical_type":"cc.wemove.eu:donate",
      "create_dt":"2017-10-31T12:34:56.531Z",
      "action_name":"campaign-PL",
      "external_id":50001,
      "contact":{
        "firstname":"Anonymous",
        "lastname":"Contact",
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
        "type":"single",
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

  /**
   * Event object without email.
   *
   * @return mixed
   */
  protected function anonymousEvent() {
    return json_decode($this->anonymousJson());
  }

  private function nullableContactJson() {
    return <<<JSON
    {
      "action_type":"donate",
      "action_technical_type":"cc.wemove.eu:donate",
      "create_dt":"2017-10-25T12:34:56.531Z",
      "action_name":"campaign-PL",
      "external_id":50002,
      "contact":{
        "firstname":null,
        "lastname":null,
        "emails":[{"email":"test+t4@example.com"}],
        "addresses":[
          {
            "zip":"01-234",
            "country":"pl"
          }
        ]
      },
      "donation":{
        "amount":25.67,
        "amount_charged":0.17,
        "currency":"EUR",
        "card_type":"Visa",
        "payment_processor":"stripe",
        "type":"single",
        "transaction_id":"ch_1NHwmdLnnERTfiJAMNHyFjVB",
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

  protected function nullableContactEvent() {
    return json_decode($this->nullableContactJson());
  }

}
