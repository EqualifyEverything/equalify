<?php
$alert['Guideline'] = 'WCAG2AA.Principle3.Guideline3_2.3_2_1.G107';

// Format guideline.
if(!empty($alert['Guideline'])){

    // First, we're going to get the general guideline.
    $general_guideline = '';
    $general_pattern = "/(?:[^.]+\.){2}([^.]+).*/";
    $general_search = preg_match(
        $general_pattern, $alert['Guideline'], $general_guideline
    );
    if(!empty($general_search))
        $general_guideline = str_replace(
            'Guideline', '', str_replace('_', '.', $general_guideline[1])
        );

    // Now let's get the subguideline.
    $sub_guideline = '';
    $sub_pattern = "/(?:[^.]+\.){3}([^.]+).*/";
    $sub_search = preg_match(
        $sub_pattern, $alert['Guideline'], $sub_guideline
    );
    if(!empty($sub_search))
        $sub_guideline = str_replace('_', '.', $sub_guideline[1]);

    // Let's put them all together.
    if(!empty($general_guideline) && !empty($sub_guideline))
        $separator = ', ';
    $guideline = $general_guideline.$separator.$sub_guideline;
    
    print_r($guideline);

}