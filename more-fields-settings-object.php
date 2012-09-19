<?php

class more_fields_admin extends more_plugins_admin_object_sputnik_8 {

  /**
   * add hooks
   */
  function add_actions() {
    add_action('admin_head-settings_page_' . $this->slug, array(&$this, 'js_for_fields'));	
    add_action('admin_head-post-new.php', array(&$this, 'add_css'));	
    add_action('admin_head-post.php', array(&$this, 'add_css'));	
    add_action('admin_head-page-new.php', array(&$this, 'add_css'));	
    add_action('admin_head-page.php', array(&$this, 'add_css'));
    
    // Save the meta keys
    add_action('save_post', array(&$this, 'save_post_meta'), 11, 2);
    add_action('save_page', array(&$this, 'save_post_meta'), 11, 2);
    
    // Add our rewrite rules
    add_filter('rewrite_rules_array', array(&$this, 'rewrite_rules_array'), 10, 1);
    
    add_action('wp_ajax_more_fields_file_list', array(&$this, 'axaj_file_list'));
    add_action('wp_ajax_more_fields_file_list_thumb', array(&$this, 'axaj_file_list_thumb'));
    
    add_filter('more_fields_write_css', 'more_fields_write_css');
    add_filter('more_fields_write_js', 'more_fields_write_js');
    add_action('admin_print_styles-post.php', array(&$this, 'enqueue_scripts'), 1, 1);
    add_action('admin_print_styles-post-new.php', array(&$this, 'enqueue_scripts'), 1, 1);
    
  }//end function add_actions
  
  /**
   *
   */
  function enqueue_scripts ($a) {
    wp_enqueue_script(array('jquery', 'editor', 'thickbox', 'media-upload'));
    wp_enqueue_style('thickbox');
    
  }//end function enqueue_scripts
  
  /**
   *
   */
  function after_settings_init() {
    global $wp_rewrite;
    
    // Flush the rewrite rules if we're saving
    if ($this->action == 'save') {
      $wp_rewrite->flush_rules();
    }
  }//end function after_settings_init
    
  /**
   *
   */
  function axaj_file_list_thumb() {
    $a['url'] = wp_get_attachment_thumb_url(esc_attr($_POST['post_id']));
    $a['thiss'] = esc_attr($_POST['thiss']);
    echo json_encode($a);
    die();   	
  }//end function axaj_file_list_thumb
  
  /**
   *
   */
  function axaj_file_list() {
    $post_id = esc_attr($_POST['post_id']);
    $attachments['data'] = get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC') );
    $attachments['clicked'] = esc_attr($_POST['clicked']);
    echo json_encode($attachments);
    die();
  }//end function axaj_file_list
	
  /**
   *
   */
  function add_css() {
    $css = apply_filters('more_fields_write_css', '');
    $js = apply_filters('more_fields_write_js', '');
    ?>
    <script type="text/javascript">
    //<![CDATA[
     jQuery(document).ready(function($){
      $(".mf_update_on_edit").change(function() {
       var val = $(this).val();
       $(this).next().html(val);
      });
     });
    //]]>
    </script>
    <?php echo $js; ?>
    <style type="text/css">
     <?php echo $css; ?>
    </style>
    <?php
  }//end function add_css
	
 /**
  *
  */
  function js_for_fields () {
    global $more_fields;
    $js = array();
    foreach ($more_fields->field_types as $key => $field) {
     if (array_key_exists('values', $field)) $js[] = "(val == '$key')";
    }
    $jsq = implode(' || ', $js);
    ?>
    <script type="text/javascript">
    //<![CDATA[
     jQuery(document).ready(function($){
      var val = $("input[name=field_type]").val();
      if (<?php echo $jsq; ?>) $('input[name=values]').removeAttr("disabled");
      else $('input[name=values]').attr("disabled", true);			
      $("input[name=field_type]").change(function() {
       var val = $(this).val();
       if (<?php echo $jsq; ?>) $('input[name=values]').removeAttr("disabled");
       else $('input[name=values]').attr("disabled", true);
      });
      // Update field on changes
      $(".mf_update_on_edit").change(function() {
       alert('suerk');
      });
     });
    //]]>
    </script>
    <?php
  
  }//end function js_for_fields
	
  /**
   *
   *
   */
  function get_field_types_select() {
    global $more_fields;
    $ret = array();
    foreach ($more_fields->field_types as $key => $type) {
      $ret[$key] = $type['label'];
    }
    unset($type);
    return $ret;
  }//end function get_field_types_select
	
  /**
   *
   *
   */
  function get_field_types_comments() {
    global $more_fields;
    $ret = array();
    foreach ($more_fields->field_types as $key => $type) {
      $ret[$key] = $type['comment'];
    }
    unset($type);
    return $ret;
  
  }//end function get_field_types_comments
	
  /**
   *
   *
   */
  function validate_sumbission() {
    if ($this->navigation == 'boxes') {
      if ($this->action == 'save') {
        if (!$_POST) return true;
        // These are the field we are saving.
        $this->fields = array(
         'var' => array('label', 'position'),
         'array' => array('fields', 'more_access_cap', 'post_types'),
        );
        // Validate
        if (!($name = esc_attr($_POST['label']))) {
          $this->set_navigation('box');
          return $this->error(__('Your box needs a title!', 'more-plugins'));
        }
        $name = sanitize_title($name);
        //$this->action_keys = array($name);
        $_POST['index'] = $name;
      }
    }
    // BOXES
    if ($this->navigation == 'box') {
   
      if ($this->action == 'save') {
        if (!$_POST) return false;
        $this->fields = array(
         'var' => array('label', 'key', 'slug', 'field_type', 'values', 'caption'),
         'array' => array(),
        );
        // Save all level 2 data in 'fields'.
        if (!($name = esc_attr($_POST['label']))) {
          $this->set_navigation('field');
          return $this->error(__('You need a name for the field!', 'more-plugins')); 
        }
        
        if (!esc_attr($_POST['key'])) {
          $this->set_navigation('field');
          return $this->error(__('You need to specify a custom field key for the field!', 'more-plugins')); 
        }
        $name = sanitize_title($name);
        $_POST['index'] = $name;
        
      }
      if ($this->action == 'add') {
        $this->default = array('position' => 'left', 'post_types' => array('post'));
      }
    }
    
    if ($this->navigation == 'field') {
      if ($this->action == 'add') {
        $this->default = array('field_type' => 'text');
      }
    }
    return true;
  }//end function validate_submission
 
 
  function load_objects () {
    global $more_fields;
    $this->data = $more_fields->load_objects();
    
    return $this->data;
  }//end function load_objects

  /**
   *	save_post_meta()
   */
  function save_post_meta($new_post_id, $post) {
	   global $wpdb, $post, $more_fields;
    
    // Ignore autosaves, ignore quick saves
    if (@constant( 'DOING_AUTOSAVE')) return $post;
    if (!$_POST) return $post;
    if (!in_array($_POST['action'], array('editpost', 'post'))) return $post;
    
    $post_id = esc_attr($_POST['post_ID']);
    if (!$post_id) $post_id = $new_post_id;
    if (!$post_id) return $post;
    
    // Make sure we're saving the correct version
    if ( $p = wp_is_post_revision($post_id)) $post_id = $p;
		
    $boxes = $more_fields->get_objects(array('_plugin_saved', '_plugin'));

    // Watch me being very defensive.
    // foreach ($ids as $post_id) {
    foreach ($boxes as $box) {
      foreach((array) $box['fields'] as $field) {
        $key = $field['key'];
        $post_key = sanitize_title($key);
        $meta_data = get_post_custom($post_id);
        // Ok, must do this since an unticked checkbox does not appear in $_POST;
        if (array_key_exists($post_key, (array) $_POST) || array_key_exists($key, (array) $meta_data)) {
          $value = stripslashes($_POST[$post_key]);
          $stored_value = (array_key_exists($key, $meta_data)) ? $meta_data[$key][0] : '';
          
          if ($value || get_post_meta($post_id, $key, true) ) {
            // File lists may send no value, but not intentionally
            $not_file_list = ($field['field_type'] != 'file-list-thumb');
            $values_dont_match = ($value != get_post_meta($post_id, $key, true));
            
            /**
             * handle the situation when we want to clear the image selection:
             * file thumb list may send '' as a value, which we do
             * not want to update.  set the attachment id to '' if we receive
             * a '-1'
             */
            if($field['field_type'] == 'file-thumb-list' && $value == '-1') {
              if( !add_post_meta($post_id, $key, '', true) ) {
                update_post_meta($post_id, $key, '');
              }
            }//end handling clear image picker selection
            if ($values_dont_match)  {
              if ($field['field_type'] == 'wysiwyg') { 
                $value = wpautop($value);
              }
              if($value || $not_file_list) {
                if (!add_post_meta($post_id, $key, $value, true)) {
                  update_post_meta($post_id, $key, $value);
                }
              }
            }//end if $values_dont_match
          }//end if $value || get_post_meta(...) 
        }//end if array_key_exists || array_key_exists
      }//end inner foreach
      unset($field);
    }//end outer foreach
    unset($box);
    return $post;
  }//end function save_post_meta()
	
  /**
   *	@method after_request_handler() Handles cross-functionality between
   *	More Types and More Fields - any changes
   *	made here are reflected in the More Types admin too.
   */
  function after_request_handler() {
    global $more_fields, $more_types_settings;
   
    if ($this->action == 'get_file_list') {
      $post_id = esc_attr($_GET['post_id']);
      $attachments['data'] = get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC') );
      $attachments['clicked'] = esc_attr($_GET['clicked']);
      echo maybe_serialize($attachments);
      exit();
    }
    if ($this->action == 'save') {
      if (is_callable(array($more_types_settings, 'update_from_more_plugin')))
        $more_types_settings->update_from_more_plugin($more_fields, 'post_types', 'boxes');
    }
  }//end function after_request_handler
	
  /**
   *	@method build_box_gut()	This function builds the inside of a box, based
   *	on the field types, as defined in more-fields-field-types.php.
   */
  function build_box_gut($box) {
    global $more_fields;
    do_action('mf_box_head', $box);
    
    foreach ((array) $box['fields'] as $field) {
      if (!($field = apply_filters('mf_field', $field))) continue;
      
      $title = '<label class="mf_label" for="' . $field['key'] . '">' . $field['label'] . ':</label>';
      echo '<div class="mf_field_wrapper mf_field_' . $field['key'] .' ' . $field['field_type'] . '">';
      
      $type = $more_fields->field_types[$field['field_type']];
      if (!$type) return false;
      
      // Parse out the values, including ascending and descending ranges
      $values = array();
      if($field['field_type'] == 'file-list') {
        // Load attachements
        //$values = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', 'orderby' => 'menu_order ASC, ID', 'order' => 'DESC'), ARRAY_A );
        $values[] = '';
        
      } else if($field['field_type'] == 'file-list-thumb') {
        /*
        $values = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', 'post_mime_type' => 'image',  'orderby' => 'menu_order ASC, ID', 'order' => 'DESC'), ARRAY_A );
        
        foreach($values as $id => $value) {
            $values[$id]['item_thumbnail'] = wp_get_attachment_image( $id ); 
        }
        */
        $values[] = '';
      } else {
        $parts = explode(',', $field['values']);
        
        // Add empty option at top for select lists
        
        if ($field['field_type'] == 'select') $values[] = '';
        foreach ((array) $parts as $part) {
          $range = explode(':', $part);
          if (count($range) == 2) {
            if ($range[0] == $range[1]) $values[] = $range[0];
            if ($range[0] < $range[1]) {
              for ($j = $range[0]; $j <= $range[1]; $j++)
                $values[] = $j;
            } else {
              for ($j = $range[0]; $j >= $range[1]; $j--)
                $values[] = $j;
            }
          } else $values[] = $part;
        }
      }
      
      $field['vals'] = $values;
      // Get the closed boxes
      //$post_type = sanitize_title($this->get_type());
      //$hidden = (array) get_user_option("meta-box-hidden_${post_type}", 0, false );
      //$box_is_hidden = (in_array(sanitize_title($box['name']), $hidden));
      
      // Write the field
      if (array_key_exists('html_before', $type)) {
        echo $this->field_type_render($type['html_before'], $field, $box['position']);
      }
      if (empty($values)) {
        echo $this->field_type_render($type['html_item'], $field, $box['position']);
      } else {
        foreach ($values as $v) {
          
          // If there is a range but no item template (e.g. html5 range)
          if (!array_key_exists('html_item', $type)) {
            continue;
          }
          
          if (!array_key_exists('html_selected', $type)) {
            $type['html_selected'] = '';
          }
            
          if( is_array($v) ) {
            echo $this->field_type_render($type['html_item'], $field, $box['position'], $v, $type['html_selected']);
          } else {
            echo $this->field_type_render($type['html_item'], $field, $box['position'], rtrim(ltrim($v)), $type['html_selected']);
          }
        }//end foreach $values as $v
        unset($v);
      }
      
      $actions = array_key_exists('actions', $type) ? $type['actions'] : '';
      if ($actions) {
        foreach ($actions as $action => $args) {
          
          // Render the arguments
          $rendered = array();
          foreach ($args as $arg) {
            $rendered[] = $this->field_type_render($arg, $field, $box['position']);
          }
          unset($arg);
          // Do the action
          if (!count($args)) do_action($action);
          else if (count($args) == 1) do_action($action, $rendered[0]);
          else if (count($args) == 2) do_action($action, $rendered[0], $rendered[1]);
          else if (count($args) == 3) do_action($action, $rendered[0], $rendered[1], $rendered[2]);			
        }
      }
      
      if (array_key_exists('html_after', $type))
        echo $this->field_type_render($type['html_after'], $field, $box['position']);			
      
      // Add caption to field
      // if ($f = html_entity_decode($field['caption'])) echo "<em class='mf_caption'>$f</em>";
      
      echo '</div>';
      do_action('mf_box_foot', $box);
    }//end foreach ((array) $box['fields'] as $field)
    unset($field);
  }//end function build_box_gut
	
  /**
   *	@method field_type_render() Renders the template format in
   *	more-fields-field-types.php.
   */
  function field_type_render ($html, $field, $position, $value_raw = '', $html_selected = '') {
    global $post;
  
    // Search and replace our template tags
    $html = str_replace('%class%', 'mf_' . $field['field_type'], $html);
    $html = str_replace('%key%', sanitize_title($field['key']), $html);
    $html = str_replace('%caption%', '<p class="mf_caption">' . stripslashes($field['caption']) . '</p>', $html);
    $html = str_replace('%title%', $field['label'], $html);
  
    $value_stored = (get_post_meta($post->ID, $field['key'], true));
    if (!$value_raw) {
      $value_raw = $value_stored;
    }
    
    if($field['field_type'] == 'wysiwyg' && strpos($html, "%editor%") !== false) {
      $editor_name = sanitize_title($field['key']);
      // Remove anything that's not a lowercase letter.
      $editor_id = preg_replace( '/[^a-z]/', '', strtolower( $editor_name ));
      ob_start();
      wp_editor( $value_raw,  "mce" . $editor_id, array( 'textarea_name' => $editor_name ) );
      $editor = ob_get_clean();
      $html = str_replace("%editor%", $editor, $html);
    } else if( is_array($value_raw) ) {
      // Do some magic
      foreach( $value_raw as $key => $value ) {
        $html = str_replace("%$key%", $value, $html);
      }
      unset($value);
      
      if($value_stored == $value_raw['ID']) {
        $html = str_replace('%selected%', $html_selected, $html);
      }
      
    } else {
      $value = (strstr($value_raw, '*') && ($html_selected)) ? substr($value_raw, 1) : $value_raw;
      
      $html = str_replace('%value%', htmlspecialchars($value, ENT_QUOTES), $html);
      $html = str_replace('%max%', max($field['vals']), $html);
      $html = str_replace('%min%', min($field['vals']), $html);
      
      // Does this needs to be checked/selected/ticked?
      if ($value && ($value == $value_stored)) $html = str_replace('%selected%', $html_selected, $html);
      else if ((!$value_stored) && ($value_raw != $value)) $html = str_replace('%selected%', $html_selected, $html);
      else $html = str_replace('%selected%', '', $html);
      if ($value_stored == 'checkbox_on') $html = str_replace('%selected%', $html_selected, $html);
      
    }
   
    if (strpos($html, '%file_list_thumb%')) {
      $t = wp_get_attachment_thumb_url($value);
      // $r = ($t = wp_get_attachment_thumb_url($value)) ? "<img class='mf_thumb' src='$t'>" : '';
      if (!$t) $t = get_option('siteurl') . '/wp-content/plugins/more-fields/images/img-list-thumb.png';
      $html = str_replace('%file_list_thumb%', $t , $html);
    }
    
    return $html;
  }//end function field_type_render
 
  /**
   *	rewrite_rules_array
   *
   */
  function rewrite_rules_array ($rules) {
    global $wp_rewrite, $more_fields;
    $boxes = $more_fields->get_objects(array('_plugin_saved', '_plugin'));
    $new = array();
    foreach ((array) $boxes as $box) {
      foreach ((array) $box['fields'] as $field) {
        
        // Use either the slug, if defined, or the key as the slug
        $key = $field['key'];
        $slug = ($s = $field['slug']) ? $s : $key;
        if (!$slug || !$key) continue;				
        
        // Create the rule
        $new[$slug . '/([^/]+)/?$'] = "index.php?mf_key=$key&mf_value=\$matches[1]";
        
        // Add pagination
        $new[$slug . '/(.+?)/page/?([0-9]{1,})/?$'] = "index.php?mf_key=$key&mf_value=\$matches[1]&paged=\$matches[2]";
        
        // Add feed rule
        $new[$slug . '/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$'] = "index.php?mf_key=$key&mf_value=\$matches[1]&feed=\$matches[2]";
        
      }//end foreach box['fields']
      unset($field);
    }//end foreach boxes
    unset($box);
    
   //		print_r($rules);
    return $new + $rules;
  }//end function rewrite_rules_array
  
}//end class more_fields_admin extends more_plugins_admin_object_sputnik_8

function mf_ua_callback($object, $box) {
  global $more_fields, $more_fields_settings;
  $boxes = $more_fields->get_objects(array('_plugin_saved', '_plugin'));
  $more_fields_settings->build_box_gut($boxes[$box['id']]);
}

