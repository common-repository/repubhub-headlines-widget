<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

delete_option('rph_widget_widget_redirect_on_first_activation');
delete_option('repubhub_dismiss_widget_video');

?>