<?php
/**
 * Bolt Edit admin: Change log tab: the list of edits, and the comments inbox.
 *
 * @package Bolt_Edit
 */

defined( 'ABSPATH' ) || exit;

final class Bolt_Edit_Admin_Changelog {
	/**
	 * Change log tab: the content history and the client comments as two sub-tabs.
	 *
	 * @return void
	 */
	public static function render() {
		?>
		<div class="bolt-edit-content bolt-edit-content--onboarding">
			<div class="bolt-edit-card bolt-edit-card--full bolt-edit-changes">
				<div data-subtabs>
					<div class="bolt-edit-subtabs" role="tablist">
						<button type="button" class="bolt-edit-subtabs__btn bolt-edit-subtabs__btn--active" data-subtab="changes" data-subtab-hash="changes" role="tab" aria-selected="true"><?php esc_html_e( 'Changes', 'bolt-edit' ); ?></button>
						<button type="button" class="bolt-edit-subtabs__btn" data-subtab="comments" data-subtab-hash="comments" role="tab" aria-selected="false"><?php esc_html_e( 'Comments', 'bolt-edit' ); ?></button>
					</div>

					<div data-subtab-panel="changes"><?php self::render_changes(); ?></div>
					<div data-subtab-panel="comments" hidden><?php self::render_comments(); ?></div>
				</div>
			</div>
		</div>
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
		<?php
	}

	private static function render_comments() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only inbox filtering.
		$resolved = isset( $_GET['bolt-edit-comment-status'] ) && 'resolved' === $_GET['bolt-edit-comment-status'];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination.
		$page = isset( $_GET['bolt-edit-comment-page'] ) ? max( 1, absint( $_GET['bolt-edit-comment-page'] ) ) : 1;
		$rows = Bolt_Edit_Comments::inbox( $resolved, $page );
		$more = count( $rows ) > 50;
		$rows = array_slice( $rows, 0, 50 );
		$base = add_query_arg( 'page', Bolt_Edit_Admin::MENU_SLUG, admin_url( 'admin.php' ) );
		$status = $resolved ? 'resolved' : 'open';
		?>
				<div class="bolt-edit-page-intro">
					<span class="bolt-edit-eyebrow"><?php esc_html_e( 'Client feedback', 'bolt-edit' ); ?></span>
					<h2><?php esc_html_e( 'Comments', 'bolt-edit' ); ?></h2>
				</div>

				<nav class="bolt-edit-admin__filters" aria-label="<?php esc_attr_e( 'Comment status', 'bolt-edit' ); ?>">
					<a class="bolt-edit-admin__edit<?php echo ! $resolved ? ' bolt-edit-admin__edit--active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'bolt-edit-comment-status', 'open', $base ) . '#comments' ); ?>"<?php echo ! $resolved ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Open', 'bolt-edit' ); ?></a>
					<a class="bolt-edit-admin__edit<?php echo $resolved ? ' bolt-edit-admin__edit--active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'bolt-edit-comment-status', 'resolved', $base ) . '#comments' ); ?>"<?php echo $resolved ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Resolved', 'bolt-edit' ); ?></a>
				</nav>

				<?php if ( ! $rows ) : ?>
					<div class="bolt-edit-admin__empty">
						<span class="dashicons dashicons-format-chat" aria-hidden="true"></span>
						<h3><?php echo esc_html( $resolved ? __( 'No resolved comments yet', 'bolt-edit' ) : __( 'No open comments', 'bolt-edit' ) ); ?></h3>
						<p><?php echo esc_html( $resolved ? __( 'Comments you resolve move here.', 'bolt-edit' ) : __( 'Private notes left on the website appear here.', 'bolt-edit' ) ); ?></p>
					</div>
				<?php else : ?>
					<ul class="bolt-edit-admin__list">
						<?php
						foreach ( $rows as $row ) :
							$target     = json_decode( $row->target, true );
							$timestamp  = strtotime( $row->created_at . ' UTC' );
							$when       = $timestamp ? wp_date( 'j M Y, H:i', $timestamp ) : $row->created_at;
							$user_name  = $row->user_name ? $row->user_name : __( 'Unknown user', 'bolt-edit' );
							$avatar     = isset( $row->user_id ) ? get_avatar( absint( $row->user_id ), 20, '', '', array( 'class' => 'bolt-edit-admin__avatar' ) ) : '';
							$who_markup = sprintf( '<span class="bolt-edit-admin__user">%s<span>%s</span></span>', $avatar, esc_html( $user_name ) );
							if ( $row->page_title && $row->page_url ) {
								$where_markup = sprintf( '<a href="%s">%s</a>', esc_url( $row->page_url ), esc_html( $row->page_title ) );
							} else {
								$where_markup = sprintf( '<span class="bolt-edit-admin__muted">%s</span>', esc_html__( 'the website', 'bolt-edit' ) );
							}
							$when_markup = sprintf( '<span class="bolt-edit-admin__when">%s</span>', esc_html( $when ) );
							?>
							<li class="bolt-edit-admin__record">
								<div class="bolt-edit-admin__record-head">
									<strong class="bolt-edit-admin__field"><?php echo esc_html( $target['label'] ?? __( 'Page element', 'bolt-edit' ) ); ?></strong>
									<div class="bolt-edit-admin__actions">
										<a class="bolt-edit-admin__edit" href="<?php echo esc_url( add_query_arg( array( 'bolt-edit-edit' => '1', 'bolt-edit-comment' => $row->comment_id ), $row->page_url ) ); ?>"><?php esc_html_e( 'View', 'bolt-edit' ); ?></a>
										<?php if ( Bolt_Edit_Client::can_manage() ) : ?>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="bolt_edit_resolve_comment"><input type="hidden" name="comment_id" value="<?php echo absint( $row->comment_id ); ?>"><input type="hidden" name="resolved" value="<?php echo $resolved ? '0' : '1'; ?>"><?php wp_nonce_field( 'bolt_edit_resolve_comment_' . $row->comment_id ); ?><button class="bolt-edit-admin__edit" type="submit"><?php echo esc_html( $resolved ? __( 'Reopen', 'bolt-edit' ) : __( 'Resolve', 'bolt-edit' ) ); ?></button></form>
										<?php endif; ?>
									</div>
								</div>
								<p class="bolt-edit-admin__meta-line">
									<?php
									echo wp_kses_post(
										sprintf(
											/* translators: 1: user name with avatar, 2: page or location, 3: date and time. */
											__( '%1$s commented on %2$s on %3$s', 'bolt-edit' ),
											$who_markup,
											$where_markup,
											$when_markup
										)
									);
									?>
								</p>
								<div class="bolt-edit-admin__value-text bolt-edit-admin__value-text--comment"><?php echo esc_html( $row->comment_text ); ?></div>
								<?php if ( ! empty( $row->edited_at ) ) : ?><p class="bolt-edit-admin__meta-line"><?php esc_html_e( 'Edited', 'bolt-edit' ); ?></p><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $page > 1 || $more ) : ?>
					<nav class="bolt-edit-admin__filters bolt-edit-admin__filters--pager" aria-label="<?php esc_attr_e( 'Comment pages', 'bolt-edit' ); ?>">
						<?php if ( $page > 1 ) : ?><a class="bolt-edit-admin__edit" href="<?php echo esc_url( add_query_arg( array( 'bolt-edit-comment-status' => $status, 'bolt-edit-comment-page' => $page - 1 ), $base ) . '#comments' ); ?>"><?php esc_html_e( 'Previous', 'bolt-edit' ); ?></a><?php endif; ?>
						<?php if ( $more ) : ?><a class="bolt-edit-admin__edit" href="<?php echo esc_url( add_query_arg( array( 'bolt-edit-comment-status' => $status, 'bolt-edit-comment-page' => $page + 1 ), $base ) . '#comments' ); ?>"><?php esc_html_e( 'Next', 'bolt-edit' ); ?></a><?php endif; ?>
					</nav>
				<?php endif; ?>
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
			<strong class="bolt-edit-admin__field" title="<?php echo esc_attr( $activity->field_id ); ?>"><?php echo esc_html( $activity->field_label ); ?></strong>
			<a class="bolt-edit-admin__edit" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'bolt-edit' ); ?></a>
		</div>
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
