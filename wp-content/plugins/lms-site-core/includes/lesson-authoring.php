<?php
/**
 * Reusable lesson layouts made entirely from WordPress core blocks.
 */
defined( 'ABSPATH' ) || exit;

function lms_site_core_register_lesson_patterns(): void {
	if ( ! post_type_exists( 'lp_lesson' ) || ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	register_block_pattern_category( 'lms-lessons', array( 'label' => 'Bố cục bài học' ) );
	$patterns = array(
		'lesson-outline' => array(
			'title' => 'Bài học: mục tiêu, nội dung và luyện tập',
			'content' => '<!-- wp:heading --><h2 class="wp-block-heading">Mục tiêu bài học</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Điền những kiến thức và kỹ năng người học sẽ đạt được.</p><!-- /wp:paragraph -->
<!-- wp:separator --><hr class="wp-block-separator has-alpha-channel-opacity"/><!-- /wp:separator -->
<!-- wp:heading --><h2 class="wp-block-heading">Nội dung chính</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Dán nội dung bài học vào đây. Chia từng chủ đề bằng khối Tiêu đề.</p><!-- /wp:paragraph -->
<!-- wp:heading --><h2 class="wp-block-heading">Luyện tập</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Thêm câu hỏi hoặc yêu cầu luyện tập để học viên áp dụng kiến thức.</p><!-- /wp:paragraph -->',
		),
		'text-and-image' => array(
			'title' => 'Hai cột: nội dung và ảnh',
			'content' => '<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading --><h2 class="wp-block-heading">Nội dung bài học</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Dán nội dung vào cột này và chọn ảnh minh họa ở cột bên cạnh.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:image /--></div><!-- /wp:column --></div><!-- /wp:columns -->',
		),
		'two-columns' => array(
			'title' => 'Hai cột: kiến thức và ví dụ',
			'content' => '<!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:heading --><h2 class="wp-block-heading">Kiến thức</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Dán phần giải thích, từ vựng hoặc công thức tại đây.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:heading --><h2 class="wp-block-heading">Ví dụ</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Dán ví dụ, bản dịch hoặc hội thoại minh họa tại đây.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->',
		),
	);

	foreach ( $patterns as $slug => $pattern ) {
		register_block_pattern(
			'lms-site-core/' . $slug,
			array_merge( $pattern, array(
				'categories' => array( 'lms-lessons' ),
				'postTypes' => array( 'lp_lesson' ),
				'inserter' => true,
			) )
		);
	}
}
add_action( 'init', 'lms_site_core_register_lesson_patterns', 30 );

/**
 * Keep authoring tips available beside the native LearnPress lesson settings.
 */
function lms_site_core_add_lesson_authoring_help(): void {
	add_meta_box( 'lms-lesson-authoring', 'Soạn bài từ Google Sites', 'lms_site_core_render_lesson_authoring_help', 'lp_lesson', 'side' );
}
add_action( 'add_meta_boxes_lp_lesson', 'lms_site_core_add_lesson_authoring_help' );

function lms_site_core_render_lesson_authoring_help(): void {
	?>
	<p>Chọn tab <strong>Visual</strong> để soạn bài. Click trong vùng nội dung rồi <strong>Ctrl+A</strong> để chọn toàn bộ bài; dùng thanh công cụ đổi font, cỡ chữ, màu và in nghiêng.</p>
	<p>Google Sites: mở Preview → chọn nội dung → Ctrl+C → dán bằng Ctrl+V. Màu chỉ giữ được khi nguồn copy có định dạng; bạn có thể chỉnh lại bằng nút màu chữ.</p>
	<p><strong>Add Media</strong>: tải ảnh lên hoặc chọn ảnh có sẵn. <strong>YouTube</strong>: dán đường link video. Bảng nhúng trong Google Sites cần copy riêng bên trong bảng.</p>
	<p>Không thể tự chuyển quyền truy cập của file Google Drive riêng tư. Hãy kiểm tra bằng tài khoản học viên sau khi lưu bài.</p>
	<?php
}

/** Preserve source typography when pasting into a lesson Classic block. */
function lms_site_core_lesson_paste_settings( array $settings ): array {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'lp_lesson' !== $screen->post_type ) {
		return $settings;
	}

	// Keep presentation only; leave the native paste and save sanitizers enabled.
	$styles = 'font-family,font-size,font-weight,font-style,color,background-color,text-decoration,text-align,line-height,vertical-align,border,border-width,border-style,border-color,border-collapse,padding';
	$settings['paste_webkit_styles'] = $styles;
	$settings['paste_retain_style_properties'] = $styles;
	$settings['wordpress_adv_hidden'] = false;
	$settings['toolbar1'] = 'formatselect,fontselect,fontsizeselect,bold,italic,underline,forecolor,backcolor';
	$settings['toolbar2'] = 'alignleft,aligncenter,alignright,bullist,numlist,link,unlink,wp_add_media,lms_youtube,undo,redo,removeformat';
	$settings['fontsize_formats'] = '12px 14px 16px 18px 20px 24px 28px 32px 40px 48px';
	$settings['font_formats'] = 'Arial=arial,helvetica,sans-serif;Verdana=verdana,geneva,sans-serif;Times New Roman=times new roman,times,serif;Georgia=georgia,palatino,serif;Lucida Sans Unicode=lucida sans unicode,sans-serif;Courier New=courier new,courier,monospace';
	$settings['body_class'] = trim( ( $settings['body_class'] ?? '' ) . ' lms-lesson-editor' );
	$css = plugins_url( '../assets/lesson-content.css', __FILE__ );
	$settings['content_css'] = ! empty( $settings['content_css'] ) ? $settings['content_css'] . ',' . $css : $css;

	return $settings;
}
add_filter( 'tiny_mce_before_init', 'lms_site_core_lesson_paste_settings' );

require_once __DIR__ . '/lesson-placement.php';

/** Load the same content rules inside Gutenberg and on the student page. */
function lms_site_core_enqueue_lesson_content_style(): void {
	if ( is_admin() ) {
		$screen = get_current_screen();
		if ( ! $screen || 'lp_lesson' !== $screen->post_type ) { return; }
	}
	wp_enqueue_style( 'lms-lesson-content', plugins_url( '../assets/lesson-content.css', __FILE__ ), array(), '0.11.0' );
}
add_action( 'enqueue_block_assets', 'lms_site_core_enqueue_lesson_content_style' );
add_action( 'wp_enqueue_scripts', 'lms_site_core_enqueue_lesson_content_style', 100 );

add_filter( 'mce_external_plugins', function ( $plugins ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'lp_lesson' === $screen->post_type ) {
		$plugins['lms_lesson_tools'] = plugins_url( '../assets/js/lesson-tinymce.js', __FILE__ );
	}
	return $plugins;
} );


/** A single native rich-text editor suits long pasted lessons and Ctrl+A. */
add_filter( 'use_block_editor_for_post_type', function ( $enabled, $post_type ) {
	return 'lp_lesson' === $post_type ? false : $enabled;
}, 100, 2 );

// Existing block lessons remain unchanged until an author saves the visual edit.
add_filter( 'the_editor_content', function ( $content ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'lp_lesson' === $screen->post_type && has_blocks( $content ) ) {
		$content = do_blocks( $content );
	}
	return $content;
}, 5 );

require_once __DIR__ . '/lesson-content-bank.php';

add_action( 'admin_enqueue_scripts', function () {
	$screen = get_current_screen();
	if ( $screen && 'lp_lesson' === $screen->post_type ) {
		wp_enqueue_style( 'lms-lesson-admin', plugins_url( '../assets/lesson-admin.css', __FILE__ ), array(), '0.11.0' );
	}
} );
