<script type="text/javascript">
//<![CDATA[

jQuery().ready(function(){

    // Set up click events
    jQuery('.mf_file_thumb_area img, .mf_file_thumb_area span.no-value').on('click', function(){
        var clicked_obj = jQuery(this);
        clicked_obj.parent().find('input').trigger('click');
    });

});
//]]>
</script>