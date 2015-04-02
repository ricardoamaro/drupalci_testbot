<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\BuildSteps\setup\Fetch
 *
 * Processes "setup: fetch:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\BuildSteps\setup;
use DrupalCI\Plugin\JobTypes\JobInterface;

/**
 * @PluginID("fetch")
 */
class Fetch extends SetupBase {

  /**
   * {@inheritdoc}
   */
  public function run(JobInterface $job, $data) {
    // Data format:
    // i) array('url' => '...', 'fetch_dir' => '...')
    // or
    // iii) array(array(...), array(...))
    // Normalize data to the third format, if necessary
    $data = (count($data) == count($data, COUNT_RECURSIVE)) ? [$data] : $data;
    $job->getOutput()->writeln("<info>Entering setup_fetch().</info>");
    foreach ($data as $key => $details) {
      // URL and target directory
      // TODO: Ensure $details contains all required parameters
      if (empty($details['url'])) {
        $job->errorOutput("Error", "No valid target file provided for fetch command.");
        return;
      }
      $url = $details['url'];
      $workingdir = realpath($job->getWorkingDir());
      $fetchdir = (!empty($details['fetch_dir'])) ? $details['fetch_dir'] : $workingdir;
      if (!($directory = $this->validate_directory($job, $fetchdir))) {
        // Invalid checkout directory
        $job->errorOutput("Error", "The fetch directory <info>$directory</info> is invalid.");
        return;
      }
      $info = pathinfo($url);
      $destfile = $directory . "/" . $info['basename'];
      $contents = file_get_contents($url);
      if ($contents === FALSE) {
        $job->errorOutput("Error", "An error was encountered while attempting to fetch <info>$url</info>.");
        return;
      }
      if (file_put_contents($destfile, $contents) === FALSE) {
        $job->errorOutput("Error", "An error was encountered while attempting to write <info>$url</info> to <info>$directory</info>");
        return;
      }
      $job->getOutput()->writeln("<comment>Fetch of <options=bold>$url</options=bold> to <options=bold>$destfile</options=bold> complete.</comment>");
    }
  }
}
