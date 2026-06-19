<?php
/**
 * Tutor LMS integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAPFW_Integration_TutorLMS extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'tutor_lms'; }
	public function get_label(): string    { return 'Tutor LMS'; }
	public function get_category(): string { return 'LMS'; }
	public function get_plugin_file(): string { return 'tutor/tutor.php'; }

	public function get_triggers(): array {
		$vars = array(
			'{student_name}'    => __( 'Student display name', 'captain-funnel-for-whatsapp' ),
			'{student_email}'   => __( 'Student email', 'captain-funnel-for-whatsapp' ),
			'{student_phone}'   => __( 'Student phone (billing meta)', 'captain-funnel-for-whatsapp' ),
			'{course_name}'     => __( 'Course title', 'captain-funnel-for-whatsapp' ),
			'{lesson_name}'     => __( 'Lesson title', 'captain-funnel-for-whatsapp' ),
			'{quiz_name}'       => __( 'Quiz title', 'captain-funnel-for-whatsapp' ),
			'{quiz_score}'      => __( 'Quiz score percentage', 'captain-funnel-for-whatsapp' ),
			'{completion_date}' => __( 'Completion date', 'captain-funnel-for-whatsapp' ),
			'{site_name}'       => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);

		return array(
			'tutor_enrolled'          => array( 'label' => __( 'Course Enrolled', 'captain-funnel-for-whatsapp' ),    'description' => __( 'Fires when student enrolls in a Tutor LMS course.', 'captain-funnel-for-whatsapp' ),    'variables' => $vars ),
			'tutor_course_completed'  => array( 'label' => __( 'Course Completed', 'captain-funnel-for-whatsapp' ),   'description' => __( 'Fires when student completes a Tutor LMS course.', 'captain-funnel-for-whatsapp' ),   'variables' => $vars ),
			'tutor_lesson_completed'  => array( 'label' => __( 'Lesson Completed', 'captain-funnel-for-whatsapp' ),   'description' => __( 'Fires when student completes a lesson.', 'captain-funnel-for-whatsapp' ),             'variables' => $vars ),
			'tutor_quiz_attempt_ended'=> array( 'label' => __( 'Quiz Attempt Ended', 'captain-funnel-for-whatsapp' ), 'description' => __( 'Fires when a Tutor LMS quiz attempt ends.', 'captain-funnel-for-whatsapp' ),          'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'tutor_after_enroll',          array( $this, 'on_enrolled' ),          10, 2 );
		add_action( 'tutor_course_complete_after',  array( $this, 'on_course_completed' ),  10, 2 );
		add_action( 'tutor_lesson_completed_after', array( $this, 'on_lesson_completed' ),  10, 2 );
		add_action( 'tutor_quiz_attempt_ended',     array( $this, 'on_quiz_ended' ),        10, 1 );
	}

	public function on_enrolled( int $course_id, int $user_id ): void {
		$vars  = $this->build_base( $user_id, $course_id );
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'tutor_enrolled', $phone, $vars, $user_id );
		}
	}

	public function on_course_completed( int $course_id, int $user_id ): void {
		$vars  = $this->build_base( $user_id, $course_id );
		$vars['{completion_date}'] = wp_date( get_option( 'date_format' ) );
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'tutor_course_completed', $phone, $vars, $user_id );
		}
	}

	public function on_lesson_completed( int $lesson_id, int $user_id ): void {
		$course_id = tutor_utils()->get_course_id_by_lesson( $lesson_id );
		$vars      = $this->build_base( $user_id, $course_id );
		$vars['{lesson_name}'] = get_the_title( $lesson_id );
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'tutor_lesson_completed', $phone, $vars, $user_id );
		}
	}

	public function on_quiz_ended( object $attempt ): void {
		$user_id   = (int) $attempt->user_id;
		$course_id = (int) $attempt->course_id;
		$quiz_id   = (int) $attempt->quiz_id;
		$vars      = $this->build_base( $user_id, $course_id );
		$vars['{quiz_name}']  = get_the_title( $quiz_id );
		$vars['{quiz_score}'] = $attempt->earned_marks . '/' . $attempt->total_marks;
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'tutor_quiz_attempt_ended', $phone, $vars, $user_id );
		}
	}

	private function build_base( int $user_id, int $course_id ): array {
		$user  = get_user_by( 'id', $user_id );
		$phone = preg_replace( '/[^0-9]/', '', get_user_meta( $user_id, 'billing_phone', true ) );
		return array(
			'{student_name}'    => $user ? $user->display_name : '',
			'{student_email}'   => $user ? $user->user_email   : '',
			'{student_phone}'   => $phone,
			'{course_name}'     => get_the_title( $course_id ),
			'{lesson_name}'     => '',
			'{quiz_name}'       => '',
			'{quiz_score}'      => '',
			'{completion_date}' => '',
		);
	}
}
