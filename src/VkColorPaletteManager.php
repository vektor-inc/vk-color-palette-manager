<?php //phpcs:ignore
/**
 * VK_Color_Palette_Manager
 *
 * @package vektor-inc/vk-color-palette-manager
 * @license GPL-2.0+
 *
 * @version 0.3.0
 */

namespace VektorInc\VK_Color_Palette_Manager;

use WP_Customize_Color_Control;
use VK_Custom_Html_Control;
use WP_Theme_JSON_Resolver;

/**
 * VK_Color_Palette_Manager
 */
class VkColorPaletteManager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'customize_register', array( __CLASS__, 'customize_register' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'setup_color_palette' ), 9999 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_color_palette_css' ), 11 );
		// 11 指定が無いと先に読み込んでしまって効かない
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'add_color_palette_css_to_editor' ), 11 );
		load_textdomain( 'vk-color-palette-manager', dirname( __FILE__ ) . '/languages/vk-color-palette-manager-' . get_locale() . '.mo' );
	}

	/**
	 * Default Options
	 */
	public static function get_option() {
		$default_options = array(
			'core_color_palette'  => true,
			'theme_color_palette' => true,
			'bootstrap_color_palette' => false,
		);
		for ( $i = 1; $i <= 5; $i++ ) {
			$default_options['color_custom_' . $i ] = '';
		}
		$options = get_option( 'vk_color_manager_options' );
		return wp_parse_args( $options, $default_options );
	}

	/**
	 * Sanitize CheckBox
	 */
	public static function sanitize_checkbox( $input ) {
		if ( 'true' === $input || true === $input ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Customizer
	 *
	 * @param object $wp_customize : customize object.
	 */
	public static function customize_register( $wp_customize ) {

		// Display Core Color Palette
		$wp_customize->add_setting(
			'vk_color_manager_options[core_color_palette]',
			array(
				'default'           => true,
				'type'              => 'option',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
			)
		);
		$wp_customize->add_control(
			'vk_color_manager_options[core_color_palette]',
			array(
				'label'    => __( 'Display Core Color Palette', 'vk-color-palette-manager' ),
				'section'  => 'colors',
				'settings' => 'vk_color_manager_options[core_color_palette]',
				'type'     => 'checkbox',
			)
		);

		// Display Theme Color Palette
		$wp_customize->add_setting(
			'vk_color_manager_options[theme_color_palette]',
			array(
				'default'           => true,
				'type'              => 'option',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
			)
		);
		$wp_customize->add_control(
			'vk_color_manager_options[theme_color_palette]',
			array(
				'label'    => __( 'Display Theme Color Palette', 'vk-color-palette-manager' ),
				'section'  => 'colors',
				'settings' => 'vk_color_manager_options[theme_color_palette]',
				'type'     => 'checkbox',
			)
		);

		// Display Bootstrap Color Palette
		$wp_customize->add_setting(
			'vk_color_manager_options[bootstrap_color_palette]',
			array(
				'default'           => false,
				'type'              => 'option',
				'capability'        => 'edit_theme_options',
				'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
			)
		);
		$wp_customize->add_control(
			'vk_color_manager_options[bootstrap_color_palette]',
			array(
				'label'    => __( 'Display Bootstrap Color Palette', 'vk-color-palette-manager' ),
				'section'  => 'colors',
				'settings' => 'vk_color_manager_options[bootstrap_color_palette]',
				'type'     => 'checkbox',
			)
		);

		if ( class_exists( 'VK_Custom_Html_Control' ) ) {
			$wp_customize->add_setting(
				'color_palette_title',
				array(
					'sanitize_callback' => 'sanitize_text_field',
				)
			);
			$wp_customize->add_control(
				new VK_Custom_Html_Control(
					$wp_customize,
					'color_palette_title',
					array(
						'label'            => '',
						'section'          => 'colors',
						'type'             => 'text',
						'custom_title_sub' => __( 'Color Palette Setting', 'vk-color-palette-manager' ),
						'custom_html'      => __( 'This color is reflected in the block editor\'s color palette.', 'vk-color-palette-manager' ),
						'priority'         => 1000,
					)
				)
			);
		}

		for ( $i = 1; $i <= 5; $i++ ) {
			$wp_customize->add_setting(
				'vk_color_manager_options[color_custom_' . $i . ']',
				array(
					'default'           => '',
					'type'              => 'option',
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => 'sanitize_hex_color',
				)
			);
			$label = __( 'Custom color', 'vk-color-palette-manager' ) . ' ' . $i;
			$wp_customize->add_control(
				new WP_Customize_Color_Control(
					$wp_customize,
					'vk_color_manager_options[color_custom_' . $i . ']',
					array(
						'label'    => $label,
						'section'  => 'colors',
						'settings' => 'vk_color_manager_options[color_custom_' . $i . ']',
						'priority' => 1000,
					)
				)
			);
		}
	}


	/**
	 * Get Core Colors
	 */
	public static function get_core_colors() {
		$colors = array();
		if ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			$settings = WP_Theme_JSON_Resolver::get_core_data()->get_settings();
			if ( ! empty($settings['color']['palette']['default'] ) ) {
				$colors = $settings['color']['palette']['default'];
			} else if ( ! empty($settings['color']['palette']['core'] ) ) {
				$colors = $settings['color']['palette']['core'];
			}
		}
		return $colors;
	}

	/**
	 * Get Theme Colors
	 */
	public static function get_theme_colors() {
		$colors = array();
		if ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			$settings = WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
			if ( ! empty($settings['color']['palette']['theme'] ) ) {
				$colors = $settings['color']['palette']['theme'];
			}
		}
		return $colors;
	}

	/**
	 * Get Bootstrap Colors
	 */
	public static function get_bootstrap_colors() {
		$colors = array(
			array(
				'name'  => __( 'VK Primary Color', 'vk-color-palette-manager' ),
				'slug'  => 'vk-color-primary',
				'color' => 'var(--vk-color-primary)',
			),
			array(
				'name'  => __( 'VK Secondary Color', 'vk-color-palette-manager' ),
				'slug'  => 'vk-color-secondary',
				'color' => '#6c757d',
			),
			array(
				'name'  => __( 'VK Successs Color', 'vk-color-palette-manager' ),
				'slug'  => 'vk-color-success',
				'color' => '#28a745',
			),
			array(
				'name'  => __( 'VK Info Color', 'vk-color-palette-manager' ),
				'slug'  => 'vk-color-info',
				'color' => '#17a2b8',
			),
			array(
				'name'  => __( 'VK Warning Color', 'vk-color-palette-manager' ),
				'slug'  => 'vk-color-warning',
				'color' => '#ffc107',
			),
			array(
				'name'  => __( 'VK Danger Color', 'vk-color-palette-manager' ),
				'slug'  => 'vk-color-danger',
				'color' => '#dc3545',
			),
			array(
				'name'  => __( 'VK Light Color', 'vk-color-palette-manager' ),
				'slug'  => 'vk-color-light',
				'color' => '#f8f9fa',
			),
			array(
				'name'  => __( 'VK Dark Color', 'vk-color-palette-manager' ),
				'slug'  => 'vk-color-dark',
				'color' => '#343a40',
			),
		);
		return $colors;
	}

	/**
	 * Get Additional Colors
	 */
	public static function get_additional_colors() {
		$options_color       = self::get_option();
		$additional_colors = array();
		if ( $options_color ) {
			for ( $i = 1; $i <= 5; $i++ ) {
				if ( ! empty( $options_color[ 'color_custom_' . $i ] ) ) {
					$additional_colors[] = array(
						'name'  => __( 'Custom color', 'vk-color-palette-manager' ) . ' ' . $i,
						'slug'  => 'vk-color-custom-' . $i,
						'color' => $options_color[ 'color_custom_' . $i ],
					);
				}
			}
		}
		return apply_filters( 'vcm_add_color_array', $additional_colors );
	}

	/**
	 * Add color palettes
	 *
	 * @param array $editor_settings : editor_settings.
	 * @param array $block_editor_context : block_editor_context.
	 * @return array $editor_settings :  editor_settings.
	 */
	public static function setup_color_palette() {
		$options = self::get_option();

		$core_colors  = ! empty( $options['core_color_palette'] ) ? self::get_core_colors() : array();
		$theme_colors = ! empty( $options['theme_color_palette'] ) ? self::get_theme_colors() : array();
		$bootstrap_colors = ! empty( $options['bootstrap_color_palette'] ) ? self::get_bootstrap_colors() : array();
		$additional_colors = self::get_additional_colors();
		$colors = array_merge( $core_colors, $theme_colors, $bootstrap_colors, $additional_colors );
		add_theme_support( 'editor-color-palette', $colors );
	}

	/**
	 * Create color palettes css
	 *
	 * @return string
	 */
	public static function inline_css() {
		$options = self::get_option();

		$bootstrap_colors = ! empty( $options['bootstrap_color_palette'] ) ? self::get_bootstrap_colors() : array();
		$additional_colors = self::get_additional_colors();
		$colors = array_merge( $bootstrap_colors, $additional_colors );

		$dynamic_css = '/* VK Color Palettes */';
		foreach ( $colors as $key => $color ) {
			if ( ! empty( $color['color'] ) ) {
				// 色はこのクラスでだけの利用なら直接指定でも良いが、他のクラス名で応用できるように一旦css変数に格納している.
				$dynamic_css .= ':root{ --' . $color['slug'] . ':' . $color['color'] . '}';
				// .has- だけだと負けるので :root は迂闊に消さないように注意
				$dynamic_css .= ':root .has-' . $color['slug'] . '-color { color:var(--' . $color['slug'] . '); }';
				$dynamic_css .= ':root .has-' . $color['slug'] . '-background-color { background-color:var(--' . $color['slug'] . '); }';
				$dynamic_css .= ':root .has-' . $color['slug'] . '-border-color { border-color:var(--' . $color['slug'] . '); }';
			}
		}

		// Delete before after space.
		$dynamic_css = trim( $dynamic_css );
		// Convert tab and br to space.
		$dynamic_css = preg_replace( '/[\n\r\t]/', '', $dynamic_css );
		// Change multiple spaces to single space.
		$dynamic_css = preg_replace( '/\s(?=\s)/', '', $dynamic_css );
		return $dynamic_css;
	}

	/**
	 * Add front css
	 *
	 * @return void
	 */
	public static function add_color_palette_css() {
		$dynamic_css = self::inline_css();
		wp_add_inline_style( 'wp-block-library', $dynamic_css );
	}

	/**
	 * Add editor css
	 *
	 * @return void
	 */
	public static function add_color_palette_css_to_editor() {
		$dynamic_css = self::inline_css();
		wp_add_inline_style( 'wp-edit-blocks', $dynamic_css, 11 );
	}

}
