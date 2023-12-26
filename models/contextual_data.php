<?php

/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * Here, we set functions that are regularly used to create
 * our views.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
 **********************************************************/

/**
 * Get Values For Reports View
 */

/**
 * Get Status Dashboard Values
 */
function get_status_dash_data()
{
    $status_dash_data = DataAccess::get_focused_view(
        'notices',
        [],
        'status_dash'
    );
    return $status_dash_data;
}
/**
 * Get Log Values for Chart
 */
function get_time_chart_data(){
    $time_chart_data = DataAccess::get_focused_view(
        'logs',
        [],
        'time_chart'
    );
    $formatted_time_chart_data =[];
    foreach($time_chart_data as $chart_node_data){
        $formatted_time_chart_data['dates'][]=$chart_node_data->month_year;
        $formatted_time_chart_data['active'][] = $chart_node_data->active_count;
        $formatted_time_chart_data['ignored'][] = $chart_node_data->ignored_count;
        $formatted_time_chart_data['equalified'][] = $chart_node_data->equalified_count;
    }
    return $formatted_time_chart_data;
}

/**
 * Get Notice Values For Notice Table
 */
function get_notice_data()
{
    $notice_table_data = DataAccess::get_focused_view(
        'notices',
        [],
        'notice_table'
    );
    return $notice_table_data;
}

/**
 * Get Tag Slugs and Counts For Tags Table
 */
function get_tag_counts()
{
    $notice_tags_data = DataAccess::get_focused_view(
        'notices',
        [],
        'tags_table'
    );

    $tags_data = DataAccess::get_db_rows(
        'tags',
        [],
        1,
        10000,
    )['content'];

    $tag_counts = [];
    foreach ($notice_tags_data as $notice_tags) {
        $tags = unserialize($notice_tags->tags);
        foreach ($tags as $tag) {
            $title = $tag;
            if (!empty($tag_counts[$title])) {
                $tag_counts[$title]++;
            } else {
                $tag_counts[$title] = 1;
            }
        }
    }
    $mapped_tag_counts = [];

    foreach ($tags_data as $tag_data) {
        $title = $tag_data->title;
        $slug = $tag_data->slug;
        $count = isset($tag_counts[$title]) ? $tag_counts[$title] : 0; // Get the count from the map, default to 0 if not found
        $mapped_tag_counts[] = ["title" => $title, "slug" => $slug, "count" => $count];
    };

    return $mapped_tag_counts;
}

/**
 * Get Related URL Values For URL Table
 */

function get_related_url_data()
{
    $related_url_table_data = DataAccess::get_focused_view(
        'notices',
        [],
        'related_url_table'
    );
    return $related_url_table_data;
}

/** 
 * End Report Specific Functions
 */
