<?php
/*
Plugin Name: WordPress Roadmap
Version: 0.1
Plugin URI: http://dev7studios.com/portfolio/wp-roadmap
Description: Create dynamic roadmaps in WordPress which show the status of your product development.
Author: Gilbert Pellegrom
Author URI: http://dev7studios.com/
*/

$pluginurl = plugin_dir_url(__FILE__);
if ( preg_match( '/^https/', $pluginurl ) && !preg_match( '/^https/', get_bloginfo('url') ) )
	$pluginurl = preg_replace( '/^https/', 'http', $pluginurl );
    
define( 'WPRDMAP_FRONT_URL', $pluginurl );
define( 'WPRDMAP_URL', plugin_dir_url(__FILE__) );
define( 'WPRDMAP_PATH', plugin_dir_path(__FILE__) );
define( 'WPRDMAP_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPRDMAP_VERSION', '0.1' );


class WPRoadMap {
    
    // For PHP v4
    function WPRoadMap() 
    {	
        $this->__construct();
	}
    
	function __construct() 
    {	
        add_action('init', array(&$this, 'init'));
        add_action('admin_init', array(&$this, 'add_meta_boxes'));
        add_action('wp_ajax_roadmap_get_item', array(&$this, 'roadmap_get_item'));
        add_action('wp_ajax_roadmap_add_item', array(&$this, 'roadmap_add_item'));
        add_action('wp_ajax_roadmap_edit_item', array(&$this, 'roadmap_edit_item'));
        add_action('wp_ajax_roadmap_delete_item', array(&$this, 'roadmap_delete_item'));
        add_action('template_redirect', array(&$this, 'template_redirect'));
        
        add_filter('post_updated_messages', array(&$this, 'updated_messages'));
	}
    
    function init()
    {
        $args = array(
            'labels' => $this->post_type_labels( 'Roadmap' ),
            'public' => true,
            'show_ui' => true, 
            'show_in_menu' => true, 
            'capability_type' => 'post',
            'has_archive' => true, 
            'supports' => array('title')
        ); 
        register_post_type( 'roadmap', $args );
    }
  
    function updated_messages( $messages ) 
    {
        global $post, $post_ID;

        $messages['roadmap'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => sprintf( __('Roadmap updated. <a href="%s">View roadmap</a>'), esc_url( get_permalink($post_ID) ) ),
            2 => __('Custom field updated.'),
            3 => __('Custom field deleted.'),
            4 => __('Roadmap updated.'),
            /* translators: %s: date and time of the revision */
            5 => isset($_GET['revision']) ? sprintf( __('Roadmap restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => sprintf( __('Roadmap published. <a href="%s">View roadmap</a>'), esc_url( get_permalink($post_ID) ) ),
            7 => __('Roadmap saved.'),
            8 => sprintf( __('Roadmap submitted. <a target="_blank" href="%s">Preview roadmap</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
            9 => sprintf( __('Roadmap scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview roadmap</a>'),
                // translators: Publish box date format, see http://php.net/date
                date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
            10 => sprintf( __('Roadmap draft updated. <a target="_blank" href="%s">Preview roadmap</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
        );

        return $messages;
    }
    
    function post_type_labels( $singular, $plural = '' )
    {
        if( $plural == '') $plural = $singular .'s';

        return array(
            'name' => _x( $plural, 'post type general name' ),
            'singular_name' => _x( $singular, 'post type singular name' ),
            'add_new' => __( 'Add New' ),
            'add_new_item' => __( 'Add New '. $singular ),
            'edit_item' => __( 'Edit '. $singular ),
            'new_item' => __( 'New '. $singular ),
            'view_item' => __( 'View '. $singular ),
            'search_items' => __( 'Search '. $plural ),
            'not_found' =>  __( 'No '. $plural .' found' ),
            'not_found_in_trash' => __( 'No '. $plural .' found in Trash' ), 
            'parent_item_colon' => ''
        );
    }

    function add_meta_boxes()
    {
        add_meta_box( 'roadmap_items', 'Roadmap Items', array(&$this, 'roadmap_inner_items'), 'roadmap', 'normal' );
        add_meta_box( 'roadmap_edit_item', 'Edit Item', array(&$this, 'roadmap_inner_edit_item'), 'roadmap', 'normal' );
        add_meta_box( 'roadmap_add_item', 'Add Item', array(&$this, 'roadmap_inner_add_item'), 'roadmap', 'normal' );
        
        wp_enqueue_style('roadmap_admin_css', WPRDMAP_URL .'style.css');
    }
    
    function roadmap_inner_items()
    {
        global $post;
                
        $items = get_post_meta( $post->ID, 'roadmap_items', true );
        if( !$items ) $items = array();
        ?>
        <table class="wp-list-table widefat" cellspacing="0">
            <thead>
                <tr><th>Title</th><th>Description</th><th>Start Date</th><th>Due Date</th><th>Progress</th><th>People Involved</th><th></th></tr>
            </thead>
            <tbody>
                <?php if(empty($items)){ ?>
                <tr><td colspan="7">No Items...</td></tr>
                <?php } ?>
                <?php $i = 0; foreach($items as $item){ ?>
                <tr>
                    <td><strong><?php echo $item['title']; ?></strong></td>
                    <td><?php echo $item['description']; ?></td>
                    <td><?php echo $item['startDate']; ?></td>
                    <td><?php echo $item['dueDate']; ?></td>
                    <td><?php
                    if($item['progress'] == '0') echo 'Proposed';
                    if($item['progress'] == '1') echo 'Started';
                    if($item['progress'] == '2') echo '10%';
                    if($item['progress'] == '3') echo '20%';
                    if($item['progress'] == '4') echo '30%';
                    if($item['progress'] == '5') echo '40%';
                    if($item['progress'] == '6') echo '50%';
                    if($item['progress'] == '7') echo '60%';
                    if($item['progress'] == '8') echo '70%';
                    if($item['progress'] == '9') echo '80%';
                    if($item['progress'] == '10') echo '90%';
                    if($item['progress'] == '11') echo 'Ready';
                    if($item['progress'] == '12') echo 'Launched';
                    if($item['progress'] == '13') echo 'Postponed';
                    if($item['progress'] == '14') echo 'Stopped';
                    ?></td>
                    <td><?php 
                    if(count($item['people']) == 0) echo 'No people';
                    if(count($item['people']) == 1){ 
                        $user_info = get_userdata($item['people'][0]);
                        echo $user_info->display_name;
                    }
                    if(count($item['people']) > 1){ 
                        $names = array();
                        foreach($item['people'] as $person){
                            $user_info = get_userdata($person);
                            $names[] = $user_info->display_name;
                        }
                        $title = $this->comma_list_ended($names);
                        echo '<span title="'. $title .'">'. count($item['people']) .' people</span>';
                    }
                    ?></td>
                    <td><a href="#" rel="<?php echo $i; ?>" class="edit">Edit</a> <a href="#" rel="<?php echo $i; ?>" class="delete">Delete</a></td>
                </tr>
                <?php $i++; } ?>
            </tbody>
        </table>
        <script type="text/javascript">
        jQuery(document).ready(function($){ 
            
            $('#roadmap_items .delete').click(function(){
                if(confirm('Are you sure you want to delete this item?')){
                    var link = $(this);
                    $('.edit', link.parent()).hide();
                    link.text('Deleting...');
                    
                    $.post('<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php', 
                        { action:'roadmap_delete_item', item_id:link.attr('rel'), post_id:'<?php echo $post->ID; ?>',
                          nonce:'<?php echo wp_create_nonce(WPRDMAP_BASENAME); ?>' }, 
                        function(data){
                            location.reload(true);
                        }
                    );
                }
                
                return false;
            });
            
            $('#roadmap_items .edit').click(function(){
                var link = $(this);
                $('#roadmap_edit_item').show().removeClass('closed');
                $('#roadmap_edit_item').data('item_id', $(this).attr('rel'));
                $('#roadmap_edit_item .inside').hide();
                
                $.post('<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php', 
                    { action:'roadmap_get_item', item_id:link.attr('rel'), post_id:'<?php echo $post->ID; ?>',
                      nonce:'<?php echo wp_create_nonce(WPRDMAP_BASENAME); ?>' }, 
                    function(data){
                        $('#roadmap_edit_item .inside').slideDown();
                        $('#roadmap_edit_item #edit_title').val(data.title);
                        $('#roadmap_edit_item #edit_description').val(data.description);
                        $('#roadmap_edit_item #edit_startDate').val(data.startDate);
                        $('#roadmap_edit_item #edit_dueDate').val(data.dueDate);
                        $('#roadmap_edit_item #edit_progress option[value="'+ data.progress +'"]').attr('selected', 'selected');
                        $('#roadmap_edit_item input[type="checkbox"]').attr('checked', '');
                        for(var i in data.people)
                        {
                            $('#roadmap_edit_item #edit_people_'+ data.people[i]).attr('checked', 'checked');
                        }
                    },
                'json');
                
                return false;
            });
            
        });
        </script>
        <?php 
    }
    
    function roadmap_inner_add_item()
    {
        wp_nonce_field( WPRDMAP_BASENAME, 'roadmap_nonce' );
        
        if( !isset($_GET['action']) || $_GET['action'] != 'edit'){
            echo '<p>Please save this roadmap before you can add any items.</p>';
            return;
        }
        ?>
        <ul>
            <li class="first title">
                <label for="title">Title</label>
                <div class="input">
                    <input type="text" id="title" name="title" />
                    <p class="info">The name of your new roadmap item, usually the name of a feature.</p>
                </div>
                <div class="clear"></div>
            </li>
            <li class="desc">
                <label for="description">Description</label>
                <div class="input">
                    <textarea id="description" name="description" rows="5"></textarea>
                </div>
                <div class="clear"></div>
            </li>
            <li class="dates">
                <label for="startDate">Dates</label>
                <div class="input">
                    <div class="col">
                        <label class="small" for="startDate">Start</label>
                        <input type="text" id="startDate" name="startDate" value="No Start Date" />
                    </div>
                    <div class="col">
                        <label class="small" for="dueDate">Due</label>
                        <input type="text" id="dueDate" name="dueDate" value="No Due Date" />
                    </div>
                </div>
                <div class="clear"></div>
            </li>
            <li class="progress">
                <label for="progress">Progress</label>
                <div class="input">
                    <select id="progress" name="progress">
                        <option value="0">Proposed</option>
                        <option value="1" selected="selected">Started</option>
                        <option value="2">10%</option>
                        <option value="3">20%</option>
                        <option value="4">30%</option>
                        <option value="5">40%</option>
                        <option value="6">50%</option>
                        <option value="7">60%</option>
                        <option value="8">70%</option>
                        <option value="9">80%</option>
                        <option value="10">90%</option>
                        <option value="11">Ready</option>
                        <option value="12">Launched</option>
                        <option value="13">Postponed</option>
                        <option value="14">Stopped</option>
                    </select>
                </div>
                <div class="clear"></div>
            </li>
            <li class="people-involved">
                <label>People Involved</label>
                <div class="input">
                    <ul>
                        <?php 
                        $blogusers = get_users('orderby=nicename');
                        foreach ($blogusers as $user) {
                            ?>
                            <li>
                                <input type="hidden" name="people_<?php echo $user->ID; ?>" value="off">
                                <input type="checkbox" class="checkbox" id="people_<?php echo $user->ID; ?>" name="people_<?php echo $user->ID; ?>" value="on">
                                <label for="people_<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></label>
                            </li>
                            <?php 
                        }
                        ?>
                    </ul>
                    <p class="info">Select the people who will work on this roadmap.</p>
                </div>
                <div class="clear"></div>
            </li>
            <li class="last">
                <input type="button" class="button-secondary" name="add_item" value="Add Item">
                <div class="clear"></div>
            </li>
        </ul>
        <script type="text/javascript">
        jQuery(document).ready(function($){ 
            
            $('#roadmap_add_item .last input').click(function(e){
                var button = $(this);
                button.val('Adding...');
                $.post('<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php', 
                    { action:'roadmap_add_item', data:$('#post').serialize(),
                      nonce:'<?php echo wp_create_nonce(WPRDMAP_BASENAME); ?>' }, 
                    function(data){
                        location.reload(true);
                    }
                );
            });
            
        });
        </script>
        <?php 
    }
    
    function roadmap_inner_edit_item()
    {
        wp_nonce_field( WPRDMAP_BASENAME, 'roadmap_nonce' );
        ?>
        <ul>
            <li class="first title">
                <label for="edit_title">Title</label>
                <div class="input">
                    <input type="text" id="edit_title" name="edit_title" />
                    <p class="info">The name of your new roadmap item, usually the name of a feature.</p>
                </div>
                <div class="clear"></div>
            </li>
            <li class="desc">
                <label for="edit_description">Description</label>
                <div class="input">
                    <textarea id="edit_description" name="edit_description" rows="5"></textarea>
                </div>
                <div class="clear"></div>
            </li>
            <li class="dates">
                <label for="edit_startDate">Dates</label>
                <div class="input">
                    <div class="col">
                        <label class="small" for="edit_startDate">Start</label>
                        <input type="text" id="edit_startDate" name="edit_startDate" value="No Start Date" />
                    </div>
                    <div class="col">
                        <label class="small" for="edit_dueDate">Due</label>
                        <input type="text" id="edit_dueDate" name="edit_dueDate" value="No Due Date" />
                    </div>
                </div>
                <div class="clear"></div>
            </li>
            <li class="progress">
                <label for="edit_progress">Progress</label>
                <div class="input">
                    <select id="edit_progress" name="edit_progress">
                        <option value="0">Proposed</option>
                        <option value="1" selected="selected">Started</option>
                        <option value="2">10%</option>
                        <option value="3">20%</option>
                        <option value="4">30%</option>
                        <option value="5">40%</option>
                        <option value="6">50%</option>
                        <option value="7">60%</option>
                        <option value="8">70%</option>
                        <option value="9">80%</option>
                        <option value="10">90%</option>
                        <option value="11">Ready</option>
                        <option value="12">Launched</option>
                        <option value="13">Postponed</option>
                        <option value="14">Stopped</option>
                    </select>
                </div>
                <div class="clear"></div>
            </li>
            <li class="people-involved">
                <label>People Involved</label>
                <div class="input">
                    <ul>
                        <?php 
                        $blogusers = get_users('orderby=nicename');
                        foreach ($blogusers as $user) {
                            ?>
                            <li>
                                <input type="hidden" name="edit_people_<?php echo $user->ID; ?>" value="off">
                                <input type="checkbox" class="checkbox" id="edit_people_<?php echo $user->ID; ?>" name="edit_people_<?php echo $user->ID; ?>" value="on">
                                <label for="edit_people_<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></label>
                            </li>
                            <?php 
                        }
                        ?>
                    </ul>
                    <p class="info">Select the people who will work on this roadmap.</p>
                </div>
                <div class="clear"></div>
            </li>
            <li class="last">
                <input type="button" class="button-secondary" name="edit_item" value="Edit Item">
                <input type="button" class="button-secondary" name="edit_item_cancel" value="Cancel" style="margin:0;">
                <div class="clear"></div>
            </li>
        </ul>
        <script type="text/javascript">
        jQuery(document).ready(function($){ 
        
            $('#roadmap_edit_item input[name="edit_item"]').click(function(e){
                var button = $(this);
                button.val('Saving...');
                
                var item_id = $('#roadmap_edit_item').data('item_id');
                $.post('<?php echo get_bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php', 
                    { action:'roadmap_edit_item', data:$('#post').serialize(), item_id:item_id,
                      nonce:'<?php echo wp_create_nonce(WPRDMAP_BASENAME); ?>' }, 
                    function(data){
                        location.reload(true);
                    }
                );
            });
            
            $('#roadmap_edit_item input[name="edit_item_cancel"]').click(function(e){
                $('#roadmap_edit_item').fadeOut(300);
            });
            
        });
        </script>
        <?php 
    }
    
    function roadmap_get_item() 
    {
        if ( !wp_verify_nonce( $_POST['nonce'], WPRDMAP_BASENAME ) )
            return 0;
        
        $item_id = $_POST['item_id'];
        $items = get_post_meta( $_POST['post_id'], 'roadmap_items', true );

        echo json_encode($items[$item_id]);
        die;
    }
    
    function roadmap_add_item() 
    {
        if ( !wp_verify_nonce( $_POST['nonce'], WPRDMAP_BASENAME ) )
            return 0;
        
        $data = $this->jquery_unserialize($_POST['data']);
        $items = get_post_meta( $data['post_ID'], 'roadmap_items', true );
        $people = array();
        
        // Find people
        while (list($key, $val) = each($data)) {
            if(strncmp($key, 'people_', strlen('people_')) == 0 && $val == 'on'){
                $user_id = str_replace('people_', '', $key);
                $people[] = $user_id;
            }
        }
        
        $new_item = array(
            'title' => $data['title'],
            'description' => $data['description'],
            'startDate' => $data['startDate'],
            'dueDate' => $data['dueDate'],
            'progress' => $data['progress'],
            'people' => $people
        );
        $items[] = $new_item;
        
        $items = array_values($items); // Reset keys
        update_post_meta( $data['post_ID'], 'roadmap_items', $items );
        
        echo json_encode($new_item);
        die;
    }
    
    function roadmap_edit_item() 
    {
        if ( !wp_verify_nonce( $_POST['nonce'], WPRDMAP_BASENAME ) )
            return 0;
        
        $data = $this->jquery_unserialize($_POST['data']);
        $items = get_post_meta( $data['post_ID'], 'roadmap_items', true );
        $people = array();
        
        // Find people
        while (list($key, $val) = each($data)) {
            if(strncmp($key, 'edit_people_', strlen('edit_people_')) == 0 && $val == 'on'){
                $user_id = str_replace('edit_people_', '', $key);
                $people[] = $user_id;
            }
        }
        
        $edit_item = array(
            'id' => $_POST['item_id'],
            'title' => $data['edit_title'],
            'description' => $data['edit_description'],
            'startDate' => $data['edit_startDate'],
            'dueDate' => $data['edit_dueDate'],
            'progress' => $data['edit_progress'],
            'people' => $people
        );
        $items[$_POST['item_id']] = $edit_item;
        
        $items = array_values($items); // Reset keys
        update_post_meta( $data['post_ID'], 'roadmap_items', $items );
        
        echo json_encode($edit_item);
        die;
    }
    
    function roadmap_delete_item() 
    {
        if ( !wp_verify_nonce( $_POST['nonce'], WPRDMAP_BASENAME ) )
            return 0;
        
        $item_id = $_POST['item_id'];
        $items = get_post_meta( $_POST['post_id'], 'roadmap_items', true );

        unset($items[$item_id]);
        
        $items = array_values($items); // Reset keys
        update_post_meta( $_POST['post_id'], 'roadmap_items', $items );
        
        echo json_encode($items);
        die;
    }
    
    function template_redirect()
    {
        $post_type = get_query_var('post_type');
        
        if( is_single() && $post_type == 'roadmap' && !file_exists(TEMPLATEPATH .'/single-roadmap.php') ){
            include(WPRDMAP_PATH .'single-roadmap.php');
            die();
        }
    }
    
    function jquery_unserialize( $string )
    {
        $output = array();
        $perfs = explode('&', $string);
        foreach($perfs as $perf) {
            $perf_key_values = explode('=', $perf);
            $key = urldecode($perf_key_values[0]);
            $values = urldecode($perf_key_values[1]);
            $output[$key] = $values;
        }
        return $output;
    }
    
    function comma_list_ended( $arr, $last = 'and' ) 
    {
        return preg_replace("/,([^,]*)$/", " {$last} $1", join($arr,', '));
    }
    
    function log( $message ) {
        if( WP_DEBUG === true ){
            if( is_array( $message ) || is_object( $message ) ){
                error_log( print_r( $message, true ) );
            } else {
                error_log( $message );
            }
        }
    }

}
$wp_roadmap = new WPRoadMap();


/**
 * Theme Functions
 */
function wp_roadmap_items()
{
    global $post;
            
    $items = get_wp_roadmap_items();
    $i = 0;
    foreach($items as $item){
        ?>
        <div id="roadmap-item-<?php echo $i; ?>" class="roadmap-item">
            <h3 class="roadmap-item-title"><?php echo $item['title']; ?></h3>
            <p class="roadmap-item-description"><?php echo $item['description']; ?></p>
            <ul class="roadmap-item-meta">
                <li><span>Start Date:</span> <?php echo $item['startDate']; ?></li>
                <li><span>Due Date:</span> <?php echo $item['dueDate']; ?></li>
                <li><span>Progress:</span> <?php
                if($item['progress'] == '0') echo 'Proposed';
                if($item['progress'] == '1') echo 'Started';
                if($item['progress'] == '2') echo '10%';
                if($item['progress'] == '3') echo '20%';
                if($item['progress'] == '4') echo '30%';
                if($item['progress'] == '5') echo '40%';
                if($item['progress'] == '6') echo '50%';
                if($item['progress'] == '7') echo '60%';
                if($item['progress'] == '8') echo '70%';
                if($item['progress'] == '9') echo '80%';
                if($item['progress'] == '10') echo '90%';
                if($item['progress'] == '11') echo 'Ready';
                if($item['progress'] == '12') echo 'Launched';
                if($item['progress'] == '13') echo 'Postponed';
                if($item['progress'] == '14') echo 'Stopped';
                ?></li>
            </ul>
            <p class="roadmap-item-people-label">People Involved:</p>
            <ul class="roadmap-item-people">
            <?php foreach($item['people'] as $person){
                $user_info = get_userdata($person); ?>
                <li><?php echo $user_info->display_name; ?></li>
            <?php } ?>
            </ul>
        </div>
        <?php
        $i++;
    }
}

function get_wp_roadmap_items()
{
    global $post;
    
    $items = get_post_meta( $post->ID, 'roadmap_items', true );
    if( !$items ) $items = array();
    
    return $items;
}

?>