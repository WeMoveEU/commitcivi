<?php

class CRM_Commitcivi_Logic_Donation {

  protected $frequencyInterval = 1;

  protected $frequencyUnit = 'month';

  protected $financialTypeId = 1;

  protected $paymentInstrumentId = 2;// stripe "Credit Card";

  protected $mapRecurringStatus = [
    'success' => 5, // in progress
    'destroy' => 3, // cancelled
  ];

  /**
   * Check if donation is recurring.
   *
   * @param string $donationType
   *
   * @return bool
   */
  protected function isRecurring($donationType) {
    return $donationType == CRM_Commitcivi_Model_Donation::TYPE_RECURRING;
  }

  /**
   * Find contribution by unique transaction id.
   *
   * @param string $transactionId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function find($transactionId) {
    $params = [
      'sequential' => 1,
      'trxn_id' => $transactionId,
    ];
    $result = civicrm_api3('Contribution', 'get', $params);
    if ($result['count'] == 1) {
      return $result;
    }
    return [];
  }

  /**
   * Find recurring contribution by unique transaction id.
   *
   * @param string $recurringId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  protected function findRecurring($recurringId) {
    $params = [
      'sequential' => 1,
      'trxn_id' => $recurringId,
    ];
    $result = civicrm_api3('ContributionRecur', 'get', $params);
    if ($result['count'] == 1) {
      return $result;
    }
    return [];
  }

  /**
   * Add UTM field values from event to $params as custom contribution fields
   *
   * @param array $params
   * @param \CRM_Commitcivi_Model_Utm $utm
   *
   * @return mixed
   */
  protected function setSourceFields($params, CRM_Commitcivi_Model_Utm $utm) {
    $mapping = [
      'Source' => 'field_contribution_source',
      'Medium' => 'field_contribution_medium',
      'Campaign' => 'field_contribution_campaign',
      'Content' => 'field_contribution_content',
    ];
    foreach ($mapping as $field => $setting) {
      if ($utm->$field) {
        $params[Civi::settings()->get($setting)] = $utm->$field;
      }
    }
    return $params;
  }

  /**
   * Add UTM field values from event to $params as custom recurring contribution fields
   *
   * @param $params
   * @param \CRM_Commitcivi_Model_Utm $utm
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  protected function setRecurSourceFields($params, CRM_Commitcivi_Model_Utm $utm) {
    if ($utm->Source) {
      $params[CRM_Contributm_Model_UtmRecur::utmSource()] = $utm->Source;
    }
    if ($utm->Medium) {
      $params[CRM_Contributm_Model_UtmRecur::utmMedium()] = $utm->Medium;
    }
    if ($utm->Campaign) {
      $params[CRM_Contributm_Model_UtmRecur::utmCampaign()] = $utm->Campaign;
    }
    if ($utm->Content) {
      $params[CRM_Contributm_Model_UtmRecur::utmContent()] = $utm->Content;
    }

    return $params;
  }

  /**
   * Set UTM fields for contribution
   * @param \CRM_Commitcivi_Model_Event $event
   * @param $contributionId
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function setUtms(CRM_Commitcivi_Model_Event $event, $contributionId) {
    $params = [
      'sequential' => 1,
      'id' => $contributionId,
    ];
    $params = $this->setSourceFields($params, $event->utm);
    civicrm_api3('Contribution', 'create', $params);
  }

  /**
   * Set UTM fields for recurring contribution
   * @param \CRM_Commitcivi_Model_Event $event
   * @param $recurId
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function setRecurUtms(CRM_Commitcivi_Model_Event $event, $recurId) {
    $params = [
      'sequential' => 1,
      'id' => $recurId,
    ];
    $params = $this->setRecurSourceFields($params, $event->utm);
    civicrm_api3('ContributionRecur', 'create', $params);
  }

}
