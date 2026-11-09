<?php
/**
 * Bolt Edit admin: Get started tab: what Bolt Edit does, for you and for your clients.
 *
 * @package Bolt_Edit
 */

defined( 'ABSPATH' ) || exit;

final class Bolt_Edit_Admin_Get_Started {
	/**
	 * Small website picture used by the For clients walkthrough.
	 *
	 * @param bool $edit Whether the headline shows the blue edit outline and pencil cursor.
	 * @return void
	 */
	private static function render_guide_site( $edit ) {
		?>
		<div class="bolt-edit-gsite">
			<span class="bolt-edit-gsite__nav"><strong><?php esc_html_e( 'Marble & Oak', 'bolt-edit' ); ?></strong><span><?php esc_html_e( 'Shop', 'bolt-edit' ); ?></span><span><?php esc_html_e( 'Story', 'bolt-edit' ); ?></span></span>
			<span class="bolt-edit-gsite__hero">
				<span class="bolt-edit-gsite__h<?php echo $edit ? ' bolt-edit-gsite__edit' : ''; ?>"><?php esc_html_e( 'Freshly roasted coffee', 'bolt-edit' ); ?></span><?php if ( $edit ) : ?><img class="bolt-edit-gsite__cursor" src="<?php echo esc_url( BOLT_EDIT_PLUGIN_URL . 'assets/img/cursor.svg' ); ?>" alt="" width="24" height="24"><?php endif; ?>
				<span class="bolt-edit-gsite__p"><?php esc_html_e( 'Small-batch beans, shipped within two working days.', 'bolt-edit' ); ?></span>
				<span class="bolt-edit-gsite__btn"><?php esc_html_e( 'Shop beans', 'bolt-edit' ); ?></span>
			</span>
			<?php if ( $edit ) : ?>
			<span class="bolt-edit-gtool"><span class="bolt-edit-gtool__btn"><?php esc_html_e( 'Edit', 'bolt-edit' ); ?><span class="dashicons dashicons-edit"></span></span></span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get started tab: for people, you and your clients. The instructions for AI agents have their own tab.
	 *
	 * @return void
	 */
	public static function render() {
		$site_line         = sprintf( /* translators: %s: the address of this website */ __( 'Website: %s. Work on it through your MCP connection to this website. If you are not connected to it, stop and tell me.', 'bolt-edit' ), home_url( '/' ) );
		$prompt_intro      = __( 'We are going to build a custom WordPress theme that works with the Bolt Edit plugin. Bolt Edit has specific theme-building instructions that you must follow throughout the project. Read the complete instructions first with the bolt-edit/get-instructions ability. It returns all instructions, including any that the site owner added, and those additions win when they conflict. If you cannot reach the ability, stop and tell me.', 'bolt-edit' );
		$idea_prompt       = implode(
			"\n\n",
			array(
				$site_line,
				$prompt_intro,
				__( 'Generate the website design and code inside the theme according to the prompt I provide. Build the first complete version and follow the Bolt Edit instructions throughout the project.', 'bolt-edit' ),
				__( 'Website idea or design brief:', 'bolt-edit' ),
			)
		);
		$html_prompt       = implode(
			"\n\n",
			array(
				$site_line,
				$prompt_intro,
				__( 'I am providing an existing HTML design. That can be a single HTML file, an HTML prototype with separate CSS, JavaScript, fonts and images, or a whole folder of files. Use everything I provide and convert it into a maintainable custom WordPress theme by following the Bolt Edit instructions.', 'bolt-edit' ),
				__( 'HTML files or folder location:', 'bolt-edit' ),
			)
		);
		?>
		<div class="bolt-edit-content bolt-edit-content--onboarding">
			<div class="bolt-edit-card bolt-edit-card--full">
				<div data-subtabs>
					<div class="bolt-edit-subtabs" role="tablist">
						<button type="button" class="bolt-edit-subtabs__btn bolt-edit-subtabs__btn--active" data-subtab="humans" role="tab" aria-selected="true"><?php esc_html_e( 'For you', 'bolt-edit' ); ?></button>
						<button type="button" class="bolt-edit-subtabs__btn" data-subtab="clients" role="tab" aria-selected="false"><?php esc_html_e( 'For clients', 'bolt-edit' ); ?></button>
					</div>

					<div data-subtab-panel="humans">
						<div class="bolt-edit-get-started__intro">
							<span class="bolt-edit-eyebrow"><?php esc_html_e( 'How to use', 'bolt-edit' ); ?></span>
							<h2><?php esc_html_e( 'Build a custom theme with your AI coding tool', 'bolt-edit' ); ?></h2>
							<p><?php esc_html_e( 'Bolt Edit provides the editable content layer and the rules your agent follows. Your coding tool builds the WordPress theme.', 'bolt-edit' ); ?></p>
						</div>

						<h3 class="bolt-edit-section-heading"><?php esc_html_e( 'Phase 1: Connect your AI tool through MCP', 'bolt-edit' ); ?></h3>
						<p><?php esc_html_e( 'Pick an MCP plugin or solution you like, add it to this website and connect it to your favourite AI tool.', 'bolt-edit' ); ?></p>
						<div class="bolt-edit-agent-tools" aria-label="<?php esc_attr_e( 'Compatible AI coding tools', 'bolt-edit' ); ?>">
							<span class="bolt-edit-agent-tools__label"><?php esc_html_e( 'Works with', 'bolt-edit' ); ?></span>
							<span class="bolt-edit-agent-tool"><?php esc_html_e( 'Codex', 'bolt-edit' ); ?></span>
							<span class="bolt-edit-agent-tool"><?php esc_html_e( 'Claude', 'bolt-edit' ); ?></span>
							<span class="bolt-edit-agent-tool"><?php esc_html_e( 'Cursor', 'bolt-edit' ); ?></span>
							<span class="bolt-edit-agent-tool"><?php esc_html_e( 'Gemini', 'bolt-edit' ); ?></span>
							<span class="bolt-edit-agent-tool"><?php esc_html_e( 'Any other coding agents', 'bolt-edit' ); ?></span>
						</div>

						<h3 class="bolt-edit-section-heading"><?php esc_html_e( 'Phase 2: Start something new', 'bolt-edit' ); ?></h3>
						<p><?php esc_html_e( 'Copy the prompt for your starting point, add your idea or the location of your HTML files, and let your agent build the theme. The link to this website is already in it.', 'bolt-edit' ); ?></p>

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
								<h3><?php esc_html_e( 'Convert an HTML prototype', 'bolt-edit' ); ?></h3>
								<p><?php esc_html_e( 'Give your agent your HTML design, from a single file to a whole folder with CSS, scripts and images, and have it keep the design while turning it into a proper WordPress theme.', 'bolt-edit' ); ?></p>
								<button type="button" class="bolt-edit-button" data-copy="bolt-edit-html-prompt" data-copied-label="<?php esc_attr_e( 'Prompt copied', 'bolt-edit' ); ?>">
									<?php esc_html_e( 'Copy conversion prompt', 'bolt-edit' ); ?>
								</button>
								<textarea id="bolt-edit-html-prompt" class="bolt-edit-copy-source" readonly tabindex="-1" aria-hidden="true"><?php echo esc_textarea( $html_prompt ); ?></textarea>
							</article>
						</div>

						<h3 class="bolt-edit-section-heading"><?php esc_html_e( 'Phase 3: Build the website', 'bolt-edit' ); ?></h3>
						<p><?php esc_html_e( 'Keep prompting and build the website the way you want. Now and then, remind your agent to follow the Bolt Edit instructions. It is not required: Phase 4 checks the whole site afterwards.', 'bolt-edit' ); ?></p>

						<h3 class="bolt-edit-section-heading"><?php esc_html_e( 'Phase 4: Clean up before you launch', 'bolt-edit' ); ?></h3>
						<p><?php esc_html_e( 'AI makes mistakes. It skips a step, jumps ahead and forgets things along the way. The clean-up prompt makes your agent check the whole site against the Bolt Edit instructions, from the CSS to SEO, mobile and speed, and fix what slipped. It ends with a short summary in plain words. After that, check every page yourself and test your text, buttons and images in Bolt Edit.', 'bolt-edit' ); ?></p>
						<button type="button" class="bolt-edit-button" data-copy="bolt-edit-cleanup-prompt-howto" data-copied-label="<?php esc_attr_e( 'Prompt copied', 'bolt-edit' ); ?>"><?php esc_html_e( 'Copy clean-up prompt', 'bolt-edit' ); ?></button>
						<textarea id="bolt-edit-cleanup-prompt-howto" class="bolt-edit-copy-source" readonly tabindex="-1" aria-hidden="true"><?php echo esc_textarea( Bolt_Edit_Dev::master_prompt() ); ?></textarea>

						<div class="bolt-edit-get-started__intro bolt-edit-good">
							<span class="bolt-edit-eyebrow"><?php esc_html_e( 'Good to know', 'bolt-edit' ); ?></span>
							<h2><?php esc_html_e( 'Light for visitors, safe to build on', 'bolt-edit' ); ?></h2>
						</div>
						<ul class="bolt-edit-good__list">
							<li><span class="bolt-edit-good__check dashicons dashicons-yes" aria-hidden="true"></span><div><h3><?php esc_html_e( 'Your website stays fast', 'bolt-edit' ); ?></h3><p><?php esc_html_e( 'Bolt Edit only loads for people who are logged in and allowed to edit. Your visitors never load the Edit button or the editing tools, so your website stays just as fast.', 'bolt-edit' ); ?></p></div></li>
							<li><span class="bolt-edit-good__check dashicons dashicons-yes" aria-hidden="true"></span><div><h3><?php esc_html_e( 'Turning Bolt Edit off is safe', 'bolt-edit' ); ?></h3><p><?php esc_html_e( 'Your agent builds a normal WordPress theme, so every page keeps showing its content without Bolt Edit. We do recommend keeping it on: without it there is no easy editing and no AI instructions to keep the theme well built.', 'bolt-edit' ); ?></p></div></li>
							<li><span class="bolt-edit-good__check dashicons dashicons-yes" aria-hidden="true"></span><div><h3><?php esc_html_e( 'A theme you can hand over', 'bolt-edit' ); ?></h3><p><?php esc_html_e( 'Everything is built the same way: one design system, real WordPress pages and images in the Media Library. Another developer with their own AI agent can pick it up and keep building without starting over.', 'bolt-edit' ); ?></p></div></li>
						</ul>
					</div>

					<div data-subtab-panel="clients" hidden>
						<div class="bolt-edit-get-started__intro bolt-edit-guide__intro">
							<span class="bolt-edit-eyebrow"><?php esc_html_e( 'The problem', 'bolt-edit' ); ?></span>
							<h2><?php esc_html_e( 'Clients want simple page editing', 'bolt-edit' ); ?></h2>
							<p class="bolt-edit-guide__problem"><?php esc_html_e( 'Clients are often overwhelmed when they log in to the WordPress dashboard. Bolt Edit changes that with a super easy front-end editing experience, so they only go to the dashboard when they really need to. It is the easiest way to let clients make changes to an AI-generated WordPress theme: click the pencil, edit, save. It also works with dynamic plugins through a popup, and you as the developer keep full control over how the theme is built.', 'bolt-edit' ); ?></p>
						</div>

						<div class="bolt-edit-guide__steps">
							<div class="bolt-edit-guide__row">
								<div><span class="bolt-edit-guide__n">1</span><h3><?php esc_html_e( 'They log in and land on the website', 'bolt-edit' ); ?></h3><p><?php if ( Bolt_Edit_Client::can_manage() ) { /* translators: %s: link to the Focus mode settings */ echo wp_kses( sprintf( __( '%s starts them on the website, with a simple top bar instead of the full WordPress menu.', 'bolt-edit' ), '<a href="#settings-focus">' . esc_html__( 'Focus mode', 'bolt-edit' ) . '</a>' ), array( 'a' => array( 'href' => array() ) ) ); } else { esc_html_e( 'Focus mode starts them on the website, with a simple top bar instead of the full WordPress menu.', 'bolt-edit' ); } ?></p></div>
								<div class="bolt-edit-guide__vis" aria-hidden="true">
									<div class="bolt-edit-glogin"><span class="bolt-edit-glogin__t"><?php esc_html_e( 'Log in', 'bolt-edit' ); ?></span><span class="bolt-edit-glogin__f"><?php esc_html_e( 'name@company.com', 'bolt-edit' ); ?></span><span class="bolt-edit-glogin__f">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span><span class="bolt-edit-glogin__b"><?php esc_html_e( 'Log in', 'bolt-edit' ); ?></span></div>
									<span class="dashicons dashicons-arrow-right-alt bolt-edit-guide__arrow"></span>
									<?php self::render_guide_site( false ); ?>
								</div>
							</div>

							<div class="bolt-edit-guide__row">
								<div><span class="bolt-edit-guide__n">2</span><h3><?php esc_html_e( 'They point at text and click', 'bolt-edit' ); ?></h3><p><?php esc_html_e( 'Anything they can change gets a blue outline and a pencil cursor. A click opens it in a popup.', 'bolt-edit' ); ?></p></div>
								<div class="bolt-edit-guide__vis" aria-hidden="true"><?php self::render_guide_site( true ); ?></div>
							</div>

							<div class="bolt-edit-guide__row">
								<div><span class="bolt-edit-guide__n">3</span><h3><?php esc_html_e( 'They edit, or leave a comment', 'bolt-edit' ); ?></h3><p><?php esc_html_e( 'Edit saves the change. Comment sends you a private note about that exact spot.', 'bolt-edit' ); ?></p></div>
								<div class="bolt-edit-guide__vis" aria-hidden="true">
									<div class="bolt-edit-gpop"><div class="bolt-edit-gpop__head"><span><span class="bolt-edit-gpop__tab is-on"><?php esc_html_e( 'Edit', 'bolt-edit' ); ?></span><span class="bolt-edit-gpop__tab"><?php esc_html_e( 'Comment', 'bolt-edit' ); ?></span></span><span class="dashicons dashicons-no-alt"></span></div><span class="bolt-edit-gpop__field"><?php esc_html_e( 'Freshly roasted coffee', 'bolt-edit' ); ?></span><span class="bolt-edit-gpop__btn"><?php esc_html_e( 'Save', 'bolt-edit' ); ?></span></div>
									<div class="bolt-edit-gpop"><div class="bolt-edit-gpop__head"><span><span class="bolt-edit-gpop__tab"><?php esc_html_e( 'Edit', 'bolt-edit' ); ?></span><span class="bolt-edit-gpop__tab is-on"><?php esc_html_e( 'Comment', 'bolt-edit' ); ?></span></span><span class="dashicons dashicons-no-alt"></span></div><span class="bolt-edit-gpop__field bolt-edit-gpop__field--area"><?php esc_html_e( 'Can we make this headline bigger?', 'bolt-edit' ); ?></span><span class="bolt-edit-gpop__btn"><?php esc_html_e( 'Send', 'bolt-edit' ); ?></span></div>
								</div>
							</div>

							<div class="bolt-edit-guide__row">
								<div><span class="bolt-edit-guide__n">4</span><h3><?php esc_html_e( 'Dynamic content opens what it needs', 'bolt-edit' ); ?></h3><p><?php esc_html_e( 'Products, posts and other managed content get a gray outline. Popular dynamic content plugins open their edit screen in the popup, scrolled to the field you clicked and outlined. Their role decides what they may edit: WooCommerce products need the Shop manager role.', 'bolt-edit' ); ?></p></div>
								<div class="bolt-edit-guide__vis" aria-hidden="true">
									<div class="bolt-edit-gsite bolt-edit-gsite--pad"><div class="bolt-edit-gouter"><span class="bolt-edit-gprod"><i></i><span><strong><?php esc_html_e( 'Cerrado', 'bolt-edit' ); ?></strong><em><?php esc_html_e( '€14.00 / 250g', 'bolt-edit' ); ?></em></span></span><span class="bolt-edit-gprod"><i></i><span><strong><?php esc_html_e( 'Kirinyaga', 'bolt-edit' ); ?></strong><em><?php esc_html_e( '€12.90 / 250g', 'bolt-edit' ); ?></em></span></span></div></div>
									<span class="dashicons dashicons-arrow-right-alt bolt-edit-guide__arrow"></span>
									<div class="bolt-edit-gpop"><div class="bolt-edit-gpop__head"><span><span class="bolt-edit-gpop__tab is-on"><?php esc_html_e( 'Edit', 'bolt-edit' ); ?></span><span class="bolt-edit-gpop__tab"><?php esc_html_e( 'Comment', 'bolt-edit' ); ?></span></span><span class="dashicons dashicons-no-alt"></span></div><span class="bolt-edit-gpop__label"><?php esc_html_e( 'Product name', 'bolt-edit' ); ?></span><span class="bolt-edit-gpop__field"><?php esc_html_e( 'Cerrado', 'bolt-edit' ); ?></span><span class="bolt-edit-gpop__label"><?php esc_html_e( 'Price', 'bolt-edit' ); ?></span><span class="bolt-edit-gpop__field"><?php esc_html_e( '14.00', 'bolt-edit' ); ?></span><span class="bolt-edit-gpop__btn"><?php esc_html_e( 'Save', 'bolt-edit' ); ?></span></div>
								</div>
							</div>

							<div class="bolt-edit-guide__row">
								<div><span class="bolt-edit-guide__n">5</span><h3><?php esc_html_e( 'You see what changed', 'bolt-edit' ); ?></h3><p><?php esc_html_e( 'Every edit is logged with the value before and after, under Changes. Comments land next to it, under Comments.', 'bolt-edit' ); ?></p></div>
								<div class="bolt-edit-guide__vis" aria-hidden="true">
									<div class="bolt-edit-glog"><span class="bolt-edit-glog__tabs"><span class="is-on"><?php esc_html_e( 'Changes', 'bolt-edit' ); ?></span><span><?php esc_html_e( 'Comments', 'bolt-edit' ); ?></span></span><span class="bolt-edit-glog__top"><strong><?php esc_html_e( 'Home headline', 'bolt-edit' ); ?></strong><em><?php esc_html_e( 'Updated by your client, 10:42', 'bolt-edit' ); ?></em></span><span class="bolt-edit-glog__diff"><span><?php esc_html_e( 'Fresh coffee', 'bolt-edit' ); ?></span><span class="dashicons dashicons-arrow-right-alt"></span><span><?php esc_html_e( 'Freshly roasted coffee', 'bolt-edit' ); ?></span></span></div>
								</div>
							</div>
						</div>

					</div>

				</div>

			</div>
		</div>
		<?php
	}
}
