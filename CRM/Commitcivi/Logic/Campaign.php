<?php

class CRM_Commitcivi_Logic_Campaign {

  /**
   * Determine language (locale) based on campaign name which have to include country on the end, ex. *_EN.
   *
   * @param $campaignName
   *
   * @return string
   */
  public function determineLanguage($campaignName) {
    $re = "/(.*)[_\\- ]([a-zA-Z]{2})$/";
    if (preg_match($re, $campaignName, $matches)) {
      $country = strtoupper($matches[2]);
      $countryLangMapping = CRM_Commitcivi_Logic_Settings::countryLanguageMapping();
      if (array_key_exists($country, $countryLangMapping)) {
        return $countryLangMapping[$country];
      }
    }
    return CRM_Commitcivi_Logic_Settings::defaultLanguage();
  }

}
