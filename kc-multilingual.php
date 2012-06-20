<?php

/**
 * @package KC_Multilingual
 * @version 0.1
 */

/*
Plugin name: KC Multilingual
Plugin URI: http://kucrut.org/
Description: Make WordPress speak your language!
Version: 0.1
Author: Dzikri Aziz
Author URI: http://kucrut.org/
License: GPL v2
*/


class kcMultilingual {
	const version = '0.1';
	protected static $data = array();


	public static function init() {
		$paths = kcSettings::_paths( __FILE__ );
		if ( !is_array($paths) )
			return false;

		$data = array(
			'paths'    => $paths,
			'settings' => kc_get_option( 'kc_ml' )
		);
		self::$data = $data;

		# i18n
		$mo_file = $paths['inc'].'/languages/kc-ml-'.get_locale().'.mo';
		if ( is_readable($mo_file) )
			load_textdomain( 'kc-ml', $mo_file );

		require_once "{$paths['inc']}/backend.php";
		kcMultilingual_backend::init();

		add_action( 'admin_enqueue_scripts', array(__CLASS__, 'admin_sns'), 99 );
		register_deactivation_hook( $paths['p_file'], array(__CLASS__, '_deactivate') );
	}


	public static function get_data() {
		if ( !func_num_args() )
			return self::$data;

		$args = func_get_args();
		return kc_array_multi_get_value( self::$data, $args );
	}


	public static function admin_sns( $hook_suffix ) {
		if ( !defined('KC_ML_SNS_DEBUG') )
			define( 'KC_ML_SNS_DEBUG', false );

		$suffix = KC_ML_SNS_DEBUG ? '.dev' : '';

		$screen = get_current_screen();
 		if ( in_array($screen->base, array('settings_page_kc-settings-kc_ml', 'post', 'edit-tags', 'media', 'media-upload', 'nav-menus', 'widgets')) ) {
			wp_enqueue_style( 'kc_ml', self::$data['paths']['styles']."/kc-ml{$suffix}.css", array('kc-settings'), self::version );
			wp_enqueue_script( 'kc_ml', self::$data['paths']['scripts']."/kc-ml{$suffix}.js", array('kc-settings'), self::version, true );
		}

		if ( $screen->base === 'nav-menus' )
			wp_localize_script( 'kc_ml', 'kcml_texts', array(
				'menuNameLabel' => __('Menu Name'),
				'title'         => __('Navigation Label'),
				'excerpt'       => __('Title Attribute'),
				'content'       => __('Description')
			) );
	}


	# Register to KC Settings
	public static function _activate() {
		if ( version_compare(get_bloginfo('version'), '3.3', '<') )
			wp_die( 'Please upgrade your WordPress to version 3.3 before using this plugin.' );

		if ( !class_exists('kcSettings') )
			wp_die( 'Please install and activate <a href="http://wordpress.org/extend/plugins/kc-settings/">KC Settings</a> before activating this plugin.<br /> <a href="'.wp_get_referer().'">&laquo; Go back</a> to plugins page.' );

		$kcs = kcSettings::get_data('status');
		$kcs['kids']['kc_multilingual'] = array(
			'name' => 'KC Multilingual',
			'type' => 'plugin',
			'file' => kc_plugin_file(__FILE__)
		);
		update_option( 'kc_settings', $kcs );

		flush_rewrite_rules();
	}


	# Unregister from KC Settings
	public static function _deactivate() {
		$kcs = kcSettings::get_data('status');
		unset( $kcs['kids']['kc_multilingual'] );
		update_option( 'kc_settings', $kcs );

		remove_filter( 'rewrite_rules_array', array('kcMultilingual_backend', 'add_rewrite_rules') );
		flush_rewrite_rules();
	}


	public static function get_language_names( $code = '', $sort = true ) {
		$languages = array(
			'aa' => __('Afar', 'kcml'),
			'ab' => __('Abkhazian', 'kcml'),
			'ae' => __('Avestan', 'kcml'),
			'af' => __('Afrikaans', 'kcml'),
			'ak' => __('Akan', 'kcml'),
			'am' => __('Amharic', 'kcml'),
			'an' => __('Aragonese', 'kcml'),
			'ar' => __('Arabic', 'kcml'),
			'as' => __('Assamese', 'kcml'),
			'av' => __('Avaric', 'kcml'),
			'ay' => __('Aymara', 'kcml'),
			'az' => __('Azerbaijani', 'kcml'),
			'ba' => __('Bashkir', 'kcml'),
			'be' => __('Belarusian', 'kcml'),
			'bg' => __('Bulgarian', 'kcml'),
			'bh' => __('Bihari', 'kcml'),
			'bi' => __('Bislama', 'kcml'),
			'bm' => __('Bambara', 'kcml'),
			'bn' => __('Bengali; Bangla', 'kcml'),
			'bo' => __('Tibetan', 'kcml'),
			'br' => __('Breton', 'kcml'),
			'bs' => __('Bosnian', 'kcml'),
			'ca' => __('Catalan', 'kcml'),
			'ce' => __('Chechen', 'kcml'),
			'ch' => __('Chamorro', 'kcml'),
			'co' => __('Corsican', 'kcml'),
			'cr' => __('Cree', 'kcml'),
			'cs' => __('Czech', 'kcml'),
			'cu' => __('Church Slavic', 'kcml'),
			'cv' => __('Chuvash', 'kcml'),
			'cy' => __('Welsh', 'kcml'),
			'da' => __('Danish', 'kcml'),
			'de' => __('German', 'kcml'),
			'dv' => __('Divehi; Maldivian', 'kcml'),
			'dz' => __('Dzongkha; Bhutani', 'kcml'),
			'ee' => __('Éwé', 'kcml'),
			'el' => __('Greek', 'kcml'),
			'en' => __('English', 'kcml'),
			'eo' => __('Esperanto', 'kcml'),
			'es' => __('Spanish', 'kcml'),
			'et' => __('Estonian', 'kcml'),
			'eu' => __('Basque', 'kcml'),
			'fa' => __('Persian', 'kcml'),
			'ff' => __('Fulah', 'kcml'),
			'fi' => __('Finnish', 'kcml'),
			'fj' => __('Fijian; Fiji', 'kcml'),
			'fo' => __('Faroese', 'kcml'),
			'fr' => __('French', 'kcml'),
			'fy' => __('Western Frisian', 'kcml'),
			'ga' => __('Irish', 'kcml'),
			'gd' => __('Scottish Gaelic', 'kcml'),
			'gl' => __('Galician', 'kcml'),
			'gn' => __('Guarani', 'kcml'),
			'gu' => __('Gujarati', 'kcml'),
			'gv' => __('Manx', 'kcml'),
			'ha' => __('Hausa', 'kcml'),
			'he' => __('Hebrew', 'kcml'),
			'hi' => __('Hindi', 'kcml'),
			'ho' => __('Hiri Motu', 'kcml'),
			'hr' => __('Croatian', 'kcml'),
			'ht' => __('Haitian; Haitian Creole', 'kcml'),
			'hu' => __('Hungarian', 'kcml'),
			'hy' => __('Armenian', 'kcml'),
			'hz' => __('Herero', 'kcml'),
			'ia' => __('Interlingua', 'kcml'),
			'id' => __('Indonesian', 'kcml'),
			'ie' => __('Interlingue; Occidental', 'kcml'),
			'ig' => __('Igbo', 'kcml'),
			'ii' => __('Sichuan Yi; Nuosu', 'kcml'),
			'ik' => __('Inupiak; Inupiaq', 'kcml'),
			'io' => __('Ido', 'kcml'),
			'is' => __('Icelandic', 'kcml'),
			'it' => __('Italian', 'kcml'),
			'iu' => __('Inuktitut', 'kcml'),
			'ja' => __('Japanese', 'kcml'),
			'jv' => __('Javanese', 'kcml'),
			'ka' => __('Georgian', 'kcml'),
			'kg' => __('Kongo', 'kcml'),
			'ki' => __('Kikuyu; Gikuyu', 'kcml'),
			'kj' => __('Kuanyama; Kwanyama', 'kcml'),
			'kk' => __('Kazakh', 'kcml'),
			'kl' => __('Kalaallisut; Greenlandic', 'kcml'),
			'km' => __('Central Khmer; Cambodian', 'kcml'),
			'kn' => __('Kannada', 'kcml'),
			'ko' => __('Korean', 'kcml'),
			'kr' => __('Kanuri', 'kcml'),
			'ks' => __('Kashmiri', 'kcml'),
			'ku' => __('Kurdish', 'kcml'),
			'kv' => __('Komi', 'kcml'),
			'kw' => __('Cornish', 'kcml'),
			'ky' => __('Kirghiz', 'kcml'),
			'la' => __('Latin', 'kcml'),
			'lb' => __('Letzeburgesch; Luxembourgish', 'kcml'),
			'lg' => __('Ganda', 'kcml'),
			'li' => __('Limburgish; Limburger; Limburgan', 'kcml'),
			'ln' => __('Lingala', 'kcml'),
			'lo' => __('Lao; Laotian', 'kcml'),
			'lt' => __('Lithuanian', 'kcml'),
			'lu' => __('Luba-Katanga', 'kcml'),
			'lv' => __('Latvian; Lettish', 'kcml'),
			'mg' => __('Malagasy', 'kcml'),
			'mh' => __('Marshallese', 'kcml'),
			'mi' => __('Maori', 'kcml'),
			'mk' => __('Macedonian', 'kcml'),
			'ml' => __('Malayalam', 'kcml'),
			'mn' => __('Mongolian', 'kcml'),
			'mo' => __('Moldavian', 'kcml'),
			'mr' => __('Marathi', 'kcml'),
			'ms' => __('Malay', 'kcml'),
			'mt' => __('Maltese', 'kcml'),
			'my' => __('Burmese', 'kcml'),
			'na' => __('Nauru', 'kcml'),
			'nb' => __('Norwegian Bokmål', 'kcml'),
			'nd' => __('Ndebele, North', 'kcml'),
			'ne' => __('Nepali', 'kcml'),
			'ng' => __('Ndonga', 'kcml'),
			'nl' => __('Dutch', 'kcml'),
			'nn' => __('Norwegian Nynorsk', 'kcml'),
			'no' => __('Norwegian', 'kcml'),
			'nr' => __('Ndebele, South', 'kcml'),
			'nv' => __('Navajo; Navaho', 'kcml'),
			'ny' => __('Chichewa; Nyanja', 'kcml'),
			'oc' => __('Occitan; Provençal', 'kcml'),
			'oj' => __('Ojibwa', 'kcml'),
			'om' => __('(Afan) Oromo', 'kcml'),
			'or' => __('Oriya', 'kcml'),
			'os' => __('Ossetian; Ossetic', 'kcml'),
			'pa' => __('Panjabi; Punjabi', 'kcml'),
			'pi' => __('Pali', 'kcml'),
			'pl' => __('Polish', 'kcml'),
			'ps' => __('Pashto; Pushto', 'kcml'),
			'pt' => __('Portuguese', 'kcml'),
			'qu' => __('Quechua', 'kcml'),
			'rm' => __('Romansh', 'kcml'),
			'rn' => __('Rundi; Kirundi', 'kcml'),
			'ro' => __('Romanian', 'kcml'),
			'ru' => __('Russian', 'kcml'),
			'rw' => __('Kinyarwanda', 'kcml'),
			'sa' => __('Sanskrit', 'kcml'),
			'sc' => __('Sardinian', 'kcml'),
			'sd' => __('Sindhi', 'kcml'),
			'se' => __('Northern Sami', 'kcml'),
			'sg' => __('Sango; Sangro', 'kcml'),
			'si' => __('Sinhala; Sinhalese', 'kcml'),
			'sk' => __('Slovak', 'kcml'),
			'sl' => __('Slovenian', 'kcml'),
			'sm' => __('Samoan', 'kcml'),
			'sn' => __('Shona', 'kcml'),
			'so' => __('Somali', 'kcml'),
			'sq' => __('Albanian', 'kcml'),
			'sr' => __('Serbian', 'kcml'),
			'ss' => __('Swati; Siswati', 'kcml'),
			'st' => __('Sesotho; Sotho, Southern', 'kcml'),
			'su' => __('Sundanese', 'kcml'),
			'sv' => __('Swedish', 'kcml'),
			'sw' => __('Swahili', 'kcml'),
			'ta' => __('Tamil', 'kcml'),
			'te' => __('Telugu', 'kcml'),
			'tg' => __('Tajik', 'kcml'),
			'th' => __('Thai', 'kcml'),
			'ti' => __('Tigrinya', 'kcml'),
			'tk' => __('Turkmen', 'kcml'),
			'tl' => __('Tagalog', 'kcml'),
			'tn' => __('Tswana; Setswana', 'kcml'),
			'to' => __('Tonga', 'kcml'),
			'tr' => __('Turkish', 'kcml'),
			'ts' => __('Tsonga', 'kcml'),
			'tt' => __('Tatar', 'kcml'),
			'tw' => __('Twi', 'kcml'),
			'ty' => __('Tahitian', 'kcml'),
			'ug' => __('Uighur', 'kcml'),
			'uk' => __('Ukrainian', 'kcml'),
			'ur' => __('Urdu', 'kcml'),
			'uz' => __('Uzbek', 'kcml'),
			've' => __('Venda', 'kcml'),
			'vi' => __('Vietnamese', 'kcml'),
			'vo' => __('Volapük; Volapuk', 'kcml'),
			'wa' => __('Walloon', 'kcml'),
			'wo' => __('Wolof', 'kcml'),
			'xh' => __('Xhosa', 'kcml'),
			'yi' => __('Yiddish', 'kcml'),
			'yo' => __('Yoruba', 'kcml'),
			'za' => __('Zhuang', 'kcml'),
			'zh' => __('Chinese', 'kcml'),
			'zu' => __('Zulu', 'kcml'),
			'ace' => __('Achinese', 'kcml'),
			'awa' => __('Awadhi', 'kcml'),
			'bal' => __('Baluchi', 'kcml'),
			'ban' => __('Balinese', 'kcml'),
			'bej' => __('Beja; Bedawiyet', 'kcml'),
			'bem' => __('Bemba', 'kcml'),
			'bho' => __('Bhojpuri', 'kcml'),
			'bik' => __('Bikol', 'kcml'),
			'bin' => __('Bini; Edo', 'kcml'),
			'bug' => __('Buginese', 'kcml'),
			'ceb' => __('Cebuano', 'kcml'),
			'din' => __('Dinka', 'kcml'),
			'doi' => __('Dogri', 'kcml'),
			'fil' => __('Filipino; Pilipino', 'kcml'),
			'fon' => __('Fon', 'kcml'),
			'gon' => __('Gondi', 'kcml'),
			'gsw' => __('Swiss German; Alemannic; Alsatian', 'kcml'),
			'hil' => __('Hiligaynon', 'kcml'),
			'hmn' => __('Hmong', 'kcml'),
			'ilo' => __('Iloko', 'kcml'),
			'kab' => __('Kabyle', 'kcml'),
			'kam' => __('Kamba', 'kcml'),
			'kbd' => __('Kabardian', 'kcml'),
			'kmb' => __('Kimbundu', 'kcml'),
			'kok' => __('Konkani', 'kcml'),
			'kru' => __('Kurukh', 'kcml'),
			'lua' => __('Luba-Lulua', 'kcml'),
			'luo' => __('Luo (Kenya and Tanzania)', 'kcml'),
			'mad' => __('Madurese', 'kcml'),
			'mag' => __('Magahi', 'kcml'),
			'mai' => __('Maithili', 'kcml'),
			'mak' => __('Makasar', 'kcml'),
			'man' => __('Mandingo', 'kcml'),
			'men' => __('Mende', 'kcml'),
			'min' => __('Minangkabau', 'kcml'),
			'mni' => __('Manipuri', 'kcml'),
			'mos' => __('Mossi', 'kcml'),
			'mwr' => __('Marwari', 'kcml'),
			'nap' => __('Neapolitan', 'kcml'),
			'nso' => __('Pedi; Sepedi; Northern Sotho', 'kcml'),
			'nym' => __('Nyamwezi', 'kcml'),
			'nyn' => __('Nyankole', 'kcml'),
			'pag' => __('Pangasinan', 'kcml'),
			'pam' => __('Pampanga; Kapampangan', 'kcml'),
			'raj' => __('Rajasthani', 'kcml'),
			'sas' => __('Sasak', 'kcml'),
			'sat' => __('Santali', 'kcml'),
			'scn' => __('Sicilian', 'kcml'),
			'shn' => __('Shan', 'kcml'),
			'sid' => __('Sidamo', 'kcml'),
			'srr' => __('Serer', 'kcml'),
			'suk' => __('Sukuma', 'kcml'),
			'sus' => __('Susu', 'kcml'),
			'tem' => __('Timne', 'kcml'),
			'tiv' => __('Tiv', 'kcml'),
			'tum' => __('Tumbuka', 'kcml'),
			'umb' => __('Umbundu', 'kcml'),
			'wal' => __('Walamo', 'kcml'),
			'war' => __('Waray', 'kcml'),
			'yao' => __('Yao', 'kcml'),
		);

		if ( $code ) {
			if ( isset($languages[$code]) )
				return $languages[$code];
			else
				return false;
		}

		if ( $sort )
			asort( $languages );

		return $languages;
	}


	public static function get_country_names( $code = '', $sort = true ) {
		$countries = array(
			'AD' => __('Andorra', 'kcml'),
			'AE' => __('United Arab Emirates', 'kcml'),
			'AF' => __('Afghanistan', 'kcml'),
			'AG' => __('Antigua and Barbuda', 'kcml'),
			'AI' => __('Anguilla', 'kcml'),
			'AL' => __('Albania', 'kcml'),
			'AM' => __('Armenia', 'kcml'),
			'AN' => __('Netherlands Antilles', 'kcml'),
			'AO' => __('Angola', 'kcml'),
			'AQ' => __('Antarctica', 'kcml'),
			'AR' => __('Argentina', 'kcml'),
			'AS' => __('Samoa (American)', 'kcml'),
			'AT' => __('Austria', 'kcml'),
			'AU' => __('Australia', 'kcml'),
			'AW' => __('Aruba', 'kcml'),
			'AX' => __('Aaland Islands', 'kcml'),
			'AZ' => __('Azerbaijan', 'kcml'),
			'BA' => __('Bosnia and Herzegovina', 'kcml'),
			'BB' => __('Barbados', 'kcml'),
			'BD' => __('Bangladesh', 'kcml'),
			'BE' => __('Belgium', 'kcml'),
			'BF' => __('Burkina Faso', 'kcml'),
			'BG' => __('Bulgaria', 'kcml'),
			'BH' => __('Bahrain', 'kcml'),
			'BI' => __('Burundi', 'kcml'),
			'BJ' => __('Benin', 'kcml'),
			'BM' => __('Bermuda', 'kcml'),
			'BN' => __('Brunei', 'kcml'),
			'BO' => __('Bolivia', 'kcml'),
			'BR' => __('Brazil', 'kcml'),
			'BS' => __('Bahamas', 'kcml'),
			'BT' => __('Bhutan', 'kcml'),
			'BV' => __('Bouvet Island', 'kcml'),
			'BW' => __('Botswana', 'kcml'),
			'BY' => __('Belarus', 'kcml'),
			'BZ' => __('Belize', 'kcml'),
			'CA' => __('Canada', 'kcml'),
			'CC' => __('Cocos (Keeling) Islands', 'kcml'),
			'CD' => __('Congo (Dem, Rep.)', 'kcml'),
			'CF' => __('Central African Republic', 'kcml'),
			'CG' => __('Congo (Rep.)', 'kcml'),
			'CH' => __('Switzerland', 'kcml'),
			'CI' => __("Côte d'Ivoire", 'kcml'),
			'CK' => __('Cook Islands', 'kcml'),
			'CL' => __('Chile', 'kcml'),
			'CM' => __('Cameroon', 'kcml'),
			'CN' => __('China', 'kcml'),
			'CO' => __('Colombia', 'kcml'),
			'CR' => __('Costa Rica', 'kcml'),
			'CU' => __('Cuba', 'kcml'),
			'CV' => __('Cape Verde', 'kcml'),
			'CX' => __('Christmas Island', 'kcml'),
			'CY' => __('Cyprus', 'kcml'),
			'CZ' => __('Czech Republic', 'kcml'),
			'DE' => __('Germany', 'kcml'),
			'DJ' => __('Djibouti', 'kcml'),
			'DK' => __('Denmark', 'kcml'),
			'DM' => __('Dominica', 'kcml'),
			'DO' => __('Dominican Republic', 'kcml'),
			'DZ' => __('Algeria', 'kcml'),
			'EC' => __('Ecuador', 'kcml'),
			'EE' => __('Estonia', 'kcml'),
			'EG' => __('Egypt', 'kcml'),
			'EH' => __('Western Sahara', 'kcml'),
			'ER' => __('Eritrea', 'kcml'),
			'ES' => __('Spain', 'kcml'),
			'ET' => __('Ethiopia', 'kcml'),
			'FI' => __('Finland', 'kcml'),
			'FJ' => __('Fiji', 'kcml'),
			'FK' => __('Falkland Islands', 'kcml'),
			'FM' => __('Micronesia', 'kcml'),
			'FO' => __('Faeroe Islands', 'kcml'),
			'FR' => __('France', 'kcml'),
			'GA' => __('Gabon', 'kcml'),
			'GB' => __('Britain (United Kingdom)', 'kcml'),
			'GD' => __('Grenada', 'kcml'),
			'GE' => __('Georgia', 'kcml'),
			'GF' => __('French Guiana', 'kcml'),
			'GG' => __('Guernsey', 'kcml'),
			'GH' => __('Ghana', 'kcml'),
			'GI' => __('Gibraltar', 'kcml'),
			'GL' => __('Greenland', 'kcml'),
			'GM' => __('Gambia', 'kcml'),
			'GN' => __('Guinea', 'kcml'),
			'GP' => __('Guadeloupe', 'kcml'),
			'GQ' => __('Equatorial Guinea', 'kcml'),
			'GR' => __('Greece', 'kcml'),
			'GS' => __('South Georgia and the South Sandwich Islands', 'kcml'),
			'GT' => __('Guatemala', 'kcml'),
			'GU' => __('Guam', 'kcml'),
			'GW' => __('Guinea-Bissau', 'kcml'),
			'GY' => __('Guyana', 'kcml'),
			'HK' => __('Hong Kong', 'kcml'),
			'HM' => __('Heard Island and McDonald Islands', 'kcml'),
			'HN' => __('Honduras', 'kcml'),
			'HR' => __('Croatia', 'kcml'),
			'HT' => __('Haiti', 'kcml'),
			'HU' => __('Hungary', 'kcml'),
			'ID' => __('Indonesia', 'kcml'),
			'IE' => __('Ireland', 'kcml'),
			'IL' => __('Israel', 'kcml'),
			'IM' => __('Isle of Man', 'kcml'),
			'IN' => __('India', 'kcml'),
			'IO' => __('British Indian Ocean Territory', 'kcml'),
			'IQ' => __('Iraq', 'kcml'),
			'IR' => __('Iran', 'kcml'),
			'IS' => __('Iceland', 'kcml'),
			'IT' => __('Italy', 'kcml'),
			'JE' => __('Jersey', 'kcml'),
			'JM' => __('Jamaica', 'kcml'),
			'JO' => __('Jordan', 'kcml'),
			'JP' => __('Japan', 'kcml'),
			'KE' => __('Kenya', 'kcml'),
			'KG' => __('Kyrgyzstan', 'kcml'),
			'KH' => __('Cambodia', 'kcml'),
			'KI' => __('Kiribati', 'kcml'),
			'KM' => __('Comoros', 'kcml'),
			'KN' => __('St Kitts and Nevis', 'kcml'),
			'KP' => __('Korea (North)', 'kcml'),
			'KR' => __('Korea (South)', 'kcml'),
			'KW' => __('Kuwait', 'kcml'),
			'KY' => __('Cayman Islands', 'kcml'),
			'KZ' => __('Kazakhstan', 'kcml'),
			'LA' => __('Laos', 'kcml'),
			'LB' => __('Lebanon', 'kcml'),
			'LC' => __('St Lucia', 'kcml'),
			'LI' => __('Liechtenstein', 'kcml'),
			'LK' => __('Sri Lanka', 'kcml'),
			'LR' => __('Liberia', 'kcml'),
			'LS' => __('Lesotho', 'kcml'),
			'LT' => __('Lithuania', 'kcml'),
			'LU' => __('Luxembourg', 'kcml'),
			'LV' => __('Latvia', 'kcml'),
			'LY' => __('Libya', 'kcml'),
			'MA' => __('Morocco', 'kcml'),
			'MC' => __('Monaco', 'kcml'),
			'MD' => __('Moldova', 'kcml'),
			'ME' => __('Montenegro', 'kcml'),
			'MG' => __('Madagascar', 'kcml'),
			'MH' => __('Marshall Islands', 'kcml'),
			'MK' => __('Macedonia', 'kcml'),
			'ML' => __('Mali', 'kcml'),
			'MM' => __('Myanmar (Burma)', 'kcml'),
			'MN' => __('Mongolia', 'kcml'),
			'MO' => __('Macao', 'kcml'),
			'MP' => __('Northern Mariana Islands', 'kcml'),
			'MQ' => __('Martinique', 'kcml'),
			'MR' => __('Mauritania', 'kcml'),
			'MS' => __('Montserrat', 'kcml'),
			'MT' => __('Malta', 'kcml'),
			'MU' => __('Mauritius', 'kcml'),
			'MV' => __('Maldives', 'kcml'),
			'MW' => __('Malawi', 'kcml'),
			'MX' => __('Mexico', 'kcml'),
			'MY' => __('Malaysia', 'kcml'),
			'MZ' => __('Mozambique', 'kcml'),
			'NA' => __('Namibia', 'kcml'),
			'NC' => __('New Caledonia', 'kcml'),
			'NE' => __('Niger', 'kcml'),
			'NF' => __('Norfolk Island', 'kcml'),
			'NG' => __('Nigeria', 'kcml'),
			'NI' => __('Nicaragua', 'kcml'),
			'NL' => __('Netherlands', 'kcml'),
			'NO' => __('Norway', 'kcml'),
			'NP' => __('Nepal', 'kcml'),
			'NR' => __('Nauru', 'kcml'),
			'NU' => __('Niue', 'kcml'),
			'NZ' => __('New Zealand', 'kcml'),
			'OM' => __('Oman', 'kcml'),
			'PA' => __('Panama', 'kcml'),
			'PE' => __('Peru', 'kcml'),
			'PF' => __('French Polynesia', 'kcml'),
			'PG' => __('Papua New Guinea', 'kcml'),
			'PH' => __('Philippines', 'kcml'),
			'PK' => __('Pakistan', 'kcml'),
			'PL' => __('Poland', 'kcml'),
			'PM' => __('St Pierre and Miquelon', 'kcml'),
			'PN' => __('Pitcairn', 'kcml'),
			'PR' => __('Puerto Rico', 'kcml'),
			'PS' => __('Palestine', 'kcml'),
			'PT' => __('Portugal', 'kcml'),
			'PW' => __('Palau', 'kcml'),
			'PY' => __('Paraguay', 'kcml'),
			'QA' => __('Qatar', 'kcml'),
			'RE' => __('Reunion', 'kcml'),
			'RO' => __('Romania', 'kcml'),
			'RS' => __('Serbia', 'kcml'),
			'RU' => __('Russia', 'kcml'),
			'RW' => __('Rwanda', 'kcml'),
			'SA' => __('Saudi Arabia', 'kcml'),
			'SB' => __('Solomon Islands', 'kcml'),
			'SC' => __('Seychelles', 'kcml'),
			'SD' => __('Sudan', 'kcml'),
			'SE' => __('Sweden', 'kcml'),
			'SG' => __('Singapore', 'kcml'),
			'SH' => __('St Helena', 'kcml'),
			'SI' => __('Slovenia', 'kcml'),
			'SJ' => __('Svalbard and Jan Mayen', 'kcml'),
			'SK' => __('Slovakia', 'kcml'),
			'SL' => __('Sierra Leone', 'kcml'),
			'SM' => __('San Marino', 'kcml'),
			'SN' => __('Senegal', 'kcml'),
			'SO' => __('Somalia', 'kcml'),
			'SR' => __('Suriname', 'kcml'),
			'ST' => __('Sao Tome and Principe', 'kcml'),
			'SV' => __('El Salvador', 'kcml'),
			'SY' => __('Syria', 'kcml'),
			'SZ' => __('Swaziland', 'kcml'),
			'TC' => __('Turks and Caicos Islands', 'kcml'),
			'TD' => __('Chad', 'kcml'),
			'TF' => __('French Southern and Antarctic Lands', 'kcml'),
			'TG' => __('Togo', 'kcml'),
			'TH' => __('Thailand', 'kcml'),
			'TJ' => __('Tajikistan', 'kcml'),
			'TK' => __('Tokelau', 'kcml'),
			'TL' => __('Timor-Leste', 'kcml'),
			'TM' => __('Turkmenistan', 'kcml'),
			'TN' => __('Tunisia', 'kcml'),
			'TO' => __('Tonga', 'kcml'),
			'TR' => __('Turkey', 'kcml'),
			'TT' => __('Trinidad and Tobago', 'kcml'),
			'TV' => __('Tuvalu', 'kcml'),
			'TW' => __('Taiwan', 'kcml'),
			'TZ' => __('Tanzania', 'kcml'),
			'UA' => __('Ukraine', 'kcml'),
			'UG' => __('Uganda', 'kcml'),
			'UM' => __('US minor outlying islands', 'kcml'),
			'US' => __('United States', 'kcml'),
			'UY' => __('Uruguay', 'kcml'),
			'UZ' => __('Uzbekistan', 'kcml'),
			'VA' => __('Vatican City', 'kcml'),
			'VC' => __('St Vincent and the Grenadines', 'kcml'),
			'VE' => __('Venezuela', 'kcml'),
			'VG' => __('Virgin Islands (UK)', 'kcml'),
			'VI' => __('Virgin Islands (US)', 'kcml'),
			'VN' => __('Vietnam', 'kcml'),
			'VU' => __('Vanuatu', 'kcml'),
			'WF' => __('Wallis and Futuna', 'kcml'),
			'WS' => __('Samoa (Western)', 'kcml'),
			'YE' => __('Yemen', 'kcml'),
			'YT' => __('Mayotte', 'kcml'),
			'ZA' => __('South Africa', 'kcml'),
			'ZM' => __('Zambia', 'kcml'),
			'ZW' => __('Zimbabwe', 'kcml')
		);

		if ( $code ) {
			if ( isset($countries[$code]) )
				return $countries[$code];
			else
				return false;
		}

		if ( $sort )
			asort( $countries );

		return $countries;
	}


	public static function get_language_fullname( $language_code, $country_code = '', $sep = ' / ' ) {
		$language_name = self::get_language_names( $language_code );
		if ( !$country_code )
			return $language_name;

		$country_name = self::get_country_names( $country_code );
		if ( $country_name )
			$language_name .= $sep . $country_name;

		return $language_name;
	}
}

add_action( 'plugins_loaded', array('kcMultilingual', 'init') );


# A hack for symlinks
if ( !function_exists('kc_plugin_file') ) {
	function kc_plugin_file( $file ) {
		if ( !file_exists($file) )
			return $file;

		$file_info = pathinfo( $file );
		$parent = basename( $file_info['dirname'] );

		$file = ( $parent == $file_info['filename'] ) ? "{$parent}/{$file_info['basename']}" : $file_info['basename'];

		return $file;
	}
}

register_activation_hook( kc_plugin_file(__FILE__), array('kcMultilingual', '_activate') );

?>
