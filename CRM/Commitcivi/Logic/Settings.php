<?php

class CRM_Commitcivi_Logic_Settings {

  /**
   * Get id of Members group.
   *
   * @return mixed
   */
  public static function groupId() {
    return Civi::settings()->get('group_id');
  }

}
