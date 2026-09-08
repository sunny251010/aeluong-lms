<?php
/** Extend the native Content Bank with reusable lessons, preserving its selection flow. */
defined( 'ABSPATH' ) || exit;
add_filter( 'learn-press/filter-list-items-not-assign-course', function ( $filter, $data, $course ) {
	if ( ( $data['item_type'] ?? '' ) !== 'lms_shared_lesson' ) { return $filter; }
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $course->get_id() ) ) {
		throw new Exception( 'Bạn không có quyền chọn bài học dùng chung.' );
	}
	global $wpdb;
	$filter->post_type = 'lp_lesson';
	$old = "AND p.ID NOT IN ( SELECT item_id FROM {$wpdb->prefix}learnpress_section_items )";
	$filter->where = array_values( array_filter( $filter->where, static function ( $where ) use ( $old ) { return $where !== $old; } ) );
	$filter->where[] = "AND p.ID IN (SELECT item_id FROM {$wpdb->prefix}learnpress_section_items)";
	$filter->where[] = $wpdb->prepare( "AND p.ID NOT IN (SELECT i.item_id FROM {$wpdb->prefix}learnpress_section_items i INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id=i.section_id WHERE s.section_course_id=%d)", $course->get_id() );
	return $filter;
}, 10, 3 );

function lms_site_core_enqueue_shared_bank(): void {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	wp_enqueue_script( 'lms-shared-bank', plugins_url( '../assets/js/lesson-content-bank.js', __FILE__ ), array(), '0.11.0', true );
}
add_action( 'admin_enqueue_scripts', 'lms_site_core_enqueue_shared_bank' );
add_action( 'wp_enqueue_scripts', 'lms_site_core_enqueue_shared_bank' );
