<script type="text/javascript">
//<![CDATA[
var mf_current_form_field = null;
var backup = null;

jQuery().ready(function(){
    // Hide thumb areas that don't have images.
    jQuery('.mf_file_thumb_area img').each(function(){
        var clicked_obj = jQuery(this);
        if( clicked_obj.attr('src') == '' ) {
            clicked_obj.parent().hide();
        }
    });
    
    // Add clear click handlers
    jQuery('.mf_thumb_clear_button').click(function(){
        var field_area = jQuery(this).parent().parent();
        
        field_area.find('.mf_file_select_field').val('-1');
        field_area.find('.mf_attachment_text .mf_attachment_value').text('None');
        
        // Hide the preview area
        field_area.find('.mf_file_thumb_area').hide();
        
    });
    
    // This code handles file select (not with thumbnail).
    jQuery('.mf_file_select_button').click(function(e) {
        mf_current_form_field = jQuery(this).parent().parent();
        tb_show('Select a file', 'media-upload.php?type=file&amp;TB_iframe=true');
        
        backup = window.send_to_editor;
        window.send_to_editor = function(html) {
            var attachment_id = jQuery(html).attr('mf-attachement-id');
            
            mf_current_form_field.find('.mf_file_select_field').val(attachment_id);
            mf_current_form_field.find('.mf_attachment_text .mf_attachment_value').text(attachment_id);
            tb_remove();
            
            // Restore normal send_to_editor
            window.send_to_editor = backup;
        }
        
        return false;
    });
    
    // The code handles image select with thumbnails
    jQuery('.mf_thumb_select_button').click(function(e) {
        mf_current_form_field = jQuery(this).parent().parent();
        tb_show('Select an image', 'media-upload.php?type=image&amp;TB_iframe=true');
        
        backup = window.send_to_editor;
        window.send_to_editor = function(html) {
            var parsed_html = jQuery(html);
            var attachment_id = parsed_html.attr('mf-attachement-id');
            var imgurl = parsed_html.find('img').attr('src');
            
            mf_current_form_field.find('.mf_file_select_field').val(attachment_id);
            mf_current_form_field.find('.mf_attachment_text .mf_attachment_value').text(attachment_id);
            
            mf_current_form_field.find('.mf_file_thumb_area img').attr('src', imgurl).parent().show();
            
            tb_remove();
            
            // Restore normal send_to_editor
            window.send_to_editor = backup;
        }
        
        return false;
    });

});
//]]>
</script>
