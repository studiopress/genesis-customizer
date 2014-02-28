<?php
/*
Plugin Name: Genesis Customizer

Description: Adds Genesis options to the customizer tool.
Version: 1.0.2

Author: Nathan Rice
Author URI: http://www.nathanrice.net/
*/

class Genesis_Customizer {
	
	/**
	 * Settings field for child theme modifications
	 */
	var $settings_field;
	
	/**
	 * Default settings
	 */
	var $default_settings;
	
	/**
	 * Available fonts
	 */
	var $fonts;

	/**
	 * Define defaults, call the `register` method, add css to head.
	 */
	function create( $settings_field = '', $default_settings = array() ) {
		
		$this->settings_field = $this->settings_field ? $this->settings_field : $settings_field;
		$this->default_settings = $this->default_settings ? $this->default_settings : $default_settings;
		
		//** Register new customizer elements
		add_action( 'customize_register', array( $this, 'register'), 15 );
		
		//** Output CSS to the <head>
		add_action( 'wp_head', array( $this, 'header_css' ) );
		
	}
	
	function register( $wp_customize ) {
		
		//** Registration
		$this->layout( $wp_customize );
		$this->color_scheme( $wp_customize );
		$this->colors( $wp_customize );
		$this->fonts( $wp_customize );
		
		/** Add customizer JS transport
		if ( $wp_customize->is_preview() && ! is_admin() )
		    add_action( 'wp_footer', array( $this, 'footer_scripts' ), 25 );
		/**/
		
	}
	
	function layout( $wp_customize ) {
		
		$wp_customize->add_section( 'genesis_layout', array(
			'title'    => 'Site Layout',
			'priority' => 150,
		) );

		$wp_customize->add_setting( GENESIS_SETTINGS_FIELD . '[site_layout]', array(
			'default'  => genesis_get_default_layout(),
			'type'     => 'option',
		) );

		$wp_customize->add_control( 'genesis_layout', array(
			'label'    => 'Select Default Layout',
			'section'  => 'genesis_layout',
			'settings' => GENESIS_SETTINGS_FIELD . '[site_layout]',
			'type'     => 'select',
			'choices'  => genesis_get_layouts_for_customizer(),
		) );
		
	}
	
	function color_scheme( $wp_customize ) {
		
		//** Color Selector
		if ( ! current_theme_supports( 'genesis-style-selector' ) )
			return;

		//** Add Section
		$wp_customize->add_section( 'color_scheme', array(
			'title'    => 'Color Scheme',
			'priority' => 150,
		) );

		$wp_customize->add_setting( GENESIS_SETTINGS_FIELD . '[style_selection]', array(
			'default'  => '',
			'type'     => 'option',
		) );

		$wp_customize->add_control( 'genesis_style_selection', array(
			'label'    => 'Select Color Style',
			'section'  => 'color_scheme',
			'settings' => GENESIS_SETTINGS_FIELD . '[style_selection]',
			'type'     => 'select',
			'choices'  => array_merge(
				array( '' => 'Default' ),
				array_shift( get_theme_support( 'genesis-style-selector' ) )
			),
		) );
		
	}
	
	function colors( $wp_customize ) {

		if ( ! isset( $this->default_settings['colors'] ) )
			return;

		$wp_customize->remove_section( 'colors' );

		//* Add Section
		$wp_customize->add_section( 'colors', array(
			'title'    => 'Colors',
			'priority' => 160,
		) );
		/**/

		//** Add Settings and Controls
		foreach ( $this->default_settings['colors'] as $key => $data ) {

			$wp_customize->add_setting( $this->get_field_name( $key, 'colors' ), array(
				'default'   => $data['default'],
				'type'      => 'option',
			) );

			$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $this->get_field_name( $key, 'colors' ), array(
				'label'    => $data['label'],
				'section'  => 'colors',
				'settings' => $this->get_field_name( $key, 'colors' ),
			) ) );

		}
		
	}
	
	function fonts( $wp_customize ) {
		
		if ( ! isset( $this->default_settings['fonts'] ) )
			return;	
		
		//* Add Section
		$wp_customize->add_section( 'fonts', array(
			'title'    => 'Fonts',
			'priority' => 160,
		) );
		
		//* Add Settings and Controls
		foreach ( $this->default_settings['fonts'] as $key => $data ) {
			
			$wp_customize->add_setting( $this->get_field_name( $key, 'fonts' ), array(
				'default'   => $data['default'],
				'type'      => 'option',
			) );

			$wp_customize->add_control( 'fonts-' . $key, array(
				'label'    => $data['label'],
				'section'  => 'fonts',
				'settings' => $this->get_field_name( $key, 'fonts' ),
				'type'     => 'select',
				'choices'  => array_flip( $this->fonts ),
			) );
			
		}
		
	}
	
	function header_css() {
		
		$rules = '';
		
		foreach ( $this->map as $group => $types ) {
			
			foreach ( $types as $key => $data ) {

				foreach ( $data as $property => $selector ) {
					$rules .= sprintf( '%s { %s: %s; }' . "\n", $selector, $property, $this->get_field_value( $key, $group ) );
				}

			}
			
		}
		
		echo '<style type="text/css">' . "\n" . $rules . '</style>';
		
	}
	
	function color_post_js( $option, $selector ) {
		return sprintf( 'wp.customize( "%s", function( value ) { value.bind( function( to ) { $( "%s" ).css( "color", to ); } ); } );', $option, $selector );
	}
	
	function footer_scripts() {
		
		$colors = '';
		
		foreach ( $this->colors as $setting => $data ) {
			$colors .= $this->color_post_js( sprintf( '%s_colors[%s]', get_stylesheet(), $setting ), $data['selector'] );
		}
		
		printf( '<script type="text/javascript">( function( $ ) { %s } )( jQuery );</script>', $colors );
		
	}
	
	function get_field_name( $key, $group ) {
		return sprintf( '%s[%s-%s]', $this->settings_field, $group, $key );
	}
	
	function get_field_id() {
		
	}
	
	function get_field_value( $key, $group ) {
		
		if ( $value = genesis_get_option( $group . '-' . $key, $this->settings_field ) )
			return $value;
		else
			return $this->default_settings[ $group ][ $key ]['default'];
		
	}
	
}

class Child_Theme_Customizer extends Genesis_Customizer {
	
	function __construct() {
		
		//* Settings field to store settings
		$this->settings_field = 'child-settings';
	
		//* Define our default settings
		$this->default_settings = array(
			'colors' => array(
				'primary' => array(
					'label' => 'Primary',
					'default' => '#1e1e1e',
				),
				'secondary' => array(
					'label' => 'Secondary',
					'default' => '#636363',
				),
				'accent' => array(
					'label' => 'Accent',
					'default' => '#ff2a00',
				),
			),
			'fonts' => array(
				'primary' => array(
					'label' => 'Primary',
					'default' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
				),
				'secondary' => array(
					'label' => 'Secondary',
					'default' => '"Lato", Arial, sans-serif',
				),
			)
		);
		
		//** Map options to property: value; output.
		$this->map = array(
			'colors' => array( //** $group => $types
				'primary' => array( //** $type => $data
					'color' => 'body, a:hover, .entry-title a, .genesis-nav-menu a, .widgettitle a, #title a',
					'background-color' => '::selection, .button, input[type="submit"], .navigation li a',
				),
				'secondary' => array( //** $type => $data
					'color' => 'blockquote, input, select, textarea, #description',
				),
				'accent' => array( //** $type => $data
					'color' => 'a, .entry-title a:hover, .genesis-nav-menu a:hover, .genesis-nav-menu .current-menu-item a',
					'background-color' => '.button:hover, input[type="submit"]:hover, .navigation li a:hover, .navigation li.active a',
				),
			),
			'fonts' => array(
				'primary' => array(
					'font-family' => 'body, input, textarea',
				),
				'secondary' => array(
					'font-family' => 'h1, h2, h3, h4, h5, h6, #title, .entry-title, .entry-title a',
				),
			),
		);
		
		$this->fonts = array(
			'Arial' => 'Arial, sans-serif',
			'Helvetica' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
			'Lato' => '"Lato", Arial, sans-serif',
			'Georgia' => 'Georgia, "Times New Roman", Times, serif',
		);
		
		$this->create();
		
	}
	
}

new Child_Theme_Customizer;
