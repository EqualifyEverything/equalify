<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the report settings view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Meta_name is used to load existing report info.
if(empty($_GET['meta_name'])){
    
    // No meta_name means we can create a new report
    // or edit the active report, which is default.
    if(!isset($_GET['new_report'])){
        $_GET['meta_name'] = 'report_active';
    }

}

// Let's setup the variables that we're going to be using
// in this document.
$title = 'Untitled';
$status = '';
$type   = '';
$name   = '';
$site_id = '';
$preset = FALSE;

// We use this view to customize reports if a id is 
// provided, otherwise we create a new report.
if(!empty($_GET['meta_name'])){
    
    // Set the meta name
    $name = $_GET['meta_name'];

    // Some reports are preset. Presets have restricted fields
    // you can edit and special naming rules.
    $presets = array(
        'report_equalified', 'report_ignored', 'report_all',
        'report_active', 'report_active'
    );
    if(in_array($name, $presets))
        $preset = TRUE;

    // Let's load in predefined variables for the report.
    $existing_report = unserialize(
        DataAccess::get_meta_value($name)
    );

    // Some reports, like Equalified alerts, won't
    // have data, so we'll have to prepare variables
    if(empty($existing_report)){

        // Set the default title field.
        if($name == 'report_equalified'){
            $title = 'Equalified Alerts';
        }elseif($name == 'report_ignored'){
            $title = 'Ignored Alerts';
        }elseif($name == 'report_all'){
            $title = 'All Alerts';
        }elseif($name == 'report_active'){
            $title = 'Active Alerts';
        }

        // Set the default status field.
        if($name == 'report_equalified'){
            $status = 'equalified';
        }elseif($name == 'report_ignored'){
            $status = 'ignored';
        }elseif($name == 'report_active'){
            $status = 'active';
        }

    }else{

        // Let's reformat the meta so we can use it in a
        // more understandable format. The dynamically
        // added content is added to $dynamic_meta.
        $dynamic_meta = array();
        foreach($existing_report as $report) {
            if($report['name'] == 'title'){
                $title = $report['value'];
            }elseif($report['name'] == 'type'){
                $type = $report['value'];
            }elseif($report['name'] == 'status'){
                $status = $report['value'];
            }elseif($report['name'] == 'site_id'){
                $site_id = $report['value'];
            }else{
                $dynamic_meta[] = $report['name'];
            }
        }

    }
    
}
?>

<section>
    <div class="mb-3 pb-4 border-bottom">
        <h1>

            <?php
            // Lets add helper text, depending on if we're
            // creating a new report or not
            if(empty($name))
                echo 'New';
            ?>
            
            "<span id="reportName"><?php echo $title;?></span>"

            <?php
            // More helper text.
            if(empty($name) || (!empty($name) && $preset == FALSE))
                echo 'Report ';
            if(!empty($name))
                echo 'Filters & Settings';
            ?>
            
        </h1>
    </div>
    <form action="actions/save_report.php" method="post">

        <?php
        // Certain reports don't allow editing
        // of fields other than tag fields.
        if($preset === FALSE):
        ?>
        
        <div class="mb-3">
            <label for="reportTitleInput" class="form-label fw-semibold">Report Name</label>
            <input type="text" id="reportTitleInput" class="form-control" value="<?php echo $title;?>" name="title" required>
        </div>
        <hr>
        <div class="mb-3 row">
            <div class="col">
                <label for="statusSelect" class="form-label fw-semibold">Site</label>
                <select id="statusSelect" class="form-select" name="site_id">
                    <option value="">Any</option>

                    <?php 
                    // Get the sites.
                    $sites = DataAccess::get_db_rows( 'sites',
                        array(), 1, 10000000
                    )['content'];

                    // Build options.
                    if(!empty($sites)){
                        foreach ($sites as $site_option){

                            // A site may already be saved. 
                            if($site_option->id == $site_id){
                                $selected_attribute = 'selected';
                            }else{
                                $selected_attribute = '';
                            }

                            // Build option.
                            echo '<option value="'.$site_option->id.'" '
                            .$selected_attribute.'>'.
                            $site_option->url.'</option>';

                        }
                    }
                    ?>

                </select>
            </div>
            <div class="col">
                <label for="statusSelect" class="form-label fw-semibold">Alert Status</label>
                <select id="statusSelect" class="form-select" name="status">
                    <option value="">Any</option>

                    <?php 
                    // Set status as array so we can simplify
                    // the logic to build the option html.
                    $status_options = array(
                        'active', 'ignored', 'equalified'
                    );
                    
                    // Build options.
                    foreach ($status_options as $option){

                        // A source may already be saved. 
                        if($option == $status){
                            $selected_attribute = 'selected';
                        }else{
                            $selected_attribute = '';
                        }

                        // Build option.
                        echo '<option value="'.$option.'" '
                        .$selected_attribute.'>'
                        .ucwords($option).'</option>';

                    }
                    ?>

                </select>
            </div>
        </div>
        <hr>

        <?php
        // End hidden fields condition.
        endif;

        // Get the tags, which we use to filter.
        $tags = DataAccess::get_db_rows( 'tags',
            array(), 1, 10000000, 'category'
        );

        // We'll use this variable later..
        $stored_category = '';

        // Start tag markup.
        if(!empty($tags)):
        ?>

        <div id="alert_tags" class="mb-3">
            <h2>Show Alerts Tagged</h2>
            <div class="d-flex flex-wrap">

            <?php
            // Start Loop
            foreach ($tags['content'] as $tag):
                
                // We need to start by ending the previous category's
                // div if it exists.
                if(($stored_category !== '') && ($stored_category !== $tag->category))
                    echo '</div>';

                // Start a new section for every new category.
                if($stored_category !== $tag->category)
                    echo '<div class="pe-3 mb-3"><h3 class="fs-4 text-muted">'.$tag->category.'</h3>';
                
            ?>

            <div class="form-check">
                <input 
                    class="form-check-input" 
                    type="checkbox" 
                    id="<?php echo $tag->slug;?>" 
                    name="<?php echo $tag->slug;?>"
                    
                    <?php
                    // Conditionally show selected tag.
                    if(!empty($dynamic_meta))
                        if(in_array($tag->slug, $dynamic_meta))
                            echo 'checked';
                    ?>

                >
                <label class="form-check-label" for="<?php echo $tag->slug;?>">
                    <?php echo $tag->title;?>
                </label>
            </div>

            <?php
            // Store the category so we can group by category.
            $stored_category = $tag->category;
                
            // End loop.
            endforeach;
            ?>

            </div>
        </div>
        <hr>
        
        <?php
        // End tag markup.
        endif;
        ?>

        <div>

            <?php
            // New reports can't be deleted.
            if(!empty($name))
                echo '<a href="actions/delete_report.php?report='.$name.'" class="btn btn-outline-danger">Delete Report</a>';
            ?>

            <input type="hidden" name="name" value="<?php echo $name;?>">
            <button type="submit" class="btn btn-primary" id="saveButton">Save Report</button>
        </div>
    </form>
</section>

<script>

// Change report title text as you type.
const reportName = document.getElementById('reportName');
const reportNameInput = document.getElementById('reportNameInput');
const changeReportName = function(e) {
    reportName.innerHTML = e.target.value;
}
reportNameInput.addEventListener('input', changeReportName);
reportNameInput.addEventListener('propertychange', changeReportName);

</script>