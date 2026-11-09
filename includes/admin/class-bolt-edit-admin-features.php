<?php
/**
 * Bolt Edit admin: Features tab: Access, Focus mode, Dev mode, Clean-up, Hidden status and Plugin updates.
 *
 * @package Bolt_Edit
 */

defined( 'ABSPATH' ) || exit;

final class Bolt_Edit_Admin_Features {
	public static function render() {
		$focus_count  = count( Bolt_Edit_Client::user_ids() );
		$role_ready   = $focus_count > 0;
		$hidden_on    = Bolt_Edit_Plugin::show_hidden();
		$dev_count    = count( Bolt_Edit_Dev::user_ids() );
		/* translators: %d: number of users */
		$dev_status   = $dev_count > 0 ? sprintf( _n( '%d user', '%d users', $dev_count, 'bolt-edit' ), $dev_count ) : __( 'Nobody yet', 'bolt-edit' );

		/* translators: %d: number of users */
		$focus_status   = $role_ready ? sprintf( _n( '%d user', '%d users', $focus_count, 'bolt-edit' ), $focus_count ) : __( 'Nobody yet', 'bolt-edit' );
		$hidden_status  = $hidden_on ? __( 'On', 'bolt-edit' ) : __( 'Off', 'bolt-edit' );

		// The parts of this tab, in menu order: label, icon, whether it is set up, and its status line.
		$sections = array(
			'access'    => array( __( 'Access', 'bolt-edit' ), 'dashicons-lock', null !== Bolt_Edit_Access::saved_roles(), Bolt_Edit_Access::status() ),
			'focus'     => array( __( 'Focus mode', 'bolt-edit' ), 'dashicons-admin-users', $role_ready, $focus_status ),
			'dev'       => array( __( 'Dev mode', 'bolt-edit' ), 'dashicons-editor-code', $dev_count > 0, $dev_status ),
			'hidden'    => array( __( 'Hidden status', 'bolt-edit' ), 'dashicons-visibility', $hidden_on, $hidden_status ),
			'updates'   => array( __( 'Plugin updates', 'bolt-edit' ), 'dashicons-update', true, 'v' . BOLT_EDIT_VERSION ),
		);
		?>
			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success notices.
			if ( isset( $_GET['bolt-edit-settings-saved'] ) ) : ?><div class="bolt-edit-toast" role="status" data-bolt-edit-toast><?php esc_html_e( 'Settings saved.', 'bolt-edit' ); ?></div><?php endif; ?>
		<div class="bolt-edit-shell" data-subtabs>
			<nav class="bolt-edit-side" aria-label="<?php esc_attr_e( 'Settings sections', 'bolt-edit' ); ?>">
				<button type="button" class="bolt-edit-side__item bolt-edit-subtabs__btn--active" data-subtab="overview" data-subtab-hash="settings-overview" aria-selected="true"><span><?php esc_html_e( 'Overview', 'bolt-edit' ); ?></span></button>
				<?php foreach ( $sections as $key => $part ) : ?>
					<button type="button" class="bolt-edit-side__item" data-subtab="<?php echo esc_attr( $key ); ?>" data-subtab-hash="settings-<?php echo esc_attr( $key ); ?>" aria-selected="false"><span><?php echo esc_html( $part[0] ); ?></span><i class="bolt-edit-side__dot<?php echo $part[2] ? ' show' : ''; ?>"></i></button>
				<?php endforeach; ?>
			</nav>

			<div class="bolt-edit-main">
				<div data-subtab-panel="overview">
					<div class="bolt-edit-content"><div class="bolt-edit-card bolt-edit-card--full">
						<?php Bolt_Edit_Admin_UI::intro( __( 'Features', 'bolt-edit' ), __( 'Overview', 'bolt-edit' ), false ); ?>
						<p class="bolt-edit-overview__lead"><?php esc_html_e( 'Everything you can set up in Bolt Edit, and where each part stands.', 'bolt-edit' ); ?></p>
						<div class="bolt-edit-overview__grid">
							<?php foreach ( $sections as $key => $part ) : ?>
							<a class="bolt-edit-overview__tile" href="#settings-<?php echo esc_attr( $key ); ?>"><span class="bolt-edit-overview__icon" aria-hidden="true"><span class="dashicons <?php echo esc_attr( $part[1] ); ?>"></span></span><span class="bolt-edit-overview__body"><strong><?php echo esc_html( $part[0] ); ?></strong><span class="bolt-edit-overview__status<?php echo $part[2] ? ' is-on' : ''; ?>"><?php echo esc_html( $part[3] ); ?></span></span><span class="bolt-edit-overview__go dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></a>
						<?php endforeach; ?>
						</div>
					</div></div>
				</div>

				<div data-subtab-panel="access" hidden>
					<?php Bolt_Edit_Access::render_panel(); ?>
				</div>

				<div data-subtab-panel="focus" hidden>
		<div class="bolt-edit-content"><div class="bolt-edit-card bolt-edit-card--full">
			<?php Bolt_Edit_Admin_UI::intro( __( 'A calmer WordPress', 'bolt-edit' ), __( 'Focus mode', 'bolt-edit' ) ); ?>
			<p class="bolt-edit-focus__lead"><?php esc_html_e( 'Give the people who edit their own website a calmer WordPress. Pick the users below. They keep their normal role.', 'bolt-edit' ); ?></p>

			<h3 class="bolt-edit-focus__title"><?php esc_html_e( 'Who gets focus mode', 'bolt-edit' ); ?></h3>
			<p class="bolt-edit-focus__sub"><?php esc_html_e( 'Administrators can\'t be added.', 'bolt-edit' ); ?> <?php esc_html_e( 'Need a new user?', 'bolt-edit' ); ?> <a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>"><?php esc_html_e( 'Add one in Users', 'bolt-edit' ); ?></a>.</p>

			<?php Bolt_Edit_Admin_UI::user_picker( 'bolt_edit_focus', Bolt_Edit_Client::chosen_users(), __( 'Users with focus mode', 'bolt-edit' ) ); ?>

			<h3 class="bolt-edit-focus__title"><?php esc_html_e( 'What they see', 'bolt-edit' ); ?></h3>
			<p class="bolt-edit-focus__sub"><?php esc_html_e( 'These two settings apply to everyone selected above.', 'bolt-edit' ); ?></p>
			<?php Bolt_Edit_Admin_UI::save_form_open( 'focus' ); ?>
				<?php foreach ( Bolt_Edit_Client::switch_fields() as $key => $field ) : ?>
					<div class="bolt-edit-setting-row bolt-edit-focus__row"><span><strong><?php echo esc_html( $field[0] ); ?></strong><small><?php echo esc_html( $field[1] ); ?></small></span><?php Bolt_Edit_Admin_UI::toggle( 'focus_' . $key, $field[0], 'toolbar' === $key ? Bolt_Edit_Client::toolbar_on() : Bolt_Edit_Client::website_on() ); ?></div>
				<?php endforeach; ?>
				<?php Bolt_Edit_Admin_UI::save_form_close(); ?>
			<p class="bolt-edit-focus__note"><?php esc_html_e( 'Want to hide more menu items for them? You need a menu plugin for that.', 'bolt-edit' ); ?></p>
		</div></div>
				</div>

				<div data-subtab-panel="dev" hidden>
		<div class="bolt-edit-content"><div class="bolt-edit-card bolt-edit-card--full">
			<?php Bolt_Edit_Admin_UI::intro( __( 'See what the AI built', 'bolt-edit' ), __( 'Dev mode', 'bolt-edit' ) ); ?>
			<p class="bolt-edit-focus__lead"><?php esc_html_e( 'Dev mode adds a Dev button next to Edit on the website. Click an element to change its framework class and to check whether the CSS follows your framework. Dev mode works best when your framework classes start with a prefix. Agents that build with Bolt Edit add it for you.', 'bolt-edit' ); ?></p>

			<h3 class="bolt-edit-focus__title"><?php esc_html_e( 'Who gets Dev mode', 'bolt-edit' ); ?></h3>
			<p class="bolt-edit-focus__sub"><?php esc_html_e( 'Dev mode is for administrators only. Only the administrators below see the Dev button. Add yourself to try it.', 'bolt-edit' ); ?></p>

			<?php Bolt_Edit_Admin_UI::user_picker( 'bolt_edit_dev', Bolt_Edit_Dev::chosen_users(), __( 'Users with Dev mode', 'bolt-edit' ), array( 'data-action-search' => 'bolt_edit_dev_search', 'data-action-users' => 'bolt_edit_dev_users', 'data-one' => __( 'user has Dev mode', 'bolt-edit' ), 'data-many' => __( 'users have Dev mode', 'bolt-edit' ) ) ); ?>

			<p class="bolt-edit-focus__note"><?php esc_html_e( 'Dev mode reads the page in your browser. Nothing is sent or stored.', 'bolt-edit' ); ?></p>
		</div></div>
				</div>

				<div data-subtab-panel="hidden" hidden>
		<div class="bolt-edit-content"><div class="bolt-edit-card bolt-edit-card--full">
			<?php Bolt_Edit_Admin_UI::intro( __( 'Hidden statuses', 'bolt-edit' ), __( 'Show hidden status', 'bolt-edit' ) ); ?>
			<?php Bolt_Edit_Admin_UI::save_form_open( 'view' ); ?>
				<div class="bolt-edit-setting-row bolt-edit-setting-row--first bolt-edit-setting-row--text"><span><small><?php esc_html_e( 'By default, forms don\'t show the messages people see after they fill them out, like a thank-you or an error. Turn this on to see them too, and edit them with Bolt Edit.', 'bolt-edit' ); ?></small></span><?php Bolt_Edit_Admin_UI::toggle( 'show_hidden', __( 'Show hidden status', 'bolt-edit' ), Bolt_Edit_Plugin::show_hidden() ); ?></div>
				<div class="bolt-edit-compare">
					<div class="bolt-edit-compare__panel">
						<p class="bolt-edit-compare__title"><?php esc_html_e( 'Off', 'bolt-edit' ); ?></p>
						<div class="bolt-edit-shot bolt-edit-shot--site bolt-edit-shot--form" aria-hidden="true"><span class="bolt-edit-shot__formtitle"><?php esc_html_e( 'Contact us', 'bolt-edit' ); ?></span><span class="bolt-edit-shot__input"><?php esc_html_e( 'Your name', 'bolt-edit' ); ?></span><span class="bolt-edit-shot__input"><?php esc_html_e( 'Your email', 'bolt-edit' ); ?></span><span class="bolt-edit-shot__button"><?php esc_html_e( 'Send', 'bolt-edit' ); ?></span></div>
					</div>
					<span class="bolt-edit-compare__arrow" aria-hidden="true"><span class="dashicons dashicons-arrow-right-alt"></span></span>
					<div class="bolt-edit-compare__panel">
						<p class="bolt-edit-compare__title"><?php esc_html_e( 'On', 'bolt-edit' ); ?></p>
						<div class="bolt-edit-shot bolt-edit-shot--site bolt-edit-shot--form" aria-hidden="true"><span class="bolt-edit-shot__formtitle"><?php esc_html_e( 'Contact us', 'bolt-edit' ); ?></span><span class="bolt-edit-shot__input"><?php esc_html_e( 'Your name', 'bolt-edit' ); ?></span><span class="bolt-edit-shot__input"><?php esc_html_e( 'Your email', 'bolt-edit' ); ?></span><span class="bolt-edit-shot__button"><?php esc_html_e( 'Send', 'bolt-edit' ); ?></span><span class="bolt-edit-shot__ok"><?php esc_html_e( 'Thanks, your message is sent', 'bolt-edit' ); ?></span></div>
					</div>
				</div>
				<?php Bolt_Edit_Admin_UI::save_form_close(); ?>
		</div></div>
				</div>

				<div data-subtab-panel="updates" hidden>
					<?php Bolt_Edit_Updates::screen(); ?>
				</div>
			</div>
		</div>
		<?php
	}
}
