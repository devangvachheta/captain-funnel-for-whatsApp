<?php
/**
 * LifterLMS integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAPFW_Integration_LifterLMS extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'lifterlms'; }
	public function get_label(): string    { return 'LifterLMS'; }
	public function get_category(): string { return 'LMS'; }
	public function get_plugin_file(): string { return 'lifterlms/lifterlms.php'; }

	public function get_triggers(): array {
		$vars = array(
			'{student_name}'    => __( 'Student display name', 'captain-funnel-for-whatsapp' ),
			'{student_email}'   => __( 'Student email', 'captain-funnel-for-whatsapp' ),
			'{student_phone}'   => __( 'Student phone (LifterLMS phone field)', 'captain-funnel-for-whatsapp' ),
			'{course_name}'     => __( 'Course title', 'captain-funnel-for-whatsapp' ),
			'{lesson_name}'     => __( 'Lesson title', 'captain-funnel-for-whatsapp' ),
			'{completion_date}' => __( 'Completion date', 'captain-funnel-for-whatsapp' ),
			'{certificate_url}' => __( 'Certificate URL if available', 'captain-funnel-for-whatsapp' ),
			'{site_name}'       => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);

		return array(
			'llms_course_enrolled'  => array( 'label' => __( 'Course Enrolled', 'captain-funnel-for-whatsapp' ),   'description' => __( 'Fires when student enrolls in a LifterLMS course.', 'captain-funnel-for-whatsapp' ),    'variables' => $vars ),
			'llms_course_completed' => array( 'label' => __( 'Course Completed', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when student completes a LifterLMS course.', 'captain-funnel-for-whatsapp' ),   'variables' => $vars ),
			'llms_lesson_completed' => array( 'label' => __( 'Lesson Completed', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when student completes a LifterLMS lesson.', 'captain-funnel-for-whatsapp' ),   'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'llms_user_enrolled_in_course',   array( $this, 'on_enrolled' ),         10, 2 );
		add_action( 'lifterlms_course_completed',      array( $this, 'on_course_completed' ), 10, 2 );
		add_action( 'lifterlms_lesson_completed',      array( $this, 'on_lesson_completed' ), 10, 2 );
	}

	public function on_enrolled( int $user_id, int $course_id ): void {
		$vars  = $this->build_base( $user_id, $course_id );
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'llms_course_enrolled', $phone, $vars, $user_id );
		}
	}

	public function on_course_completed( int $user_id, int $course_id ): void {
		$vars  = $this->build_base( $user_id, $course_id );
		$vars['{completion_date}'] = wp_date( get_option( 'date_format' ) );
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'llms_course_completed', $phone, $vars, $user_id );
		}
	}

	public function on_lesson_completed( int $user_id, int $lesson_id ): void {
		$course_id = llms_get_post( $lesson_id ) ? llms_get_post( $lesson_id )->get( 'parent_course' ) : 0;
		$vars      = $this->build_base( $user_id, (int) $course_id );
		$vars['{lesson_name}'] = get_the_title( $lesson_id );
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'llms_lesson_completed', $phone, $vars, $user_id );
		}
	}

	private function build_base( int $user_id, int $course_id ): array {
		$user  = get_user_by( 'id', $user_id );
		$phone = preg_replace( '/[^0-9]/', '', get_user_meta( $user_id, 'llms_phone', true ) );
		return array(
			'{student_name}'    => $user ? $user->display_name : '',
			'{student_email}'   => $user ? $user->user_email   : '',
			'{student_phone}'   => $phone,
			'{course_name}'     => get_the_title( $course_id ),
			'{lesson_name}'     => '',
			'{completion_date}' => '',
			'{certificate_url}' => '',
		);
	}
}
