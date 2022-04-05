<?php
/**
 * WordPress Properties Adder
 */
function wordpress_properties_adder($site_url){

    // Reformat URL for JSON request.
    $json_url = $site_url.'wp-json/wp/v2/pages?per_page=100';

    // Get URL contents.
    $curl = curl_init($json_url);
    curl_setopt($curl, CURLOPT_URL, $json_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Equalify');
    $url_contents = curl_exec($curl);
    if($url_contents == false)
        throw new Exception('Contents of "'.$site_url.'" cannot be loaded');
    curl_close($curl);

    // Create JSON.
    $wp_api_json = json_decode($url_contents, true);
    if(empty($wp_api_json[0]))
        throw new Exception('The URL "'.$site_url.'" does not contain valid WordPress API V2 JSON');

    // Push JSON to properties array.
    $properties = [];
    foreach ($wp_api_json as $property):
        array_push($properties, array('url' => $property['link']));
    endforeach;
    return $properties;
}

/**
 * XML Site Adder
 */
function xml_site_adder($site_url){

    // Reformat URL for XML request.
    $xml_url = $site_url.'sitemap.xml';

    // Get URL contents.
    $curl = curl_init($xml_url);
    curl_setopt($curl, CURLOPT_URL, $xml_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Equalify');
    $url_contents = curl_exec($curl);
    if($url_contents == false)
        throw new Exception('Contents of "'.$site_url.'" cannot be loaded');
    curl_close($curl);

    // Create JSON.
    $drupal_xml = xmlrpc_decode($url_contents, true);
    if(empty($drupal_xml[0]))
        throw new Exception('The URL "'.$site_url.'" does not contain valid WordPress API V2 JSON');
    var_dump($drupal_xml); die;

    // Push JSON to properties array.
    $properties = [];
    foreach ($drupal_xml as $property):
        array_push($properties, array('url' => $property['link']));
    endforeach;

    // Insert properties.
    $properties_records = [];
    foreach ($properties as &$property):

        // Set parent.
        if($property['url'] == $site_url || $property['url'] == $site_url.'/'){
            $is_parent = 1;  
        }else{
            $is_parent = NULL;                    
        }
        $property_group = $site_url;                    

        // Push each property to properties' records.
        // TODO: Make group an id instead of URL
        array_push(
            $properties_records, 
            array(
                'url'       => $property['url'], 
                'group'     => $property_group,
                'is_parent' => $is_parent,
                'status'    => 'active',
                'type'      => 'wordpress'
            )
        );

    endforeach;

    // Return Properties
    return $properties_records;

}