<?php
/**
 * Get an alert array that can be used for comparisons.
 *
 * @param array $alert The full alert.
 * @return array
 */
function get_alert_entry($alert) {
	return array(
		'type' => $alert['type'],
		'source' => $alert['source'],
		'url' => $alert['url'],
		'message' => $alert['message'],
	);
}

/**
 * Compare two arrays of alerts, exclude duplicates, return all three arrays.
 *
 * @param array $existing The array of existing alerts.
 * @param array $existing The array of new alerts.
 * @return array
 */
function compare_alerts($existing, $new) {
	$alerts = array(
		'existing' => array(),
		'new' => array(),
		'duplicate' => array(),
	);

	$existing_entries = array();
	foreach ($existing as $alert) {
		$existing_entries[] = get_alert_entry($alert);
	}

	$new_entries = array();
	foreach ($new as $alert) {
		$new_entry = get_alert_entry($alert);
		$new_entries[] = $new_entry;

		if (in_array($new_entry, $existing_entries, true)) {
			$alerts['duplicate'][] = $alert;
		} else {
			$alerts['new'][] = $alert;
		}
	}

	foreach ($existing as $alert) {
		$existing_entry = get_alert_entry($alert);

		if (!in_array($existing_entry, $new_entries, true)) {
			$alerts['existing'][] = $alert;
		}
	}

	return $alerts;
}

function test_compare_alerts() {
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

	$alerts = compare_alerts($existing_alerts, $new_alerts);

	var_dump(
		$alerts['existing'] === array(
			array(
				'id' => '1',
				'time' => '2022-07-19 13:54:30',
				'status' => 'active',
				'type' => 'notice',
				'source' => 'little_forest',
				'url' => 'https://decubing.com',
				'message' => '[code]<title>...</title>[/code]Check that!',
				'meta' => 'a:2:{s:9:"guideline";s:43:"WCAGtle";}'
			)
		),
		$alerts['new'] === array(
			array(
				'status' => 'active',
				'type' => 'notice',
				'source' => 'little_forest',
				'url' => 'https://wpcampus.org',
				'message' => '[code]<title>...</title>[/code]Check that!',
				'meta' => 'a:2:{s:9:"guideline";s:43:"WCAGtle";}'
			)
		),
		$alerts['duplicate'] === array(
			array(
				'status' => 'active',
				'type' => 'notice',
				'source' => 'little_forest',
				'url' => 'https://equalify.app',
				'message' => '[code]<title>...</title>[/code]Check that!',
				'meta' => 'a:2:{s:9:"guiitle";}'
			)
		)
	);
}
