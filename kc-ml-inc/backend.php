<?php

class kcMultilingual_backend {
	public static $settings   = array();
	public static $prettyURL  = false;
	public static $is_active  = false;
	public static $locales    = array();
	public static $default    = '';
	public static $locale     = '';
	public static $lang       = '';
	public static $languages  = array();
	public static $post_types = array();


	public static function init() {
		self::$prettyURL = (bool) get_option('permalink_structure');

		add_filter( 'rewrite_rules_array', array(__CLASS__, 'add_rewrite_rules') );
		add_filter( 'kc_plugin_settings', array(__CLASS__, 'settings') );
		add_filter( 'kcv_setting_kc_ml_general_languages', array(__CLASS__, 'validate_settings') );
		add_action( 'update_option_kc_ml_settings', array(__CLASS__, 'flush_rewrite_rules'), 0, 2 );

		$settings = get_option( 'kc_ml_settings', array() );
		self::$settings = $settings;
		if ( !isset($settings['general']['languages']['current']) || empty($settings['general']['languages']['current']) )
			return;

		self::$locales = $settings['general']['languages']['current'];
		self::$default = $settings['general']['languages']['default'];
		if ( !is_admin() )
			self::_set_locale();

		$names = array(
			'languages' => kcMultilingual::get_language_names(),
			'countries' => kcMultilingual::get_country_names()
		);
		$languages = array();
		foreach ( array_keys(self::$locales) as $lang ) {
			$codes = explode( '_', $lang );
			$name = $names['languages'][$codes[0]];
			if ( isset($codes[1]) )
				$name .= " / {$names['countries'][$codes[1]]}";

			$languages[$lang] = $name;
		}
		asort( $languages );
		self::$languages = $languages;

		if ( count(self::$locales) < 2 )
			return;

		if ( self::$locale !== self::$default ) {
			self::$is_active = true;
			require_once dirname( __FILE__ ) . '/frontend.php';
			kcMultilingual_frontend::init();
		}

		add_filter( 'kc_term_settings', array(__CLASS__, 'fields_term') );
		//add_filter( 'kc_post_settings', array(__CLASS__, 'fields_attachment') );
		add_action( 'init', array(__CLASS__, 'fields_post_prepare'), 999 );
		add_filter( 'kcv_termmeta_category_kcml_kcml-translation', array(__CLASS__, 'validate_termmeta') );
	}


	public static function get_locale() {
		return self::$locale;
	}


	private static function _set_locale() {
		$locale = get_locale();

		if ( isset($_REQUEST['lang']) && !empty($_REQUEST['lang']) ) {
			$locale = $_REQUEST['lang'];
		}
		elseif ( self::$prettyURL ) {
			preg_match( '/^\/([a-zA-Z]{2,3})\//', $_SERVER['REQUEST_URI'], $matches);
			if ( !empty($matches) )
				$locale = $matches[1];
		}

		if ( ($locale = array_search($locale, self::$locales)) === false )
			$locale = self::$default;

		self::$locale = $locale;
		self::$lang = self::$locales[$locale];

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
		foreach ( self::$locales as $locale => $lang ) {
			if ( $locale === self::$default )
				continue;

			$new[$lang.'/?$'] = "index.php?lang={$lang}"; // Homepage
			foreach( $rules as $key => $val )
				$new["{$lang}/$key"] = "{$val}&lang={$lang}";
		}

		$rules = $new + $rules;

		return $rules;
	}


	public static function flush_rewrite_rules( $old, $new ) {
		self::$locales = $new['general']['languages']['current'];
		self::$default = isset($new['general']['languages']['default']) ? $new['general']['languages']['default'] : WPLANG;
		flush_rewrite_rules();
	}


	public static function settings( $groups ) {
		$groups[] = array(
			'prefix'       => 'kc_ml',
			'menu_title'   => 'KC Multilingual',
			'page_title'   => __('KC Multilingual Settings', 'kc-essentials'),
			'display'      => 'metabox',
			'options'      => array(
				array(
					'id'     => 'general',
					'title'  => __('General', 'kc_ml'),
					'fields' => array(
						array(
							'id'    => 'languages',
							'title' => __('Languages', 'kc_ml'),
							'type'  => 'special',
							'cb'    => array(__CLASS__, 'cb_settings')
						)
					)
				)
			)
		);

		return $groups;
	}


	public static function cb_settings( $args, $db_value ) {
		$out  = '';
		$out .= "<div class='field'>\n";
		$out .= "<h4>".__('Current languages', 'kcml')."</h4>\n";
		$current = ( isset($db_value['current']) && !empty($db_value['current']) ) ? $db_value['current'] : array();
		$out .= "<input type='hidden' name='{$args['field']['name']}[current]' value='".serialize($current)."'/>\n";
		if ( !isset(self::$locales) || empty(self::$locales) ) {
			$out .= "<p>".__('N/A')."</p>\n";
		}
		else {
			$out .= "<ul>\n";
			foreach ( self::$languages as $locale => $name ) {
				$out .= "<li><label><input type='checkbox' name='{$args['field']['name']}[remove][]' value='{$locale}'";
				if ( self::$default === $locale )
					$out .= " disabled";
				$out .= " /> {$name}: <code>{$locale}</code> | <code>".self::$locales[$locale]."</code></label></li>\n";
			}
			$out .= "</ul>\n";
			$out .= "<p class='description'>".__('Check languages to remove. Current default language can&#8216;t be removed.', 'kcml')."</p>\n";
			$out .= "</div>\n";

			$out .= "<div class='field'>\n";
			$out .= "<h4><label for='kcml-default'>".__('Default language', 'kcml')."</label></h4>\n";
			$out .= "<p>\n";
			$out .= kcForm::field(array(
				'type'    => 'select',
				'options' => self::$languages,
				'none'    => false,
				'current' => $db_value['default'],
				'attr'    => array(
					'id'    => 'kcml-default',
					'name'  => "{$args['field']['name']}[default]"
				)
			));
			$out .= "</p>\n";
			$out .= "<p class='description'>".__('Careful! Changing this may bla bla bla&hellip;', 'kcml')."</p>\n";
		}
		$out .= "</div>\n";

		$out .= "<div class='field'>\n";
		$out .= "<h4>".__('Add language', 'kcml')."</h4>\n";
		$out .= "<p>";
		$out .= "<label for='kcml-add-language' class='fw'>".__('Language:', 'kcml')."</label>";
		$out .= kcForm::field(array(
			'type'    => 'select',
			'options' => kcMultilingual::get_language_names(),
			'attr'    => array(
				'id'    => 'kcml-add-language',
				'name'  => "{$args['field']['name']}[add][language]"
			)
		));
		$out .= "</p>\n";
		$out .= "<p>";
		$out .= "<label for='kcml-add-country' class='fw'>".__('Country:', 'kcml')."</label>";
		$out .= kcForm::field(array(
			'type'    => 'select',
			'options' => kcMultilingual::get_country_names(),
			'attr'    => array(
				'id'    => 'kcml-add-country',
				'name'  => "{$args['field']['name']}[add][country]"
			)
		));
		$out .= "<span class='description'>".__('Optional', 'kcml')."</span></p>\n";
		$out .= "<p>";
		$out .= "<label for='kcml-add-url' class='fw'>".__('Custom URL suffix:', 'kcml')."</label>";
		$out .= "<input type='text' id='kcml-add-url' name='{$args['field']['name']}[add][url]' />";
		$out .= "<span class='description'>".__('Defaults to language code')."</span>";
		$out .= "</p>\n";
		$out .= "</div>\n";

		//$out .= '<pre>'.print_r( $db_value, true).'</pre>';

		return $out;
	}


	public static function validate_settings( $value ) {
		$current = isset($value['current']) ? $value['current'] : '';
		$value['current'] = unserialize( $current );

		# Remove
		if ( isset($value['remove']) && !empty($value['remove']) ) {
			foreach ( $value['remove'] as $lang )
				unset( $value['current'][$lang] );
		}
		unset( $value['remove'] );

		# Add new language / edit the URL suffix
		if ( isset($value['add']['language']) && $value['add']['language'] ) {
			$new_lang = $value['add']['language'];
			if ( isset($value['add']['country']) && $value['add']['country'] )
				$new_lang .= "_{$value['add']['country']}";

			$url = ( isset($value['add']['url']) && !empty($value['add']['url']) ) ? $value['add']['url'] : $value['add']['language'];
			$url = sanitize_html_class( $url );
			$value['current'][$new_lang] = !empty($url) ? $url : $value['add']['language'] ;
		}
		unset( $value['add'] );

		# Set default
		if ( empty($value['current']) ) {
			unset( $value['default'] );
		}
		else {
			if ( !isset($value['default']) || !isset($value['current'][$value['default']]) ) {
				$_current = array_keys( $value['current'] );
				$value['default'] = $_current[0];
			}
		}

		flush_rewrite_rules();

		return $value;
	}


	public static function fields_term( $groups ) {
		$objects = get_taxonomies( array('show_ui' => true) );
		if ( !$objects )
			return $groups;

		foreach ( $objects as $object ) {
			$groups[] = array(
				$object => array(
					array(
						'id'     => 'kcml',
						'title'  => 'KC Multilingual',
						'fields' => array(
							array(
								'id'    => 'kcml-translation',
								'title' => __('Translations', 'kc_ml'),
								'type'  => 'special',
								'cb'    => array(__CLASS__, 'cb_translation_term')
							)
						)
					)
				)
			);
		}

		return $groups;
	}


	public static function cb_translation_term( $args, $db_value ) {
		if ( !$db_value )
			$db_value = array();

		$id_base = "{$args['section']}-{$args['field']['id']}";
		$list   = "<ul class='kcml-langs kcs-tabs'>\n";
		$fields = '';

		foreach ( self::$languages as $code => $name ) {
			if ( self::$default === $code )
				continue;

			$value = isset($db_value[$code]) ? $db_value[$code] : array();
			$value = wp_parse_args( $value, array('title' => '', 'content' => '') );

			$list .= "<li><a href='#kcml-{$code}'>{$name}</a></li>\n";

			$fields .= "<div id='kcml-{$code}'>\n";
			$fields .= "<h4 class='screen-reader-text'>{$name}</h4>\n";
			$fields .= "<div class='field'>\n";
			$fields .= "<label for='{$id_base}-{$code}-title'>".__('Name')."</label>\n";
			$fields .= "<input class='widefat kcs-input' type='text' value='".esc_attr($value['title'])."' name='{$args['field']['name']}[{$code}][title]' id='{$id_base}-{$code}-title' />\n";
			$fields .= "</div>\n";
			$fields .= "<div class='field'>\n";
			$fields .= "<label for='{$id_base}-{$code}-content'>".__('Description')."</label>\n";
			$fields .= "<textarea class='widefat kcs-input' name='{$args['field']['name']}[{$code}][content]' id='{$id_base}-{$code}-content' cols='50' rows='5'>".esc_textarea($value['content'])."</textarea>\n";
			$fields .= "</div>\n";
			$fields .= "</div>\n";
		}

		$list   .= "</ul>\n";

		return "<div class='kcml-wrap'>\n{$list}{$fields}</div>";
	}


	public static function validate_termmeta( $value ) {
		return kc_array_remove_empty( (array) $value );
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

			if ( $fields )
				$post_types[$pt] = $fields;
		}

		if ( !$post_types )
			return;

		self::$post_types = $post_types;

		add_action( 'edit_page_form', array(__CLASS__, 'fields_post_render') );
		add_action( 'edit_form_advanced', array(__CLASS__, 'fields_post_render') );
		add_action( 'save_post', array(__CLASS__, 'fields_post_save'), 1, 2 );
	}


	public static function fields_post_render() {
		$screen = get_current_screen();
		if ( !isset(self::$post_types[$screen->post_type]) )
			return;

		global $post;
		$post_id = $post->ID;

		?>
<div class="kcml-wrap">
	<h3>KC Multilingual</h3>
	<ul class='kcml-langs kcs-tabs'>
		<?php foreach ( self::$languages as $locale => $name ) { if ( self::$default === $locale ) continue; ?>
		<li><a href='#kcml-<?php echo $locale ?>'><?php echo $name ?></a></li>
		<?php } ?>
	</ul>
	<?php wp_nonce_field( '___kcml_nonce___', "{$screen->post_type}_kcml_nonce" ) ?>
	<?php
		foreach ( self::$languages as $locale => $name ) {
			if ( self::$default === $locale )
				continue;
	?>
	<div id="kcml-<?php echo $locale ?>">
		<h4 class="screen-reader-text"><?php echo $name ?></h4>
		<?php foreach ( self::$post_types[$screen->post_type] as $field ) { ?>
		<div class="field">
		<?php switch ( $field ) { case 'title' : ?>
			<label for="kcmlpost<?php echo $field . $locale ?>"><?php _e('Title') ?></label>
			<input id="kcmlpost<?php echo $field . $locale ?>" name="kc-postmeta[kcml][kcml-translation][<?php echo $locale ?>][title]" type="text" class="kc-input widefat" value="<?php echo esc_attr(kcMultilingual_frontend::get_translation($locale, 'post', $post_id, 'title')) ?>" />
			<?php break; case 'excerpt' : ?>
			<label for="kcmlpost<?php echo $field . $locale ?>"><?php _e('Excerpt') ?></label>
			<textarea id="kcmlpost<?php echo $field . $locale ?>" name="kc-postmeta[kcml][kcml-translation][<?php echo $locale ?>][excerpt]" class="kc-input widefat" cols="50" rows="5"><?php echo esc_textarea(kcMultilingual_frontend::get_translation($locale, 'post', $post_id, 'excerpt')) ?></textarea>
			<?php break; case 'editor' : ?>
			<label for="kcmlpost<?php echo $field . $locale ?>" class="screen-reader-text"><?php _e('Content') ?></label>
			<?php
				wp_editor(
					kcMultilingual_frontend::get_translation($locale, 'post', $post_id, 'content'),
					"kcmlposteditor{$locale}",
					array( 'textarea_name' => "kc-postmeta[kcml][kcml-translation][{$locale}][content]" )
				);
			?>
		<?php break; } ?>
		</div>
		<?php } ?>
	</div>
	<?php } ?>
</div>

	<?php }


	public static function fields_post_save( $post_id, $post ) {
		if ( !isset(self::$post_types[$post->post_type])
		      || ( isset($_POST['action']) && in_array($_POST['action'], array('inline-save', 'trash', 'untrash')) )
		      || $post->post_status == 'auto-draft'
		      || !isset($_POST["{$post->post_type}_kcml_nonce"]) )
			return $post_id;

		$post_type_obj = get_post_type_object( $post->post_type );
		if ( ( wp_verify_nonce($_POST["{$post->post_type}_kcml_nonce"], '___kcml_nonce___') && current_user_can($post_type_obj->cap->edit_post) ) !== true )
			return $post_id;

		_kc_update_meta( 'post', $post->post_type, $post_id, array('id' => 'kcml'), array('id' => 'kcml-translation', 'type' => 'special'), false );
	}
}
kcMultilingual_backend::init();

?>
