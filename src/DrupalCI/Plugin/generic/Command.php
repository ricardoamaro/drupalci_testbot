<?php
/**
 * @file
 * Contains \DrupalCI\Plugin\generic\Command
 *
 * Processes "[build_step]: command:" instructions from within a job definition.
 */

namespace DrupalCI\Plugin\generic;
use DrupalCI\Plugin\PluginBase;

/**
 * @PluginID("command")
 */
class Command extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function run($job, $data) {
    // Data format: 'command [arguments]' or array('command [arguments]', 'command [arguments]')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $data = is_array($data) ? $data : [$data];
    foreach ($data as $key => $details) {
      // TODO: Validation and security checks
      $cmd = $details;
      exec($cmd, $cmdoutput, $result);
      if ($result !==0) {
        // The command threw an error.
        $job->error_output("Command failed", "The command returned a non-zero result code.");
        $job->output->writeln("<comment>Attempted command: <options=bold>$cmd</options=bold></comment>");
        $job->output->writeln("<comment>Result code: <options=bold>$result</options=bold></comment>");
        $job->output->writeln("<comment>Command output:</comment>");
        $job->output->writeln($cmdoutput);
        // TODO: Pass on the actual return value for the patch attempt
        return;
      }
      $job->output->writeln("<comment>Command <options=bold>$cmd</options=bold> complete.</comment>");
      if (!empty($cmdoutput)) {
        $job->output->writeln("<comment>Command Output:</comment>");
        $job->output->writeln($cmdoutput);
      }
    }
  }
}
