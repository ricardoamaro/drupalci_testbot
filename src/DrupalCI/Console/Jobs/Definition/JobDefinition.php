<?php
/**
 * Created by PhpStorm.
 * User: Jeremy
 * Date: 1/19/15
 * Time: 7:50 PM
 */

namespace DrupalCI\Console\Jobs\Definition;

use Symfony\Component\Yaml\Parser;

class JobDefinition {

  // The definition source may be a local file or URL
  protected $source = NULL;

  // TODO: Parse passed $source variable and set any per-component values which can be deduced.
  //protected $scheme = NULL;

  //protected $host = NULL;

  //protected $path = NULL;

  //protected $directory = NULL;

  //protected $filename = NULL;

  // Placeholder for parsed key=>value parameter pairs
  protected $parameters = array();

  public function load($source) {
    if (!empty($source)) {
      $this->source = $source;
      $yaml = new Parser();
      if ($content = file_get_contents($this->source)) {
        $parameters = $yaml->parse($content);
      }
      else {
        // TODO: Error Handling
        return -1;
      }
      //$parameters = $yaml->parse(file_get_contents($this->source));
      $this->parameters = $parameters;
    }
    return;
  }

  public function getParameters() {
    return $this->parameters;
  }

}