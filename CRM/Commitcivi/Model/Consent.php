<?php

/**
 * Class CRM_Commitcivi_Model_Consent
 * @deprecated
 */
class CRM_Commitcivi_Model_Consent {

  const STATUS_NOTPROVIDED = 'not_provided';
  const STATUS_ACCEPTED = 'explicit_opt_in';
  const STATUS_REJECTED = 'none_given';

  public $publicId;
  public $version;
  public $language;
  public $date;
  public $createDate;
  public $level;
  public $method;
  public $methodOption;
  public $utmSource;
  public $utmMedium;
  public $utmCampaign;
  public $campaignId;

}
