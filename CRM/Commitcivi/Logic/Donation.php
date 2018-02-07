<?php

class CRM_Commitcivi_Logic_Donation {

  protected $frequencyInterval = 1;

  protected $frequencyUnit = 'month';

  protected $financialTypeId = 1;

  protected $paymentInstrumentId = "Credit Card";

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
  protected function find($transactionId) {
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

}
