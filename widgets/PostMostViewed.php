<?php
/**
 * Widget: Post most viewed
 * Description: Show 5 most viewed posts
 */
class WEBMFT_PostMostViewed_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'',
			'_Most viewed',
			array('description' => 'Show 5 most viewed posts', /*'classname' => 'my_widget',*/ )
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

		$cat    = isset($instance['cat']) ? $instance['cat']:'';
		$num    = isset($instance['num']) ? $instance['num']:'5';
		$key    = isset($instance['key']) ? $instance['key']:'views';
		$order  = isset($instance['order']) ? 'ASC':'DESC';
		// $cache  = isset($instance['cache']) ? 1:0;
		$days   = isset($instance['days']) ? (int)$instance['days']:0;
		$echo   = isset($instance['echo']) ? 0:1;
		$format = isset($instance['format']) ? stripslashes($instance['format']):0;
		$cur_postID = $post->ID;

		if($cache) {
			$cache_key = (string) md5(__FUNCTION__ . serialize($args));
			if ($cache_out = wp_cache_get($cache_key)){
				if ($echo) return print($cache_out);
				else return $cache_out;
			}
		}

		if($days){
			$AND_days = "AND post_date > CURDATE() - INTERVAL $days DAY";
			if( strlen($days) == 4 )
				$AND_days = "AND YEAR(post_date)=" . $days;
		}

		foreach( $GLOBALS['wp_query']->posts as $post ) $IDs .= $post->ID .',';
		$AND_NOT_IN = ' AND p.ID NOT IN ('. rtrim($IDs, ',') .')';

		if( $cat ){

			$JOIN = "
				LEFT JOIN $wpdb->term_relationships rel ON ( p.ID = rel.object_id )
				LEFT JOIN $wpdb->term_taxonomy tax ON ( tax.term_taxonomy_id = rel.term_taxonomy_id  )
				";
			$DISTINCT = "DISTINCT";
			$AND_taxonomy = "AND tax.taxonomy = 'category'";
			$AND_category = "AND tax.term_id IN ($cat)";
			if( strpos($cat, '-')!==false )
				$AND_category = 'AND tax.term_id NOT IN ('. str_replace( '-','', $cat ) .')';
		}
		$sql = "SELECT $DISTINCT p.ID, p.post_title, p.post_date, p.guid, p.comment_count, (pm.meta_value+0) AS views
		FROM $wpdb->posts p $JOIN
			LEFT JOIN $wpdb->postmeta pm ON (pm.post_id = p.ID)
		WHERE pm.meta_key = '$key' $AND_days AND p.post_type = 'post' AND p.post_status = 'publish' $AND_category $AND_taxonomy $AND_NOT_IN
		ORDER BY views DESC LIMIT $num";
		$res = $wpdb->get_results($sql);

		if(!$res) {
			echo $args['after_widget'];
			return false;
		}

		$i = 0;
		foreach($res as $val){
			$i++;
			if ((int)$val->ID == (int)$cur_postID) $classActive = "active";
			else $classActive = '';
			$title = $val->post_title;
			if ($args['format'] == 0) {
				$out .= '<div class="'.$classActive.'"><a class="item" href="'.get_permalink($val->ID).'" title="'.$val->views.' views: '.$title.'">'.$title.'</a></div>';
			} elseif ($args['format'] == 1) {
					$atchment_post_IDD = get_the_post_thumbnail( url_to_postid( get_permalink($val->ID)), 'thumbnail' );
					$Sformat = '<div class="mrg-slots-cards"><a href="'. get_permalink($val->ID) .'"><div class="mrg-slot-card-img lazy"><p class="pabz">'.$atchment_post_IDD.'</p><div class="mrg-slot-info"><h1 class="myh1">'.$title.'</h1></div></div></a><div class="mrg-slot-play-btn"><a href="'. get_permalink($val->ID) .'">Review</a></div></div>';
					$out .= $Sformat;
			} elseif ($args['format'] == 2) {

						$atchment_post_IDD = get_the_post_thumbnail( url_to_postid( get_permalink($val->ID)), 'thumbnail' ); 
						$x = 'ben_'.$i;
						$Sformat = '<a href="/goto/1/" target="_blank">'.$atchment_post_IDD.'</a><div class="button_k"><a href="'. get_permalink($val->ID) .'" style="text-decoration: none; line-height: 35px;"><center style="color: rgba(89, 94, 99, 0.91);">'.$title.'</center> </a></div>';
						$out .= "<div class='$x'>$Sformat</div>";
			} else {
				$out .= '<div class="'.$classActive.'"><a class="item" href="'.get_permalink($val->ID).'" title="'.$val->views.' views: '.$title.'">'.$title.'</a></div>';
			}
		}

		if ($args['format'] == 0) {
			$out = '<div class="post-most-viewed">'. $out .'</div>';
		} elseif ($args['format'] == 2) {
			$out = '<div class="static">'. $out .'</div>';
		} else {
			$out = '<div class="post-most-viewed">'. $out .'</div>';
		}

		if($cache) wp_cache_add($cache_key, $out);

		if( $echo ) {
			echo $out;
			echo $args['after_widget'];
		} else {
			return $out;
		}
	}

	function form ($instance) {
		$title = @$instance['title']? : 'Most viewed';
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