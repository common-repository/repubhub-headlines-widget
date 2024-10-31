<?php
/*
Plugin Name: repubHub Headlines Widget
Plugin URI: http://www.icopyright.com/wordpress
Description: Display trending headlines from world-class publications. Headlines are linked to the full article, <i>automatically republished by the plugin on your own site</i>.
Author: iCopyright, Inc.  
Author URI: http://www.icopyright.com/home
Version: 1.0.3
*/

//include icopyright common functions file, there is defined server settings.
include (plugin_dir_path(__FILE__) . 'repubhub-common-widget.php');

if(!defined("RPH_WIDGET_VERSION")) define("RPH_WIDGET_VERSION", "1.0.3");
if(!defined("RPH_WIDGET_USERAGENT")) define("RPH_WIDGET_USERAGENT", "repubhub Headlines Widget v1.0.3");

// register our oembed technology
$portal = rphWidgetGetPortal() ."/";
wp_oembed_add_provider($portal . 'freePost.act?*', $portal . 'oembed.act', false );


// register Icopy_Widget widget
include (plugin_dir_path(__FILE__) . 'Repubhub_Widget.php');
function register_repubhub_widget() {
	register_widget( 'Repubhub_Widget' );
}
add_action( 'widgets_init', 'register_repubhub_widget' );
add_action('edit_form_after_title', 'rph_widget_widget_edit_form_after_title' );

add_filter( 'default_content', 'rph_widget_widget_republish_content', 10, 2 );
add_filter( 'default_title', 'rph_widget_widget_republish_title', 10, 2 );

add_filter('the_title', 'rph_widget_hide_title', 11, 2);

/**
 * Called on activation.
 */
function rph_widget_widget_activate() {
	update_option('rph_widget_widget_redirect_on_first_activation', 'true');
}


/**
 * On first activation, redirect the user to the general options page
 */
function rph_widget_widget_redirect_on_activation() {
  if (current_user_can('activate_plugins')) {
    if (get_option('rph_widget_widget_redirect_on_first_activation') == 'true') {
      delete_option('rph_widget_widget_redirect_on_first_activation');
      $rph_widget_settings_url = admin_url() . "widgets.php";
      wp_safe_redirect($rph_widget_settings_url);
    }
  }
}

register_activation_hook(__FILE__, 'rph_widget_widget_activate');
add_action('admin_init', 'rph_widget_widget_redirect_on_activation');


function rph_widget_widget_republish_content( $content, $post ) {
	//set content
	if (!empty( $_GET['icx_tag'] ) && !empty($_GET['rph_widget'])) {
		$user_agent = RPH_WIDGET_USERAGENT;
		$email = null;
		$password = null;
		$allowScript = current_user_can("unfiltered_html");
		$res = rph_widget_get_embed(urlencode($_GET['icx_tag']), $allowScript, $user_agent, $email, $password);


		if (!$res->response) {
			// some error must have happened like a timeout.  Try a few more times.
			$i = 0;
			do {
				$res = rph_widget_get_embed(urlencode($_GET['icx_tag']), $allowScript, $user_agent, $email, $password);
				$i++;
			} while ($i < 5 && !$res->response);
				
		}
		$xml = @simplexml_load_string($res->response);
		$content = $xml->embedCode;

		if (!isset($post)) {
			$post = new stdClass();
		}

		$post->title = $xml->title;

		update_post_meta($post->ID, "rph_widget_widget_republish_content", true);

		return($content);
	}
	return $content;
}

function rph_widget_hide_title($title, $id = null) {
	if(in_the_loop() && get_option("repubhub_widget_hide_title_" . $id) != null) {
		return '';
	}
	return $title;
}



function rph_widget_widget_republish_title( $title, $post ) {
	//set content
	if (!empty( $_GET['icx_tag'] )) {
		return($post->title);
	}
	return $title;
}

function rph_widget_widget_edit_form_after_title() {
	if ((!empty($_GET['icx_tag']) && !empty($_GET['rph_widget'])) || (!empty($_GET['post']) && get_post_meta($_GET['post'], "rph_widget_widget_republish_content"))) {
		if(get_option("repubhub_dismiss_post_new_info_box") == null) {
			$adminAjaxUrl = admin_url('admin-ajax.php');
			$dataLoc = admin_url('edit.php?page=repubhub-republish');
			?>
      <p style="float:left; background:lightblue; padding:10px; margin: 0 0 20px 0;" id="icx_post_new_info_box">
			The embed code in the text editor will display the republished article. 
			To preview the article, be sure to click &quot;Save Draft&quot; first, 
			and then &quot;View Post&quot; at top (since clicking Preview will not work in some browsers). 
			You may add an intro or conclusion above or below the embed code in the text editor.
        <br/>
        <a style="float: right;" href="" id="icx_dismiss_post_new_info_box">Dismiss</a>
      </p>
      <div style="clear: both;"></div>
      <script type="text/javascript">
        jQuery(document).ready(function () {
          jQuery("#icx_dismiss_post_new_info_box").click(function (event) {
            jQuery("#icx_post_new_info_box").hide();
            jQuery.ajax({
              url : '<?php echo $adminAjaxUrl;?>',
              type : "get",
              data : {action: "widget_dismiss_post_new_info_box", loc: '<?php echo $dataLoc;?>'},
              success: function() {}
            });
            event.preventDefault();
          });
        });
      </script>
    <?php
    }
    
    if (!empty($_GET['icx_tag'])) {
  	?>
  <p style="float:left; background:lightblue; padding:10px; margin: 0 0 20px 0;" id="icx_terms_of_use_box">
    By clicking "Publish" you agree to the
  <a target="_blank" href="<?php print rph_widget_get_server() ?>/rights/termsOfUse.act?sid=15&tag=<?php print urlencode($_GET['icx_tag']) ?>">terms of use</a>.
      </p>
<?php }?>  
  
      <div style="clear: both;"></div>
  <?php
  }
}
add_action('wp_ajax_widget_dismiss_post_new_info_box', 'rph_widget_widget_dismiss_post_new_info_box');
add_action('wp_ajax_widget_dismiss_video_link', 'rph_widget_dismiss_video');

function rph_widget_widget_dismiss_post_new_info_box() {
	update_option("repubhub_dismiss_post_new_info_box", "true");
}

function rph_widget_dismiss_video() {
	update_option("repubhub_dismiss_widget_video", "true");
}

// Shortcode for widget to display article on widget page if no article specified
function repubhub_widget_default_func($atts) {
	return '<div class="repubhubembed" data-type="hash" data-source="wordpress" data-tag="' . $atts['tag'] . '&showTitle=true"></div><script async type="text/javascript" src="' . rph_widget_static_server() . '/user/js/rh.js"></script>';
}

add_shortcode('repubhub_widget_default', 'repubhub_widget_default_func');

add_filter('plugin_row_meta', 'rph_widget_settings_link', 10, 2);

//function to create video link
function rph_widget_settings_link($links, $file) {
	if ($file == plugin_basename(__FILE__)) {		
		//add_thickbox();
		wp_enqueue_style('thickbox');
		wp_enqueue_script('thickbox');		
		
		echo '<div id="rph_video_content" style="display:none;"><iframe width="740" height="480" src="https://www.youtube.com/embed/uoNcvB1S-m4?rel=0" frameborder="0" allowfullscreen></iframe></div>';
		$video_link = "<a id=\"rph_wp_settings_video\" href=\"#TB_inline?width=755&height=485&inlineId=rph_video_content\" class=\"thickbox\">View a video introduction</a>"; 
		$links[] .= $video_link; 		

	}
	return $links;
}

// define the widgets_admin_page callback
function rph_action_widgets_admin_page(  ) {
	if(get_option("repubhub_dismiss_widget_video") == null) {
		add_thickbox();
		echo '<div id="rph_video_content" style="display:none;"><iframe width="853" height="480" src="https://www.youtube.com/embed/uoNcvB1S-m4?rel=0" frameborder="0" allowfullscreen></iframe></div>';
		echo '<h3 id="rph_video_h3" style="background-color: #cedfe4; width: 75%; padding: 10px;">Click <a id="rph_wp_settings_video" href="#TB_inline?width=858&height=485&inlineId=rph_video_content" class="thickbox">here</a> for a 2 minute video explanation of the repubHub Headlines Widget. <a id="rph_widget_video_dismiss" href="">Dismiss</a></h3>';
		
		$adminAjaxUrl = admin_url('admin-ajax.php');
		?>
				
		      <script type="text/javascript">
		        jQuery(document).ready(function () {
		          jQuery("#rph_widget_video_dismiss").click(function (event) {
		            jQuery("#rph_video_h3").hide();
		            jQuery.ajax({
		              url : '<?php echo $adminAjaxUrl;?>',
		              type : "get",
		              data : {action: "widget_dismiss_video_link"},
		              success: function() {}
		            });
		            event.preventDefault();
		          });
		        });
		      </script>
		      
		      <?php 			
	}
};
 
// add the action
add_action( 'widgets_admin_page', 'rph_action_widgets_admin_page', 10, 0 );

?>
