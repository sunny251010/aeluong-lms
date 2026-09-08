<?php
/**
 * Plugin Name: LMS Site Core
 * Description: Project-owned LMS behavior that complements LearnPress without replacing it.
 * Version: 0.10.0
 * Author: LMS Project
 * Text Domain: lms-site-core
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/lesson-authoring.php';

/**
 * Send the site homepage to LearnPress course archive.
 */
function lms_site_core_redirect_home_to_courses(): void {
	if ( ! is_front_page() || is_admin() || is_feed() || wp_doing_ajax() ) {
		return;
	}

	$courses_url = get_post_type_archive_link( 'lp_course' );

	if ( $courses_url ) {
		wp_safe_redirect( $courses_url, 302 );
		exit;
	}
}
add_action( 'template_redirect', 'lms_site_core_redirect_home_to_courses', 20 );

/**
 * Detect the LearnPress checkout page without removing or disabling it.
 */
function lms_site_core_is_checkout_request(): bool {
	if ( is_page( array( 'checkout', 'lp-checkout' ) ) ) {
		return true;
	}

	if ( function_exists( 'learn_press_get_page_id' ) ) {
		$checkout_page_id = absint( learn_press_get_page_id( 'checkout' ) );

		if ( $checkout_page_id > 0 && is_page( $checkout_page_id ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Resolve a course ID when LearnPress includes it in the checkout URL.
 */
function lms_site_core_checkout_course_id(): int {
	$keys = array( 'course_id', 'lp_item', 'item_id', 'course' );

	foreach ( $keys as $key ) {
		if ( isset( $_GET[ $key ] ) ) {
			$course_id = absint( wp_unslash( $_GET[ $key ] ) );

			if ( $course_id > 0 ) {
				return $course_id;
			}
		}
	}

	return 0;
}

/**
 * Temporarily keep restricted learners out of checkout while access is manual.
 */
function lms_site_core_redirect_restricted_checkout(): void {
	if ( is_admin() || is_feed() || wp_doing_ajax() || ! is_user_logged_in() ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) || ! lms_site_core_is_checkout_request() ) {
		return;
	}

	$course_id = lms_site_core_checkout_course_id();

	if ( $course_id > 0 && lms_site_core_user_has_course_access( $course_id ) ) {
		return;
	}

	$target_url = $course_id > 0 ? get_permalink( $course_id ) : get_post_type_archive_link( 'lp_course' );

	if ( ! $target_url ) {
		return;
	}

	$target_url = add_query_arg( 'lms_access', 'restricted', $target_url );

	wp_safe_redirect( $target_url, 302 );
	exit;
}
add_action( 'template_redirect', 'lms_site_core_redirect_restricted_checkout', 5 );

/**
 * Hide optional LMS navigation items while keeping their pages available.
 *
 * Remove a path from this list when the related feature should be visible again.
 */
function lms_site_core_hide_optional_navigation( array $items ): array {
	$hidden_paths = array(
		'/checkout',
		'/lp-checkout',
		'/instructor',
		'/instructors',
		'/become-a-teacher',
		'/become_a_teacher',
		'/become-an-instructor',
	);

	foreach ( $items as $key => $item ) {
		$path = wp_parse_url( $item->url, PHP_URL_PATH );

		if ( ! is_string( $path ) ) {
			continue;
		}

		$path = untrailingslashit( strtolower( $path ) );

		foreach ( $hidden_paths as $hidden_path ) {
			$hidden_path = untrailingslashit( strtolower( $hidden_path ) );

			if ( $path === $hidden_path || str_starts_with( $path, $hidden_path . '/' ) ) {
				unset( $items[ $key ] );
				break;
			}
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'lms_site_core_hide_optional_navigation', 20 );
/**
 * Exclude the same optional pages from Kadence fallback page menus.
 */
function lms_site_core_exclude_optional_pages( array $exclude ): array {
	$slugs = array(
		'checkout',
		'lp-checkout',
		'instructor',
		'instructors',
		'become-a-teacher',
		'become_a_teacher',
		'become-an-instructor',
	);

	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );

		if ( $page ) {
			$exclude[] = $page->ID;
		}
	}

	return array_values( array_unique( $exclude ) );
}
add_filter( 'wp_list_pages_excludes', 'lms_site_core_exclude_optional_pages' );
/**
 * Exclude optional pages when the theme renders a fallback page menu.
 */
function lms_site_core_fallback_page_menu_args( array $args ): array {
	$args['exclude'] = implode( ',', lms_site_core_exclude_optional_pages( array() ) );

	return $args;
}
add_filter( 'wp_page_menu_args', 'lms_site_core_fallback_page_menu_args' );
/**
 * Remove optional page items from themes that render menu HTML directly.
 */
function lms_site_core_filter_navigation_html( string $items, $args ): string {
	$hidden_paths = array(
		'/checkout',
		'/lp-checkout',
		'/instructor',
		'/instructors',
		'/become-a-teacher',
		'/become_a_teacher',
		'/become-an-instructor',
	);

	foreach ( $hidden_paths as $hidden_path ) {
		$items = (string) preg_replace(
			'#<li[^>]*>[[:space:]]*<a[^>]*href=[^ >]*' . preg_quote( $hidden_path, '#' ) . '(?:/|[ >])[^>]*>.*?</a>[[:space:]]*</li>#is',
			'',
			$items
		);
	}

	return $items;
}
add_filter( 'wp_nav_menu_items', 'lms_site_core_filter_navigation_html', 20, 2 );
add_filter( 'wp_page_menu', 'lms_site_core_filter_navigation_html', 20, 2 );
/**
 * Hide optional LearnPress admin entries without disabling their functionality.
 */
function lms_site_core_cleanup_admin_menu(): void {
	remove_submenu_page( 'learn_press', 'edit.php?post_type=lp_order' );
	$hidden_submenus = array(
		'learn-press-statistics',
		'learn-press-addons',
		'learn-press-themes',
		'learn-press-tools',
		'learn-press-help-center',
	);

	foreach ( $hidden_submenus as $submenu ) {
		remove_submenu_page( 'learn_press', $submenu );
	}
}
add_action( 'admin_menu', 'lms_site_core_cleanup_admin_menu', 9999 );


/**
 * Add a small admin tool for manually enrolling a student into a course.
 */


/**
 * Ensure manual LMS accounts use a dedicated Student role.
 */
function lms_site_core_ensure_student_role(): void {
	if ( get_role( 'student' ) ) {
		return;
	}

	$subscriber = get_role( 'subscriber' );
	$capabilities = $subscriber ? $subscriber->capabilities : array( 'read' => true );
	add_role( 'student', 'Student', $capabilities );
}
add_action( 'init', 'lms_site_core_ensure_student_role', 5 );
register_activation_hook( __FILE__, 'lms_site_core_ensure_student_role' );

/**
 * Add a small admin tool for creating students and managing course access.
 */
function lms_site_core_add_enrollment_admin_page(): void {
	add_submenu_page(
		'learn_press',
		'Students & enrollment',
		'Students & enrollment',
		'manage_options',
		'lms-site-core-enroll',
		'lms_site_core_render_enrollment_admin_page'
	);
}
add_action( 'admin_menu', 'lms_site_core_add_enrollment_admin_page', 40 );

/**
 * Find an active LearnPress enrollment for a specific user/course pair.
 */
function lms_site_core_get_user_course_enrollment( int $user_id, int $course_id ) {
	if ( $user_id <= 0 || $course_id <= 0 || ! class_exists( '\LearnPress\Models\UserItems\UserCourseModel' ) ) {
		return false;
	}

	return \LearnPress\Models\UserItems\UserCourseModel::find( $user_id, $course_id, false );
}

function lms_site_core_user_has_course_access_for_user( int $user_id, int $course_id ): bool {
	$enrollment = lms_site_core_get_user_course_enrollment( $user_id, $course_id );
	$active_statuses = array( 'enrolled', 'purchased', 'finished', 'completed' );

	return $enrollment instanceof \LearnPress\Models\UserItems\UserCourseModel
		&& in_array( $enrollment->get_status(), $active_statuses, true );
}

/**
 * Create a LearnPress enrollment using its official enrollment tool/model.
 */
function lms_site_core_enroll_user_in_course( int $user_id, int $course_id ) {
	if ( class_exists( '\LearnPress\MCP\Domain\EnrollmentTools' ) ) {
		$result = \LearnPress\MCP\Domain\EnrollmentTools::enroll_student(
			array(
				'user_id'   => $user_id,
				'course_id' => $course_id,
				'status'    => 'enrolled',
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return ! empty( $result['already_enrolled'] ) ? 'already' : true;
	}

	if ( ! class_exists( '\LearnPress\Models\UserItems\UserCourseModel' ) ) {
		return new WP_Error( 'learnpress_unavailable', 'LearnPress enrollment model is unavailable.' );
	}

	try {
		$existing = lms_site_core_get_user_course_enrollment( $user_id, $course_id );
		$active_statuses = array( 'enrolled', 'purchased', 'finished', 'completed' );

		if ( $existing instanceof \LearnPress\Models\UserItems\UserCourseModel && in_array( $existing->get_status(), $active_statuses, true ) ) {
			return 'already';
		}

		$enrollment             = new \LearnPress\Models\UserItems\UserCourseModel();
		$enrollment->user_id    = $user_id;
		$enrollment->item_id    = $course_id;
		$enrollment->item_type  = LP_COURSE_CPT;
		$enrollment->ref_type   = '';
		$enrollment->status     = 'enrolled';
		$enrollment->graduation = 'in-progress';
		$enrollment->start_time = gmdate( 'Y-m-d H:i:s' );
		$enrollment->save();

		return true;
	} catch ( Throwable $exception ) {
		return new WP_Error( 'enrollment_failed', $exception->getMessage() );
	}
}

function lms_site_core_admin_enrollment_redirect( string $notice, string $message, int $student_id = 0 ): void {
	$args = array(
		'page'               => 'lms-site-core-enroll',
		'lms_admin_notice'   => $notice,
		'lms_admin_message'  => $message,
	);

	if ( $student_id > 0 ) {
		$args['student_id'] = $student_id;
	}

	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}

function lms_site_core_process_create_student_admin_form(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to create students.', 'lms-site-core' ) );
	}

	check_admin_referer( 'lms_site_core_create_student', 'lms_site_core_create_student_nonce' );

	$username = isset( $_POST['student_username'] ) ? sanitize_user( wp_unslash( $_POST['student_username'] ), true ) : '';
	$name     = isset( $_POST['student_name'] ) ? sanitize_text_field( wp_unslash( $_POST['student_name'] ) ) : '';
	$email    = isset( $_POST['student_email'] ) ? sanitize_email( wp_unslash( $_POST['student_email'] ) ) : '';
	$password = isset( $_POST['student_password'] ) ? (string) wp_unslash( $_POST['student_password'] ) : '';

	if ( '' === $username || '' === $password ) {
		lms_site_core_admin_enrollment_redirect( 'error', 'Username and password are required.' );
	}

	if ( username_exists( $username ) ) {
		lms_site_core_admin_enrollment_redirect( 'error', 'This username already exists.' );
	}

	if ( $email && email_exists( $email ) ) {
		lms_site_core_admin_enrollment_redirect( 'error', 'This email already belongs to another account.' );
	}

	$user_id = wp_insert_user(
		array(
			'user_login'   => $username,
			'user_pass'    => $password,
			'display_name' => $name ?: $username,
			'user_email'   => $email,
			'role'         => 'student',
		)
	);

	if ( is_wp_error( $user_id ) ) {
		lms_site_core_admin_enrollment_redirect( 'error', $user_id->get_error_message() );
	}

	lms_site_core_admin_enrollment_redirect( 'success', 'Student created successfully.', (int) $user_id );
}
add_action( 'admin_post_lms_site_core_create_student', 'lms_site_core_process_create_student_admin_form' );

function lms_site_core_process_student_courses_admin_form(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to manage student access.', 'lms-site-core' ) );
	}

	check_admin_referer( 'lms_site_core_set_student_courses', 'lms_site_core_set_student_courses_nonce' );

	$student_id = isset( $_POST['student_id'] ) ? absint( $_POST['student_id'] ) : 0;
	$student    = $student_id > 0 ? get_userdata( $student_id ) : false;
	$course_ids = isset( $_POST['course_ids'] ) && is_array( $_POST['course_ids'] )
		? array_values( array_filter( array_map( 'absint', $_POST['course_ids'] ) ) )
		: array();

	if ( ! $student || ! in_array( 'student', (array) $student->roles, true ) ) {
		lms_site_core_admin_enrollment_redirect( 'error', 'Please select a valid Student account.' );
	}

	$granted = 0;
	$already = 0;

	foreach ( $course_ids as $course_id ) {
		$course = get_post( $course_id );

		if ( ! $course || 'lp_course' !== $course->post_type || 'publish' !== $course->post_status ) {
			continue;
		}

		$result = lms_site_core_enroll_user_in_course( $student_id, $course_id );

		if ( 'already' === $result ) {
			$already++;
		} elseif ( true === $result ) {
			$granted++;
		}
	}

	$message = sprintf( 'Granted access to %d course(s). Existing access kept: %d.', $granted, $already );
	lms_site_core_admin_enrollment_redirect( 'success', $message, $student_id );
}
add_action( 'admin_post_lms_site_core_set_student_courses', 'lms_site_core_process_student_courses_admin_form' );

function lms_site_core_render_enrollment_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$search = isset( $_GET['student_search'] ) ? sanitize_text_field( wp_unslash( $_GET['student_search'] ) ) : '';
	$query_args = array(
		'role'        => 'student',
		'orderby'     => 'registered',
		'order'       => 'DESC',
		'number'      => 50,
		'count_total' => false,
	);

	if ( '' !== $search ) {
		$query_args['search'] = '*' . $search . '*';
		$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name' );
	}

	$students       = get_users( $query_args );
	$courses        = get_posts(
		array(
			'post_type'      => 'lp_course',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	$selected_id    = isset( $_GET['student_id'] ) ? absint( $_GET['student_id'] ) : 0;
	$selected       = $selected_id > 0 ? get_userdata( $selected_id ) : false;
	$notice         = isset( $_GET['lms_admin_notice'] ) ? sanitize_key( wp_unslash( $_GET['lms_admin_notice'] ) ) : '';
	$message        = isset( $_GET['lms_admin_message'] ) ? sanitize_text_field( wp_unslash( $_GET['lms_admin_message'] ) ) : '';
	?>
	<div class="wrap">
		<h1>Students &amp; enrollment</h1>
		<?php if ( 'success' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php elseif ( 'error' === $notice ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php endif; ?>

		<div style="display:grid;grid-template-columns:minmax(280px,1fr) minmax(280px,1fr);gap:24px;max-width:1100px;">
			<div>
				<h2>Create student</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="lms_site_core_create_student">
					<?php wp_nonce_field( 'lms_site_core_create_student', 'lms_site_core_create_student_nonce' ); ?>
					<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;max-width:700px;">
						<p style="margin:0;">
							<label for="lms-student-name"><strong>Full name</strong></label><br>
							<input style="width:100%;" id="lms-student-name" name="student_name" type="text">
						</p>
						<p style="margin:0;">
							<label for="lms-student-username"><strong>Username</strong></label><br>
							<input style="width:100%;" id="lms-student-username" name="student_username" type="text" required>
						</p>
						<p style="margin:0;">
							<label for="lms-student-email"><strong>Email</strong></label><br>
							<input style="width:100%;" id="lms-student-email" name="student_email" type="email">
						</p>
						<p style="margin:0;">
							<label for="lms-student-password"><strong>Password</strong></label><br>
							<span style="display:flex;gap:8px;align-items:center;">
								<input style="width:100%;min-width:0;" id="lms-student-password" name="student_password" type="password" required>
								<button type="button" class="button" id="lms-student-password-toggle" aria-pressed="false">Show</button>
							</span>
						</p>
					</div>
					<?php submit_button( 'Create student' ); ?>
				</form>
			</div>

			<div>
				<h2>Find students</h2>
				<form method="get">
					<input type="hidden" name="page" value="lms-site-core-enroll">
					<p>
						<label class="screen-reader-text" for="lms-student-search">Search students</label>
						<input id="lms-student-search" name="student_search" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="Name, username or email">
						<?php submit_button( 'Search', 'secondary', '', false ); ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=lms-site-core-enroll' ) ); ?>">Reset</a>
					</p>
				</form>
				<p class="description">Sorted by registration date, newest first. Select a row to manage course access.</p>
			</div>
		</div>

		<h2>Recently registered students</h2>
		<table class="widefat striped" style="max-width:1100px;">
			<thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Registered</th><th>Action</th></tr></thead>
			<tbody>
			<?php if ( empty( $students ) ) : ?>
				<tr><td colspan="5">No Student accounts found.</td></tr>
			<?php else : ?>
				<?php foreach ( $students as $student ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $student->display_name ?: $student->user_login ); ?></strong></td>
						<td><?php echo esc_html( $student->user_login ); ?></td>
						<td><?php echo esc_html( $student->user_email ?: '—' ); ?></td>
						<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $student->user_registered ) ); ?></td>
						<td><a class="button button-secondary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'lms-site-core-enroll', 'student_id' => $student->ID ), admin_url( 'admin.php' ) ) ); ?>">Manage courses</a></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>

		<?php if ( $selected && in_array( 'student', (array) $selected->roles, true ) ) : ?>
			<div id="student-courses" style="max-width:1100px;margin-top:28px;">
				<h2>Manage courses for <?php echo esc_html( $selected->display_name ?: $selected->user_login ); ?></h2>
				<p><?php echo esc_html( $selected->user_login . ( $selected->user_email ? ' · ' . $selected->user_email : '' ) ); ?></p>
				<p class="description">Checked courses already have access. This screen grants access; it does not remove progress.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="lms_site_core_set_student_courses">
					<input type="hidden" name="student_id" value="<?php echo esc_attr( $selected->ID ); ?>">
					<?php wp_nonce_field( 'lms_site_core_set_student_courses', 'lms_site_core_set_student_courses_nonce' ); ?>
					<table class="widefat striped">
						<thead><tr><th>Grant</th><th>Course</th><th>Status</th></tr></thead>
						<tbody>
						<?php foreach ( $courses as $course_item ) : ?>
							<?php $has_access = lms_site_core_user_has_course_access_for_user( (int) $selected->ID, (int) $course_item->ID ); ?>
							<tr>
								<td><input type="checkbox" name="course_ids[]" value="<?php echo esc_attr( $course_item->ID ); ?>" <?php checked( $has_access ); ?> <?php disabled( $has_access ); ?>></td>
								<td><?php echo esc_html( $course_item->post_title ); ?></td>
								<td><?php echo $has_access ? '<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> Access granted' : 'Not granted'; ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<?php submit_button( 'Grant selected access' ); ?>
				</form>
			</div>
		<?php endif; ?>

		<script>
		(function () {
			var password = document.getElementById( 'lms-student-password' );
			var toggle = document.getElementById( 'lms-student-password-toggle' );
			if ( ! password || ! toggle ) {
				return;
			}
			toggle.addEventListener( 'click', function () {
				var visible = 'password' === password.type;
				password.type = visible ? 'text' : 'password';
				toggle.textContent = visible ? 'Hide' : 'Show';
				toggle.setAttribute( 'aria-pressed', visible ? 'true' : 'false' );
			} );
		}());
		</script>
	</div>
	<?php
}

/**
 * Hide optional LMS pages from the admin Pages list while keeping them accessible.
 */
function lms_site_core_hide_optional_pages_from_admin( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() || 'page' !== $query->get( 'post_type' ) ) {
		return;
	}

	$hidden_page_ids = lms_site_core_exclude_optional_pages( array() );
	$existing_ids   = $query->get( 'post__not_in' );
	$existing_ids   = is_array( $existing_ids ) ? $existing_ids : array();
	$query->set( 'post__not_in', array_values( array_unique( array_merge( $existing_ids, $hidden_page_ids ) ) ) );
}
add_action( 'pre_get_posts', 'lms_site_core_hide_optional_pages_from_admin' );


/**
 * Use Vietnamese translations on public pages while keeping wp-admin in English.
 */
function lms_site_core_frontend_locale( string $locale ): string {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $locale;
	}

	return 'vi';
}
add_filter( 'locale', 'lms_site_core_frontend_locale', 20 );

/**
 * Fill the small set of LearnPress labels used by the current frontend flow.
 */
function lms_site_core_translate_frontend( string $translated, string $text, string $domain ): string {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $translated;
	}

	if ( ! in_array( $domain, array( 'learnpress', 'default', 'kadence' ), true ) ) {
		return $translated;
	}

	$translations = array(
		'Buy Now'            => 'Xem chi tiết',
		'Free'              => 'Miễn phí',
		'Free Course'       => 'Khóa học miễn phí',
		'View More'          => 'Xem chi tiết',
		'View Detail'        => 'Xem chi tiết',
		'Start Learning'     => 'Tiếp tục học',
		'Continue Learning'  => 'Tiếp tục học',
		'Continue'           => 'Tiếp tục học',
		'Enroll Now'         => 'Liên hệ để học',
		'Search courses...'  => 'Tìm khóa học...',
		'Newly published'    => 'Mới đăng',
		'Title a-z'          => 'Tên A-Z',
		'Title z-a'          => 'Tên Z-A',
		'Price high to low'  => 'Giá cao đến thấp',
		'Price low to high'  => 'Giá thấp đến cao',
		'Popular'            => 'Phổ biến',
		'All levels'         => 'Mọi cấp độ',
		'by'                 => 'bởi',
		'Students'           => 'Học viên',
		'Quizzes'            => 'Bài kiểm tra',
		'Lesson'             => 'Bài học',
		'Lessons'            => 'Bài học',
		'Contact'            => 'Liên hệ',
		'Home'               => 'Trang chủ',
		'Courses'            => 'Khóa học',
	);

	return $translations[ $text ] ?? $translated;
}
add_filter( 'gettext', 'lms_site_core_translate_frontend', 20, 3 );

/**
 * Keep the Zalo contact URL in one project-owned place.
 */
function lms_site_core_zalo_url(): string {
	return 'https://zalo.me/0984715632';
}

/**
 * Resolve the project Contact page URL.
 */
function lms_site_core_contact_page_url(): string {
	$contact_page = get_page_by_path( 'contact' );
	return $contact_page instanceof WP_Post ? get_permalink( $contact_page ) : home_url( '/contact/' );
}

/**
 * Add a stable body class so the child theme can own the Contact page layout.
 */
function lms_site_core_contact_body_class( array $classes ): array {
	if ( is_page( 'contact' ) ) {
		$classes[] = 'lms-site-core-contact-page';
	}

	return $classes;
}
add_filter( 'body_class', 'lms_site_core_contact_body_class' );

/**
 * Render the public feedback/contact form.
 */
function lms_site_core_render_contact_form(): string {
	$categories = array(
		'course_feedback' => 'Góp ý về khóa học',
		'website_feedback' => 'Góp ý về website',
		'account_support' => 'Hỗ trợ tài khoản',
		'other'           => 'Nội dung khác',
	);
	$courses = get_posts(
		array(
			'post_type'      => 'lp_course',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	$user = wp_get_current_user();
	$status = isset( $_GET['lms_contact'] ) ? sanitize_key( wp_unslash( $_GET['lms_contact'] ) ) : '';
	$selected_course = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
	$notice = '';
	if ( 'success' === $status ) {
		$notice = '<p class="lms-site-core-contact-notice lms-site-core-contact-notice-success" role="status">Cảm ơn bạn. Góp ý đã được gửi thành công.</p>';
	} elseif ( 'error' === $status ) {
		$notice = '<p class="lms-site-core-contact-notice lms-site-core-contact-notice-error" role="alert">Chưa thể gửi góp ý. Vui lòng thử lại hoặc nhắn Zalo trực tiếp.</p>';
	}
	ob_start();
	?>
	<section class="lms-site-core-contact" aria-labelledby="lms-contact-title">
		<div class="lms-site-core-contact-content">
		<div class="lms-site-core-contact-header">
			<p class="lms-site-core-contact-eyebrow">Contact</p>
			<h1 id="lms-contact-title">Liên hệ và góp ý</h1>
			<p>Gửi góp ý về khóa học, website hoặc tài khoản. Mình sẽ xem và phản hồi sớm nhất có thể.</p>
		</div>
		<?php echo $notice; ?>
		<div class="lms-site-core-contact-layout">
			<form class="lms-site-core-contact-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lms_site_core_submit_contact">
				<?php wp_nonce_field( 'lms_site_core_submit_contact', 'lms_site_core_submit_contact_nonce' ); ?>
				<p class="lms-site-core-contact-honeypot" aria-hidden="true">
					<label for="lms-contact-website">Website</label>
					<input id="lms-contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
				</p>
				<div class="lms-site-core-contact-fields">
					<p>
						<label for="lms-contact-name">Họ và tên</label>
						<input id="lms-contact-name" name="contact_name" type="text" value="<?php echo esc_attr( $user->exists() ? $user->display_name : '' ); ?>" required>
					</p>
					<p>
						<label for="lms-contact-email">Email</label>
						<input id="lms-contact-email" name="contact_email" type="email" value="<?php echo esc_attr( $user->exists() ? $user->user_email : '' ); ?>" required>
					</p>
				</div>
				<p>
					<label for="lms-contact-category">Nội dung liên hệ</label>
					<select id="lms-contact-category" name="contact_category" required>
						<?php foreach ( $categories as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label for="lms-contact-course">Khóa học liên quan <span>(không bắt buộc)</span></label>
					<select id="lms-contact-course" name="course_id">
						<option value="0">Chọn khóa học</option>
						<?php foreach ( $courses as $course ) : ?>
							<option value="<?php echo esc_attr( $course->ID ); ?>" <?php selected( $selected_course, $course->ID ); ?>><?php echo esc_html( $course->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label for="lms-contact-message">Nội dung góp ý</label>
					<textarea id="lms-contact-message" name="contact_message" rows="7" required placeholder="Bạn muốn chia sẻ điều gì?"></textarea>
				</p>
				<button class="lms-site-core-contact-submit" type="submit">Gửi góp ý</button>
			</form>
			<aside class="lms-site-core-contact-aside">
				<h2>Liên hệ nhanh</h2>
				<p>Nếu cần hỗ trợ đăng nhập, nhận quyền học hoặc trao đổi nhanh, bạn có thể nhắn trực tiếp qua Zalo.</p>
				<a class="lms-site-core-contact-zalo-button" href="<?php echo esc_url( lms_site_core_zalo_url() ); ?>" target="_blank" rel="noopener">Nhắn Zalo 0984 715 632</a>
			</aside>
		</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'lms_site_contact_form', 'lms_site_core_render_contact_form' );

/**
 * Render the project Contact form when the existing Contact page has no saved content.
 *
 * Page content is stored in the WordPress database, so it is not transported by Git deploy.
 */
function lms_site_core_contact_page_content_fallback( string $content ): string {
	if ( is_admin() || ! is_page( 'contact' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	if ( '' !== trim( wp_strip_all_tags( $content ) ) || has_shortcode( $content, 'lms_site_contact_form' ) ) {
		return $content;
	}

	return do_shortcode( '[lms_site_contact_form]' );
}
add_filter( 'the_content', 'lms_site_core_contact_page_content_fallback', 20 );


/**
 * Send public Contact form submissions to the site administrator.
 */
function lms_site_core_process_contact_form(): void {
	$redirect_url = lms_site_core_contact_page_url();
	if ( ! isset( $_POST['lms_site_core_submit_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lms_site_core_submit_contact_nonce'] ) ), 'lms_site_core_submit_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'lms_contact', 'error', $redirect_url ) );
		exit;
	}
	if ( ! empty( $_POST['website'] ) ) {
		wp_safe_redirect( add_query_arg( 'lms_contact', 'success', $redirect_url ) );
		exit;
	}

	$name     = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
	$email    = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
	$category = isset( $_POST['contact_category'] ) ? sanitize_key( wp_unslash( $_POST['contact_category'] ) ) : 'other';
	$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
	$message  = isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '';
	$allowed_categories = array(
		'course_feedback' => 'Góp ý về khóa học',
		'website_feedback' => 'Góp ý về website',
		'account_support' => 'Hỗ trợ tài khoản',
		'other'           => 'Nội dung khác',
	);
	if ( '' === $name || ! is_email( $email ) || '' === $message || ! isset( $allowed_categories[ $category ] ) ) {
		wp_safe_redirect( add_query_arg( 'lms_contact', 'error', $redirect_url ) );
		exit;
	}

	$course_title = 'Không chọn khóa học';
	if ( $course_id > 0 ) {
		$course = get_post( $course_id );
		if ( ! $course || 'lp_course' !== $course->post_type || 'publish' !== $course->post_status ) {
			$course_id = 0;
		} else {
			$course_title = $course->post_title;
		}
	}
	$user = wp_get_current_user();
	$subject = sprintf( '[%s] Góp ý mới từ %s', get_bloginfo( 'name' ), $name );
	$body = implode( "\n", array(
		'Người gửi: ' . $name,
		'Email: ' . $email,
		'Loại liên hệ: ' . $allowed_categories[ $category ],
		'Khóa học: ' . $course_title,
		'User ID: ' . ( $user->exists() ? $user->ID : 'Khách chưa đăng nhập' ),
		'',
		'Nội dung:',
		$message,
	) );
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( is_email( $email ) ) {
		$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
	}
	$sent = wp_mail( get_option( 'admin_email' ), $subject, $body, $headers );
	wp_safe_redirect( add_query_arg( 'lms_contact', $sent ? 'success' : 'error', $redirect_url ) );
	exit;
}
add_action( 'admin_post_lms_site_core_submit_contact', 'lms_site_core_process_contact_form' );
add_action( 'admin_post_nopriv_lms_site_core_submit_contact', 'lms_site_core_process_contact_form' );


/**
 * Resolve a LearnPress course ID from its model.
 */
function lms_site_core_course_id( $course ): int {
	if ( is_object( $course ) && method_exists( $course, 'get_id' ) ) {
		return absint( $course->get_id() );
	}

	return 0;
}

/**
 * Resolve the project-owned fallback image for a course.
 */
function lms_site_core_course_image_asset_url( int $course_id ): string {
	$slug = sanitize_title( (string) get_post_field( 'post_name', $course_id ) );
	$assets = array(
		'giao-tiep-tieng-anh-co-ban'          => 'course-english-basic.png',
		'giao-tiep-tieng-anh-nang-cao'        => 'course-english-advanced.png',
		'giao-tiep-tieng-anh-mien-phi-cho-sinh-vien' => 'course-english-scholarship.png',
	);

	if ( ! isset( $assets[ $slug ] ) ) {
		return '';
	}

	return plugin_dir_url( __FILE__ ) . 'assets/images/courses/' . $assets[ $slug ];
}

/**
 * Keep course cards visually complete without relying on uploads being deployed.
 */
function lms_site_core_course_thumbnail_fallback( string $html, int $post_id, int $post_thumbnail_id, $size, array $attr ): string {
	if ( '' !== $html || 'lp_course' !== get_post_type( $post_id ) ) {
		return $html;
	}

	$image_url = lms_site_core_course_image_asset_url( $post_id );
	if ( '' === $image_url ) {
		return $html;
	}

	$size_name = is_string( $size ) ? sanitize_html_class( $size ) : 'course';
	return sprintf(
		'<img src="%s" alt="%s" class="attachment-%s size-%s wp-post-image" loading="lazy">',
		esc_url( $image_url ),
		esc_attr( get_the_title( $post_id ) ),
		$size_name,
		$size_name
	);
}
add_filter( 'post_thumbnail_html', 'lms_site_core_course_thumbnail_fallback', 20, 5 );

/**
 * Replace LearnPress's no-image placeholder for project-owned courses.
 */
function lms_site_core_learnpress_course_image_fallback( string $html, int $course_id, string $size, array $attr ): string {
	if ( 'lp_course' !== get_post_type( $course_id ) || has_post_thumbnail( $course_id ) ) {
		return $html;
	}

	$image_url = lms_site_core_course_image_asset_url( $course_id );
	if ( '' === $image_url ) {
		return $html;
	}

	$alt = esc_attr( get_the_title( $course_id ) );
	$class = isset( $attr['class'] ) ? sanitize_html_class( (string) $attr['class'] ) : 'course-image';
	return sprintf(
		'<img src="%s" alt="%s" class="%s" loading="lazy">',
		esc_url( $image_url ),
		$alt,
		$class
	);
}
add_filter( 'learn-press/course/image', 'lms_site_core_learnpress_course_image_fallback', 20, 4 );

/**
 * Keep LearnPress's archive API aligned with the project-owned course images.
 */
function lms_site_core_filter_course_api_image( $course_data, $course ): object {
	if ( is_object( $course_data ) && is_object( $course ) && ! has_post_thumbnail( lms_site_core_course_id( $course ) ) ) {
		$image_url = lms_site_core_course_image_asset_url( lms_site_core_course_id( $course ) );
		if ( '' !== $image_url ) {
			$course_data->image = $image_url;
		}
	}

	return $course_data;
}
add_filter( 'learnPress/prepare_struct_courses_response/courseObjPrepare', 'lms_site_core_filter_course_api_image', 20, 2 );

/**
 * Patch LearnPress REST course responses used by the archive JavaScript.
 */
function lms_site_core_filter_course_rest_response( $response, $server, $request ) {
	$route = (string) $request->get_route();
	if ( 0 !== strpos( $route, '/learnpress/v1/courses' ) || is_wp_error( $response ) || ! method_exists( $response, 'get_data' ) ) {
		return $response;
	}

	$data = $response->get_data();
	$items = isset( $data[0] ) && is_array( $data[0] ) ? $data : array( $data );
	$changed = false;

	foreach ( $items as $index => $item ) {
		$course_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
		$image_url = lms_site_core_course_image_asset_url( $course_id );
		if ( $course_id > 0 && '' !== $image_url && isset( $item['image'] ) && false !== strpos( (string) $item['image'], 'no-image.png' ) ) {
			$items[ $index ]['image'] = $image_url;
			$changed = true;
		}
	}

	if ( $changed ) {
		$response->set_data( isset( $data[0] ) && is_array( $data[0] ) ? $items : $items[0] );
	}

	return $response;
}
add_filter( 'rest_post_dispatch', 'lms_site_core_filter_course_rest_response', 20, 3 );





/**
 * Identify courses that require admin contact before access is granted.
 */
function lms_site_core_is_contact_course( int $course_id ): bool {
	return '1' === (string) get_post_meta( $course_id, '_lms_contact_course', true );
}

/**
 * Add a small project-owned access flag to the LearnPress course editor.
 */
function lms_site_core_add_course_access_meta_box(): void {
	add_meta_box(
		'lms-site-core-course-access',
		'Course access',
		'lms_site_core_render_course_access_meta_box',
		'lp_course',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes_lp_course', 'lms_site_core_add_course_access_meta_box' );

function lms_site_core_render_course_access_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'lms_site_core_course_access', 'lms_site_core_course_access_nonce' );
	$contact_required = lms_site_core_is_contact_course( $post->ID );
	?>
	<label>
		<input type="checkbox" name="_lms_contact_course" value="1" <?php checked( $contact_required ); ?>>
		Contact admin before enrollment
	</label>
	<p class="description">Use this for community or scholarship courses that require admin approval before access.</p>
	<?php
}

function lms_site_core_save_course_access_meta( int $post_id, WP_Post $post, bool $is_update ): void {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! isset( $_POST['lms_site_core_course_access_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lms_site_core_course_access_nonce'] ) ), 'lms_site_core_course_access' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! empty( $_POST['_lms_contact_course'] ) ) {
		update_post_meta( $post_id, '_lms_contact_course', '1' );
	} else {
		delete_post_meta( $post_id, '_lms_contact_course' );
	}
}
add_action( 'save_post_lp_course', 'lms_site_core_save_course_access_meta', 5, 3 );

/**
 * Check LearnPress access without duplicating enrollment data.
 */
function lms_site_core_user_has_course_access( int $course_id ): bool {
	if ( $course_id <= 0 || ! is_user_logged_in() ) {
		return false;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	return lms_site_core_user_has_course_access_for_user( get_current_user_id(), $course_id );
}


/**
 * Enforce manual enrollment in models, including REST and legacy flows.
 * The dedicated administrator enrollment tool remains available.
 */
function lms_site_core_requires_manual_enrollment( int $course_id, int $user_id ): bool {
	return $course_id > 0
		&& ! current_user_can( 'manage_options' )
		&& ! lms_site_core_user_has_course_access_for_user( $user_id, $course_id );
}

function lms_site_core_filter_can_enroll( $result, $course, $user ) {
	$course_id = lms_site_core_course_id( $course );
	$user_id   = is_object( $user ) && method_exists( $user, 'get_id' ) ? (int) $user->get_id() : 0;

	if ( lms_site_core_requires_manual_enrollment( $course_id, $user_id ) ) {
		return new WP_Error( 'lms_manual_enrollment_required', 'Vui lòng liên hệ quản trị viên để được cấp quyền học.' );
	}

	return $result;
}
add_filter( 'learn-press/user/can-enroll/course', 'lms_site_core_filter_can_enroll', 100, 3 );

function lms_site_core_filter_legacy_can_enroll( $result, $course, $return_bool, $user ) {
	$course_id = lms_site_core_course_id( $course );
	$user_id   = is_object( $user ) && method_exists( $user, 'get_id' ) ? (int) $user->get_id() : 0;

	if ( ! lms_site_core_requires_manual_enrollment( $course_id, $user_id ) ) {
		return $result;
	}

	if ( $return_bool ) {
		return false;
	}

	return (object) array(
		'check'   => false,
		'code'    => 'lms_manual_enrollment_required',
		'message' => 'Vui lòng liên hệ quản trị viên để được cấp quyền học.',
	);
}
add_filter( 'learn-press/user/can-enroll-course', 'lms_site_core_filter_legacy_can_enroll', 100, 4 );

/**
 * Resume using the next curriculum item selected by LearnPress progress.
 */
function lms_site_core_course_continue_url( int $course_id ): string {
	$fallback = (string) get_permalink( $course_id );
	if ( ! lms_site_core_user_has_course_access( $course_id ) ) {
		return $fallback;
	}

	$enrollment = lms_site_core_get_user_course_enrollment( get_current_user_id(), $course_id );
	if ( ! $enrollment || ! method_exists( $enrollment, 'get_item_continue' ) ) {
		return $fallback;
	}

	$course = $enrollment->get_course_model();
	$item   = $enrollment->get_item_continue();
	return $course && $item ? $course->get_item_link( (int) $item->ID ) : $fallback;
}

/**
 * Render archive actions according to login and enrollment state.
 */
function lms_site_core_course_archive_cta( array $sections, $course, $settings ): array {
	$course_id = lms_site_core_course_id( $course );

	if ( $course_id <= 0 || ! is_object( $course ) || ! method_exists( $course, 'get_permalink' ) ) {
		return $sections;
	}

	$has_access      = lms_site_core_user_has_course_access( $course_id );
	$contact_required = lms_site_core_is_contact_course( $course_id );
	$label            = 'Xem chi tiết';
	$attributes       = '';

	if ( is_user_logged_in() && $has_access ) {
		$label = 'Tiếp tục học';
	} elseif ( $contact_required ) {
		$label      = 'Liên hệ để nhận khóa học';
		$attributes = sprintf(
			' data-lms-course-request="1" data-course-id="%d" data-lms-contact-only="1"',
			$course_id
		);
	} elseif ( is_user_logged_in() ) {
		$label      = 'Liên hệ để học';
		$attributes = sprintf(
			' data-lms-course-request="1" data-course-id="%d"',
			$course_id
		);
	}

	$sections['btn_read_more'] = sprintf(
		'<div class="course-readmore"><a href="%s" class="lms-site-core-course-cta"%s>%s</a></div>',
		esc_url( $has_access ? lms_site_core_course_continue_url( $course_id ) : $course->get_permalink() ),
		$attributes,
		esc_html( $label )
	);

	return $sections;
}
add_filter( 'learn-press/layout/list-courses/item/section/bottom/end', 'lms_site_core_course_archive_cta', 30, 3 );

/**
 * Disable LearnPress self-service enrollment/purchase on course detail pages.
 */
function lms_site_core_hide_self_service_button( bool $can_show, $user, $course ): bool {
	if ( ! is_singular( 'lp_course' ) ) {
		return $can_show;
	}

	$course_id = lms_site_core_course_id( $course );

	if ( lms_site_core_user_has_course_access( $course_id ) ) {
		return $can_show;
	}

	return false;
}
add_filter( 'learnpress/course/template/button-purchase/can-show', 'lms_site_core_hide_self_service_button', 20, 3 );
add_filter( 'learnpress/course/template/button-enroll/can-show', 'lms_site_core_hide_self_service_button', 20, 3 );

/**
 * Provide frontend state and load the small project-owned interaction script.
 */
function lms_site_core_enqueue_frontend_assets(): void {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	$current_course_id = is_singular( 'lp_course' ) ? get_queried_object_id() : 0;

	wp_enqueue_script(
		'lms-site-core-frontend',
		plugin_dir_url( __FILE__ ) . 'assets/js/lms-site-core.js',
		array(),
		'0.10.0',
		true
	);

	wp_localize_script(
		'lms-site-core-frontend',
		'lmsSiteCore',
		array(
			'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
			'nonce'               => wp_create_nonce( 'lms_site_core_login' ),
			'isLoggedIn'          => is_user_logged_in(),
			'currentCourseId'     => $current_course_id,
			'currentCourseAccess' => $current_course_id > 0
				? lms_site_core_user_has_course_access( $current_course_id )
				: false,
			'zaloUrl'             => lms_site_core_zalo_url(),
			'loginError'          => 'Thông tin đăng nhập chưa đúng.',
			'networkError'        => 'Không thể kết nối. Vui lòng thử lại.',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'lms_site_core_enqueue_frontend_assets', 20 );

/**
 * Resolve the action fragment used by a manually managed navigation item.
 */
function lms_site_core_menu_item_fragment( $item ): string {
	$url = is_object( $item ) && isset( $item->url ) ? (string) $item->url : '';

	if ( 0 === strpos( $url, '#' ) ) {
		return sanitize_key( substr( $url, 1 ) );
	}

	$fragment = wp_parse_url( $url, PHP_URL_FRAGMENT );

	return is_string( $fragment ) ? sanitize_key( $fragment ) : '';
}

/**
 * Check whether a menu filter is rendering Kadence's Secondary Navigation.
 */
function lms_site_core_is_secondary_navigation( $args ): bool {
	return is_object( $args ) && isset( $args->theme_location ) && 'secondary' === (string) $args->theme_location;
}

/**
 * Mark only manually added Secondary Navigation action items.
 */
function lms_site_core_mark_secondary_menu_items( array $items, $args ): array {
	if ( ! lms_site_core_is_secondary_navigation( $args ) ) {
		return $items;
	}

	foreach ( $items as $item ) {
		$fragment = lms_site_core_menu_item_fragment( $item );

		if ( 'lms-support-modal' === $fragment ) {
			$item->classes = array_values( array_unique( array_merge( (array) $item->classes, array( 'lms-donation-menu-item' ) ) ) );
		} elseif ( 'lms-login-modal' === $fragment ) {
			$class = is_user_logged_in() ? 'lms-user-menu-item' : 'lms-login-menu-item';
			$item->classes = array_values( array_unique( array_merge( (array) $item->classes, array( $class ) ) ) );
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'lms_site_core_mark_secondary_menu_items', 20, 2 );

/**
 * Add modal triggers or the authenticated profile URL to Secondary Navigation links.
 */
function lms_site_core_secondary_link_attributes( array $atts, $item, $args, int $depth ): array {
	if ( ! lms_site_core_is_secondary_navigation( $args ) ) {
		return $atts;
	}

	$fragment = lms_site_core_menu_item_fragment( $item );

	if ( 'lms-support-modal' === $fragment ) {
		$atts['data-lms-support-trigger'] = '1';
	} elseif ( 'lms-login-modal' === $fragment ) {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			$profile_url = function_exists( 'learn_press_user_profile_url' )
				? (string) learn_press_user_profile_url()
				: '';
			if ( ! $profile_url ) {
				$profile_page = get_page_by_path( 'lp-profile' );
				$profile_url  = $profile_page ? (string) get_permalink( $profile_page ) : '';
			}
			$atts['href'] = $profile_url ?: '#';
			unset( $atts['data-lms-login-trigger'] );
		} else {
			$atts['data-lms-login-trigger'] = '1';
		}
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'lms_site_core_secondary_link_attributes', 20, 4 );

/**
 * Replace the logged-in Login label with the current user's avatar and display name.
 */
function lms_site_core_secondary_menu_item_title( string $title, $item, $args, int $depth ): string {
	if ( ! lms_site_core_is_secondary_navigation( $args ) || ! is_user_logged_in() || 'lms-login-modal' !== lms_site_core_menu_item_fragment( $item ) ) {
		return $title;
	}

	$user        = wp_get_current_user();
	$display_name = $user->display_name ?: $user->user_login;
	$avatar      = get_avatar( $user->ID, 32, '', $display_name, array( 'class' => array( 'lms-user-avatar' ) ) );

	return $avatar . '<span class="lms-user-menu-name">' . esc_html( $display_name ) . '</span>';
}
add_filter( 'nav_menu_item_title', 'lms_site_core_secondary_menu_item_title', 20, 4 );

/**
 * Render the login and access dialogs once per frontend page.
 */
function lms_site_core_render_frontend_dialogs(): void {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	?>
	<div id="lms-login-modal" class="lms-site-core-modal" role="dialog" aria-modal="true" aria-labelledby="lms-login-title" hidden>
		<div class="lms-site-core-modal-panel">
			<button type="button" class="lms-site-core-modal-close" data-lms-modal-close aria-label="Đóng">×</button>
			<h2 id="lms-login-title">Đăng nhập để tiếp tục</h2>
			<p class="lms-site-core-modal-intro">Đăng nhập bằng tài khoản do quản trị viên cấp.</p>
			<form id="lms-site-core-login-form">
				<label for="lms-login-username">Tên đăng nhập hoặc email</label>
				<input id="lms-login-username" name="login" type="text" autocomplete="username" required>
				<label for="lms-login-password">Mật khẩu</label>
				<input id="lms-login-password" name="password" type="password" autocomplete="current-password" required>
				<label class="lms-site-core-remember"><input name="remember" type="checkbox" value="1"> Ghi nhớ đăng nhập</label>
				<p class="lms-site-core-modal-message" data-lms-login-message role="alert" hidden></p>
				<button type="submit" class="lms-site-core-modal-submit">Đăng nhập</button>
			</form>
			<p class="lms-site-core-modal-note">Google Login sẽ được bổ sung sau.</p>
		</div>
	</div>
	<div id="lms-access-modal" class="lms-site-core-modal" role="dialog" aria-modal="true" aria-labelledby="lms-access-title" hidden>
		<div class="lms-site-core-modal-panel">
			<button type="button" class="lms-site-core-modal-close" data-lms-modal-close aria-label="Đóng">×</button>
			<h2 id="lms-access-title">Chưa được cấp quyền học</h2>
			<p class="lms-site-core-modal-intro" data-lms-access-message>Tài khoản của bạn chưa được cấp quyền cho khóa học này.</p>
			<a class="lms-site-core-zalo-button" href="<?php echo esc_url( lms_site_core_zalo_url() ); ?>" target="_blank" rel="noopener">Liên hệ qua Zalo</a>
		</div>
	</div>
	<div id="lms-support-modal" class="lms-site-core-modal" role="dialog" aria-modal="true" aria-labelledby="lms-support-title" hidden>
		<div class="lms-site-core-modal-panel lms-site-core-support-panel">
			<button type="button" class="lms-site-core-modal-close" data-lms-modal-close aria-label="Đóng">×</button>
			<h2 id="lms-support-title">Ủng hộ dự án</h2>
			<p class="lms-site-core-modal-intro">Nếu nội dung hữu ích, bạn có thể ủng hộ dự án bằng chuyển khoản.</p>
			<img class="lms-site-core-bank-placeholder" src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'assets/images/bank-placeholder.svg' ); ?>" alt="Ảnh minh họa thông tin ngân hàng">
			<div class="lms-site-core-bank-details">
				<strong>Thông tin ngân hàng sẽ cập nhật</strong>
				<span>Ngân hàng demo · STK 0000 0000 0000</span>
				<span>Chủ tài khoản: Bel Nguyễn</span>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'lms_site_core_render_frontend_dialogs', 20 );

/**
 * Handle the project login form with WordPress authentication.
 */
function lms_site_core_ajax_login(): void {
	check_ajax_referer( 'lms_site_core_login', 'nonce' );

	$login    = sanitize_text_field( wp_unslash( $_POST['login'] ?? '' ) );
	$password = (string) wp_unslash( $_POST['password'] ?? '' );
	$remember = ! empty( $_POST['remember'] );

	if ( '' === $login || '' === $password ) {
		wp_send_json_error( array( 'message' => 'Vui lòng nhập đủ thông tin đăng nhập.' ), 400 );
	}

	if ( is_email( $login ) ) {
		$user_by_email = get_user_by( 'email', $login );
		$login         = $user_by_email ? $user_by_email->user_login : $login;
	}

	$user = wp_signon(
		array(
			'user_login'    => $login,
			'user_password' => $password,
			'remember'      => $remember,
		),
		is_ssl()
	);

	if ( is_wp_error( $user ) ) {
		wp_send_json_error( array( 'message' => 'Thông tin đăng nhập chưa đúng.' ), 401 );
	}

	wp_set_current_user( $user->ID );
	wp_send_json_success( array( 'userId' => $user->ID ) );
}
add_action( 'wp_ajax_nopriv_lms_site_core_login', 'lms_site_core_ajax_login' );
add_action( 'wp_ajax_lms_site_core_login', 'lms_site_core_ajax_login' );


/**
 * Replace native purchase/enroll actions with the project access flow.
 */
function lms_site_core_course_detail_buttons( array $buttons, $course, $user ): array {
	$course_id = lms_site_core_course_id( $course );

	if ( lms_site_core_user_has_course_access( $course_id ) ) {
		$url = lms_site_core_course_continue_url( $course_id );
		if ( $url !== (string) get_permalink( $course_id ) ) {
			$enrollment = lms_site_core_get_user_course_enrollment( get_current_user_id(), $course_id );
			$label = $enrollment && in_array( $enrollment->get_status(), array( 'finished', 'completed' ), true ) ? 'Ôn lại bài học' : 'Tiếp tục học';
			// Replace only the native continue action; retain Finish/Retake controls.
			foreach ( array( 'btn_continue_and_finish', 'btn_learning' ) as $key ) {
				if ( isset( $buttons[ $key ] ) ) {
					$buttons[ $key ] = preg_replace( '~<a\b[^>]*>\s*<button\b[^>]*class=["\'][^"\']*course-btn-continue[^"\']*["\'][^>]*>.*?</button>\s*</a>~s', '', $buttons[ $key ] );
				}
			}
			$link = sprintf( '<a class="lp-button button lms-course-resume" href="%s">%s</a>', esc_url( $url ), esc_html( $label ) );
			$buttons['btn_buy'] = $link;
			$buttons['btn_enroll'] = '';
			$buttons['btn_contact'] = '';
		}
		return $buttons;
	}

	$contact_required = lms_site_core_is_contact_course( $course_id );
	$label            = $contact_required
		? 'Liên hệ để nhận khóa học'
		: ( is_user_logged_in() ? 'Liên hệ để học' : 'Đăng nhập để học' );
	$contact_attribute = $contact_required ? ' data-lms-contact-only="1"' : '';
	$buttons['btn_buy'] = sprintf(
		'<button type="button" class="lp-button button lms-site-core-course-access-trigger" data-lms-course-request="1" data-course-id="%d"%s>%s</button>',
		esc_attr( $course_id ),
		$contact_attribute,
		esc_html( $label )
	);

	if ( array_key_exists( 'btn_enroll', $buttons ) ) {
		$buttons['btn_enroll'] = '';
	}

	if ( array_key_exists( 'btn_contact', $buttons ) ) {
		$buttons['btn_contact'] = '';
	}

	return $buttons;
}
add_filter( 'learn-press/single-course/modern/section-right/buttons', 'lms_site_core_course_detail_buttons', 20, 3 );
add_filter( 'learn-press/single-course/model/section-right/info-meta/buttons', 'lms_site_core_course_detail_buttons', 20, 3 );
add_filter( 'learn-press/single-course/offline/section-right/info-meta/buttons', 'lms_site_core_course_detail_buttons', 20, 3 );
