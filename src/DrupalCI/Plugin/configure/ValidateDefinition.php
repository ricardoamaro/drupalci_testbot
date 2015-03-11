<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\configure\ValidateDefinition
 *
 * Validates a compiled job definition against the job type definition:
 * 1. Verifies all 'mandatory' parameters present
 * 2. (TODO) Strips out any parameters not specified in the 'allowed' list for that job type
 */

namespace DrupalCI\Plugin\configure;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("validate_definition")
 */
class ValidateDefinition extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run configure validate_definition';
  }

  // TODO: Grab source code from DrupalCI/Console/Jobs/Job/Component/ParameterValidator.php

}