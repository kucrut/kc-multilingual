<?php

/**
 * @package KC_Multilingual
 * @version 0.1
 */


class kc_ml_widget_languages extends WP_Widget {
	var $defaults;

	function __construct() {
		$widget_ops = array( 'classname' => 'kc_ml_languages', 'description' => __('Languages list', 'kc-ml') );
		parent::__construct( 'kc_ml_languages', 'KC Multilingual', $widget_ops );
		$this->defaults = array(
			'title'           => '',
			'exclude_current' => false,
			'text'            => 'custom_name',
			'separator'       => ' / ',
		);
	}


	function update( $new, $old ) {
		return $new;
	}


	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults );
		$instance['title'] = strip_tags( $instance['title'] );
		$options = array(
			'title' => array(
				'label' => __('Title'),
				'type'  => 'text'
			),
			'exclude_current' => array(
				'label'   => __('Exclude current?', 'kc-ml'),
				'type'    => 'select',
				'options' => kcSettings_options::$yesno,
				'none'    => false
			),
			'text' => array(
				'label'   => __('Text', 'kc-ml'),
				'type'    => 'select',
				'options' => array(
					'custom_name'   => __('Custom name', 'kc-ml'),
					'language_name' => __('Language name', 'kc-ml'),
					'full_name'     => __('Full name', 'kc-ml'),
					'language_code' => __('Language code', 'kc-ml')
				),
				'none'    => false
			),
			'separator' => array(
				'label' => __('Separator', 'kc-ml'),
				'type'  => 'text'
			)
		);

		foreach ( $options as $id => $field ) {
	?>
		<p>
			<label for="<?php echo $this->get_field_id($id); ?>"><?php echo $field['label'] ?></label>
			<?php echo kcForm::field(
				array_merge(
					$field,
					array(
						'attr'    => array('id' => $this->get_field_id($id), 'name' => $this->get_field_name($id), 'class' => 'widefat'),
						'current' => $instance[$id]
					)
				)
			) ?>
		</p>
	<?php }
	}


	function widget( $args, $instance ) {
		$output  = $args['before_widget'];
		if ( $title = apply_filters( 'widget_title', $instance['title'] ) )
			$output .= $args['before_title'] . $title . $args['after_title'];
		$output .= kc_ml_list_languages( $instance['exclude_current'], $instance['text'], $instance['separator'], false );
		$output .= $args['after_widget'];

		echo $output;
	}


	public static function kcml_fields( $widgets ) {
		$widgets['widget_kc_ml_languages'] = array(
			array(
				'id'    => 'title',
				'type'  => 'text',
				'label' => __('Title')
			)
		);

		return $widgets;
	}
}
add_filter( 'kcml_widget_fields' , array('kc_ml_widget_languages', 'kcml_fields') );

?>
