<?php

namespace Equalify\Controller;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Exception;

/**
 * @author Chris Kelly (TolstoyDotCom)
 */
class SingleAlertController extends BaseController {

    /**
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     */
    public function run() : void {
        // Get alert ID.
        $alert_id = $_GET['id'];
        if(empty($alert_id))
            throw new Exception('You have not supplied an alert id');

        // Now lets get the alert.
        $filtered_to_alert = array(
            array(
                'name' => 'id',
                'value' => $alert_id
            )
        );

        $report = (array) $this->db->getRows(
            'alerts', $filtered_to_alert
        )['content'][0];

        if (strlen($report['url']) > 20) {
            $shortUrl = substr($report['url'], 0, 20).'...';
        }
        else {
            $shortUrl = $report['url'];
        }

        // This was formatted for a pretty-printed JSON dump.
        $info_pieces = json_decode($report['more_info'], TRUE);
        $escaped_info = $info_pieces ? htmlspecialchars( $report['more_info'], ENT_NOQUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8', FALSE) : NULL;

        $params = [
            'report' => $report,
            'short_url' => $shortUrl,
            'tags' => !empty($report['tags']) ? $this->getTags($report['tags']) : [],
            'escaped_info' => $escaped_info,
        ];

        echo $this->container->get('twig')->render('single-alert.html.twig', $params);
    }

    protected function getTags($rawTags) {
        $ret = [];

        // Create badges for tags.
        if (empty($rawTags)) {
            return $ret;
        }

        $tag_array = preg_split ("/\,/", $rawTags);
        foreach ($tag_array as $tag) {
            // Get Tag Title
            if (empty($tag)){
                continue;
            }

            $tag_filter = [[ 'name' => 'slug', 'value' => $tag ]];
            $tag_result = $this->db->getRows('tags', $tag_filter);
            $tag_info = (array) $tag_result['content'][0] ?? [];
            if (!empty($tag_info)) {
                $ret[] = $tag_info['title'];
            }
        }

        return $ret;
    }

}

/*
            // $count = 0;
            // foreach($info_pieces as $info_key => $info_val){
            //     $count++;
            //     echo '<div class="mb-3 pb-3 border-bottom" id="error-'.$count.'">';
            //     echo '<h3 class="fs-5">'.$info_key.'</h3>';
            //     echo '<pre aria-describe="code snippet" class="rounded bg-secondary 
            //     text-white p-3 mb-1"><code>'.$info_val.'</code></pre>';
            //     echo '</div>';
            // }
*/
