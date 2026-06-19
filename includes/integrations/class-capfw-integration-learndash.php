<?php
/**
 * LearnDash LMS integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAPFW_Integration_LearnDash extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'learndash'; }
	public function get_label(): string    { return 'LearnDash'; }
	public function get_category(): string { return 'LMS'; }
	public function get_plugin_file(): string { return 'sfwd-lms/sfwd_lms.php'; }

	public function get_triggers(): array {
		$vars = array(
			'{student_name}'  => __( 'Student display name', 'captain-funnel-for-whatsapp' ),
			'{student_email}' => __( 'Student email', 'captain-funnel-for-whatsapp' ),
			'{student_phone}' => __( 'Student phone (billing meta)', 'captain-funnel-for-whatsapp' ),
			'{course_name}'   => __( 'Course title', 'captain-funnel-for-whatsapp' ),
			'{lesson_name}'   => __( 'Lesson title', 'captain-funnel-for-whatsapp' ),
			'{quiz_name}'     => __( 'Quiz title', 'captain-funnel-for-whatsapp' ),
			'{quiz_score}'    => __( 'Quiz score percentage', 'captain-funnel-for-whatsapp' ),
			'{completion_date}' => __( 'Completion date', 'captain-funnel-for-whatsapp' ),
			'{certificate_url}' => __( 'Certificate download URL', 'captain-funnel-for-whatsapp' ),
			'{site_name}'     => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);

		return array(
			'ld_course_enrolled'   => array( 'label' => __( 'Course Enrolled', 'captain-funnel-for-whatsapp' ),   'description' => __( 'Fires when a student enrolls in a LearnDash course.', 'captain-funnel-for-whatsapp' ),   'variables' => $vars ),
			'ld_course_completed'  => array( 'label' => __( 'Course Completed', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when a student completes a LearnDash course.', 'captain-funnel-for-whatsapp' ),  'variables' => $vars ),
			'ld_lesson_completed'  => array( 'label' => __( 'Lesson Completed', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when a student completes a lesson.', 'captain-funnel-for-whatsapp' ),            'variables' => $vars ),
			'ld_quiz_completed'    => array( 'label' => __( 'Quiz Completed', 'captain-funnel-for-whatsapp' ),    'description' => __( 'Fires when a student completes a quiz.', 'captain-funnel-for-whatsapp' ),              'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'learndash_course_enrolled',   array( $this, 'on_course_enrolled' ), 10, 2 );
		add_action( 'learndash_course_completed',  array( $this, 'on_course_completed' ), 10, 1 );
		add_action( 'learndash_lesson_completed',  array( $this, 'on_lesson_completed' ), 10, 1 );
		add_action( 'learndash_quiz_completed',    array( $this, 'on_quiz_completed' ), 10, 2 );
	}

	public function on_course_enrolled( int $course_id, int $user_id ): void {
		$vars = $this->build_base( $user_id, $course_id );
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'ld_course_enrolled', $phone, $vars, $user_id );
		}
	}

	public function on_course_completed( array $data ): void {
		$user_id   = $data['user']->ID ?? 0;
		$course_id = $data['course']->ID ?? 0;
		$vars      = $this->build_base( $user_id, $course_id );
		$vars['{completion_date}']  = wp_date( get_option( 'date_format' ) );
		$vars['{certificate_url}']  = learndash_get_certificate_link( $course_id, $user_id ) ?? '';
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'ld_course_completed', $phone, $vars, $user_id );
		}
	}

	public function on_lesson_completed( array $data ): void {
		$user_id   = $data['user']->ID ?? 0;
		$lesson_id = $data['lesson']->ID ?? 0;
		$course_id = $data['course']->ID ?? 0;
		$vars      = $this->build_base( $user_id, $course_id );
		$vars['{lesson_name}'] = get_the_title( $lesson_id );
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'ld_lesson_completed', $phone, $vars, $user_id );
		}
	}

	public function on_quiz_completed( array $data, WP_User $user ): void {
		$course_id = $data['course_id'] ?? 0;
		$quiz_id   = $data['quiz'] ?? 0;
		$vars      = $this->build_base( $user->ID, $course_id );
		$vars['{quiz_name}']  = get_the_title( $quiz_id );
		$vars['{quiz_score}'] = ( $data['percentage'] ?? 0 ) . '%';
		$phone = $vars['{student_phone}'];
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'ld_quiz_completed', $phone, $vars, $user->ID );
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
			'{certificate_url}' => '',
		);
	}
}
