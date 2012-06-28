<?php

class kcMultilingual_frontend {
	public static $is_active = false;


	public static function init( $destroy = false ) {
		self::$is_active = !$destroy;
		$func = $destroy ? 'remove_filter' : 'add_filter';

		call_user_func( $func, 'query_vars', array(__CLASS__, 'query_vars'), 0 );

		# Links / URLs
		call_user_func( $func, 'home_url', array(__CLASS__, 'filter_home_url'), 0, 4 );

		# Posts
		# 0. Global
		call_user_func( $func, 'posts_results', array(__CLASS__, 'filter_objects'), 0 );
		call_user_func( $func, 'the_posts', array(__CLASS__, 'filter_objects'), 0 );
		call_user_func( $func, 'get_pages', array(__CLASS__, 'filter_objects'), 0 );
		if ( $destroy )
			remove_action( 'wp', array(__CLASS__, '_filter_single_page') );
		else
			add_action( 'wp', array(__CLASS__, '_filter_single_page') );

		# 1. Individual
		call_user_func( $func, 'the_title', array(__CLASS__, 'filter_post_title'), 0, 2 );
		call_user_func( $func, 'get_the_excerpt', array(__CLASS__, 'filter_post_excerpt'), 0 );
		call_user_func( $func, 'the_content', array(__CLASS__, 'filter_post_content'), 0 );
		call_user_func( $func, 'wp_get_attachment_image_attributes', array(__CLASS__, 'filter_attachment_attributes'), 0, 2 );

		# Terms
		call_user_func( $func, 'get_term', array(__CLASS__, 'filter_term'), 0 );
		call_user_func( $func, 'get_terms', array(__CLASS__, 'filter_objects'), 0 );
		call_user_func( $func, 'get_the_terms', array(__CLASS__, 'filter_objects'), 0 );

		# Date & Time
		call_user_func( $func, 'option_date_format', array(__CLASS__, 'filter_date_format') );
		call_user_func( $func, 'option_time_format', array(__CLASS__, 'filter_time_format') );

		# Site name / desc
		call_user_func( $func, 'pre_option_blogname', array(__CLASS__, 'filter_blogname') );
		call_user_func( $func, 'pre_option_blogdescription', array(__CLASS__, 'filter_blogdescription') );

		# Widgets
		call_user_func( $func, 'widget_display_callback', array(__CLASS__, 'filter_widget'), 0, 3 );

		# Meta
		foreach ( array('post', 'term', 'user', 'comment') as $meta_type )
			call_user_func( $func, "get_{$meta_type}_metadata", array(__CLASS__, "filter_{$meta_type}_meta"), 0, 4 );
	}


	public static function query_vars( $vars ) {
		$vars[] = 'lang';
		return $vars;
	}


	public static function filter_home_url( $url, $path, $orig_scheme, $blog_id ) {
		return self::filter_url( $url, kcMultilingual_backend::$lang, kcMultilingual_backend::$prettyURL );
	}


	public static function filter_url( $url, $lang, $pretty = false ) {
		if ( $pretty && is_string($lang) && !empty($lang) ) {
			$url = preg_replace('/'.str_replace('/', '\/', kcMultilingual_backend::$home_url ).'/', kcMultilingual_backend::$home_url . "/{$lang}", $url, 1 );
		}
		else {
			if ( !empty($lang) && is_string($lang) )
				$url = add_query_arg( array('lang' => $lang), $url );
			else
				$url = remove_query_arg( 'lang', $url );
		}

		return $url;
	}


	public static function get_current_url() {
		if ( self::$is_active )
			remove_filter( 'home_url', array(__CLASS__, 'filter_home_url'), 0, 4 );

		global $wp;
		if ( kcMultilingual_backend::$prettyURL )
			$current_url = home_url( $wp->request );
		else {
			$current_url = add_query_arg( $wp->query_string, '', home_url() );
		}

		if ( self::$is_active )
			add_filter( 'home_url', array(__CLASS__, 'filter_home_url'), 0, 4 );

		return $current_url;
	}


	public static function get_translation( $lang, $type, $id, $field, $is_attachment = false ) {
		$translation = wp_cache_get( $id, "kcml_{$type}_{$field}_{$lang}" );
		if ( $translation === false ) {
			$meta_prefix = ( $type === 'post' ) ? '_' : '';
			$meta = get_metadata( $type, $id, "{$meta_prefix}kcml-translation", true );
			if ( isset($meta[$lang][$field]) && !empty($meta[$lang][$field]) )
				$translation = $meta[$lang][$field];
			else
				$translation = NULL;

			wp_cache_set( $id, $translation, "kcml_{$type}_{$field}_{$lang}" );
		}

		return $translation;
	}


	public static function filter_objects( $objects ) {
		if ( !empty($objects) ) {
			$method = in_array( current_filter(), array('get_terms', 'get_the_terms') ) ? 'filter_term' : 'filter_post';
			foreach ( $objects as $i => $object )
				$objects[$i] = call_user_func( array(__CLASS__, $method),  $object );
		}

		return $objects;
	}


	public static function filter_post_title( $title, $id ) {
		if ( $translation = self::get_translation( kcMultilingual_backend::$lang, 'post', $id, 'title', get_post_type($id) === 'attachment' ) )
			$title = $translation;

		return $title;
	}


	public static function filter_post_content( $content, $id = 0 ) {
		if ( !$id ) {
			global $post;
			if ( isset($post->kcml_filtered) && $post->kcml_filtered )
				return $content;

			$id = $post->ID;
		}

		if ( $translation = self::get_translation( kcMultilingual_backend::$lang, 'post', $id, 'content', get_post_type($id) === 'attachment' ) )
			$content = $translation;

		return $content;
	}


	public static function filter_post_excerpt( $excerpt, $id = 0 ) {
		if ( !$id ) {
			global $post;
			$id = $post->ID;
		}

		if ( $translation = self::get_translation( kcMultilingual_backend::$lang, 'post', $id, 'excerpt', get_post_type($id) === 'attachment' ) )
			$excerpt = $translation;

		return $excerpt;
	}


	public static function filter_attachment_attributes( $attr, $attachment ) {
		if ( $alt = self::get_translation( kcMultilingual_backend::$lang, 'post', $attachment->ID, 'image_alt', true ) )
			$attr['alt'] = $alt;
		if ( $title = self::get_translation( kcMultilingual_backend::$lang, 'post', $attachment->ID, 'title', true ) )
			$attr['title'] = $title;

		return $attr;
	}


	public static function filter_post( $post ) {
		$post->post_title   = self::filter_post_title( $post->post_title, $post->ID );
		$post->post_excerpt = self::filter_post_excerpt( $post->post_excerpt, $post->ID );
		$post->post_content = self::filter_post_content( $post->post_content, $post->ID );
		$post->kcml_filtered = true;

		return $post;
	}


	public static function _filter_single_page() {
		if ( !is_page() )
			return;

		global $wp_query;
		$wp_query->queried_object = $wp_query->posts[0];
		$wp_query->queried_object_id = $wp_query->posts[0]->ID;
	}


	public static function filter_term_field( $string, $id, $field ) {
		if ( $translation = self::get_translation( kcMultilingual_backend::$lang, 'term', $id, $field ) )
			$string = $translation;

		return $string;
	}


	public static function filter_term( $term ) {
		$term->name = self::filter_term_field( $term->name, $term->term_id, 'title' );
		$term->description = self::filter_term_field( $term->description, $term->term_id, 'content' );
		$term->kcml_filtered = true;

		return $term;
	}


	public static function filter_date_format( $value ) {
		$value = kcMultilingual_backend::$languages[kcMultilingual_backend::$lang]['date_format'];
		return $value;
	}


	public static function filter_time_format( $value ) {
		$value = kcMultilingual_backend::$languages[kcMultilingual_backend::$lang]['time_format'];
		return $value;
	}


	public static function get_global_translation( $value, $field ) {
		if (
			isset(kcMultilingual_backend::$settings['translations']['global'][kcMultilingual_backend::$lang][$field])
			&& !empty(kcMultilingual_backend::$settings['translations']['global'][kcMultilingual_backend::$lang][$field])
		)
			$value = kcMultilingual_backend::$settings['translations']['global'][kcMultilingual_backend::$lang][$field];

		return $value;
	}


	public static function filter_blogname( $value ) {
		return self::get_global_translation( $value, 'blogname' );
	}


	public static function filter_blogdescription( $value ) {
		return self::get_global_translation( $value, 'blogdescription' );
	}


	public static function filter_widget( $instance, $widget, $args ) {
		if ( !isset(kcMultilingual_backend::$widget_fields[$widget->option_name])
			|| empty(kcMultilingual_backend::$widget_fields[$widget->option_name])
			|| !isset($instance['kcml'][kcMultilingual_backend::$lang])
			|| empty($instance['kcml'][kcMultilingual_backend::$lang])
		)
			return $instance;

		foreach ( kcMultilingual_backend::$widget_fields[$widget->option_name] as $field ) {
			if ( isset($instance['kcml'][kcMultilingual_backend::$lang][$field['id']]) && !empty($instance['kcml'][kcMultilingual_backend::$lang][$field['id']]) )
				$instance[$field['id']] = apply_filters( 'kcml_widget_translation', $instance['kcml'][kcMultilingual_backend::$lang][$field['id']], $field['id'], $instance, $widget, $args );
		}

		return $instance;
	}


	public static function filter_post_meta( $value, $object_id, $meta_key, $single ) {
		if ( $meta_key === '_kcml-translation' )
			return $value;

		return self::filter_meta( 'post', $value, $object_id, $meta_key, $single );
	}


	public static function filter_term_meta( $value, $object_id, $meta_key, $single ) {
		if ( $meta_key === 'kcml-translation' )
			return $value;

		return self::filter_meta( 'term', $value, $object_id, $meta_key, $single );
	}


	public static function filter_user_meta( $value, $object_id, $meta_key, $single ) {
		if ( $meta_key === 'kcml-translation' )
			return $value;

		return self::filter_meta( 'user', $value, $object_id, $meta_key, $single );
	}


	public static function filter_comment_meta( $value, $object_id, $meta_key, $single ) {
		if ( $meta_key === 'kcml-translation' )
			return $value;

		return self::filter_meta( 'comment', $value, $object_id, $meta_key, $single );
	}


	private static function filter_meta( $meta_type, $value, $object_id, $meta_key, $single ) {
		if ( $_value = kcMultilingual_frontend::get_translation( kcMultilingual_backend::$lang, $meta_type, $object_id, 'meta' ) ) {
			if ( isset($_value[$meta_key]) && !empty($_value[$meta_key]) )
				$value = array( $_value[$meta_key] );
		}

		return $value;
	}
}


/* Helper functions */

/**
 * Get registered languages
 *
 * @param bool $exclude_current Whether to exclude current language
 * @param string $text Language name format: custom_name, full_name, language_name, language_code
 * @param string $sep Separator between language and country name
 *
 * @return bool|array
 */
function kc_ml_get_languages( $exclude_current = true, $text = 'custom_name', $sep = ' / ' ) {
	$_languages = kcMultilingual_backend::$languages;
	if ( empty($_languages) )
		return false;

	if ( $exclude_current )
		unset( $_languages[kcMultilingual_backend::$lang] );

	$languages = array();
	$_url = trailingslashit( kcMultilingual_frontend::get_current_url() );
	foreach ( $_languages as $lang => $data ) {
		$item = array(
			'locale'    => $data['locale'],
			'url'       => ( $lang === kcMultilingual_backend::$default ) ? $_url : kcMultilingual_frontend::filter_url( $_url, $lang, kcMultilingual_backend::$prettyURL ),
			'current'   => kcMultilingual_backend::$lang === $lang,
			'full_name' => isset($data['country']) ? kcMultilingual::get_language_fullname( $lang, $data['country'], $sep ) : kcMultilingual::get_language_fullname( $lang )
		);

		if ( 'custom_name' === $text && isset($data['custom_name']) ) {
			$name = $data['custom_name'];
		}
		else {
			if ( 'language_code' == $text )
				$name = $data['language'];
			elseif ( 'full_name' == $text )
				$name = $item['full_name'];
			else
				$name = kcMultilingual::get_language_fullname( $lang );
		}

		$item['name'] = $name;
		$languages[$lang] = $item;
	}

	return $languages;
}


/**
 * Get/display registered languages in HTML list format
 *
 * @param bool $exclude_current Whether to exclude current language
 * @param string $text Language name format: custom_name, full_name, language_name, language_code
 * @param string $sep Separator used when $text is set to full_name
 * @param bool $echo Wheter to return or display the list
 *
 * @return bool|void
 */
function kc_ml_list_languages( $exclude_current = true, $text = 'custom_name', $sep = ' / ', $echo = true ) {
	$languages = kc_ml_get_languages( $exclude_current, $text, $sep );
	if ( !$languages )
		return false;

	$out  = "<ul class='kc-ml-languages'>\n";
	foreach ( $languages as $lang => $data ) {
		$out .= "<li class='lang-{$lang}";
		if ( $data['current'] )
			$out .= " current-language";
		$out .= "'><a href='{$data['url']}' title='{$data['full_name']}'>{$data['name']}</a></li>\n";
	}
	$out .= "</ul>\n";

	if ( $echo )
		echo $out;
	else
		return $out;
}

?>
