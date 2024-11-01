<?php
/**
 * Widget: Post prev post
 * Description: Show prev 1 post relative Current post
 */
class WEBMFT_PostPrev_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'',
			'_Previous post',
			array('description' => 'Show prev 1 post relative Current post', /*'classname' => 'my_widget',*/ )
		);
	}

	/**
	 * Вывод виджета во Фронт-энде
	 *
	 * @param array $args     аргументы виджета.
	 * @param array $instance сохраненные данные из настроек
	 */
	function widget( $args, $instance ) {
		global $post, $wpdb;

		$title = apply_filters('widget_title', $instance['title']);

		echo $args['before_widget'];
		if (!empty($title)) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		// $cache = 1;
		$cache_key = (string) md5( __FUNCTION__ . $post->ID );
		$cache_flag = __FUNCTION__;
		if ($cache && $cache_out = wp_cache_get($cache_key, $cache_flag)) return $cache_out;

		$sql = "
			SELECT ID, post_title, post_date, comment_count, guid
			FROM $wpdb->posts p
			WHERE p.ID < $post->ID
				AND p.post_status = 'publish'
				AND p.post_type = 'post'
			ORDER BY p.ID DESC
			LIMIT 1
		";
		$res = $wpdb->get_results($sql);

		if (!$res){
			$sql = "
				SELECT ID, post_title, post_date, comment_count, guid
				FROM $wpdb->posts p
				WHERE p.ID > $post->ID
					AND p.post_status = 'publish'
					AND p.post_type = 'post'
				ORDER BY p.ID DESC
				LIMIT 1
			";
			$res = $wpdb->get_results($sql);
		}

		if(!$res) {
			echo $args['after_widget'];
			return false;
		}

		foreach ($res as $val){

			$sql = "
				SELECT w.meta_value
				FROM $wpdb->postmeta w
					LEFT JOIN $wpdb->postmeta p ON (p.meta_value = w.post_id)
				WHERE p.meta_key = '_thumbnail_id'
					AND p.post_id = $val->ID
					AND w.meta_key = '_wp_attached_file'
				LIMIT 1
			";
			$resTH = $wpdb->get_results($sql);
			$title = stripslashes($val->post_title);
			//get_permalink($val->ID) меняем на $val->guid если настроено поле guid

			$out .= '<div><a class="item" href="'.get_permalink($val->ID).'" title="'.$title.'"><img src="/wp-content/uploads/'.$resTH[0]->meta_value.'" alt="'.$title.'"></a></div>';
		}
		$out = '<div class="post-prev list-unstyled">'. $out .'</div>';

		if($cache) wp_cache_add($cache_key, $out, $cache_flag);

		echo $out;
		echo $args['after_widget'];
	}

	function form ($instance) {
		$title = @$instance['title']? : 'Previous post';
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	function update ($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = (!empty($new_instance['title']))? strip_tags($new_instance['title']) : '';

		return $instance;
	}
}
?>
