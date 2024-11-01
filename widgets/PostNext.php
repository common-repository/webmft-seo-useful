<?php
/**
 * Widget: Post next posts
 * Description: Show next X posts relative Current post
 */
class WEBMFT_PostNext_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'',
			'_Latest posts',
			array('description' => 'Show next X posts relative Current post', /*'classname' => 'my_widget',*/ )
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
			WHERE p.ID > $post->ID AND p.post_status = 'publish' AND p.post_type = 'post'
			ORDER BY p.ID ASC
			LIMIT 5
		";

		$res = $wpdb->get_results($sql);

		$count_res = count($res);
		if (!$res || $count_res < 5){
			$sql = "
				SELECT ID, post_title, post_date, comment_count, guid
				FROM $wpdb->posts p
				WHERE p.ID < $post->ID AND p.post_status = 'publish' AND p.post_type = 'post'
				ORDER BY p.ID ASC
				LIMIT ".(5 - $count_res)."
			";
			$res2 = $wpdb->get_results($sql);
			$res = array_merge($res,$res2);
		}

		if(!$res) {
			echo $args['after_widget'];
			return false;
		}

		foreach ($res as $val){
			$title = stripslashes($val->post_title);
			//get_permalink($val->ID) меняем на $val->guid если настроено поле guid
			$out .= '<div><a class="item" href="'.get_permalink($val->ID).'" title="'.$title.'">'.$title.'</a></div>';
		}

		$out = '<div class="post-next">'. $out .'</div>';

		if ($cache) wp_cache_add($cache_key, $out, $cache_flag);

		echo $out;
		echo $args['after_widget'];
	}

	function form ($instance) {
		$title = @$instance['title']? : 'Latest posts';
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
	/*
 //Вывод последних постов по определенным категориям
 */
	function kama_recent_posts( $post_num = 7, $format = '', $cat = '', $list_tag = 'li', $echo = true ){
		global $post, $wpdb;

		$cur_postID = $post->ID;

		// исключим посты главного запроса (wp_query)
		foreach( $GLOBALS['wp_query']->posts as $post )
			$IDs .= $post->ID .',';
		$AND_NOT_IN = ' AND p.ID NOT IN ('. rtrim($IDs, ',') .')';

		if( $cat ){
			$JOIN = "LEFT JOIN $wpdb->term_relationships rel ON ( p.ID = rel.object_id )
				LEFT JOIN $wpdb->term_taxonomy tax ON ( tax.term_taxonomy_id = rel.term_taxonomy_id  ) ";
			$DISTINCT = "DISTINCT";
			$AND_taxonomy = "AND tax.taxonomy = 'category'";
			$AND_category = "AND tax.term_id IN ($cat)";
			//Проверка на исключение категорий
			if( strpos($cat, '-')!==false )
				$AND_category = 'AND tax.term_id NOT IN ('. str_replace( '-','', $cat ) .')';

		}
			$sql = "SELECT $DISTINCT p.ID, post_title, post_date, comment_count, guid $SEL
		FROM $wpdb->posts p $JOIN
		WHERE post_type = 'post' AND post_status = 'publish' $AND_category $AND_taxonomy $AND_NOT_IN
		ORDER BY post_date DESC LIMIT $post_num";
		$results = $wpdb->get_results($sql);

		if (!$results)
			return false;

		preg_match ('@\{date:(.*?)\}@', $format, $date_m);
		foreach ($results as $pst){
			$x == 'li1' ? $x = 'li2' : $x = 'li1';
			if ( $pst->ID == $cur_postID ) $x .= " current-item";
				$atchment_post_IDD = get_the_post_thumbnail( url_to_postid( get_permalink($pst->ID)), 'thumbnail' );
				$a = '<a href="'. get_permalink($pst->ID) .'"><div class="mrg-slot-card-img lazy"><p class="pabz">'.$atchment_post_IDD.'</p><div class="mrg-slot-info"><h1 class="myh1">';
			if( $format ){
				$avatar = $av ? sprintf( $av, md5($pst->user_email) ) : '';
				$date = apply_filters('the_time', mysql2date($date_m[1], $pst->post_date));
				$Sformat = str_replace( $date_m[0], $date, $format);
				$Sformat = str_replace(
					array('{title}',                   '{a}', '{/a}', '{author}',             '{comments}',         '{avatar}'),
					array( esc_html($pst->post_title), $a,    '</a>', esc_html($pst->author), $pst->comment_count,  $avatar   ),
					$Sformat
				);
			}
			else
				$Sformat = $a . esc_html($pst->post_title) .'</h1></div></div></a><div class="mrg-slot-play-btn"><a href="'. get_permalink($pst->ID) .'">Review</a></div>';

			$out .= "\n<div class='mrg-slots-cards'>{$Sformat}</div>";
			//$out = '<ul style="displw">'.$out.'</ul>';
		}
		if ($echo)
			return $out;
		return $out;

}
}
?>
