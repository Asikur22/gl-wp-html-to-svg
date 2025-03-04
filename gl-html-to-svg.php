<?php
/*
* Plugin Name: GL HTML to SVG
* Plugin URI: https://greenlifeit.com/
* Description: A WordPress plugin to convert HTML to SVG and save to WordPress media library.
* Version: 1.0.0
* Author: Asiqur Rahman <asikur22@gmail.com>
* Author URI: https://asique.net/
* License: GPL v2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: gl-html-to-svg
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GL_HTML_to_SVG {
	private static $instance = null;
	
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	private function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_convert_html_to_svg', array( $this, 'convert_html_to_svg' ) );
		add_filter('upload_mimes', array( $this, 'allow_svg_uploads' ) );
		
	}

	public function allow_svg_uploads($mimes) {
		$mimes['svg'] = 'image/svg+xml';

		return $mimes;
	}
	
	public function enqueue_scripts() {
		wp_enqueue_style( 'gl-html-to-svg', plugins_url( 'css/html-to-svg.css', __FILE__ ), array(), '1.0.0' );
		
		wp_enqueue_media();
		wp_enqueue_script( 'gl-html-to-svg', plugins_url( 'js/html-to-svg.js', __FILE__ ), array( 'jquery', 'media-views' ), '1.0.0', true );
		wp_localize_script( 'gl-html-to-svg', 'wp_html_to_svg', array(
			'nonce' => wp_create_nonce( 'html-to-svg-nonce' )
		) );
		
		add_action( 'print_media_templates', array( $this, 'print_media_templates' ) );
	}
	
	public function print_media_templates() {
		?>
		<script type="text/html" id="tmpl-gl_html_to_svg">
			<div class="html-to-svg">
				<div class="html-to-svg__editor">
					<div class="html-to-svg__field">
						<label for="html-to-svg__editor__textarea">SVG Content *</label>
						<textarea id="html-to-svg__editor__textarea" class="large-text code" required></textarea>
					</div>
					<div class="html-to-svg__field">
						<label for="html-to-svg__editor__name">SVG File Name</label>
						<input type="text" id="html-to-svg__editor__name" class="regular-text"/>
					</div>
					<div class="html-to-svg__field">
						<label>
							<input type="checkbox" id="html-to-svg__editor__allow_style_script"/>
							Allow style and script tags in SVG
						</label>
					</div>
				</div>
				<div class="html-to-svg__actions">
					<button class="html-to-svg__actions__convert button button-primary">Convert to SVG</button>
				</div>
			</div>
		</script>
		<?php
	}
	
	function gl_sanitize_svg_html( $svg, $allow_style_script = true ) : string {
		$allowed_tags = array(
			'svg'              => array(
				'xmlns'              => true,
				'xmlns:xlink'        => true,
				'xml:space'          => true,
				'src'                => true,
				'width'              => true,
				'height'             => true,
				'viewBox'            => true,
				'viewbox'            => true,
				'preserveAspectRatio'=> true,
				'version'            => true,
				'class'              => true,
				'id'                 => true,
				'x'                  => true,
				'y'                  => true,
				'style'              => true,
				'fill'               => true,
				'stroke'             => true,
				'stroke-width'       => true,
				'stroke-linecap'     => true,
				'stroke-linejoin'    => true,
				'stroke-miterlimit'  => true,
				'stroke-dasharray'   => true,
				'stroke-dashoffset'  => true,
				'stroke-opacity'     => true,
				'fill-opacity'       => true,
				'fill-rule'          => true,
				'clip-rule'          => true,
				'transform'          => true,
				'opacity'            => true,
				'aria-hidden'        => true,
				'aria-label'         => true,
				'aria-labelledby'    => true,
				'aria-describedby'   => true,
				'role'               => true,
				'focusable'          => true,
				'tabindex'           => true,
				'data-*'             => true,
				'overflow'           => true,
				'color'              => true,
				'cursor'             => true,
				'display'            => true,
				'visibility'         => true,
				'mask'               => true,
				'filter'             => true,
				'clip-path'          => true,
				'enable-background'  => true,
				'shape-rendering'    => true,
				'pointer-events'     => true,
			),
			// Structure
			'defs'             => array(),
			'g'                => array( 'fill' => true, 'stroke' => true, 'class' => true, 'id' => true ),
			'symbol'           => array( 'id' => true, 'viewBox' => true ),
			'use'              => array( 'href' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true ),
			
			// Shapes
			'circle'           => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'style' => true ),
			'ellipse'          => array( 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true ),
			'line'             => array( 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true, 'stroke' => true ),
			'polygon'          => array( 'points' => true, 'fill' => true, 'stroke' => true ),
			'polyline'         => array( 'points' => true, 'fill' => true, 'stroke' => true ),
			'rect'             => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'rx' => true, 'ry' => true ),
			'path'             => array( 'd' => true, 'fill' => true, 'stroke' => true ),
			
			// Text
			'text'             => array( 'x' => true, 'y' => true, 'fill' => true, 'stroke' => true, 'class' => true ),
			'tspan'            => array( 'x' => true, 'y' => true, 'class' => true ),
			'textPath'         => array( 'href' => true ),
			
			// Gradients & Patterns
			'linearGradient'   => array( 'id' => true, 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true, 'gradientUnits' => true ),
			'radialGradient'   => array( 'id' => true, 'cx' => true, 'cy' => true, 'r' => true ),
			'stop'             => array( 'offset' => true, 'stop-color' => true, 'stop-opacity' => true ),
			'pattern'          => array( 'id' => true, 'patternUnits' => true, 'width' => true, 'height' => true ),
			
			// Clipping & Masking
			'clipPath'         => array( 'id' => true ),
			'mask'             => array( 'id' => true ),
			
			// Animation
			'animate'          => array( 'attributeName' => true, 'from' => true, 'to' => true, 'dur' => true, 'repeatCount' => true ),
			'animateTransform' => array( 'attributeName' => true, 'type' => true, 'from' => true, 'to' => true, 'dur' => true, 'repeatCount' => true ),
			
			// Filters
			'filter'           => array( 'id' => true, 'filterUnits' => true ),
			'feGaussianBlur'   => array( 'stdDeviation' => true ),
			'feOffset'         => array( 'dx' => true, 'dy' => true ),
			'feMerge'          => array(),
			'feMergeNode'      => array(),
			
			// Metadata
			'title'            => array(),
			'desc'             => array(),
			'metadata'         => array(),
		);

		// Add style and script tags if allowed
		if ($allow_style_script) {
			$allowed_tags['style'] = [];
			$allowed_tags['script'] = [];
		}
		
		return wp_kses( $svg, $allowed_tags );
	}
	
	public function convert_html_to_svg() : void {
		check_ajax_referer( 'html-to-svg-nonce', 'nonce' );
		
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( 'Permission denied' );
		}
		
		$allow_style_script = isset($_POST['allow_style_script']) ? filter_var($_POST['allow_style_script'], FILTER_VALIDATE_BOOLEAN) : false;
		$html = isset( $_POST['html'] ) ? $this->gl_sanitize_svg_html( $_POST['html'], $allow_style_script ) : '';
		if ( empty( $html ) ) {
			wp_send_json_error( 'SVG content is required' );
		}
		
		if ( isset( $_POST['name'] ) ) {
			$filename = sanitize_file_name( $_POST['name'] );
			
			if ( pathinfo( $filename, PATHINFO_EXTENSION ) === '' ) {
				$filename = $filename . '.svg';
			}
		} else {
			$filename = 'svg-' . time() . '.svg';
		}
		
		$upload_dir = wp_upload_dir();
		$filename   = wp_unique_filename( $upload_dir['path'], $filename );
		$file_path   = $upload_dir['path'] . '/' . $filename;
		
		try {
			write_log( $html );
			// write_log( $filename );
			file_put_contents( $file_path, $html );
			
			$attachment = array(
				'post_mime_type' => 'image/svg+xml',
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			);
			
			$attach_id = wp_insert_attachment( $attachment, $file_path );
			
			if ( is_wp_error( $attach_id ) ) {
				wp_send_json_error( 'Failed to save SVG to media library' );
			}
			
			wp_send_json_success( array( 'svg' => $attach_id, ) );
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}
	}
}

// Initialize the plugin
GL_HTML_to_SVG::get_instance();