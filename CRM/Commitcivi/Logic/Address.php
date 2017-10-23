<?php

class CRM_Commitcivi_Logic_Address {

  const API_ADDRESS_GET = 'api.Address.get';
  const API_ADDRESS_CREATE = 'api.Address.create';

  /**
   * Preparing params for creating/update a address.
   *
   * @param $contact
   * @param $existingContact
   * @param $params
   *
   * @return mixed
   */
  public function prepareParamsAddress($contact, $existingContact, $params) {
    if ($existingContact[self::API_ADDRESS_GET]['count'] == 1) {
      // if we have a one address, we update it by new values (?)
      if (($existingContact[self::API_ADDRESS_GET]['values'][0]['postal_code'] != $params['postal_code']) ||
        ($existingContact[self::API_ADDRESS_GET]['values'][0]['country_id'] != $params['country_id'])
      ) {
        $contact[self::API_ADDRESS_CREATE]['id'] = $existingContact[self::API_ADDRESS_GET]['id'];
        $contact[self::API_ADDRESS_CREATE]['postal_code'] = $params['postal_code'];
        $contact[self::API_ADDRESS_CREATE]['country_id'] = $params['country_id'];
      }
    }
    elseif ($existingContact[self::API_ADDRESS_GET]['count'] > 1) {
      // from speakout we have only (postal_code) or (postal_code and country)
      foreach ($existingContact[self::API_ADDRESS_GET]['values'] as $k => $v) {
        $adr = $this->getAddressValues($v);
        if (
          array_key_exists('country_id', $adr) && $params['country_id'] == $adr['country_id'] &&
          array_key_exists('postal_code', $adr) && $params['postal_code'] == $adr['postal_code']
        ) {
          // return without any modification, needed address already exists
          return $contact;
        }
      }
      $postal = FALSE;
      foreach ($existingContact[self::API_ADDRESS_GET]['values'] as $k => $v) {
        $adr = $this->getAddressValues($v);
        if (
          !array_key_exists('country_id', $adr) &&
          array_key_exists('postal_code', $adr) && $params['postal_code'] == $adr['postal_code']
        ) {
          $contact[self::API_ADDRESS_CREATE]['id'] = $v['id'];
          $contact[self::API_ADDRESS_CREATE]['country'] = $params['country'];
          $postal = TRUE;
          break;
        }
      }
      if (!$postal) {
        foreach ($existingContact[self::API_ADDRESS_GET]['values'] as $k => $v) {
          $adr = $this->getAddressValues($v);
          if (
            array_key_exists('country_id', $adr) && $params['country_id'] == $adr['country_id'] &&
            !array_key_exists('postal_code', $adr)
          ) {
            $contact[self::API_ADDRESS_CREATE]['id'] = $v['id'];
            $contact[self::API_ADDRESS_CREATE]['postal_code'] = $params['postal_code'];
            break;
          }
        }
      }
      if (!array_key_exists(self::API_ADDRESS_CREATE, $contact) || !array_key_exists('id', $contact[self::API_ADDRESS_CREATE])) {
        unset($contact[self::API_ADDRESS_CREATE]);
        $contact = $this->prepareParamsAddressDefault($contact, $params);
      }
    }
    else {
      // we have no address, creating new one
      $contact = $this->prepareParamsAddressDefault($contact, $params);
    }
    return $contact;
  }

  /**
   * Prepare default address
   *
   * @param $contact
   * @param $params
   *
   * @return mixed
   */
  public function prepareParamsAddressDefault($contact, $params) {
    $contact[self::API_ADDRESS_CREATE]['location_type_id'] = 1;
    $contact[self::API_ADDRESS_CREATE]['postal_code'] = $params['postal_code'];
    $contact[self::API_ADDRESS_CREATE]['country'] = $params['country'];
    return $contact;
  }

  /**
   * Remove null params from address
   *
   * @param $contact
   *
   * @return array
   */
  public function removeNullAddress($contact) {
    if (array_key_exists(self::API_ADDRESS_CREATE, $contact)) {
      if (array_key_exists('postal_code', $contact[self::API_ADDRESS_CREATE]) && $contact[self::API_ADDRESS_CREATE]['postal_code'] == '') {
        unset($contact[self::API_ADDRESS_CREATE]['postal_code']);
      }
      if (array_key_exists('country', $contact[self::API_ADDRESS_CREATE]) && $contact[self::API_ADDRESS_CREATE]['country'] == '') {
        unset($contact[self::API_ADDRESS_CREATE]['country']);
      }
      if (array_key_exists('country_id', $contact[self::API_ADDRESS_CREATE]) && $contact[self::API_ADDRESS_CREATE]['country_id'] == 0) {
        unset($contact[self::API_ADDRESS_CREATE]['country_id']);
      }
      if (array_key_exists('id', $contact[self::API_ADDRESS_CREATE]) && count($contact[self::API_ADDRESS_CREATE]) == 1) {
        unset($contact[self::API_ADDRESS_CREATE]['id']);
      }
      if (count($contact[self::API_ADDRESS_CREATE]) == 0) {
        unset($contact[self::API_ADDRESS_CREATE]);
      }
    }
    return $contact;
  }

  /**
   * Return relevant keys from address
   *
   * @param $address
   *
   * @return array
   */
  private function getAddressValues($address) {
    $expectedKeys = array(
      'city' => '',
      'street_address' => '',
      'postal_code' => '',
      'country_id' => '',
    );
    return array_intersect_key($address, $expectedKeys);
  }

}
