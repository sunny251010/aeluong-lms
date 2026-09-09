<?php

/**
 * Enqueue the Kadence parent stylesheet before the child stylesheet.
 */
add_action( 'wp_enqueue_scripts', function (): void {
	$parent_theme = wp_get_theme( get_template() );
	$child_theme  = wp_get_theme();

	wp_enqueue_style(
		'kadence-parent',
		get_template_directory_uri() . '/style.css',
		[],
		$parent_theme->get( 'Version' )
	);

	wp_enqueue_style(
		'lms-kadence-child',
		get_stylesheet_directory_uri() . '/style.css',
		[ 'kadence-parent' ],
		$child_theme->get( 'Version' )
	);
} );

/**
 * Replace Kadence's default theme credit with the project footer credit.
 */
function lms_kadence_child_render_footer_credit(): void {
	echo '<p class="footer-copyright">&copy; 2026 Bel Nguy&#7877;n - website by <a href="https://www.facebook.com/ducbang02" target="_blank" rel="noopener noreferrer">Bang Nguyen</a></p>';
}

/**
 * Keep the parent footer intact while replacing only its credit output.
 */
function lms_kadence_child_override_footer_credit(): void {
	if ( function_exists( 'Kadence\footer_html' ) ) {
		remove_action( 'kadence_footer_html', 'Kadence\footer_html' );
		add_action( 'kadence_footer_html', 'lms_kadence_child_render_footer_credit' );
	}
}
add_action( 'after_setup_theme', 'lms_kadence_child_override_footer_credit', 1000 );
