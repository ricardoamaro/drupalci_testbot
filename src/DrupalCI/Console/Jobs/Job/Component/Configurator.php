<?php
/**
 * Created by PhpStorm.
 * User: Jeremy
 * Date: 2/26/15
 * Time: 10:22 PM
 */

namespace DrupalCI\Console\Jobs\Job\Component;

use DrupalCI\Console\Helpers\ConfigHelper;
use DrupalCI\Console\Jobs\Definition\JobDefinition;

class Configurator {

  public function configure($job, $source = NULL) {
    // Get and parse test definitions
    // DrupalCI jobs are controlled via a hierarchy of configuration settings, which define the behaviour of the platform while running DrupalCI jobs.  This hierarchy is defined as follows, which each level overriding the previous:
    // 1. Out-of-the-box DrupalCI defaults
    // 2. Local overrides defined in ~/.drupalci/config
    // 3. 'DCI_' namespaced environment variable overrides
    // 4. Test-specific overrides passed inside a DrupalCI test definition (e.g. .drupalci.yml)
    // 5. Custom overrides located inside a test definition defined via the $source variable when calling this function.

    $confighelper = new ConfigHelper();

    // Load job defaults
    $platform_args = $job->platform_defaults;
    $default_args = $job->default_arguments;
    if (!empty($default_args)) {
      $job->output->writeln("<comment>Loading build variables for this job type.</comment>");
    }

    // Load DrupalCI local config overrides
    $local_args = $confighelper->getCurrentConfigSetParsed();
    if (!empty($local_args)) {
      $job->output->writeln("<comment>Loading build variables from DrupalCI local config overrides.</comment>");
    }

    // Load "DCI_ namespaced" environment variable overrides
    $environment_args = $confighelper->getCurrentEnvVars();
    if (!empty($environment_args)) {
      $job->output->writeln("<comment>Loading build variables from namespaced environment variable overrides.</comment>");
    }

    // Load command line arguments
    // TODO: Routine for loading command line arguments.
    // TODO: How do we pull arguments off the drupalci command, when in a job class?
    // $cli_args = $somehelper->loadCLIargs();
    $cli_args = array();
    if (!empty($cli_args)) {
      $job->output->writeln("<comment>Loading test parameters from command line arguments.</comment>");
    }

    // Create temporary config array to use in determining the definition file source
    $config = $cli_args + $environment_args + $local_args + $default_args + $platform_args;

    // Load any build vars defined in the job definition file
    // Retrieve test definition file
    if (isset($source)) {
      $config['explicit_source'] = $source;
    }

    $definition_file = $this->getDefinitionFile($config);

    $definition_args = array();

    // Load test definition file
    if (!empty($definition_file)) {
      $job->output->writeln("<comment>Loading test parameters from build file: </comment><info>$definition_file</info>");
      $jobdef = new JobDefinition();
      $result = $jobdef->load($definition_file);
      if ($result == -1) {
        // Error loading definition file.
        $job->error_output("Failed", "Unable to parse build file.");
        // TODO: Robust error handling
        return;
      };
      $job_definition = $jobdef->getParameters();
      if (empty($job_definition)) {
        $job_definition = array();
        $definition_args = array();
      }
      else {
        $definition_args = !empty($job_definition['build_vars']) ? $job_definition['build_vars'] : array();
        $job->job_definition = $job_definition;
      }
    }

    $config = $cli_args + $definition_args + $environment_args + $local_args + $default_args + $platform_args;

    // Set initial build variables
    $buildvars = $job->get_buildvars();
    $job->set_buildvars($buildvars + $config);

    // Map relevant build variables into the job definition array
    $this->buildvarsToDefinition($job);



    return;
  }

  protected function getDefinitionFile($config) {
    $definition_file = "";

    // DrupalCI file-based test definition overrides can come from a number of sources:
    // 1. A file location explicitly passed into the config function
    if (!empty($config['explicit_source'])) {
      // TODO: Validate passed filename
      $definition_file = $config['explicit_source'];
    }
    // 2. A .drupalci.yml file located in a local codebase directory
    // TODO: file_exists throws warnings if passed a 'git' URL.
    elseif (file_exists($config['DCI_CodeBase'] . ".drupalci.yml")) {
      $definition_file = $config['DCI_CodeBase'] . ".drupalci.yml";
    }
    // 3. A file location stored in the 'DCI_BuildFile' environment variable
    elseif (!empty($config['DCI_BuildFile'])) {
      $definition_file = $config['DCI_BuildFile'];
    }
    return $definition_file;
  }




  protected function buildvarsToDefinition($job) {
    $buildvars = $job->get_buildvars();
    $job_definition = $job->job_definition;

    // Process dependencies
    if (!empty($buildvars['DCI_DEPENDENCIES'])) {
      // Format: module1,module2,module3
      $dependencies = explode(',', trim($buildvars['DCI_DEPENDENCIES'], '"'));
      foreach ($dependencies as $dependency) {
        // TODO: Remove the hardcoded git.drupal.org!!!
        // Perhaps we extend this with a DrupalConfigurator class?
        $directory = 'sites/all/modules';
        // TODO: We can't assume a branch here. Need to determine the Drupal version earlier!
        $job_definition['setup']['checkout'][] = array('protocol' => 'git', 'repo' => "git://git.drupal.org/project/$dependency.git", 'branch' => 'master', 'checkout_dir' => $directory, );
      }
    }

    // Process GIT dependencies
    if (!empty($buildvars['DCI_DEPENDENCIES_GIT'])) {
      // Format: gitrepo1,branch;gitrepo2,branch;
      $dependencies = explode(';', trim($buildvars['DCI_DEPENDENCIES_GIT'], '"'));
      foreach ($dependencies as $dependency) {
        if (!empty($dependency)) {
          list($repo, $branch) = explode(',', $dependency);
          // TODO: Remove this hardcoded drupalism!!!
          $directory = 'sites/all/modules/' . basename(parse_url($repo, PHP_URL_PATH), ".git");
          $job_definition['setup']['checkout'][] = array('protocol' => 'git', 'repo' => $repo, 'branch' => $branch, 'checkout_dir' => $directory);
        }
      }
    }

    $job->job_definition = $job_definition;
    /*
### ./run.sh Options
# Any valid Drupal branch or tag, like 8.0.x, 7.x or 7.30:
DCI_DrupalBRANCH="8.0.x"

# The identifier used by jenkins to name the Drupal docroot where all is stored:
DCI_IDENTIFIER="build_$(date +%Y_%m_%d_%H%M%S)" # Only [a-z0-9-_.] allowed

# The place where Drupal repos and DrupalDocRoot identifiers are kept:
DCI_REPODIR="$HOME/testbotdata"

# Request the runner to update the Drupal local repo before local cloning:
DCI_UPDATEREPO="false"  # true to force repos update

# By default we put the Drupal repo and docroots on the same place, but you can have BUILDSDIR elsewhere:
DCI_BUILDSDIR="$DCI_REPODIR"

# Same for the workspace:
DCI_WORKSPACE="$DCI_BUILDSDIR/$DCI_IDENTIFIER/"

# Install modules:
DCI_DEPENDENCIES=""     # module1,module2,module2...

# Git clone sandboxes:
DCI_DEPENDENCIES_GIT="" # gitrepo1,branch;gitrepo2,branch;...

# Download tgz modules:
DCI_DEPENDENCIES_TGZ="" # module1_url.tgz,module1_url.tgz,...

# Download and patch one or several patches:
DCI_PATCH=""            # patch_url,apply_dir;patch_url,apply_dir;...

*/
  }


} 