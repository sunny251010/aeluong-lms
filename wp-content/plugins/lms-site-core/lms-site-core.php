<?php
/**
 * Plugin Name: LMS Site Core
 * Description: Project-owned LMS behavior that complements LearnPress without replacing it.
 * Version: 0.1.0
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
function lms_site_core_filter_navigation_html( string $items, array $args ): string {
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
