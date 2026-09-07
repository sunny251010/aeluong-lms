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
	<p>Trong Block Editor, chọn <strong>+ → Patterns → Bố cục bài học</strong> để chèn mẫu có sẵn. Dùng List View để chọn và di chuyển cả nhóm nội dung.</p>
	<p>Copy từng đoạn từ Google Sites rồi dán vào khối văn bản. Với bài cũ dùng Classic, hãy lưu bản nháp trước khi chọn Convert to blocks.</p>
	<p>Ảnh: tải về và thêm qua Media Library. Video YouTube: dán link vào khối YouTube. Tài liệu: dùng khối File hoặc liên kết.</p>
	<p>Không thể tự chuyển quyền truy cập của file Google Drive riêng tư. Hãy kiểm tra bằng tài khoản học viên sau khi lưu bài.</p>
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=learn-press-settings&tab=advanced' ) ); ?>">Bật Block Editor: LearnPress Settings → Advanced → Enable gutenberg → Lesson</a></p>
	<?php
}
