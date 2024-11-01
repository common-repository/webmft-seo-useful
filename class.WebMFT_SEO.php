<?php


class WebMFT_SEO {

	public $meta_key = 'views';

	protected $option_name = 'webmft_option';
    protected $options;
	protected static $inst;

	static function init(){
		if (is_null(self::$inst)) self::$inst = is_admin() ? new WebMFT_SEO_Admin() : new self;
		return self::$inst;
	}

	function __construct(){
        global $wp_query, $posts;

		$this->plugin_name = 'webmft-seo-useful';
		$this->options     = ($opt = get_option($this->option_name))? $opt : $this->def_opt();

		add_action('wp_head', 'webmft_seo');
		add_filter('widget_text', 'do_shortcode');
		add_theme_support('title-tag');

		if ( !is_admin() ){
			if (isset($this->options['postview_is'])){
				add_action('wp_enqueue_scripts', create_function('','wp_enqueue_script("jquery");'));
				add_action('wp_footer', array( $this, 'show_js'), 99);
			}

			if (isset($this->options['postmeta_is'])){
				add_filter('pre_get_document_title', array (&$this, 'header_title'));
				add_action('wp_head', array( $this, 'header_meta'), 1);
				add_action('wp_head', array( $this, 'header_noindex'), 1);
			}

			if (isset($this->options['analytics_yandex_is'])){
				add_action( 'wp_footer', array( $this, 'analytics_yandex' ) );
			}
			if (isset($this->options['analytics_piwik_is'])){
				add_action( 'wp_footer', array( $this, 'analytics_piwik' ) );
			}
			/**
			 * Connecting jQuery to create animation of the first block
			 */
			add_action( 'wp_footer', array( $this, 'script_jquery' ), 99 );
			/**
			 * Add most viewed in Front page $content
			 */
			add_action( 'the_content', array( $this, 'most_viewed_in_fontpage' ) );
			
			/**
			 * Edit название категории при выводе
			 */
			add_filter( 'get_the_archive_title', array( $this, 'name_categories') );
			// add_action( 'get_the_archive_title', array( $this, 'name_categories' ) );
			add_filter('single_cat_title', array( $this, 'name_categories'));
			//add_filter('get_the_archive_title', array( $this, 'name_categories'));
			//add_filter('the_archive_titile', array( $this, 'name_categories_acrhive'));
			


			/**
			 * Add external link to $content
			 */

			if (isset($this->options['extlinks_is'])){
				add_filter('the_content', array( $this, 'add_link_to_content'));
				add_action('wp_head', array( $this, 'add_link_to_content_css'), 99);
			}

			/**
			 * Activate GoTo
			 */
			register_activation_hook(__FILE__, array( $this, 'webmft_goto_activate'));
			register_deactivation_hook(__FILE__, array( $this, 'webmft_goto_deactivate'));
			add_action('init', array( $this, 'webmft_goto_rules'));
			add_filter('query_vars', array( $this, 'webmft_goto_query_vars'));
			add_filter('template_redirect', array( $this, 'webmft_goto_display'));
		}

		remove_action('wp_head', 'wp_print_scripts');
		add_action('wp_footer', 'wp_print_scripts', 5);
		remove_action('wp_head', 'wp_print_head_scripts', 9);
		add_action('wp_footer', 'wp_print_head_scripts', 5);
		// remove_action('wp_head', 'wp_enqueue_scripts', 1);
		// add_action('wp_footer', 'wp_enqueue_scripts', 5);

		add_action('widgets_init', array (&$this, 'register_webmft_widgets'));

        /**
         * Add css and js files
         */
    	// add_action('wp_enqueue_scripts', array( $this, 'enqueue_site_styles') );
		if (is_admin()){
    		add_action('admin_enqueue_scripts', array( $this, 'enqueue_admin_styles') );
        	add_action('admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts') );
		}

	}

	function analytics_yandex() {
		if (!empty($this->options['analytics_yandex_id']) )
			echo '<!-- Yandex.Metrika counter --> <script type="text/javascript"> (function (d, w, c) { (w[c] = w[c] || []).push(function() { try { w.yaCounter'.$this->options['analytics_yandex_id'].' = new Ya.Metrika({ id:'.$this->options['analytics_yandex_id'].', clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:true }); } catch(e) { } }); var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () { n.parentNode.insertBefore(s, n); }; s.type = "text/javascript"; s.async = true; s.src = "https://mc.yandex.ru/metrika/watch.js"; if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); } })(document, window, "yandex_metrika_callbacks"); </script> <noscript><div><img src="https://mc.yandex.ru/watch/'.$this->options['analytics_yandex_id'].'" style="position:absolute; left:-9999px;" alt="" /></div></noscript> <!-- /Yandex.Metrika counter -->';
	}
	function analytics_piwik() {
		if (!empty($this->options['analytics_piwik_id']) )
			echo '<!-- Piwik --> <script type="text/javascript">var _paq = _paq || [];_paq.push(["trackPageView"]);_paq.push(["enableLinkTracking"]);(function(){var u="'.$this->options['analytics_piwik_url_track'].'";_paq.push(["setTrackerUrl", u+"piwik.php"]);_paq.push(["setSiteId", "'.$this->options['analytics_piwik_id'].'"]);var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0];g.type="text/javascript"; g.async=true; g.defer=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);})();</script><noscript><p><img src="'.$this->options['analytics_piwik_url_track'].'piwik.php?idsite='.$this->options['analytics_piwik_id'].'" style="border:0;" alt="" /></p></noscript><!-- End Piwik Code -->';
	}
	function script_jquery() {
			echo '<script>jQuery(document).ready(function(){jQuery(".srchicon").click(function(){jQuery(".searchtop").toggle(),jQuery(".topsocial").toggle()}),jQuery(document).ready(function(){jQuery(".do-open-info").on("click",function(){return jQuery(this).parent().parent().parent().addClass("card--show-back"),!1}),jQuery(".do-close-info").on("click",function(){return jQuery(this).parent().parent().parent().removeClass("card--show-back"),!1})})});</script>';
}
	function def_opt(){
		return array(
			'goto_provider_def' => 'http://bit.ly/2eUjbaA',
			'goto_provider_1' => 'https://goo.gl/ZAdFfO',
			'postview_is' => 'on',
			'postview_who_count' => 'all',
			'postview_hold_sec' => 2,
		);
	}
//function name_categories_achive($text){ //изменение названия категории
 //   $text = apply_filters( 'single_term_title', $term );
 //    return $text;
 // }
	function name_categories($text){ //изменение названия категории
		 $text = apply_filters( 'single_term_title', $term );
    // return $text;
		$pageNumsss=(get_query_var('paged')) ? get_query_var('paged') : 1;
		$thisNameCats = get_category(get_query_var('cat'),false);
		$mvs_titl_befor = $this->options['category_'.$thisNameCats->slug.'_name_before']. ' ';
		$mvs_titl_after = ' '.$this->options['category_'.$thisNameCats->slug.'_name_after'];
		$catVklName = $this->options['category_'.$thisNameCats->slug.'_catVklname'];
		$catVklPag = $this->options['category_'.$thisNameCats->slug.'_catVklPaginasia'];
		$term = get_queried_object()->name;
		$text = apply_filters( 'single_term_title', $term );
			if (!empty($this->options['category_'.$thisNameCats->slug.'_catVklname']) ) {
				if (is_category()) 	

					if (!empty($this->options['category_'.$thisNameCats->slug.'_catVklPaginasia']) ) {
						if ($pageNumsss>1) {
							if (!empty($this->options['extposts_yazik_meta_retings']) ) {
								$page_navigsss = ' - страница '.$pageNumsss;
							} else {
								$page_navigsss = ' - page '.$pageNumsss;
							}
							return $text = $mvs_titl_befor.$text.$mvs_titl_after.$page_navigsss;
						} else {
							return $text = $mvs_titl_befor.$text.$mvs_titl_after;
						}
					} else {
					return $text = $mvs_titl_befor.$text.$mvs_titl_after;	}
				} else {
				 return $text;
				}
	}
	
	function register_webmft_widgets() {
		register_widget('WEBMFT_PostMostViewed_Widget');
		register_widget('WEBMFT_PostNext_Widget');
		register_widget('WEBMFT_PostPrev_Widget');
	}

	function show_js(){
		// allow manage script show. In the filter maybe you need to set custom $wp_query->queried_object
		$force_show = apply_filters('webmft_seo_postviews_force_show_js', false);

		if (!$force_show){
			if (is_attachment() || is_front_page()) return;
			if (!( is_singular() || is_tax() || is_category() || is_tag())) return;
		}

		$should_count = 0;
		switch ($this->options['postview_who_count']) {
			case 'all': $should_count = 1;
				break;
			case 'not_logged_users':
				if (!is_user_logged_in())
					$should_count = 1;
				break;
			case 'logged_users':
				if (is_user_logged_in())
					$should_count = 1;
				break;
			case 'not_administrators':
				if (!current_user_can('manage_options'))
					$should_count = 1;
				break;
			default : $should_count = 0;
		}

		if (!$should_count) return;

		global $post, $wpdb;

		$queri = get_queried_object();

		// post
		if (isset($queri->post_type) && isset($post->ID)){
			$view_type = 'post_view';

			$_sql = $wpdb->prepare("SELECT meta_id, meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s LIMIT 1", $post->ID, $this->meta_key );

			// create if not exists
			if (!$row = $wpdb->get_row($_sql)){
				if (add_post_meta($post->ID, $this->meta_key, '0', true))
					$row = $wpdb->get_row($_sql);
			}
		} elseif (isset($queri->term_id) && $wpdb->termmeta){
			$view_type = 'term_view';

			$_sql = $wpdb->prepare("SELECT meta_id, meta_value FROM $wpdb->termmeta WHERE term_id = %d AND meta_key = %s LIMIT 1", $queri->term_id, $this->meta_key);

			// create if not exists
			if (!$row = $wpdb->get_row($_sql)){
				if (add_term_meta($queri->term_id, $this->meta_key, '0', true))
					$row = $wpdb->get_row($_sql);
			}
		}

		if (!isset($view_type) || ! $row) return;

		$relpath = '';

		ob_start();
		?>
		<script>setTimeout(function(){
			jQuery.post(
				'<?php echo MFT_URL . 'ajax-request.php' ?>',
				{meta_id:'<?php echo $row->meta_id ?>', view_type:'<?php echo $view_type ?>', relpath:'<?php echo $relpath ?>'},
				function(result){jQuery('.ajax_views').html(result);}
			);
		}, <?php echo ($this->options['postview_hold_sec'] * 1000) ?>);
		</script>
		<?php
		$script = apply_filters('webmft_seo_postviews_script', ob_get_clean());

		echo preg_replace('~[\r\n\t]~', '', $script)."\n";

		do_action('after_webmft_seo_postviews_show_js');
	}

	function header_title(){
		global $post;

		if (is_home() && is_front_page()){
			$mv_titl = $this->options['postmeta_front_title'];

		} elseif(is_category()) {
			$thisCat = get_category(get_query_var('cat'),false);
			$mv_titl = $this->options['category_'.$thisCat->slug.'_title'];
				
					if (!empty($this->options['category_'.$thisCat->slug.'_catVklPaginasiaMeta']) ) {
						$pageNum=(get_query_var('paged')) ? get_query_var('paged') : 1;
						if ($pageNum>1) 
						$page_navig = ' - page '.$pageNum;
						$mv_titl = $mv_titl.$page_navig;
					} else {
						return $mv_titl;
					}
			} else {
				$mv_titl = get_post_meta($post->ID, '_webmft_title', true);
			}
		return $mv_titl;

	}
	function header_meta(){
		global $post;

		if(is_front_page()){
			$mv_desc = $this->options['postmeta_front_description'];
			$mv_keys = $this->options['postmeta_front_keywords'];
		
		} elseif( is_category()) {  //добавление номер страницы пагинации в мета-теги
			$thisCat = get_category(get_query_var('cat'),false);
			$mv_desc = $this->options['category_'.$thisCat->slug.'_description'];
				if (!empty($this->options['category_'.$thisCat->slug.'_catVklPaginasiaMeta']) ) 
				$pageNum=(get_query_var('paged')) ? get_query_var('paged') : 1;
				if ($pageNum>1) 
					if (!empty($this->options['extposts_yazik_meta_retings']) ) {
						$page_navig = ' - страница '.$pageNum;
					} else {
						$page_navig = ' - page '.$pageNum;
					}
					
				$mv_desc = $mv_desc.$page_navig;

		} else {
			$mv_desc = get_post_meta($post->ID, '_webmft_description', true);
			$mv_keys = get_post_meta($post->ID, '_webmft_keywords', true);
			//$thisCat = get_category(get_query_var('cat'),false);
			//$mv_desc = $this->options['category_'.$thisCat->slug.'_description'];
		}
		echo '<meta name="description" content="'.$mv_desc.'">'."\n";
		echo '<meta name="keywords" content="'.$mv_keys.'">'."\n";
	}
	function header_noindex(){
		global $post;

		$robots_meta = apply_filters( 'webmft_robots_meta', $this->get_robots_meta() );
		// echo "\n".'!!!-'.$robots_meta.'-!!!'."\n";
		if (!empty($robots_meta)) {
			$meta_string .= '<meta name="robots" content="' . esc_attr( $robots_meta ) . '" />' . "\n";
		}

		$prev_next = $this->get_prev_next_links( $post );
		$prev      = apply_filters( 'webmft_prev_link', $prev_next['prev'] );
		$next      = apply_filters( 'webmft_next_link', $prev_next['next'] );
		if ( ! empty( $prev ) ) {
			$meta_string .= "<link rel='prev' href='" . esc_url( $prev ) . "' />\n";
		}
		if ( ! empty( $next ) ) {
			$meta_string .= "<link rel='next' href='" . esc_url( $next ) . "' />\n";
		}
		if ( $meta_string != null ) {
			echo "$meta_string\n";
		}
	}

	function robots_custom(){
		global $post;

		if($_SERVER['REQUEST_URI']=='/robots.txt'){
			require($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');

			/*
			add_filter('robots_txt', 'add_robotstxt');
			function add_robotstxt($text){
				$text .= "Disallow: *\/comments2222222222";
				return $text;
			}
			*/
			do_robots();

			exit;
		}
	}

    /**
     * Register the Stylesheets for the admin area
     * Register the JavaScript for the admin area
     *
     */
     public function enqueue_admin_stylessssss() {
       wp_enqueue_style($this->plugin_name, MFT_URL . 'inc/css/webmft-admin-seo-new.css', array(), $this->version, 'all');
    }
    public function enqueue_admin_styles() {
        wp_enqueue_style($this->plugin_name, MFT_URL . 'inc/css/webmft-admin-seo.css', array(), $this->version, 'all');
        //wp_enqueue_style($this->plugin_name.'-new', MFT_URL . 'inc/css/webmft-admin-seo-new.css', array(), $this->version, 'all');
        wp_enqueue_style($this->plugin_name.'-new', MFT_URL . 'inc/css/webmft-admin-seo-new.css', array(), '1.1', 'all');
    }
	public function enqueue_admin_scripts() {
        wp_enqueue_script($this->plugin_name, MFT_URL . 'inc/js/webmft-admin-seo.js', array('jquery'), $this->version, false);
    }

    /**
     * Register the Stylesheets for the site
     * Register the JavaScript for the site
     *
     */
    public function enqueue_site_styles() {
        // wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'inc/css/webmft-site-seo.css', array(), $this->version, 'all');
    }
	public function enqueue_site_scripts() {
        wp_enqueue_script($this->plugin_name, MFT_URL . 'inc/js/webmft-site-seo.js', array('jquery'), $this->version, false);
    }

	function get_robots_meta() {
		$webmft_option = $this->options;
		$page          = $this->get_page_number();
		$robots_meta   = $tax_noindex = '';
		if ( isset( $webmft_option['noindex_tax'] ) ) {
			$tax_noindex = $webmft_option['noindex_tax'];
		}

		if ( empty( $tax_noindex ) || ! is_array( $tax_noindex ) ) {
			$tax_noindex = array();
		}

		$noindex       = 'index';
		$nofollow      = 'follow';
		if ( ( is_category() && ! empty( $webmft_option['noindex_category'] ) )
			|| ( ! is_category() && is_archive() && ! is_tag() && ! is_tax() && ( ( is_date() && ! empty( $webmft_option['noindex_archive_date'] ) ) || ( is_author() && ! empty( $webmft_option['noindex_archive_author'] ) ) ) )
		     || ( is_tag() && ! empty( $webmft_option['noindex_tags'] ) )
		     || ( is_search() && ! empty( $webmft_option['noindex_search'] ) )
		     || ( is_404() && ! empty( $webmft_option['noindex_404'] ) )
		     || ( is_tax() && in_array( get_query_var( 'taxonomy' ), $tax_noindex ) )
		) {
			$noindex = 'noindex';
		} elseif ( is_single() || is_page() || $this->is_static_posts_page() || is_attachment() || is_category() || is_tag() || is_tax() || ($page>1) ) {

			$post_type = get_post_type();

			if ( (!empty($webmft_option['noindex_paginated'])) && $page > 1 ) {
				$noindex = 'noindex';
			}
			if ( (!empty($webmft_option['nofollow_paginated'])) && $page > 1 ) {
				$nofollow = 'nofollow';
			}
		}
		$robots_meta = $noindex . ', ' . $nofollow;
		if ( $robots_meta == 'index, follow' ) {
			$robots_meta = '';
		}

		return $robots_meta;
	}

	/**
	 * Wrapper for substr() - uses mb_substr() if possible.
	 */
	function substr( $string, $start = 0, $length = 2147483647 ) {
		$args = func_get_args();
		if ( function_exists( 'mb_substr' ) ) {
			return call_user_func_array( 'mb_substr', $args );
		}

		return call_user_func_array( 'substr', $args );
	}
	function get_page_number() {
		$page = get_query_var( 'page' );
		if ( empty( $page ) ) {
			$page = get_query_var( 'paged' );
		}

		return $page;
	}
	/**
	 * @return null|object|WP_Post
	 */
	function get_queried_object() {
		static $p = null;
		global $wp_query, $post;
		if ( null !== $p ) {
			return $p;
		}
		if ( is_object( $post ) ) {
			$p = $post;
		} else {
			if ( ! $wp_query ) {
				return null;
			}
			$p = $wp_query->get_queried_object();
		}

		return $p;
	}
	/**
	 * @return bool|null
	 */
	function is_static_posts_page() {
		static $is_posts_page = null;
		if ( $is_posts_page !== null ) {
			return $is_posts_page;
		}
		$post          = $this->get_queried_object();
		$is_posts_page = ( get_option( 'show_on_front' ) == 'page' && is_home() && ! empty( $post ) && $post->ID == get_option( 'page_for_posts' ) );

		return $is_posts_page;
	}
	/**
	 * @param $link
	 *
	 * @return string
	 */
	function get_paged( $link ) {
		global $wp_rewrite;
		$page      = $this->get_page_number();
		$page_name = 'page';
		if ( ! empty( $wp_rewrite ) && ! empty( $wp_rewrite->pagination_base ) ) {
			$page_name = $wp_rewrite->pagination_base;
		}
		if ( ! empty( $page ) && $page > 1 ) {
			if ( $page == get_query_var( 'page' ) ) {
				$link = trailingslashit( $link ) . "$page";
			} else {
				$link = trailingslashit( $link ) . trailingslashit( $page_name ) . $page;
			}
			$link = user_trailingslashit( $link, 'paged' );
		}

		return $link;
	}
	/**
	 * @param null $post
	 *
	 * @return array
	 */
	function get_prev_next_links( $post = null ) {
		$prev = $next = '';
		$page = $this->get_page_number();
		if ( is_home() || is_archive() || is_paged() ) {
			global $wp_query;
			$max_page = $wp_query->max_num_pages;
			if ( $page > 1 ) {
				$prev = get_previous_posts_page_link();
			}
			if ( $page < $max_page ) {
				$paged = $GLOBALS['paged'];
				if ( ! is_single() ) {
					if ( ! $paged ) {
						$paged = 1;
					}
					$nextpage = intval( $paged ) + 1;
					if ( ! $max_page || $max_page >= $nextpage ) {
						$next = get_pagenum_link( $nextpage );
					}
				}
			}
		} else if ( is_page() || is_single() ) {
			$numpages  = 1;
			$multipage = 0;
			$page      = get_query_var( 'page' );
			if ( ! $page ) {
				$page = 1;
			}
			if ( is_single() || is_page() || is_feed() ) {
				$more = 1;
			}
			$content = $post->post_content;
			if ( false !== strpos( $content, '<!--nextpage-->' ) ) {
				if ( $page > 1 ) {
					$more = 1;
				}
				$content = str_replace( "\n<!--nextpage-->\n", '<!--nextpage-->', $content );
				$content = str_replace( "\n<!--nextpage-->", '<!--nextpage-->', $content );
				$content = str_replace( "<!--nextpage-->\n", '<!--nextpage-->', $content );
				// Ignore nextpage at the beginning of the content.
				if ( 0 === strpos( $content, '<!--nextpage-->' ) ) {
					$content = substr( $content, 15 );
				}
				$pages    = explode( '<!--nextpage-->', $content );
				$numpages = count( $pages );
				if ( $numpages > 1 ) {
					$multipage = 1;
				}
			}
			if ( ! empty( $page ) ) {
				if ( $page > 1 ) {
					$prev = _wp_link_page( $page - 1 );
				}
				if ( $page + 1 <= $numpages ) {
					$next = _wp_link_page( $page + 1 );
				}
			}
			if ( ! empty( $prev ) ) {
				$prev = $this->substr( $prev, 9, - 2 );
			}
			if ( ! empty( $next ) ) {
				$next = $this->substr( $next, 9, - 2 );
			}
		}

		return array( 'prev' => $prev, 'next' => $next );
	}


function most_post_id($text) {
		if (is_front_page()){
				//$id_post = 2690;
				$post_id = get_post( 2690, ARRAY_A);
				$title = $post_id['post_title'];

				$thumb = get_the_post_thumbnail( 2690, 'thumbnail' );
				$echos = '<div class="feffew"><a href="#">'.$thumb.'</a><p>'.$title.'</p></div>';
		    	$text = $echos.$text;
		    	echo $text;
		}
	    return $text;
	}
	/**
	 * most_viewed_in_fontpage   добавление на главную страницу последних и популярных записей
	 */
	function most_viewed_in_fontpage($text) {
		if (is_front_page()){
				$id_post = $this->options['extposts_id_nyhnovo_posta'];//айди нужно поста
				$id_id_post = explode(",", $id_post);
				foreach ($id_id_post as $id_post) {
					$post_id = get_post( $id_post, ARRAY_A);
					$title = $post_id['post_title']; //название поста
					$conte = $post_id['post_content'];//берем текст поста
					$conte = strip_tags($conte);//удаляем html теги
					$length=30;
					$postfix='...';
				    if ( strlen($conte) <= $length){
				        return $conte;
				    }
				    $temp = substr($conte, 0, $length);
				    $contez =  substr($temp, 0, strrpos($temp, ' ') ) . $postfix;
					$length_two=100;
					$postfix='...';
				    if ( strlen($conte) <= $length_two){
				        return $conte;
				    }
				    $temps = substr($conte, 0, $length_two);
				    $contezq =  substr($temps, 0, strrpos($temps, ' ') ) . $postfix;
					$numbersl = get_post_meta ($id_post,'views',true);
    				$numral = $numbersl / 100 * 100;
    				$numretigl = $numral /10;
					if ( $numretigl >= 10 ){
						$numretigl = 10;
					} else {
						$numretigl;
					}
						//echo "<pre>";
						//print_r($conte);
						//echo "</pre>";
					$thumb = get_the_post_thumbnail( $id_post, array(90,90) ); //миниатюра
					$url = get_permalink($id_post); //урл поста
					if (!empty($this->options['extposts_ids_posts_one_style']) ) {
					$echosl .= '<li class="card-list__item"><div class="card"><div class="card__front"><span class="card__open-info do-open-info"><i class="fa fa-toggle-on" aria-hidden="true"></i></span><div class="card__media card__media--rating card__media--casino"><a href="'.$url.'">'.$thumb.'</a><div 
					class="card__ratingq ratingq"><div class="starq-ratingq"><span style="width: '.$numral.'%;"></span></div><span class="starq-ratingq--after">'.$numretigl.'</span></div></div><div class="card__desc"><h3 class="card__desc-title card__desc-title--clip"><span class="title">'.$title.'</span></h3><p class="card__status card__status--mobile"><span class="certified">Certified</span></p><p class="card__desc-body">'.$contez.'<p class="card__status card__status--with-action"><i class="fa fa-check-square-o" aria-hidden="true"></i>Certified</p></div><div class="card__action"><a href="/goto/1/" target="_blank">Visit</a></div></div><div class="card__back"><span class="card__close-info do-close-info"><i class="fa fa-toggle-off" aria-hidden="true"></i></span><p class="card__desc-title card__desc-title--clip">'.$title.'</p><p class="card__desc-body">'.$contezq.'</p><a href="/goto/1/" target="_blank" class="btn btn--cta card__desc-btn" style="color: #fff; padding-top: 0;">Play now</a></div></div></li>';
						}


					if (!empty($this->options['extposts_ids_posts_two_style']) ) {
					$echosl_1 .= '<li class="games-list__item"><div class="game-box " style="width:184px;height:145px;"><span class="imageloader loading game-box__img-holder">'.$thumb.'</span><div class="game-box__action-content"><div class="game-box__align-content"><h3 class="game-box__title">'.$title.'</h3><div class="game-box__holder"><a style="color: #000000;" class="button button_style_success button_size_m" href="#"><span class="button__text">Play</span></a></div><div class="game-box__demo-holder"><a class="game-box__pseudo-link" rel="nofollow" href="'.$url.'">Review</a></div></div></div></div></li>';
					}
					if (!empty($this->options['extposts_ids_posts_three_style']) ) {
					$echosl_2 .= '<div class="casino-box"> <div class="thumbnail"> <a href="/goto/1/" target="_blank"> '.$thumb.'<a href="/goto/1/" target="_blank"><button class="btn-tocasino btn-orange">'.$title.'</button>  </a>  </a></div></div>';
					}
					if (!empty($this->options['extposts_ids_posts_four_style']) ) {
						$thumb = get_the_post_thumbnail( $id_post, array(150,140) );
					$echosl_3 .= '<div class="mrg-slots-cards"><a href="'.$url.'"><div class="mrg-slot-card-img lazy"><p class="pabz">'.$thumb.'</p><div class="mrg-slot-info"><h1 class="myh1">'.$title.'</h1></div></div></a><div class="mrg-slot-play-btn"><a href="'.$url.'">Review</a></div></div>';
					}
					if (!empty($this->options['extposts_ids_posts_five_style']) ) {
						$thumb = get_the_post_thumbnail( $id_post, array(150,140) );
					$echosl_4 .= '<div class="ben_1" style="height: 201px;" ><a href="/goto/1/" target="_blank"><div class="class_imf">'.$thumb.'</div></a><div class="button_k"><a href="'.$url.'" style="text-decoration: none; line-height: 35px;"><center style="color: rgba(89, 94, 99, 0.91);">'.$title.'</center> </a></div></div>';
					}
    




	






				}//foreach
				if (!empty($this->options['extposts_ids_posts']) ) {
					if (!empty($this->options['extposts_ids_posts_one_style']) ) {
						$echosl = '<ul class="card-list group">'.$echosl.'</ul>';
					}
					if (!empty($this->options['extposts_ids_posts_two_style']) ) {
						$echosl_1 = '<ul class="games-list">'.$echosl_1.'</ul>';
					}
					if (!empty($this->options['extposts_ids_posts_three_style']) ) {
						$echosl_2 = '<div class="loop-container cf">'.$echosl_2.'</div>';
					}
					if (!empty($this->options['extposts_ids_posts_four_style']) ) {
						$echosl_3 = '<div class="post-most-viewed">'.$echosl_3.'</div>';
					}
					if (!empty($this->options['extposts_ids_posts_four_style']) ) {
						$echosl_4 = '<div class="static">'.$echosl_4.'</div>';
					}
					$text = $text.$echosl.$echosl_1.$echosl_2.$echosl_3.$echosl_4;
					}


				include 'include/seo-block-posts.php';
				if (!empty($this->options['extposts_casino_reviews']) ) {
		  			$text_1_2 = '<h2>'.$this->options['extposts_nazanie_h2_before_casino_reviews'].'</h2>';
		  			$text_1_1_2 ='<h2>'.$this->options['extposts_nazanie_h2_after_casino_reviews'].'</h2>';
		  			$number_blok_one = $this->options['extposts_kolichestvo_postov_casino_reviews'];
		  			$number_id_cat_one_one = $this->options['extposts_id_postov_one_one'];
		  			$rec_copy_text = WEBMFT_PostMostViewed_Widget::widget(['format'=>2], ['num'=>$number_blok_one, 'echo'=>1, 'cat'=>$number_id_cat_one_one]);
				}
		  		if (!empty($this->options['extposts_all_slots']) ) {
		  			$text_2 = '<h2>'.$this->options['extposts_nazanie_h2_all_slots'].'</h2>';
		  			$text_2_1 ='<h2>'.$this->options['extposts_nazanie_h2_all_slots_after'].'</h2>';
		  			$number_blok_two = $this->options['extposts_kolichestvo_postov_two_blok'];
		  			$number_id_cat_two = $this->options["extposts_id_categories_2_blok"];
		  			$rec_text = WEBMFT_PostNext_Widget::kama_recent_posts($number_blok_two, '', $number_id_cat_two);
		  		}
		  		if (!empty($this->options['extposts_gamblink_news']) ) {
		  			$text_3 = '<h2>'.$this->options['extposts_nazanie_h2_gamblink_news'].'</h2>';
		  			$text_3_1 ='<h2>'.$this->options['extposts_nazanie_h2_gamblink_news_after'].'</h2>';
		  			$number_blok_three = $this->options['extposts_kolichestvo_postov_news_blok'];
		  			$number_id_cat_three = $this->options['extposts_id_categories_3_blok'];
		  			$recs_text = WEBMFT_PostNext_Widget::kama_recent_posts($number_blok_three, '', $number_id_cat_three);
		  		}		  		
		  		if (!empty($this->options['extposts_before_text']) ) {
		  			$text = $text_1_2.$rec_copy_text.$text_1_1_2.$text_1.$tmp_text.$text_1_1.$text_2.$rec_text.$text_2_1.$text_3.$recs_text.$text_3_1.$text;
		  		} else {
		    		$text = $text.$text_1_2.$rec_copy_text.$text_1_1_2.$text_1.$tmp_text.$text_1_1.$text_2.$rec_text.$text_2_1.$text_3.$recs_text.$text_3_1;
		  		}
		}
	    return $text;
	}
	/**
	 * most_viewed_in_posts   добавление на главную страницу последних и популярных записей
	 */
	




	/**
	 * webmft_GoTo
	 */
	function webmft_goto_activate() {
	    webmft_goto_rules();
	    flush_rewrite_rules();
	}

	function webmft_goto_deactivate() {
	    flush_rewrite_rules();
	}

	function webmft_goto_rules() {
		if ('' == $this->options['goto_setup_link']) $goto_setup_link = 'goto';
		else $goto_setup_link = $this->options['goto_setup_link'];
	    add_rewrite_rule(''.$goto_setup_link.'/?([^/]*)', 'index.php?pagename='.$goto_setup_link.'&provider=$matches[1]', 'top');
	}

	function webmft_goto_query_vars($vars) {
	    $vars[] = 'provider';
	    return $vars;
	}

	function webmft_goto_display() {
		if ('' == $this->options['goto_setup_link']) $goto_setup_link = 'goto';
		else $goto_setup_link = $this->options['goto_setup_link'];

	    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
	    $urlTemp = parse_url($url, PHP_URL_PATH);
	    $urlArr = explode('/', $urlTemp);
	    $cust_page = $urlArr[1];
	    $provider = $urlArr[2];
	    if ($goto_setup_link == $cust_page && '' == $provider):
	    	echo '<script>window.location.replace("'.$this->options['goto_provider_def'].'");</script>';
	        exit;
	    elseif ($goto_setup_link == $cust_page && '' != $provider):
	    	echo '<script>window.location.replace("'.$this->options['goto_provider_'.$provider].'");</script>';
	        exit;
	    endif;
	}

	function add_link_to_content($text) {
	    global $post;
	    $numbers = get_post_meta ($post->ID,'views',true);
    	$numra = $numbers / 100 * 100;
    	//$numretig = $numra / 10;
    	//$numretig = round( $numretig, 1, PHP_ROUND_HALF_UP);
    		$numretig = ($numra / 2)/10;
			if ( $numretig >= 5 ){
				$numretig = 5;
			} else {
				$numretig;
			}
	    	if (!empty($this->options['extposts_yazik_retings']) ) {
				$languages = '(Рейтинг: '.$numretig .' из 5)';	
				$viewss = ' просмотров';
			} else {
				$languages = '(Rating: '.$numretig .' out of 5)';
				$viewss = ' views';
			}
    	$tmpl_text = '<div class="card__ratingz ratingz"><div class="star-ratingz"><span style="width: '. $numra.'%;"></span></div><span class="star-ratingz--after">
    	'.$languages.'</span></div><p style="font-size: 17px;margin-top: 5px;
    	font-style: italic;">'.get_post_meta ($post->ID,'views',true).$viewss.'</p>';
    	$tmpl_text = preg_replace('/(<h1.+?>)/iu','$1'.$tmpl_text.'', $text, 1); 
		if ('' == $this->options['goto_setup_link']) $goto_setup_link = 'goto';
		else $goto_setup_link = $this->options['goto_setup_link'];
	    $content_title = apply_filters( 'single_post_title', $post->post_title, $post);
	   // $current_category = single_cat_title('', false);
	    $term = get_queried_object()->name;
		$current_category = apply_filters( 'single_term_title', $term );
	    if (get_post_type($post->ID) == 'post') {
	        if (in_category($current_category)) {
	        //if (in_category( )) {
	            return $text;
	        } elseif (in_category('News')) {
	        		if (!empty($this->options['extposts_vuvod_reting']) ) {
	        			$text = $tmpl_text;
	        		return  $text;
	        		} else {
	           	return  $text;}
	        } else {	 
	            if (!empty($this->options['extposts_vuvod_reting']) ) {
	            	$tmpl_text = preg_replace('/(<img.+?>)/iu','<ul class="grid cs-style-3"><li><figure>$1<figcaption><h3>'. $content_title .'</h3><a href="/'.$goto_setup_link.'/1/" target="_blank">Play for real money</a></figcaption></figure></li></ul>', $tmpl_text, 1);
	                 	echo $tmpl_text.'<div><a href="/'.$goto_setup_link.'/1/" target="_blank" class="play-for" >Play '.$content_title.' for real money</a></div>';
	            } else {
	            	 $text = preg_replace('/(<img.+?>)/iu','<ul class="grid cs-style-3"><li><figure>$1<figcaption><h3>'. $content_title .'</h3><a href="/'.$goto_setup_link.'/1/"target="_blank">Play for real money</a></figcaption></figure></li></ul>', $text, 1);
	            	echo $text.'<div><a href="/'.$goto_setup_link.'/1/" target="_blank" class="play-for">Play '.$content_title.' for real money</a></div>';
	            }
	        }
	    } else {
	        return $text;  
	    }
	}







	function add_link_to_content_css() {

echo '<style>
.class_imf img {
    left: 21%!important;
    top: 5%!important;
}
.loop-container {
    display: block;
    overflow: hidden;
}
.clearfix, .cf {
    zoom: 1;
}

   

.clearfix:before, .clearfix:after, .cf:before, .comment-respond:before, .cf:after, .comment-respond:after {
    content: "";
    display: table;
}
.casino-box {
	 width: 22.4%;
    display: block;
    float: left;
        position: relative;
    margin-right: 2%;
    margin-bottom: 1em;
    padding: 5px;
    background: #7ea6e3;
}
.casino-box .thumbnail {
    margin-bottom: 0px;
}
.thumbnail {
    font-size: 0;
    text-align: center;
}
.thumbnail {
    display: block;
    padding: 4px;
    margin-bottom: 20px;
    line-height: 1.42857143;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    -webkit-transition: all .2s ease-in-out;
    -o-transition: all .2s ease-in-out;
    transition: all .2s ease-in-out;
}
.thumbnail>img, .thumbnail a>img {
    margin-left: auto;
    margin-right: auto;
}
.img-responsive, .thumbnail>img, .thumbnail a>img, .carousel-inner>.item>img, .carousel-inner>.item>a>img {
    display: block;
    width: 100% \9;
    max-width: 100%;
    height: auto;
}
.thumbnail img {
    width: 100%;
    height: auto;
    border-radius: 5px;
}
.casino-box img, .game-box img {
    width: 100%;
    height: auto;
}
@media only screen and (min-width: 480px)
.home .button {
    width: 100%;
    text-align: center;
}
@media only screen and (min-width: 320px)
.casino-box .button {
    font-size: .625em;
}
@media only screen and (min-width: 480px)
.home .btn-orange {
    font-size: 15px;
    width: 100%;
}
.gbox, .nbox, .btn-orange, .btn-grey, .casino-box, .game-box {
    -webkit-border-radius: 5px;
    -moz-border-radius: 5px;
    border-radius: 5px;
}
 .btn-orange {
    position: absolute;
    bottom: 11px;
    line-height: 1;
    left: 10px;
    padding-top: 3px;
    padding-bottom: 1px;
    background: #f6b848;
    background: -moz-linear-gradient(top,#f6b848 0,#f5b134 100%);
    background: -webkit-gradient(linear,left top,left bottom,color-stop(0%,#f6b848),color-stop(100%,#f5b134));
    background: -webkit-linear-gradient(top,#f6b848 0,#f5b134 100%);
    background: -o-linear-gradient(top,#f6b848 0,#f5b134 100%);
    background: -ms-linear-gradient(top,#f6b848 0,#f5b134 100%);
    background: linear-gradient(to bottom,#f6b848 0,#f5b134 100%);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#f6b848",endColorstr="#f5b134",GradientType=0);
    -webkit-box-shadow: 0 2px 0 0 rgba(164,119,31,1);
    -moz-box-shadow: 0 2px 0 0 rgba(164,119,31,1);
    box-shadow: 0 2px 0 0 rgba(164,119,31,1);
    width: 89%;
    text-shadow: 0 0 3px #3a2601;
    border: 1px solid #c08716;
}
button, input[type="button"], input[type="reset"], input[type="submit"] {
    border: none;
    background: #fa5742;
}











.games-list__item{vertical-align:top;display:inline-block;margin-bottom:1px;margin-left:1px;list-style-type:none}.games-list{margin:0;padding:0;font-size:0}.game-box{position:relative}.game-box:hover .game-box__action-content{display:block}.slick-loading .slick-track{visibility:hidden}.slick-slide.slick-loading img{display:none}.slick-loading .slick-slide{visibility:hidden}.game-box__img-holder{display:block}.game-box__action-content{display:none;overflow:hidden;position:absolute;top:0;right:0;bottom:0;left:0;text-align:center;white-space:nowrap;font-size:0;background-color:hsla(0,0%,100%,.89)}.game-box__action-content:before{content:"";vertical-align:middle;display:inline-block;height:100%}.game-box__align-content{vertical-align:middle;display:inline-block;white-space:normal}.game-box__title{font-family:helveticaneuecyr-thin,Arial,sans-serif;font-size:20px;line-height:1.25;color:#020021;overflow:hidden;max-height:50px;margin:5px 20px 20px;font-weight:400;letter-spacing:.25px}.game-box__holder{margin:0}.button_style_success{background-color:#03a528;border-color:#03a528;color:#fff;display:block;height:30px;margin:0 15px}.button_style_success:hover{border-color:#03a528;background-color:#0FD53D;color:#fff}.button_style_success1{background-color:#03a528;border-color:#03a528;color:#fff}.button_style_success1:hover{border-color:#03a528;background-color:#00B72A;color:green}.button_size_m{border-width:3px;border-radius:22px}.button_size_m:before{height:39px}.button_size_m .button__text{margin-right:47px;margin-left:47px;margin-top:-1px;font-size:15px}.button_style_system{background-color:#202021;border-color:#0083c3;color:#0083c3}.button_style_system:hover{border-color:#0083c3;background-color:#0083c3;color:#fff}.button_size_s{font-family:Arial,Tahoma,sans-serif;border-radius:9pt}.button_size_s:before{height:22px}.button_size_s .button__text{font-size:11px;text-transform:uppercase}.button_size_l{border-width:3px;border-radius:30px}.button_size_l:before{height:53px}.button_size_l .button__text{margin-right:67px;margin-left:67px;font-size:1pc}.button_size_xl{border-width:3px;border-radius:35px}.button_size_xl:before{height:61px}.button_size_xl .button__text{margin-right:77px;margin-left:77px;font-size:18px}.button_size_xxl{border-width:3px;border-radius:45px}.button_size_xxl:before{height:78px}.button_size_xxl .button__text{margin-right:105px;margin-left:105px;font-size:25px}.button_type_full-width{display:block;width:100%}.button_type_full-width .button__text{margin:0}.button__text{vertical-align:middle;display:inline-block;margin-right:20px;margin-left:20px;font-size:13px;line-height:1.25;white-space:normal}.game-box_size_s .game-box__demo-holder,.game-box_size_s .game-box__title{display:none}.game-box__demo-holder{margin:20px 20px 5px}.game-box__pseudo-link{font-family:Arial,Tahoma,sans-serif;font-size:9pt;line-height:1;color:#020021;display:inline-block;border-bottom:1px dotted;text-transform:uppercase;text-decoration:none;cursor:pointer}.game-box__pseudo-link:hover{color:#0083c3}.game-box__img-holder img{height:145px;width:184px}












	
ul.card_list_group{margin:0}li.card-list__item{width:31%;margin-bottom:30px;border:1px solid #cacaca;height:350px;display:inline-block;margin-left:5px;margin-right:5px;vertical-align:top;border-radius:11px}.card_font{height:100%;box-shadow:0 1px 2px 0 rgba(0,0,0,.3);border-radius:4px}.card_back{background:red;top:0;left:0;backface-visibility:visible}.card_1{height:50%;position:relative;border-top-left-radius:11px;border-top-right-radius:11px}.card_2{height:50%;border-bottom-left-radius:11px;border-bottom-right-radius:11px;background-color:#fff}.ahrefahref{position:relative}.fa-info-circle:before{content:"\f05a";vertical-align:text-top;margin-left:170px;font-size:27px;color:rgba(64,64,64,0.56);position:absolute;top:-5px;left:11px}.card__ratingq{margin-top:15px;text-align:center}.starq-ratingq{height:12.5px;position:relative;margin-right:7px;width:156px;display:inline-block}.starq-ratingq:before{background-image:url(http://casinoyour.bid/wp-content/themes/colorist/img/stars-empty.svg)!important}.starq-ratingq:before,.starq-ratingq>span{width:156px;display:block;height:14px;position:absolute}.starq-ratingq:before,.starq-ratingq>span:before{top:0;background-repeat:repeat-x;background-size:cover;left:0;right:0;content:"";bottom:0}.starq_ratingq_afte{font-size:15px;padding-left:11px}.starq-ratingq>span{text-indent:-10000px;overflow:hidden}.starq-ratingq:before,.starq-ratingq>span{width:156px;display:block;height:14px;position:absolute}.starq-ratingq>span:before{background-image:url(http://casinoyour.bid/wp-content/themes/colorist/img/stars-full.svg)!important;display:block;height:14px;position:absolute;text-indent:10000px}.starq_ratingq_after{font-size:14px;padding-left:5px}.text_container{color:rgba(56,56,56,0.46);padding-top:25px;padding-left:20px}.text_container h5{color:rgba(56,56,56,0.71)}.card_status_card_status_with_action{display:inline-block}.abzac_gertified{color:#ff5d5d;font-weight:bolder;margin-left:0;width:100%}*,:after,:before{box-sizing:inherit}body{margin:0;background-color:#f1f1f1;color:#26292D;font-family:ProximaNova,Helvetica,Arial,sans-serif;font-size:16px;line-height:30px}html{box-sizing:border-box}.btn,.write__textarea--editable button{border-radius:4px;height:45px;line-height:51px;font-size:12px;letter-spacing:.15em;font-weight:700;display:inline-block;text-transform:uppercase;border:none}.btn--cta{background:#2396F7;text-align:center}.btn--cta:hover{background-color:#65b6fa;color:#f8f8f8}.card-list{margin:0 -15px}.card__back,.card__front{transition:transform .6s cubic-bezier(.39,.2,.37,1.44)}.card-list__item{float:left;width:20%;min-height:50px;perspective:687px}.card-list__item.filter{opacity:.1}.card,.card__back{border-radius:11px;width:100%}.card{background:0 0;position:relative}.card__front{overflow:hidden;box-shadow:0 1px 2px 0 rgba(0,0,0,.3);border-radius:11px;backface-visibility:hidden;-webkit-backface-visibility:hidden;transform:rotateY(0);position:relative;z-index:2}.card__back{background:#fff;box-shadow:none;backface-visibility:hidden;-webkit-backface-visibility:hidden;transform:rotateY(-180deg);position:absolute;left:0;top:0;height:100%;padding:63px 18px 18px;z-index:1}.card--show-back .card__front{transform:rotateY(180deg);z-index:1;box-shadow:none}.card--show-back .card__back{transform:rotateY(0);z-index:2;box-shadow:0 1px 2px 0 rgba(0,0,0,.3)}.card__close-info,.card__open-info{position:absolute;top:20px;right:20px;opacity:.3;cursor:pointer;background-color:#f8f8f8;border-radius:24px;height:24px;transition:.1s}.card__close-info:hover,.card__open-info:hover{opacity:1;transition:.1s}.card__media{display:block;height:180px;background:#f8f8f8;box-shadow:0 1px 0 #dddee1;padding-top:45px;text-align:center;border-radius:11px 11px 0 0;overflow:hidden;color:#26292D}.card__media img{border-radius:50%}.card__media--rating{padding-top:30px;color:#26292D}.card__desc{padding:1px 18px 0;height:170px;background:#fff}.card__desc-title{font-size:16px;line-height:1.5;margin-top:11px;color:#3C3C3C;margin-bottom:4px;word-break:break-word}.card__desc-body{font-size:14px;line-height:1.64;color:#888;word-break:break-word}.card__action,.card__status{font-size:12px;text-transform:uppercase;letter-spacing:.05em}.card__status{position:absolute;left:20px;color:#3C3C3C;text-overflow:ellipsis;white-space:nowrap;max-width:83%;overflow:hidden;padding-bottom:0;bottom:7px}.card__action,.card__desc-btn{bottom:20px;position:absolute}.card__status.card__status--mobile{display:none}.card__status--with-action{color:#2396f7;max-width:60%}.card__action{right:20px;color:#26292D;transition:.1s}.card__action:hover{opacity:.4;transition:.1s}.card__rating.rating--mobile{display:none}.card__desc-btn{left:20px;right:20px}.rating{font-size:14px}.star-rating{height:14px;position:relative;width:156px}.star-rating--after{padding-left:7px;position:relative;font-size:14px;top:-1px}.star-rating:before,.star-rating>span:before{top:0;background-repeat:repeat-x;background-size:cover;left:0;right:0;content:"";bottom:0}.star-rating--mobile-value{display:none}.star-rating--mobile>span:before{background-repeat:repeat-x;background-size:cover;display:inline-block;content:"";height:13px;width:13px}.star-rating:before,.star-rating>span{width:156px;display:block;height:14px;position:absolute}.star-rating:before{background-image:url(http://casinoyour.bid/wp-content/themes/colorist/img/stars-empty.svg)!important}.star-rating>span{text-indent:-10000px;overflow:hidden}.star-rating>span:before{background-image:url(http://casinoyour.bid/wp-content/themes/colorist/img/stars-full.svg)!important;display:block;height:14px;position:absolute;text-indent:10000px}.fa-toggle-on:before{content:"\f205";font-size:20px}.fa-toggle-off:before{content:"\f204";font-size:20px}

	

</style>';



echo '<style>























.star-ratingz{height:12.5px;position:relative;width:110px;display:inline-block}.star-ratingz:before{background-image:url(http://webitcoinslotsgambling.xyz/wp-content/uploads/primer_2-01.svg)!important}.star-ratingz:before,.star-ratingz>span{width:110px;display:block;height:20px;position:absolute}.star-ratingz:before,.star-ratingz>span:before{top:0;background-repeat:repeat-x;background-size:cover;left:0;right:0;content:"";bottom:0}.star_rating_afte{font-size:15px;padding-left:11px}.star-ratingz>span{text-indent:-10000px;overflow:hidden}.star-ratingz:before,.star-ratingz>span{width:110px;display:block;height:20px;position:absolute}.star-ratingz>span:before{background-image:url(http://webitcoinslotsgambling.xyz/wp-content/uploads/primer_1-01.svg)!important;display:block;height:20px;position:absolute;text-indent:10000px;width:114px}.star_rating_after{font-size:14px;padding-left:5px}.card__ratingz{margin-top:15px}.star-ratingz--after{position:relative;font-size:15px;font-style:italic;top:3px}.static{with:100%}.ben_1,.ben_2,.ben_3{width:31%;height:171px;margin:5px;display:inline-block;position:relative;box-shadow:0 3px 20px rgba(0,0,0,.25),inset 0 2px 0 rgba(255,255,255,.6),0 2px 0 rgba(0,0,0,.1),inset 0 0 20px rgba(0,0,0,.1)}.ben_1:hover,.ben_2:hover,.ben_3:hover{box-shadow:inset 0 0 20px rgba(0,0,0,.2),0 2px 0 rgba(255,255,255,.4),0 2px 0 rgba(0,0,0,.1);-moz-box-shadow:inset 0 0 20px rgba(0,0,0,.2),0 2px 0 rgba(255,255,255,.4),0 2px 0 rgba(0,0,0,.1);-webkit-box-shadow:inset 0 0 20px rgba(0,0,0,.2),0 2px 0 rgba(255,255,255,.4),0 2px 0 rgba(0,0,0,.1);-o-box-shadow:inset 0 0 20px rgba(0,0,0,.2),0 2px 0 rgba(255,255,255,.4),0 2px 0 rgba(0,0,0,.1);-ms-box-shadow:inset 0 0 20px rgba(0,0,0,.2),0 2px 0 rgba(255,255,255,.4),0 2px 0 rgba(0,0,0,.1)}.ben_1 img,.ben_2 img,.ben_3 img{position:absolute;left:15%;top:-7%;border-radius:15px}.ben_1:before,.ben_2:before,.ben_3:before{content:"";position:absolute;width:100%;height:100%}.button_k{height:35px;width:100%;background-color:rgba(191,191,191,0.22);position:absolute;bottom:0;color:#000;font-weight:700}.fa-chevron-right:before{content:"\f054";color:#ff9e00;margin-left:10px;font-size:19px}.ben_2:before{background:rgba(230,230,230,0.48)}.ben_1:before{background:rgba(230,230,230,0.48)}.ben_3:before{background:rgba(230,230,230,0.48)}@media screen and (max-width: 960px){.ben_1 img,.ben_2 img,.ben_3 img{left:8%}}@media screen and (max-width: 620px){.ben_1 img,.ben_2 img,.ben_3 img{left:3%}center{line-height:1}thumbnail.wp-post-image{width:130px;height:130px}.mrg-slot-card-img.lazy{height:188px}.myh1{font-size:14px}.mrg-slot-play-btn a{font-size:21px!important;height:32px;padding-top:1px}}@media screen and (max-width: 520px){.ben_1,.ben_2,.ben_3{width:60%}.attachment-thumbnail.size-thumbnail.wp-post-image{max-width:160px;max-height:160px}.ben_1 img,.ben_2 img,.ben_3 img{left:17%}center{line-height:2;font-size:18px}.mrg-slots-cards{width:45%!important}}@media screen and (max-width: 420px){.attachment-thumbnail.size-thumbnail.wp-post-image{max-width:80%;max-height:80%}.ben_1 img,.ben_2 img,.ben_3 img{left:3%}}@media screen and (max-width: 400px){center{line-height:1}.ben_1 img,.ben_2 img,.ben_3 img{left:7%;top:1px}.mrg-slots-cards{width:70%!important}}@media screen and (max-width: 300px){.ben_1,.ben_2,.ben_3{width:90%}.ben_1 img,.ben_2 img,.ben_3 img{left:10%}.mrg-slots-cards{width:95%!important}h2,.h2{font-size:23px}}.mrg-slots-cards .mrg-slot-info{padding:3px;background:#000;padding:3px;position:absolute;display:block;bottom:0;width:100%}.mrg-slots-cards{width:31%;display:inline-block;margin:5px 5px 15px}.mrg-slot-card-img.lazy{background-color:#000;position:relative;height:220px}.pabz{text-align:center;margin:0;padding:5px 0}.myh1{font-size:17px;margin-top:7px;text-align:center;text-transform:capitalize;font-weight:600}.mrg-slot-play-btn a{display:block;width:100%;height:45px;padding-top:5px;line-height:34px;font-weight:700;text-align:center;background:#ffc01a;text-transform:uppercase;font-size:29px!important;color:#1a1a1a!important;transform-style:preserve-3d;border-bottom-left-radius:5px;border-bottom-right-radius:5px}.mrg-slot-play-btn a:hover{color:#FDFDFD!important;background:#0381E9}.attachment-thumbnail.size-thumbnail.wp-post-image{width:150px;height:150px}</style>';
		echo '<style>.play-for{ -moz-box-shadow:0 10px 14px -7px #276873;-webkit-box-shadow:0 10px 14px -7px #276873;box-shadow:0 10px 14px -7px #276873;background:-webkit-gradient(linear,left top,left bottom,color-stop(.05,#599bb3),color-stop(1,#408c99));background:-moz-linear-gradient(top,#599bb3 5%,#408c99 100%);background:-webkit-linear-gradient(top,#599bb3 5%,#408c99 100%);background:-o-linear-gradient(top,#599bb3 5%,#408c99 100%);background:-ms-linear-gradient(top,#599bb3 5%,#408c99 100%);background:linear-gradient(to bottom,#599bb3 5%,#408c99 100%);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#599bb3\', endColorstr=\'#408c99\', GradientType=0);

		    background-color:#599bb3;-moz-border-radius:8px;-webkit-border-radius:8px;border-radius:8px;display:inline-block;cursor:pointer;color:#fff;font-family:Arial;font-size:15px;font-weight:700;padding:13px 32px;text-decoration:none;text-shadow:0 1px 0 #3d768a}.play-for:hover{background:-webkit-gradient(linear,left top,left bottom,color-stop(.05,#408c99),color-stop(1,#599bb3));background:-moz-linear-gradient(top,#408c99 5%,#599bb3 100%);background:-webkit-linear-gradient(top,#408c99 5%,#599bb3 100%);background:-o-linear-gradient(top,#408c99 5%,#599bb3 100%);background:-ms-linear-gradient(top,#408c99 5%,#599bb3 100%);background:linear-gradient(to bottom,#408c99 5%,#599bb3 100%);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#408c99\', endColorstr=\'#599bb3\', GradientType=0);background-color:#408c99}.play-for:active{position:relative;top:1px}.grid figure,.grid li{position:relative;margin:0}.cs-style-3 figure,.cs-style-4 figure>div{overflow:hidden}.grid{padding:10px 20px 0;max-width:1300px;margin:0 auto;list-style:none;text-align:center}.grid li{display:inline-block;padding:20px;text-align:left}.grid figure img{max-width:100%;display:block;position:relative}.grid figcaption{position:absolute;top:0;left:0;padding:5px 0 0 15px;background:#2c3f52;color:#ed4e6e}.grid figcaption h3{margin:0;padding:0;color:#fff}.grid figcaption span:before{content:"by "}.grid figcaption a{text-align:center;padding:5px 10px;border-radius:2px;display:inline-block;background:#ed4e6e;color:#fff}.cs-style-1 figcaption{height:100%;width:100%;opacity:0;text-align:center;-webkit-backface-visibility:hidden;-moz-backface-visibility:hidden;backface-visibility:hidden;-webkit-transition:-webkit-transform .3s,opacity .3s;-moz-transition:-moz-transform .3s,opacity .3s;transition:transform .3s,opacity .3s}.cs-style-4 figcaption,.cs-style-5 figcaption{-webkit-backface-visibility:hidden;-moz-backface-visibility:hidden}.cs-style-1 figure.cs-hover figcaption,.no-touch .cs-style-1 figure:hover figcaption{opacity:1;-webkit-transform:translate(15px,15px);-moz-transform:translate(15px,15px);-ms-transform:translate(15px,15px);transform:translate(15px,15px)}.cs-style-1 figcaption h3{margin-top:70px}.cs-style-1 figcaption span{display:block}.cs-style-1 figcaption a{margin-top:30px}.cs-style-2 figure img{z-index:10;-webkit-transition:-webkit-transform .4s;-moz-transition:-moz-transform .4s;transition:transform .4s}.cs-style-2 figure.cs-hover img,.no-touch .cs-style-2 figure:hover img{-webkit-transform:translateY(-90px);-moz-transform:translateY(-90px);-ms-transform:translateY(-90px);transform:translateY(-90px)}.cs-style-2 figcaption{height:90px;width:100%;top:auto;bottom:0}.cs-style-2 figcaption a{position:absolute;right:20px;top:30px}.cs-style-3 figure img{-webkit-transition:-webkit-transform .4s;-moz-transition:-moz-transform .4s;transition:transform .4s}.cs-style-3 figure.cs-hover img,.no-touch .cs-style-3 figure:hover img{-webkit-transform:translateY(-50px);-moz-transform:translateY(-50px);-ms-transform:translateY(-50px);transform:translateY(-50px)}.cs-style-3 figcaption{height:100px;width:100%;top:auto;bottom:0;opacity:0;-webkit-transform:translateY(100%);-moz-transform:translateY(100%);-ms-transform:translateY(100%);transform:translateY(100%);-webkit-transition:-webkit-transform .4s,opacity .1s .3s;-moz-transition:-moz-transform .4s,opacity .1s .3s;transition:transform .4s,opacity .1s .3s}.cs-style-3 figcaption a,.cs-style-4 figcaption a,.cs-style-5 figure a,.cs-style-6 figcaption a,.cs-style-7 figcaption a{position:absolute;bottom:20px;right:20px}.cs-style-3 figure.cs-hover figcaption,.no-touch .cs-style-3 figure:hover figcaption{opacity:1;-webkit-transform:translateY(0);-moz-transform:translateY(0);-ms-transform:translateY(0);transform:translateY(0);-webkit-transition:-webkit-transform .4s,opacity .1s;-moz-transition:-moz-transform .4s,opacity .1s;transition:transform .4s,opacity .1s}.cs-style-4 li{-webkit-perspective:1700px;-moz-perspective:1700px;perspective:1700px;-webkit-perspective-origin:0 50%;-moz-perspective-origin:0 50%;perspective-origin:0 50%}.cs-style-4 figure{-webkit-transform-style:preserve-3d;-moz-transform-style:preserve-3d;transform-style:preserve-3d}.cs-style-4 figure img{-webkit-transition:-webkit-transform .4s;-moz-transition:-moz-transform .4s;transition:transform .4s}.cs-style-4 figure.cs-hover img,.no-touch .cs-style-4 figure:hover img{-webkit-transform:translateX(25%);-moz-transform:translateX(25%);-ms-transform:translateX(25%);transform:translateX(25%)}.cs-style-4 figcaption{height:100%;width:50%;opacity:0;backface-visibility:hidden;-webkit-transform-origin:0 0;-moz-transform-origin:0 0;transform-origin:0 0;-webkit-transform:rotateY(-90deg);-moz-transform:rotateY(-90deg);transform:rotateY(-90deg);-webkit-transition:-webkit-transform .4s,opacity .1s .3s;-moz-transition:-moz-transform .4s,opacity .1s .3s;transition:transform .4s,opacity .1s .3s}.cs-style-5 figcaption,.cs-style-6 figcaption,.cs-style-7 figcaption{height:100%;width:100%}.cs-style-4 figure.cs-hover figcaption,.no-touch .cs-style-4 figure:hover figcaption{opacity:1;-webkit-transform:rotateY(0);-moz-transform:rotateY(0);transform:rotateY(0);-webkit-transition:-webkit-transform .4s,opacity .1s;-moz-transition:-moz-transform .4s,opacity .1s;transition:transform .4s,opacity .1s}.cs-style-5 figure img{z-index:10;-webkit-transition:-webkit-transform .4s;-moz-transition:-moz-transform .4s;transition:transform .4s}.cs-style-5 figure.cs-hover img,.no-touch .cs-style-5 figure:hover img{-webkit-transform:scale(.4);-moz-transform:scale(.4);-ms-transform:scale(.4);transform:scale(.4)}.cs-style-5 figcaption{opacity:0;-webkit-transform:scale(.7);-moz-transform:scale(.7);-ms-transform:scale(.7);transform:scale(.7);backface-visibility:hidden;-webkit-transition:-webkit-transform .4s,opacity .4s;-moz-transition:-moz-transform .4s,opacity .4s;transition:transform .4s,opacity .4s}.cs-style-5 figure.cs-hover figcaption,.no-touch .cs-style-5 figure:hover figcaption{-webkit-transform:scale(1);-moz-transform:scale(1);-ms-transform:scale(1);transform:scale(1);opacity:1}.cs-style-6 figure img{z-index:10;-webkit-transition:-webkit-transform .4s;-moz-transition:-moz-transform .4s;transition:transform .4s}.cs-style-6 figure.cs-hover img,.no-touch .cs-style-6 figure:hover img{-webkit-transform:translateY(-50px) scale(.5);-moz-transform:translateY(-50px) scale(.5);-ms-transform:translateY(-50px) scale(.5);transform:translateY(-50px) scale(.5)}.cs-style-6 figcaption h3{margin-top:60%}.cs-style-7 li:first-child{z-index:6}.cs-style-7 li:nth-child(2){z-index:5}.cs-style-7 li:nth-child(3){z-index:4}.cs-style-7 li:nth-child(4){z-index:3}.cs-style-7 li:nth-child(5){z-index:2}.cs-style-7 li:nth-child(6){z-index:1}.cs-style-7 figure img{z-index:10}.cs-style-7 figcaption{opacity:0;-webkit-backface-visibility:hidden;-moz-backface-visibility:hidden;backface-visibility:hidden;-webkit-transition:opacity .3s,height .3s,box-shadow .3s;-moz-transition:opacity .3s,height .3s,box-shadow .3s;transition:opacity .3s,height .3s,box-shadow .3s;box-shadow:0 0 0 0 #2c3f52}.cs-style-7 figure.cs-hover figcaption,.no-touch .cs-style-7 figure:hover figcaption{opacity:1;height:130%;box-shadow:0 0 0 10px #2c3f52}.cs-style-7 figcaption h3{margin-top:86%}.cs-style-7 figcaption a,.cs-style-7 figcaption h3,.cs-style-7 figcaption span{opacity:0;-webkit-transition:opacity 0s;-moz-transition:opacity 0s;transition:opacity 0s}.cs-style-7 figure.cs-hover figcaption a,.cs-style-7 figure.cs-hover figcaption h3,.cs-style-7 figure.cs-hover figcaption span,.no-touch .cs-style-7 figure:hover figcaption a,.no-touch .cs-style-7 figure:hover figcaption h3,.no-touch .cs-style-7 figure:hover figcaption span{-webkit-transition:opacity .3s .2s;-moz-transition:opacity .3s .2s;transition:opacity .3s .2s;opacity:1}@media screen and (max-width:31.5em){.grid{padding:5px 10px 0}.grid li{width:100%;min-width:300px}}</style>';
		echo '<script>;window.Modernizr=function(a,b,c){function w(a){j.cssText=a}function x(a,b){return w(m.join(a+";")+(b||""))}function y(a,b){return typeof a===b}function z(a,b){return!!~(""+a).indexOf(b)}function A(a,b,d){for(var e in a){var f=b[a[e]];if(f!==c)return d===!1?a[e]:y(f,"function")?f.bind(d||b):f}return!1}var d="2.6.2",e={},f=!0,g=b.documentElement,h="modernizr",i=b.createElement(h),j=i.style,k,l={}.toString,m=" -webkit- -moz- -o- -ms- ".split(" "),n={},o={},p={},q=[],r=q.slice,s,t=function(a,c,d,e){var f,i,j,k,l=b.createElement("div"),m=b.body,n=m||b.createElement("body");if(parseInt(d,10))while(d--)j=b.createElement("div"),j.id=e?e[d]:h+(d+1),l.appendChild(j);return f=["&#173;",\'<style id="s\',h,\'">\',a,"</style>"].join(""),l.id=h,(m?l:n).innerHTML+=f,n.appendChild(l),m||(n.style.background="",n.style.overflow="hidden",k=g.style.overflow,g.style.overflow="hidden",g.appendChild(n)),i=c(l,a),m?l.parentNode.removeChild(l):(n.parentNode.removeChild(n),g.style.overflow=k),!!i},u={}.hasOwnProperty,v;!y(u,"undefined")&&!y(u.call,"undefined")?v=function(a,b){return u.call(a,b)}:v=function(a,b){return b in a&&y(a.constructor.prototype[b],"undefined")},Function.prototype.bind||(Function.prototype.bind=function(b){var c=this;if(typeof c!="function")throw new TypeError;var d=r.call(arguments,1),e=function(){if(this instanceof e){var a=function(){};a.prototype=c.prototype;var f=new a,g=c.apply(f,d.concat(r.call(arguments)));return Object(g)===g?g:f}return c.apply(b,d.concat(r.call(arguments)))};return e}),n.touch=function(){var c;return"ontouchstart"in a||a.DocumentTouch&&b instanceof DocumentTouch?c=!0:t(["@media (",m.join("touch-enabled),("),h,")","{#modernizr{top:9px;position:absolute}}"].join(""),function(a){c=a.offsetTop===9}),c};for(var B in n)v(n,B)&&(s=B.toLowerCase(),e[s]=n[B](),q.push((e[s]?"":"no-")+s));return e.addTest=function(a,b){if(typeof a=="object")for(var d in a)v(a,d)&&e.addTest(d,a[d]);else{a=a.toLowerCase();if(e[a]!==c)return e;b=typeof b=="function"?b():b,typeof f!="undefined"&&f&&(g.className+=" "+(b?"":"no-")+a),e[a]=b}return e},w(""),i=k=null,function(a,b){function k(a,b){var c=a.createElement("p"),d=a.getElementsByTagName("head")[0]||a.documentElement;return c.innerHTML="x<style>"+b+"</style>",d.insertBefore(c.lastChild,d.firstChild)}function l(){var a=r.elements;return typeof a=="string"?a.split(" "):a}function m(a){var b=i[a[g]];return b||(b={},h++,a[g]=h,i[h]=b),b}function n(a,c,f){c||(c=b);if(j)return c.createElement(a);f||(f=m(c));var g;return f.cache[a]?g=f.cache[a].cloneNode():e.test(a)?g=(f.cache[a]=f.createElem(a)).cloneNode():g=f.createElem(a),g.canHaveChildren&&!d.test(a)?f.frag.appendChild(g):g}function o(a,c){a||(a=b);if(j)return a.createDocumentFragment();c=c||m(a);var d=c.frag.cloneNode(),e=0,f=l(),g=f.length;for(;e<g;e++)d.createElement(f[e]);return d}function p(a,b){b.cache||(b.cache={},b.createElem=a.createElement,b.createFrag=a.createDocumentFragment,b.frag=b.createFrag()),a.createElement=function(c){return r.shivMethods?n(c,a,b):b.createElem(c)},a.createDocumentFragment=Function("h,f","return function(){var n=f.cloneNode(),c=n.createElement;h.shivMethods&&("+l().join().replace(/\w+/g,function(a){return b.createElem(a),b.frag.createElement(a),\'c("\'+a+\'")\'})+");return n}")(r,b.frag)}function q(a){a||(a=b);var c=m(a);return r.shivCSS&&!f&&!c.hasCSS&&(c.hasCSS=!!k(a,"article,aside,figcaption,figure,footer,header,hgroup,nav,section{display:block}mark{background:#FF0;color:#000}")),j||p(a,c),a}var c=a.html5||{},d=/^<|^(?:button|map|select|textarea|object|iframe|option|optgroup)$/i,e=/^(?:a|b|code|div|fieldset|h1|h2|h3|h4|h5|h6|i|label|li|ol|p|q|span|strong|style|table|tbody|td|th|tr|ul)$/i,f,g="_html5shiv",h=0,i={},j;(function(){try{var a=b.createElement("a");a.innerHTML="<xyz></xyz>",f="hidden"in a,j=a.childNodes.length==1||function(){b.createElement("a");var a=b.createDocumentFragment();return typeof a.cloneNode=="undefined"||typeof a.createDocumentFragment=="undefined"||typeof a.createElement=="undefined"}()}catch(c){f=!0,j=!0}})();var r={elements:c.elements||"abbr article aside audio bdi canvas data datalist details figcaption figure footer header hgroup mark meter nav output progress section summary time video",shivCSS:c.shivCSS!==!1,supportsUnknownElements:j,shivMethods:c.shivMethods!==!1,type:"default",shivDocument:q,createElement:n,createDocumentFragment:o};a.html5=r,q(b)}(this,b),e._version=d,e._prefixes=m,e.testStyles=t,g.className=g.className.replace(/(^|\s)no-js(\s|$)/,"$1$2")+(f?" js "+q.join(" "):""),e}(this,this.document),function(a,b,c){function d(a){return"[object Function]"==o.call(a)}function e(a){return"string"==typeof a}function f(){}function g(a){return!a||"loaded"==a||"complete"==a||"uninitialized"==a}function h(){var a=p.shift();q=1,a?a.t?m(function(){("c"==a.t?B.injectCss:B.injectJs)(a.s,0,a.a,a.x,a.e,1)},0):(a(),h()):q=0}function i(a,c,d,e,f,i,j){function k(b){if(!o&&g(l.readyState)&&(u.r=o=1,!q&&h(),l.onload=l.onreadystatechange=null,b)){"img"!=a&&m(function(){t.removeChild(l)},50);for(var d in y[c])y[c].hasOwnProperty(d)&&y[c][d].onload()}}var j=j||B.errorTimeout,l=b.createElement(a),o=0,r=0,u={t:d,s:c,e:f,a:i,x:j};1===y[c]&&(r=1,y[c]=[]),"object"==a?l.data=c:(l.src=c,l.type=a),l.width=l.height="0",l.onerror=l.onload=l.onreadystatechange=function(){k.call(this,r)},p.splice(e,0,u),"img"!=a&&(r||2===y[c]?(t.insertBefore(l,s?null:n),m(k,j)):y[c].push(l))}function j(a,b,c,d,f){return q=0,b=b||"j",e(a)?i("c"==b?v:u,a,b,this.i++,c,d,f):(p.splice(this.i++,0,a),1==p.length&&h()),this}function k(){var a=B;return a.loader={load:j,i:0},a}var l=b.documentElement,m=a.setTimeout,n=b.getElementsByTagName("script")[0],o={}.toString,p=[],q=0,r="MozAppearance"in l.style,s=r&&!!b.createRange().compareNode,t=s?l:n.parentNode,l=a.opera&&"[object Opera]"==o.call(a.opera),l=!!b.attachEvent&&!l,u=r?"object":l?"script":"img",v=l?"script":u,w=Array.isArray||function(a){return"[object Array]"==o.call(a)},x=[],y={},z={timeout:function(a,b){return b.length&&(a.timeout=b[0]),a}},A,B;B=function(a){function b(a){var a=a.split("!"),b=x.length,c=a.pop(),d=a.length,c={url:c,origUrl:c,prefixes:a},e,f,g;for(f=0;f<d;f++)g=a[f].split("="),(e=z[g.shift()])&&(c=e(c,g));for(f=0;f<b;f++)c=x[f](c);return c}function g(a,e,f,g,h){var i=b(a),j=i.autoCallback;i.url.split(".").pop().split("?").shift(),i.bypass||(e&&(e=d(e)?e:e[a]||e[g]||e[a.split("/").pop().split("?")[0]]),i.instead?i.instead(a,e,f,g,h):(y[i.url]?i.noexec=!0:y[i.url]=1,f.load(i.url,i.forceCSS||!i.forceJS&&"css"==i.url.split(".").pop().split("?").shift()?"c":c,i.noexec,i.attrs,i.timeout),(d(e)||d(j))&&f.load(function(){k(),e&&e(i.origUrl,h,g),j&&j(i.origUrl,h,g),y[i.url]=2})))}function h(a,b){function c(a,c){if(a){if(e(a))c||(j=function(){var a=[].slice.call(arguments);k.apply(this,a),l()}),g(a,j,b,0,h);else if(Object(a)===a)for(n in m=function(){var b=0,c;for(c in a)a.hasOwnProperty(c)&&b++;return b}(),a)a.hasOwnProperty(n)&&(!c&&!--m&&(d(j)?j=function(){var a=[].slice.call(arguments);k.apply(this,a),l()}:j[n]=function(a){return function(){var b=[].slice.call(arguments);a&&a.apply(this,b),l()}}(k[n])),g(a[n],j,b,n,h))}else!c&&l()}var h=!!a.test,i=a.load||a.both,j=a.callback||f,k=j,l=a.complete||f,m,n;c(h?a.yep:a.nope,!!i),i&&c(i)}var i,j,l=this.yepnope.loader;if(e(a))g(a,0,l,0);else if(w(a))for(i=0;i<a.length;i++)j=a[i],e(j)?g(j,0,l,0):w(j)?B(j):Object(j)===j&&h(j,l);else Object(a)===a&&h(a,l)},B.addPrefix=function(a,b){z[a]=b},B.addFilter=function(a){x.push(a)},B.errorTimeout=1e4,null==b.readyState&&b.addEventListener&&(b.readyState="loading",b.addEventListener("DOMContentLoaded",A=function(){b.removeEventListener("DOMContentLoaded",A,0),b.readyState="complete"},0)),a.yepnope=k(),a.yepnope.executeStack=h,a.yepnope.injectJs=function(a,c,d,e,i,j){var k=b.createElement("script"),l,o,e=e||B.errorTimeout;k.src=a;for(o in d)k.setAttribute(o,d[o]);c=j?h:c||f,k.onreadystatechange=k.onload=function(){!l&&g(k.readyState)&&(l=1,c(),k.onload=k.onreadystatechange=null)},m(function(){l||(l=1,c(1))},e),i?k.onload():n.parentNode.insertBefore(k,n)},a.yepnope.injectCss=function(a,c,d,e,g,i){var e=b.createElement("link"),j,c=i?h:c||f;e.href=a,e.rel="stylesheet",e.type="text/css";for(j in d)e.setAttribute(j,d[j]);g||(n.parentNode.insertBefore(e,n),m(c,0))}}(this,document),Modernizr.load=function(){yepnope.apply(window,[].slice.call(arguments,0))};
		!function(e){function n(e){return new RegExp("(^|\\\s+)"+e+"(\\\s+|$)")}function t(e,n){var t=s(e,n)?c:a;t(e,n)}if(Modernizr.touch){var s,a,c;"classList"in document.documentElement?(s=function(e,n){return e.classList.contains(n)},a=function(e,n){e.classList.add(n)},c=function(e,n){e.classList.remove(n)}):(s=function(e,t){return n(t).test(e.className)},a=function(e,n){s(e,n)||(e.className=e.className+" "+n)},c=function(e,t){e.className=e.className.replace(n(t)," ")});var o={hasClass:s,addClass:a,removeClass:c,toggleClass:t,has:s,add:a,remove:c,toggle:t};"function"==typeof define&&define.amd?define(o):e.classie=o,[].slice.call(document.querySelectorAll("ul.grid > li > figure")).forEach(function(e,n){e.querySelector("figcaption > a").addEventListener("touchstart",function(e){e.stopPropagation()},!1),e.addEventListener("touchstart",function(e){o.toggle(this,"cs-hover")},!1)})}}(window);</script>';

		if (!empty($this->options['extlinks_custom_css']) )
			echo '<style>'.$this->options['extlinks_custom_css'].'</style>';
	}

}
?>
