<?php
/**
 * Создает блок произвольных полей для указанных типов записей.
 *
 * Возможные параметры класса, смотрите в: Kama_Post_Meta_Box::__construct()
 * Возможные параметры для каждого поля, смотрите в: Kama_Post_Meta_Box::field()
 *
 * При сохранении, очищает каждое поле, через: wp_kses() или sanitize_text_field().
 * Функцию очистки можно заменить через хук 'kpmb_save_sanitize_{$id}' и
 * также можно указать название функции очистки в параметре 'save_sanitize'.
 * Если указать функции очистки и в параметре, и в хуке, то будут работать обе!
 * Обе функции очистки получают данные: $metas - все метаполя, $post_id - ID записи.
 *
 * Блок выводиться и метаполя сохраняются для юзеров с правом редактировать текущую запись.
 *
 * PHP: 5.3+
 * ver: 1.7
 */
class WebMFT_CustomField {

	public $opt;
	public $id;

	static $instances = array(); // сохраняет ссылки на все экземпляры

	/**
	 * Конструктор
	 * @param array $opt Опции по которым будет строиться метаблок
	 */
	function __construct( $opt ){
		$defaults = array(
			'id'         => '',            // Идент. блока. Используется как префикс для названия метаполя.
										   // Укажите Идент. с начального '_': '_foo', чтобы ID не был префиксом в названии метаполей.
			'title'      => '',            // заголовок блока
			'post_type'  => '',            // строка/массив. Типы записей для которых добавляется блок: array('post','page'). По умолчанию: '' - для всех типов записей.
			'priority'   => 'high',        // Приоритет блока для показа выше или ниже остальных блоков ('high' или 'low').
			'context'    => 'normal',      // Место где должен показываться блок ('normal', 'advanced' или 'side').

			// функция отключения метабокса во время вызова самого метабокса. Если что-либо вернет, то метабокс будет отключен. Передает объект поста
			'disable_func'  => '',

			// Метаполя, которые будут выводиться в блоке
			'fields'     => array(
				'foo' => array('title'=>'Первое метаполе' ),
				'bar' => array('title'=>'Второе метаполе' ),
			),

			// Функция очистки сохраняемых в БД полей. Получает 2 параметра: $metas - все поля для очистки и $post_id
			'save_sanitize' => '',

			// тема оформления. Может быть: 'table', 'line' или массив с данными полей (см. изменение опции '$this->opt->theme').
			// изменить тему можно также через фильтр 'kpmb_theme'
			'theme' => 'table',
		);

		$this->opt = (object) array_merge( $defaults, $opt );

		// темы оформления
		if(1){
			// тема 'table'
			if( $this->opt->theme === 'table' ){
				$this->opt->theme = array(
					'css'         => '.kpmb-table td{ padding:.6em .5em; } .kpmb-table tr:hover{ background:rgba(0,0,0,.03); }', // CSS стили всего блока. Например: '.postbox .tit{ font-weight:bold; }'
					'fields_wrap' => '<table class="form-table kpmb-table">%s</table>',        // '%s' будет заменено на html всех полей
					'field_wrap'  => '<tr class="%1$s">%2$s</tr>',                  // '%2$s' будет заменено на html поля (вместе с заголовком, полем и описанием)
					'title_patt'  => '<td style="width:10em;" class="tit">%s</td>', // '%s' будет заменено на заголовок
					'field_patt'  => '<td class="field">%s</td>',                   // '%s' будет заменено на HTML поля (вместе с описанием)
					'desc_patt'   => '<br><span class="description" style="opacity:0.8;">%s</span>', // '%s' будет заменено на текст описания
				);
			}

			// тема 'line'
			if( $this->opt->theme === 'line' ){
				$this->opt->theme = array(
					'css'         => '',
					'fields_wrap' => '%s',
					'field_wrap'  => '<p class="%1$s">%2$s</p>',
					'title_patt'  => '<strong class="tit">%s</strong>',
					'field_patt'  => '%s',
					'desc_patt'   => '<br><span class="description" style="opacity:0.6;">%s</span>',
				);
			}

			// для изменения темы через фильтр
			$this->opt->theme = apply_filters('kpmb_theme', $this->opt->theme, $this->opt );

			// добавим все переменные из theme в опции, если в опциях уже есть переменная, то она остается как есть
			foreach( $this->opt->theme as $kk => $vv ) if( ! isset($this->opt->{$kk}) ) $this->opt->{$kk} = $vv;
		}

		$this->disabled = false; // по умолчанию всегда включен

		// создадим уникальный ID объекта
		$_opt = (array) clone $this->opt;
		// удалим (очистим) все closure
		array_walk_recursive( $_opt, function(&$val, $key){ if( $val instanceof Closure ) $val = ''; });
		$this->id = substr( md5(serialize($_opt)), 0, 7 ); // ID экземпляра

		// сохраним ссылку на экземпляр, чтобы к нему был доступ
		self::$instances[$this->opt->id][$this->id] = & $this;

		// хуки
		add_action('add_meta_boxes', array( &$this, 'add_meta_box' ), 10, 2 );
		add_action('save_post', array( &$this, 'meta_box_save' ), 1, 2 );
	}

	function add_meta_box( $post_type, $post ){
		// maybe disable?
		if( in_array( $post_type, array('comment','link')) ) return;
		if( ! current_user_can( get_post_type_object( $post_type )->cap->edit_post, $post->ID ) ) return;

		$opt = $this->opt; // short love

		// maybe disable meta_box
		if( is_callable($opt->disable_func) )
			$this->disabled = call_user_func( $opt->disable_func, $post );
		if( $this->disabled ) return;

		$p_types = $opt->post_type ?: $post_type;

		// if WP < 4.4
		if( is_array($p_types) && version_compare( $GLOBALS['wp_version'], '4.4', '<' ) ){
			foreach( $p_types as $p_type )
				add_meta_box( $this->id, $opt->title, array( &$this, 'meta_box'), $p_type, $opt->context, $opt->priority );
		}
		else
			add_meta_box( $this->id, $opt->title, array( &$this, 'meta_box'), $p_types, $opt->context, $opt->priority );

		// добавим css класс к метабоксу
		// apply_filters( "postbox_classes_{$page}_{$id}", $classes );
		add_filter( "postbox_classes_{$post_type}_{$this->id}", array( &$this, '__postbox_classes_add') );
	}

	/**
	 * Выводит код блока
	 * @param object $post Объект записи
	 */
	function meta_box( $post ){
		$fields_out = $hidden_out = '';
		foreach( $this->opt->fields as $key => $args ){
			if( ! $key || ! $args ) continue; // пустое поле

			if( empty($args['title_patt']) ) $args['title_patt'] = @ $this->opt->title_patt ?: '%s';
			if( empty($args['desc_patt'])  ) $args['desc_patt']  = @ $this->opt->desc_patt  ?: '%s';
			if( empty($args['field_patt']) ) $args['field_patt'] = @ $this->opt->field_patt ?: '%s';

			$args['key'] = $key;

			$field_wrap = & $this->opt->field_wrap;
			if( @ $args['type'] == 'wp_editor' )  $field_wrap = str_replace(array('<p ','</p>'), array('<div ','</div><br>'), $field_wrap );

			if( @ $args['type'] == 'hidden' )
				$hidden_out .= $this->field( $args, $post );
			else
				$fields_out .= sprintf( $field_wrap, $key .'_meta', $this->field( $args, $post ) );
		}

		echo ( $this->opt->css ? '<style>'. $this->opt->css .'</style>' : '' ) .
			sprintf( (@ $this->opt->fields_wrap ?: '%s'), $fields_out ) .
			$hidden_out .
			'<div class="clear"></div>';
	}

	/**
	 * Выводит отдельные мета поля
	 * @param  string $name Атрибут name
	 * @param  array  $args Параметры поля
	 * @param  object $post Объект текущего поста
	 * @return string HTML код
	 */
	function field( $args, $post ){
		$def = array(
			'type'        => '', // тип поля: 'text', 'textarea', 'select', 'checkbox', 'radio', 'wp_editor', 'hidden' или другое (email, number). По умолчанию 'text'.
			'title'       => '', // заголовок метаполя
			'desc'        => '', // описание для поля
			'placeholder' => '', // атрибут placeholder
			'id'          => '', // атрибут id. По умолчанию: $this->opt->id .'_'. $key
			'class'       => '', // атрибут class: добавляется в input, textarea, select. Для checkbox, radio в оборачивающий label
			'attr'        => '', // любая строка, будет расположена внутри тега. Для создания атрибутов. Пр: style="width:100%;"
			'val'         => '', // значение по умолчанию, если нет сохраненного.
			'options'     => '', // массив: array('значение'=>'название'). Варианты для select, radio, или аргументы для wp_editor
								 // Для 'checkbox' станет значением атрибута value.
			'callback'    => '', // название функции, которая отвечает за вывод поля.
								 // если указана, то ни один параметр не учитывается и за вывод полностью отвечает указанная функция.
								 // Все параметры передаются ей... Получит параметры:
								 // $args - все параметры указанные тут
								 // $post - объект WP_Post текущей записи
								 // $name - название атрибута 'name' (название полей собираются в массив)
								 // $val - атрибут 'value' текущего поля
			// служебные
			'key'         => '', // Обязательный! Автоматический
			'title_patt'  => '', // Обязательный! Автоматический
			'field_patt'  => '', // Обязательный! Автоматический
			'desc_patt'   => '', // Обязательный! Автоматический
		);

		$rg = (object) array_merge( $def, $args );
		//extract( $args );

		if( ! $rg->type )
			$rg->type = 'text';

		$meta_key = $this->__key_prefix() . $rg->key;
		$meta_val = get_post_meta( $post->ID, $meta_key, true );

		$rg->val = $meta_val ?: $rg->val;
		$name = $this->id . "_meta[$meta_key]";

		$pholder = $rg->placeholder ? ' placeholder="'. esc_attr($rg->placeholder) .'"' : '';
		$rg->id      = $rg->id ?: ($this->opt->id .'_'. $rg->key);

		// при табличной теме, td заголовка должен выводиться всегда!
		if( false !== strpos($rg->title_patt, '<td ') )
			$_title   = sprintf( $rg->title_patt, $rg->title ) . ($rg->title ? ' ' : '');
		else
			$_title   = $rg->title ? sprintf( $rg->title_patt, $rg->title ).' ' : '';

		$rg->options = (array) $rg->options;

		$_class = $rg->class ? ' class="'. $rg->class .'"' : '';

		$out = '';

		$fn__desc = function() use( $rg ){
			return $rg->desc ? sprintf( $rg->desc_patt, $rg->desc ) : '';
		};
		$fn__field = function($field) use( $rg ){
			return sprintf( $rg->field_patt, $field );
		};

		// произвольная функция...
		if( is_callable( $rg->callback ) ){
			$out .= call_user_func_array( $rg->callback, array( $args, $post, $name, $rg->val ) );
		}
		// wp_editor
		elseif( $rg->type == 'wp_editor' ){
			$ed_args = array_merge( array(
				'textarea_name'    => $name, //нужно указывать!
				'editor_class'     => $rg->class,
				// изменяемое
				'wpautop'          => 1,
				'textarea_rows'    => 5,
				'tabindex'         => null,
				'editor_css'       => '',
				'teeny'            => 0,
				'dfw'              => 0,
				'tinymce'          => 1,
				'quicktags'        => 1,
				'media_buttons'    => false,
				'drag_drop_upload' => false,
			), $rg->options );

			ob_start();
			wp_editor( $rg->val, $rg->id, $ed_args );
			$wp_editor = ob_get_clean();

			$out .= $_title . $fn__field( $wp_editor . $fn__desc() );
		}
		// textarea
		elseif( $rg->type == 'textarea' ){
			$_style = (false === strpos($rg->attr,'style=')) ? ' style="width:98%;"' : '';
			$out .= $_title . $fn__field('<textarea '. $rg->attr . $_class . $pholder . $_style .'  id="'. $rg->id .'" name="'. $name .'">'. esc_textarea($rg->val) .'</textarea>'. $fn__desc() );
		}
		// select
		elseif( $rg->type == 'select' ){
			$is_assoc = ( array_keys($rg->options) !== range(0, count($rg->options) - 1) ); // ассоциативный или нет?
			$_options = array();
			foreach( $rg->options as $v => $l ){
				$_val = $is_assoc ? $v : $l;
				$_options[] = '<option value="'. esc_attr($_val) .'" '. selected($rg->val, $_val, 0) .'>'. $l .'</option>';
			}

			$out .= $_title . $fn__field('<select '. $rg->attr . $_class .' id="'. $rg->id .'" name="'. $name .'">' . implode("\n", $_options ) . '</select>' . $fn__desc() );
		}
		// checkbox
		elseif( $rg->type == 'checkbox' ){
			$out .= $_title . $fn__field('
			<label '. $rg->attr . $_class .'>
				<input type="hidden" name="'. $name .'" value="">
				<input type="checkbox" id="'. $rg->id .'" name="'. $name .'" value="'. esc_attr(reset($rg->options) ?: 1) .'" '. checked( $rg->val, (reset($rg->options) ?: 1), 0) .'>
				'.( $rg->desc ?: '' ).'
			</label>');
		}
		// radio
		elseif( $rg->type == 'radio' ){
			$radios = array();
			foreach( $rg->options as $v => $l )
				$radios[] = '<label '. $rg->attr . $_class .'><input type="radio" name="'. $name .'" value="'. $v .'" '. checked($rg->val, $v, 0) .'>'. $l .'</label> ';

			$out .= $_title . $fn__field('<span class="radios">'. implode("\n", $radios ) .'</span>'. $fn__desc() );
		}
		// hidden
		elseif( $rg->type == 'hidden' ){
			$out .= '<input type="'. $rg->type .'" id="'. $rg->id .'" name="'. $name .'" value="'. esc_attr($rg->val) .'" title="'. esc_attr($rg->title) .'">';
		}
		// text
		else {
			$_style = ($rg->type==='text' && false===strpos($rg->attr,'style=')) ? ' style="width:100%;"' : '';

			$out .= $_title . $fn__field('<input '. $rg->attr . $_class  . $pholder . $_style .' type="'. $rg->type .'" id="'. $rg->id .'" name="'. $name .'" value="'. esc_attr($rg->val) .'">'. $fn__desc() );
		}

		return $out;
	}

	/**
	 * Сохраняем данные, при сохранении поста
	 * @param  integer $post_id ID записи
	 * @return boolean  false если проверка не пройдена
	 */
	function meta_box_save( $post_id, $post ){
		if(
			! ( $metas = @ $_POST["{$this->id}_meta"] )                                                || // нет данных
			( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  )                                           || // выходим, если это автосохр.
			! wp_verify_nonce( $_POST['_wpnonce'], 'update-post_'. $post_id )                          || // nonce проверка
			( $this->opt->post_type && ! in_array( $post->post_type, (array) $this->opt->post_type ) ) || // не подходящий тип записи
			! current_user_can( get_post_type_object( $post->post_type )->cap->edit_post, $post_id )      // нет права редакт. запись
		)
			return;

		// Оставим только поля текущего класса (защиты от подмены поля)
		$__key_prefix = $this->__key_prefix();
		$fields = array_map( function($key)use($__key_prefix){  return $__key_prefix . $key;  }, array_keys($this->opt->fields) );
		$metas  = array_intersect_key( $metas, array_flip($fields) );

		// Очистка
		if(1){
			// своя функция очистки
			if( is_callable($this->opt->save_sanitize) ){
				$metas = call_user_func_array( $this->opt->save_sanitize, array( $metas, $post_id ) );
				$sanitized = true;
			}
			// хук очистки
			if( has_filter("kpmb_save_sanitize_{$this->opt->id}") ){
				$metas = apply_filters("kpmb_save_sanitize_{$this->opt->id}", $metas, $post_id );
				$sanitized = true;
			}
			// если нет функции и хука очистки, то чистим все поля с помощью wp_kses() или sanitize_text_field()
			if( ! isset($sanitized) ){
				array_walk_recursive( $metas, function( & $val, $key ){
					// has tags
					if( false !== strpos($val, '<') )
						$val = addslashes( wp_kses( stripslashes( $val ), 'post' ) ); // default ?
					else
						$val = sanitize_text_field( $val );
				} );
			}
		}

		// Сохраняем
		foreach( $metas as $key => $val ){
			// удаляем поле, если значение пустое. 0 остается...
			if( ! $val && ($val !== '0') )
				delete_post_meta( $post_id, $key );
			else
				update_post_meta( $post_id, $key, $val ); // add_post_meta() работает автоматически
		}
	}

	function __postbox_classes_add( $classes ){
		$classes[] = "kama_meta_box_{$this->opt->id}";
		return $classes;
	}

	function __key_prefix(){
		return ($this->opt->id{0} == '_') ? '' : $this->opt->id .'_';
	}

}
