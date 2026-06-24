<?php
/**
 * Page builders integration (Gutenberg Blocks & Elementor Widgets stubs).
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Gutenberg Block & Elementor hooks.
 */
function xpressui_register_builder_widgets() {
	// 1. Gutenberg Block Registration
	register_block_type( 'xpressui/workflow-embed', [
		'editor_script'   => 'xpressui-block-editor-stub',
		'render_callback' => 'xpressui_render_builder_block_callback',
		'attributes'      => [
			'workflowId'   => [ 'type' => 'string', 'default' => '' ],
			'isHostedLink' => [ 'type' => 'boolean', 'default' => false ],
			'projectSlug'  => [ 'type' => 'string', 'default' => '' ],
		],
	] );

	// Register block script stub for editor visual dropdown
	add_action( 'enqueue_block_editor_assets', 'xpressui_enqueue_block_editor_assets' );

	// 2. Elementor Widgets Registration
	if ( did_action( 'elementor/loaded' ) ) {
		add_action( 'elementor/widgets/register', 'xpressui_register_elementor_widget_class' );
	}
}
add_action( 'init', 'xpressui_register_builder_widgets' );

/**
 * Enqueue editor block script stub.
 */
function xpressui_enqueue_block_editor_assets() {
	$workflows = [];
	foreach ( xpressui_get_installed_workflow_slugs() as $slug ) {
		$meta = xpressui_get_workflow_manifest_meta( $slug );
		$workflows[] = [
			'value' => $slug,
			'label' => ! empty( $meta['projectName'] ) ? $meta['projectName'] : $slug,
		];
	}

	// Hosted links across all installed workflows — embedded with the `link` attribute
	// (`[xpressui link="<id>"]`), which resolves its own workflow. Without these in the
	// dropdown the block could only embed bare workflows, not hosted-link pages.
	$links    = [];
	$base_dir = xpressui_get_workflows_base_dir();
	if ( '' !== $base_dir && is_dir( $base_dir ) ) {
		$subdirs = glob( trailingslashit( $base_dir ) . '*', GLOB_ONLYDIR );
		if ( is_array( $subdirs ) ) {
			foreach ( $subdirs as $subdir ) {
				$slug      = basename( $subdir );
				$wf_meta   = xpressui_get_workflow_manifest_meta( $slug );
				$wf_label  = ! empty( $wf_meta['projectName'] ) ? $wf_meta['projectName'] : $slug;
				$link_dirs = glob( trailingslashit( $subdir ) . 'hosted-links/*', GLOB_ONLYDIR );
				if ( ! is_array( $link_dirs ) ) {
					continue;
				}
				foreach ( $link_dirs as $link_dir ) {
					$link_id = basename( $link_dir );
					$cfg     = xpressui_get_hosted_link_config( $slug, $link_id );
					$present = ( is_array( $cfg ) && isset( $cfg['presentation'] ) && is_array( $cfg['presentation'] ) ) ? $cfg['presentation'] : [];
					$link_lbl = trim( (string) ( $present['title'] ?? $present['label'] ?? ( is_array( $cfg ) ? ( $cfg['label'] ?? '' ) : '' ) ) );
					$links[] = [
						'value' => $slug . ':' . $link_id,
						'label' => $wf_label . ' — ' . ( '' !== $link_lbl ? $link_lbl : $link_id ),
					];
				}
			}
		}
	}

	wp_register_script(
		'xpressui-block-editor-stub',
		plugins_url( 'assets/block-editor-stub.js', __DIR__ ),
		[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ],
		XPRESSUI_BRIDGE_VERSION,
		true
	);

	wp_localize_script( 'xpressui-block-editor-stub', 'xpressuiEditorData', [
		'workflows'   => $workflows,
		'links'       => $links,
		'placeholder' => __( 'Select a form or hosted link…', 'xpressui-bridge' ),
		'renderNote'  => __( 'This form renders on the published page.', 'xpressui-bridge' ),
	] );

	wp_enqueue_script( 'xpressui-block-editor-stub' );
}

/**
 * Render callback for Gutenberg block.
 *
 * @param array $attributes Block attributes.
 * @return string Block HTML output.
 */
function xpressui_render_builder_block_callback( $attributes ) {
	$workflow_id = isset( $attributes['workflowId'] ) ? sanitize_title( $attributes['workflowId'] ) : '';
	if ( empty( $workflow_id ) ) {
		return '<div class="xpressui-block-placeholder" style="border: 2px dashed #cbd5e1; padding: 20px; text-align: center; color: #64748b; font-family: sans-serif; border-radius: 8px;">'
			. '<strong>IntakeFlow Form</strong><br>'
			. esc_html__( 'Please select a workflow in the block settings.', 'xpressui-bridge' )
			. '</div>';
	}

	$is_hosted   = ! empty( $attributes['isHostedLink'] );
	$project_slug = isset( $attributes['projectSlug'] ) ? sanitize_title( $attributes['projectSlug'] ) : '';

	if ( $is_hosted ) {
		$shortcode = sprintf(
			'[xpressui id="%s" link="%s"]',
			esc_attr( $project_slug ),
			esc_attr( $workflow_id )
		);
	} else {
		$shortcode = sprintf(
			'[xpressui id="%s"]',
			esc_attr( $workflow_id )
		);
	}

	return do_shortcode( $shortcode );
}

/**
 * Register Elementor widget class.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
 */
function xpressui_register_elementor_widget_class( $widgets_manager ) {
	class XPressUI_Elementor_Widget extends \Elementor\Widget_Base {
		public function get_name() {
			return 'xpressui-workflow-embed';
		}
		public function get_title() {
			return __( 'IntakeFlow Form', 'xpressui-bridge' );
		}
		public function get_icon() {
			return 'eicon-form-horizontal';
		}
		public function get_categories() {
			return [ 'general' ];
		}
		protected function register_controls() {
			$workflows = [ '' => __( 'Select a workflow...', 'xpressui-bridge' ) ];
			foreach ( xpressui_get_installed_workflow_slugs() as $slug ) {
				$meta = xpressui_get_workflow_manifest_meta( $slug );
				$workflows[ $slug ] = ! empty( $meta['projectName'] ) ? $meta['projectName'] : $slug;
			}

			$this->start_controls_section(
				'section_content',
				[
					'label' => __( 'Form Settings', 'xpressui-bridge' ),
				]
			);

			$this->add_control(
				'workflow_id',
				[
					'label'   => __( 'Select Workflow', 'xpressui-bridge' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => '',
					'options' => $workflows,
				]
			);

			$this->add_control(
				'is_hosted_link',
				[
					'label'        => __( 'Is Hosted Link?', 'xpressui-bridge' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => __( 'Yes', 'xpressui-bridge' ),
					'label_off'    => __( 'No', 'xpressui-bridge' ),
					'return_value' => 'yes',
					'default'      => '',
				]
			);

			$this->end_controls_section();
		}
		protected function render() {
			$settings = $this->get_settings_for_display();
			$workflow_id = isset( $settings['workflow_id'] ) ? sanitize_title( $settings['workflow_id'] ) : '';
			if ( empty( $workflow_id ) ) {
				echo '<div class="xpressui-block-placeholder" style="border: 2px dashed #cbd5e1; padding: 20px; text-align: center; color: #64748b; font-family: sans-serif; border-radius: 8px;">'
					. '<strong>IntakeFlow Form</strong><br>'
					. esc_html__( 'Please select a workflow in the widget settings.', 'xpressui-bridge' )
					. '</div>';
				return;
			}
			$is_hosted = $settings['is_hosted_link'] === 'yes';
			if ( $is_hosted ) {
				$base_dir = xpressui_get_workflows_base_dir();
				$link_dirs = glob( trailingslashit( $base_dir ) . $workflow_id . '/hosted-links/*', GLOB_ONLYDIR );
				$link_id = '';
				if ( is_array( $link_dirs ) && ! empty( $link_dirs ) ) {
					$link_id = basename( $link_dirs[0] );
				}
				$shortcode = sprintf(
					'[xpressui id="%s" link="%s"]',
					esc_attr( $workflow_id ),
					esc_attr( $link_id )
				);
			} else {
				$shortcode = sprintf(
					'[xpressui id="%s"]',
					esc_attr( $workflow_id )
				);
			}
			echo do_shortcode( $shortcode );
		}
	}

	$widgets_manager->register( new XPressUI_Elementor_Widget() );
}
