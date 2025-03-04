<?php
/*
* Plugin Name: GL HTML to SVG
* Plugin URI: https://greenlifeit.com/
* Description: A WordPress plugin to convert HTML to SVG and save to WordPress media library.
* Version: 2.0.0
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
	
	function gl_sanitize_svg_html( $svg_content, $allow_style_script = true ) : string {
		$allowed_tags = array(
			'svg'              => array(
				'xmlns'              => true,
				'xmlns:xlink'        => true,
				'xml:space'          => true,
				'xml:lang'           => true,
				'xml:base'           => true,
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
				'transform-origin'   => true,
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
				'vector-effect'      => true,
				'dominant-baseline'  => true,
				'alignment-baseline' => true,
				'baseline-shift'     => true,
				'text-anchor'        => true,
				'writing-mode'       => true,
				'font-family'        => true,
				'font-size'          => true,
				'font-style'         => true,
				'font-weight'        => true,
				'text-decoration'    => true,
				'letter-spacing'     => true,
				'word-spacing'       => true,
				'direction'          => true,
				'unicode-bidi'       => true,
				'paint-order'        => true,
				'marker-start'       => true,
				'marker-mid'         => true,
				'marker-end'         => true,
			),
			// Structure
			'defs'             => array(),
			'g'                => array(
				'fill'               => true,
				'stroke'             => true,
				'class'              => true,
				'id'                 => true,
				'transform'          => true,
				'style'              => true,
				'clip-path'          => true,
				'mask'               => true,
				'filter'             => true,
				'opacity'            => true,
			),
			'symbol'           => array(
				'id'                 => true,
				'viewBox'            => true,
				'preserveAspectRatio'=> true,
				'overflow'           => true,
			),
			'use'              => array(
				'href'               => true,
				'xlink:href'         => true,
				'x'                  => true,
				'y'                  => true,
				'width'              => true,
				'height'             => true,
				'transform'          => true,
			),
			'foreignObject',
			'tspan'            => array(
				'x'                  => true,
				'y'                  => true,
				'dx'                 => true,
				'dy'                 => true,
				'rotate'             => true,
				'lengthAdjust'       => true,
				'textLength'         => true,
				'fill'               => true,
				'stroke'             => true,
				'class'              => true,
				'style'              => true,
				'id'                 => true,
			),
			'textPath'         => array(
				'href'               => true,
				'xlink:href'         => true,
				'startOffset'        => true,
				'method'             => true,
				'spacing'            => true,
				'class'              => true,
				'style'              => true,
				'id'                 => true,
			),
			
			// Gradients and Patterns
			'linearGradient'   => array(
				'id'                 => true,
				'x1'                 => true,
				'x2'                 => true,
				'y1'                 => true,
				'y2'                 => true,
				'gradientUnits'      => true,
				'gradientTransform'  => true,
				'spreadMethod'       => true,
				'href'               => true,
				'xlink:href'         => true,
			),
			'radialGradient'   => array(
				'id'                 => true,
				'cx'                 => true,
				'cy'                 => true,
				'r'                  => true,
				'fx'                 => true,
				'fy'                 => true,
				'fr'                 => true,
				'gradientUnits'      => true,
				'gradientTransform'  => true,
				'spreadMethod'       => true,
				'href'               => true,
				'xlink:href'         => true,
			),
			'stop'             => array(
				'offset'             => true,
				'stop-color'         => true,
				'stop-opacity'       => true,
				'style'              => true,
			),
			'pattern'          => array(
				'id'                 => true,
				'x'                  => true,
				'y'                  => true,
				'width'              => true,
				'height'             => true,
				'patternUnits'       => true,
				'patternContentUnits'=> true,
				'patternTransform'   => true,
				'href'               => true,
				'xlink:href'         => true,
			),
			
			// Filters
			'filter'           => array(
				'id'                 => true,
				'x'                  => true,
				'y'                  => true,
				'width'              => true,
				'height'             => true,
				'filterUnits'        => true,
				'primitiveUnits'     => true,
				'href'               => true,
				'xlink:href'         => true,
			),
			'feBlend'          => array(
				'mode'               => true,
				'in'                 => true,
				'in2'                => true,
				'result'             => true,
			),
			'feColorMatrix'    => array(
				'type'               => true,
				'values'             => true,
				'in'                 => true,
				'result'             => true,
			),
			'feComponentTransfer' => array(
				'in'                 => true,
				'result'             => true,
			),
			'feComposite'      => array(
				'operator'           => true,
				'in'                 => true,
				'in2'                => true,
				'k1'                 => true,
				'k2'                 => true,
				'k3'                 => true,
				'k4'                 => true,
				'result'             => true,
			),
			'feConvolveMatrix' => array(
				'order'              => true,
				'kernelMatrix'       => true,
				'divisor'            => true,
				'bias'               => true,
				'targetX'            => true,
				'targetY'            => true,
				'edgeMode'           => true,
				'in'                 => true,
				'result'             => true,
			),
			'feDiffuseLighting'=> array(
				'surfaceScale'       => true,
				'diffuseConstant'    => true,
				'in'                 => true,
				'result'             => true,
			),
			'feDisplacementMap'=> array(
				'scale'              => true,
				'xChannelSelector'   => true,
				'yChannelSelector'   => true,
				'in'                 => true,
				'in2'                => true,
				'result'             => true,
			),
			'feFlood'          => array(
				'flood-color'        => true,
				'flood-opacity'      => true,
				'result'             => true,
			),
			'feFuncR'          => array(
				'type'               => true,
				'tableValues'        => true,
				'slope'              => true,
				'intercept'          => true,
				'amplitude'          => true,
				'exponent'           => true,
				'offset'             => true,
			),
			'feFuncG'          => array(
				'type'               => true,
				'tableValues'        => true,
				'slope'              => true,
				'intercept'          => true,
				'amplitude'          => true,
				'exponent'           => true,
				'offset'             => true,
			),
			'feFuncB'          => array(
				'type'               => true,
				'tableValues'        => true,
				'slope'              => true,
				'intercept'          => true,
				'amplitude'          => true,
				'exponent'           => true,
				'offset'             => true,
			),
			'feFuncA'          => array(
				'type'               => true,
				'tableValues'        => true,
				'slope'              => true,
				'intercept'          => true,
				'amplitude'          => true,
				'exponent'           => true,
				'offset'             => true,
			),
			'feGaussianBlur'   => array(
				'stdDeviation'       => true,
				'in'                 => true,
				'result'             => true,
			),
			'feImage'          => array(
				'href'               => true,
				'xlink:href'         => true,
				'result'             => true,
			),
			'feMerge'          => array(
				'result'             => true,
			),
			'feMergeNode'      => array(
				'in'                 => true,
			),
			'feMorphology'     => array(
				'operator'           => true,
				'radius'             => true,
				'in'                 => true,
				'result'             => true,
			),
			'feOffset'         => array(
				'dx'                 => true,
				'dy'                 => true,
				'in'                 => true,
				'result'             => true,
			),
			'feSpecularLighting'=> array(
				'surfaceScale'       => true,
				'specularConstant'   => true,
				'specularExponent'   => true,
				'in'                 => true,
				'result'             => true,
			),
			'feTile'           => array(
				'in'                 => true,
				'result'             => true,
			),
			'feTurbulence'     => array(
				'baseFrequency'      => true,
				'numOctaves'         => true,
				'seed'               => true,
				'stitchTiles'        => true,
				'type'               => true,
				'result'             => true,
			),
			
			// Lighting
			'feDistantLight'   => array(
				'azimuth'            => true,
				'elevation'          => true,
			),
			'fePointLight'     => array(
				'x'                  => true,
				'y'                  => true,
				'z'                  => true,
			),
			'feSpotLight'      => array(
				'x'                  => true,
				'y'                  => true,
				'z'                  => true,
				'pointsAtX'          => true,
				'pointsAtY'          => true,
				'pointsAtZ'          => true,
				'specularExponent'   => true,
				'limitingConeAngle'  => true,
			)
		);

		if ( $allow_style_script ) {
			$allowed_tags['style'] = array();
			$allowed_tags['script'] = array();
		}

		return wp_kses( $svg_content, $allowed_tags );
	}	
	
	public function convert_html_to_svg() : void {
		check_ajax_referer( 'html-to-svg-nonce', 'nonce' );
		
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error( 'Permission denied' );
		}
		
		$allow_style_script = isset($_POST['allow_style_script']) ? filter_var($_POST['allow_style_script'], FILTER_VALIDATE_BOOLEAN) : false;
		$html = isset( $_POST['html'] ) ? $this->gl_sanitize_svg_html( $_POST['html'], $allow_style_script ) : '';
		write_log( $html );
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
