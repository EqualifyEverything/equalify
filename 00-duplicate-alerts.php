<?php


$existing_alerts = array(
  array(
    'id' => '1',
    'time' => '2022-07-19 13:54:30',
    'status' => 'active',
    'type' => 'notice',
    'source' => 'little_forest',
    'url' => 'https://decubing.com',
    'message' => '[code]<title>...</title>[/code]Check that!',
    'meta' => 'a:2:{s:9:"guideline";s:43:"WCAGtle";}'
  ),
  array(
    'id' => '2',
    'time' => '2022-07-19 13:54:30',
    'status' => 'active',
    'type' => 'notice',
    'source' => 'little_forest',
    'url' => 'https://equalify.app',
    'message' => '[code]<title>...</title>[/code]Check that!',
    'meta' => 'a:2:{s:9:"guiitle";}'
  )
);

$new_alerts = array(
  array(
    'status' => 'active',
    'type' => 'notice',
    'source' => 'little_forest',
    'url' => 'https://wpcampus.org',
    'message' => '[code]<title>...</title>[/code]Check that!',
    'meta' => 'a:2:{s:9:"guideline";s:43:"WCAGtle";}'
  ),
  array(
    'status' => 'active',
    'type' => 'notice',
    'source' => 'little_forest',
    'url' => 'https://equalify.app',
    'message' => '[code]<title>...</title>[/code]Check that!',
    'meta' => 'a:2:{s:9:"guiitle";}'
  )
);

function make_alert_key($alert) {
  return json_encode(array(
    $alert['type'],
    $alert['source'],
    $alert['url'],
    $alert['message']
  ));
}

function get_duplicate_alerts(&$existing_alerts, &$new_alerts, &$duplicate_alerts) {
  $alert_keys = array();
  for ($i = 0; $i < count($existing_alerts); $i++) {
    $existing_alert = $existing_alerts[$i];
    $alert_key = make_alert_key($existing_alert);
    $alert_keys[$alert_key] = $i;
  }
  for ($j = 0; $j < count($new_alerts); $j++) {
    $new_alert = $new_alerts[$j];
    $alert_key = make_alert_key($new_alert);
    if (isset($alert_keys[$alert_key])) {
      $i = $alert_keys[$alert_key];
      $duplicate_alerts[] = $new_alert;
      unset($existing_alerts[$j]);
      unset($new_alerts[$i]);
    }
  }
  // Reset array indices after removing duplicates
  $existing_alerts = array_values($existing_alerts);
  $new_alerts = array_values($new_alerts);
}

$duplicate_alerts = [];
get_duplicate_alerts($existing_alerts, $new_alerts, $duplicate_alerts);

print('$existing_alerts = ' . var_export($existing_alerts, true) . "\n\n");
print('$new_alerts = ' . var_export($new_alerts, true) . "\n\n");
print('$duplicate_alerts = ' . var_export($duplicate_alerts, true) . "\n\n");

