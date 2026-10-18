<?php
if (!defined('ABSPATH')) { exit; }

/** Published public GitHub releases; no site token is required. */
final class Bolt_Edit_Updates {
    const REPO = 'Bolt-Connect/bolt-edit';
    const UPDATE_URI = 'https://github.com/Bolt-Connect/bolt-edit/';
    const API = 'https://api.github.com/repos/Bolt-Connect/bolt-edit';
    const MAX_PACKAGE_BYTES = 20971520;

    public static function boot() {
        add_filter('update_plugins_github.com', [__CLASS__, 'update'], 10, 4);
        add_filter('plugins_api', [__CLASS__, 'information'], 10, 3);
        add_filter('upgrader_pre_download', [__CLASS__, 'download'], 10, 4);
        add_filter('upgrader_source_selection', [__CLASS__, 'source'], 10, 4);
        add_action('admin_post_bolt_edit_check_updates', [__CLASS__, 'check']);
    }
    private static function cache_key() { return 'bolt_edit_github_public_release'; }
    private static function args() {
        return ['timeout'=>15, 'redirection'=>0, 'limit_response_size'=>262144,
            'user-agent'=>'Bolt Edit Updater/' . BOLT_EDIT_VERSION,
            'headers'=>['Accept'=>'application/vnd.github+json'], 'cookies'=>[]];
    }
    private static function error($code, $message) { return new WP_Error('bolt_edit_' . $code, $message); }
    private static function json($path) {
        $response = wp_safe_remote_get(self::API . $path, self::args());
        if (is_wp_error($response)) { return self::error('github_unavailable', __('Could not reach GitHub. Your editor continues working normally.', 'bolt-edit')); }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code === 401 || $code === 404) {
            return self::error('github_access', __('The public Bolt Edit repository is unavailable. Try again later.', 'bolt-edit'));
        }
        if ($code !== 200) { return self::error('github_unavailable', __('GitHub could not complete the check. Try again later.', 'bolt-edit')); }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($data) ? $data : self::error('github_invalid', __('GitHub returned invalid release information.', 'bolt-edit'));
    }
    /**
     * Resolve a tag to its commit SHA only. The full commit response includes the
     * diff and can exceed the response size limit for large releases.
     */
    private static function commit_sha($tag) {
        $args = self::args();
        $args['headers']['Accept'] = 'application/vnd.github.sha';
        $response = wp_safe_remote_get(self::API . '/commits/' . rawurlencode($tag), $args);
        if (is_wp_error($response)) { return self::error('github_unavailable', __('Could not reach GitHub. Your editor continues working normally.', 'bolt-edit')); }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code === 401 || $code === 404) {
            return self::error('github_access', __('The public Bolt Edit repository is unavailable. Try again later.', 'bolt-edit'));
        }
        if ($code !== 200) { return self::error('github_unavailable', __('GitHub could not complete the check. Try again later.', 'bolt-edit')); }
        $sha = trim((string) wp_remote_retrieve_body($response));
        return preg_match('/^[a-f0-9]{40}$/D', $sha) ? $sha : self::error('github_invalid', __('GitHub returned invalid release information.', 'bolt-edit'));
    }
    public static function validate($data, $commit) {
        if (!is_array($data) || !empty($data['draft']) || !empty($data['prerelease'])
            || !is_string($data['tag_name'] ?? null) || !preg_match('/^v?(\d+\.\d+\.\d+)$/D', $data['tag_name'], $version)
            || !is_string($commit) || !preg_match('/^[a-f0-9]{40}$/D', $commit)) {
            return self::error('github_invalid', __('Publish a stable Bolt Edit release with a tag such as v0.1.3.', 'bolt-edit'));
        }
        return ['version'=>$version[1], 'commit'=>$commit, 'download_url'=>self::API . '/zipball/' . $commit,
            'homepage'=>self::UPDATE_URI . 'releases/tag/' . rawurlencode($data['tag_name']),
            'changelog'=>nl2br(esc_html(substr((string)($data['body'] ?? ''), 0, 16000)))];
    }
    public static function release($force = false) {
        $cached = $force ? false : get_site_transient(self::cache_key());
        if (is_array($cached)) { return $cached['release'] ?? self::error($cached['code'], $cached['error']); }
        $release = self::json('/releases/latest');
        if (is_wp_error($release) && $release->get_error_code() === 'bolt_edit_github_access') {
            // GitHub returns 404 when there are no published releases.
            $repo = self::json('');
            if (!is_wp_error($repo)) { $release = self::error('github_no_release', __('No published Bolt Edit release yet.', 'bolt-edit')); }
        }
        if (!is_wp_error($release)) {
            $tag = $release['tag_name'] ?? '';
            if (!is_string($tag) || !preg_match('/^v?\d+\.\d+\.\d+$/D', $tag) || !empty($release['draft']) || !empty($release['prerelease'])) {
                $release = self::error('github_invalid', __('Publish a stable Bolt Edit release with a tag such as v0.1.3.', 'bolt-edit'));
            } else {
                // Pin the offered release to its commit, not a movable branch or tag.
                $commit = self::commit_sha($tag);
                $release = is_wp_error($commit) ? $commit : self::validate($release, $commit);
            }
        }
        set_site_transient(self::cache_key(), ['release'=>is_wp_error($release) ? null : $release,
            'code'=>is_wp_error($release) ? substr($release->get_error_code(), strlen('bolt_edit_')) : '',
            'error'=>is_wp_error($release) ? $release->get_error_message() : ''],
            is_wp_error($release) ? 15 * MINUTE_IN_SECONDS : 6 * HOUR_IN_SECONDS);
        return $release;
    }
    public static function update($update, $plugin_data, $plugin_file, $locales) {
        if ($plugin_file !== plugin_basename(BOLT_EDIT_PLUGIN_FILE)) { return $update; }
        $release = self::release();
        if (is_wp_error($release)) { return false; }
        return ['id'=>self::UPDATE_URI, 'slug'=>'bolt-edit', 'version'=>$release['version'],
            'url'=>$release['homepage'], 'package'=>$release['download_url'],
            'requires'=>'6.4', 'requires_php'=>'7.4', 'bolt_edit_commit'=>$release['commit']];
    }
    public static function information($result, $action, $args) {
        if ($action !== 'plugin_information' || ($args->slug ?? '') !== 'bolt-edit') { return $result; }
        $release = self::release();
        if (is_wp_error($release)) { return $release; }
        return (object)['name'=>'Bolt Edit', 'slug'=>'bolt-edit', 'version'=>$release['version'], 'author'=>'Bolt Connect',
            'homepage'=>$release['homepage'], 'requires'=>'6.4', 'requires_php'=>'7.4', 'download_link'=>$release['download_url'],
            'sections'=>['description'=>esc_html__('Frontend content editing for AI-built WordPress themes.', 'bolt-edit'),'changelog'=>$release['changelog']]];
    }
    private static function own_update($extra) {
        // Single updates pass type and action; bulk updates (also the "Update now" link) pass only the plugin.
        return ($extra['plugin'] ?? '') === plugin_basename(BOLT_EDIT_PLUGIN_FILE)
            && ($extra['action'] ?? 'update') === 'update' && ($extra['type'] ?? 'plugin') === 'plugin';
    }
    private static function offered() {
        $updates = get_site_transient('update_plugins');
        return $updates->response[plugin_basename(BOLT_EDIT_PLUGIN_FILE)] ?? null;
    }
    /** Only the published release commit may be downloaded. */
    public static function package_allowed($url, $commit) {
        return is_string($commit) && preg_match('/^[a-f0-9]{40}$/D', $commit) && $url === self::API . '/zipball/' . $commit;
    }
    public static function download_url_allowed($url, $commit) {
        if (!is_string($url) || strlen($url) > 4096 || !preg_match('/^[a-f0-9]{40}$/D', (string)$commit)) { return false; }
        $parts = wp_parse_url($url);
        return $parts && ($parts['scheme'] ?? '') === 'https' && ($parts['host'] ?? '') === 'codeload.github.com'
            && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['fragment'])
            && (!isset($parts['port']) || $parts['port'] === 443)
            && in_array($parts['path'] ?? '', ['/' . self::REPO . '/legacy.zip/' . $commit, '/' . self::REPO . '/zip/' . $commit], true);
    }
    public static function download($reply, $package, $upgrader, $extra) {
        if (!self::own_update($extra) || is_wp_error($reply)) { return $reply; }
        $offered = self::offered();
        if (!$offered || ($offered->package ?? '') !== $package
            || !self::package_allowed($package, $offered->bolt_edit_commit ?? '')) {
            return self::error('package_unverified', __('Check for Bolt Edit updates again before installing.', 'bolt-edit'));
        }
        if (!version_compare($offered->new_version ?? '0', BOLT_EDIT_VERSION, '>')) {
            return self::error('package_old', __('This release is not newer than the installed version.', 'bolt-edit'));
        }
        // Resolve the archive without sending site credentials or cookies.
        $response = wp_safe_remote_get($package, self::args());
        if (is_wp_error($response) || (int)wp_remote_retrieve_response_code($response) !== 302) {
            return self::error('package_unavailable', __('GitHub could not provide the update download. Try checking for updates again.', 'bolt-edit'));
        }
        $url = wp_remote_retrieve_header($response, 'location');
        if (!self::download_url_allowed($url, $offered->bolt_edit_commit)) {
            return self::error('package_redirect', __('GitHub returned an unexpected update download location.', 'bolt-edit'));
        }
        if (!function_exists('wp_tempnam')) { require_once ABSPATH . 'wp-admin/includes/file.php'; }
        $file = wp_tempnam('bolt-edit-update.zip');
        if (!$file) { return self::error('package_temp', __('Could not create a temporary update file.', 'bolt-edit')); }
        $response = wp_safe_remote_get($url, ['timeout'=>60, 'redirection'=>0, 'stream'=>true, 'filename'=>$file,
            'limit_response_size'=>self::MAX_PACKAGE_BYTES, 'user-agent'=>'Bolt Edit Updater/' . BOLT_EDIT_VERSION,
            'cookies'=>[], 'headers'=>[]]);
        $handle = fopen($file, 'rb'); $signature = $handle ? fread($handle, 4) : '';
        if ($handle) { fclose($handle); }
        if (is_wp_error($response) || (int)wp_remote_retrieve_response_code($response) !== 200
            || $signature !== "PK\x03\x04" || filesize($file) >= self::MAX_PACKAGE_BYTES) {
            wp_delete_file($file);
            return self::error('package_invalid', __('The update ZIP could not be downloaded. Your installed plugin has not been changed.', 'bolt-edit'));
        }
        return $file;
    }
    public static function source($source, $remote, $upgrader, $extra) {
        if (!self::own_update($extra) || is_wp_error($source)) { return $source; }
        global $wp_filesystem;
        $offered = self::offered();
        $header = $wp_filesystem->get_contents(trailingslashit($source) . 'bolt-edit.php');
        $core = $header;
        if (!$offered || !is_string($header) || !is_string($core)
            || !preg_match('/^\s*\* Plugin Name:\s*Bolt Edit\s*$/m', $header)
            || !preg_match('/^\s*\* Version:\s*(\d+\.\d+\.\d+)\s*$/m', $header, $version)
            || $version[1] !== ($offered->new_version ?? '') || false === strpos($core, "define( 'BOLT_EDIT_VERSION', '" . $version[1] . "' );")) {
            return self::error('package_version', __('The downloaded plugin does not match the published release version.', 'bolt-edit'));
        }
        foreach (['includes/class-bolt-edit-plugin.php', 'includes/class-bolt-edit-fields.php', 'includes/class-bolt-edit-admin.php', 'includes/class-bolt-edit-activity.php', 'includes/class-bolt-edit-client.php', 'includes/class-bolt-edit-comments.php', 'includes/template-functions.php', 'includes/class-bolt-edit-updates.php', 'assets/js/editor.js', 'assets/css/editor.css', 'admin/js/admin.js', 'admin/css/admin.css'] as $required) {
            if (!$wp_filesystem->is_file(trailingslashit($source) . $required)) { return self::error('package_incomplete', __('The published Bolt Edit release is missing required files.', 'bolt-edit')); }
        }
        // GitHub archives use an owner/repo/hash folder; WordPress needs bolt-edit/.
        $target = trailingslashit(dirname(untrailingslashit($source))) . 'bolt-edit/';
        if (untrailingslashit($source) === untrailingslashit($target)) { return $source; }
        return $wp_filesystem->move($source, $target, true) ? $target : self::error('package_folder', __('Could not prepare the Bolt Edit update folder.', 'bolt-edit'));
    }
    public static function check() {
        if (!current_user_can('update_plugins')) { wp_die(esc_html__('You cannot check plugin updates.', 'bolt-edit')); }
        check_admin_referer('bolt_edit_check_updates');
        self::release(true); wp_clean_plugins_cache(); wp_update_plugins();
        wp_safe_redirect(admin_url('admin.php?page=bolt-edit#settings')); exit;
    }
    public static function screen() {
        $cached = get_site_transient(self::cache_key());
        $release = is_array($cached) ? ($cached['release'] ?? null) : null;
        /* translators: %s: version number. */
        $available = __('Version %s is available.', 'bolt-edit');
        $message = ($release ? (version_compare($release['version'], BOLT_EDIT_VERSION, '>') ? sprintf($available, $release['version']) : __('You have the latest published version.', 'bolt-edit'))
                : ($cached['error'] ?? __('Ready to check GitHub for published releases.', 'bolt-edit')));
        ?>
        <section id="bolt-edit-updates" class="bolt-edit-content"><div class="bolt-edit-card bolt-edit-card--full"><h2><?php esc_html_e('Plugin updates', 'bolt-edit'); ?></h2>
            <p><?php esc_html_e('Installed version:', 'bolt-edit'); ?> <strong><?php echo esc_html(BOLT_EDIT_VERSION); ?></strong></p>
            <p role="status"><?php echo esc_html($message); ?></p>
            <p class="bolt-edit-settings-notice"><?php esc_html_e('Updates come directly from GitHub releases. Updates keep your content, comments, and settings. Automatic updates follow your WordPress choice.', 'bolt-edit'); ?></p>
            <?php if (current_user_can('update_plugins')): ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="bolt_edit_check_updates"><?php wp_nonce_field('bolt_edit_check_updates'); ?><button class="bolt-edit-button bolt-edit-button--quiet"><?php esc_html_e('Check for updates', 'bolt-edit'); ?></button></form>
                <?php if ($release && version_compare($release['version'], BOLT_EDIT_VERSION, '>')): ?><p><a class="bolt-edit-button" href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php esc_html_e('Open Plugins to update', 'bolt-edit'); ?></a></p><?php endif; ?>
            <?php endif; ?>
            <p class="bolt-edit-settings-notice"><?php esc_html_e('This public repository needs no GitHub token or wp-config.php changes.', 'bolt-edit'); ?></p>
        </div></section>
        <?php
    }
}
