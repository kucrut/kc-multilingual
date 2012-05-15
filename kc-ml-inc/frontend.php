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
		call_user_func( $func, 'the_excerpt', array(__CLASS__, 'filter_post_excerpt'), 0 );
		call_user_func( $func, 'the_content', array(__CLASS__, 'filter_post_content'), 0 );
		call_user_func( $func, 'wp_get_attachment_image_attributes', array(__CLASS__, 'filter_attachment_attributes'), 0, 2 );

		# Terms
		call_user_func( $func, 'get_term', array(__CLASS__, 'filter_term'), 0 );
		call_user_func( $func, 'get_terms', array(__CLASS__, 'filter_objects'), 0 );
		call_user_func( $func, 'get_the_terms', array(__CLASS__, 'filter_objects'), 0 );

		# Date & Time
		call_user_func( $func, 'option_date_format', array(__CLASS__, 'filter_date_format') );
		call_user_func( $func, 'option_time_format', array(__CLASS__, 'filter_time_format') );
	}


	public static function query_vars( $vars ) {
		$vars[] = 'lang';
		return $vars;
	}


	public static function filter_home_url( $url, $path, $orig_scheme, $blog_id ) {
		return self::filter_url( $url, $path, kcMultilingual_backend::$lang, kcMultilingual_backend::$prettyURL );
	}


	public static function filter_url( $url, $path, $lang, $pretty = false ) {
		if ( !$pretty ) {
			if ( !empty($lang) && is_string($lang) )
				$url = add_query_arg( array('lang' => $lang), $url );
			else
				$url = remove_query_arg( 'lang', $url );
		}
		else {
			$path = ltrim( $path, '/' );

			if ( empty($path) || !is_string($path) || $path === '/' )
				$url = untrailingslashit( $url ) . "/{$lang}";
			else
				$url = str_replace( $path, "{$lang}/{$path}", $url );

			$url = trailingslashit( $url );
		}

		return $url;
	}


	public static function get_current_url() {
		remove_filter( 'home_url', array(__CLASS__, 'filter_home_url'), 0, 4 );

		global $wp;
		if ( kcMultilingual_backend::$prettyURL )
			$current_url = home_url( $wp->request );
		else {
			$current_url = add_query_arg( $wp->query_string, '', home_url() );
		}

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
}


/* Helper functions */

/**
 * Get/display list of languages
 */
function kc_ml_list_languages( $exclude_current = true, $full_name = true, $sep = '/', $echo = true ) {
	$languages = kcMultilingual_backend::$languages;
	if ( empty($languages) )
		return false;

	if ( $exclude_current )
		unset( $languages[kcMultilingual_backend::$lang] );

	$url = kcMultilingual_frontend::get_current_url();
	$out  = "<ul class='kc-ml-languages'>\n";
	foreach ( $languages as $lang => $data ) {
		$suffix = ( $lang === kcMultilingual_backend::$default ) ? '' : $lang;
		$out .= "<li";
		if ( $lang === kcMultilingual_backend::$default )
			$out .= " class='current-language'";
		$out .= "><a href='".kcMultilingual_frontend::filter_url( $url, '', $suffix)."'>";
		if ( $full_name )
			$out .= kcMultilingual::get_language_fullname( $lang, $data['country'] );
		else
			$out .= kcMultilingual::get_language_fullname( $lang );
		$out .= "</a></li>\n";
	}
	$out .= "</ul>\n";

	if ( $echo )
		echo $out;
	else
		return $out;
}

?>
