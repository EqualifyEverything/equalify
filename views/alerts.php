<section>
    <h1 class="mb-3 pb-4 border-bottom">
        All Alerts
    </h1>
    <ul class="nav nav-tabs mb-3">

        <?php
        // Setup alert tabs.
        $alert_tabs = DataAccess::get_meta_value('alert_tabs');
        if(empty($alert_tabs)){

            // By default, we include an "Default Alerts" tab.
            $alert_tabs = array(
                'current_tab' => 1,
                'tabs'  => array(
                    1 => array(
                        'id'        => 1,
                        'name'      => 'My Alerts',
                        'filters'   => array()
                    )
                )
            );
            DataAccess::add_meta('alert_tabs', $alert_tabs);

        }else{

            // We need to unserialze MySQL data if that data 
            // exists.
            $alert_tabs = unserialize($alert_tabs);

        }

        // Setup variables.
        $tabs = $alert_tabs['tabs'];
        $current_tab = $alert_tabs['current_tab'];
        $current_tab_data = $tabs[$current_tab];

        // Start tabs Loop
        if(!empty($tabs)): foreach ($tabs as $tab):
        ?>

        <li class="nav-item">
        
            <a 
                class="nav-link <?php if($current_tab == $tab['id']) echo 'active';?>" 
                aria-current="page" 
                href="actions/switch_alert_tab.php?alert_tab=<?php echo $tab['id'];?>"
            >
            
                <?php echo $tab['name']; ?>

                <span class="
                    ms-1
                    badge 
                    bg-<?php if($current_tab == $tab['id']){ echo 'primary'; }else{ echo 'secondary'; }?> 
                    rounded
                ">

                <?php
                //  Alert counter
                $alert_count = DataAccess::count_alerts($tab['filters']);
                echo $alert_count;
                ?>

                </span>
            </a>
        </li>

        <?php
        // End tabs loop
        endforeach; 
    endif;
        ?>

        <li class="nav-item <?php if(empty($tabs)){ echo 'mb-3';}else{ echo 'ms-2'; }?>">
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#alertOptions" id="addTabButton">+ Add Tab</button>
        </li>
    </ul>
    <div class="row row-cols-lg-auto g-3 align-items-center mb-3">
        <div class="col-12">
            <div class="input-group input-group-sm">
                <input type="text" class="form-control" placeholder="Keyword or Website.." aria-label="Search Term" aria-describedby="basic-addon1">
                <button class="btn btn-outline-secondary" type="button">Search</button>
            </div>
        </div>
        <div class="col-12">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#alertOptions" id="editTabButton">
                Tab Filters & Settings
            </button>

            <?php 
            // Alert tabs Modal
            the_alert_tab_options($current_tab_data);
            ?>

        </div>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Time</th>
                <th scope="col">Source</th>
                <th scope="col">Type</th>
                <th scope="col">Message</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>

        <?php
        // Begin Alerts
        $filters = $current_tab_data['filters'];
        $alerts = DataAccess::get_alerts($filters, get_current_page_number());
        $alerts_content = $alerts['content'];
        if(count($alerts_content) > 0 ): foreach($alerts_content as $alert):    
        ?>

        <tr>
            <td><?php echo $alert->time;?></td>
            <td><?php echo ucwords($alert->source);?></td>
            <td><?php echo ucwords($alert->type);?></td>
            <td><?php echo covert_code_shortcode($alert->message);?></td>
            <td style="min-width: 200px;">
                <a href="actions/delete_alert.php?id=<?php echo $alert->id;?>" class="btn btn-outline-secondary btn-sm">
                    Dismiss
                </a>
            </td>
        </tr>

        <?php 
        // Fallback
        endforeach; else:
        ?>

        <tr>
            <td colspan="4">No alerts found.</td>
        </tr>

        <?php 
        // End Alerts
        endif;
        ?>

    </table>

    <?php
    // The pagination
    the_pagination($alerts['total_pages']);
    ?>

</section>