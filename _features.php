<?php
// Блок произвольных полей в админке WordPress своими руками
// http://wp-kama.ru/id_740/blok-proizvolnyih-poley-v-adminke-wordpress-svoimi-rukami.html#plaginy-dlya-sozdaniya-blokov-proizvolnyh-polej
// подключаем функцию активации мета блока (my_extra_fields)
add_action('admin_init', 'my_extra_fields', 1);

function my_extra_fields() {
	add_meta_box( 'extra_fields', 'Дополнительные поля', 'extra_fields_box_func', 'post', 'normal', 'high'  );
}

// код блока
function extra_fields_box_func( $post ){
?>
	<p><label><input type="text" name="extra[title]" value="<?php echo get_post_meta($post->ID, 'title', 1); ?>" style="width:50%" /> ? заголовок страницы (title)</label></p>

	<p>Описание статьи (description):
		<textarea type="text" name="extra[description]" style="width:100%;height:50px;"><?php echo get_post_meta($post->ID, 'description', 1); ?></textarea>
	</p>

	<p>Видимость поста: <?php $mark_v = get_post_meta($post->ID, 'robotmeta', 1); ?>
		<label><input type="radio" name="extra[robotmeta]" value="" <?php checked( $mark_v, '' ); ?> /> index,follow</label>
		<label><input type="radio" name="extra[robotmeta]" value="nofollow" <?php checked( $mark_v, 'nofollow' ); ?> /> nofollow</label>
		<label><input type="radio" name="extra[robotmeta]" value="noindex" <?php checked( $mark_v, 'noindex' ); ?> /> noindex</label>
		<label><input type="radio" name="extra[robotmeta]" value="noindex,nofollow" <?php checked( $mark_v, 'noindex,nofollow' ); ?> /> noindex,nofollow</label>
	</p>

	<p><select name="extra[select]" />
			<?php $sel_v = get_post_meta($post->ID, 'select', 1); ?>
			<option value="0">----</option>
			<option value="1" <?php selected( $sel_v, '1' )?> >Выбери меня</option>
			<option value="2" <?php selected( $sel_v, '2' )?> >Нет, меня</option>
			<option value="3" <?php selected( $sel_v, '3' )?> >Лучше меня</option>
		</select> ? выбор за вами</p>

	<p>
		<input type="hidden" name="extra[white]" value="">
		<label><input type="checkbox" name="extra[white]" value="1" <?php checked( get_post_meta($post->ID, 'white', 1), 1 )?> /> белый</label>
		<input type="hidden" name="extra[red]" value="">
		<label><input type="checkbox" name="extra[red]" value="1"   <?php checked( get_post_meta($post->ID, 'red',   1), 1 )?> /> красный</label>
		<input type="hidden" name="extra[black]" value="">
		<label><input type="checkbox" name="extra[black]" value="1" <?php checked( get_post_meta($post->ID, 'black', 1), 1 )?> /> черный</label>
	</p>

	<input type="hidden" name="extra_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>" />
<?php
}

// включаем обновление полей при сохранении
add_action('save_post', 'my_extra_fields_update', 0);

/* Сохраняем данные, при сохранении поста */
function my_extra_fields_update( $post_id ){
	if ( ! wp_verify_nonce($_POST['extra_fields_nonce'], __FILE__) ) return false; // проверка
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false; // если это автосохранение
	if ( ! current_user_can('edit_post', $post_id) ) return false; // если юзер не имеет право редактировать запись

	if( !isset($_POST['extra']) ) return false;

	// Все ОК! Теперь, нужно сохранить/удалить данные
	$_POST['extra'] = array_map('trim', $_POST['extra']);
	foreach( $_POST['extra'] as $key=>$value ){
		if( empty($value) )
			delete_post_meta($post_id, $key); // удаляем поле если значение пустое
			continue;

		update_post_meta( $post_id, $key, $value ); // add_post_meta() работает автоматически
	}
	return $post_id;
}