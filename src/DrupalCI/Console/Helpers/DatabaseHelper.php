<?php

/**
 * @file
 * DrupalCI Database helper class.
 */

namespace DrupalCI\Console\Helpers;

use DrupalCI\Console\Helpers\DrupalCIHelperBase;

class DatabaseHelper extends DrupalCIHelperBase {

  /**
   * {@inheritdoc}
   */
  public function getDatabaseTypes() {
    return array('mysql_5_5', 'mariadb_5_5', 'mariadb_10', 'postgres_8_3', 'postgres_9_1');
  }

}