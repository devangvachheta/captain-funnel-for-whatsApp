<?php
/**
 * Admin — registers menu pages, enqueues React app, handles unified AJAX.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Admin
 */
class CAPFW_Admin {

	/**
	 * Register all admin hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_capfw_react_ajax', array( $this, 'handle_react_ajax' ) );
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_menu_pages() {
		add_menu_page(
			esc_html__( 'Captain Funnel for WhatsApp', 'captain-funnel-for-whatsapp' ),
			esc_html__( 'Captain Funnel', 'captain-funnel-for-whatsapp' ),
			'manage_options',
			'capfw-dashboard',
			array( $this, 'render_app_page' ),
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTA2IiBoZWlnaHQ9IjEwMiIgdmlld0JveD0iMCAwIDEwNiAxMDIiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxtYXNrIGlkPSJtYXNrMF82Nl81NzA0IiBzdHlsZT0ibWFzay10eXBlOmx1bWluYW5jZSIgbWFza1VuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeD0iMCIgeT0iMiIgd2lkdGg9Ijg2IiBoZWlnaHQ9Ijk3Ij4KPHBhdGggZD0iTTg1LjkzMDUgMjYuMTczM0M4MC43NzkgMTcuMzE0IDczLjAwMzEgMTAuNDE3NiA2My43NjI2IDYuNTEyMzFDNTQuNTIyIDIuNjA2OTggNDQuMzExMyAxLjkwMTgxIDM0LjY1MjYgNC41MDE5NEMyNC45OTM4IDcuMTAyMDkgMTYuNDA0NCAxMi44NjgyIDEwLjE2NDYgMjAuOTQwOUMzLjkyNDkxIDI5LjAxMzUgMC4zNjkwNzEgMzguOTYwMyAwLjAyNzE4NTYgNDkuMjk4M0MtMC4zMTQ3IDU5LjYzNjYgMi41NzU2OSA2OS44MTI0IDguMjY3NDUgNzguMzA5QzEzLjk1OTMgODYuODA1NSAyMi4xNDc2IDkzLjE2ODIgMzEuNjExOSA5Ni40NDc3QzQxLjA3NjEgOTkuNzI3MiA1MS4zMDkzIDk5Ljc0ODMgNjAuNzg2NiA5Ni41MDgzQzcwLjI2MzUgOTMuMjY4IDc4LjQ3NjMgODYuOTQgODQuMjAwOSA3OC40NjcxTDcxLjU1NDcgNjkuMjg1OUM2Ny43Mzk1IDc0LjkzMjcgNjIuMjY2IDc5LjE0OTkgNTUuOTUwMSA4MS4zMDk0QzQ5LjYzNDMgODMuNDY4NSA0Mi44MTQzIDgzLjQ1NDMgMzYuNTA3MSA4MS4yNjg5QzMwLjE5OTcgNzkuMDgzIDI0Ljc0MjcgNzQuODQzIDIwLjk0OTUgNjkuMTgwNEMxNy4xNTYyIDYzLjUxNzkgMTUuMjMgNTYuNzM2NSAxNS40NTc4IDQ5Ljg0NjlDMTUuNjg1NiA0Mi45NTcgMTguMDU1NCAzNi4zMjgyIDIyLjIxMzggMzAuOTQ4MkMyNi4zNzIyIDI1LjU2ODMgMzIuMDk2NiAyMS43MjU1IDM4LjUzMzcgMTkuOTkyNkM0NC45NzAyIDE4LjI1OTggNTEuNzc1MiAxOC43Mjk3IDU3LjkzMzggMjEuMzMyNEM2NC4wOTE4IDIzLjkzNTEgNjkuMjczOCAyOC41MzExIDcyLjcwNyAzNC40MzUzTDg1LjkzMDUgMjYuMTczM1oiIGZpbGw9ImJsYWNrIi8+CjwvbWFzaz4KPGcgbWFzaz0idXJsKCNtYXNrMF82Nl81NzA0KSI+CjxwYXRoIGQ9Ik04NS45MzA1IDI2LjE3MzNDODAuNzc5IDE3LjMxNCA3My4wMDMxIDEwLjQxNzYgNjMuNzYyNiA2LjUxMjMxQzU0LjUyMiAyLjYwNjk4IDQ0LjMxMTMgMS45MDE4MSAzNC42NTI2IDQuNTAxOTRDMjQuOTkzOCA3LjEwMjA5IDE2LjQwNDQgMTIuODY4MiAxMC4xNjQ2IDIwLjk0MDlDMy45MjQ5MSAyOS4wMTM1IDAuMzY5MDcxIDM4Ljk2MDMgMC4wMjcxODU2IDQ5LjI5ODNDLTAuMzE0NyA1OS42MzY2IDIuNTc1NjkgNjkuODEyNCA4LjI2NzQ1IDc4LjMwOUMxMy45NTkzIDg2LjgwNTUgMjIuMTQ3NiA5My4xNjgyIDMxLjYxMTkgOTYuNDQ3N0M0MS4wNzYxIDk5LjcyNzIgNTEuMzA5MyA5OS43NDgzIDYwLjc4NjYgOTYuNTA4M0M3MC4yNjM1IDkzLjI2OCA3OC40NzYzIDg2Ljk0IDg0LjIwMDkgNzguNDY3MUw3MS41NTQ3IDY5LjI4NTlDNjcuNzM5NSA3NC45MzI3IDYyLjI2NiA3OS4xNDk5IDU1Ljk1MDEgODEuMzA5NEM0OS42MzQzIDgzLjQ2ODUgNDIuODE0MyA4My40NTQzIDM2LjUwNzEgODEuMjY4OUMzMC4xOTk3IDc5LjA4MyAyNC43NDI3IDc0Ljg0MyAyMC45NDk1IDY5LjE4MDRDMTcuMTU2MiA2My41MTc5IDE1LjIzIDU2LjczNjUgMTUuNDU3OCA0OS44NDY5QzE1LjY4NTYgNDIuOTU3IDE4LjA1NTQgMzYuMzI4MiAyMi4yMTM4IDMwLjk0ODJDMjYuMzcyMiAyNS41NjgzIDMyLjA5NjYgMjEuNzI1NSAzOC41MzM3IDE5Ljk5MjZDNDQuOTcwMiAxOC4yNTk4IDUxLjc3NTIgMTguNzI5NyA1Ny45MzM4IDIxLjMzMjRDNjQuMDkxOCAyMy45MzUxIDY5LjI3MzggMjguNTMxMSA3Mi43MDcgMzQuNDM1M0w4NS45MzA1IDI2LjE3MzNaIiBmaWxsPSJibGFjayIvPgo8L2c+CjxwYXRoIGQ9Ik02My4xMTc4IDM5SDQyLjgwODFDNDAuNzA0OSAzOSAzOSA0MC4wMjMxIDM5IDQxLjI4NTJWNjIuNzE0OEMzOSA2My45NzY5IDQwLjcwNDkgNjUgNDIuODA4MSA2NUM0NC45MTEyIDY1IDQ2LjYxNjIgNjMuOTc2OSA0Ni42MTYyIDYyLjcxNDhWNTQuMjg1Mkg1OS4wNTU5QzYxLjE1OTEgNTQuMjg1MiA2Mi44NjQgNTMuMjYyMSA2Mi44NjQgNTJDNjIuODY0IDUwLjczNzkgNjEuMTU5MSA0OS43MTQ4IDU5LjA1NTkgNDkuNzE0OEg0Ni42MTYyVjQzLjU3MDNINjMuMTE3OEM2NS4yMjEgNDMuNTcwMyA2Ni45MjU5IDQyLjU0NzIgNjYuOTI1OSA0MS4yODUyQzY2LjkyNTkgNDAuMDIzMSA2NS4yMjEgMzkgNjMuMTE3OCAzOVoiIGZpbGw9ImJsYWNrIi8+CjxwYXRoIGQ9Ik04My44MzkgNDUuNEw4NS42MDc0IDM5LjgzMzVMODguNzIzMSA0OS43ODg3Qzg4Ljg3MjYgNDkuODU0OSA4OS4wMTI2IDQ5Ljg4NzIgODkuMTQ0IDQ5Ljg4NzJDOTAuMDYwOCA0OS44ODcyIDkwLjgxODMgNDguMDMxMyA5MS40MTc4IDQ0LjMyMDdDOTIuMDE2MSA0MC42MTAyIDkyLjUxMyAzOC42NDQ1IDkyLjkwNSAzOC40MjY0QzkzLjI5ODMgMzguMjA4MyA5My44MjE4IDM4LjA5ODUgOTQuNDc2OCAzOC4wOTg1Qzk0Ljk0NDggMzguMDk4NSA5NS41NzIgMzguMTM2NSA5Ni4zNTczIDM4LjIxMjVDOTcuMTQzNyAzOC4yODk5IDk3LjU3MzEgMzguMzI2NCA5Ny42NDkxIDM4LjMyNjRDOTkuMzg5NyAzOC4zMjY0IDEwMC41NSAzOC4zNzAxIDEwMS4xMyAzOC40NTczQzEwMS43MTEgMzguNTQ0NiAxMDIgMzguNzUxNCAxMDIgMzkuMDc3OEMxMDEuNTMyIDQwLjAzNDcgMTAxLjExNiA0MS4wNTc2IDEwMC43NSA0Mi4xNDUzQzEwMC4zODUgNDMuMjMzIDEwMC4yMDMgNDQuMzk2NyAxMDAuMjAzIDQ1LjYzNjRDMTAwLjEwOSA0Ni43MjQxIDk5Ljk3ODQgNDcuMjY3MiA5OS44MDk1IDQ3LjI2NzJDOTkuNzE1NCA0Ny4yNjcyIDk5LjU4NTEgNDcuMDQzNSA5OS40MTYzIDQ2LjU5NkM5OS4yNDc0IDQ2LjE0ODYgOTkuMTA3NSA0NS43NTAzIDk4Ljk5NTMgNDUuNDAxNEw5NC4xMzkgNjZIODguNjM2Mkw4NS42MDUgNTYuNjM0M0w4Mi41NzM3IDY2SDc3LjA3NDVMNzIuMDIwNCA0NC42NDcyQzcxLjYwOTEgNDUuNjk1NSA3MS4zMjMyIDQ2LjM5OSA3MS4xNjQgNDYuNzU5MkM3MS4wMDQ4IDQ3LjExOTUgNzAuODQwNyA0Ny4yOTk2IDcwLjY3MzEgNDcuMjk5NkM3MC40NDg3IDQ3LjI5OTYgNzAuMjcwMiA0Ni43NDIzIDcwLjEzOTkgNDUuNjI5M0M3MC4xMzk5IDQ0Ljk1MjUgNzAuMTE1OCA0NC4xNTYxIDcwLjA3IDQzLjIzODdDNzAuMDIyOSA0Mi4zMjEyIDcwIDQxLjgzMDEgNzAgNDEuNzY1NEM3MCA0MC44NDggNzAuMTU5MiA0MC4xNDQ0IDcwLjQ3NzcgMzkuNjUzM0M3MC43OTYxIDM5LjE2MjMgNzEuNDk4MSAzOC43NjQxIDcyLjU4MzcgMzguNDU4N0M3My42NjkzIDM4LjE1MzQgNzQuNjYwOSAzOCA3NS41NTk1IDM4Qzc2LjA4MyAzOCA3Ni41Nzg4IDM4LjA1NDkgNzcuMDQ2OCAzOC4xNjMyQzc3LjUxNDggMzguMjczIDc4LjA1NzYgMzguNDY4NiA3OC42NzUyIDM4Ljc1MjhDNzkuMjczNSAzOS4xMjQzIDc5LjcwNDEgNDAuMjU5OCA3OS45NjcxIDQyLjE1OEM4MC4wOTczIDQzLjgzOTUgODAuMjM4NSA0NS41MTk2IDgwLjM4OCA0Ny4yMDExQzgwLjUzNzYgNDguODgyNiA4MC43ODk3IDQ5LjcyMjYgODEuMTQ1NSA0OS43MjI2QzgxLjM2OTkgNDkuNzIyNiA4MS42ODM1IDQ5LjM4NDkgODIuMDg2NCA0OC43MDgxQzgyLjQ4ODEgNDguMDMxMyA4Mi45NzA2IDQ2Ljk3MzEgODMuNTMyNyA0NS41MzIyQzgzLjU4ODEgNDUuNDQ1IDgzLjY0OTcgNDUuNDAxNCA4My43MTQ4IDQ1LjQwMTRDODMuNzc4NyA0NS40IDgzLjgyMSA0NS40IDgzLjgzOSA0NS40WiIgZmlsbD0iYmxhY2siLz4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzY2XzU3MDQpIj4KPHBhdGggZD0iTTEwMS45MiA1LjAzMzA1QzEwMC42MDUgMy43MjI3OSA5OC44NTUgMy4wMDA3NSA5Ni45OTMgM0M5NS4xMzUxIDMgOTMuMzgyNyAzLjcyMTQxIDkyLjA1ODkgNS4wMzEzNEM5MC43MzI3IDYuMzQzNTIgOTAuMDAxNyA4LjA4NzMzIDkwIDkuOTM1MTdWOS45MzczVjkuOTM4NThDOTAuMDAwMiAxMS4wNTcgOTAuMjk0MSAxMi4xODUzIDkwLjg1MTggMTMuMjE1MUw5MC4wMTkxIDE3TDkzLjg0NzYgMTYuMTI5MkM5NC44MTcyIDE2LjYxNzggOTUuODk5MSAxNi44NzU1IDk2Ljk5MDMgMTYuODc1OUg5Ni45OTMxQzk4Ljg1MDYgMTYuODc1OSAxMDAuNjAzIDE2LjE1NDQgMTAxLjkyNyAxNC44NDQzQzEwMy4yNTQgMTMuNTMxMSAxMDMuOTg2IDExLjc4OTUgMTAzLjk4NyA5Ljk0MDUxQzEwMy45ODcgOC4xMDQ1MiAxMDMuMjUzIDYuMzYxNjggMTAxLjkyIDUuMDMzMDVaTTk2Ljk5MyAxNS43ODMySDk2Ljk5MDVDOTYuMDEwNyAxNS43ODI4IDk1LjAzOTkgMTUuNTM2OCA5NC4xODMyIDE1LjA3MTZMOTQuMDAyMSAxNC45NzM0TDkxLjQ1NjQgMTUuNTUyNEw5Mi4wMDkzIDEzLjAzOTNMOTEuOTAyNyAxMi44NTU1QzkxLjM3MjcgMTEuOTQxNSA5MS4wOTI3IDEwLjkzMjYgOTEuMDkyNyA5LjkzNzQxQzkxLjA5NDcgNi43MTYyOSA5My43NDEzIDQuMDkyNjggOTYuOTkyNyA0LjA5MjY4Qzk4LjU2MzUgNC4wOTMzMiAxMDAuMDM5IDQuNzAyMjYgMTAxLjE0OSA1LjgwNzExQzEwMi4yNzQgNi45MjkwNiAxMDIuODk0IDguMzk2ODcgMTAyLjg5NCA5Ljk0MDE5QzEwMi44OTIgMTMuMTYyIDEwMC4yNDUgMTUuNzgzMiA5Ni45OTMgMTUuNzgzMloiIGZpbGw9ImJsYWNrIi8+CjxwYXRoIGQ9Ik05NS4wOTA4IDYuODc4OTFIOTQuNzg0M0M5NC42Nzc2IDYuODc4OTEgOTQuNTA0MyA2LjkxODg1IDk0LjM1NzggNy4wNzgzMkM5NC4yMTExIDcuMjM3OSA5My43OTc5IDcuNjIzNiA5My43OTc5IDguNDA4MDJDOTMuNzk3OSA5LjE5MjQ0IDk0LjM3MTEgOS45NTAzOCA5NC40NTEgMTAuMDU2OUM5NC41MzEgMTAuMTYzMyA5NS41NTc2IDExLjgyNDQgOTcuMTgzNSAxMi40NjM0Qzk4LjUzNDcgMTIuOTk0NSA5OC44MDk4IDEyLjg4ODkgOTkuMTAzIDEyLjg2MjNDOTkuMzk2MyAxMi44MzU4IDEwMC4wNDkgMTIuNDc2NyAxMDAuMTgzIDEyLjEwNDRDMTAwLjMxNiAxMS43MzIyIDEwMC4zMTYgMTEuNDEzMSAxMDAuMjc2IDExLjM0NjRDMTAwLjIzNiAxMS4yOCAxMDAuMTI5IDExLjI0MDEgOTkuOTY5NCAxMS4xNjA0Qzk5LjgwOTQgMTEuMDgwNyA5OS4wMjU0IDEwLjY4ODQgOTguODc4OCAxMC42MzUxQzk4LjczMjEgMTAuNTgyMSA5OC42MjU1IDEwLjU1NTUgOTguNTE4OCAxMC43MTUxQzk4LjQxMjEgMTAuODc0NSA5OC4wOTggMTEuMjQzMiA5OC4wMDQ2IDExLjM0OTZDOTcuOTExNCAxMS40NTYxIDk3LjgxOCAxMS40Njk1IDk3LjY1OCAxMS4zODk3Qzk3LjQ5OCAxMS4zMDk3IDk2Ljk4ODEgMTEuMTM4MyA5Ni4zNzcgMTAuNTk1M0M5NS45MDE0IDEwLjE3MjcgOTUuNTcxNCA5LjYzMzY4IDk1LjQ3OCA5LjQ3NDExQzk1LjM4NDggOS4zMTQ2NCA5NS40NjgxIDkuMjI4MzMgOTUuNTQ4MyA5LjE0ODc2Qzk1LjYyMDIgOS4wNzc0MSA5NS43MTczIDguOTc5NzggOTUuNzk3MyA4Ljg4Njc1Qzk1Ljg3NzIgOC43OTM2MSA5NS44OTk5IDguNzI3MTcgOTUuOTUzMyA4LjYyMDc5Qzk2LjAwNjYgOC41MTQ0IDk1Ljk3OTkgOC40MjEyNiA5NS45NCA4LjM0MTU4Qzk1Ljg5OTkgOC4yNjE4IDk1LjU5MjkgNy40NzM0MiA5NS40NTA3IDcuMTU4MTFIOTUuNDUwOEM5NS4zMzA5IDYuODkyNTggOTUuMjA0OCA2Ljg4MzYxIDk1LjA5MDggNi44Nzg5MVoiIGZpbGw9ImJsYWNrIi8+CjwvZz4KPGRlZnM+CjxjbGlwUGF0aCBpZD0iY2xpcDBfNjZfNTcwNCI+CjxyZWN0IHdpZHRoPSIxNCIgaGVpZ2h0PSIxNCIgZmlsbD0iYmxhY2siIHRyYW5zZm9ybT0idHJhbnNsYXRlKDkwIDMpIi8+CjwvY2xpcFBhdGg+CjwvZGVmcz4KPC9zdmc+Cg==',
			56
		);

		add_submenu_page( 'capfw-dashboard', esc_html__( 'Dashboard', 'captain-funnel-for-whatsapp' ),    esc_html__( 'Dashboard', 'captain-funnel-for-whatsapp' ),      'manage_options', 'capfw-dashboard',     array( $this, 'render_app_page' ) );
		add_submenu_page( 'capfw-dashboard', esc_html__( 'Settings', 'captain-funnel-for-whatsapp' ),     esc_html__( 'Settings', 'captain-funnel-for-whatsapp' ),       'manage_options', 'capfw-settings',      array( $this, 'render_app_page' ) );
		add_submenu_page( 'capfw-dashboard', esc_html__( 'Integrations', 'captain-funnel-for-whatsapp' ), esc_html__( 'Integrations', 'captain-funnel-for-whatsapp' ),   'manage_options', 'capfw-integrations',  array( $this, 'render_app_page' ) );
		add_submenu_page( 'capfw-dashboard', esc_html__( 'Templates', 'captain-funnel-for-whatsapp' ),    esc_html__( 'Templates', 'captain-funnel-for-whatsapp' ),      'manage_options', 'capfw-templates',     array( $this, 'render_app_page' ) );
		add_submenu_page( 'capfw-dashboard', esc_html__( 'Funnels', 'captain-funnel-for-whatsapp' ),      esc_html__( 'Funnels', 'captain-funnel-for-whatsapp' ),        'manage_options', 'capfw-funnels',       array( $this, 'render_app_page' ) );
		add_submenu_page( 'capfw-dashboard', esc_html__( 'Message Logs', 'captain-funnel-for-whatsapp' ), esc_html__( 'Logs', 'captain-funnel-for-whatsapp' ),           'manage_options', 'capfw-logs',          array( $this, 'render_app_page' ) );
	}

	/**
	 * Enqueue React app assets — only on plugin pages.
	 * FIX: Correct WP submenu hook format: {parent_slug}_page_{submenu_slug}
	 * FIX: Use wp-element (not react/react-dom) so WP 6.x React globals are available.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ) {
		$capfw_pages = array(
			'toplevel_page_capfw-dashboard',
			'capfw-dashboard_page_capfw-settings',
			'capfw-dashboard_page_capfw-integrations',
			'capfw-dashboard_page_capfw-templates',
			'capfw-dashboard_page_capfw-funnels',
			'capfw-dashboard_page_capfw-logs',
		);

		if ( ! in_array( $hook, $capfw_pages, true ) ) {
			return;
		}

		wp_register_style( 'capfw-react-app-css', CAPFW_PLUGIN_URL . 'admin/css/capfw-react-app.css', array(), CAPFW_VERSION );
		wp_enqueue_style( 'capfw-react-app-css' );

		// The compiled bundle (capfw-react-app.js) ships with React 18 bundled inside.
		// DO NOT list wp-element here — that would load a second React instance from
		// WordPress core (potentially React 19 in WP 7.x), causing the fatal
		// "recentlyCreatedOwnerStacks" conflict. wp-i18n is safe as it has no React dep.
		wp_register_script( 'capfw-react-app-js', CAPFW_PLUGIN_URL . 'admin/js/capfw-react-app.js', array( 'wp-i18n' ), CAPFW_VERSION, true );
		wp_enqueue_script( 'capfw-react-app-js' );

		wp_localize_script(
			'capfw-react-app-js',
			'capfw_data',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'capfw_react_nonce' ),
				'plugin_url' => CAPFW_PLUGIN_URL,
				'version'    => CAPFW_VERSION,
				'home_url'   => home_url(),
				'page'       => sanitize_key( $_GET['page'] ?? 'capfw-dashboard' ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			)
		);

		wp_set_script_translations( 'capfw-react-app-js', 'captain-funnel-for-whatsapp', CAPFW_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Render the single-page React app container.
	 */
	public function render_app_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div id="capfw-react-app"></div>';
	}

	// =========================================================================
	// Unified AJAX — single endpoint, routed by 'type' field.
	// FIX: wp_die() only once at the end — removed duplicate calls inside methods.
	// =========================================================================

	/**
	 * Main AJAX router.
	 */
	public function handle_react_ajax() {
		check_ajax_referer( 'capfw_react_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'captain-funnel-for-whatsapp' ) ) );
			wp_die();
		}

		$type = sanitize_key( $_POST['type'] ?? '' );

		switch ( $type ) {
			case 'get_stats':        $this->ajax_get_stats();        break;
			case 'get_recent_logs':  $this->ajax_get_recent_logs();  break;
			case 'get_settings':     $this->ajax_get_settings();     break;
			case 'save_settings':    $this->ajax_save_settings();    break;
			case 'test_connection':  $this->ajax_test_connection();  break;
			case 'get_templates':    $this->ajax_get_templates();    break;
			case 'save_templates':   $this->ajax_save_templates();   break;
			case 'get_funnels':      $this->ajax_get_funnels();      break;
			case 'save_funnel':      $this->ajax_save_funnel();      break;
			case 'delete_funnel':    $this->ajax_delete_funnel();    break;
			case 'get_logs':              $this->ajax_get_logs();              break;
			case 'clear_logs':            $this->ajax_clear_logs();            break;
			case 'get_integrations':             $this->ajax_get_integrations();             break;
			case 'save_integrations':            $this->ajax_save_integrations();            break;
			case 'get_integration_msg_settings': $this->ajax_get_integration_msg_settings(); break;
			case 'save_integration_msg_settings':$this->ajax_save_integration_msg_settings();break;
			case 'get_available_triggers':$this->ajax_get_available_triggers(); break;
			case 'get_integration_templates': $this->ajax_get_integration_templates(); break;
			case 'save_integration_template': $this->ajax_save_integration_template(); break;
			default:
				wp_send_json_error( array( 'message' => esc_html__( 'Unknown request type.', 'captain-funnel-for-whatsapp' ) ) );
		}

		wp_die(); // Single wp_die() at the end — no duplicates in private methods.
	}

	// ── Stats ─────────────────────────────────────────────────────────────────

	private function ajax_get_stats() {
		wp_send_json_success( CAPFW_Logger::get_stats() );
	}

	private function ajax_get_recent_logs() {
		wp_send_json_success( CAPFW_Logger::get_logs( 5, 1 ) );
	}

	// ── Settings ──────────────────────────────────────────────────────────────

	/**
	 * Mask a sensitive token for display — shows only last 4 chars.
	 *
	 * @param string $token Raw token value.
	 * @return string
	 */
	private function mask_token( string $token ): string {
		if ( empty( $token ) ) {
			return '';
		}
		$visible = substr( $token, -4 );
		return str_repeat( '*', max( 8, strlen( $token ) - 4 ) ) . $visible;
	}

	private function ajax_get_settings() {
		$settings = (array) get_option( 'capfw_settings', array() );
		$token    = sanitize_text_field( $settings['access_token'] ?? '' );

		wp_send_json_success( array(
			// Fix #4: Never send the raw token to the browser — send a masked version.
			// The React UI shows this for UX confirmation; actual token stays server-side.
			'access_token'        => $this->mask_token( $token ),
			'access_token_is_set' => ! empty( $token ),
			'phone_number_id'     => sanitize_text_field( $settings['phone_number_id']     ?? '' ),
			'business_account_id' => sanitize_text_field( $settings['business_account_id'] ?? '' ),
			'admin_phone'         => sanitize_text_field( $settings['admin_phone']         ?? '' ),
			'enabled_statuses'    => (array) ( $settings['enabled_statuses'] ?? array() ),
			// Fix: Message Type toggle — 'text' (free-form, requires 24h customer
			// service window) or 'template' (pre-approved, deliverable anytime).
			'message_type'           => ( 'template' === ( $settings['message_type'] ?? 'text' ) ) ? 'template' : 'text',
			'template_name'          => sanitize_text_field( $settings['template_name']        ?? '' ),
			'template_language'      => sanitize_text_field( $settings['template_language']    ?? 'en_US' ),
			'template_no_variables'  => ! empty( $settings['template_no_variables'] ),
		) );
	}

	private function ajax_save_settings() {
		$raw      = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$incoming = json_decode( $raw, true );

		if ( ! is_array( $incoming ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid settings data.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$settings       = (array) get_option( 'capfw_settings', array() );
		$incoming_token = sanitize_text_field( $incoming['access_token'] ?? '' );

		// Fix #4: If the React UI sends back the masked placeholder (starts with '***'),
		// the user did not change the token — preserve the existing stored value.
		if ( ! empty( $incoming_token ) && strpos( $incoming_token, '***' ) !== 0 ) {
			$settings['access_token'] = $incoming_token;
		}

		$settings['phone_number_id']     = sanitize_text_field( $incoming['phone_number_id']     ?? '' );
		$settings['business_account_id'] = sanitize_text_field( $incoming['business_account_id'] ?? '' );
		$settings['admin_phone']         = sanitize_text_field( $incoming['admin_phone']         ?? '' );
		$settings['enabled_statuses']    = array_map( 'sanitize_key', (array) ( $incoming['enabled_statuses'] ?? array() ) );

		// Fix: Message Type toggle — 'text' or 'template'.
		$incoming_type             = sanitize_text_field( $incoming['message_type'] ?? 'text' );
		$settings['message_type']  = ( 'template' === $incoming_type ) ? 'template' : 'text';
		$settings['template_name']         = sanitize_text_field( $incoming['template_name']        ?? '' );
		$settings['template_language']     = sanitize_text_field( $incoming['template_language']    ?? 'en_US' );
		$settings['template_no_variables'] = ! empty( $incoming['template_no_variables'] );

		update_option( 'capfw_settings', $settings );
		wp_send_json_success( array( 'message' => esc_html__( 'Settings saved successfully.', 'captain-funnel-for-whatsapp' ) ) );
	}

	/**
	 * Test API connection.
	 *
	 * React sends back the masked token (***...XXXX) when the user hasn't typed
	 * a new one — in that case we must use the token stored in the DB, not the
	 * masked display value which would fail authentication.
	 */
	private function ajax_test_connection() {
		$settings   = (array) get_option( 'capfw_settings', array() );
		$saved_token = sanitize_text_field( $settings['access_token'] ?? '' );

		$live_token = isset( $_POST['access_token'] )    ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) )    : '';
		$live_phone = isset( $_POST['phone_number_id'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number_id'] ) ) : '';

		// If React sent back the masked placeholder, use the real saved token.
		$token = ( ! empty( $live_token ) && strpos( $live_token, '***' ) !== 0 )
			? $live_token
			: $saved_token;

		// Phone: prefer live form value, fall back to saved.
		$phone = ! empty( $live_phone )
			? $live_phone
			: sanitize_text_field( $settings['phone_number_id'] ?? '' );

		$result = CAPFW_WhatsApp_API::test_connection_with( $token, $phone );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => esc_html( $result['message'] ) ) );
		} else {
			wp_send_json_error( array( 'message' => esc_html( $result['message'] ) ) );
		}
	}

	// ── Templates ─────────────────────────────────────────────────────────────

	private function ajax_get_templates() {
		$settings  = (array) get_option( 'capfw_settings', array() );
		$statuses  = array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' );
		$templates = array();

		foreach ( $statuses as $status ) {
			$templates[ $status ] = sanitize_textarea_field( $settings[ 'template_' . sanitize_key( $status ) ] ?? '' );
		}

		wp_send_json_success( $templates );
	}

	private function ajax_save_templates() {
		$raw      = isset( $_POST['templates'] ) ? wp_unslash( $_POST['templates'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$incoming = json_decode( $raw, true );

		if ( ! is_array( $incoming ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid template data.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$settings = (array) get_option( 'capfw_settings', array() );
		$statuses = array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' );

		foreach ( $statuses as $status ) {
			if ( array_key_exists( $status, $incoming ) ) {
				$settings[ 'template_' . sanitize_key( $status ) ] = sanitize_textarea_field( $incoming[ $status ] );
			}
		}

		update_option( 'capfw_settings', $settings );
		wp_send_json_success( array( 'message' => esc_html__( 'Templates saved successfully.', 'captain-funnel-for-whatsapp' ) ) );
	}

	// ── Funnels ───────────────────────────────────────────────────────────────

	private function ajax_get_funnels() {
		global $wpdb;
		$table   = $wpdb->prefix . 'capfw_funnels';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$funnels = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );
		wp_send_json_success( $funnels ?: array() );
	}

	private function ajax_save_funnel() {
		global $wpdb;

		$funnel_id   = absint( $_POST['funnel_id']     ?? 0 );
		$funnel_name = sanitize_text_field( wp_unslash( $_POST['funnel_name']   ?? '' ) );
		$trigger     = sanitize_key( $_POST['trigger_event'] ?? '' );
		$status      = sanitize_key( $_POST['funnel_status'] ?? 'active' );

		if ( empty( $funnel_name ) || empty( $trigger ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Funnel name and trigger are required.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$table = $wpdb->prefix . 'capfw_funnels';

		if ( $funnel_id > 0 ) {
			$wpdb->update(
				$table,
				array( 'funnel_name' => $funnel_name, 'trigger_event' => $trigger, 'status' => $status ),
				array( 'id' => $funnel_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array( 'funnel_name' => $funnel_name, 'trigger_event' => $trigger, 'status' => $status ),
				array( '%s', '%s', '%s' )
			);
			$funnel_id = $wpdb->insert_id;
		}

		// FIX Critical #5: Return created_at so React can show it immediately.
		wp_send_json_success( array(
			'message'    => esc_html__( 'Funnel saved successfully.', 'captain-funnel-for-whatsapp' ),
			'funnel_id'  => absint( $funnel_id ),
			'created_at' => current_time( 'mysql' ),
		) );
	}

	private function ajax_delete_funnel() {
		global $wpdb;

		$funnel_id = absint( $_POST['funnel_id'] ?? 0 );
		if ( ! $funnel_id ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid funnel ID.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$wpdb->delete( $wpdb->prefix . 'capfw_funnel_steps', array( 'funnel_id' => $funnel_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'capfw_funnels',      array( 'id'        => $funnel_id ), array( '%d' ) );

		wp_send_json_success( array( 'message' => esc_html__( 'Funnel deleted.', 'captain-funnel-for-whatsapp' ) ) );
	}

	// ── Logs ──────────────────────────────────────────────────────────────────

	private function ajax_get_logs() {
		$per_page      = absint( $_POST['per_page'] ?? 20 );
		$paged         = absint( $_POST['paged']    ?? 1 );
		$filter_status = sanitize_key( $_POST['filter_status'] ?? 'all' );

		global $wpdb;
		$table  = $wpdb->prefix . 'capfw_logs';
		$offset = ( $paged - 1 ) * $per_page;
		$where  = '';
		$args   = array();

		if ( 'all' !== $filter_status ) {
			$where  = 'WHERE status = %s';
			$args[] = $filter_status;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $args ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where}", $args ) );
		} else {
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}

		$query_args = array_merge( $args, array( $per_page, $offset ) );
		$logs       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", $query_args ) );
		// phpcs:enable

		wp_send_json_success( array(
			'logs'  => $logs ?: array(),
			'total' => $total,
		) );
	}

	/**
	 * Clear logs — either all logs, or only logs older than N days.
	 *
	 * POST params:
	 *   mode = 'all' | 'older_than'  (default 'all')
	 *   days = int                  (used only when mode === 'older_than')
	 */
	private function ajax_clear_logs() {
		$mode = sanitize_key( $_POST['mode'] ?? 'all' );

		if ( 'older_than' === $mode ) {
			$days = absint( $_POST['days'] ?? 30 );
			if ( $days < 1 ) {
				wp_send_json_error( array( 'message' => esc_html__( 'Please enter a valid number of days.', 'captain-funnel-for-whatsapp' ) ) );
				return;
			}
			$result = CAPFW_Logger::clear_older_than( $days );
		} else {
			$result = CAPFW_Logger::clear_all();
		}

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed to clear logs. Please try again.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		wp_send_json_success( array(
			'message' => esc_html__( 'Logs cleared successfully.', 'captain-funnel-for-whatsapp' ),
			'deleted' => (int) $result,
		) );
	}

	// ── Integrations ──────────────────────────────────────────────────────────

	private function ajax_get_integrations() {
		$registry = CAPFW_Integration_Registry::instance();
		wp_send_json_success( $registry->get_all_for_api() );
	}

	private function ajax_save_integrations() {
		$raw      = isset( $_POST['enabled'] ) ? wp_unslash( $_POST['enabled'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$incoming = json_decode( $raw, true );

		if ( ! is_array( $incoming ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid data.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$settings = (array) get_option( 'capfw_settings', array() );
		$settings['enabled_integrations'] = array_map( 'sanitize_key', $incoming );
		update_option( 'capfw_settings', $settings );

		wp_send_json_success( array( 'message' => esc_html__( 'Integrations saved.', 'captain-funnel-for-whatsapp' ) ) );
	}


	private function ajax_get_integration_msg_settings() {
		$all = (array) get_option( 'capfw_integration_settings', array() );
		wp_send_json_success( $all );
	}

	private function ajax_save_integration_msg_settings() {
		$raw      = isset( $_POST['integration_settings'] ) ? wp_unslash( $_POST['integration_settings'] ) : '{}'; // phpcs:ignore
		$incoming = json_decode( $raw, true );

		if ( ! is_array( $incoming ) ) {
			wp_send_json_error( array( 'message' => 'Invalid data.' ) );
			return;
		}

		$sanitized = array();
		foreach ( $incoming as $slug => $cfg ) {
			$slug = sanitize_key( $slug );
			if ( ! $slug || ! is_array( $cfg ) ) continue;
			$sanitized[ $slug ] = array(
				'message_type'         => ( 'template' === ( $cfg['message_type'] ?? 'text' ) ) ? 'template' : 'text',
				'template_name'        => sanitize_text_field( $cfg['template_name']        ?? '' ),
				'template_language'    => sanitize_text_field( $cfg['template_language']     ?? 'en_US' ),
				'template_no_variables'=> ! empty( $cfg['template_no_variables'] ),
			);
		}

		update_option( 'capfw_integration_settings', $sanitized );
		wp_send_json_success( array( 'message' => 'Integration message settings saved.' ) );
	}

	private function ajax_get_available_triggers() {
		$registry = CAPFW_Integration_Registry::instance();
		wp_send_json_success( $registry->get_available_triggers() );
	}

	private function ajax_get_integration_templates() {
		$slug     = sanitize_key( $_POST['integration_slug'] ?? '' );
		$registry = CAPFW_Integration_Registry::instance();
		wp_send_json_success( $registry->get_templates_for_integration( $slug ) );
	}

	private function ajax_save_integration_template() {
		global $wpdb;

		$slug        = sanitize_key( $_POST['integration_slug'] ?? '' );
		$trigger_key = sanitize_key( $_POST['trigger_key']      ?? '' );
		$body        = sanitize_textarea_field( wp_unslash( $_POST['template_body'] ?? '' ) );
		$status      = sanitize_key( $_POST['status'] ?? 'active' );


		if ( empty( $slug ) || empty( $trigger_key ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Integration slug and trigger key are required.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		if ( empty( $body ) ) {
		}

		$table = $wpdb->prefix . 'capfw_templates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT id FROM {$table} WHERE integration_slug = %s AND trigger_key = %s",
			$slug,
			$trigger_key
		) );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array( 'template_body' => $body, 'status' => $status ),
				array( 'integration_slug' => $slug, 'trigger_key' => $trigger_key ),
				array( '%s', '%s' ),
				array( '%s', '%s' )
			);
		} else {
			$wpdb->insert(
				$table,
				array( 'integration_slug' => $slug, 'trigger_key' => $trigger_key, 'template_body' => $body, 'status' => $status ),
				array( '%s', '%s', '%s', '%s' )
			);
		}

		wp_send_json_success( array( 'message' => esc_html__( 'Template saved.', 'captain-funnel-for-whatsapp' ) ) );
	}
}
