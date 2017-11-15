<?php

require_once 'BaseTest.php';

/**
 * @group e2e
 */
class CRM_Commitcivi_ContactTest extends CRM_Commitcivi_BaseTest {

  public function testCreateAnonymous() {
    $event = new CRM_Commitcivi_Model_Event($this->anonymousOneOffEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $campaignId = $processor->campaign($event);
    $contactId = $processor->contact($event, $campaignId);
    $this->assertGreaterThan(0, $contactId);
  }

  public function testCreateContactWithOldStyle() {
    $event = new CRM_Commitcivi_Model_Event($this->oneOffStripeOldStyleEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $campaignId = $processor->campaign($event);
    $contactId = $processor->contact($event, $campaignId);
    $this->assertGreaterThan(0, $contactId);
  }

  public function testCreateNew() {
    $event = new CRM_Commitcivi_Model_Event($this->oneOffStripeOldStyleEvent());
    $event->contact->email = time() . '@speakcivi.com';
    $processor = new CRM_Commitcivi_EventProcessor();
    $campaignId = $processor->campaign($event);
    $contactId = $processor->contact($event, $campaignId);
    $this->assertGreaterThan(0, $contactId);
  }

  public function testUpdateExisting() {
    $event = new CRM_Commitcivi_Model_Event($this->oneOffStripeExistingContactEvent());
    $processor = new CRM_Commitcivi_EventProcessor();
    $campaignId = $processor->campaign($event);
    $contactId = $processor->contact($event, $campaignId);
    $params = [
      'sequential' => 1,
      'contact_id' => $contactId,
    ];
    $contact = civicrm_api3('Contact', 'get', $params);
    $address = civicrm_api3('Address', 'get', $params);
    $groups = civicrm_api3('GroupContact', 'get', $params);
    $tags = civicrm_api3('EntityTag', 'get', [
      'sequential' => 1,
      'entity_table' => "civicrm_contact",
      'entity_id' => $contactId,
    ]);
    $camp = new CRM_Commitcivi_Logic_Campaign();
    $locale = $camp->determineLanguage($event->actionName);
    $lang = substr($locale, 0, 2);
    $tagName = CRM_Commitcivi_Logic_Settings::languageTagNamePrefix() . $lang;
    $result = civicrm_api3('Tag', 'get', array(
      'sequential' => 1,
      'name' => $tagName,
    ));
    $tagId = $result['id'];

    $this->assertGreaterThan(0, $contactId);
    $this->assertEquals($locale, $contact['values'][0]['preferred_language'], 'has updated preferred language');
    $this->assertTrue($this->hasAddress($address, $event), 'has address');
    $this->assertTrue($this->hasGroup($groups, CRM_Commitcivi_Logic_Settings::groupId()), 'has Members group');
    $this->assertTrue($this->hasTag($tags, $tagId), 'has tag ' . $tagName);
  }

  protected function hasAddress($address, CRM_Commitcivi_Model_Event $event) {
    foreach ($address['values'] as $k => $adr) {
      if ($adr['postal_code'] == $event->contact->postalCode) {
        return TRUE;
      }
    }
    return FALSE;
  }

  protected function hasGroup($groups, $groupId) {
    foreach ($groups['values'] as $group) {
      if ($group['group_id'] == $groupId) {
        RETURN TRUE;
      }
    }
    RETURN FALSE;
  }

  protected function hasTag($tags, $tagId) {
    foreach ($tags['values'] as $tag) {
      if ($tag['tag_id'] == $tagId) {
        RETURN TRUE;
      }
    }
    RETURN FALSE;
  }
}
