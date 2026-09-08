<?php
/**
 * Local integration smoke test. Run with LocalWP's PHP CLI and php.ini.
 * Uses temporary fixtures, suppresses outgoing mail, and cleans up in finally.
 */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
$_SERVER['HTTP_HOST'] = 'aeluong-lms.local';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__ ) . '/wp-load.php';
if ( wp_parse_url( home_url(), PHP_URL_HOST ) !== 'aeluong-lms.local' ) {
	fwrite( STDERR, "This smoke test only runs on aeluong-lms.local.\n" ); exit( 1 );
}
require_once ABSPATH . 'wp-admin/includes/user.php';
add_filter( 'pre_wp_mail', '__return_true' );
$checks = 0;
function lms_smoke_check( $condition, string $label ): void {
	global $checks;
	if ( ! $condition ) { throw new RuntimeException( $label ); }
	$checks++;
	echo "PASS: $label\n";
}
$admin = get_user_by( 'login', 'admin' );
$course = \LearnPress\Models\CourseModel::find( 34, true );
$test_user_id = 0;
$test_post_id = 0;
$failed = false;
try {
	lms_smoke_check( $admin && $course && lms_site_core_is_contact_course( 34 ), 'Local fixtures available' );
	wp_set_current_user( $admin->ID );
	$test_password = wp_generate_password( 32, true, true );
	$test_user_id = wp_insert_user( array( 'user_login' => 'lms_audit_' . strtolower( wp_generate_password( 10, false ) ), 'user_pass' => $test_password, 'role' => 'student' ) );
	lms_smoke_check( ! is_wp_error( $test_user_id ), 'Create temporary Student' );
	$user_model = \LearnPress\Models\UserModel::find( $test_user_id, true );
	$legacy_user = learn_press_get_user( $test_user_id );
	$admin_native = $course->can_enroll( $user_model );
	lms_smoke_check( ! is_wp_error( $admin_native ), 'Admin enrollment eligibility retained' );

	wp_set_current_user( $test_user_id );
	$eligibility = $course->can_enroll( $user_model );
	lms_smoke_check( is_wp_error( $eligibility ) && $eligibility->get_error_code() === 'lms_manual_enrollment_required', 'Unassigned Student cannot self-enroll in contact course' );
	lms_smoke_check( false === $legacy_user->can_enroll_course( 34 ), 'Legacy boolean enrollment blocked' );
	$legacy = $legacy_user->can_enroll_course( 34, false );
	lms_smoke_check( ! $legacy->check && $legacy->code === 'lms_manual_enrollment_required', 'Legacy structured enrollment blocked' );
	lms_smoke_check( is_wp_error( \LearnPress\Models\CourseModel::find(13,true)->can_purchase($user_model) ), 'Unassigned Student cannot enter paid checkout' );
	$request = new WP_REST_Request( 'POST', '/learnpress/v1/courses/enroll' );
	$request->set_param( 'id', 34 );
	$response = rest_do_request( $request );
	$data = json_decode(wp_json_encode($response->get_data()), true);
	lms_smoke_check( isset($data['status']) && $data['status'] === 'error', 'LearnPress REST enroll endpoint rejects self-enrollment: ' . wp_json_encode($data) );
	lms_smoke_check( ! lms_site_core_get_user_course_enrollment( $test_user_id, 34 ), 'Blocked REST request creates no enrollment' );
	lms_smoke_check( ! current_user_can('edit_posts') && ! current_user_can('edit_lp_lessons'), 'Student has no content editing capability' );

	wp_set_current_user( 0 );
	lms_smoke_check( is_wp_error($course->can_enroll(false)), 'Guest self-enrollment blocked' );
	lms_smoke_check( lms_site_core_course_continue_url(34) === get_permalink(34), 'Guest Continue resolver does not reveal lesson link' );
	$login = wp_remote_post( admin_url('admin-ajax.php'), array('timeout'=>20, 'body'=>array('action'=>'lms_site_core_login','login'=>get_userdata($test_user_id)->user_login,'password'=>$test_password,'nonce'=>wp_create_nonce('lms_site_core_login'))) );
	lms_smoke_check( ! is_wp_error($login) && ! empty(json_decode(wp_remote_retrieve_body($login),true)['success']), 'Local AJAX login accepts temporary Student credentials' );

	wp_set_current_user( $admin->ID );
	lms_smoke_check( true === lms_site_core_enroll_user_in_course($test_user_id,34), 'Admin grants enrollment through native LearnPress model' );
	lms_smoke_check( 'already' === lms_site_core_enroll_user_in_course($test_user_id,34), 'Repeated grant does not duplicate enrollment' );
	wp_set_current_user($test_user_id);
	lms_smoke_check( lms_site_core_user_has_course_access(34), 'Granted Student retains course access' );
	$enrollment = lms_site_core_get_user_course_enrollment($test_user_id,34);
	$next = $enrollment->get_item_continue();
	$expected = $next ? $course->get_item_link($next->ID) : '';
	lms_smoke_check( $expected && lms_site_core_course_continue_url(34) === $expected, 'Continue resolves native curriculum item' );
	$cta = lms_site_core_course_archive_cta(array(),$course,array());
	lms_smoke_check( str_contains($cta['btn_read_more'],esc_url($expected)), 'Archive CTA links directly to native lesson' );

	wp_set_current_user( $admin->ID );
	lms_smoke_check( ! apply_filters('use_block_editor_for_post_type',true,'lp_lesson'), 'Lesson uses native continuous Visual editor' );
	$registry = WP_Block_Patterns_Registry::get_instance();
	foreach (array('lesson-outline','text-and-image','two-columns') as $slug) {
		$pattern = $registry->get_registered('lms-site-core/'.$slug);
		lms_smoke_check( $pattern && $pattern['postTypes'] === array('lp_lesson') && has_blocks($pattern['content']), 'Lesson pattern registered: '.$slug );
		lms_smoke_check( ! str_contains(do_blocks($pattern['content']),'<!-- wp:'), 'Pattern renders through core blocks: '.$slug );
	}
	$pattern = $registry->get_registered('lms-site-core/two-columns');
	$controller = new WP_REST_Posts_Controller('lp_lesson');
	$save = new WP_REST_Request('POST');
	$save->set_param('title','Temporary lesson editor smoke test');
	$save->set_param('status','draft');
	$save->set_param('content',$pattern['content']);
	lms_smoke_check( true === $controller->create_item_permissions_check($save), 'Admin can save lesson via native REST controller' );
	$saved = $controller->create_item($save);
	lms_smoke_check( ! is_wp_error($saved), 'Save block lesson draft through REST controller' );
	$test_post_id = $saved->get_data()['id'];
	lms_smoke_check( get_post($test_post_id)->post_content === $pattern['content'], 'Lesson block markup survives database round trip' );
	wp_set_current_user($test_user_id);
	lms_smoke_check( is_wp_error($controller->create_item_permissions_check($save)), 'Student cannot save lesson via native REST controller' );
	wp_set_current_user(0);
	$guest_response = wp_remote_get(home_url('/wp-json/wp/v2/lp_lesson'),array('timeout'=>20));
	lms_smoke_check( ! is_wp_error($guest_response) && wp_remote_retrieve_response_code($guest_response) === 404, 'Public Lesson REST route remains unavailable after Gutenberg enabled' );
	echo "Completed $checks checks.\n";
} catch ( Throwable $error ) {
	$failed = true;
	fwrite( STDERR, 'FAIL: ' . $error->getMessage() . "\n" );
} finally {
	wp_set_current_user( $admin ? $admin->ID : 0 );
	if ( $test_post_id ) { wp_delete_post( $test_post_id, true ); }
	if ( is_int($test_user_id) && $test_user_id > 0 ) {
		$enrollment = lms_site_core_get_user_course_enrollment($test_user_id,34);
		if($enrollment){$enrollment->delete();}
		wp_delete_user($test_user_id);
	}
}
exit( $failed ? 1 : 0 );
