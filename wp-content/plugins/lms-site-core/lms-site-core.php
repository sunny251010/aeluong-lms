<?php
/**
 * Plugin Name: LMS Site Core
 * Description: Project-owned LMS behavior that complements LearnPress without replacing it.
 * Version: 0.3.0
 * Author: LMS Project
 * Text Domain: lms-site-core
 */

defined( 'ABSPATH' ) || exit;

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
 * Resolve a LearnPress course ID from its model.
 */
function lms_site_core_course_id( $course ): int {
	if ( is_object( $course ) && method_exists( $course, 'get_id' ) ) {
		return absint( $course->get_id() );
	}

	return 0;
}

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

	if ( ! function_exists( 'learn_press_get_current_user' ) ) {
		return false;
	}

	$user = learn_press_get_current_user();

	return $user && method_exists( $user, 'has_enrolled_or_finished' )
		? (bool) $user->has_enrolled_or_finished( $course_id )
		: false;
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
		esc_url( $course->get_permalink() ),
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
		'0.3.0',
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
 * Add a neutral project-support action beside the account action.
 */
function lms_site_core_add_support_menu_item( string $items, $args ): string {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $items;
	}

	$theme_location = is_object( $args ) && isset( $args->theme_location )
		? (string) $args->theme_location
		: '';

	if ( ! in_array( $theme_location, array( 'primary', 'primary_navigation' ), true ) ) {
		return $items;
	}

	$items .= '<li class="menu-item lms-site-core-support-item"><a href="#lms-support-modal" class="lms-site-core-support-link" data-lms-support-trigger="1">Support</a></li>';

	return $items;
}
add_filter( 'wp_nav_menu_items', 'lms_site_core_add_support_menu_item', 29, 2 );

/**
 * Add the account action to the primary navigation.
 */
function lms_site_core_add_account_menu_item( string $items, $args ): string {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $items;
	}

	$theme_location = is_object( $args ) && isset( $args->theme_location )
		? (string) $args->theme_location
		: '';

	if ( ! in_array( $theme_location, array( 'primary', 'primary_navigation' ), true ) ) {
		return $items;
	}

	if ( is_user_logged_in() ) {
		$user       = wp_get_current_user();
		$profile_url = function_exists( 'learn_press_user_profile_url' )
			? learn_press_user_profile_url()
			: get_edit_profile_url( $user->ID );
		$avatar     = get_avatar( $user->ID, 32, '', $user->display_name, array( 'class' => array( 'lms-site-core-avatar' ) ) );

		$items .= sprintf(
			'<li class="menu-item lms-site-core-account-item"><button type="button" class="lms-site-core-account-link" aria-expanded="false" aria-haspopup="true">%s<span class="lms-site-core-account-name">%s</span></button><span class="lms-site-core-account-dropdown"><a href="%s">Tài khoản</a><a href="%s">Đăng xuất</a></span></li>',
			$avatar,
			esc_html( $user->display_name ?: $user->user_login ),
			esc_url( $profile_url ),
			esc_url( wp_logout_url( home_url( '/' ) ) )
		);
	} else {
		$items .= '<li class="menu-item lms-site-core-account-item"><a href="#lms-login-modal" class="lms-site-core-account-link" data-lms-login-trigger="1">Đăng nhập</a></li>';
	}

	return $items;
}
add_filter( 'wp_nav_menu_items', 'lms_site_core_add_account_menu_item', 30, 2 );

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
