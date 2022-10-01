<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document creates the new report view. 
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Let's setup the variables that we're going to be using
// in this document.
$data = array(
    'name'  => '',
    'title' => 'Untitled',
    'status' => '',
    'type'   => ''
);

// We use this view to customize reports if a id is 
// provided, otherwise we create a new report.
if(!empty($_GET['name'])){
    $data['name'] = $_GET['name'];
    
    // Let's load in predefined variables for the report.
    $existing_report = unserialize(
        DataAccess::get_meta_value($data['name'])
    );

    // Let's reformat the meta so we can use it in a
    // more understandable format.
    foreach($existing_report as $report) {
        if($report['name'] == 'title') 
            $data['title'] = $report['value'];
        if($report['name'] == 'type') 
            $data['type'] = $report['value'];
        if($report['name'] == 'status') 
            $data['status'] = $report['value'];
    }

}
?>

<section>
    <div class="mb-3 pb-4 border-bottom">
        <h1>
            <?php
            // Lets add helper text, depending on if we're
            // editing the report.
            if(!empty($data['name'])){
                echo 'Editing ';
            }else{
                echo 'New ';
            }
            ?>
            
            "<span id="reportName"><?php echo $data['title'];?></span>" Report
        </h1>
    </div>
        
    <?php
    // The report settings fields.
    the_report_settings($data);
    ?>

</section>
<script>

// Change report title text as you type.
const reportName = document.getElementById('reportName');
const reportNameInput = document.getElementById('reportTitleInput');
const changeReportName = function(e) {
    reportName.innerHTML = e.target.value;
}
reportNameInput.addEventListener('input', changeReportName);
reportNameInput.addEventListener('propertychange', changeReportName);

</script>