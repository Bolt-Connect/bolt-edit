<?php
/**
 * Bolt Edit admin: About tab and License tab.
 *
 * @package Bolt_Edit
 */

defined( 'ABSPATH' ) || exit;

final class Bolt_Edit_Admin_About {
	/**
	 * About tab.
	 *
	 * @return void
	 */
	public static function render() {
		?>
		<div class="bolt-edit-content">
			<div class="bolt-edit-card bolt-edit-about">
				<div class="bolt-edit-page-intro">
					<span class="bolt-edit-eyebrow"><?php esc_html_e( 'About', 'bolt-edit' ); ?></span>
					<h2><?php esc_html_e( 'About Bolt Edit', 'bolt-edit' ); ?></h2>
				</div>
				<p><?php esc_html_e( 'Bolt Edit lets clients change the words and images on their website, right on the page, without touching the design.', 'bolt-edit' ); ?></p>

				<p><?php esc_html_e( 'The theme owns the design. Other plugins keep owning their own data. Every piece of static text or image in the theme becomes a field the client can edit. One field, one owner. Nothing lives in two places.', 'bolt-edit' ); ?></p>

				<p><?php esc_html_e( 'Bolt Edit does nothing on its own. It needs a theme built to use its helpers, following the instructions in the How to use tab. It will not make an existing page builder or block theme editable.', 'bolt-edit' ); ?></p>

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

				<div class="bolt-edit-about__feedback">
					<p><?php esc_html_e( 'Run into a problem or a bug? Contact us and describe what happened and on which page.', 'bolt-edit' ); ?></p>
					<a href="<?php echo esc_url( BOLT_EDIT_SUPPORT_URL ); ?>" target="_blank" rel="noopener noreferrer" class="bolt-edit-button"><?php esc_html_e( 'Contact Bolt Connect', 'bolt-edit' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * License tab.
	 *
	 * @return void
	 */
	public static function render_license() {
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
}
