<?php
/**
 * Dev mode: an inspector on the website for the users you choose.
 *
 * Hover an element to see its classes, click it to see how it is styled and whether
 * the CSS follows your framework. Only the chosen users get the Dev button, so an
 * administrator who is not chosen sees nothing.
 *
 * @package Bolt_Edit
 */

defined( 'ABSPATH' ) || exit;

final class Bolt_Edit_Dev {
	const USERS_OPTION  = 'bolt_edit_dev_users';
	const PREFIX_OPTION = 'bolt_edit_dev_prefix';
	const MAX_RESULTS   = 8;

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_bolt_edit_dev_search', array( __CLASS__, 'ajax_search' ) );
		add_action( 'wp_ajax_bolt_edit_dev_users', array( __CLASS__, 'ajax_users' ) );
		add_action( 'delete_user', array( __CLASS__, 'forget_user' ) );
	}

	/** IDs of the users who get Dev mode. */
	public static function user_ids() {
		return array_values( array_unique( array_filter( array_map( 'absint', (array) get_option( self::USERS_OPTION, array() ) ) ) ) );
	}


	/** The start of your framework class names. The standard is be-, unless a different one was saved. */
	public static function prefix() {
		$saved = self::clean_prefix( get_option( self::PREFIX_OPTION, '' ) );
		return '' !== $saved ? $saved : 'be-';
	}

	public static function clean_prefix( $value ) {
		return substr( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ), 0, 32 );
	}

	public static function save_prefix( $value ) {
		update_option( self::PREFIX_OPTION, self::clean_prefix( $value ), true );
	}

	/** The rules every clean-up prompt shares. The short prompt in the popup and the master prompt in Settings are built from these. */
	public static function prompt_rules() {
		return array(
			'intro'  => 'Clean up this whole WordPress site, all pages and all templates, so it follows the Bolt Edit instructions again.',
			'read'   => 'First read all Bolt Edit instructions with the bolt-edit/get-instructions ability. Read every part in full and do not skip anything: the default instructions and the additions of the site owner come as one document, and the details matter. Then go through the site part by part, from the design system to SEO, mobile, speed and safety, and fix everything that does not follow them.',
			'swap'   => 'Class changes made in Dev mode are temporary overrides. Make each change in the templates by replacing the class on that element, and check that the new class gives the same result without the override.',
			'safe'   => 'The site must look the same afterwards. Tiny differences from merging near-identical values are fine. Never remove or overwrite content or saved Bolt Edit values, and do not change page URLs. If a fix would change content, a URL or how the site looks, do not make it: list it as a suggestion.',
			'report' => 'When you are done, give a short summary in non-technical, plain words: what you improved, and what still needs attention, such as missing images, a favicon, texts to fill in and the suggestions you did not carry out.',
		);
	}

	/**
	 * The clean-up prompt. It points the agent to the Bolt Edit instructions and only adds what a clean-up needs.
	 *
	 * @return string
	 */
	public static function master_prompt() {
		$rules = self::prompt_rules();
		$lines = array_merge(
			array( $rules['intro'], '', $rules['read'] ),
			array( '', $rules['safe'], '', $rules['report'] )
		);
		$changes = array();
		if ( class_exists( 'Bolt_Edit_Classes' ) ) {
			foreach ( Bolt_Edit_Classes::all() as $item ) {
				if ( empty( $item['selector'] ) || empty( $item['swaps'] ) ) {
					continue;
				}
				foreach ( Bolt_Edit_Classes::clean_swaps( $item['swaps'] ) as $swap ) {
					$changes[] = 'On ' . $item['selector'] . ', replace the class ' . $swap['from'] . ' with ' . $swap['to'] . '.';
				}
			}
		}
		if ( $changes ) {
			$at = array_search( $rules['safe'], $lines, true );
			$add = array_merge( array( $rules['swap'] ), array_map( static function ( $line ) {
				return '- ' . $line;
			}, array_slice( $changes, 0, 40 ) ), array( '' ) );
			array_splice( $lines, false === $at ? count( $lines ) : $at, 0, $add );
		}
		return implode( "\n", $lines );
	}

	/** Whether this user gets the Dev button. Needs the website editor too, and never applies to Focus mode users. */
	public static function allowed() {
		if ( ! is_user_logged_in() || ! Bolt_Edit_Plugin::can_edit() || Bolt_Edit_Client::is_client() || ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return in_array( get_current_user_id(), self::user_ids(), true );
	}

	public static function enqueue_assets() {
		if ( Bolt_Edit_Plugin::in_iframe() || ! self::allowed() ) {
			return;
		}


		wp_enqueue_style( 'bolt-edit-dev', BOLT_EDIT_PLUGIN_URL . 'assets/css/dev.css', array( 'bolt-edit-editor' ), Bolt_Edit_Plugin::asset_version( 'assets/css/dev.css' ) );
		wp_enqueue_script( 'bolt-edit-dev', BOLT_EDIT_PLUGIN_URL . 'assets/js/dev.js', array( 'bolt-edit-editor' ), Bolt_Edit_Plugin::asset_version( 'assets/js/dev.js' ), true );
		wp_enqueue_style( 'bolt-edit-dev-spacing', BOLT_EDIT_PLUGIN_URL . 'assets/css/dev-spacing.css', array( 'bolt-edit-dev' ), Bolt_Edit_Plugin::asset_version( 'assets/css/dev-spacing.css' ) );
		wp_enqueue_script( 'bolt-edit-dev-spacing', BOLT_EDIT_PLUGIN_URL . 'assets/js/dev-spacing.js', array( 'bolt-edit-dev' ), Bolt_Edit_Plugin::asset_version( 'assets/js/dev-spacing.js' ), true );
		wp_enqueue_script( 'bolt-edit-dev-classes', BOLT_EDIT_PLUGIN_URL . 'assets/js/dev-classes.js', array( 'bolt-edit-dev', 'bolt-edit-dev-spacing' ), Bolt_Edit_Plugin::asset_version( 'assets/js/dev-classes.js' ), true );
		wp_localize_script(
			'bolt-edit-dev',
			'boltEditDev',
			array(
				'prefix' => self::prefix(),
				'cleanupUrl' => admin_url( 'admin.php?page=bolt-edit#ai-cleanup' ),
				'prompt' => self::prompt_rules(),
				'spacing' => Bolt_Edit_Spacing::localized(),
				'classes' => Bolt_Edit_Classes::localized(),
				'labels' => array(
					'dev'          => __( 'Dev', 'bolt-edit' ),
					'copied'       => __( 'Copied', 'bolt-edit' ),
					'copySelector' => __( 'Copy selector', 'bolt-edit' ),
					'close'        => __( 'Close', 'bolt-edit' ),
					'classes'      => __( 'Classes', 'bolt-edit' ),
					'panelTitle'   => __( 'Dev mode', 'bolt-edit' ),
					'copy'         => __( 'Copy', 'bolt-edit' ),
					'noClasses'    => __( 'No classes on this element.', 'bolt-edit' ),
					'field'        => __( 'Bolt Edit field', 'bolt-edit' ),
					'styling'      => __( 'Styling', 'bolt-edit' ),
					'font'         => __( 'Font', 'bolt-edit' ),
					'size'         => __( 'Size', 'bolt-edit' ),
					'color'        => __( 'Colour', 'bolt-edit' ),
					'hard'         => __( 'Hard-coded in the CSS', 'bolt-edit' ),
					'browser'      => __( 'Browser default', 'bolt-edit' ),
					'from'         => __( 'from', 'bolt-edit' ),
					'framework'    => __( 'Consistency check', 'bolt-edit' ),
					'done'         => __( 'Done', 'bolt-edit' ),
					'hideResults'  => __( 'Hide results', 'bolt-edit' ),
					'frameworkClass' => __( 'Framework class', 'bolt-edit' ),
					'variables'    => __( 'Uses variables', 'bolt-edit' ),
					'none'         => __( 'None', 'bolt-edit' ),
					'check'        => __( 'Check the whole page', 'bolt-edit' ),
					'checking'     => __( 'Checking...', 'bolt-edit' ),
					'hardFonts'    => __( 'Fonts', 'bolt-edit' ),
					'hardSizes'    => __( 'Sizes', 'bolt-edit' ),
					'hardColors'   => __( 'Colours', 'bolt-edit' ),
					'showMe'       => __( 'Elements to look at', 'bolt-edit' ),
					'blocked'      => __( 'Some stylesheets come from another domain and cannot be read.', 'bolt-edit' ),
					'textElements' => __( 'text elements use only variables (or the browser default).', 'bolt-edit' ),
					'limited'      => __( 'Only the first 1500 text elements were checked.', 'bolt-edit' ),
				),
			)
		);
	}

	/** Every handler checks the nonce itself first. */
	private static function ajax_guard() {
		if ( ! Bolt_Edit_Client::can_manage() ) {
			wp_send_json_error( array( 'message' => __( 'You cannot change these settings.', 'bolt-edit' ) ), 403 );
		}
	}

	/** One user as the picker shows it. */
	public static function chosen_users() {
		$ids = self::user_ids();
		if ( ! $ids ) {
			return array();
		}
		return array_map( array( 'Bolt_Edit_Client', 'user_row' ), get_users( array( 'include' => $ids, 'orderby' => 'display_name', 'number' => count( $ids ) ) ) );
	}

	/** Search by name or email. Two letters or more, a handful of results. Only administrators. */
	public static function ajax_search() {
		check_ajax_referer( 'bolt_edit_dev', 'nonce' );
		self::ajax_guard();
		$term = isset( $_POST['q'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['q'] ) ) ) : '';
		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array( 'users' => array(), 'more' => 0 ) );
		}
		$query = new WP_User_Query(
			array(
				'search'         => '*' . $term . '*',
				'search_columns' => array( 'user_login', 'user_email', 'display_name', 'user_nicename' ),
				'capability__in' => array( 'manage_options' ),
				'exclude'        => array_merge( self::user_ids(), Bolt_Edit_Client::user_ids() ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- User query with a short list of IDs.
				'orderby'        => 'display_name',
				'number'         => self::MAX_RESULTS,
				'count_total'    => true,
			)
		);
		$users = array_map( array( 'Bolt_Edit_Client', 'user_row' ), $query->get_results() );
		wp_send_json_success( array( 'users' => $users, 'more' => max( 0, (int) $query->get_total() - count( $users ) ) ) );
	}

	/** Add or remove one user. */
	public static function ajax_users() {
		check_ajax_referer( 'bolt_edit_dev', 'nonce' );
		self::ajax_guard();
		$id  = isset( $_POST['user'] ) ? absint( $_POST['user'] ) : 0;
		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$ids = self::user_ids();
		if ( 'add' === $do ) {
			$user = get_userdata( $id );
			if ( ! $user || ! user_can( $user, 'manage_options' ) || in_array( $id, Bolt_Edit_Client::user_ids(), true ) ) {
				wp_send_json_error( array( 'message' => __( 'This user cannot be added.', 'bolt-edit' ) ), 400 );
			}
			$ids[] = $id;
		} elseif ( 'remove' === $do ) {
			$ids = array_diff( $ids, array( $id ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Nothing to do.', 'bolt-edit' ) ), 400 );
		}
		update_option( self::USERS_OPTION, array_values( array_unique( array_map( 'absint', $ids ) ) ), true );
		wp_send_json_success( array( 'count' => count( $ids ) ) );
	}

	/** A deleted user drops out of the list. */
	public static function forget_user( $id ) {
		$ids = self::user_ids();
		if ( in_array( (int) $id, $ids, true ) ) {
			update_option( self::USERS_OPTION, array_values( array_diff( $ids, array( (int) $id ) ) ), true );
		}
	}
}