<?php

/**
 * @file
 * Local Lando settings for the Simple Voting project.
 */

$databases['default']['default'] = [
  'database' => 'drupal11',
  'username' => 'drupal11',
  'password' => 'drupal11',
  'prefix' => '',
  'host' => 'database',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
];

$settings['hash_salt'] = 'simple-voting-local-project';
$settings['trusted_host_patterns'] = [
  '^simple-voting\\.lndo\\.site$',
  '^localhost$',
  '^127\\.0\\.0\\.1$',
];

$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config/sync';
