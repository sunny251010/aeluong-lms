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
