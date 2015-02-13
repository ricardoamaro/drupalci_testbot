<?php
/**
 * @file
 * Base Job class for DrupalCI.
 */

namespace DrupalCI\Console\Jobs\Job;

use DrupalCI\Console\Helpers\ConfigHelper;
use Symfony\Component\Process\Process;
use DrupalCI\Console\Jobs\ContainerBase;
use DrupalCI\Console\Jobs\Definition\JobDefinition;
use DrupalCI\Console\Helpers\ContainerHelper;

class JobBase extends ContainerBase {

  // Defines the job type
  public $jobtype = 'base';

  // Defines argument variable names which are valid for this job type
  protected $available_arguments = array();

  // Defines platform defaults which apply for all jobs.  (Can still be overridden by per-job defaults)
  protected $platform_defaults = array(
    "DCI_CodeBase" => "./",
    // DCI_CheckoutDir defaults to a random directory in the system temp directory.
  );

  // Defines the default arguments which are valid for this job type
  protected $default_arguments = array();

  // Defines the required arguments which are necessary for this job type
  // Format:  array('ENV_VARIABLE_NAME' => 'CONFIG_FILE_LOCATION'), where
  // CONFIG_FILE_LOCATION is a colon-separated nested location for the
  // equivalent var in a job definition file.
  protected $required_arguments = array(
    // eg:   'DCI_DBTYPE' => 'environment:db'
  );

  // Holds the compiled argument list for this particular job
  public $arguments;

  // Placeholder which holds the parsed job definition file for this job
  public $job_definition = NULL;

  // Retrieve the argument list for this job
  public function get_arguments() {
    return $this->arguments;
  }

  // Set the argument list for this job
  public function set_arguments($arguments) {
    $this->arguments = $arguments;
  }

  // Merge the passed arguments into the existing argument list
  public function merge_arguments($arguments) {
    $existing = $this->arguments;
    $this->arguments = $arguments + $existing;
  }

  // Default working directory
  public $working_dir = "./";

  // Holds build variables which need to be persisted between build steps
  protected $build_vars = array();

  // Retrieves the build variables for this job
  public function get_buildvars() {
    return $this->build_vars;
  }

  // Sets the build variables for this job
  public function set_buildvars($build_vars) {
    $this->build_vars = $build_vars;
  }

  // Retrieves a single build variable for this job
  public function get_buildvar($build_var) {
    return $this->build_vars[$build_var];
  }

  // Sets a single build variable for this job
  public function set_buildvar($build_var, $value) {
    $this->build_vars[$build_var] = $value;
  }

  // Stores the calling command's output buffer
  protected $output;

  // Sets the output buffer
  public function setOutput($output) {
    $this->output = $output;
  }

  // Individual functions for each build step
  public function configure($source = NULL) {
    // Get and parse test definitions
    // DrupalCI jobs are controlled via a hierarchy of configuration settings, which define the behaviour of the platform while running DrupalCI jobs.  This hierarchy is defined as follows, which each level overriding the previous:
    // 1. Out-of-the-box DrupalCI defaults
    // 2. Local overrides defined in ~/.drupalci/config
    // 3. 'DCI_' namespaced environment variable overrides
    // 4. Test-specific overrides passed inside a DrupalCI test definition (e.g. .drupalci.yml)
    // 5. Custom overrides located inside a test definition defined via the $source variable when calling this function.

    $confighelper = new ConfigHelper();

    // Load job defaults
    $platform_args = $this->platform_defaults;
    $default_args = $this->default_arguments;
    if (!empty($default_args)) {
      $this->output->writeln("<comment>Loading build variables for this job type.</comment>");
    }

    // Load DrupalCI local config overrides
    $local_args = $confighelper->getCurrentConfigSetParsed();
    if (!empty($local_args)) {
      $this->output->writeln("<comment>Loading build variables from DrupalCI local config overrides.</comment>");
    }

    // Load "DCI_ namespaced" environment variable overrides
    $environment_args = $confighelper->getCurrentEnvVars();
    if (!empty($environment_args)) {
      $this->output->writeln("<comment>Loading build variables from namespaced environment variable overrides.</comment>");
    }

    // Load command line arguments
    // TODO: Routine for loading command line arguments.
    // TODO: How do we pull arguments off the drupalci command, when in a job class?
    // $cli_args = $somehelper->loadCLIargs();
    $cli_args = array();
    if (!empty($cli_args)) {
      $this->output->writeln("<comment>Loading test parameters from command line arguments.</comment>");
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
      $this->output->writeln("<comment>Loading test parameters from build file: </comment><info>$definition_file</info>");
      $job = new JobDefinition();
      $result = $job->load($definition_file);
      if ($result == -1) {
        // Error loading definition file.
        $this->output->writeln("<error>FAILED:</error> <info>Unable to parse build file.</info>");
        // TODO: Robust error handling
        return -1;
      };
      $job_definition = $job->getParameters();
      if (empty($job_definition)) {
        $job_definition = array();
        $definition_args = array();
      }
      else {
        $definition_args = !empty($job_definition['build_vars']) ? $job_definition['build_vars'] : array();
        $this->job_definition = $job_definition;
      }
    }

    $config = $cli_args + $definition_args + $environment_args + $local_args + $default_args + $platform_args;

    // Set initial build variables
    $buildvars = $this->get_buildvars();
    $this->set_buildvars($buildvars + $config);

    // TODO: Remove the 'arguments' parameter.
    $this->set_arguments($config);

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


  // Defines the default build_steps for this job type
  public function build_steps() {
    return array(
      'validate',
      'checkout',
      //'environment',
      //'setup',
      //'install',
      //'validate_install',
      //'execute',
      //'complete',
      //'success',
      //'failure'
    );
  }

  public function validate() {
    $this->output->write("<comment>Validating test parameters ... </comment>");
    // TODO: Ensure that all 'required' arguments are defined
    $definition = $this->job_definition;
    $failflag = FALSE;
    foreach ($this->required_arguments as $env_var => $yaml_loc) {
      if (!empty($this->build_vars[$env_var])) {
        continue;
      }
      else {
        // Look for the appropriate array structure in the job definition file
        // eg: environment:db
        $keys = explode(":", $yaml_loc);
        $eval = $definition;
        foreach ($keys as $key) {
          if (!empty($eval[$key])) {
            $eval=$eval[$key];
          }
          else {
            // Missing a required key in the array key chain
            $failflag = TRUE;
            break;
          }
        }
        if (!$failflag) {
          continue;
        }
      }

      // If processing gets to here, we're missing a required variable
      $this->output->writeln("<error>FAILED</error>");
      $this->output->writeln("<info>Required test parameter <options=bold>'$env_var'</options=bold> not found in environment variables, and <options=bold>'$yaml_loc'</options=bold> not found in job definition file.</info>");
      // TODO: Graceful handling of failed exit states
      return -1;
    }
    // TODO: Strip out arguments which are not defined in the 'Available' arguments array
    $this->output->writeln("<info>PASSED</info>");
    return;
  }

  public function checkout() {
    $arguments = $this->get_buildvars();

    // Check if the source codebase directory has been specified
    if (empty($arguments['DCI_CodeBase'])) {
      // If no explicit codebase provided, assume we are using the code in the local directory.
      $arguments['DCI_CodeBase'] = "./";
      $this->set_buildvars($arguments);
    }
    // Check if the target working directory has been specified.
    if (empty($arguments['DCI_CheckoutDir'])) {
      // If no explicit working directory provided, we generate one in the system temporary directory.
      $tmpdir = $this->create_tempdir(sys_get_temp_dir() . '/drupalci/', $this->jobtype . "-");
      if (!$tmpdir) {
        // Error creating checkout directory
        $this->output->writeln("<error>ERROR</error>");
        $this->output->writeln("<comment>Failure encountered while attempting to create a local checkout directory");
        return -1;
      }
      $this->output->writeln("<comment>Checkout directory created at <info>$tmpdir</info></comment>");
      $arguments['DCI_CheckoutDir'] = $tmpdir;
      $this->set_buildvars($arguments);
    }
    elseif ($arguments['DCI_CheckoutDir'] != $arguments['DCI_CodeBase']) {
      // We ensure the checkout directory is within the system temporary directory, to ensure
      // that we don't provide access to the entire file system.

      // Create checkout directory
      $result = $this->create_local_checkout_dir();
      // Pass through any errors encountered while creating the directory
      if ($result == -1) {
        return -1;
      }
    }

    // Update the checkout directory in the class object
    $this->working_dir = $arguments['DCI_CheckoutDir'];

    // Refresh our arguments list (may have changed in create_local_checkout_dir())
    $arguments = $this->get_buildvars();

    // Determine if local or remote codebase
    $parsed_url = parse_url($arguments['DCI_CodeBase']);
    if (empty($parsed_url['scheme'])) {
      // Local directory
      $dirname = $arguments['DCI_CodeBase'];
      $this->output->writeln("<comment>Using local source codebase directory: <info>$dirname</info></comment>");
      // Check if a checkout is necessary
      // See if working directory is provided, and differs from the CodeBase directory.
      if ($arguments['DCI_CheckoutDir'] == $arguments['DCI_CodeBase']) {
        // No checkout required.
        $this->output->writeln("<info>Using original code base for the working directory.</info>");
        return;
      }
      $checkoutdir = $arguments['DCI_CheckoutDir'];
      // Create the local checkout directory and copy the code.
      $this->output->writeln("<comment>Using local checkout directory: <info>$checkoutdir</info></comment>");
      return $this->checkout_local_to_working();
    }
    elseif (in_array($parsed_url['scheme'], array('http', 'https', 'git')) && substr($parsed_url['path'], -4) == ".git") {
      // Remote codebase
      $repository = $arguments['DCI_CodeBase'];
      $this->output->writeln("<comment>Using remote source repository: <info>$repository</info></comment>");
      $checkoutdir = $arguments['DCI_CheckoutDir'];
      $this->output->writeln("<comment>Using local checkout directory: <info>$checkoutdir</info></comment>");
      return $this->checkout_git_to_working();
    }
    else {
      // Unsupported format in DCI_CodeBase
      // TODO: Add support for zipped files referenced with an http:// url
      $this->output->writeln("<error>ERROR</error>");
      $this->output->writeln("<info>Unable to determine source codebase directory or URL.</info>");
      return -1;
    }
  }

  protected function create_tempdir($dir=NULL,$prefix=NULL) {
    $template = "{$prefix}XXXXXX";
    if (($dir) && (is_dir($dir))) { $tmpdir = "--tmpdir=$dir"; }
    else { $tmpdir = '--tmpdir=' . sys_get_temp_dir(); }
    return shell_exec("mktemp -d $tmpdir $template");
  }

  protected function create_local_checkout_dir() {
    $arguments = $this->get_buildvars();
    $directory = $arguments['DCI_CheckoutDir'];
    $tempdir = sys_get_temp_dir();

    // Prefix the system temp dir on the DCI_CheckoutDir variable if needed
    if (strpos($directory, $tempdir) !== 0) {
      // If not, prefix the system temp directory on the variable.
      if ($directory[0] != "/") {
        $directory = "/" . $directory;
      }
      $arguments['DCI_CheckoutDir'] = $tempdir . $directory;
      $this->set_buildvars($arguments);
    }

    // Check if the DCI_CheckoutDir exists within the /tmp directory, or create it if not
    $path = realpath($arguments['DCI_CheckoutDir']);
    if ($path !== FALSE) {
      // Directory exists.  Check that we're still in /tmp
      if (!$this->validate_checkout_dir()) {
        // Something bad happened.  Attempt to transverse out of the /tmp dir, perhaps?
        $this->output->writeln("<error>Error</error>");
        $this->output->writeln("<info>Detected an invalid local checkout directory.  DCI_CheckoutDir must reside somewhere within the system temporary file directory.</info>");
        return -1;
      }
      else {
        // Directory is within the system temp dir.
        $this->output->writeln("<comment>Found existing local checkout directory <info>$path</info></comment>");
        return;
      }
    }
    elseif ($path === FALSE) {
      // Directory doesn't exist, so create it.
      $directory = $arguments['DCI_CheckoutDir'];
      mkdir($directory, 0777, true);
      $this->output->writeln("<comment>Checkout Directory created at <info>$directory</info>");
      // Ensure we are under the system temp dir
      if (!$this->validate_checkout_dir()) {
        // Something bad happened.  Attempt to transverse out of the /tmp dir, perhaps?
        $this->output->writeln("<error>Error</error>");
        $this->output->writeln("<info>DCI_CheckoutDir must reside somewhere within the system temporary file directory.</info>");
        $this->output->writeln("<info>You may wish to manually remove the directory created above.");
        return -1;
      }
    }
  }

  protected function validate_checkout_dir() {
    $arguments = $this->get_buildvars();
    $path = realpath($arguments['DCI_CheckoutDir']);
    $tmpdir = sys_get_temp_dir();
    if (strpos($path, $tmpdir) === 0) {
      return TRUE;
    }
    return FALSE;
  }

  protected function checkout_local_to_working() {
    // Load arguments
    $arguments = $this->get_buildvars();
    $srcdir = $arguments['DCI_CodeBase'];
    $targetdir = $arguments['DCI_CheckoutDir'];
    // TODO: Prompt for confirmation.
    // TODO: Should we restrict the Working/Checkout variables to local use only?
    // TODO: Additional validation

    $this->output->write("<comment>Copying files to the local checkout directory ... </comment>");
    $result = exec("cp -r $srcdir $targetdir");
    if (is_null($result)) {
      $this->output->writeln("<error>FAILED</error>");
      $this->output->writeln("<comment>Error encountered while attempting to copy code to the local checkout directory.");
      return -1;
    }
    $this->output->writeln("<comment>DONE</comment>");
  }

  protected function checkout_git_to_working() {
    // Load arguments
    $arguments = $this->get_buildvars();

    // See if a specific branch has been supplied.  If not, default to 'master'.
    if (empty($arguments['DCI_GitBranch'])) {
      $arguments['DCI_GitBranch'] = "master";
      $this->set_buildvars($arguments);
    }

    $repodir = $arguments['DCI_CodeBase'];
    $targetdir = $arguments['DCI_CheckoutDir'];
    $branch = $arguments['DCI_GitBranch'];

    // TODO: Sanitize the directory and branch parameters to prevent people from adding to the clone command.

    $cmd = "git clone -b $branch $repodir $targetdir";
    
    $this->output->writeln("<comment>Cloning repository from <info>$repodir</info> ... </comment>");
    exec($cmd, $cmdout, $result);

    if ($result !== 0) {
      $this->output->writeln("<error>FAILED</error>");
      $this->output->writeln("<comment>Error encountered while attempting to clone remote repository.</comment>");
      $this->output->writeln("<comment>Return code: <info>$result</info></comment>");
      return -1;
    }
    $this->output->writeln("<comment>Checkout directory populated.</comment>");
  }

  public function environment() {
    $this->build_container_names();
    if (!($this->validate_container_names())) {
      return -1;
    }
  }

  protected function build_container_names() {
    // Determine whether to use environment variables or definition file to determine what containers are needed
    if (empty($this->job_definition['environment'])) {
      $containers = $this->env_containers_from_env();
    }
    else {
      $containers = $this->env_containers_from_file();
    }
    if (!empty($containers)) {
      $this->build_vars['DCI_Container_Images'] = $containers;
    }
  }

  protected function env_containers_from_env() {
    $containers = array();
    $this->output->writeln("<comment>Parsing environment variables to determine required containers.</comment>");
    // Retrieve environment-related variables from the job arguments
    $dbtype = $this->build_vars['DCI_DBTYPE'];
    $dbver = $this->build_vars['DCI_DBVER'];
    $phpversion = $this->build_vars['DCI_PHPVERSION'];
    $containers['php'][$phpversion] = "drupalci/php-$phpversion";
    $this->output->writeln("<info>Adding container: <options=bold>drupalci/php-$phpversion</options=bold></info>");
    $containers['db'][$dbtype . "-" . $dbver] = "drupalci/$dbtype-$dbver";
    $this->output->writeln("<info>Adding container: <options=bold>drupalci/$dbtype-$dbver</options=bold></info>");
    return $containers;
  }

  protected function env_containers_from_file() {
    $config = $this->job_definition['environment'];
    $this->output->writeln("<comment>Evaluating container requirements as defined in job definition file ...</comment>");
    $containers = array();

    // Determine required php containers
    if (!empty($config['php'])) {
      // May be a string if one version required, or array if multiple
      if (is_array($config['php'])) {
        foreach ($config['php'] as $phpversion) {
          // TODO: Make the drupalci prefix a variable (overrideable to use custom containers)
          $containers['php'][$phpversion] = "drupalci/php-$phpversion";
          $this->output->writeln("<info>Adding container: <options=bold>drupalci/php-$phpversion</options=bold></info>");
        }
      }
      else {
        $phpversion = $config['php'];
        $containers['php'][$phpversion] = "drupalci/php-$phpversion";
        $this->output->writeln("<info>Adding container: <options=bold>drupalci/php-$phpversion</options=bold></info>");
      }
    }
    else {
      // We assume will always need at least one default PHP container
      $containers['php']['5.5'] = "drupalci/php-5.5";
    }

    // Determine required database containers
    if (!empty($config['db'])) {
      // May be a string if one version required, or array if multiple
      if (is_array($config['db'])) {
        foreach ($config['db'] as $dbversion) {
          $containers['db'][$dbversion] = "drupalci/$dbversion";
          $this->output->writeln("<info>Adding container: <options=bold>drupalci/$dbversion</options=bold></info>");
        }
      }
      else {
        $dbversion = $config['db'];
        $containers['db'][$dbversion] = "drupalci/$dbversion";
        $this->output->writeln("<info>Adding container: <options=bold>drupalci/$dbversion</options=bold></info>");
      }
    }
    return $containers;
  }

  protected function validate_container_names() {
    // Verify that the appropriate container images exist
    $this->output->writeln("<comment>Ensuring appropriate container images exist.</comment>");
    $helper = new ContainerHelper();
    foreach ($this->build_vars['DCI_Container_Images'] as $type) {
      foreach ($type as $key => $image) {
        if (!$helper->containerExists($image)) {
          // Error: No such container image
          $this->output->writeln("<error>FAIL:</error> <comment>Required container image <options=bold>'$image'</options=bold> does not exist.</comment>");
          // TODO: Robust error handling.
          return -1;
        }
      }
    }
    return TRUE;
  }

  public function setup() {
    return;
  }

  public function install() {
    return;
  }

  public function validate_install() {
    return;
  }

  public function execute() {
    return;
  }

  public function complete() {
    return;
  }

  public function success() {
    return;
  }

  public function failure() {
    return;
  }

  public function shell_command($cmd) {
    $process = new Process($cmd);
    $process->setTimeout(3600*6);
    $process->setIdleTimeout(3600);
    $process->run(function ($type, $buffer) {
        $this->output->writeln($buffer);
    });
   }

}