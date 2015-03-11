<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\configure\CompileDefinition
 *
 * Compiles a complete job definition from a hierarchy of sources.
 * This hierarchy is defined as follows, which each level overriding the previous:
 * 1. Out-of-the-box DrupalCI defaults
 * 2. Local overrides defined in ~/.drupalci/config
 * 3. 'DCI_' namespaced environment variable overrides
 * 4. Test-specific overrides passed inside a DrupalCI test definition (e.g. .drupalci.yml)
 * 5. Custom overrides located inside a test definition defined via the $source variable when calling this function.
 */

namespace DrupalCI\Plugin\configure;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("compile_definition")
 */
class CompileDefinition extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run() {
    echo 'run configure compile_definition';
  }

  // TODO: Grab source code from DrupalCI/Console/Jobs/Job/Component/Configurator.php

}