<?php

class kcMultilingual_backend {
	private static $data = array(
		'lang' => '',
		'doing_edit' => false
	);


	public static function get_data() {
		$data = self::$data;
		if ( !func_num_args() )
			return $data;

		$args = func_get_args();
		return kc_array_multi_get_value( $data, $args );
	}


	public static function init() {
		self::$data['home_url'] = preg_replace('/^(\w+\:\/\/)/i', '', get_option('home'), 1 );
		self::$data['prettyURL'] = (bool) get_option('permalink_structure');

		add_filter( 'rewrite_rules_array', array(__CLASS__, 'add_rewrite_rules'), 999 );
		add_filter( 'kc_plugin_settings', array(__CLASS__, 'settings') );
		add_action( 'kc_ml_kc_settings_page_before', array(__CLASS__, 'settings_table') );
		add_filter( 'kcv_setting_kc_ml_general_languages', array(__CLASS__, 'validate_settings_general_languages') );
		add_action( 'update_option_kc_ml_settings', array(__CLASS__, 'settings_update'), 0, 2 );

		self::$data['settings'] = $settings = kcMultilingual::get_data( 'settings' );
		if ( empty($settings['general']['languages']['current']) )
			return;

		$languages = array();
		$locales   = array();
		foreach ( $settings['general']['languages']['current'] as $url => $data ) {
			if ( isset($data['country']) )
				$data['name']  = kcMultilingual::get_language_fullname($data['language'], $data['country']);
			else
				$data['name']  = kcMultilingual::get_language_fullname($data['language']);
			$languages[$url] = $data;
			$locales[$url]   = $data['locale'];
		}
		ksort( $languages );
		self::$data['languages'] = $languages;
		ksort( $locales );
		self::$data['locales'] = $locales;
		self::$data['default'] = $settings['general']['languages']['default'];

		if ( !is_admin() )
			self::_set_locale();

		if ( count(self::$data['locales']) < 2 )
			return;

		require_once dirname( __FILE__ ) . '/frontend.php';
		if ( self::$data['lang'] !== self::$data['default'] && !is_admin() )
			kcMultilingual_frontend::init();

		add_action( 'init', array(__CLASS__, 'prepare_meta_fields'), 999 );
		add_filter( 'kc_term_settings', array(__CLASS__, 'fields_term_attachment_prepare'), 99 );
		add_filter( 'kc_post_settings', array(__CLASS__, 'fields_term_attachment_prepare'), 99 );
		add_filter( 'kc_menu_nav_settings', array(__CLASS__, 'fields_term_attachment_prepare'), 99 );
		add_filter( 'kc_menu_item_settings', array(__CLASS__, 'fields_term_attachment_prepare'), 99 );
		add_filter( 'kcv_postmeta_attachment_kcml_kcml-translation', array(__CLASS__, 'validate_translation') );
		add_action( 'init', array(__CLASS__, 'fields_post_prepare'), 999 );

		# Widgets
		add_action( 'widgets_init', array(__CLASS__, 'fields_widget_prepare'), 100 );
		add_action( 'in_widget_form', array(__CLASS__, 'fields_widget_render'), 10, 3 );
		add_filter( 'widget_update_callback', array(__CLASS__, 'fields_widget_save'), 10, 4 );
		add_action( 'widgets_init', array(__CLASS__, 'register_widget') );
	}


	public static function get_locale() {
		return self::$data['locale'];
	}


	private static function _set_locale() {
		$locale = get_locale();

		if ( isset($_REQUEST['lang']) && !empty($_REQUEST['lang']) ) {
			$lang = $_REQUEST['lang'];
		}
		elseif ( self::$data['prettyURL'] ) {
			$r_uri = $_SERVER['REQUEST_URI'];
			if ( $subdir = end( array_intersect(explode('/', home_url()), explode('/', $_SERVER['REQUEST_URI'])) ) )
				$r_uri = preg_replace('/\/'.$subdir.'/', '', $r_uri );

			preg_match( '/^\/([a-zA-Z]{2,3})\//', $r_uri, $matches);
			if ( !empty($matches) )
				$lang = $matches[1];
		}
		if ( !isset($lang) )
			$lang = self::$data['default'];

		if ( !isset(self::$data['locales'][$lang]) )
			return $locale;

		self::$data['locale'] = $locale = self::$data['locales'][$lang];
		self::$data['lang'] = $lang;

		$locales = array(
			$locale.'.utf8',
			$locale.'@euro',
			$locale,
		);
		$pos = strpos( $locale, '_' );
		if ( $pos !== false )
			$locales[] = substr( $locale, 0, $pos );

		setlocale( LC_TIME, $locales );
		add_filter( 'locale', array(__CLASS__, 'get_locale') );
	}


	public static function add_rewrite_rules( $rules ) {
		$new = array();
		foreach ( self::$data['locales'] as $lang => $locale ) {
			if ( $lang === self::$data['default'] )
				continue;

			$new[$lang.'/?$'] = "index.php?lang={$lang}"; // Homepage
			foreach( $rules as $key => $val )
				$new["{$lang}/$key"] = "{$val}&lang={$lang}";
		}

		$rules = $new + $rules;

		return $rules;
	}


	public static function settings_update( $old, $new ) {
		$locales = array();
		if ( !empty($new['general']['languages']['current']) ) {
			foreach ( $new['general']['languages']['current'] as $url => $data )
				$locales[$url] = $data['locale'];
			ksort($locales);
		}
		self::$data['locales'] = $locales;
		self::$data['default'] = $new['general']['languages']['default'];
		self::$data['locale']  = self::$data['default'] ? $new['general']['languages']['current'][self::$data['default']]['locale'] : WPLANG;

		flush_rewrite_rules();
	}


	public static function settings( $groups ) {
		$groups['kc-ml'] = array(
			'prefix'       => 'kc_ml',
			'menu_title'   => 'KC Multilingual',
			'page_title'   => __('KC Multilingual Settings', 'kc-ml'),
			'load_actions' => array(__CLASS__, 'settings_actions'),
			'display'      => 'metabox',
			'options'      => array(
				array(
					'id'      => 'general',
					'title'   => __('General', 'kc-ml'),
					'metabox' => array(
						'button_text' => __('Add language', 'kc-ml')
					),
					'fields'  => array(
						array(
							'id'    => 'languages',
							'title' => __('Languages', 'kc-ml'),
							'type'  => 'special',
							'cb'    => array(__CLASS__, 'cb_settings_general_languages')
						)
					)
				)
			)
		);

		if ( isset(self::$data['languages']) && count(self::$data['languages']) > 1 )
			$groups['kc-ml']['options'][] = array(
				'id'     => 'translations',
				'title'  => __('Global Translations', 'kc-ml'),
				'fields' => array(
					array(
						'id'    => 'global',
						'title' => __('Global translations', 'kc-ml'),
						'type'  => 'special',
						'cb'    => array(__CLASS__, 'cb_settings_general_translations_global')
					)
				)
			);

		return $groups;
	}


	public static function settings_actions() {
		if (
			!isset($_REQUEST['action']) || empty($_REQUEST['action'])
			|| !isset($_REQUEST['_nonce']) || !wp_verify_nonce($_REQUEST['_nonce'], '__kc_ml__')
			|| !isset($_REQUEST['lang']) || !isset(self::$data['locales'][$_REQUEST['lang']])
		)
			return;

		$redirect = false;
		switch ( $_REQUEST['action'] ) {
			case 'edit' :
				if ( isset(self::$data['languages'][$_REQUEST['lang']]) )
					self::$data['doing_edit'] = self::$data['languages'][$_REQUEST['lang']];
			break;
			case 'set_default' :
				$settings = self::$data['settings'];
				$settings['general']['languages']['default'] = $_REQUEST['lang'];
				$settings['general']['languages'] = self::validate_settings_general_languages( $settings['general']['languages'] );
				self::$data['settings'] = $settings;
				update_option('kc_ml_settings', self::$data['settings']);
				$redirect = true;
			break;
			case 'delete' :
				$settings = self::$data['settings'];
				unset($settings['general']['languages']['current'][$_REQUEST['lang']]);
				$settings['general']['languages'] = self::validate_settings_general_languages( $settings['general']['languages'] );
				self::$data['settings'] = $settings;
				update_option('kc_ml_settings', $settings);
				$redirect = true;
			break;
		}

		if ( $redirect )
			self::settings_redirect();
	}


	public static function settings_table( $group ) {
		if ( empty(self::$data['languages']) || self::$data['doing_edit'] )
			return;

		require_once kcMultilingual::get_data('paths', 'inc') . '/table.php';
		$table = new kcMultilingual_table();
		$table->prepare_items();
		$table->display();
	}


	public static function cb_settings_general_languages( $args, $db_value ) {
		$default = isset($db_value['default']) ? $db_value['default'] : '';
		$out  = "<input type='hidden' name='{$args['field']['name']}[default]' value='{$default}' />";
		$current = isset($db_value['current']) ? $db_value['current'] : array();
		$out .= "<input type='hidden' name='{$args['field']['name']}[current]' value='".serialize($current)."' />";

		if ( self::$data['doing_edit'] ) {
			$name_prefix = 'edit';
			$value = self::$data['doing_edit'];
			$out .= "<input type='hidden' name='{$args['field']['name']}[edit][_lang]' value='".self::$data['doing_edit']['url']."' />";
		}
		else {
			$name_prefix = 'add';
			$value = array('language' => '', 'country' => '', 'date_format' => '', 'time_format' => '', 'url' => '');
		}

		$fields = array(
			array(
				'id'    => 'language',
				'type'  => 'select',
				'options' => kcMultilingual::get_language_names(),
				'label'   => __('Language', 'kc-ml'),
				'desc'    => __('Mandatory', 'kc-ml')
			),
			array(
				'id'    => 'country',
				'type'  => 'select',
				'options' => kcMultilingual::get_country_names(),
				'label'   => __('Country', 'kc-ml'),
				'desc'    => __('Optional', 'kc-ml')
			),
			array(
				'id'    => 'custom_name',
				'type'  => 'text',
				'label'   => __('Name')
			),
			array(
				'id'    => 'date_format',
				'type'  => 'text',
				'label'   => __('Date format'),
				'desc'    => __('Defaults to global setting', 'kc-ml')
			),
			array(
				'id'    => 'time_format',
				'type'  => 'text',
				'label'   => __('Time format'),
				'desc'    => __('Defaults to global setting', 'kc-ml')
			),
			array(
				'id'    => 'url',
				'type'  => 'text',
				'label'   => __('Custom URL Suffix'),
				'desc'    => __('Defaults to language code', 'kc-ml')
			)
		);

		$out .= "<div class='field'>\n";
		$title = self::$data['doing_edit'] ? __('Edit language', 'kcml') : __('Add language', 'kcml');
		$out .= "<h4>{$title}</h4>\n";
		foreach ( $fields as $field ) {
			$field['attr'] = array(
				'id'   => "kcml-{$name_prefix}-{$field['id']}",
				'name' => "{$args['field']['name']}[{$name_prefix}][{$field['id']}]"
			);
			$field['current'] = isset($value[$field['id']]) ? $value[$field['id']] : '';

			$out .= "<p>";
			$out .= "<label for='{$field['attr']['id']}' class='fw'>{$field['label']}</label>";
			$out .= kcForm::field( $field );
			if ( isset($field['desc']) )
				$out .= "<span class='description'>{$field['desc']}</span>";
			$out .= "</p>\n";
		}
		$out .= "</div>\n";
		add_action( 'admin_print_footer_scripts', array( __CLASS__, '_settings_inline_script') );

		return $out;
	}


	public static function _settings_inline_script() {
	?>
<script>
jQuery(document).ready(function($) {
	var $lang_sel  = $('#kcml-edit-language, #kcml-add-language'),
	    $lang_name = $('#kcml-edit-custom_name, #kcml-add-custom_name');

	$lang_sel.on("change", function() {
		$lang_name.val( $lang_sel.children('[value="'+$lang_sel.val()+'"]').text() );
	});
});
</script>
	<?php
	}


	public static function cb_settings_general_translations_global( $args, $db_value ) {
		if ( !$db_value )
			$db_value = array();

		$list   = "<ul class='kcml-langs kcs-tabs'>\n";
		$fields = '';

		foreach ( self::$data['languages'] as $lang => $data ) {
			if ( self::$data['default'] === $lang )
				continue;

			$id_base = "{$args['section']}-{$args['field']['id']}-{$lang}";
			$value = isset($db_value[$lang]) ? $db_value[$lang] : array();
			$value = wp_parse_args( $value, array('blogname' => '', 'blogdescription' => '') );

			$list .= "<li><a href='#{$id_base}'>{$data['name']}</a></li>\n";

			$fields .= "<div id='{$id_base}'>\n";
			$fields .= "<h4 class='screen-reader-text'>{$data['name']}</h4>\n";
			$fields .= "<div class='field'>\n";
			$fields .= "<label for='{$id_base}-blogname'>".__('Site Title')."</label>\n";
			$fields .= "<input class='kcs-input' type='text' value='".esc_attr($value['blogname'])."' name='{$args['field']['name']}[{$lang}][blogname]' id='{$id_base}-blogname' />\n";
			$fields .= "</div>\n";
			$fields .= "<div class='field'>\n";
			$fields .= "<label for='{$id_base}-blogdescription'>".__('Tagline')."</label>\n";
			$fields .= "<input class='kcs-input' type='text' value='".esc_attr($value['blogdescription'])."' name='{$args['field']['name']}[{$lang}][blogdescription]' id='{$id_base}-blogdescription' />\n";
			$fields .= "</div>\n";
			$fields .= "</div>\n";
		}

		$list   .= "</ul>\n";

		return "<div class='kcml-wrap'>\n{$list}{$fields}</div>";
	}


	private static function _add_language( $data ) {
		$url = ( isset($data['url']) && !empty($data['url']) ) ? $data['url'] : $data['language'];
		$url = sanitize_html_class( $url );

		$_new = array(
			'language' => $data['language'],
			'url'      => $url
		);
		$locale = $data['language'];
		if ( isset($data['country']) && $data['country'] ) {
			$_new['country'] = $data['country'];
			$locale .= "_{$data['country']}";
		}
		else {
			$new['country'] = '';
		}
		$_new['locale'] = $locale;
		$_new['custom_name'] = isset($data['custom_name']) ? $data['custom_name'] : '';
		$_new['date_format'] = (isset($data['date_format']) && !empty($data['date_format'])) ? $data['date_format'] : get_option( 'date_format');
		$_new['time_format'] = (isset($data['time_format']) && !empty($data['time_format'])) ? $data['time_format'] : get_option( 'time_format');

		return $_new;
	}


	public static function validate_settings_general_languages( $value ) {
		if ( !is_array($value['current']) )
			$value['current'] = unserialize( $value['current'] );

		# Add
		if ( isset($value['add']['language']) && $value['add']['language'] ) {
			$_new = self::_add_language( $value['add'] );
			$value['current'][$_new['url']] = $_new;
			unset( $value['add'] );
		}
		# Edit
		elseif ( isset($value['edit']) && $value['edit'] ) {
			$_new = self::_add_language( $value['edit'] );
			$value['current'][$_new['url']] = $_new;

			if ( $_new['url'] !== $value['edit']['_lang'] )
				unset( $value['current'][$value['edit']['_lang']] );

			add_action( 'update_option_kc_ml_settings', array(__CLASS__, 'settings_redirect'), 11 );
			unset( $value['edit'] );
		}

		$locales = array();
		# Set default
		if ( empty($value['current']) ) {
			$value['default'] = '';
		}
		else {
			if ( !$value['default'] || !isset($value['current'][$value['default']]) ) {
				$_current = array_keys( $value['current'] );
				$value['default'] = $_current[0];
			}
		}

		return $value;
	}


	public static function settings_redirect() {
		$referer = remove_query_arg( array('action', '_nonce', 'lang'), wp_get_referer() );
		$referer = add_query_arg( array('settings-updated' => 'true'), $referer );

		wp_redirect( $referer );
		exit;
	}


	public static function prepare_meta_fields() {
		$_meta = kcSettings::get_data('settings');
		unset( $_meta['plugin'] );
		unset( $_meta['theme'] );
		if ( empty($_meta) )
			return;

		$meta = $_meta;
		$not_supported = array( 'select', 'multiselect', 'special', 'editor', 'multiinput', 'radio', 'checkbox' );
		foreach ( $_meta as $type => $object_types ) {
			if ( $type == 'menu_nav' )
				continue;

			foreach ( $object_types as $object_type => $sections ) {
				foreach ( $sections as $section_id => $section ) {
					unset($meta[$type][$object_type]['kcml']);
					if ( empty($section['fields']) || !is_array($section['fields']) )
						continue;

					foreach ( $section['fields'] as $field_id => $field ) {
						if ( in_array($field['type'], $not_supported) || (isset($field['kcml']) && !$field['kcml']) )
							unset( $meta[$type][$object_type][$section_id]['fields'][$field_id] );
					}

					if ( empty($meta[$type][$object_type][$section_id]['fields']) )
						unset( $meta[$type][$object_type][$section_id] );
				}

				if ( empty($meta[$type][$object_type]) )
					unset( $meta[$type][$object_type] );
			}

			if ( empty($meta[$type]) )
				unset( $meta[$type] );
		}

		self::$data['meta_fields'] = $meta;
	}


	public static function fields_term_attachment_prepare( $groups ) {
		$section = array(
			array(
				'id'     => 'kcml',
				'title'  => 'KC Multilingual',
				'fields' => array(
					array(
						'id'    => 'kcml-translation',
						'title' => 'KC Multilingual',
						'type'  => 'special',
						'cb'    => array(__CLASS__, 'fields_term_attachment_render')
					)
				)
			)
		);

		# Attachment
		$current_filter = current_filter();
		if (  $current_filter == 'kc_post_settings' ) {
			$groups[] = array( 'attachment' => $section );
			return $groups;
		}
		elseif ( in_array( $current_filter, array( 'kc_menu_nav_settings', 'kc_menu_item_settings' ) ) ) {
			$groups[] = $section;
			return $groups;
		}

		# Terms
		$taxonomies = get_taxonomies( array('show_ui' => true) );
		if ( !$taxonomies )
			return $groups;

		foreach ( $taxonomies as $taxonomy ) {
			$groups[] = array( $taxonomy => $section );
			self::$data['taxonomies'][] = $taxonomy;
			add_filter( "kcv_termmeta_{$taxonomy}_kcml_kcml-translation", array(__CLASS__, 'validate_translation') );
		}

		return $groups;
	}


	public static function fields_term_attachment_render( $args, $db_value ) {
		if ( empty($db_value) )
			$db_value = array();

		$screen = get_current_screen();
		$object_id = isset($args['object_id']) ? $args['object_id'] : 0;
		$id_base = "{$args['section']}-{$args['mode']}-{$object_id}-{$args['field']['id']}";

		$field_tag_open  = "<div class='field'>\n";
		$field_tag_close = "</div>\n";

		if ( $args['mode'] === 'attachment' ) {
			$labels = array(
				'title'     => __('Title'),
				'image_alt' => __('Alternate Text'),
				'excerpt'   => __('Caption'),
				'content'   => __('Description')
			);
			$input_class = '';
			$object_type = 'post';
			$object_name = 'attachment';
			$hidden = true;
		}
		elseif ( $args['mode'] === 'menu_item' ) {
			$labels = array(
				'title'   => __('Navigation Label'),
				'excerpt' => __('Title Attribute'),
				'content' => __('Description')
			);
			$input_class = " class='widefat kcs-input'";
			$object_type = 'term';
			$object_name = 'nav_menu';
			$field_tag_open  = "<p class='description description-thin'>\n";
			$field_tag_close = "</p>\n";
		}
		else {
			$labels = array(
				'title' => __('Name'),
				'content' => __('Description')
			);
			$input_class = " class='widefat kcs-input'";
			$object_type = 'term';
			$object_name = $screen->taxonomy;
			$hidden = false;
		}
		$list   = "<ul class='kcml-langs kcs-tabs'>\n";
		$fields = '';

		foreach ( self::$data['languages'] as $lang => $data ) {
			if ( self::$data['default'] === $lang )
				continue;

			$value = isset($db_value[$lang]) ? $db_value[$lang] : array();
			$value = wp_parse_args( $value, array('title' => '', 'content' => '', 'excerpt' => '', 'image_alt' => '') );

			$list .= "<li><a href='#{$id_base}-{$lang}'>{$data['name']}</a></li>\n";

			$fields .= "<div id='{$id_base}-{$lang}'>\n";
			$fields .= "<h4 class='screen-reader-text'>{$data['name']}</h4>\n";

			$fields .= $field_tag_open;
			$fields .= "<label for='{$id_base}-{$lang}-title'>{$labels['title']}</label>\n";
			$fields .= "<input{$input_class} type='text' value='".esc_attr($value['title'])."' name='{$args['field']['name']}[{$lang}][title]' id='{$id_base}-{$lang}-title' />\n";
			$fields .= $field_tag_close;

			if ( $args['mode'] === 'attachment' ) {
				if ( strpos(get_post_mime_type($object_id), 'image') !== false ) {
					$fields .= $field_tag_open;
					$fields .= "<label for='{$id_base}-{$lang}-image_alt'>{$labels['image_alt']}</label>\n";
					$fields .= "<input type='text' value='".esc_attr($value['image_alt'])."' name='{$args['field']['name']}[{$lang}][image_alt]' id='{$id_base}-{$lang}-image_alt' />\n";
					$fields .= $field_tag_close;
				}
			}

			if ( in_array($args['mode'], array('attachment', 'menu_item')) ) {
				$fields .= $field_tag_open;
				$fields .= "<label for='{$id_base}-{$lang}-excerpt'>{$labels['excerpt']}</label>\n";
				$fields .= "<input type='text' value='".esc_attr($value['excerpt'])."' name='{$args['field']['name']}[{$lang}][excerpt]' id='{$id_base}-{$lang}-excerpt' />\n";
				$fields .= $field_tag_close;
			}

			$fields .= ( $args['mode'] == 'menu_item' ) ? "<p class='description description-wide'>\n" : $field_tag_open;
			$fields .= "<label for='{$id_base}-{$lang}-content'>{$labels['content']}</label>\n";
			$fields .= "<textarea{$input_class} name='{$args['field']['name']}[{$lang}][content]' id='{$id_base}-{$lang}-content' cols='50' rows='5'>".esc_textarea($value['content'])."</textarea>\n";
			$fields .= $field_tag_close;

			if ( $meta_fields = kc_array_multi_get_value(self::$data['meta_fields'], array($object_type, $object_name)) ) {
				$fields .= self::fields_metadata_render( array(
					'meta_fields' => $meta_fields,
					'meta_values' => $object_id ? kcMultilingual_frontend::get_translation( $lang, $object_type, $object_id, 'meta' ) : array(),
					'lang'        => $lang,
					'name_prefix' => $args['field']['name'],
					'hidden'      => $hidden,
					'parent'      => $object_id,
					'display'     => false
				) );
			}

			$fields .= "</div>\n";
		}

		$list   .= "</ul>\n";

		return "<div class='kcml-wrap'>\n{$list}{$fields}</div>";
	}


	public static function fields_post_prepare() {
		$_post_types = get_post_types( array('show_ui' => true ) );
		if ( !$_post_types )
			return;

		$post_types = array();
		foreach ( $_post_types as $pt ) {
			$fields = array();
			foreach ( array('title', 'excerpt', 'editor') as $feature )
				if ( post_type_supports($pt, $feature) )
					$fields[] = $feature;

			if ( !$fields )
				continue;

			$post_types[$pt] = $fields;
			add_filter( "kcv_postmeta_{$pt}_kcml_kcml-translation", array(__CLASS__, 'validate_translation') );
		}

		if ( !$post_types )
			return;

		self::$data['post_types'] = $post_types;

		add_action( 'add_meta_boxes', array(__CLASS__, 'fields_post_meta_box'), 11, 2 );
		add_action( 'save_post', array(__CLASS__, 'fields_post_save'), 1, 2 );
	}


	public static function fields_post_meta_box( $post_type, $post ) {
		add_meta_box( "kcml-post-metabox", 'KC Multilingual', array(__CLASS__, 'fields_post_render'), $post_type, 'normal', 'default' );
	}


	public static function fields_post_render() {
		$screen = get_current_screen();
		if ( !isset(self::$data['post_types'][$screen->post_type]) )
			return;

		global $post;
		$post_id = $post->ID;
		?>
<div class="kcml-wrap">
	<ul class='kcml-langs kcs-tabs'>
		<?php foreach ( self::$data['languages'] as $lang => $data ) { if ( self::$data['default'] === $lang ) continue; ?>
		<li><a href='#kcml-<?php echo $lang ?>'><?php echo $data['name'] ?></a></li>
		<?php } ?>
	</ul>
	<?php wp_nonce_field( '___kcml_nonce___', "{$screen->post_type}_kcml_nonce" ) ?>
	<?php
		foreach ( self::$data['languages'] as $lang => $data ) {
			if ( self::$data['default'] === $lang )
				continue;
	?>
	<div id="kcml-<?php echo $lang ?>">
		<h4 class="screen-reader-text"><?php echo $data['name'] ?></h4>
		<?php foreach ( self::$data['post_types'][$screen->post_type] as $field ) { ?>
		<div class="field">
		<?php switch ( $field ) { case 'title' : ?>
			<label for="kcmlpost<?php echo $field . $lang ?>"><?php _e('Title') ?></label>
			<input id="kcmlpost<?php echo $field . $lang ?>" name="kc-postmeta[kcml][kcml-translation][<?php echo $lang ?>][title]" type="text" class="kc-input widefat" value="<?php echo esc_attr(kcMultilingual_frontend::get_translation($lang, 'post', $post_id, 'title')) ?>" />
			<?php break; case 'excerpt' : ?>
			<label for="kcmlpost<?php echo $field . $lang ?>"><?php _e('Excerpt') ?></label>
			<textarea id="kcmlpost<?php echo $field . $lang ?>" name="kc-postmeta[kcml][kcml-translation][<?php echo $lang ?>][excerpt]" class="kc-input widefat" cols="50" rows="5"><?php echo esc_textarea(kcMultilingual_frontend::get_translation($lang, 'post', $post_id, 'excerpt')) ?></textarea>
			<?php break; case 'editor' : ?>
			<label for="kcmlpost<?php echo $field . $lang ?>" class="screen-reader-text"><?php _e('Content') ?></label>
			<?php
				wp_editor(
					kcMultilingual_frontend::get_translation($lang, 'post', $post_id, 'content'),
					'kcmlposteditor' . strtolower( str_replace(array('-', '_'), '', $lang) ),
					array( 'textarea_name' => "kc-postmeta[kcml][kcml-translation][{$lang}][content]", 'tinymce' => array('height' => '350px') )
				);
			?>
		<?php break; } ?>
		</div>
		<?php } ?>
		<?php
			if ( $meta_fields = kc_array_multi_get_value(self::$data['meta_fields'], array('post', $screen->post_type)) ) {
				self::fields_metadata_render( array(
					'meta_fields' => $meta_fields,
					'meta_values' => kcMultilingual_frontend::get_translation( $lang, 'post', $post_id, 'meta' ),
					'lang'        => $lang,
					'name_prefix' => "kc-postmeta[kcml][kcml-translation]",
					'hidden'      => true,
					'parent'      => $post_id,
					'display'     => true
				) );
			}
		?>
	</div>
	<?php } ?>
</div>

	<?php }


	private static function fields_metadata_render( $args = array() ) {
		extract( $args, EXTR_OVERWRITE );
		if ( !$display ) ob_start(); ?>

		<div class="kcml-post-meta">
			<h4><?php _e('Custom fields') ?></h4>
			<?php foreach ( $meta_fields as $section ) { ?>
			<h5><?php echo $section['title'] ?></h5>
			<?php foreach ( $section['fields'] as $field ) { ?>
			<div class="field meta-field">
				<?php if ( $field['type'] === 'multiinput' ) { ?>
				<label><?php echo $field['title'] ?></label>
				<?php } else { ?>
				<label for="kcml-meta-<?php echo "{$section['id']}-{$field['id']}-{$lang}" ?>"><?php echo $field['title'] ?></label>
				<?php } ?>
				<?php
					$field_id = $hidden ? "_{$field['id']}" : $field['id'];
					$field_html_id = "kcml-meta-{$section['id']}-{$field['id']}-{$lang}";
					$field_html_name = "{$name_prefix}[{$lang}][meta][{$field_id}]";
					$field_value = isset( $meta_values[$field_id] ) ? $meta_values[$field_id] : '';

					switch ( $field['type'] ) {
						case 'multiinput' :
							$field['_id'] = $field_html_id;
							echo _kc_field_multiinput( $field_html_name, $field_value, $field );
						break;
						case 'file' :
							echo _kc_field_file( array(
								'id'       => $field_html_id,
								'name'     => $field_html_name,
								'field'    => $field,
								'db_value' => $field_value,
								'parent'   => isset( $parent ) ? $parent : 0
							) );
						break;
						default :
							$field_args = array(
								'type' => $field['type'],
								'attr' => array(
									'id'    => $field_html_id,
									'name'  => $field_html_name,
									'class' => "kcs-{$field['type']} kcs-input"
								),
								'current' => $field_value
							);
							if ( isset($field['attr']) )
								$field_args['attr'] = array_merge( $field['attr'], $_args['attr'] );

							echo kcForm::field( $field_args );
						break;
					}
					if ( isset($field['desc']) && !empty($field['desc']) ) { ?>
				<p class="description"><?php echo esc_html( $field['desc'] ) ?></p>
					<?php }
				?>
			</div>
			<?php } ?>
			<?php } ?>
		</div>

		<?php if ( !$display )
			return ob_get_clean();
	}


	public static function fields_post_save( $post_id, $post ) {
		if ( !isset(self::$data['post_types'][$post->post_type])
			|| ( isset($_POST['action']) && in_array($_POST['action'], array('inline-save', 'trash', 'untrash')) )
			|| $post->post_status == 'auto-draft'
			|| empty($_POST["{$post->post_type}_kcml_nonce"])
		) {
			return $post_id;
		}

		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ( wp_verify_nonce($_POST["{$post->post_type}_kcml_nonce"], '___kcml_nonce___') && current_user_can( 'edit_post', $post_id ) ) !== true )
			return $post_id;

		_kc_update_meta( 'post', $post->post_type, $post_id, array('id' => 'kcml'), array('id' => 'kcml-translation', 'type' => 'special') );
	}


	public static function validate_translation( $value ) {
		$value = kc_array_remove_empty( (array) $value );
		$allow_unfiltered = ( current_user_can('unfiltered_html') );
		$_keys = array( 'content', 'excerpt' );
		if ( !empty($value) ) {
			foreach ( $value as $lang => $data ) {
				if ( empty($data['title']) )
					$value[$lang]['title'] = wp_filter_kses( $value[$lang][$field] );

				foreach ( $_keys as $field ) {
					if ( empty($data[$field]) )
						continue;

					if ( !$allow_unfiltered )
						$value[$lang][$field] = wp_kses_post( $value[$lang][$field] );
				}
			}
		}

		return $value;
	}


	public static function fields_menu_nav_prepare( $groups ) {
		$groups['kc_ml'] = array(
			array(
				'id'     => 'kc_ml',
				'title'  => 'KC Multilingual',
				'fields'  => array(
					array(
						'id'    => 'languages',
						'title' => __('Languages', 'kc-ml'),
						'type'  => 'special',
						'cb'    => array(__CLASS__, 'fields_term_attachment_render')
					)
				)
			)
		);

		return $groups;
	}


	public static function fields_widget_prepare() {
		$widgets = array();
		foreach(
			array(
				'widget_archives', 'widget_calendar', 'widget_nav_menu',
				'widget_links', 'widget_pages', 'widget_meta', 'widget_pages',
				'widget_recent-comments', 'widget_recent-posts', 'widget_rss',
				'widget_search', 'widget_tag_cloud', 'widget_text'
			)
			as $widget
		) {
			$widgets[$widget] = array(
				array(
					'id'    => 'title',
					'type'  => 'text',
					'label' => __('Title')
				)
			);
		}

		$widgets['widget_text'][] = array(
			'id'   => 'text',
			'type' => 'textarea',
			'attr' => array('cols' => 20, 'rows' => 16)
		);

		self::$data['widget_fields'] = (array) apply_filters( 'kcml_widget_fields', $widgets );
	}


	public static function fields_widget_render( $widget, $return, $instance ) {
		$return = null;
		if (
			empty( self::$data['widget_fields'][$widget->option_name] )
			|| empty( self::$data['widget_fields'][$widget->option_name] )
		)
			return;

		$languages = self::$data['languages'];
		unset( $languages[self::$data['default']] );
		$id_prefix = "kcml-{$widget->id}";
		$name_prefix = $widget->get_field_name( 'kcml' );
		$translation = !empty( $instance['kcml'] ) ? $instance['kcml'] : array();
	?>
<details>
	<summary>KC Multilingual</summary>
	<ul class='kcml-langs kcs-tabs'>
		<?php foreach ( $languages as $lang => $data ) : ?>
		<li><a href="#<?php echo esc_attr("{$id_prefix}-{$lang}") ?>"><?php echo esc_html( $data['name'] ) ?></a></li>
		<?php endforeach; ?>
	</ul>
	<div class="kcml-langs-panels">
		<?php
			foreach ( $languages as $lang => $data ) :
				$value = !empty($translation[$lang]) ? $translation[$lang] : array();
		?>
		<div id="<?php echo esc_attr("{$id_prefix}-{$lang}") ?>">
			<h4 class="screen-reader-text"><?php echo esc_html( $data['name'] ) ?></h4>
			<?php
				foreach ( self::$data['widget_fields'][$widget->option_name] as $_field ) :
					$_args = array(
						'type'    => $_field['type'],
						'current' => isset($value[$_field['id']]) ? $value[$_field['id']] : '',
						'attr'    => array(
							'name'  => "{$name_prefix}[{$lang}][{$_field['id']}]",
							'id'    => "{$id_prefix}-{$lang}-{$_field['id']}",
							'class' => 'widefat'
						)
					);
					if ( !empty($_field['attr']) && is_array($_field['attr']) ) {
						unset( $_field['attr']['id'] );
						unset( $_field['attr']['name'] );
						$_args['attr'] = array_merge( $_field['attr'], $_args['attr'] );
					}
			?>
			<p>
				<?php if ( isset($_field['label']) && $_field['label'] ) : ?>
				<label for="<?php echo esc_attr( "{$id_prefix}-{$lang}-{$_field['id']}" ) ?>"><?php echo esc_html( $_field['label'] ) ?></label>
				<?php endif; ?>
				<?php echo kcForm::field( $_args ) ?>
			</p>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
	</div>
</details>
		<?php
	}


	public static function fields_widget_save( $instance, $new_instance, $old_instance, $widget ) {
		$translation = isset( $new_instance['kcml'] ) ? $new_instance['kcml'] : array();
		$translation = kc_array_remove_empty( $translation );
		if ( empty($translation) )
			unset( $instance['kcml'] );
		else
			$instance['kcml'] = $translation;

		return $instance;
	}


	public static function register_widget() {
		require_once dirname( __FILE__ ) . '/widget.php';
		register_widget( 'kc_ml_widget_languages' );
	}
}
