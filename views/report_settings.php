<?php
/**************!!EQUALIFY IS FOR EVERYONE!!***************
 * This document composes the report settings view.
 * 
 * As always, we must remember that every function should 
 * be designed to be as efficient as possible so that 
 * Equalify works for everyone.
**********************************************************/

// Let's setup the variables that we're going to be using
// in this document.
$name   = '';
$title = 'Untitled';
$status = '';
$type   = '';

// We use this view to customize reports if a id is 
// provided, otherwise we create a new report.
if(!empty($_GET['report'])){
    $name = $_GET['report'];
    
    // Let's load in predefined variables for the report.
    $existing_report = unserialize(
        DataAccess::get_meta_value($name)
    );

    // Let's reformat the meta so we can use it in a
    // more understandable format.
    foreach($existing_report as $report) {
        if($report['name'] == 'title') 
            $title = $report['value'];
        if($report['name'] == 'type') 
            $type = $report['value'];
        if($report['name'] == 'status') 
            $status = $report['value'];
    }

}
?>

<section>
    <div class="mb-3 pb-4 border-bottom">
        <h1>
            <?php
            // Lets add helper text, depending on if we're
            // editing the report.
            if(!empty($name)){
                echo 'Editing ';
            }else{
                echo 'New ';
            }
            ?>
            
            "<span id="reportName"><?php echo $title;?></span>" Report
        </h1>
    </div>
    <form action="actions/save_report.php" method="post">
        <div class="mb-3">
            <label for="reportTitleInput" class="form-label fw-semibold">Report Name</label>
            <input type="text" id="reportNameInput" class="form-control" value="<?php echo $title;?>" name="title" required>
        </div>
        <hr>
        <div class="mb-3 row">
            <div class="col">
                <label for="statusSelect" class="form-label fw-semibold">Status</label>
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
            <div class="col">
                <label for="typeSelect" class="form-label fw-semibold">Alert Type</label>
                <select id="typeSelect" class="form-select" name="type">
                    <option value="">Any</option>
                    
                    <?php 
                    // Set types as array so we can simplify the logic
                    // when building the option html.
                    $type_options = array(
                        'error', 'warning', 'notice'
                    );
                
                    // Build options.
                    foreach ($type_options as $option){

                        // A type may already be saved. 
                        if($option == $type){
                            $selected_attribute = 'selected';
                        }else{
                            $selected_attribute = '';
                        }
                        
                        // Build option
                        echo '<option value="'.$option.'" '
                        .$selected_attribute.' >'
                        .ucwords($option).'</option>';

                    }
                    ?>
            
                </select>
            </div>
        </div>

        <?php
        // Get the tags, which we use to filter.
        $tags = DataAccess::get_db_rows( 'tags',
            array(), 1, 10000000, 'category'
        );

        // We'll use this variable later..
        $stored_category = '';

        // Start tag markup.
        if(!empty($tags)):
        ?>

        <hr>
        <div id="alert_tags" class="mb-3">
            <h2>Filter by Tags</h2>
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
                <input class="form-check-input" type="checkbox" value="" id="<?php echo $tag->slug;?>">
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

            <button type="submit" class="btn btn-primary" id="saveButton">Save Report</button>
            <input type="hidden" name="name" value="<?php echo $name;?>">
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