<?php

class kcMultilingual_frontend {
	private static $lang;


	public static function init() {
		if ( is_admin() )
			return;

		add_filter( 'query_vars', array(__CLASS__, 'query_vars'), 0 );

		# Links / URLs
		add_filter( 'home_url', array(__CLASS__, 'filter_home_url'), 0, 4 );

		# Posts
		# 0. Global
		add_filter( 'posts_results', array(__CLASS__, 'filter_objects'), 0 );
		add_filter( 'the_posts', array(__CLASS__, 'filter_objects'), 0 );
		add_filter( 'get_pages', array(__CLASS__, 'filter_objects'), 0 );
		add_action( 'wp', array(__CLASS__, '_filter_single_page') );
		# 1. Individual
		add_filter( 'the_title', array(__CLASS__, 'filter_post_title'), 0, 2 );
		add_filter( 'the_content', array(__CLASS__, 'filter_post_content'), 0 );

		# Terms
		add_filter( 'get_term', array(__CLASS__, 'filter_term'), 0 );
		add_filter( 'get_terms', array(__CLASS__, 'filter_objects'), 0 );
		add_filter( 'get_the_terms', array(__CLASS__, 'filter_objects'), 0 );
	}


	public static function query_vars( $vars ) {
		$vars[] = 'lang';
		return $vars;
	}


	public static function filter_home_url( $url, $path, $orig_scheme, $blog_id ) {
		if ( !kcMultilingual_backend::$prettyURL ) {
			$url = add_query_arg( array('lang' => kcMultilingual_backend::$lang), $url );
		}
		else {
			if ( !$path || $path === '/' )
				$url .= trailingslashit( kcMultilingual_backend::$lang );
			else
				$url = str_replace( $path, '/' . kcMultilingual_backend::$lang . $path, $url );
		}

		return $url;
	}


	public static function get_translation( $locale, $type, $id, $field, $is_attachment = false ) {
		$translation = wp_cache_get( $id, "kcml_{$type}_{$field}_{$locale}" );
		if ( $translation === false ) {
			$meta_prefix = ( $type === 'post' && !$is_attachment ) ? '_' : '';
			$meta = get_metadata( $type, $id, "{$meta_prefix}kcml-translation", true );
			if ( isset($meta[$locale][$field]) && !empty($meta[$locale][$field]) )
				$translation = $meta[$locale][$field];
			else
				$translation = NULL;

			wp_cache_set( $id, $translation, "kcml_{$type}_{$field}_{$locale}" );
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
		if ( $translation = self::get_translation( kcMultilingual_backend::$locale, 'post', $id, 'title', get_post_type($id) === 'attachment' ) )
			$title = $translation;

		return $title;
	}


	public static function filter_post_content( $content, $id = 0 ) {
		if ( !$id ) {
			global $post;
			$id = $post->ID;
		}

		if ( $translation = self::get_translation( kcMultilingual_backend::$locale, 'post', $id, 'content', get_post_type($id) === 'attachment' ) )
			$content = $translation;

		return $content;
	}


	public static function filter_post( $post ) {
		$post->post_title = self::filter_post_title( $post->post_title, $post->ID );
		$post->post_content = self::filter_post_content( $post->post_content, $post->ID );

		return $post;
	}


	public static function _filter_single_page() {
		if ( !is_page() )
			return;

		global $wp_query;
		$wp_query->queried_object = $wp_query->posts[0];
	}


	public static function filter_term_field( $string, $id, $field ) {
		if ( $translation = self::get_translation( kcMultilingual_backend::$locale, 'term', $id, $field ) )
			$string = $translation;

		return $string;
	}


	public static function filter_term( $term ) {
		$term->name = self::filter_term_field( $term->name, $term->term_id, 'title' );
		$term->description = self::filter_term_field( $term->description, $term->term_id, 'content' );

		return $term;
	}
}
?>
