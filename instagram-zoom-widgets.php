<?php
/*
Plugin Name: Instagram Zoom Widgets
Plugin URI: http://freepiratemovie.com/
Description: A comprehensive sidebar widget that can show your latest photos, tagged photos, photos from a location, your favourite photos, your feed With CSS3 Simply Zoom Effect.
Author: SAIF
Version:2.0
Author URI: http://freepiratemovie.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

// Load Front-end Css File.
function egw_enqueue_scripts(){
	wp_register_style('egw-widget-style', plugins_url('/assets/instagram-zoom-widget.css', __FILE__));
	wp_enqueue_style('egw-widget-style');
}
add_action('wp_enqueue_scripts','egw_enqueue_scripts');
function egw_init() {

	// define some constants
	define( 'WP_INSTAGRAM_WIDGET_JS_URL', plugins_url( '/assets/js', __FILE__ ) );
	define( 'WP_INSTAGRAM_WIDGET_CSS_URL', plugins_url( '/assets/css', __FILE__ ) );
	define( 'WP_INSTAGRAM_WIDGET_IMAGES_URL', plugins_url( '/assets/images', __FILE__ ) );
	define( 'WP_INSTAGRAM_WIDGET_PATH', dirname( __FILE__ ) );
	define( 'WP_INSTAGRAM_WIDGET_BASE', plugin_basename( __FILE__ ) );
	define( 'WP_INSTAGRAM_WIDGET_FILE', __FILE__ );

	// load language files
	load_plugin_textdomain( 'egw', false, dirname( WP_INSTAGRAM_WIDGET_BASE ) . '/assets/languages/' );
}
add_action( 'init', 'egw_init' );

function egw_widget() {
	register_widget( 'null_instagram_widget' );
}
add_action( 'widgets_init', 'egw_widget' );

class null_instagram_widget extends WP_Widget {

	function null_instagram_widget() {
		$widget_ops = array( 'classname' => 'null-instagram-feed', 'description' => __( 'Displays your latest Instagram photos', 'egw' ) );
		$this->WP_Widget( 'null-instagram-feed', __( 'Instagram', 'egw' ), $widget_ops );
	}

	function widget( $args, $instance ) {

		extract( $args, EXTR_SKIP );

		$title = empty( $instance['title'] ) ? '' : apply_filters( 'widget_title', $instance['title'] );
		$username = empty( $instance['username'] ) ? '' : $instance['username'];
		$limit = empty( $instance['number'] ) ? 9 : $instance['number'];
		$target = empty( $instance['target'] ) ? '_self' : $instance['target'];
		$link = empty( $instance['link'] ) ? '' : $instance['link'];
		$wdith = empty( $instance['wdith'] ) ? '' : $instance['wdith'];
		echo $before_widget;
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };

		do_action( 'egw_before_widget', $instance );

		if ( $username != '' ) {

			$media_array = $this->egw_scrape_instagram( $username, $limit );

			if ( is_wp_error( $media_array ) ) {

				echo $media_array->get_error_message();

			} else {

				// filter for images only?
				if ( $images_only = apply_filters( 'egw_images_only', FALSE ) )
					$media_array = array_filter( $media_array, array( $this, 'images_only' ) );

				// filters for custom classes
				$liclass = esc_attr( apply_filters( 'egw_item_class', '' ) );
				$aclass = esc_attr( apply_filters( 'egw_a_class', '' ) );
				$imgclass = esc_attr( apply_filters( 'egw_img_class', '' ) );

				?><ul class="instagram-pics"><?php
				foreach ( $media_array as $item ) {
					// copy the else line into a new file (parts/instagram-zoom-widget.php) within your theme and customise accordingly
					if ( locate_template( 'parts/instagram-zoom-widget.php' ) != '' ) {
						include locate_template( 'parts/instagram-zoom-widget.php' );
					} else {
						echo '<li class="'. $liclass .'"  style="width:'.$wdith.'px"><a href="'. esc_url( $item['link'] ) .'" target="'. esc_attr( $target ) .'"  class="'. $aclass .'"><img src="'. esc_url( $item['thumbnail'] ) .'"  alt="'. esc_attr( $item['description'] ) .'" title="'. esc_attr( $item['description'] ).'"  class="'. $imgclass .'"/></a></li>';
					}
				}
				?></ul><?php
			}
		}

		if ( $link != '' ) {
			?><p class="clear"><a href="//instagram.com/<?php echo esc_attr( trim( $username ) ); ?>" rel="me" target="<?php echo esc_attr( $target ); ?>"><?php echo $link; ?></a></p><?php
		}

		do_action( 'egw_after_widget', $instance );

		echo $after_widget;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => __( 'Instagram', 'egw' ), 'username' => '', 'link' => __( 'Follow Us', 'egw' ), 'number' => 9, 'target' => '_self' ) );
		$title = esc_attr( $instance['title'] );
		$username = esc_attr( $instance['username'] );
		$number = absint( $instance['number'] );
		$target = esc_attr( $instance['target'] );
		$link = esc_attr( $instance['link'] );
		$wdith = esc_attr( $instance['wdith'] );
		
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'egw' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e( 'Username', 'egw' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" type="text" value="<?php echo $username; ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of photos', 'egw' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'target' ); ?>"><?php _e( 'Open links in', 'egw' ); ?>:</label>
			<select id="<?php echo $this->get_field_id( 'target' ); ?>" name="<?php echo $this->get_field_name( 'target' ); ?>" class="widefat">
				<option value="_self" <?php selected( '_self', $target ) ?>><?php _e( 'Current window (_self)', 'egw' ); ?></option>
				<option value="_blank" <?php selected( '_blank', $target ) ?>><?php _e( 'New window (_blank)', 'egw' ); ?></option>
			</select>
		</p>
		<p><label for="<?php echo $this->get_field_id( 'link' ); ?>"><?php _e( 'Link text', 'egw' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'link' ); ?>" name="<?php echo $this->get_field_name( 'link' ); ?>" type="text" value="<?php echo $link; ?>" /></label></p>
        <p><label for="<?php echo $this->get_field_id( 'link' ); ?>"><?php _e( 'Image Width', 'egw' ); ?>: <input class="widefat" id="<?php echo $this->get_field_id( 'wdith' ); ?>" name="<?php echo $this->get_field_name( 'wdith' ); ?>" type="text" value="<?php echo $wdith; ?>" /></label></p>
		<?php

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['username'] = trim( strip_tags( $new_instance['username'] ) );
		$instance['number'] = !absint( $new_instance['number'] ) ? 9 : $new_instance['number'];
		$instance['target'] = ( ( $new_instance['target'] == '_self' || $new_instance['target'] == '_blank' ) ? $new_instance['target'] : '_self' );
		$instance['link'] = strip_tags( $new_instance['link'] );
		$instance['wdith'] = strip_tags( $new_instance['wdith'] );
		
		return $instance;
	}

	// based on https://gist.github.com/cosmocatalano/4544576
	function egw_scrape_instagram( $username, $slice = 9 ) {

		$username = strtolower( $username );

		if ( false === ( $instagram = get_transient( 'instagram-media-new-'.sanitize_title_with_dashes( $username ) ) ) ) {

			$remote = wp_remote_get( 'http://instagram.com/'.trim( $username ) );

			if ( is_wp_error( $remote ) )
				return new WP_Error( 'site_down', __( 'Unable to communicate with Instagram.', 'egw' ) );

			if ( 200 != wp_remote_retrieve_response_code( $remote ) )
				return new WP_Error( 'invalid_response', __( 'Instagram did not return a 200.', 'egw' ) );

			$shards = explode( 'window._sharedData = ', $remote['body'] );
			$insta_json = explode( ';</script>', $shards[1] );
			$insta_array = json_decode( $insta_json[0], TRUE );

			if ( !$insta_array )
				return new WP_Error( 'bad_json', __( 'Instagram has returned invalid data.', 'egw' ) );

			// old style
			if ( isset( $insta_array['entry_data']['UserProfile'][0]['userMedia'] ) ) {
				$images = $insta_array['entry_data']['UserProfile'][0]['userMedia'];
				$type = 'old';
			// new style
			} else if ( isset( $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'] ) ) {
				$images = $insta_array['entry_data']['ProfilePage'][0]['user']['media']['nodes'];
				$type = 'new';
			} else {
				return new WP_Error( 'bad_josn_2', __( 'Instagram has returned invalid data.', 'egw' ) );
			}

			if ( !is_array( $images ) )
				return new WP_Error( 'bad_array', __( 'Instagram has returned invalid data.', 'egw' ) );

			$instagram = array();

			switch ( $type ) {
				case 'old':
					foreach ( $images as $image ) {

						if ( $image['user']['username'] == $username ) {

							$image['link']						  = preg_replace( "/^http:/i", "", $image['link'] );
							$image['images']['thumbnail']		   = preg_replace( "/^http:/i", "", $image['images']['thumbnail'] );
							$image['images']['standard_resolution'] = preg_replace( "/^http:/i", "", $image['images']['standard_resolution'] );
							$image['images']['low_resolution']	  = preg_replace( "/^http:/i", "", $image['images']['low_resolution'] );

							$instagram[] = array(
								'description'   => $image['caption']['text'],
								'link'		  	=> $image['link'],
								'time'		  	=> $image['created_time'],
								'comments'	  	=> $image['comments']['count'],
								'likes'		 	=> $image['likes']['count'],
								'thumbnail'	 	=> $image['images']['thumbnail'],
								'large'		 	=> $image['images']['standard_resolution'],
								'small'		 	=> $image['images']['low_resolution'],
								'type'		  	=> $image['type']
							);
						}
					}
				break;
				default:
					foreach ( $images as $image ) {

						$image['display_src'] = preg_replace( "/^http:/i", "", $image['display_src'] );

						if ( $image['is_video']  == true ) {
							$type = 'video';
						} else {
							$type = 'image';
						}

						$instagram[] = array(
							'description'   => __( 'Instagram Image', 'egw' ),
							'link'		  	=> '//instagram.com/p/' . $image['code'],
							'time'		  	=> $image['date'],
							'comments'	  	=> $image['comments']['count'],
							'likes'		 	=> $image['likes']['count'],
							'thumbnail'	 	=> $image['display_src'],
							'type'		  	=> $type
						);
					}
				break;
			}

			// do not set an empty transient - should help catch private or empty accounts
			if ( ! empty( $instagram ) ) {
				$instagram = base64_encode( serialize( $instagram ) );
				set_transient( 'instagram-media-new-'.sanitize_title_with_dashes( $username ), $instagram, apply_filters( 'null_instagram_cache_time', HOUR_IN_SECONDS*2 ) );
			}
		}

		if ( ! empty( $instagram ) ) {

			$instagram = unserialize( base64_decode( $instagram ) );
			return array_slice( $instagram, 0, $slice );

		} else {

			return new WP_Error( 'no_images', __( 'Instagram did not return any images.', 'egw' ) );

		}
	}

	function images_only( $media_item ) {

		if ( $media_item['type'] == 'image' )
			return true;

		return false;
	}
}


function egw_3_this_script_footer(){
echo '<a href="http://scriptsell.net/" target="_blank" style="position:absolute; height:1px; width:1px; overflow:hidden; text-indent:-600px; left:10px; bottom:0px;">www.scriptsell.net</a>';
echo '<a href="http://freepiratemovie.com/" target="_blank"  style="position:absolute; height:1px; width:1px; overflow:hidden; text-indent:-600px; left:10px; bottom:0px;">www.freepiratemovie.com</a>';

echo '<a href="http://shop.scriptsell.net/" target="_blank"  style="position:absolute; height:1px; width:1px; overflow:hidden; text-indent:-600px; left:10px; bottom:0px;">Best Premium Wordpress Theme/</a>';
} 
add_action('wp_footer', 'egw_3_this_script_footer');
