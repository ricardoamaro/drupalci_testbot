<?php
$settings["container_yamls"][] = __DIR__ . "/services.yml";
include __DIR__ . '/settings.testing.php';
$databases['default']['default'] = array (
  'database' => '[host]',
  'prefix' => '',
  'namespace' => 'Drupal\\Driver\\Database\\mongodb',
  'driver' => 'mongodb',
);
