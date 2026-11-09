<?php
/**
 * AI instructions tab: the agent instructions in sections, with room for the site owner's own additions.
 *
 * The default text always comes from docs/agent-instructions.md, so plugin updates reach everyone.
 * What the site owner adds is stored apart from it and placed after the default of its section.
 *
 * @package Bolt_Edit
 */
defined( 'ABSPATH' ) || exit;

final class Bolt_Edit_AI_Instructions {

	const OPTION     = 'bolt_edit_ai_additions';
	const MAX_LENGTH = 8000;

	public static function init() {
		add_action( 'admin_post_bolt_edit_save_ai_instructions', array( __CLASS__, 'save' ) );
	}

	/**
	 * The sections of the tab. Each one lists the headings of the instructions file it covers,
	 * in file order, so the sections together make up the whole file.
	 *
	 * @return array
	 */
	private static function groups() {
		return array(
			'front-end'   => array(
				'title'       => __( 'Front-end editing', 'bolt-edit' ),
				'problem'     => __( 'AI often hard-codes the text and images in the templates, so a client cannot change anything without a developer.', 'bolt-edit' ),
				'lead'        => __( 'The agent starts from your idea or from your own HTML template, builds the theme, and hands all static content to Bolt Edit. You and your clients then edit text, buttons and images right on the website. The site keeps working, and shows its content, even when the plugin is switched off.', 'bolt-edit' ),
				'placeholder' => __( 'Example: Make every heading, text and button editable, also in the footer.', 'bolt-edit' ),
				'headings'    => array( 'Role', 'Choose the starting path', 'Bolt Edit theme compatibility contract', 'Bolt Edit v2 client workflow', 'Hidden states for editors', 'Required safe theme integration', 'Content continuity', 'Non-negotiable rules', 'Decide who owns content before writing code', 'Bolt Edit-owned content' ),
			),
			'templates'   => array(
				'title'       => __( 'Templates', 'bolt-edit' ),
				'problem'     => __( 'Agents often paste the header and footer into every page, or forget the 404, search and blog templates. Those then fall back to the plain WordPress look.', 'bolt-edit' ),
				'lead'        => __( 'One shared header and footer for the whole site, repeated sections in one file, and a 404, search and blog template in your design.', 'bolt-edit' ),
				'placeholder' => __( 'Example: Always add a simple landing page template without a menu.', 'bolt-edit' ),
				'headings'    => array( 'Shared parts: header, footer and repeated sections', 'Templates beyond the pages' ),
			),
			'structure'   => array(
				'title'       => __( 'Pages and site basics', 'bolt-edit' ),
				'problem'     => __( 'A page that only exists as a template does not show up under Pages in WordPress, so you cannot manage it or link to it.', 'bolt-edit' ),
				'lead'        => __( 'The agent creates a real WordPress page for every page, sets your homepage as the front page, and builds a menu you can edit and reorder. The theme also gets a proper name and screenshot, and a favicon or share image only when you provide one.', 'bolt-edit' ),
				'placeholder' => __( 'Example: Always add a Contact page and a Privacy page.', 'bolt-edit' ),
				'headings'    => array( 'Pages, URLs, and templates', 'Navigation', 'Theme identity and presentation', 'Site basics' ),
			),
			'content'     => array(
				'title'       => __( 'Content and images', 'bolt-edit' ),
				'problem'     => __( 'Hard-coded text and images can only be changed by a developer, and some content, like products, already belongs to another plugin.', 'bolt-edit' ),
				'lead'        => __( 'Every text and image on the page becomes editable on the front end. Images go into the Media Library in a fixed frame, so replacing a photo never breaks the layout. Content from plugins like ACF or WooCommerce stays where it is. Images come from your own files, free Pexels and Unsplash photos, or the image model of your AI.', 'bolt-edit' ),
				'placeholder' => __( 'Example: Keep button labels under 30 characters.', 'bolt-edit' ),
				'headings'    => array( 'Avoid conflicts with WordPress UI on the frontend', 'Required image workflow', 'Managed content from other providers', 'Content in the block editor (Gutenberg)' ),
			),
			'design'      => array(
				'title'       => __( 'Design system', 'bolt-edit' ),
				'problem'     => __( 'AI-written CSS gets messy fast: values everywhere, classes that fight each other, and a design that is hard to change later.', 'bolt-edit' ),
				'lead'        => __( 'The agent uses your CSS framework if you bring one. If not, Bolt Edit gives it a default framework to build with, so the code stays clean. It also adds a private brand guide page.', 'bolt-edit' ),
				'placeholder' => __( 'Example: Use at most two font families.', 'bolt-edit' ),
				'headings'    => array( 'Design system rules', 'Default design basics', 'Brand guide page' ),
			),
			'mobile'      => array(
				'title'       => __( 'Mobile', 'bolt-edit' ),
				'problem'     => __( 'AI-built sections often look fine on a desktop and break on a phone.', 'bolt-edit' ),
				'lead'        => __( 'Every section works from 360 px wide, and the mobile menu works with touch and keyboard. The focus outline stays visible and animations respect visitors who want less motion.', 'bolt-edit' ),
				'placeholder' => __( 'Example: Use the breakpoints 768 px and 1200 px.', 'bolt-edit' ),
				'headings'    => array( 'Mobile and accessibility' ),
			),
			'speed'       => array(
				'title'       => __( 'Speed', 'bolt-edit' ),
				'problem'     => __( 'Fonts from outside servers and heavy images slow a site down, and fonts from outside servers are a GDPR risk.', 'bolt-edit' ),
				'lead'        => __( 'Fonts load from your own server, styles and scripts load the WordPress way, and images are resized, converted to WebP and compressed before they are uploaded.', 'bolt-edit' ),
				'placeholder' => __( 'Example: Only load a script on the pages that use it.', 'bolt-edit' ),
				'headings'    => array( 'Speed' ),
			),
			'safety'      => array(
				'title'       => __( 'Safety', 'bolt-edit' ),
				'problem'     => __( 'Text from the database or from a visitor can hide harmful code if a theme prints it as it is.', 'bolt-edit' ),
				'lead'        => __( 'The agent is told to clean everything it prints and to check that only the right people can save things. It is an instruction, so it helps, but it is not a guarantee.', 'bolt-edit' ),
				'note'        => __( 'These are only a few basic checks on the code the agent writes. They do not replace a security plugin, so it is recommended to use one as well.', 'bolt-edit' ),
				'placeholder' => __( 'Example: Never put passwords or API keys in theme files.', 'bolt-edit' ),
				'headings'    => array( 'Safety' ),
			),
			'seo'         => array(
				'title'       => __( 'SEO', 'bolt-edit' ),
				'problem'     => __( 'Bolt Edit pages keep their content in the theme, so SEO plugins like Yoast and Rank Math see an empty page. Google does not mind, but those plugins do.', 'bolt-edit' ),
				'lead'        => __( 'The agent first checks whether your site already has an SEO plugin, then follows one of two paths. Either way, every page gets one main heading and every image gets alt text.', 'bolt-edit' ),
				'paths'       => array(
					'A' => array(
						'title' => __( 'You have an SEO plugin', 'bolt-edit' ),
						'text'  => __( 'The plugin owns your titles, descriptions and sitemap. The agent only gives it the text of your pages, so its content checks work, and it prints no SEO tags itself.', 'bolt-edit' ),
					),
					'B' => array(
						'title' => __( 'You have no SEO plugin', 'bolt-edit' ),
						'text'  => __( 'Bolt Edit adds an SEO box to every page and post with a title and a meta description. The agent fills it in from the real page content.', 'bolt-edit' ),
					),
				),
				'placeholder' => __( 'Example: Write SEO titles in Dutch and keep them under 55 characters.', 'bolt-edit' ),
				'headings'    => array( 'SEO' ),
			),
			'checks'      => array(
				'title'       => __( 'Final checks', 'bolt-edit' ),
				'problem'     => __( 'Without a check, a theme can look finished and still miss pages, have the wrong images or broken edit buttons.', 'bolt-edit' ),
				'lead'        => __( 'The agent runs a checklist before it calls a theme done, and follows a list of things it must never do.', 'bolt-edit' ),
				'placeholder' => __( 'Example: Also check the site at 320 px wide.', 'bolt-edit' ),
				'headings'    => array( 'Required checks before completing a theme change', 'Never do these things' ),
			),
		);
	}

	/**
	 * The instructions file cut into the sections above, each with its default text and a hash of it.
	 * A heading the sections do not know stays with the section before it, so no text gets lost.
	 *
	 * @return array
	 */
	public static function sections() {
		static $sections = null;
		if ( null !== $sections ) {
			return $sections;
		}

		$sections = array();
		$groups   = self::groups();
		$file     = BOLT_EDIT_PLUGIN_PATH . 'docs/agent-instructions.md';
		$text     = file_exists( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		// The notice at the top of the file is for agents that read the raw file. It is not part of any section.
		$text     = (string) preg_replace( '/<!-- bolt-edit:file-notice -->.*?<!-- \/bolt-edit:file-notice -->\s*/s', '', $text );
		if ( '' === trim( $text ) ) {
			return $sections;
		}

		$map = array();
		foreach ( $groups as $slug => $group ) {
			foreach ( $group['headings'] as $heading ) {
				$map[ $heading ] = $slug;
			}
		}

		$lines   = array_fill_keys( array_keys( $groups ), array() );
		$current = key( $groups );
		$in_code = false;
		foreach ( explode( chr( 10 ), $text ) as $line ) {
			if ( 0 === strpos( ltrim( $line ), '```' ) ) {
				$in_code = ! $in_code;
			}
			if ( ! $in_code && 0 === strpos( $line, '## ' ) ) {
				$heading = trim( substr( $line, 3 ) );
				if ( isset( $map[ $heading ] ) ) {
					$current = $map[ $heading ];
				}
			}
			$lines[ $current ][] = $line;
		}

		foreach ( $groups as $slug => $group ) {
			$default = implode( chr( 10 ), $lines[ $slug ] );
			if ( '' === trim( $default ) ) {
				continue;
			}
			$group['default']  = $default;
			$group['hash']     = md5( trim( $default ) );
			$sections[ $slug ] = $group;
		}

		return $sections;
	}

	/** All saved additions: section => array( text, seen, time ). */
	private static function records() {
		$records = get_option( self::OPTION, array() );
		if ( ! is_array( $records ) ) {
			return array();
		}
		// The Basics and Theme rules tabs became Front-end editing. Additions saved there move along.
		$moved = false;
		foreach ( array( 'basics', 'theme-rules' ) as $old ) {
			if ( ! isset( $records[ $old ] ) ) {
				continue;
			}
			$text = isset( $records[ $old ]['text'] ) ? trim( (string) $records[ $old ]['text'] ) : '';
			if ( '' !== $text ) {
				$have = isset( $records['front-end']['text'] ) ? trim( (string) $records['front-end']['text'] ) : '';
				$records['front-end'] = array(
					'text' => '' === $have ? $text : $have . chr( 10 ) . chr( 10 ) . $text,
					'seen' => isset( $records['front-end']['seen'] ) ? $records['front-end']['seen'] : ( isset( $records[ $old ]['seen'] ) ? $records[ $old ]['seen'] : '' ),
					'time' => isset( $records[ $old ]['time'] ) ? $records[ $old ]['time'] : time(),
				);
			}
			unset( $records[ $old ] );
			$moved = true;
		}
		if ( $moved ) {
			update_option( self::OPTION, $records, false );
		}
		return $records;
	}

	/** The site owner's additions to one section. */
	public static function additions( $slug ) {
		$records = self::records();
		return isset( $records[ $slug ]['text'] ) ? (string) $records[ $slug ]['text'] : '';
	}

	/** Whether the default of a section changed since the site owner last looked, while they have additions there. */
	private static function default_changed( $slug ) {
		$sections = self::sections();
		$records  = self::records();
		if ( ! isset( $sections[ $slug ], $records[ $slug ] ) || empty( $records[ $slug ]['text'] ) ) {
			return false;
		}
		return isset( $records[ $slug ]['seen'] ) && $records[ $slug ]['seen'] !== $sections[ $slug ]['hash'];
	}

	/**
	 * The instructions of one section as an agent should read them: the default, followed by the additions of the site owner.
	 *
	 * @param string $slug Section slug.
	 * @return string
	 */
	public static function section_text( $slug ) {
		$sections = self::sections();
		if ( ! isset( $sections[ $slug ] ) ) {
			return '';
		}
		$chunk = $sections[ $slug ]['default'];
		$extra = trim( self::additions( $slug ) );
		if ( '' !== $extra ) {
			$chunk = rtrim( $chunk ) . chr( 10 ) . chr( 10 ) . '## Site owner additions: ' . $sections[ $slug ]['title'] . chr( 10 ) . chr( 10 ) . 'The site owner wrote the following for this part. If it conflicts with the instructions above, follow it.' . chr( 10 ) . chr( 10 ) . $extra . chr( 10 );
		}
		return $chunk;
	}

	/**
	 * The complete instructions as an agent should read them: every default, each followed by the additions of its section.
	 * Without additions this is the instructions file, unchanged.
	 *
	 * @return string
	 */
	public static function full_text() {
		$chunks = array();
		foreach ( array_keys( self::sections() ) as $slug ) {
			$chunks[] = self::section_text( $slug );
		}
		return implode( chr( 10 ), $chunks );
	}

	/**
	 * The sections in file order: slug, title and whether the site owner added something.
	 *
	 * @return array
	 */
	public static function overview() {
		$list = array();
		foreach ( self::sections() as $slug => $section ) {
			$list[] = array(
				'slug'          => $slug,
				'title'         => $section['title'],
				'has_additions' => '' !== trim( self::additions( $slug ) ),
			);
		}
		return $list;
	}

	/**
	 * Save, clear or acknowledge the additions of one section.
	 *
	 * @return void
	 */
	public static function save() {
		if ( ! Bolt_Edit_Client::can_manage() ) {
			wp_die( esc_html__( 'You cannot change these settings.', 'bolt-edit' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'bolt_edit_save_ai_instructions' );

		$slug     = isset( $_POST['bolt_edit_ai_section'] ) ? sanitize_key( wp_unslash( $_POST['bolt_edit_ai_section'] ) ) : '';
		$op       = isset( $_POST['bolt_edit_ai_op'] ) ? sanitize_key( wp_unslash( $_POST['bolt_edit_ai_op'] ) ) : 'save';
		$sections = self::sections();

		if ( isset( $sections[ $slug ] ) ) {
			$records = self::records();
			$current = isset( $records[ $slug ] ) && is_array( $records[ $slug ] ) ? $records[ $slug ] : array();
			$had     = ! empty( $current['text'] );

			if ( 'clear' === $op ) {
				unset( $records[ $slug ] );
			} elseif ( 'seen' === $op ) {
				if ( $had ) {
					$records[ $slug ]['seen'] = $sections[ $slug ]['hash'];
				}
			} else {
				// Plain text, kept as typed (an instruction may mention tags like main or h1). It is only ever printed escaped.
				$text = isset( $_POST['bolt_edit_ai_text'] ) ? wp_check_invalid_utf8( wp_unslash( $_POST['bolt_edit_ai_text'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$text = trim( str_replace( array( chr( 13 ) . chr( 10 ), chr( 13 ) ), chr( 10 ), (string) $text ) );
				$text = mb_substr( $text, 0, self::MAX_LENGTH );

				if ( '' === $text ) {
					unset( $records[ $slug ] );
				} else {
					$records[ $slug ] = array(
						'text' => $text,
						'seen' => $had && isset( $current['seen'] ) ? $current['seen'] : $sections[ $slug ]['hash'],
						'time' => time(),
					);
				}
			}

			if ( $records ) {
				update_option( self::OPTION, $records, false );
			} else {
				delete_option( self::OPTION );
			}
		}

		wp_safe_redirect( add_query_arg( array( 'page' => Bolt_Edit_Admin::MENU_SLUG, 'bolt-edit-settings-saved' => '1' ), admin_url( 'admin.php' ) ) . '#ai-' . $slug );
		exit;
	}

	/** The hidden fields every form of this tab needs. */
	private static function fields( $slug, $op ) {
		printf(
			'<input type="hidden" name="action" value="bolt_edit_save_ai_instructions"><input type="hidden" name="bolt_edit_ai_section" value="%1$s"><input type="hidden" name="_wpnonce" value="%2$s">',
			esc_attr( $slug ),
			esc_attr( wp_create_nonce( 'bolt_edit_save_ai_instructions' ) )
		);
		if ( '' !== $op ) {
			printf( '<input type="hidden" name="bolt_edit_ai_op" value="%s">', esc_attr( $op ) );
		}
	}

	/**
	 * The tab: a sidebar with one item per section, and for each section an explanation,
	 * the read-only default and a field for the site owner's own additions.
	 *
	 * @return void
	 */
	public static function render() {
		$sections = self::sections();

		if ( ! $sections ) {
			?>
			<div class="bolt-edit-content"><div class="bolt-edit-card bolt-edit-card--full">
				<p><?php esc_html_e( 'The instructions file could not be found. Reinstall Bolt Edit to restore it.', 'bolt-edit' ); ?></p>
			</div></div>
			<?php
			return;
		}

		$records = self::records();
		$first   = key( $sections );
		$action  = admin_url( 'admin-post.php' );
		?>
		<div class="bolt-edit-shell" data-subtabs>
			<nav class="bolt-edit-side" aria-label="<?php esc_attr_e( 'AI instruction sections', 'bolt-edit' ); ?>">
				<?php foreach ( $sections as $slug => $section ) : ?>
					<?php
					$updated = self::default_changed( $slug );
					$dot     = 'bolt-edit-side__dot' . ( ( $updated || '' !== self::additions( $slug ) ) ? ' show' : '' ) . ( $updated ? ' bolt-edit-side__dot--update' : '' );
					?>
					<button type="button" class="bolt-edit-side__item<?php echo $slug === $first ? ' bolt-edit-subtabs__btn--active' : ''; ?>" data-subtab="<?php echo esc_attr( $slug ); ?>" data-subtab-hash="ai-<?php echo esc_attr( $slug ); ?>" aria-selected="<?php echo $slug === $first ? 'true' : 'false'; ?>"><span><?php echo esc_html( $section['title'] ); ?></span><i class="<?php echo esc_attr( $dot ); ?>"></i></button>
				<?php endforeach; ?>
				<button type="button" class="bolt-edit-side__item" data-subtab="cleanup" data-subtab-hash="ai-cleanup" aria-selected="false"><span><?php esc_html_e( 'Clean-up', 'bolt-edit' ); ?></span><i class="bolt-edit-side__dot"></i></button>
			</nav>

			<div class="bolt-edit-main">
				<?php foreach ( $sections as $slug => $section ) : ?>
					<?php
					$text    = self::additions( $slug );
					$updated = self::default_changed( $slug );
					$time    = isset( $records[ $slug ]['time'] ) ? (int) $records[ $slug ]['time'] : 0;
					?>
					<div data-subtab-panel="<?php echo esc_attr( $slug ); ?>"<?php echo $slug === $first ? '' : ' hidden'; ?>>
						<div class="bolt-edit-content"><div class="bolt-edit-card bolt-edit-card--full">
							<div class="bolt-edit-ai__head">
								<?php Bolt_Edit_Admin_UI::intro( __( 'AI instructions', 'bolt-edit' ), $section['title'] ); ?>
							</div>

							<div class="bolt-edit-ai__what">
								<p><strong><?php esc_html_e( 'The problem:', 'bolt-edit' ); ?></strong> <?php echo esc_html( $section['problem'] ); ?></p>
								<p><strong><?php esc_html_e( 'The solution:', 'bolt-edit' ); ?></strong> <?php echo esc_html( $section['lead'] ); ?></p>
								<?php if ( ! empty( $section['note'] ) ) : ?>
									<p><strong><?php esc_html_e( 'Good to know:', 'bolt-edit' ); ?></strong> <?php echo esc_html( $section['note'] ); ?></p>
								<?php endif; ?>
								<?php if ( ! empty( $section['paths'] ) ) : ?>
									<div class="bolt-edit-ai__paths">
										<?php foreach ( $section['paths'] as $letter => $path ) : ?>
											<div class="bolt-edit-ai__path"><strong><span class="bolt-edit-ai__tag"><?php echo esc_html( $letter ); ?></span><?php echo esc_html( $path['title'] ); ?></strong><p><?php echo esc_html( $path['text'] ); ?></p></div>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>

							<?php if ( $updated ) : ?>
								<form class="bolt-edit-ai__notice" action="<?php echo esc_url( $action ); ?>" method="post">
									<?php self::fields( $slug, 'seen' ); ?>
									<p><strong><?php esc_html_e( 'The default for this section changed.', 'bolt-edit' ); ?></strong> <?php esc_html_e( 'Your additions are untouched. Open the default below to see what is new.', 'bolt-edit' ); ?></p>
									<button type="submit" class="bolt-edit-button bolt-edit-button--quiet"><?php esc_html_e( 'Got it', 'bolt-edit' ); ?></button>
								</form>
							<?php endif; ?>

							<details class="bolt-edit-ai__default">
								<summary><strong><?php esc_html_e( 'Default instructions', 'bolt-edit' ); ?></strong><span><?php esc_html_e( 'Read only. Updated with the plugin.', 'bolt-edit' ); ?></span></summary>
								<textarea class="bolt-edit-instructions__text" readonly spellcheck="false" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: section name */ __( 'Default instructions: %s', 'bolt-edit' ), $section['title'] ) ); ?>"><?php echo esc_textarea( rtrim( $section['default'] ) ); ?></textarea>
							</details>


							<form class="bolt-edit-ai__form" action="<?php echo esc_url( $action ); ?>" method="post">
								<?php self::fields( $slug, '' ); ?>
								<label for="bolt-edit-ai-text-<?php echo esc_attr( $slug ); ?>"><?php esc_html_e( 'Your additions', 'bolt-edit' ); ?></label>
								<p class="bolt-edit-focus__sub"><?php esc_html_e( 'Added after the default. If your text conflicts with the default, yours wins.', 'bolt-edit' ); ?></p>
								<textarea id="bolt-edit-ai-text-<?php echo esc_attr( $slug ); ?>" name="bolt_edit_ai_text" rows="6" maxlength="<?php echo esc_attr( self::MAX_LENGTH ); ?>" spellcheck="false" placeholder="<?php echo esc_attr( $section['placeholder'] ); ?>"><?php echo esc_textarea( $text ); ?></textarea>
								<div class="bolt-edit-ai__actions">
									<span class="bolt-edit-ai__meta"><?php echo $time ? esc_html( sprintf( /* translators: %s: time since the last save, such as 2 days */ __( 'Last saved %s ago', 'bolt-edit' ), human_time_diff( $time ) ) ) : esc_html__( 'Nothing added yet', 'bolt-edit' ); ?></span>
									<span>
										<?php if ( '' !== $text ) : ?>
											<button type="submit" name="bolt_edit_ai_op" value="clear" class="bolt-edit-button bolt-edit-button--quiet"><?php esc_html_e( 'Clear additions', 'bolt-edit' ); ?></button>
										<?php endif; ?>
										<button type="submit" name="bolt_edit_ai_op" value="save" class="bolt-edit-button"><?php esc_html_e( 'Save', 'bolt-edit' ); ?></button>
									</span>
								</div>
							</form>
						</div></div>
					</div>
				<?php endforeach; ?>

				<div data-subtab-panel="cleanup" hidden>
					<div class="bolt-edit-content"><div class="bolt-edit-card bolt-edit-card--full">
						<div class="bolt-edit-ai__head">
							<?php Bolt_Edit_Admin_UI::intro( __( 'AI instructions', 'bolt-edit' ), __( 'Clean-up', 'bolt-edit' ) ); ?>
						</div>
						<div class="bolt-edit-ai__what">
							<p><strong><?php esc_html_e( 'The problem:', 'bolt-edit' ); ?></strong> <?php esc_html_e( 'AI makes mistakes. While it builds, it skips steps, jumps ahead and forgets parts of these instructions.', 'bolt-edit' ); ?></p>
							<p><strong><?php esc_html_e( 'The solution:', 'bolt-edit' ); ?></strong> <?php esc_html_e( 'One prompt that makes your agent read all the instructions again, check the whole site against them and fix what slipped. The site keeps looking the same, and the agent ends with a short summary in non-technical words.', 'bolt-edit' ); ?></p>
							<p><strong><?php esc_html_e( 'Good to know:', 'bolt-edit' ); ?></strong> <?php esc_html_e( 'Use it before you launch and after bigger changes. Your own additions in the other tabs are part of the check.', 'bolt-edit' ); ?></p>
						</div>
						<div class="bolt-edit-ai__form">
							<label for="bolt-edit-cleanup-prompt"><?php esc_html_e( 'Clean-up prompt', 'bolt-edit' ); ?></label>
							<p class="bolt-edit-focus__sub"><?php esc_html_e( 'Adjust it here before you copy it. Your changes are only for this copy and are not saved.', 'bolt-edit' ); ?></p>
							<textarea id="bolt-edit-cleanup-prompt" rows="16" spellcheck="false"><?php echo esc_textarea( Bolt_Edit_Dev::master_prompt() ); ?></textarea>
							<div class="bolt-edit-ai__actions">
								<span></span>
								<button type="button" class="bolt-edit-button" data-copy="bolt-edit-cleanup-prompt" data-copied-label="<?php esc_attr_e( 'Prompt copied', 'bolt-edit' ); ?>"><?php esc_html_e( 'Copy clean-up prompt', 'bolt-edit' ); ?></button>
							</div>
						</div>
					</div></div>
				</div>
			</div>
		</div>
		<?php
	}
}
