<?php

class WebMFT_SEO_Admin extends WebMFT_SEO {

	function __construct(){
		parent::__construct();
        $this->options = ($opt = get_option($this->option_name))? $opt : $this->def_opt();

		add_action('admin_menu', array(&$this, 'add_options_page'));
		add_action('admin_init', array(&$this, 'register_webmft_settings') );
        add_action ('save_post', array(&$this, 'guid_write'), 100);

        add_filter('plugin_action_links_'. MFT_BASE, array( &$this, 'settings_link' ));
    }

    function guid_write( $id ){
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE  ) return false; // если это автосохранение

        global $wpdb;

        if( $id = (int) $id )
            $wpdb->query("UPDATE $wpdb->posts SET guid='". get_permalink($id) ."' WHERE ID=$id LIMIT 1");
    }

	function settings_link($links){
		array_unshift( $links, '<a href="'.admin_url('admin.php?page=webmft_seo').'">'.__('Settings', 'webmft') .'</a>' );
		return $links;
	}

	function add_options_page(){
		add_menu_page( 'WebMFT: SEO', 'WebMFT: SEO', 'manage_options', 'webmft_seo', array(&$this, 'options_page_output'), 'dashicons-shield', 6);
	}

	function options_page_output(){
		?>
		<div class="wrap">
			<h1>WebMFT: SEO useful</h1>
            <h3>Настройки</h3>

            <form method="post" action="options.php" class="js-webmft-form">
				<?php
				settings_fields('webmft_settings');  // скрытые защитные поля
				?>

                <h2 class="nav-tab-wrapper webmft-tab-wrapper js-tab-wrapper">
                    <a class="nav-tab nav-tab-active" id="postview-tab" href="#top#postview">Post viewes</a>
                    <a class="nav-tab" id="postmeta-tab" href="#top#postmeta">Post Meta & Title</a>
                    <a class="nav-tab" id="noindex-tab" href="#top#noindex">Noindex</a>
                    <a class="nav-tab" id="analytics-tab" href="#top#analytics">Analytic`s</a>
                    <a class="nav-tab" id="hidelinks-tab" href="#top#hidelinks">GoTo</a>
                    <a class="nav-tab" id="extlinks-tab" href="#top#extlinks">Ext Links</a>
                    <a class="nav-tab" id="extposts-tab" href="#top#nupopposts">Widget on Front</a>
                    <a class="nav-tab" id="extidpost-tab" href="#top#nupopidposts">Id Posts</a>
                </h2>
                <?php
                submit_button();
                ?>
                <div id="postview" class="wp-webmft-tab js-tab-item active">
                    <h3>Post viewes</h3>
                    <div class="form-group">
	                    <label for="postview_is">
	                        <?php $this->display_checkbox('postview_is') ?>
	                        	Gloabal Postview is active?
	                    </label>
                	</div>
                    <div class="form-group">
	                    <label for="postview_who_count">
                        	Whose visit count? <sup class="webmft-recommend">Рекомендовано</sup>
	                    </label>
                        <?php
						$tmpA = array('all'=>__('All','webmft'),
							'not_logged_users'=>__('Only not logged users','webmft'),
							'logged_users'=>__('Only logged users','webmft'),
							'not_administrators'=>__('All, except administrators','webmft'));
                        $this->display_select('postview_who_count', $tmpA);
                        ?>
                    </div>
                    <div class="form-group">
                        <label for="postview_hold_sec">Delay in seconds</label>
                        <?php $this->display_input_number('postview_hold_sec', 1, 1, 10) ?>
                        <p class="form-text">How many seconds to delay and then count visit?</p>
                    </div>
                </div>
                <div id="postmeta" class="wp-webmft-tab js-tab-item">
                    <h3>Post Meta & Title</h3>
                    <div class="form-group">
                        <label for="postmeta_is">
                            <?php $this->display_checkbox('postmeta_is') ?>
                                Gloabal Postmeta is active?
                        </label>
                    </div>
                    <div class="row">
                        <div class="col-md-5">
                        <h4>Categories Meta</h4>
                        <?
                        $myterms = get_terms('category', 'orderby=count&hide_empty=0');
                        foreach ($myterms as $key => $value) {
                            $catTitle = 'category_'.$value->slug.'_title';
                            $catDescr = 'category_'.$value->slug.'_description';
                            echo '<h4>'.$value->name.'</h4>';
                            echo '<div class="form-group"><label for="'.$catTitle.'">Title for '.$value->name.'</label>';
                            $this->display_input_text($catTitle);
                            echo '</div>';

                            echo '<div class="form-group"><label for="'.$catDescr.'">Description for '.$value->name.'</label>';
                            $this->display_input_text($catDescr);
                            echo '</div>';
                            echo '<hr>';
                        }
                        ?>
                        </div>
                        <div class="col-md-5">
                            <h4>Meta & Title for Front page</h4>
                            <div class="form-group">
                                <label for="postmeta_front_title">Title</label>
                                <?php $this->display_input_text('postmeta_front_title') ?>
                            </div>
                            <div class="form-group">
                                <label for="postmeta_front_description">Description</label>
                                <?php $this->display_input_text('postmeta_front_description') ?>
                            </div>
                            <div class="form-group">
                                <label for="postmeta_front_keywords">Keywords</label>
                                <?php $this->display_input_text('postmeta_front_keywords') ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="noindex" class="wp-webmft-tab js-tab-item">
                    <h3>Noindex Settings</h3>
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label for="noindex_tax">
                                    <?php $this->display_checkbox('noindex_tax') ?>
                                    Use noindex for Tax?
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="noindex_category">
                                    <?php $this->display_checkbox('noindex_category') ?>
                                    Use noindex for Categories?
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="noindex_archive_date">
                                    <?php $this->display_checkbox('noindex_archive_date') ?>
                                    Use noindex for Date Archives?
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="noindex_archive_author">
                                    <?php $this->display_checkbox('noindex_archive_author') ?>
                                    Use noindex for Author Archives?
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="noindex_tags">
                                    <?php $this->display_checkbox('noindex_tags') ?>
                                    Use noindex for Tag Archives?
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="noindex_search">
                                    <?php $this->display_checkbox('noindex_search') ?>
                                    Use noindex for the Search page?
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="noindex_404">
                                    <?php $this->display_checkbox('noindex_404') ?>
                                    Use noindex for the 404 page?
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="noindex_paginated">
                                    <?php $this->display_checkbox('noindex_paginated') ?>
                                    Use noindex for paginated pages/posts?
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="nofollow_paginated">
                                    <?php $this->display_checkbox('nofollow_paginated') ?>
                                    Use nofollow for paginated pages/posts?
                                </label>
                            </div>
                        </div>
                        <div class="col-md-5">
                        </div>
                    </div>
                </div>
                <div id="analytics" class="wp-webmft-tab js-tab-item">
                    <h3>Analytic`s</h3>
                    <div class="row">
                        <div class="col-md-5">
                            <h4>Yandex Metrica</h4>
                            <div class="form-group">
                                <label for="analytics_yandex_is">
                                    <?php $this->display_checkbox('analytics_yandex_is') ?>
                                        Gloabal Yandex Metrica is active?
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="analytics_yandex_id">Yandex Metrica ID</label>
                                <?php $this->display_input_text('analytics_yandex_id') ?>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h4>PIWIK Metrica</h4>
                            <div class="form-group">
                                <label for="analytics_piwik_is">
                                    <?php $this->display_checkbox('analytics_piwik_is') ?>
                                        PIWIK Metrica is active?
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="analytics_piwik_id">Local or Gloabal PIWIK ID</label>
                                <?php $this->display_input_text('analytics_piwik_id') ?>
                            </div>
                            <div class="form-group">
                                <label for="analytics_piwik_url_track">URL track</label>
                                <?php $this->display_input_text('analytics_piwik_url_track') ?>
                                <p class="form-text">Example: //site.com/piwik/</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="hidelinks" class="wp-webmft-tab js-tab-item">
                    <div class="row">
                        <div class="col-md-5">
                            <h4>Setup Links</h4>
                           
                            <div class="form-group">
                                <label for="goto_provider_def">Link Default</label>
                                <?php $this->display_input_text('goto_provider_def') ?>
                            </div>
                            <div class="form-group">
                                <label for="goto_provider_1">Link #1</label>
                                <?php $this->display_input_text('goto_provider_1') ?>
                            </div>
                            <div class="form-group">
                                <label for="goto_provider_2">Link #2</label>
                                <?php $this->display_input_text('goto_provider_2') ?>
                            </div>
                            <div class="form-group">
                                <label for="goto_provider_3">Link #3</label>
                                <?php $this->display_input_text('goto_provider_3') ?>
                            </div>
                            <div class="form-group">
                                <label for="goto_provider_4">Link #4</label>
                                <?php $this->display_input_text('goto_provider_4') ?>
                            </div>
                            <div class="form-group">
                                <label for="goto_provider_5">Link #5</label>
                                <?php $this->display_input_text('goto_provider_5') ?>
                            </div>
                            <div class="form-group">
                                <label for="goto_provider_6">Link #6</label>
                                <?php $this->display_input_text('goto_provider_6') ?>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h4>Setup GoTo</h4>
                            <div class="form-group">
                                <label for="goto_setup_link">Router Link</label>
                                <?php $this->display_input_text('goto_setup_link') ?>
                                <p class="form-text">Default var 'goto' => Example: '/goto/1/'</p>
                                <?php
                                if ('' == $this->options['goto_setup_link']) $goto_setup_link = 'goto';
                                else $goto_setup_link = $this->options['goto_setup_link'];
                                ?>
                                <p class="form-text">Now your GoTo Links is: '/<?php echo $goto_setup_link;?>/1/'</p>
                            </div>
                        </div>
                    </div>
                </div>
				<div id="extlinks" class="wp-webmft-tab js-tab-item">
                    <div class="row">
                        <div class="col-md-5">
                            <h4>Setup Links</h4>
                            <div class="form-group">
                                <label for="extlinks_is">
                                    <?php $this->display_checkbox('extlinks_is') ?>
                                        External Links is active?
                                </label>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h4>Setup CSS</h4>
                            <p class="form-text">You need customize .btn and .btn-success</p>
                            <div class="form-group">
                                <label for="extlinks_custom_css">Custom CSS</label>
                                <?php $this->display_textarea('extlinks_custom_css') ?>
                            </div>
                        </div>
                    </div>
				</div>
                <div id="extposts" class="wp-webmft-tab js-tab-item">
                    <div class="row">
                    <div class="col-md-3">
                            <h4>Переключить язик рейтинга на русский</h4>
                            <div class="form-group">
                                <label for="extposts_yazik_retings">
                                    <?php $this->display_checkbox('extposts_yazik_retings') ?>
                                    по умолчанию английский
                                    <p>(Рейтинг: N из 5) N = просмотров</p>
                                    <p>(Rating: N out of 5) N = viewss</p>
                                </label>
                            </div>
                        </div>
                    <div class="col-md-3">
                            <h4>Переключить язик мета-тегах на русский</h4>
                            <div class="form-group">
                                <label for="extposts_yazik_meta_retings">
                                    <?php $this->display_checkbox('extposts_yazik_meta_retings') ?>
                                    по умолчанию английский
                                </label>
                                <p>- page N / - страница N</p>
                            </div>
                        </div>

                     <div class="col-md-3">
                            <h4>Включение рейтинга постов</h4>
                            <div class="form-group">
                                <label for="extposts_vuvod_reting">
                                    <?php $this->display_checkbox('extposts_vuvod_reting') ?>
                                    Включить/Выключить вывод рейтинга
                                </label>
                            </div>
                        </div>
                     <div class="col-md-10">
                            <h4>Управление выводом блоков</h4>
                            <div class="form-group">
                                <label for="extposts_before_text">
                                    <?php $this->display_checkbox('extposts_before_text') ?> 
                                    Если функция не активна то вывод будет производиться после текста
                                </label>
                            </div>
                        </div>





                         <div class="col-md-2">
                            <h4>C наибольшим количеством просмотров</h4>
                            
                            <div class="form-group">
                                <label for="extposts_casino_reviews">
                                    <?php $this->display_checkbox('extposts_casino_reviews') ?>
                                        Блок "Casino Reviews"
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="extposts_nazanie_h2_before_casino_reviews">Заголовок перед блоком</label>
                                <?php $this->display_input_text('extposts_nazanie_h2_before_casino_reviews') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_nazanie_h2_after_casino_reviews">Заголовок после блока</label>
                                <?php $this->display_input_text('extposts_nazanie_h2_after_casino_reviews') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_kolichestvo_postov_casino_reviews">Количество выводимых постов</label>
                                <?php $this->display_input_text('extposts_kolichestvo_postov_casino_reviews') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_id_postov_one_one">ID категорий для которых нужно вывести посты</label>
                                <?php $this->display_input_text('extposts_id_postov_one_one') ?>
                                ID записывать через запятую
                            </div>
                        </div>






                        <div class="col-md-2">
                            <h4>C наибольшим количеством просмотров</h4>
                            <div class="form-group">
                                <label for="extposts_featured_slot_machines">
                                    <?php $this->display_checkbox('extposts_featured_slot_machines') ?>
                                        Блок "Featured Slot Machines"
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="extposts_nazanie_h2_before_featured">Заголовок перед блоком</label>
                                <?php $this->display_input_text('extposts_nazanie_h2_before_featured') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_nazanie_h2_after_featured">Заголовок после блока</label>
                                <?php $this->display_input_text('extposts_nazanie_h2_after_featured') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_kolichestvo_postov">Количество выводимых постов</label>
                                <?php $this->display_input_text('extposts_kolichestvo_postov') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_id_postov_one">ID категорий для которых нужно вывести посты</label>
                                <?php $this->display_input_text('extposts_id_postov_one') ?>
                                ID записывать через запятую
                            </div>
                        </div>
                        <div class="col-md-3">
                            <h4>Cамые новые</h4>
                            <div class="form-group">
                                <label for="extposts_all_slots">
                                    <?php $this->display_checkbox('extposts_all_slots') ?>
                                        Блок "All Slots Reviews"
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="extposts_nazanie_h2_all_slots">Заголовок перед блоком</label>
                                <?php $this->display_input_text('extposts_nazanie_h2_all_slots') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_nazanie_h2_all_slots_after">Заголовок после блока</label>
                                <?php $this->display_input_text('extposts_nazanie_h2_all_slots_after') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_kolichestvo_postov_two_blok">Количество выводимых постов</label>
                                <?php $this->display_input_text('extposts_kolichestvo_postov_two_blok') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_id_categories_2_blok">ID категорий для которых нужно вывести посты</label>
                                <?php $this->display_input_text('extposts_id_categories_2_blok') ?>
                                ID записывать через запятую
                            </div>
                        </div>
                        <div class="col-md-3">
                            <h4>Cамые новые</h4>
                            <div class="form-group">
                                <label for="extposts_gamblink_news">
                                    <?php $this->display_checkbox('extposts_gamblink_news') ?>
                                        Блок "Gambling news"
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="extposts_nazanie_h2_gamblink_news">Заголовок перед блоком</label>
                                <?php $this->display_input_text('extposts_nazanie_h2_gamblink_news') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_nazanie_h2_gamblink_news_after">Заголовок после блока</label>
                                <?php $this->display_input_text('extposts_nazanie_h2_gamblink_news_after') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_kolichestvo_postov_news_blok">Количество выводимых постов</label>
                                <?php $this->display_input_text('extposts_kolichestvo_postov_news_blok') ?>
                            </div>
                            <div class="form-group">
                                <label for="extposts_id_categories_3_blok">ID категорий для которых нужно вывести посты</label>
                                <?php $this->display_input_text('extposts_id_categories_3_blok') ?>
                                ID записывать через запятую
                            </div>
                        </div>
                             <div class="col-md-10">
                                <h4>Categories Name</h4>
                                <?
                                $myterms = get_terms('category', 'orderby=count&hide_empty=0');
                                foreach ($myterms as $key => $value) {
                                    $catNameBefore = 'category_'.$value->slug.'_name_before';
                                    $catNameAfter = 'category_'.$value->slug.'_name_after';
                                    $catVklname = 'category_'.$value->slug.'_catVklname';
                                    $catVklPaginasia = 'category_'.$value->slug.'_catVklPaginasia';
                                    $catVklPaginasiaMeta = 'category_'.$value->slug.'_catVklPaginasiaMeta';
                                    echo '<h4>'.$value->name.'</h4>';
                                    echo '<div class="form-group"><label for="'.$catNameBefore.'">Текст до</label>';
                                    $this->display_input_text($catNameBefore);
                                    echo '</div>';

                                    echo '<div class="form-group"><label for="'.$catNameAfter.'">Текст после</label>';
                                    $this->display_input_text($catNameAfter);
                                    echo '</div>';

                                    echo '<div class="form-group"><label for="'.$catVklname.'">';
                                    $this->display_checkbox($catVklname);
                                    echo ' Включение изменения названий</label></div>';

                                    echo '<div class="form-group"><label for="'.$catVklPaginasia.'">';
                                    $this->display_checkbox($catVklPaginasia);
                                    echo ' Включить добавление приставки - page</label></div>';

                                     echo '<div class="form-group"><label for="'.$catVklPaginasiaMeta.'">';
                                    $this->display_checkbox($catVklPaginasiaMeta);
                                    echo ' Включить добавление приставки - page в мета-теги</label></div>';
                                  
                                    echo '<hr>';
                                }
                                ?>
                        </div>
                       <div class="col-md-5">
                            <h4>Выбор цвета для кнопки</h4>
                                <div class="form-group">
                                <label for="goto_dasdasd">goto_dasdasd: <?php echo $this->options['goto_dasdasd'];?> </label>
                                <?php $this->display_input_text('goto_dasdasd', 'color') ?>
                            </div>
                            <a href="#" class="myButton" style="background-color:<?php echo $this->options['goto_dasdasd'];?>;">green</a>
                       </div>
                    </div>
                </div>






                <div id="extidpost" class="wp-webmft-tab js-tab-item">
                    <div class="row">
                        <div class="col-md-10">
                            <h2>Вывод постов по ID</h2>
                            <div class="form-group">
                                <label for="extposts_ids_posts">
                                    <?php $this->display_checkbox('extposts_ids_posts') ?>
                                    Активировать вывод блоков
                                </label>
                            </div>
                        </div>    
                        <div class="col-md-3">
                            <img  src="http://best7casino.us/wp-content/uploads/2016/11/one_block.jpg" class="img_one_admin_block">
                        </div>
                        <div class="col-md-3" style="top: 100px;">    
                           <div class="form-group">
                                <label for="extposts_ids_posts_one_style">
                                    <?php $this->display_checkbox('extposts_ids_posts_one_style') ?>
                                    Включить блок
                                </label>
                            </div>
                            <div class="form-group">
                                <?php $this->display_input_text('extposts_id_nyhnovo_posta') ?>
                                <label for="extposts_id_nyhnovo_posta">ID постов которые нужно вывести, записывать через запятую</label>
                            </div>
                        </div>
                        <div class="col-md-10">
                        <hr>
                        </div>
                         <div class="col-md-3">
                            <img  src="http://best7casino.us/wp-content/uploads/2016/11/block_two.jpg" class="img_one_admin_block">
                        </div>
                        <div class="col-md-3">    
                             <div class="form-group">
                                <label for="extposts_ids_posts_two_style">
                                    <?php $this->display_checkbox('extposts_ids_posts_two_style') ?>
                                    Включить блок
                                </label>
                            </div>
                            <div class="form-group">
                            <?php $this->display_input_text('extposts_id_nyhnovo_posta') ?>
                            <label for="extposts_id_nyhnovo_posta">ID постов которые нужно вывести, записывать через запятую</label>
                            </div>
                        </div>
                         <div class="col-md-10">
                         <hr>
                        </div>
                         <div class="col-md-3">
                            <img  src="http://best7casino.us/wp-content/uploads/2016/11/block_three.jpg" style="width: 150px;padding-left: 131px;" class="img_one_admin_block">
                        </div>
                        <div class="col-md-3">    
                             <div class="form-group">
                                <label for="extposts_ids_posts_three_style">
                                    <?php $this->display_checkbox('extposts_ids_posts_three_style') ?>
                                    Включить блок
                                </label>
                            </div>
                            <div class="form-group">
                            <?php $this->display_input_text('extposts_id_nyhnovo_posta') ?>
                            <label for="extposts_id_nyhnovo_posta">ID постов которые нужно вывести, записывать через запятую</label>
                            </div>
                        </div>
                        <div class="col-md-10">
                        <hr>
                        </div>
                         <div class="col-md-3">
                            <img  src="http://best7casino.us/wp-content/uploads/2016/11/block_four.jpg"  class="img_one_admin_block">
                        </div>
                        <div class="col-md-3">    
                             <div class="form-group">
                                <label for="extposts_ids_posts_four_style">
                                    <?php $this->display_checkbox('extposts_ids_posts_four_style') ?>
                                    Включить блок
                                </label>
                            </div>
                            <div class="form-group">
                            <?php $this->display_input_text('extposts_id_nyhnovo_posta') ?>
                            <label for="extposts_id_nyhnovo_posta">ID постов которые нужно вывести, записывать через запятую</label>
                            </div>
                        </div>
                        <div class="col-md-10">
                        <hr>
                        </div>
                         <div class="col-md-3">
                            <img  src="http://best7casino.us/wp-content/uploads/2016/11/block_sixten.jpg" style="width: 231px;padding-left: 75px;" class="img_one_admin_block">
                        </div>
                        <div class="col-md-3">    
                             <div class="form-group">
                                <label for="extposts_ids_posts_five_style">
                                    <?php $this->display_checkbox('extposts_ids_posts_five_style') ?>
                                    Включить блок
                                </label>
                            </div>
                            <div class="form-group">
                            <?php $this->display_input_text('extposts_id_nyhnovo_posta') ?>
                            <label for="extposts_id_nyhnovo_posta">ID постов которые нужно вывести, записывать через запятую</label>
                            </div>
                        </div>
                        <div class="col-md-10">
                        <p>http://verybitcoinslotsgambling.xyz/</p>
                        <p>http://casinoideal.me/</p>
                        <p>http://takeslotscasino.us/</p>
                        <p>http://bitcasino-reviews.com/</p>
                        </div>
                   </div>
                </div>











                
				<?php
				submit_button();

				?>
			</form>
		</div>
		<?php 
	}

    /**
     * Display option checkbox
     *
     * @param string $name
     */
    public function display_checkbox( $name ) {
        $checked = '';
        if (isset($this->options[$name]) && $this->options[$name] == 'on') $checked = ' checked';
        $string = '<input name="'.$this->option_name.'['.$name.']" type="checkbox" id="'.$name.'" value="on"'. $checked .'>';
        echo $string;
    }

    /**
     * Display input text field
     *
     * @param string $name
     */
    public function display_input_text( $name, $type = 'text' ) {
        $value = '';
        if (isset($this->options[$name]) && ! empty($this->options[$name])) $value = $this->options[$name];
        $string = '<input name="'.$this->option_name.'['.$name.']" type="'.$type.'" id="'.$name.'" value="'. $value .'"" class="form-control">';
        echo $string;
    }

    /**
     * Display textarea field
     *
     * @param string $name
     */
    public function display_textarea( $name ) {
        $value = '';
        if (isset($this->options[$name]) && ! empty($this->options[$name])) $value = $this->options[$name];
        $string = '<textarea name="'.$this->option_name.'['.$name .']" id="'.$name.'" class="form-control" rows="7" autocomplete="off">'.$value.'</textarea>';
        echo $string;
    }

    /**
     * Display input number field
     *
     * @param $name
     * @param $step
     * @param $min
     * @param $max
     */
    public function display_input_number( $name , $step = '', $min = '', $max = '' ) {
        $value = '';
        if (isset($this->options[$name]) && ! empty($this->options[$name])) $value = $this->options[$name];
        $string  = '<input name="'.$this->option_name.'['.$name.']" type="number" ';
        if (!empty($step)) $string .= 'step="'. $step .'" ';
        if (!empty($min) || $min === 0)  $string .= 'min="'. $min .'"  ';
        if (!empty($max))  $string .= 'max="'. $max .'" ';
        $string .= 'id="'.$name.'" value="'. $value .'"" class="form-control">';
        echo $string;
    }

    /**
     * Display select
     *
     * @param string $name
     * @param array $values
     */
    public function display_select( $name , $values ) {
        if (isset($this->options[$name]) && ! empty($this->options[$name])) $value = $this->options[$name];
        $string  = '<select class="form-control" name="'.$this->option_name.'['.$name.']" id="'.$name.'">';

        if (is_array( $values )) {
            foreach ($values as $key => $value) {
                $selected = '';
                if (isset($this->options[$name]) && $this->options[$name] == $key) $selected = ' selected';

                $string .= '<option value="'.$key.'"'. $selected .'>'.$value.'</option>';
            }
        }

        $string .= '</select>';
        echo $string;
    }

    /**
     * Register settings
     */
    public function register_webmft_settings() {
        register_setting( 'webmft_settings', $this->option_name, array( $this, 'sanitize_webmft_options' ) );
    }

    public function sanitize_webmft_options( $options ) {
        return $options;
    }
    
}
?>
