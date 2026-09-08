<?php
/** Assign existing lessons using native LearnPress curriculum models. */
defined( 'ABSPATH' ) || exit;

function lms_site_core_lesson_locations( int $lesson_id, int $course_id = 0 ): array {
	global $wpdb;
	$sql = "SELECT s.section_course_id, s.section_id, s.section_name FROM {$wpdb->prefix}learnpress_sections s INNER JOIN {$wpdb->prefix}learnpress_section_items i ON i.section_id=s.section_id WHERE i.item_id=%d";
	$args = array( $lesson_id );
	if ( $course_id ) { $sql .= ' AND s.section_course_id=%d'; $args[] = $course_id; }
	return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
}

function lms_site_core_place_lesson( int $lesson_id, int $course_id, int $section_id, int $expected_section ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $lesson_id ) || ! current_user_can( 'edit_post', $course_id ) ) {
		return new WP_Error( 'forbidden', 'Bạn không có quyền chỉnh sửa bài học và khóa học này.' );
	}
	if ( 'lp_lesson' !== get_post_type( $lesson_id ) || 'lp_course' !== get_post_type( $course_id ) || in_array( get_post_status( $course_id ), array( 'trash', 'auto-draft' ), true ) || in_array( get_post_status( $lesson_id ), array( 'trash', 'auto-draft' ), true ) ) {
		return new WP_Error( 'invalid_post', 'Hãy lưu bài học trước khi gán khóa học.' );
	}
	$locations = lms_site_core_lesson_locations( $lesson_id, $course_id );
	if ( count( $locations ) > 1 ) { return new WP_Error( 'duplicate', 'Bài đang nằm ở nhiều chương trong cùng khóa. Hãy kiểm tra curriculum của khóa.' ); }
	$current = $locations ? (int) $locations[0]->section_id : 0;
	if ( $current !== $expected_section ) { return new WP_Error( 'conflict', 'Vị trí bài đã thay đổi ở tab khác. Reload Lesson rồi thử lại.' ); }
	try {
		$section = $section_id ? \LearnPress\Models\CourseSectionModel::find( $section_id, $course_id, false ) : null;
		if ( $section_id && ! $section ) { return new WP_Error( 'invalid_section', 'Chương không thuộc khóa học đã chọn.' ); }
		if ( $current === $section_id ) { return true; }
		if ( $current ) {
			$relation = \LearnPress\Models\CourseSectionItemModel::find( $current, $lesson_id, false );
			if ( ! $relation ) { return new WP_Error( 'conflict', 'Không tìm thấy vị trí bài. Hãy reload.' ); }
			$relation->section_course_id = $course_id;
			if ( $section_id ) {
				// Invalidate the old section key before moving the same relationship row.
				$relation->clean_caches();
				$relation->section_id = $section_id;
				$relation->item_order = LP_Section_Items_DB::getInstance()->get_last_number_order( $section_id ) + 1;
				$relation->save();
			} else { $relation->delete(); }
		} else {
			$section->add_items( array( 'items' => array( array( 'id' => $lesson_id, 'type' => 'lp_lesson' ) ) ) );
		}
		$after = lms_site_core_lesson_locations( $lesson_id, $course_id );
		if ( ( $after ? (int) $after[0]->section_id : 0 ) !== $section_id ) { throw new RuntimeException( 'Không lưu được vị trí bài học.' ); }
		return true;
	} catch ( Throwable $e ) { return new WP_Error( 'placement_failed', $e->getMessage() ); }
}

add_action( 'add_meta_boxes_lp_lesson', function () {
	if ( current_user_can( 'manage_options' ) ) {
		add_meta_box( 'lms-lesson-placement', 'Khóa học và chương', 'lms_site_core_render_lesson_placement', 'lp_lesson', 'side', 'high' );
	}
} );

function lms_site_core_render_lesson_placement( WP_Post $post ): void {
	global $wpdb;
	$courses = get_posts( array( 'post_type' => 'lp_course', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
	$locations = lms_site_core_lesson_locations( $post->ID );
	$current = array();
	foreach ( $locations as $row ) { $current[ $row->section_course_id ] = (int) $row->section_id; }
	echo '<p>Một bài có thể dùng trong nhiều khóa, mỗi khóa một chương. Sửa nội dung sẽ cập nhật mọi khóa đang dùng bài.</p>';
	if ( 'auto-draft' === $post->post_status ) { echo '<p>Lưu bản nháp rồi reload để chọn khóa và chương.</p>'; return; }
	echo '<p>Chọn chương rồi bấm Lưu vị trí. Thao tác này lưu riêng với nội dung bài.</p>';
	echo '<div class="lms-lesson-placements" data-lesson="' . esc_attr( $post->ID ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'lms_lesson_placement_' . $post->ID ) ) . '">';
	foreach ( $courses as $course ) {
		if ( ! current_user_can( 'edit_post', $course->ID ) ) { continue; }
		$sections = $wpdb->get_results( $wpdb->prepare( "SELECT section_id, section_name FROM {$wpdb->prefix}learnpress_sections WHERE section_course_id=%d ORDER BY section_order, section_id", $course->ID ) );
		echo '<div class="lms-placement-row" data-course="' . esc_attr( $course->ID ) . '" data-current="' . esc_attr( $current[ $course->ID ] ?? 0 ) . '" style="margin:16px 0">';
		echo '<label for="lms-section-' . esc_attr( $course->ID ) . '"><strong>' . esc_html( $course->post_title ) . '</strong></label>';
		echo '<select style="width:100%;margin:6px 0" id="lms-section-' . esc_attr( $course->ID ) . '"><option value="0">Không gán vào khóa này</option>';
		foreach ( $sections as $section ) { printf( '<option value="%d" %s>%s</option>', $section->section_id, selected( $current[ $course->ID ] ?? 0, $section->section_id, false ), esc_html( $section->section_name ) ); }
		echo '</select><button type="button" class="button lms-save-placement">Lưu vị trí</button><p class="lms-placement-status" role="status" aria-live="polite"></p>';
		if ( ! $sections ) { echo '<p>Khóa chưa có chương. <a href="' . esc_url( get_edit_post_link( $course->ID ) ) . '">Tạo chương trong khóa học</a>.</p>'; }
		echo '</div>';
	}
	echo '</div>';
}

add_action( 'wp_ajax_lms_lesson_placement', function () {
	$lesson_id = absint( $_POST['lesson_id'] ?? 0 );
	check_ajax_referer( 'lms_lesson_placement_' . $lesson_id, 'nonce' );
	foreach ( array( 'course_id', 'section_id', 'expected_section' ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) || ! ctype_digit( (string) $_POST[ $key ] ) ) { wp_send_json_error( array( 'message' => 'Dữ liệu vị trí không hợp lệ.' ), 400 ); }
	}
	$result = lms_site_core_place_lesson( $lesson_id, absint( $_POST['course_id'] ), absint( $_POST['section_id'] ), absint( $_POST['expected_section'] ) );
	if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 ); }
	wp_send_json_success( array( 'message' => 'Đã lưu vị trí bài học.', 'section_id' => absint( $_POST['section_id'] ) ) );
} );

add_action( 'admin_enqueue_scripts', function () {
	$screen = get_current_screen();
	if ( $screen && 'lp_lesson' === $screen->post_type ) {
		wp_enqueue_script( 'lms-lesson-placement', plugins_url( '../assets/js/lesson-placement.js', __FILE__ ), array(), '0.11.0', true );
	}
} );
