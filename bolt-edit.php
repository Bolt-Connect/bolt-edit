<?php
/**
 * Plugin Name: Bolt Edit
 * Description: Frontend content editing for AI-built WordPress themes. The theme owns the design, your client edits the words and images.
 * Version: 1.0.0
 * Plugin URI: https://boltconnect.nl
 * Update URI: https://github.com/Bolt-Connect/bolt-edit/
 * Author: Bolt Connect
 * Author URI: https://boltconnect.nl
 * Text Domain: bolt-edit
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'BOLT_EDIT_VERSION', '1.0.0' );
define( 'BOLT_EDIT_PLUGIN_FILE', __FILE__ );
define( 'BOLT_EDIT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BOLT_EDIT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'BOLT_EDIT_SUPPORT_URL', 'https://boltconnect.nl' );

require_once BOLT_EDIT_PLUGIN_PATH . 'includes/class-bolt-edit-fields.php';
require_once BOLT_EDIT_PLUGIN_PATH . 'includes/class-bolt-edit-activity.php';
require_once BOLT_EDIT_PLUGIN_PATH . 'includes/class-bolt-edit-admin.php';
require_once BOLT_EDIT_PLUGIN_PATH . 'includes/class-bolt-edit-plugin.php';
require_once BOLT_EDIT_PLUGIN_PATH . 'includes/template-functions.php';
require_once BOLT_EDIT_PLUGIN_PATH . 'includes/class-bolt-edit-client.php';
require_once BOLT_EDIT_PLUGIN_PATH . 'includes/class-bolt-edit-comments.php';
require_once BOLT_EDIT_PLUGIN_PATH . 'includes/class-bolt-edit-abilities.php';

/**
 * Load the Dutch translation for every Dutch WordPress locale (nl_NL, nl_BE,
 * nl_NL_formal, ...). All other locales keep the English source strings.
 *
 * Runs again once the request is parsed and when the locale switches, so
 * multilingual plugins that pick the language from the URL get the right one.
 *
 * @return void
 */
function bolt_edit_load_textdomain() {
	$locale = determine_locale();
	$dutch  = 'nl' === strtolower( substr( $locale, 0, 2 ) );

	unload_textdomain( 'bolt-edit', true );

	if ( $dutch ) {
		load_textdomain( 'bolt-edit', BOLT_EDIT_PLUGIN_PATH . 'languages/bolt-edit-nl_NL.mo', $locale );
	}
}
add_action( 'init', 'bolt_edit_load_textdomain', 1 );
add_action( 'wp', 'bolt_edit_load_textdomain', 1 );
add_action( 'change_locale', 'bolt_edit_load_textdomain' );

register_activation_hook( BOLT_EDIT_PLUGIN_FILE, array( 'Bolt_Edit_Activity', 'install' ) );

Bolt_Edit_Activity::init();
Bolt_Edit_Admin::init();
Bolt_Edit_Plugin::init();
Bolt_Edit_Client::init();
Bolt_Edit_Comments::init();
Bolt_Edit_Abilities::init();

require_once BOLT_EDIT_PLUGIN_PATH . 'includes/class-bolt-edit-updates.php';
Bolt_Edit_Updates::boot();
