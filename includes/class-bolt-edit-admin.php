<?php
/**
 * Bolt Edit admin page: Changes, Get started, About, Changelog, Support.
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
		add_action( 'admin_bar_menu', array( __CLASS__, 'register_admin_bar_shortcut' ), 80 );
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
		$svg  = file_get_contents( BOLT_EDIT_PLUGIN_PATH . 'admin/img/bolt-edit-icon-white.svg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$icon = 'data:image/svg+xml;base64,' . base64_encode( $svg ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		self::$page_hook = add_menu_page(
			__( 'Bolt Edit', 'bolt-edit' ),
			__( 'Bolt Edit', 'bolt-edit' ),
			'edit_pages',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' ),
			$icon,
			58
		);
	}

	/**
	 * Add an "Open Bolt Edit" shortcut to the admin bar while viewing the site.
	 *
	 * @param WP_Admin_Bar $admin_bar WordPress admin bar instance.
	 * @return void
	 */
	public static function register_admin_bar_shortcut( $admin_bar ) {
		if ( is_admin() || ! Bolt_Edit_Plugin::can_edit() ) {
			return;
		}

		$current_url = home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) ) );
		$editor_url  = add_query_arg( 'bolt-edit-edit', '1', $current_url );

		$admin_bar->add_node(
			array(
				'id'    => 'bolt-edit-open-editor',
				'title' => __( 'Open Bolt Edit', 'bolt-edit' ),
				'href'  => esc_url( $editor_url ),
				'meta'  => array(
					'class' => 'bolt-edit-admin-bar-shortcut',
				),
			)
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

		$css_path = BOLT_EDIT_PLUGIN_PATH . 'admin/css/admin.css';
		$js_path  = BOLT_EDIT_PLUGIN_PATH . 'admin/js/admin.js';

		wp_enqueue_style(
			'bolt-edit-admin',
			BOLT_EDIT_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : BOLT_EDIT_VERSION
		);
		wp_enqueue_script(
			'bolt-edit-admin',
			BOLT_EDIT_PLUGIN_URL . 'admin/js/admin.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : BOLT_EDIT_VERSION,
			true
		);
	}

	/**
	 * Render the page. Every tab is in the DOM; JS switches between them.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'bolt-edit' ) );
		}

		$tabs = array(
			'changes'     => __( 'Changes', 'bolt-edit' ),
			'comments'    => __( 'Comments', 'bolt-edit' ),
			'get-started' => __( 'Get started', 'bolt-edit' ),
			'about'       => __( 'About', 'bolt-edit' ),
			'changelog'   => __( 'Release notes', 'bolt-edit' ),
			'license'     => __( 'License', 'bolt-edit' ),
		);
		if ( current_user_can( 'manage_options' ) ) {
			$tabs['settings'] = __( 'Settings', 'bolt-edit' );
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

			<div data-tab-panel="changes"><?php self::render_changes(); ?></div>
			<div data-tab-panel="comments" hidden><?php self::render_comments(); ?></div>
			<?php if ( current_user_can( 'manage_options' ) ) : ?><div data-tab-panel="settings" hidden><?php self::render_settings(); ?></div><?php endif; ?>
			<div data-tab-panel="get-started" hidden><?php self::render_get_started(); ?></div>
			<div data-tab-panel="about" hidden><?php self::render_about(); ?></div>
			<div data-tab-panel="changelog" hidden><?php self::render_changelog(); ?></div>
			<div data-tab-panel="license" hidden><?php self::render_license(); ?></div>

		</div>
		<?php
	}

	private static function render_settings() {
		?>
		<div class="bolt-edit-content"><div class="bolt-edit-card bolt-edit-card--full">
			<div class="bolt-edit-page-intro"><span class="bolt-edit-eyebrow"><?php esc_html_e( 'A simpler editing experience', 'bolt-edit' ); ?></span><h2><?php esc_html_e( 'Client mode', 'bolt-edit' ); ?></h2></div>
			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success notice.
			if ( isset( $_GET['bolt-edit-settings-saved'] ) ) : ?><p class="bolt-edit-settings-notice" role="status"><?php esc_html_e( 'Settings saved.', 'bolt-edit' ); ?></p><?php endif; ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="bolt_edit_save_settings"><?php wp_nonce_field( 'bolt_edit_save_settings' ); ?>
				<label class="bolt-edit-setting-row"><span><strong><?php esc_html_e( 'Enable client mode', 'bolt-edit' ); ?></strong><small><?php esc_html_e( 'Create the Clients role and simplify their editing screens.', 'bolt-edit' ); ?></small></span><span class="bolt-edit-setting-switch"><input type="checkbox" name="client_mode" value="1" role="switch" aria-label="<?php esc_attr_e( 'Enable client mode', 'bolt-edit' ); ?>" <?php checked( Bolt_Edit_Client::enabled() ); ?>><span class="bolt-edit-setting-switch__track" aria-hidden="true"></span></span></label>
				<p><?php esc_html_e( 'Assign the Clients role to your client accounts in WordPress. Clients start on the website and use the existing managed-content links to open the correct editor.', 'bolt-edit' ); ?></p>
				<p class="bolt-edit-settings-notice"><?php esc_html_e( 'The WordPress top bar and left menu are hidden. The editor sidebar stays available for featured images, categories, and content fields.', 'bolt-edit' ); ?></p>
				<p><?php esc_html_e( 'Static page content stays editable through the pencil. Clients can create, edit, and trash blog posts, products, and frontend-facing custom post types. Pages remain unavailable in the backend.', 'bolt-edit' ); ?></p>
				<p><?php esc_html_e( 'Disabling client mode keeps assigned users in the Clients role but removes their content-editing access until you enable it again or assign another role.', 'bolt-edit' ); ?></p>
				<button class="bolt-edit-button" type="submit"><?php esc_html_e( 'Save settings', 'bolt-edit' ); ?></button>
			</form>
		</div></div>
		<?php
		Bolt_Edit_Updates::screen();
	}

	private static function render_comments() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only inbox filtering.
		$resolved = isset( $_GET['bolt-edit-comment-status'] ) && 'resolved' === $_GET['bolt-edit-comment-status'];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination.
		$page = isset( $_GET['bolt-edit-comment-page'] ) ? max( 1, absint( $_GET['bolt-edit-comment-page'] ) ) : 1;
		$rows = Bolt_Edit_Comments::inbox( $resolved, $page );
		$more = count( $rows ) > 50;
		$rows = array_slice( $rows, 0, 50 );
		$base = add_query_arg( 'page', self::MENU_SLUG, admin_url( 'admin.php' ) );
		?>
		<div class="bolt-edit-content bolt-edit-content--wide"><div class="bolt-edit-card bolt-edit-card--full">
			<div class="bolt-edit-page-intro"><span class="bolt-edit-eyebrow"><?php esc_html_e( 'Client feedback', 'bolt-edit' ); ?></span><h2><?php esc_html_e( 'Comments', 'bolt-edit' ); ?></h2><p><?php esc_html_e( 'Private notes attached to the website. Content changes stay in their own tab.', 'bolt-edit' ); ?></p></div>
			<nav class="bolt-edit-comment-filters" aria-label="<?php esc_attr_e( 'Comment status', 'bolt-edit' ); ?>">
				<a class="bolt-edit-button<?php echo $resolved ? ' bolt-edit-button--quiet' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'bolt-edit-comment-status', 'open', $base ) . '#comments' ); ?>"><?php esc_html_e( 'Open', 'bolt-edit' ); ?></a>
				<a class="bolt-edit-button<?php echo ! $resolved ? ' bolt-edit-button--quiet' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'bolt-edit-comment-status', 'resolved', $base ) . '#comments' ); ?>"><?php esc_html_e( 'Resolved', 'bolt-edit' ); ?></a>
			</nav>
			<?php if ( ! $rows ) : ?><p class="bolt-edit-comment-empty"><?php echo esc_html( $resolved ? __( 'No resolved comments yet.', 'bolt-edit' ) : __( 'No open comments.', 'bolt-edit' ) ); ?></p><?php endif; ?>
			<?php foreach ( $rows as $row ) : $target = json_decode( $row->target, true ); ?>
				<article class="bolt-edit-comment-record">
					<div class="bolt-edit-comment-heading"><h3><?php echo esc_html( $target['label'] ?? __( 'Page element', 'bolt-edit' ) ); ?></h3><span class="bolt-edit-comment-badge"><?php echo esc_html( $resolved ? __( 'Resolved', 'bolt-edit' ) : __( 'Comment', 'bolt-edit' ) ); ?></span></div>
					<p class="bolt-edit-comment-text"><?php echo esc_html( $row->comment_text ); ?></p>
					<p class="bolt-edit-comment-meta"><?php echo esc_html( $row->user_name . ' · ' . $row->page_title . ' · ' . wp_date( 'j M Y, H:i', strtotime( $row->created_at . ' UTC' ) ) ); ?></p>
					<div class="bolt-edit-comment-actions"><a class="bolt-edit-button bolt-edit-button--quiet" href="<?php echo esc_url( add_query_arg( array( 'bolt-edit-edit' => '1', 'bolt-edit-comment' => $row->comment_id ), $row->page_url ) ); ?>"><?php esc_html_e( 'View on website', 'bolt-edit' ); ?></a>
					<?php if ( current_user_can( 'manage_options' ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="bolt_edit_resolve_comment"><input type="hidden" name="comment_id" value="<?php echo absint( $row->comment_id ); ?>"><input type="hidden" name="resolved" value="<?php echo $resolved ? '0' : '1'; ?>"><?php wp_nonce_field( 'bolt_edit_resolve_comment_' . $row->comment_id ); ?><button class="bolt-edit-button" type="submit"><?php echo esc_html( $resolved ? __( 'Reopen', 'bolt-edit' ) : __( 'Resolve', 'bolt-edit' ) ); ?></button></form><?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
			<nav class="bolt-edit-comment-actions" aria-label="<?php esc_attr_e( 'Comment pages', 'bolt-edit' ); ?>"><?php if ( $page > 1 ) : ?><a class="bolt-edit-button bolt-edit-button--quiet" href="<?php echo esc_url( add_query_arg( array( 'bolt-edit-comment-status' => $resolved ? 'resolved' : 'open', 'bolt-edit-comment-page' => $page - 1 ), $base ) . '#comments' ); ?>"><?php esc_html_e( 'Previous', 'bolt-edit' ); ?></a><?php endif; ?><?php if ( $more ) : ?><a class="bolt-edit-button bolt-edit-button--quiet" href="<?php echo esc_url( add_query_arg( array( 'bolt-edit-comment-status' => $resolved ? 'resolved' : 'open', 'bolt-edit-comment-page' => $page + 1 ), $base ) . '#comments' ); ?>"><?php esc_html_e( 'Next', 'bolt-edit' ); ?></a><?php endif; ?></nav>
		</div></div>
		<?php
	}

	/**
	 * Changes tab: open button and the activity log.
	 *
	 * @return void
	 */
	private static function render_changes() {
		$activities = Bolt_Edit_Activity::get_recent( 100 );
		?>
		<div class="bolt-edit-content bolt-edit-content--onboarding">
			<div class="bolt-edit-card bolt-edit-card--full bolt-edit-changes">
				<div class="bolt-edit-page-intro">
					<span class="bolt-edit-eyebrow"><?php esc_html_e( 'Content history', 'bolt-edit' ); ?></span>
					<h2><?php esc_html_e( 'Changes', 'bolt-edit' ); ?></h2>
				</div>

				<?php if ( empty( $activities ) ) : ?>
					<div class="bolt-edit-admin__empty">
						<span class="dashicons dashicons-clock" aria-hidden="true"></span>
						<h3><?php esc_html_e( 'No changes yet', 'bolt-edit' ); ?></h3>
						<p><?php esc_html_e( 'Every change saved through Bolt Edit appears here, with the value before and after.', 'bolt-edit' ); ?></p>
					</div>
				<?php else : ?>
					<ul class="bolt-edit-admin__list">
						<?php foreach ( $activities as $activity ) : ?>
							<li class="bolt-edit-admin__record">
								<?php self::render_activity_row( $activity ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>

		</div>
		<?php
	}

	/**
	 * Get started tab: one part for people, one for the AI agent.
	 *
	 * @return void
	 */
	private static function render_get_started() {
		$instructions_file = BOLT_EDIT_PLUGIN_PATH . 'docs/agent-instructions.md';
		$instructions      = file_exists( $instructions_file ) ? file_get_contents( $instructions_file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$prompt_intro      = __( 'We are going to build a custom WordPress theme that works with the Bolt Edit plugin. Bolt Edit has specific theme-building instructions that you must follow throughout the project. Find and read the complete instructions in WordPress admin under Bolt Edit > Get Started > For AI Agents, or locate docs/agent-instructions.md inside the active Bolt Edit plugin. If your environment cannot access them, ask me to copy them into this conversation.', 'bolt-edit' );
		$idea_prompt       = implode(
			"\n\n",
			array(
				$prompt_intro,
				__( 'Generate the website design and code inside the theme according to the prompt I provide. Build the first complete version and follow the Bolt Edit instructions throughout the project.', 'bolt-edit' ),
				__( 'Website link: [Insert link]', 'bolt-edit' ),
				__( 'Website idea or design brief:', 'bolt-edit' ),
			)
		);
		$html_prompt       = implode(
			"\n\n",
			array(
				$prompt_intro,
				__( 'I am providing an HTML template. Start with the complete template package I provide and convert it into a maintainable custom WordPress theme by following the Bolt Edit instructions.', 'bolt-edit' ),
				__( 'Website link: [Insert link]', 'bolt-edit' ),
				__( 'HTML template location or files:', 'bolt-edit' ),
			)
		);
		?>
		<div class="bolt-edit-content bolt-edit-content--onboarding">
			<div class="bolt-edit-card bolt-edit-card--full">
				<div data-subtabs>
					<div class="bolt-edit-subtabs" role="tablist">
						<button type="button" class="bolt-edit-subtabs__btn bolt-edit-subtabs__btn--active" data-subtab="humans" role="tab" aria-selected="true"><?php esc_html_e( 'For you', 'bolt-edit' ); ?></button>
						<button type="button" class="bolt-edit-subtabs__btn" data-subtab="agents" role="tab" aria-selected="false"><?php esc_html_e( 'For AI agents', 'bolt-edit' ); ?></button>
					</div>

					<div data-subtab-panel="humans">
						<div class="bolt-edit-get-started__intro">
							<span class="bolt-edit-eyebrow"><?php esc_html_e( 'Choose your starting point', 'bolt-edit' ); ?></span>
							<h2><?php esc_html_e( 'Build a custom theme with your AI coding tool', 'bolt-edit' ); ?></h2>
							<p><?php esc_html_e( 'Bolt Edit provides the editable content layer and the rules your agent follows. Your coding tool builds the WordPress theme.', 'bolt-edit' ); ?></p>
						</div>

						<div class="bolt-edit-agent-tools" aria-label="<?php esc_attr_e( 'Compatible AI coding tools', 'bolt-edit' ); ?>">
							<span class="bolt-edit-agent-tools__label"><?php esc_html_e( 'Works with', 'bolt-edit' ); ?></span>
							<span class="bolt-edit-agent-tool"><?php esc_html_e( 'Codex', 'bolt-edit' ); ?></span>
							<span class="bolt-edit-agent-tool"><?php esc_html_e( 'Claude', 'bolt-edit' ); ?></span>
							<span class="bolt-edit-agent-tool"><?php esc_html_e( 'Cursor', 'bolt-edit' ); ?></span>
							<span class="bolt-edit-agent-tool"><?php esc_html_e( 'Any other coding agents', 'bolt-edit' ); ?></span>
						</div>

						<div class="bolt-edit-paths">
							<article class="bolt-edit-path">
								<div class="bolt-edit-path__icon"><span class="dashicons dashicons-art" aria-hidden="true"></span></div>
								<span class="bolt-edit-path__number"><?php esc_html_e( 'Path 1', 'bolt-edit' ); ?></span>
								<h3><?php esc_html_e( 'Start from an idea', 'bolt-edit' ); ?></h3>
								<p><?php esc_html_e( 'Describe the website and let your agent design and build the complete theme from scratch.', 'bolt-edit' ); ?></p>
								<button type="button" class="bolt-edit-button" data-copy="bolt-edit-idea-prompt" data-copied-label="<?php esc_attr_e( 'Prompt copied', 'bolt-edit' ); ?>">
									<?php esc_html_e( 'Copy starter prompt', 'bolt-edit' ); ?>
								</button>
								<textarea id="bolt-edit-idea-prompt" class="bolt-edit-copy-source" readonly tabindex="-1" aria-hidden="true"><?php echo esc_textarea( $idea_prompt ); ?></textarea>
							</article>

							<article class="bolt-edit-path">
								<div class="bolt-edit-path__icon"><span class="dashicons dashicons-media-code" aria-hidden="true"></span></div>
								<span class="bolt-edit-path__number"><?php esc_html_e( 'Path 2', 'bolt-edit' ); ?></span>
								<h3><?php esc_html_e( 'Convert an HTML template', 'bolt-edit' ); ?></h3>
								<p><?php esc_html_e( 'Give your agent the complete HTML package and have it preserve the design while turning it into a proper WordPress theme.', 'bolt-edit' ); ?></p>
								<button type="button" class="bolt-edit-button" data-copy="bolt-edit-html-prompt" data-copied-label="<?php esc_attr_e( 'Prompt copied', 'bolt-edit' ); ?>">
									<?php esc_html_e( 'Copy conversion prompt', 'bolt-edit' ); ?>
								</button>
								<textarea id="bolt-edit-html-prompt" class="bolt-edit-copy-source" readonly tabindex="-1" aria-hidden="true"><?php echo esc_textarea( $html_prompt ); ?></textarea>
							</article>
						</div>

						<h3 class="bolt-edit-section-heading"><?php esc_html_e( 'How to use it', 'bolt-edit' ); ?></h3>
						<ol class="bolt-edit-steps">
							<li>
								<strong><?php esc_html_e( 'Connect your coding tool to WordPress', 'bolt-edit' ); ?></strong>
								<?php esc_html_e( 'Connect it to this website through MCP or another WordPress integration, or open your local WordPress installation as a project folder.', 'bolt-edit' ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Use a starter prompt', 'bolt-edit' ); ?></strong>
								<?php esc_html_e( 'Copy the prompt for your starting point, then add the website link and your idea or HTML template location.', 'bolt-edit' ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Let the agent build or convert the theme', 'bolt-edit' ); ?></strong>
								<?php esc_html_e( 'The agent builds the theme, creates the pages, imports editable images, and follows the Bolt Edit instructions.', 'bolt-edit' ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Review and refine the result', 'bolt-edit' ); ?></strong>
								<?php esc_html_e( 'Check every page, test text, buttons and images in Bolt Edit, and ask your agent to fix anything that needs work.', 'bolt-edit' ); ?>
							</li>
						</ol>

						<div class="bolt-edit-get-started__details">
							<div>
								<h3><?php esc_html_e( 'Later design changes', 'bolt-edit' ); ?></h3>
								<p><?php esc_html_e( 'Return to your agent with the same instructions. Saved content remains intact as long as the field IDs and types stay the same.', 'bolt-edit' ); ?></p>
							</div>
							<div>
								<h3><?php esc_html_e( 'Client access', 'bolt-edit' ); ?></h3>
								<p><?php esc_html_e( 'Administrators and Editors can use Bolt Edit. For a simpler client experience, enable client mode in Settings and assign the Clients role to your client accounts.', 'bolt-edit' ); ?></p>
							</div>
						</div>
					</div>

					<div data-subtab-panel="agents" hidden>
						<div class="bolt-edit-agent-instructions__intro">
							<span class="bolt-edit-eyebrow"><?php esc_html_e( 'The complete contract', 'bolt-edit' ); ?></span>
							<h2><?php esc_html_e( 'Instructions for the coding agent', 'bolt-edit' ); ?></h2>
							<p><?php esc_html_e( 'Copy these instructions into any coding agent, or let a connected agent read them through the WordPress files or MCP environment.', 'bolt-edit' ); ?></p>
						</div>
						<div class="bolt-edit-instructions__actions">
							<button type="button" class="bolt-edit-button" data-copy="bolt-edit-agent-instructions" data-copied-label="<?php esc_attr_e( 'Copied', 'bolt-edit' ); ?>">
								<?php esc_html_e( 'Copy instructions', 'bolt-edit' ); ?>
							</button>
							<span class="bolt-edit-instructions__path"><?php esc_html_e( 'Bundled with Bolt Edit:', 'bolt-edit' ); ?> <code>docs/agent-instructions.md</code></span>
						</div>
						<textarea id="bolt-edit-agent-instructions" class="bolt-edit-instructions__text" readonly spellcheck="false" aria-label="<?php esc_attr_e( 'Instructions for AI agents', 'bolt-edit' ); ?>"><?php echo esc_textarea( $instructions ); ?></textarea>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * About tab.
	 *
	 * @return void
	 */
	private static function render_about() {
		?>
		<div class="bolt-edit-content">
			<div class="bolt-edit-card bolt-edit-about">
				<div class="bolt-edit-page-intro">
					<span class="bolt-edit-eyebrow"><?php esc_html_e( 'About', 'bolt-edit' ); ?></span>
					<h2><?php esc_html_e( 'About Bolt Edit', 'bolt-edit' ); ?></h2>
				</div>
				<p><?php esc_html_e( 'Bolt Edit lets clients change the words and images on their website, right on the page, without touching the design.', 'bolt-edit' ); ?></p>

				<p><?php esc_html_e( 'The theme owns the design. Other plugins keep owning their own data. Every piece of static text or image in the theme becomes a field the client can edit. One field, one owner. Nothing lives in two places.', 'bolt-edit' ); ?></p>

				<p><?php esc_html_e( 'Bolt Edit does nothing on its own. It needs a theme built to use its helpers, following the instructions in the Get started tab. It will not make an existing page builder or block theme editable.', 'bolt-edit' ); ?></p>

				<p>
					<?php
					printf(
						/* translators: %s: link to the Bolt Connect website. */
						esc_html__( 'Bolt Edit is developed and maintained by %s.', 'bolt-edit' ),
						sprintf(
							'<a href="%s" target="_blank" rel="noopener noreferrer">Bolt Connect</a>',
							/* translators: Bolt Connect website in the language of this translation. */
							esc_url( __( 'https://www.boltconnect.nl/en', 'bolt-edit' ) )
						)
					);
					?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * License tab.
	 *
	 * @return void
	 */
	private static function render_license() {
		$license = file_get_contents( BOLT_EDIT_PLUGIN_PATH . 'LICENSE' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		?>
		<div class="bolt-edit-content">
			<div class="bolt-edit-card bolt-edit-card--full">
				<div class="bolt-edit-page-intro">
					<span class="bolt-edit-eyebrow"><?php esc_html_e( 'License', 'bolt-edit' ); ?></span>
					<h2><?php esc_html_e( 'GNU General Public License v2 or later', 'bolt-edit' ); ?></h2>
				</div>

				<p><?php esc_html_e( 'Copyright © 2026 Bolt Connect and contributors.', 'bolt-edit' ); ?></p>

				<p><?php esc_html_e( 'Bolt Edit is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.', 'bolt-edit' ); ?></p>

				<p><?php esc_html_e( 'Bolt Edit is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the full license text below for details.', 'bolt-edit' ); ?></p>

				<?php if ( $license ) : ?>
					<pre class="bolt-edit-license"><?php echo esc_html( $license ); ?></pre>
				<?php else : ?>
					<p><a href="https://www.gnu.org/licenses/gpl-2.0.html" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read the full license text', 'bolt-edit' ); ?></a></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Changelog tab.
	 *
	 * @return void
	 */
	private static function render_changelog() {
		?>
		<div class="bolt-edit-content">
			<div class="bolt-edit-card">
				<div class="bolt-edit-page-intro">
					<span class="bolt-edit-eyebrow"><?php esc_html_e( 'Release notes', 'bolt-edit' ); ?></span>
					<h2><?php esc_html_e( 'What is new in Bolt Edit', 'bolt-edit' ); ?></h2>
				</div>

				<div class="bolt-edit-changelog__release">
					<div class="bolt-edit-changelog__release-header">
						<span class="bolt-edit-changelog__version">v1.0.0</span>
						<span class="bolt-edit-changelog__date"><?php esc_html_e( 'October 2026', 'bolt-edit' ); ?></span>
					</div>
					<ul class="bolt-edit-changelog__list">
						<li><?php esc_html_e( 'Text, rich text, button and image fields, editable on the live page.', 'bolt-edit' ); ?></li>
						<li><?php esc_html_e( 'Empty image fields show a clickable placeholder for editors, so a theme can ship without images.', 'bolt-edit' ); ?></li>
						<li><?php esc_html_e( 'Managed regions for content owned by ACF, JetEngine, WooCommerce or WordPress, with a link to the right edit screen.', 'bolt-edit' ); ?></li>
						<li><?php esc_html_e( 'A Changes log with who, where, when, and the value before and after.', 'bolt-edit' ); ?></li>
						<li><?php esc_html_e( 'Private comments anywhere on the website, with a separate inbox and Resolve action.', 'bolt-edit' ); ?></li>
						<li><?php esc_html_e( 'Optional Clients role with simplified post and product editing screens.', 'bolt-edit' ); ?></li>
						<li><?php esc_html_e( 'Dutch translation. Bolt Edit follows the WordPress language: Dutch for every Dutch locale, English for all other languages.', 'bolt-edit' ); ?></li>
						<li><?php esc_html_e( 'Instructions for AI agents, included in the plugin.', 'bolt-edit' ); ?></li>
						<li><?php esc_html_e( 'Updates through GitHub releases.', 'bolt-edit' ); ?></li>
					</ul>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Support tab.
	 *
	 * @return void
	 */
	private static function render_support() {
		?>
		<div class="bolt-edit-content">
			<div class="bolt-edit-card">
				<h2><?php esc_html_e( 'Need help?', 'bolt-edit' ); ?></h2>
				<p><?php esc_html_e( 'Bolt Edit is maintained by Bolt Connect. Run into a problem or a bug? Contact us and describe what happened and on which page.', 'bolt-edit' ); ?></p>
				<a href="<?php echo esc_url( BOLT_EDIT_SUPPORT_URL ); ?>" target="_blank" rel="noopener noreferrer" class="bolt-edit-button">
					<?php esc_html_e( 'Contact Bolt Connect', 'bolt-edit' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one activity record.
	 *
	 * @param object $activity Activity database record.
	 * @return void
	 */
	private static function render_activity_row( $activity ) {
		$timestamp  = strtotime( $activity->created_at . ' UTC' );
		$when       = $timestamp ? wp_date( 'j M Y, H:i', $timestamp ) : $activity->created_at;
		$user_name  = $activity->user_name ? $activity->user_name : __( 'Unknown user', 'bolt-edit' );
		$page_id    = absint( $activity->page_id );
		$page_title = $page_id ? get_the_title( $page_id ) : '';
		$page_url   = $page_id ? get_permalink( $page_id ) : '';
		$edit_url   = add_query_arg(
			array(
				'bolt-edit-edit'  => '1',
				'bolt-edit-field' => $activity->field_id,
			),
			$page_url ? $page_url : home_url( '/' )
		);

		$who_markup = sprintf(
			'<span class="bolt-edit-admin__user">%s<span>%s</span></span>',
			get_avatar( absint( $activity->user_id ), 20, '', '', array( 'class' => 'bolt-edit-admin__avatar' ) ),
			esc_html( $user_name )
		);

		if ( $page_title && $page_url ) {
			$where_markup = sprintf( '<a href="%s">%s</a>', esc_url( $page_url ), esc_html( $page_title ) );
		} else {
			$where_markup = sprintf( '<span class="bolt-edit-admin__muted">%s</span>', esc_html__( 'a site-wide field', 'bolt-edit' ) );
		}

		$when_markup = sprintf( '<span class="bolt-edit-admin__when">%s</span>', esc_html( $when ) );
		?>
		<div class="bolt-edit-admin__record-head">
			<div class="bolt-edit-admin__record-col bolt-edit-admin__record-col--before">
				<strong class="bolt-edit-admin__field" title="<?php echo esc_attr( $activity->field_id ); ?>"><?php echo esc_html( $activity->field_label ); ?></strong>
			</div>
			<span class="bolt-edit-admin__record-gutter" aria-hidden="true"></span>
			<div class="bolt-edit-admin__record-col bolt-edit-admin__record-col--after">
				<p class="bolt-edit-admin__meta-line">
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: 1: user name with avatar, 2: page or location, 3: date and time. */
							__( '%1$s updated %2$s on %3$s', 'bolt-edit' ),
							$who_markup,
							$where_markup,
							$when_markup
						)
					);
					?>
				</p>
				<a class="bolt-edit-admin__edit" href="<?php echo esc_url( $edit_url ); ?>">
					<?php esc_html_e( 'Edit', 'bolt-edit' ); ?>
				</a>
			</div>
		</div>
		<div class="bolt-edit-admin__change">
			<?php self::render_value( __( 'Before:', 'bolt-edit' ), $activity->old_value, $activity->field_type ); ?>
			<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
			<?php self::render_value( __( 'After:', 'bolt-edit' ), $activity->new_value, $activity->field_type ); ?>
		</div>
		<?php
	}

	/**
	 * Render one before or after value box.
	 *
	 * Rich text keeps its formatting so a change such as adding bold is visible.
	 *
	 * @param string $label      Screen-reader label.
	 * @param string $value      Stored value.
	 * @param string $field_type Field type.
	 * @return void
	 */
	private static function render_value( $label, $value, $field_type ) {
		$is_rich = 'richtext' === $field_type && '' !== trim( wp_strip_all_tags( (string) $value ) );
		?>
		<div class="bolt-edit-admin__value-text<?php echo $is_rich ? ' bolt-edit-admin__value-text--rich' : ''; ?>">
			<span class="screen-reader-text"><?php echo esc_html( $label ); ?> </span>
			<?php
			if ( $is_rich ) {
				echo wp_kses( $value, Bolt_Edit_Fields::richtext_tags() );
			} else {
				echo esc_html( self::display_value( $value, $field_type ) );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Return a readable plain-text activity value.
	 *
	 * @param string $value      Stored value.
	 * @param string $field_type Field type.
	 * @return string
	 */
	private static function display_value( $value, $field_type ) {
		if ( 'image' === $field_type ) {
			$attachment_id = absint( $value );

			if ( ! $attachment_id ) {
				return __( 'No image', 'bolt-edit' );
			}

			$title = get_the_title( $attachment_id );

			return $title ? $title : sprintf(
				/* translators: %d: Media attachment ID. */
				__( 'Media #%d', 'bolt-edit' ),
				$attachment_id
			);
		}

		if ( 'button' === $field_type ) {
			$button = json_decode( (string) $value, true );

			if ( is_array( $button ) && ! empty( $button['text'] ) ) {
				return sprintf( '%s (%s)', $button['text'], isset( $button['url'] ) ? $button['url'] : '' );
			}

			return __( 'Empty', 'bolt-edit' );
		}

		$value = trim( wp_strip_all_tags( (string) $value ) );

		if ( '' === $value ) {
			return __( 'Empty', 'bolt-edit' );
		}

		return $value;
	}
}
