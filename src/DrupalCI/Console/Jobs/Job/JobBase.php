<?php
/**
 * @file
 * Base Job class for DrupalCI.
 */

namespace DrupalCI\Console\Jobs\Job;


use DrupalCI\Console\Jobs\Job\Component\Configurator;
use DrupalCI\Console\Jobs\Job\Component\ParameterValidator;
use Symfony\Component\Process\Process;
use DrupalCI\Console\Jobs\ContainerBase;
use DrupalCI\Console\Helpers\ContainerHelper;

class JobBase extends ContainerBase {

  // Defines the job type
  public $jobtype = 'base';

  // Defines argument variable names which are valid for this job type
  public $available_arguments = array();

  // Defines platform defaults which apply for all jobs.  (Can still be overridden by per-job defaults)
  public $platform_defaults = array(
    "DCI_CodeBase" => "./",
    // DCI_CheckoutDir defaults to a random directory in the system temp directory.
  );

  // Defines the default arguments which are valid for this job type
  public $default_arguments = array();

  // Defines the required arguments which are necessary for this job type
  // Format:  array('ENV_VARIABLE_NAME' => 'CONFIG_FILE_LOCATION'), where
  // CONFIG_FILE_LOCATION is a colon-separated nested location for the
  // equivalent var in a job definition file.
  public $required_arguments = array(
    // eg:   'DCI_DBTYPE' => 'environment:db'
  );

  // Placeholder which holds the parsed job definition file for this job
  public $job_definition = NULL;

  // Error status
  public $error_status = 0;

  // Default working directory
  public $working_dir = "./";

  // Holds build variables which need to be persisted between build steps
  public $build_vars = array();

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
  public $output;

  // Sets the output buffer
  public function setOutput($output) {
    $this->output = $output;
  }

  // Compile the complete job definition
  // DrupalCI jobs are controlled via a hierarchy of configuration settings, which define the behaviour of the platform while running DrupalCI jobs.  This hierarchy is defined as follows, which each level overriding the previous:
  // 1. Out-of-the-box DrupalCI defaults
  // 2. Local overrides defined in ~/.drupalci/config
  // 3. 'DCI_' namespaced environment variable overrides
  // 4. Test-specific overrides passed inside a DrupalCI test definition (e.g. .drupalci.yml)
  // 5. Custom overrides located inside a test definition defined via the $source variable when calling this function.
  public function configure($source = NULL) {
    $configurator = new Configurator();
    $configurator->configure($this, $source);
  }

  // Defines the default build_steps for this job type
  public function build_steps() {
    return array(
      'validate',
      'checkout',
      'environment',
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
    $validator = new ParameterValidator();
    $validator->load_values($this);
    $result = $validator->validate();
    if (!$result) {
      $this->error_output("Failed", "Required test parameter <options=bold>'$env_var'</options=bold> not found in environment variables, and <options=bold>'$yaml_loc'</options=bold> not found in job definition file.");
      // TODO: Graceful handling of failed exit states
      return;
    }
    else {
      $this->output->writeln("<info>PASSED</info>");
      return;
    }
  }




  protected function create_tempdir($dir=NULL,$prefix=NULL) {
    // PHP seems to have trouble creating temporary unique directories with the appropriate permissions,
    // So we create a temp file to get the unique filename, then mkdir a directory in it's place.
    $prefix = empty($prefix) ? "drupalci-" : $prefix;
    $tmpdir = ($dir && is_dir($dir)) ? $dir : sys_get_temp_dir();
    $tempname = tempnam($tmpdir, $prefix);
    if (empty($tempname)) {
      // Unable to create temp filename
      $this->error_output("Error", "Unable to create temporary directory inside of $tmpdir.");
      return;
    }
    $tempdir = $tempname;
    unlink($tempname);
    if (mkdir($tempdir)) {
      return $tempdir;
    }
    else {
      // Unable to create temp directory
      $this->error_output("Error", "Error encountered while attempting to create temporary directory $tempdir.");
      return;
    }
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
        $this->error_output("Error", "Detected an invalid local checkout directory.  The checkout directory must reside somewhere within the system temporary file directory.");
        return;
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
        $this->error_output("Error", "DCI_CheckoutDir must reside somewhere within the system temporary file directory. You may wish to manually remove the directory created above.");
        return;
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
      $this->error_output("Failed", "Error encountered while attempting to copy code to the local checkout directory.");
      return;
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
      $this->error_output("Failed", "Error encountered while attempting to clone remote repository.  Git return code: <info>$result</info>");
      return;
    }
    $this->output->writeln("<comment>Checkout directory populated.</comment>");
  }

  public function environment() {
    // The 'environment' step determines which containers are going to be
    // required, validates that the appropriate container images exist, and
    // starts any required service containers.
    $this->build_container_names();
    $this->validate_container_names();
    $this->start_service_containers();
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
          $containers['php']["$phpversion"] = "drupalci/php-$phpversion";
          $this->output->writeln("<info>Adding container: <options=bold>drupalci/php-$phpversion</options=bold></info>");
        }
      }
      else {
        $phpversion = $config['php'];
        $containers['php']["$phpversion"] = "drupalci/php-$phpversion";
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
          $containers['db']["$dbversion"] = "drupalci/$dbversion";
          $this->output->writeln("<info>Adding container: <options=bold>drupalci/$dbversion</options=bold></info>");
        }
      }
      else {
        $dbversion = $config['db'];
        $containers['db']["$dbversion"] = "drupalci/$dbversion";
        $this->output->writeln("<info>Adding container: <options=bold>drupalci/$dbversion</options=bold></info>");
      }
    }
    return $containers;
  }

  protected function validate_container_names() {
    // Verify that the appropriate container images exist
    $this->output->writeln("<comment>Ensuring appropriate container images exist.</comment>");
    $helper = new ContainerHelper();
    foreach ($this->build_vars['DCI_Container_Images'] as $type => $containers) {
      foreach ($containers as $key => $image) {
        if (!$helper->containerExists($image)) {
          // Error: No such container image
          $this->error_output("Failed", "Required container image <options=bold>'$image'</options=bold> does not exist.");
          // TODO: Robust error handling.
          return;
        }
      }
    }
    return TRUE;
  }

  protected function start_service_containers() {
    // We need to ensure that any service containers are started.
    $helper = new ContainerHelper();
    if (empty($this->build_vars['DCI_Container_Images']['db'])) {
      // No service containers required.
      return;
    }
    foreach ($this->build_vars['DCI_Container_Images']['db'] as $image) {
      // Start an instance of $image.
      // TODO: Ensure container is not already running!
      $helper->startContainer($image);
      $need_sleep = TRUE;
    }
    // Pause to allow any container services (e.g. mysql) to start up.
    // TODO: This currently pauses even if the container was already found.  Do we need the
    // start_container.sh script to throw an error return code?
    if (!empty($need_sleep)) {
      echo "Sleeping 10 seconds to allow container services to start.\n";
      sleep(10);
    }
  }

  /*
   *  The setup stage will be responsible for:
   * - Perform checkouts (setup_checkout)
   * - Perform fetches  (setup_fetch)
   * - Apply patches  (setup_patch)
   */
  public function setup() {
    // Setup codebase and working directories
    $this->setup_codebase();
    $this->setup_working_dir();

    // Bail if we don't have a setup stage in the job definition.
    if (empty($this->job_definition['setup'])) {
      return;
    }

    // Run through the job definition file setup stages.
    $this->do_setup();
  }

  function setup_codebase() {
    $arguments = $this->get_buildvars();
    // Check if the source codebase directory has been specified
    if (empty($arguments['DCI_CodeBase'])) {
      // If no explicit codebase provided, assume we are using the code in the local directory.
      $arguments['DCI_CodeBase'] = "./";
      $this->set_buildvars($arguments);
    }
  }

  function setup_working_dir() {
    // Check if the target working directory has been specified.
    if (empty($arguments['DCI_CheckoutDir'])) {
      // If no explicit working directory provided, we generate one in the system temporary directory.
      $tmpdir = $this->create_tempdir(sys_get_temp_dir() . '/drupalci/', $this->jobtype . "-");
      if (!$tmpdir) {
        // Error creating checkout directory
        $this->error_output("Error", "Failure encountered while attempting to create a local checkout directory");
        return;
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
  }


  public function do_setup() {
    $setup = $this->job_definition['setup'];
    foreach ($setup as $step => $details) {
      $func = "setup_" . $step;
      if (!isset($details[0])) {
        // Non-numeric array found ... assume we have only one iteration.
        // We wrap this in an array in order to handle both singletons and
        // arrays with the same code.
        $details = array($details);
      }
      foreach ($details as $iteration => $detail) {
        $this->$func($detail);
        // Handle errors encountered during sub-function execution.
        if ($this->error_status != 0) {
          echo "Received failed return code from function $func.";
          return;
        }
      }
    }
    return;
  }

  protected function setup_checkout($details) {
    $this->output->writeln("<info>Entering setup_checkout().</info>");
    // TODO: Ensure $details contains all required parameters
    $protocol = isset($details['protocol']) ? $details['protocol'] : 'git';
    $func = "setup_checkout_" . $protocol;
    return $this->$func($details);
  }

  protected function setup_checkout_git($details) {
    $this->output->writeln("<info>Entering setup_checkout_git().</info>");
    $repo = isset($details['repo']) ? $details['repo'] : 'git://drupalcode.org/project/drupal.git';
    $gitbranch = isset($details['branch']) ? $details['branch'] : 'master';
    $gitdepth = isset($details['depth']) ? $details['depth'] : NULL;
    $workingdir = $this->working_dir;

    $checkoutdir = isset($details['checkout_dir']) ? $details['checkout_dir'] : $workingdir;
    // TODO: Ensure we don't end up with double slashes
    // Validate target directory.  Must be within workingdir.
    if (!($directory = $this->validate_directory($checkoutdir))) {
      // Invalid checkout directory
      $this->error_output("Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }

    $this->output->writeln("<comment>Performing git checkout of $repo $gitbranch branch to $directory.</comment>");

    $cmd = "git clone -b $gitbranch $repo $directory";
    if (!is_null($gitdepth)) {
      $cmd .=" --depth=$gitdepth";
    }
    exec($cmd, $cmdoutput, $result);
    if ($result !==0) {
      // Git threw an error.
      $this->error_output("Checkout failed", "The git checkout returned an error.");
      // TODO: Pass on the actual return value for the git checkout
      return;
    }
    $this->output->writeln("<comment>Checkout complete.</comment>");
  }

  protected function setup_fetch($details) {
    $this->output->writeln("<info>Entering setup_fetch().</info>");
    // URL and target directory
    // TODO: Ensure $details contains all required parameters
    if (empty($details['url'])) {
      $this->error_output("Error", "No valid target file provided for fetch command.");
      return;
    }
    $url = $details['url'];
    $workingdir = realpath($this->working_dir);
    $fetchdir = (!empty($details['fetch_dir'])) ? $details['fetch_dir'] : $workingdir;
    if (!($directory = $this->validate_directory($fetchdir))) {
      // Invalid checkout directory
      $this->error_output("Error", "The fetch directory <info>$directory</info> is invalid.");
      return;
    }
    $info = pathinfo($url);
    $destfile = $directory . "/" . $info['basename'];
    $contents = file_get_contents($url);
    if ($contents === FALSE) {
      $this->error_output("Error", "An error was encountered while attempting to fetch <info>$url</info>.");
      return;
    }
    if (file_put_contents($destfile, $contents) === FALSE) {
      $this->error_output("Error", "An error was encountered while attempting to write <info>$url</info> to <info>$directory</info>");
      return FALSE;
    }
    $this->output->writeln("<comment>Fetch of <options=bold>$url</options=bold> to <options=bold>$destfile</options=bold> complete.</comment>");
  }

  protected function setup_patch($details) {
    $this->output->writeln("<info>Entering setup_patch().</info>");
    if (empty($details['patch_file'])) {
      $this->error_output("Error", "No valid patch file provided for the patch command.");
      return;
    }
    $workingdir = realpath($this->working_dir);
    $patchfile = $details['patch_file'];
    $patchdir = (!empty($details['patch_dir'])) ? $details['patch_dir'] : $workingdir;
    // Validate target directory.
    if (!($directory = $this->validate_directory($patchdir))) {
      // Invalid checkout directory
      $this->error_output("Error", "The patch directory <info>$directory</info> is invalid.");
      return;
    }
    $cmd = "patch -p1 -i $patchfile -d $directory";

    exec($cmd, $cmdoutput, $result);
    if ($result !==0) {
      // The command threw an error.
      $this->error_output("Patch failed", "The patch attempt returned an error.");
      // TODO: Pass on the actual return value for the patch attempt
      return;
    }
    $this->output->writeln("<comment>Patch <options=bold>$patchfile</options=bold> applied to directory <options=bold>$directory</options=bold></comment>");
  }

  protected function validate_directory($dir) {
    // Validate target directory.  Must be within workingdir.
    $working_dir = $this->working_dir;
    $true_dir = realpath($dir);
    if (!empty($true_dir)) {
      if ($true_dir == realpath($working_dir)) {
        // Passed directory is the root working directory.
        return $true_dir;
      }
      // Passed directory is different than working directory. Check whether working directory included in path.
      elseif (strpos($true_dir, realpath($working_dir)) === 0) {
        // Passed directory is a subdirectory within the working path.
        return $true_dir;
      }
    }
    // Assume the Passed directory is a subdirectory of the working, without the working prefix.  Construct the full path.
    $directory = realpath($working_dir . "/" . $dir);
    // TODO: Ensure we don't have double slashes

    // Check whether this is a pre-existing directory
    if ($directory === FALSE) {
      // Directory doesn't exist. Create and then validate.
      mkdir($working_dir . "/" . $dir, 0777, TRUE);
      $directory = realpath($working_dir . "/" . $dir);
    }
    // Validate that resulting directory is still within the working directory path.
    if (!strpos(realpath($directory), realpath($working_dir)) === 0) {
      // Invalid checkout directory
      $this->error_output("Error", "The checkout directory <info>$directory</info> is invalid.");
      return;
    }

    // Return the updated directory value.
    return $directory;
  }

  // Note:  After setup has run, the rest of these need to happen on the container ... so instead of
  // executing directly, we store as instructions for the container to execute.  Perhaps we can have the
  // container parse these out of the .drupalci.yml file directly???

  public function install() {
/*
  install:
    command: pre_install_script.php
    composer:
        action: install
    drupal_install:
        action: install
    command: post_install_script.php
*/
    // TODO: Do these go in a scripts directory passed into each container?
    // TODO: Figure out how we can get these executed on the container directly.
    // Change logic to build out a list of instructions on the container side.
    // Essentially, we need to build the contents of start.sh dynamically!


    /*
    // Bail if we don't have an install stage in the job definition.
    if (empty($this->job_definition['install'])) {
      return;
    }

    $install = $this->job_definition['install'];
    foreach ($install as $step => $details) {
      $func = "install_" . $step;
      if (!isset($details[0])) {
        // Non-numeric array found ... assume we have only one iteration.
        // We wrap this in an array in order to handle both singletons and
        // arrays with the same code.
        $details = array($details);
      }
      foreach ($details as $iteration => $detail) {
        //$result = $this->$func($detail);
        $this->$func($detail);
        // Handle errors encountered during sub-function execution.
        if ($this->error_status != 0) {
          echo "Received failed return code from function $func.";
          return;
        }
      }
    }
    */
    return;
  }

  protected function install_command($details) {

  }


  public function validate_install() {
    // Validate that any required linked containers are actually running.
    return;
  }

  public function execute() {
    return;
  }

  public function complete() {
    // Run any post-execute clean-up or notification scripts, as desired.
    return;
  }

  public function success() {
    // Run any post-execute clean-up or notification scripts, which are
    // intended to be run only upon success.
    return;
  }

  public function failure() {
    // Run any post-execute clean-up or notification scripts, which are
    // intended to be run only upon failure.
    return;
  }

  protected function error_output($type = 'Error', $message = 'DrupalCI has encountered an error.') {
    if (!empty($type)) {
      $this->output->writeln("<error>$type</error>");
    }
    if (!empty($message)) {
      $this->output->writeln("<comment>$message</comment>");
    }
    $this->error_status = -1;
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