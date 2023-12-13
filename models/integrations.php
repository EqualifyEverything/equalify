<?php

/**
 * Uploaded Integrations
 */
function uploaded_integrations($path_to_integrations){

    // List all uploaded integrations.
    $integration_folders = array_diff(scandir($path_to_integrations), array('..', '.'));
    $uploaded_integrations = [];
    foreach ($integration_folders as $integration_folder){

		// Exclude certain files or folders
		$excluded_items = array('.DS_Store');

        // Create URI from folder name.
		if(!in_array($integration_folder, $excluded_items)){
			array_push(
				$uploaded_integrations, 
				array(
					'uri' => $integration_folder
				)
			);
		}

    }
    return $uploaded_integrations;

}

/**
 * Get Integration Meta
 *
 * Modeled after WordPress's get_file_data() .. Searches for metadata in the 
 * first 8 KB of an integration. Each piece of metadata must be on its own line. 
 * Fields can not span multiple lines, the value will get cut at the end of the 
 * first line.
 *
 * If the file data is not within that first 8 KB, then the author should correct
 * their integration file and move the data headers to the top.
 *
 * @param string $file            Absolute path to the file.
 * @return string[] Array of file header values keyed by header name.
 */
function get_integration_meta( $file ) {

	// We need to read the meta.
	$fp = fopen( $file, 'r' );

	if ( $fp ) {

		// Pull only the first 8 KB of the file in.
		$file_data = fread( $fp, 8192 );

		// PHP will close file handle, but we are good citizens.
		fclose( $fp );

	} else {
		$file_data = '';
	}

	// Make sure we catch CR-only line endings.
	$file_data = str_replace( "\r", "\n", $file_data );

    // Set headers.
    $headers = array(
		'name'        => 'name',
        'status'      => 'status',
		'description' => 'description'
	);
	foreach ( $headers as $field => $regex ) {
		if ( preg_match( '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] ) {
            $cleaned_header_comment = trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $match[1]) );
			$headers[ $field ] = $cleaned_header_comment;
		} else {
			$headers[ $field ] = '';
		}
	}

	return $headers;
}

/**
 * Get Integration Tags
 */
function get_integration_tags( $uri ){

	// Get integration file.
	$integration_path = __DIR__.'/../integrations/'.$uri.'/functions.php';
    require_once $integration_path;
    $integration_db_tags = $uri.'_tags';
    if( function_exists( $integration_db_tags) ){
		return $integration_db_tags();
	}else{
		false;
	}

}