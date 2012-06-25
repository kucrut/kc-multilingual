<?php

require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
class kcMultilingual_table extends WP_List_Table {
	function get_columns() {
		$columns = array(
			'lc'          => __('Language / Country', 'kc-ml'),
			'name'        => __('Name'),
			'locale'      => __('Locale', 'kc-ml'),
			'url'         => __('URL suffix', 'kc-ml'),
			'date_format' => __('Date format'),
			'time_format' => __('Time format')
		);

		return $columns;
	}


	function prepare_items() {
		$columns = $this->get_columns();
		$this->_column_headers = array( $columns, array(), array() );
		$this->items = kcMultilingual_backend::$languages;
  }


  function column_lc( $item ) {
		$url = "?page={$_REQUEST['page']}&lang={$item['url']}&_nonce=".wp_create_nonce('__kc_ml__');
		$actions = array( 'edit' => "<a href='{$url}&action=edit'>".__('Edit')."</a>" );
		if ( kcMultilingual_backend::$default === $item['url'] ) {
			$name = sprintf('%1$s (%2$s)', "<strong>{$item['name']}</strong>", __('default', 'kc-ml') );
		}
		else {
			$name = $item['name'];
			$actions['delete'] = "<a href='{$url}&action=delete'>".__('Delete')."</a>";
			$actions['set_default'] = "<a href='{$url}&action=set_default'>".__('Set default', 'kc-ml')."</a>";
		}

		return sprintf('%1$s %2$s', $name, $this->row_actions($actions) );
  }


  function column_name( $item ) {
		return isset( $item['custom_name'] ) ? $item['custom_name'] : '';
	}


  function column_default( $item, $column_name ) {
		return isset( $item[$column_name] ) ? $item[$column_name] : '';
  }


	function no_items() {
		_e('No language found.', 'kc-ml');
	}
}

?>
