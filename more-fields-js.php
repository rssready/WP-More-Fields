<script type="text/javascript">
//<![CDATA[

jQuery().ready(function(){

    // Set up click events
    // Error fix: changed .on to .click so that it doesn't 
    // cause an error in older versions of jQuery.
    jQuery('.mf_file_thumb_area img, .mf_file_thumb_area span.no-value').click(function(){
        var clicked_obj = jQuery(this);
        clicked_obj.parent().find('input').trigger('click');
    });

});
//]]>
</script>