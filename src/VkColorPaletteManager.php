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
			'color_palette_core'      => true,
			'color_palette_theme'     => true,
			'color_palette_bootstrap' => false,
		);
		for ( $i = 1; $i <= 5; $i++ ) {
			$default_options[ 'color_custom_' . $i ] = '';
		}
		$options = get_option( 'vk_color_manager_options' );
		return wp_parse_args( $options, $default_options );
	}

	/**
	 * Sanitize CheckBox
	 *
	 * @param bool $input .
	 * @return bool
	 */
	public static function sanitize_checkbox( $input ) {
		if ( 'true' === $input || true === $input ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get Theme JSON Color Setting
	 *
	 * @return array
	 */
	public static function get_theme_json_color_settings() {
		$color_setting = array();
		$theme_json    = get_stylesheet_directory() . '/theme.json';
		$template_json = get_template_directory() . '/theme.json';
		$json_data = array();
		if ( $theme_json ) {
			$theme_json_data = wp_json_file_decode($theme_json, array( 'associative' => true ) );
			if ( is_array( $theme_json_data ) ) {
				$json_data = array_merge( $json_data, $theme_json_data );
			}
		}
		if ( wp_get_theme()->parent() && $template_json ) {
			$template_json_data = wp_json_file_decode( $template_json, array( 'associative' => true ) );
			if ( is_array( $template_json_data ) ) {
				$json_data = array_merge( $json_data, $template_json_data );
			}
		}
		if ( isset( $json_data['settings']['color'] ) ) {
			$color_setting = $json_data['settings']['color'];
		}
		return $color_setting;
	}

	/**
	 * Customizer
	 *
	 * @param object $wp_customize : customize object.
	 */
	public static function customize_register( $wp_customize ) {
		// theme.json の色に関するデータの配列を取得
		$color_setting = self::get_theme_json_color_settings();

		// theme.json で color.palette がある場合この設定は一切効かなくなる.
		if ( isset( $color_setting['palette'] ) ) {
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
							'label'            => __( 'Editor Color Palette Setting', 'vk-color-palette-manager' ),
							'section'          => 'colors',
							'type'             => 'text',
							'custom_title_sub' => '',
							'custom_html'      => __( 'If you use a theme with theme.json, custom color settings etc. will not be displayed. If you want to add or change any color, you can customize the color from Appearance> Editor screen style.', 'vk-color-palette-manager' ),
							'priority'         => 1000,
						)
					)
				);
			}
		} else {
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
							'label'            => __( 'Editor Color Palette Setting', 'vk-color-palette-manager' ),
							'section'          => 'colors',
							'type'             => 'text',
							'custom_title_sub' => __( 'Display Color Setting', 'vk-color-palette-manager' ),
							'custom_html'      => __( 'Select a group of colors to reflect in the editor\'s color palette.', 'vk-color-palette-manager' ),
							'priority'         => 1000,
						)
					)
				);
			}

			// theme.json がある場合自動的に ON / OFF が決定される
			if ( WP_Theme_JSON_Resolver::theme_has_support() ) {				
				// Display Core Color.
				$wp_customize->add_setting(
					'vk_color_manager_options[color_palette_core]',
					array(
						'default'           => true,
						'type'              => 'option',
						'capability'        => 'edit_theme_options',
						'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
					)
				);
				$wp_customize->add_control(
					'vk_color_manager_options[color_palette_core]',
					array(
						'label'    => __( 'WordPress Standard Color', 'vk-color-palette-manager' ),
						'section'  => 'colors',
						'settings' => 'vk_color_manager_options[color_palette_core]',
						'type'     => 'checkbox',
						'priority' => 1000,
					)
				);
			}

			if ( self::get_theme_colors() ) {
				// Display Theme Color.
				$wp_customize->add_setting(
					'vk_color_manager_options[color_palette_theme]',
					array(
						'default'           => true,
						'type'              => 'option',
						'capability'        => 'edit_theme_options',
						'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
					)
				);
				$wp_customize->add_control(
					'vk_color_manager_options[color_palette_theme]',
					array(
						'label'    => __( 'Theme Color (Classic)', 'vk-color-palette-manager' ),
						'section'  => 'colors',
						'settings' => 'vk_color_manager_options[color_palette_theme]',
						'type'     => 'checkbox',
						'priority' => 1000,
					)
				);
			}

			// Display Bootstrap Color.
			$wp_customize->add_setting(
				'vk_color_manager_options[color_palette_bootstrap]',
				array(
					'default'           => false,
					'type'              => 'option',
					'capability'        => 'edit_theme_options',
					'sanitize_callback' => array( __CLASS__, 'sanitize_checkbox' ),
				)
			);
			$wp_customize->add_control(
				'vk_color_manager_options[color_palette_bootstrap]',
				array(
					'label'    => __( 'Bootstrap Color', 'vk-color-palette-manager' ),
					'section'  => 'colors',
					'settings' => 'vk_color_manager_options[color_palette_bootstrap]',
					'type'     => 'checkbox',
					'priority' => 1000,
				)
			);

			if ( class_exists( 'VK_Custom_Html_Control' ) ) {
				$wp_customize->add_setting(
					'color_palette_custom_title',
					array(
						'sanitize_callback' => 'sanitize_text_field',
					)
				);
				$wp_customize->add_control(
					new VK_Custom_Html_Control(
						$wp_customize,
						'color_palette_custom_title',
						array(
							'label'            => '',
							'section'          => 'colors',
							'type'             => 'text',
							'custom_title_sub' => __( 'Custom Color Palette Setting', 'vk-color-palette-manager' ),
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
	}


	/**
	 * Get Core Colors
	 */
	public static function get_core_colors() {
		$colors = array();
		if ( class_exists( 'WP_Theme_JSON_Resolver' ) ) {
			// コアのセッティングを取得.
			$settings = WP_Theme_JSON_Resolver::get_core_data()->get_settings();
			// デフォルトパレットが存在していたら.
			if ( ! empty( $settings['color']['palette']['default'] ) ) {
				// デフォルトのカラーを挿入.
				$colors = $settings['color']['palette']['default'];
			} elseif ( ! empty( $settings['color']['palette']['core'] ) ) {
				// コアのカラーを挿入.
				// こあのカラーとは？どの条件で入る？ → WordPress 5.7 とか 5.8 とか.
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
			// テーマのデータを取得 .
			$settings = WP_Theme_JSON_Resolver::get_theme_data()->get_settings();
			// ※ $settings['color']['palette']['theme'] はコアのパレットなど、概ねすべて結合された状態で落ちてくる .
			if ( ! empty( $settings['color']['palette']['theme'] ) ) {
				$colors = $settings['color']['palette']['theme'];
			}
		}
		return $colors;
	}

	/**
	 * Get Bootstrap Colors
	 *
	 * @return array Bootstrapの配列 .
	 */
	public static function get_bootstrap_colors() {
		$colors = array(
			array(
				'name'  => __( 'Bootstrap Primary', 'vk-color-palette-manager' ),
				'slug'  => 'bootstrap-primary',
				'color' => '#0d6efd',
			),
			array(
				'name'  => __( 'Bootstrap Secondary', 'vk-color-palette-manager' ),
				'slug'  => 'bootstrap-secondary',
				'color' => '#6c757d',
			),
			array(
				'name'  => __( 'Bootstrap Successs', 'vk-color-palette-manager' ),
				'slug'  => 'bootstrap-success',
				'color' => '#28a745',
			),
			array(
				'name'  => __( 'Bootstrap Info', 'vk-color-palette-manager' ),
				'slug'  => 'bootstrap-info',
				'color' => '#17a2b8',
			),
			array(
				'name'  => __( 'Bootstrap Warning', 'vk-color-palette-manager' ),
				'slug'  => 'bootstrap-warning',
				'color' => '#ffc107',
			),
			array(
				'name'  => __( 'Bootstrap Danger', 'vk-color-palette-manager' ),
				'slug'  => 'bootstrap-danger',
				'color' => '#dc3545',
			),
			array(
				'name'  => __( 'Bootstrap Light', 'vk-color-palette-manager' ),
				'slug'  => 'bootstrap-light',
				'color' => '#f8f9fa',
			),
			array(
				'name'  => __( 'Bootstrap Dark', 'vk-color-palette-manager' ),
				'slug'  => 'bootstrap-dark',
				'color' => '#343a40',
			),
		);
		return $colors;
	}

	/**
	 * Get Additional Colors
	 *
	 * カスタマイザーから追加する 1 〜 5 のカスタムカラー
	 *
	 * @return array カスタムカラーの配列 .
	 */
	public static function get_additional_colors() {
		$options_color     = self::get_option();
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
	 * Get Unique Colors
	 * 色配列に同じ色がある場合に重複して出力しないように配列を加工する
	 *
	 * @param array $colors colors.
	 */
	public static function get_unique_colors( $colors ) {

		$unique_colors = array();

		if ( is_array( $colors ) ) {
			foreach ( $colors as $color ) {
				if (
					is_array( $color ) &&
					! in_array( $color['name'], array_column( $unique_colors, 'name' ) ) &&
					! in_array( $color['slug'], array_column( $unique_colors, 'slug' ) ) &&
					! in_array( $color['color'], array_column( $unique_colors, 'color' ) )
				) {
					$unique_colors[] = $color;
				}
			}
		}

		return $unique_colors;
	}

	/**
	 * Add color palettes
	 * 生成したカラーパレット配列を合成・単一化してカラーパレットに登録
	 *
	 * @return void
	 */
	public static function setup_color_palette() {
		$options          = self::get_option();
		$core_colors      = ! empty( $options['color_palette_core'] ) ? self::get_core_colors() : array();
		$theme_colors     = ! empty( $options['color_palette_theme'] ) ? self::get_theme_colors() : array();
		$bootstrap_colors = ! empty( $options['color_palette_bootstrap'] ) ? self::get_bootstrap_colors() : array();
		// 1 - 5 のカスタムカラー
		$additional_colors = self::get_additional_colors();
		// 色の重複を整理 .
		$colors = self::get_unique_colors( array_merge( $core_colors, $theme_colors, $bootstrap_colors, $additional_colors ) );

		add_theme_support( 'editor-color-palette', $colors );
	}

	/**
	 * Create color palettes css
	 *
	 * @return string
	 */
	public static function inline_css() {
		$options = self::get_option();

		$bootstrap_colors  = ! empty( $options['color_palette_bootstrap'] ) ? self::get_bootstrap_colors() : array();
		$additional_colors = self::get_additional_colors();
		$colors            = self::get_unique_colors( array_merge( $bootstrap_colors, $additional_colors ) );

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
