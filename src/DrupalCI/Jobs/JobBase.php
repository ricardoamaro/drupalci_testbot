<?php
/**
 * @file
 * Base Job class for DrupalCI.
 */

namespace DrupalCI\Jobs;

use Drupal\Component\Annotation\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use DrupalCI\Jobs\Component\Configurator;
use DrupalCI\Jobs\Component\EnvironmentValidator;
use DrupalCI\Jobs\Component\ParameterValidator;
use DrupalCI\Jobs\Component\SetupComponent;
use DrupalCI\Jobs\Component\SetupDirectoriesComponent;
use Symfony\Component\Process\Process;
use DrupalCI\Console\Jobs\ContainerBase;
use DrupalCI\Console\Helpers\ContainerHelper;
use Docker\Docker;
use Docker\Http\DockerClient as Client;
use Symfony\Component\Yaml\Yaml;
use Docker\Container;
use Docker\PortCollection;

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

  /**
   * @var array
   */
  protected $pluginDefinitions;

  /**
   * @var array
   */
  protected $plugins;

  // Holds the name and Docker IDs of our service containers.
  public $service_containers;

  /**
   * @param mixed $service_containers
   */
  public function setServiceContainers($service_containers)
  {
    $this->service_containers = $service_containers;
  }

  /**
   * @return mixed
   */
  public function getServiceContainers()
  {
    return $this->service_containers;
  }

  /**
   * @param mixed $executable_containers
   */
  public function setExecutableContainers($executable_containers)
  {
    $this->executable_containers = $executable_containers;
  }

  /**
   * @return mixed
   */
  public function getExecutableContainers()
  {
    return $this->executable_containers;
  }

  // Holds the name and Docker IDs of our executable containers.
  public $executable_containers;

  // Holds our Docker container manager
  private $docker;

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

  public function validate() {
    $this->output->write("<comment>Validating test parameters ... </comment>");
    $validator = new ParameterValidator();
    $result = $validator->validate($this);
    if ($result) {
      $this->output->writeln("<info>PASSED</info>");
    }
    return;
  }

  public function environment() {
    $this->output->writeln("<comment>Validating environment parameters ...</comment>");
    // The 'environment' step determines which containers are going to be
    // required, validates that the appropriate container images exist, and
    // starts any required service containers.
    $validator = new EnvironmentValidator();
    $validator->build_container_names($this);
    $validator->validate_container_names($this);
    $validator->start_service_containers($this);
  }

  /*
   *  The setup stage will be responsible for:
   * - Perform checkouts (setup_checkout)
   * - Perform fetches  (setup_fetch)
   * - Apply patches  (setup_patch)
   */
  public function setup() {
    // Setup codebase and working directories
    $presetup = new SetupDirectoriesComponent();
    $presetup->setup_codebase($this);
    $presetup->setup_working_dir($this);
    // Run through the job definition file setup stages.
    $setup = new SetupComponent();
    $setup->execute($this);
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

  public function error_output($type = 'Error', $message = 'DrupalCI has encountered an error.') {
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

  protected function discoverPlugins() {
    $dir = 'src/DrupalCI/Plugin';
    $plugin_definitions = [];
    foreach (new \DirectoryIterator($dir) as $file) {
      if ($file->isDir() && !$file->isDot()) {
        $plugin_type = $file->getFilename();
        $plugin_namespaces = ["DrupalCI\\Plugin\\$plugin_type" => ["$dir/$plugin_type"]];
        $discovery  = new AnnotatedClassDiscovery($plugin_namespaces, 'Drupal\Component\Annotation\PluginID');
        $plugin_definitions[$plugin_type] = $discovery->getDefinitions();
      }
    }
    return $plugin_definitions;
  }

  /**
   * @return \DrupalCI\Plugin\PluginBase
   */
  protected function getPlugin($type, $plugin_id, $configuration = []) {
    if (!isset($this->pluginDefinitions)) {
      $this->pluginDefinitions = $this->discoverPlugins();
    }
    if (!isset($this->plugins[$type][$plugin_id])) {
      if (isset($this->pluginDefinitions[$type][$plugin_id])) {
        $plugin_definition = $this->pluginDefinitions[$type][$plugin_id];
      }
      elseif (isset($this->pluginDefinitions['generic'][$plugin_id])) {
        $plugin_definition = $this->pluginDefinitions['generic'][$plugin_id];
      }
      else {
        throw new PluginNotFoundException("Plugin type $type plugin id $plugin_id not found.");
      }
      $this->plugins[$type][$plugin_id] = new $plugin_definition['class']($configuration, $plugin_id, $plugin_definition);
    }
    return $this->plugins[$type][$plugin_id];
  }


  public function getDocker()
  {
    $client = Client::createWithEnv();
    if (null === $this->docker) {
      $this->docker = new Docker($client);
    }
    return $this->docker;
  }

  public function getExecContainers() {
    $configs = $this->executable_containers;
    foreach ($configs as $type => $containers) {
      foreach ($containers as $key => $container) {
        // Check if container is created.  If not, create it
        if (empty($container['created'])) {
          $this->startContainer($container);
          $this->executable_containers[$type][$key] = $container;
        }
      }
    }
    return $this->executable_containers;
  }

  public function startContainer(&$container) {
    $docker = $this->getDocker();
    $manager = $docker->getContainerManager();
    // Get container configuration, which defines parameters such as exposed ports, etc.
    $configs = $this->getContainerConfiguration($container['image']);
    $config = $configs[$container['image']];
    // TODO: Allow classes to modify the default configuration before processing
    // Add service container links
    $links = $this->createContainerLinks();
    if (!empty($links)) {
      $existing = (!empty($config['HostConfig']['Links'])) ? $config['HostConfig']['Links'] : array();
      $config['HostConfig']['Links'] = $existing + $links;
    }
    // Add volumes
    $volumes = $this->createContainerVolumes();
    if (!empty($volumes)) {
      foreach ($volumes as $dir => $volume) {
        $config['Volumes']["$dir"] = $volume;
      }
    }
    $instance = new Container($config);
    // $instance->setCmd(['/bin/true']);
    $instance->setCmd(['/bin/bash', '-c', 'ls /tmp/test']);
    $manager->create($instance);
    $manager->run($instance);
    $container['id'] = $instance->getID();
    $container['name'] = $instance->getName();
    $container['created'] = TRUE;
    $short_id = substr($container['id'], 0, 8);
    $this->output->writeln("<comment>Container <options=bold>${container['name']}</options=bold> created from image <options=bold>${container['image']}</options=bold> with ID <options=bold>$short_id</options=bold></comment>");

    /*
    $type = 0;
    $output = "";

    $response = $manager->attach($container, function ($log, $stdtype) use (&$type, &$output) {
      $type = $stdtype;
      $output = $log;
    });

    $manager->start($container);
    $manager->wait($container);
    */

  }

  private function createContainerLinks() {
    if (empty($this->service_containers)) { return; }
    $links = array();
    $config = $this->service_containers;
    foreach ($config as $type => $containers) {
      foreach ($containers as $key => $container) {
        $links[] = "${container['name']}:${container['name']}";
      }
    }
    return $links;
  }

  private function createContainerVolumes() {
    $volumes = array();
    // Map working directory
    $working = $this->working_dir;
    $volumes[$working] = array();
    // TODO: Map results directory
    return $volumes;
  }

  public function getContainerConfiguration($image = NULL) {
    $path = __DIR__ . '/../Containers';
    // RecursiveDirectoryIterator recurses into directories and returns an
    // iterator for each directory. RecursiveIteratorIterator then iterates over
    // each of the directory iterators, which consecutively return the files in
    // each directory.
    $directory = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
    $configs = [];
    foreach ($directory as $file) {
      if (!$file->isDir() && $file->isReadable() && $file->getExtension() === 'yml') {
        $image_name = 'drupalci/' . $file->getBasename('.yml');
        if (!empty($image) && $image_name != $image) {
          continue;
        }
        // Get the default configuration.
        $container_config = Yaml::parse(file_get_contents($file->getPathname()));
        $configs[$image_name] = $container_config;
      }
    }
    return $configs;
  }

  public function startServiceContainerDaemons($type) {
    $docker = $this->getDocker();
    $manager = $docker->getContainerManager();
    $instances = array();
    foreach ($manager->findAll() as $running) {
      $repo = $running->getImage()->getRepository();
      $id = substr($running->getID(), 0, 8);
      $instances[$repo] = $id;
    };
    foreach ($this->service_containers[$type] as $key => $image) {
      if (in_array($image['image'], array_keys($instances))) {
        // TODO: Determine service container ports, id, etc, and save it to the job.
        $this->output->writeln("<comment>Found existing <options=bold>${image['image']}</options=bold> service container instance.</comment>");
        // TODO: Load up container parameters
        $container = $manager->find($instances[$image['image']]);
        $container_id = $container->getID();
        $container_name = $container->getName();
        $this->service_containers[$type][$key]['id'] = $container_id;
        $this->service_containers[$type][$key]['name'] = $container_name;
        continue;
      }
      // Container not running, so we'll need to create it.
      $this->output->writeln("<comment>No active <options=bold>${image['image']}</options=bold> service container instances found. Generating new service container.</comment>");
      // Instantiate container
      $container = new Container(['Image' => $image['image']]);
      // Get container configuration, which defines parameters such as exposed ports, etc.
      $config = $this->getContainerConfiguration($image['image']);
      // TODO: Allow classes to modify the default configuration before processing
      // Configure the container
      $this->configureContainer($container, $config[$image['image']]);
      // Create the docker container instance, running as a daemon.
      // TODO: Ensure there are no stopped containers with the same name (currently throws fatal)
      $manager->run($container, function($output, $type) {
        fputs($type === 1 ? STDOUT : STDERR, $output);
      }, [], true);
      $container_id = $container->getID();
      $container_name = $container->getName();
      $this->service_containers[$type][$key]['id'] = $container_id;
      $this->service_containers[$type][$key]['name'] = $container_name;
      $short_id = substr($container_id, 0, 8);
      $this->output->writeln("<comment>Created new <options=bold>${image['image']}</options=bold> container instance with ID <options=bold>$short_id</options=bold></comment>");
    }
  }

  protected function configureContainer($container, $config) {
    if (!empty($config['name'])) {
      $container->setName($config['name']);
    }
    if (!empty($config['exposed_ports'])) {
      $ports = new PortCollection($config['exposed_ports']);
      $container->setExposedPorts($ports);
    }
    // TODO: Process Tmpfs configuration
  }

}
