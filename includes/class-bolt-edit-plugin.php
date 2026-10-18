<?php
/**
 * Plugin bootstrap and editor interface.
 *
 * @package Bolt_Edit
 */

defined( 'ABSPATH' ) || exit;

final class Bolt_Edit_Plugin {
	/**
	 * Register plugin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_editor_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_editor' ), 5 );
	}

	/**
	 * Whether the current visitor may use Bolt Edit.
	 *
	 * Administrators and Editors by default. Contributors and Authors have
	 * `edit_posts` but not `edit_pages`, so they are left out.
	 *
	 * @return bool
	 */
	public static function can_edit() {
		$can_edit = is_user_logged_in() && ( current_user_can( 'edit_pages' ) || ( Bolt_Edit_Client::enabled() && current_user_can( 'bolt_edit_edit_content' ) ) );
		if ( Bolt_Edit_Client::is_client() && ! Bolt_Edit_Client::enabled() ) {
			$can_edit = false;
		}

		/**
		 * Filter who may open Bolt Edit and save content.
		 *
		 * @param bool $can_edit Whether the current user may edit.
		 */
		return (bool) apply_filters( 'bolt_edit_can_edit', $can_edit );
	}

	/**
	 * Register save endpoints.
	 *
	 * @return void
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'bolt-edit/v1',
			'/fields/(?P<id>[a-z0-9._-]+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'save_field' ),
				'permission_callback' => array( __CLASS__, 'can_edit' ),
				'args'                => array(
					'id'      => array(
						'required' => true,
					),
					'value'   => array(
						'required' => true,
					),
					'page_id' => array(
						'required'          => false,
						'sanitize_callback' => 'absint',
						'default'           => 0,
					),
				),
			)
		);
	}

	/**
	 * Save an editable field.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function save_field( WP_REST_Request $request ) {
		$id     = (string) $request->get_param( 'id' );
		$schema = Bolt_Edit_Fields::get_schema( $id );

		if ( ! $schema ) {
			return new WP_Error(
				'bolt_edit_unknown_field',
				__( 'This content field is no longer available.', 'bolt-edit' ),
				array( 'status' => 404 )
			);
		}

		$old_value = Bolt_Edit_Fields::get_current_value( $id );
		$value     = Bolt_Edit_Fields::save( $id, $request->get_param( 'value' ) );

		if ( is_wp_error( $value ) ) {
			return $value;
		}

		Bolt_Edit_Activity::record(
			$id,
			$schema,
			$old_value,
			$value,
			absint( $request->get_param( 'page_id' ) )
		);

		$response = array(
			'id'    => $id,
			'type'  => $schema['type'],
			'value' => $value,
		);

		if ( 'image' === $schema['type'] ) {
			$response['image'] = Bolt_Edit_Fields::get_image_data(
				$value,
				isset( $schema['size'] ) ? $schema['size'] : 'full'
			);
		}

		return rest_ensure_response(
			$response
		);
	}

	/**
	 * Load editor-only assets for authorised users.
	 *
	 * @return void
	 */
	public static function enqueue_editor_assets() {
		if ( ! self::can_edit() ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'bolt-edit-editor',
			BOLT_EDIT_PLUGIN_URL . 'assets/css/editor.css',
			array( 'dashicons' ),
			self::asset_version( 'assets/css/editor.css' )
		);

		wp_enqueue_script(
			'bolt-edit-editor',
			BOLT_EDIT_PLUGIN_URL . 'assets/js/editor.js',
			array(),
			self::asset_version( 'assets/js/editor.js' ),
			true
		);

		wp_localize_script(
			'bolt-edit-editor',
			'boltEditEditor',
			array(
				'restUrl' => esc_url_raw( rest_url( 'bolt-edit/v1/fields/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'pageId'  => get_queried_object_id(),
				'commentsUrl' => esc_url_raw( rest_url( 'bolt-edit/v1/comments' ) ),
				'clientMode' => Bolt_Edit_Client::clean_admin(),
				'labels'  => array(
					'openPencil'  => __( 'Open Bolt Edit', 'bolt-edit' ),
					'closePencil' => __( 'Close Bolt Edit', 'bolt-edit' ),
					'saved'       => __( 'Saved', 'bolt-edit' ),
					'error'       => __( 'Something went wrong. Please try again.', 'bolt-edit' ),
					'chooseImage' => __( 'Choose image', 'bolt-edit' ),
					'useImage'    => __( 'Use image', 'bolt-edit' ),
					'postComment' => __( 'Post comment', 'bolt-edit' ),
					'commentSaved' => __( 'Comment posted', 'bolt-edit' ),
					'writeComment' => __( 'Write a comment first.', 'bolt-edit' ),
					'saveChanges' => __( 'Save changes', 'bolt-edit' ),
					'section' => __( 'Page section', 'bolt-edit' ),
					'locationMissing' => __( 'The original element has changed. This comment is still saved with its page context.', 'bolt-edit' ),
					'selectText'  => __( 'Select the text you want to turn into a link first.', 'bolt-edit' ),
					'commentOpen' => __( 'Open', 'bolt-edit' ),
					'commentResolved' => __( 'Resolved', 'bolt-edit' ),
					'edit'        => __( 'Edit', 'bolt-edit' ),
				),
			)
		);
	}

	/**
	 * Render the minimal editor interface.
	 *
	 * @return void
	 */
	public static function render_editor() {
		if ( ! self::can_edit() ) {
			return;
		}
		?>
		<div class="bolt-edit-ui bolt-edit-toolbar" data-bolt-edit-toolbar hidden>
			<button class="bolt-edit-toolbar__select" type="button" data-bolt-edit-toggle aria-pressed="false">
				<span data-bolt-edit-toggle-label><?php esc_html_e( 'Open Bolt Edit', 'bolt-edit' ); ?></span>
				<span class="dashicons dashicons-edit" aria-hidden="true"></span>
			</button>
		</div>
		<?php if ( Bolt_Edit_Client::clean_admin() ) : ?>
			<a class="bolt-edit-ui bolt-edit-client-logout" href="<?php echo esc_url( wp_logout_url( Bolt_Edit_Client::logged_out_url() ) ); ?>"><?php esc_html_e( 'Log out', 'bolt-edit' ); ?></a>
		<?php endif; ?>

		<div class="bolt-edit-ui bolt-edit-status" data-bolt-edit-status role="status" aria-live="polite" hidden></div>

		<div class="bolt-edit-ui bolt-edit-highlight" data-bolt-edit-highlight hidden>
			<div class="bolt-edit-highlight__label">
				<span class="bolt-edit-highlight__text" data-bolt-edit-highlight-label></span>
				<a
					class="bolt-edit-highlight__action"
					data-bolt-edit-managed-edit
					href="#"

					hidden
				></a>
			</div>
		</div>

		<div
			class="bolt-edit-ui bolt-edit-popup"
			data-bolt-edit-popup
			role="dialog"
			aria-labelledby="bolt-edit-popup-title"
			hidden
		>
			<form data-bolt-edit-form>
				<div class="bolt-edit-popup__label" id="bolt-edit-popup-title" data-bolt-edit-popup-label></div>
				<div class="bolt-edit-popup__switch" role="tablist" aria-label="<?php esc_attr_e( 'Selected element action', 'bolt-edit' ); ?>">
					<button type="button" role="tab" aria-selected="true" data-bolt-edit-mode="edit"><?php esc_html_e( 'Edit content', 'bolt-edit' ); ?></button>
					<button type="button" role="tab" aria-selected="false" data-bolt-edit-mode="comment"><?php esc_html_e( 'Leave a comment', 'bolt-edit' ); ?></button>
				</div>
				<div class="bolt-edit-popup__comment" data-bolt-edit-comment-editor hidden>
					<label class="bolt-edit-popup__field"><span class="bolt-edit-popup__field-label"><?php esc_html_e( 'Comment', 'bolt-edit' ); ?></span><textarea class="bolt-edit-popup__input" rows="4" maxlength="5000" data-bolt-edit-comment-input></textarea></label>
					<p class="bolt-edit-popup__hint"><?php esc_html_e( 'Private feedback for your site team. This does not change the page.', 'bolt-edit' ); ?></p>
				</div>
				<div class="bolt-edit-popup__managed" data-bolt-edit-managed-editor hidden>
					<p class="bolt-edit-popup__hint" data-bolt-edit-managed-description></p>
					<a class="bolt-edit-popup__button bolt-edit-popup__button--primary" data-bolt-edit-popup-managed-link href="#"><?php esc_html_e( 'Open editor', 'bolt-edit' ); ?></a>
				</div>
				<div class="bolt-edit-popup__comment" data-bolt-edit-comment-detail hidden><p data-bolt-edit-comment-author class="bolt-edit-popup__hint"></p><p data-bolt-edit-comment-body class="bolt-edit-popup__note"></p></div>
				<div data-bolt-edit-text-editor>
					<textarea class="bolt-edit-popup__input" id="bolt-edit-popup-value" name="value" rows="1" wrap="soft" autocomplete="off" aria-labelledby="bolt-edit-popup-title" data-bolt-edit-input></textarea>
				</div>
				<div class="bolt-edit-popup__richtext-editor" data-bolt-edit-richtext-editor hidden>
					<div class="bolt-edit-popup__toolbar" role="toolbar" aria-label="<?php esc_attr_e( 'Formatting', 'bolt-edit' ); ?>">
						<?php
						$tools = array(
							'bold'                => array( 'editor-bold', __( 'Bold', 'bolt-edit' ) ),
							'italic'              => array( 'editor-italic', __( 'Italic', 'bolt-edit' ) ),
							'link'                => array( 'admin-links', __( 'Link', 'bolt-edit' ) ),
							'insertUnorderedList' => array( 'editor-ul', __( 'Bulleted list', 'bolt-edit' ) ),
							'insertOrderedList'   => array( 'editor-ol', __( 'Numbered list', 'bolt-edit' ) ),
						);

						foreach ( $tools as $command => $tool ) :
							?>
							<button class="bolt-edit-popup__tool" type="button" data-bolt-edit-command="<?php echo esc_attr( $command ); ?>" aria-label="<?php echo esc_attr( $tool[1] ); ?>" title="<?php echo esc_attr( $tool[1] ); ?>" aria-pressed="false">
								<span class="dashicons dashicons-<?php echo esc_attr( $tool[0] ); ?>" aria-hidden="true"></span>
							</button>
						<?php endforeach; ?>
					</div>
					<div class="bolt-edit-popup__richtext" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="bolt-edit-popup-title" data-bolt-edit-richtext-input></div>
					<div class="bolt-edit-popup__link" data-bolt-edit-link-row hidden>
						<input class="bolt-edit-popup__input" type="text" autocomplete="off" inputmode="url" placeholder="<?php esc_attr_e( 'https://example.com or /contact', 'bolt-edit' ); ?>" aria-label="<?php esc_attr_e( 'Link address', 'bolt-edit' ); ?>" data-bolt-edit-link-input>
						<button class="bolt-edit-popup__button bolt-edit-popup__button--quiet" type="button" data-bolt-edit-link-apply>
							<?php esc_html_e( 'Apply', 'bolt-edit' ); ?>
						</button>
					</div>
				</div>
				<div class="bolt-edit-popup__button-editor" data-bolt-edit-button-editor hidden>
					<label class="bolt-edit-popup__field">
						<span class="bolt-edit-popup__field-label"><?php esc_html_e( 'Text', 'bolt-edit' ); ?></span>
						<input class="bolt-edit-popup__input" type="text" autocomplete="off" data-bolt-edit-button-text>
					</label>
					<label class="bolt-edit-popup__field">
						<span class="bolt-edit-popup__field-label"><?php esc_html_e( 'Link', 'bolt-edit' ); ?></span>
						<input class="bolt-edit-popup__input" type="text" autocomplete="off" inputmode="url" placeholder="<?php esc_attr_e( 'https://example.com or /contact', 'bolt-edit' ); ?>" data-bolt-edit-button-url>
					</label>
				</div>
				<div class="bolt-edit-popup__image-editor" data-bolt-edit-image-editor hidden>
					<div class="bolt-edit-popup__image-preview">
						<img src="" alt="" data-bolt-edit-image-preview>
					</div>
					<button class="bolt-edit-popup__replace" type="button" data-bolt-edit-replace-image>
						<?php esc_html_e( 'Replace image', 'bolt-edit' ); ?>
					</button>
				</div>
				<p class="bolt-edit-popup__error" data-bolt-edit-error hidden></p>
				<div class="bolt-edit-popup__actions">
					<button class="bolt-edit-popup__button bolt-edit-popup__button--quiet" type="button" data-bolt-edit-cancel>
						<?php esc_html_e( 'Close', 'bolt-edit' ); ?>
					</button>
					<button class="bolt-edit-popup__button bolt-edit-popup__button--primary" type="submit" data-bolt-edit-save>
						<?php esc_html_e( 'Save changes', 'bolt-edit' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Return a cache-busting asset version.
	 *
	 * @param string $relative_path Plugin-relative path.
	 * @return string
	 */
	private static function asset_version( $relative_path ) {
		$path = BOLT_EDIT_PLUGIN_PATH . $relative_path;

		return file_exists( $path ) ? (string) filemtime( $path ) : BOLT_EDIT_VERSION;
	}
}
