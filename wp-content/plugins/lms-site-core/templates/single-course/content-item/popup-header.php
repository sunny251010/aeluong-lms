<?php
/**
 * Project-owned LearnPress lesson popup header.
 *
 * The title and close/back link return learners to the course archive.
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $course ) || ! isset( $user ) || ! isset( $percentage ) ||
	! isset( $completed_items ) || ! isset( $total_items ) ) {
	return;
}

$courses_url = function_exists( 'lms_site_core_course_archive_url' )
	? lms_site_core_course_archive_url()
	: home_url( '/courses/' );
?>

<div id="popup-header">
	<?php
	/**
	 * @since 4.0.6
	 * @see single-button-toggle-sidebar - 5
	 */
	do_action( 'learn-press/single-button-toggle-sidebar' );
	?>
	<div class="popup-header__inner">
		<h2 class="course-title">
			<a href="<?php echo esc_url( $courses_url ); ?>"><?php echo wp_kses_post( $course->get_title() ); ?></a>
		</h2>

		<?php if ( $user->has_enrolled_or_finished( $course->get_id() ) ) : ?>
		<div class="items-progress" data-total-items="<?php echo esc_attr( $total_items ); ?>">
			<span class="number">
				<?php
				printf(
					__(
						'<span class="items-completed">%1$s</span> of %2$d items',
						'learnpress'
					),
					esc_html( $completed_items ),
					esc_html( $course->count_items() )
				);
				?>
			</span>
			<div class="learn-press-progress">
				<div class="learn-press-progress__active" data-value="<?php echo esc_attr( $percentage ); ?>%;"></div>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<a href="<?php echo esc_url( $courses_url ); ?>"
		class="back-course"
		aria-label="<?php esc_attr_e( 'Back to course', 'learnpress' ); ?>"
	>
		<i class="lp-icon-times"></i>
	</a>
</div>