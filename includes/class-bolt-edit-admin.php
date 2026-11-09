<?php
/**
 * Bolt Edit admin page: the menu, the assets and the tab shell. Each tab has its own class in includes/admin/.
 *
 * @package Bolt_Edit
 */

defined( 'ABSPATH' ) || exit;

final class Bolt_Edit_Admin {
	const MENU_SLUG = 'bolt-edit';

	/**
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	private static $page_hook = '';

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Add the Bolt Edit top-level menu.
	 *
	 * The icon is passed as a data URI so WordPress recolours it to match the
	 * user's admin colour scheme.
	 *
	 * @return void
	 */
	public static function register_menu() {
		if ( ! Bolt_Edit_Client::can_manage() ) {
			return;
		}
		$svg  = file_get_contents( BOLT_EDIT_PLUGIN_PATH . 'admin/img/bolt-edit-icon-white.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$icon = 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		self::$page_hook = add_menu_page(
			__( 'Bolt Edit', 'bolt-edit' ),
			__( 'Bolt Edit', 'bolt-edit' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' ),
			$icon,
			58
		);
	}

	/**
	 * Load styles and scripts only on the Bolt Edit admin page.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( self::$page_hook !== $hook_suffix ) {
			return;
		}


		wp_enqueue_style(
			'bolt-edit-admin',
			BOLT_EDIT_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			Bolt_Edit_Plugin::asset_version( 'admin/css/admin.css' )
		);
		wp_enqueue_script(
			'bolt-edit-admin',
			BOLT_EDIT_PLUGIN_URL . 'admin/js/admin.js',
			array(),
			Bolt_Edit_Plugin::asset_version( 'admin/js/admin.js' ),
			true
		);
		if ( Bolt_Edit_Client::can_manage() ) {
			wp_enqueue_script( 'bolt-edit-focus', BOLT_EDIT_PLUGIN_URL . 'admin/js/focus.js', array(), Bolt_Edit_Plugin::asset_version( 'admin/js/focus.js' ), true );
			wp_localize_script( 'bolt-edit-focus', 'boltEditFocus', array( 'remove' => __( 'Remove', 'bolt-edit' ), 'one' => __( 'user has focus mode', 'bolt-edit' ), 'many' => __( 'users have focus mode', 'bolt-edit' ), 'noMatch' => __( 'No users found', 'bolt-edit' ), 'more' => __( 'more. Keep typing to narrow it down.', 'bolt-edit' ), 'error' => __( 'Could not save. Please try again.', 'bolt-edit' ) ) );
		}
	}

	/**
	 * Render the page. Every tab is in the DOM; JS switches between them.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! Bolt_Edit_Client::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'bolt-edit' ) );
		}

		$tabs = array(
			'activity'    => __( 'Change log', 'bolt-edit' ),
			'about'       => __( 'About', 'bolt-edit' ),
			'get-started' => __( 'How to use', 'bolt-edit' ),
			'license'     => __( 'License', 'bolt-edit' ),
		);
		if ( Bolt_Edit_Client::can_manage() ) {
			$tabs = array(
				'activity'        => $tabs['activity'],
				'templates'       => __( 'Templates', 'bolt-edit' ),
				'ai-instructions' => __( 'AI instructions', 'bolt-edit' ),
				'settings'        => __( 'Features', 'bolt-edit' ),
				'about'           => $tabs['about'],
				'get-started'     => $tabs['get-started'],
				'license'         => $tabs['license'],
			);
		}
		?>
		<div class="bolt-edit-wrap">

			<header class="bolt-edit-header">
				<div class="bolt-edit-header__left">
					<img src="<?php echo esc_url( add_query_arg( 'ver', filemtime( BOLT_EDIT_PLUGIN_PATH . 'admin/img/bolt-edit-icon-color.svg' ), BOLT_EDIT_PLUGIN_URL . 'admin/img/bolt-edit-icon-color.svg' ) ); ?>" alt="" aria-hidden="true" class="bolt-edit-header__icon" width="32" height="32">
					<h1 class="bolt-edit-header__title"><?php esc_html_e( 'Bolt Edit', 'bolt-edit' ); ?></h1>
					<span class="bolt-edit-header__version">v<?php echo esc_html( BOLT_EDIT_VERSION ); ?></span>
				</div>
				<nav class="bolt-edit-header__nav">
					<?php foreach ( $tabs as $slug => $label ) : ?>
						<a href="#<?php echo esc_attr( $slug ); ?>" data-tab="<?php echo esc_attr( $slug ); ?>" class="bolt-edit-header__link">
							<?php echo esc_html( $label ); ?>
						</a>
					<?php endforeach; ?>
					<a class="bolt-edit-header__open" href="<?php echo esc_url( add_query_arg( 'bolt-edit-edit', '1', home_url( '/' ) ) ); ?>">
						<?php esc_html_e( 'Open Bolt Edit', 'bolt-edit' ); ?>
					</a>
				</nav>
			</header>
			<hr class="wp-header-end">

			<div data-tab-panel="activity"><?php Bolt_Edit_Admin_Changelog::render(); ?></div>
			<?php if ( Bolt_Edit_Client::can_manage() ) : ?><div data-tab-panel="templates" hidden><?php Bolt_Edit_Admin_Templates::render(); ?></div><?php endif; ?>
			<?php if ( Bolt_Edit_Client::can_manage() ) : ?><div data-tab-panel="settings" hidden><?php Bolt_Edit_Admin_Features::render(); ?></div><?php endif; ?>
			<?php if ( Bolt_Edit_Client::can_manage() ) : ?><div data-tab-panel="ai-instructions" hidden><?php Bolt_Edit_AI_Instructions::render(); ?></div><?php endif; ?>
			<div data-tab-panel="get-started" hidden><?php Bolt_Edit_Admin_Get_Started::render(); ?></div>
			<div data-tab-panel="about" hidden><?php Bolt_Edit_Admin_About::render(); ?></div>
			<div data-tab-panel="license" hidden><?php Bolt_Edit_Admin_About::render_license(); ?></div>

		</div>
		<?php
	}
}
