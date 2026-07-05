<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('Onedown_Theme_Updater')) :
    final class Onedown_Theme_Updater
    {
        private const MANIFEST_FILE = 'version.json';
        private const RESULT_QUERY_KEY = 'onedown_theme_update_result';

        private static ?self $instance = null;

        public static function instance(): self
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct()
        {
            add_action('admin_post_onedown_save_version_manifest', array($this, 'handle_save_manifest'));
        }

        public function manifest_exists(): bool
        {
            return file_exists($this->manifest_file()) && is_readable($this->manifest_file());
        }

        public function handle_save_manifest(): void
        {
            if (! current_user_can('manage_options')) {
                wp_die(esc_html__('Insufficient permissions.', 'onedown'));
            }

            check_admin_referer('onedown_save_version_manifest');

            if (! $this->manifest_exists()) {
                $this->redirect_with_result($this->manifest_page_url(), 'error', 'version.json 不存在');
            }

            if (! is_writable($this->manifest_file())) {
                $this->redirect_with_result($this->manifest_page_url(), 'error', 'version.json 不可写');
            }

            $current = $this->local_manifest();
            $manifest = $current;

            $manifest['name'] = $this->sanitize_text_input('name', $current['name']);
            $manifest['author'] = $this->sanitize_text_input('author', $current['author']);
            $manifest['author_blog'] = esc_url_raw($this->sanitize_text_input('author_blog', $current['author_blog']));
            $manifest['version'] = $this->sanitize_version($this->sanitize_text_input('version', $current['version']));
            $manifest['changelog'] = $this->sanitize_multiline_list('changelog');
            $manifest['online_update_urls'] = $this->sanitize_url_list('online_update_urls');
            $manifest['external_update_urls'] = $this->sanitize_url_list('external_update_urls');
            $manifest['update_files'] = array();

            if ($manifest['version'] === '') {
                $this->redirect_with_result($this->manifest_page_url(), 'error', '版本号不能为空');
            }

            if (! $this->write_local_manifest($manifest)) {
                $this->redirect_with_result($this->manifest_page_url(), 'error', 'version.json 写入失败');
            }

            $this->sync_style_headers($manifest);

            $this->redirect_with_result($this->manifest_page_url(), 'success', 'version.json 已保存');
        }

        public function render_manifest_panel(): void
        {
            if (! $this->manifest_exists()) {
                echo '<div style="padding:16px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;color:#6b7280;">version.json 不存在，当前不支持版本配置。</div>';
                return;
            }

            $manifest = $this->local_manifest();
            $result = $this->get_result_notice();

            echo '<div style="padding:16px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;">';
            echo '<p style="margin-top:0;color:#4b5563;">后续版本信息统一维护在主题根目录 version.json。</p>';

            if (! empty($result) && $this->is_manifest_result_page()) {
                $this->render_result_notice($result);
            }

            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="onedown_save_version_manifest">';
            wp_nonce_field('onedown_save_version_manifest');
            echo '<table class="form-table" role="presentation"><tbody>';
            $this->render_text_row('name', '名称', $manifest['name']);
            $this->render_text_row('author', '作者', $manifest['author']);
            $this->render_text_row('author_blog', '作者博客', $manifest['author_blog']);
            $this->render_text_row('version', '版本号', $manifest['version']);
            $this->render_textarea_row('changelog', '更新日志', implode("\n", $manifest['changelog']), '每行一条');
            $this->render_textarea_row('online_update_urls', '在线更新地址', implode("\n", $manifest['online_update_urls']), '每行一个，用户在更新页点击按钮自行下载');
            $this->render_textarea_row('external_update_urls', '外链更新地址', implode("\n", $manifest['external_update_urls']), '每行一个，用户在更新页点击按钮自行下载');
            echo '</tbody></table>';
            submit_button('保存到 version.json');
            echo '</form>';
            echo '</div>';
        }

        public function render_update_panel(): void
        {
            $remote_state = $this->fetch_remote_manifest_state();
            $manifest = ! empty($remote_state['manifest']) ? $remote_state['manifest'] : $this->local_manifest();
            $result = $this->get_result_notice();

            echo '<div style="padding:16px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;">';
            echo '<p><strong>当前版本：</strong> ' . esc_html($manifest['version']) . '</p>';
            echo '<p><strong>更新方式：</strong> 用户手动下载更新包后自行覆盖安装</p>';
            echo '<p><strong>远程状态：</strong> ' . esc_html((string) ($remote_state['message'] ?? '')) . '</p>';

            if (! empty($result) && ! $this->is_manifest_result_page()) {
                $this->render_result_notice($result);
            }

            $this->render_download_buttons('在线更新下载', $manifest['online_update_urls'], 'button button-primary');
            $this->render_download_buttons('外链更新下载', $manifest['external_update_urls'], 'button');

            echo '<div style="margin-top:16px;padding:12px;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb;">';
            echo '<strong style="display:block;margin-bottom:8px;">更新日志</strong>';
            echo $this->render_changelog_html($manifest['changelog']);
            echo '</div>';
            echo '</div>';
        }

        private function render_download_buttons(string $title, array $urls, string $button_class): void
        {
            echo '<div style="margin:14px 0 0;">';
            echo '<strong style="display:block;margin-bottom:8px;">' . esc_html($title) . '</strong>';

            if (empty($urls)) {
                echo '<p style="margin:0;color:#6b7280;">未配置下载地址</p>';
                echo '</div>';
                return;
            }

            foreach ($urls as $index => $url) {
                echo '<a class="' . esc_attr($button_class) . '" style="margin:0 8px 8px 0;" target="_blank" rel="noopener noreferrer" href="' . esc_url($url) . '">下载地址 ' . esc_html((string) ($index + 1)) . '</a>';
            }

            echo '</div>';
        }

        private function local_manifest(): array
        {
            $manifest = $this->manifest_exists()
                ? $this->read_manifest_file($this->manifest_file())
                : array();

            return $this->normalize_manifest(array_merge($this->build_default_manifest(), $manifest));
        }

        private function remote_manifest(): array
        {
            $state = $this->fetch_remote_manifest_state();
            return isset($state['manifest']) && is_array($state['manifest']) ? $state['manifest'] : array();
        }

        private function fetch_remote_manifest_state(): array
        {
            $url = $this->remote_manifest_url();
            $state = array(
                'url' => $url,
                'manifest' => array(),
                'message' => '未配置远程 version.json',
                'success' => false,
            );

            if ($url === '') {
                return $state;
            }

            $response = wp_remote_get($url, array(
                'timeout' => 10,
                'sslverify' => false,
            ));

            if (is_wp_error($response)) {
                $state['message'] = '远程拉取失败：' . $response->get_error_message();
                return $state;
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            if ($code < 200 || $code >= 300) {
                $state['message'] = '远程拉取失败：HTTP ' . $code;
                return $state;
            }

            $body = wp_remote_retrieve_body($response);
            if (! is_string($body) || $body === '') {
                $state['message'] = '远程拉取失败：返回内容为空';
                return $state;
            }

            $manifest = json_decode($body, true);
            if (! is_array($manifest)) {
                $state['message'] = '远程拉取失败：version.json 格式无效';
                return $state;
            }

            $state['manifest'] = $this->normalize_manifest($manifest);
            $state['message'] = '远程 version.json 拉取成功';
            $state['success'] = true;

            return $state;
        }

        private function build_default_manifest(): array
        {
            $theme = wp_get_theme();

            return array(
                'name' => $theme->get('Name') ?: 'onedown',
                'author' => $theme->get('Author') ?: '',
                'author_blog' => $theme->get('AuthorURI') ?: '',
                'version' => $theme->get('Version') ?: '1.0.0',
                'changelog' => array(),
                'online_update_urls' => array(),
                'external_update_urls' => array(),
                'update_files' => array(),
            );
        }

        private function read_manifest_file(string $file): array
        {
            if (! file_exists($file) || ! is_readable($file)) {
                return array();
            }

            $content = file_get_contents($file);
            if (! is_string($content) || $content === '') {
                return array();
            }

            $data = json_decode($content, true);
            return is_array($data) ? $data : array();
        }

        private function write_local_manifest(array $manifest): bool
        {
            $manifest = $this->normalize_manifest($manifest);
            $json = wp_json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (! is_string($json) || $json === '') {
                return false;
            }

            return false !== file_put_contents($this->manifest_file(), $json . PHP_EOL);
        }

        private function normalize_manifest(array $manifest): array
        {
            $normalized = array(
                'name' => isset($manifest['name']) ? sanitize_text_field((string) $manifest['name']) : 'onedown',
                'author' => isset($manifest['author']) ? sanitize_text_field((string) $manifest['author']) : '',
                'author_blog' => '',
                'version' => isset($manifest['version']) ? $this->sanitize_version((string) $manifest['version']) : '',
                'changelog' => array(),
                'online_update_urls' => array(),
                'external_update_urls' => array(),
                'update_files' => array(),
            );

            foreach (array('author_blog', 'author_uri', 'author_url') as $key) {
                if (! empty($manifest[$key])) {
                    $normalized['author_blog'] = esc_url_raw((string) $manifest[$key]);
                    break;
                }
            }

            $normalized['changelog'] = $this->normalize_string_list($manifest['changelog'] ?? array());
            $normalized['online_update_urls'] = $this->normalize_url_list($manifest['online_update_urls'] ?? array());
            $normalized['external_update_urls'] = $this->normalize_url_list($manifest['external_update_urls'] ?? array());

            if ($normalized['version'] === '') {
                $normalized['version'] = (string) wp_get_theme()->get('Version');
            }

            return $normalized;
        }

        private function normalize_string_list($values): array
        {
            if (is_string($values)) {
                $values = preg_split('/\r\n|\r|\n/', $values);
            }

            if (! is_array($values)) {
                return array();
            }

            $list = array();
            foreach ($values as $value) {
                $value = sanitize_text_field((string) $value);
                if ($value !== '') {
                    $list[] = $value;
                }
            }

            return array_values(array_unique($list));
        }

        private function normalize_url_list($values): array
        {
            if (is_string($values)) {
                $values = preg_split('/\r\n|\r|\n/', $values);
            }

            if (! is_array($values)) {
                return array();
            }

            $list = array();
            foreach ($values as $value) {
                $url = esc_url_raw(trim((string) $value));
                if ($url !== '') {
                    $list[] = $url;
                }
            }

            return array_values(array_unique($list));
        }

        private function render_changelog_html(array $changelog): string
        {
            if (empty($changelog)) {
                return '<p style="margin:0;color:#6b7280;">暂无更新日志</p>';
            }

            $items = array();
            foreach ($changelog as $item) {
                $items[] = '<li>' . esc_html($item) . '</li>';
            }

            return '<ul style="margin:0 0 0 18px;list-style:disc;">' . implode('', $items) . '</ul>';
        }

        private function render_result_notice(array $result): void
        {
            $color = 'success' === $result['type'] ? '#166534' : '#b91c1c';
            $bg = 'success' === $result['type'] ? '#f0fdf4' : '#fef2f2';
            echo '<div style="margin:12px 0;padding:10px 12px;border-radius:8px;background:' . esc_attr($bg) . ';color:' . esc_attr($color) . ';">' . esc_html($result['message']) . '</div>';
        }

        private function render_text_row(string $name, string $label, string $value): void
        {
            echo '<tr>';
            echo '<th scope="row"><label for="od-' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
            echo '<td><input type="text" class="regular-text" id="od-' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"></td>';
            echo '</tr>';
        }

        private function render_textarea_row(string $name, string $label, string $value, string $desc = ''): void
        {
            echo '<tr>';
            echo '<th scope="row"><label for="od-' . esc_attr($name) . '">' . esc_html($label) . '</label></th>';
            echo '<td>';
            echo '<textarea id="od-' . esc_attr($name) . '" name="' . esc_attr($name) . '" rows="6" class="large-text code">' . esc_textarea($value) . '</textarea>';
            if ($desc !== '') {
                echo '<p class="description">' . esc_html($desc) . '</p>';
            }
            echo '</td>';
            echo '</tr>';
        }

        private function sanitize_text_input(string $key, string $default = ''): string
        {
            return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : $default;
        }

        private function sanitize_multiline_list(string $key): array
        {
            $value = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
            return $this->normalize_string_list($value);
        }

        private function sanitize_url_list(string $key): array
        {
            $value = isset($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
            return $this->normalize_url_list($value);
        }

        private function sanitize_version(string $version): string
        {
            $version = preg_replace('/[^0-9A-Za-z\.\-\_]/', '', trim($version));
            return is_string($version) ? $version : '';
        }

        private function sync_style_headers(array $manifest): void
        {
            $style_file = trailingslashit(get_template_directory()) . 'style.css';
            if (! file_exists($style_file) || ! is_readable($style_file) || ! is_writable($style_file)) {
                return;
            }

            $content = file_get_contents($style_file);
            if (! is_string($content) || $content === '') {
                return;
            }

            $headers = array(
                'Theme Name' => $manifest['name'] ?? '',
                'Author' => $manifest['author'] ?? '',
                'Author URI' => $manifest['author_blog'] ?? '',
                'Version' => $manifest['version'] ?? '',
            );

            foreach ($headers as $header => $value) {
                if ($value === '') {
                    continue;
                }

                $updated = preg_replace('/^([ \t\/*#@]*' . preg_quote($header, '/') . ':\s*).*$/mi', '${1}' . $value, $content, 1);
                if (is_string($updated) && $updated !== '') {
                    $content = $updated;
                }
            }

            file_put_contents($style_file, $content);
        }

        private function get_result_notice(): array
        {
            if (empty($_GET[self::RESULT_QUERY_KEY]) || empty($_GET['message'])) {
                return array();
            }

            return array(
                'type' => sanitize_key(wp_unslash($_GET[self::RESULT_QUERY_KEY])),
                'message' => sanitize_text_field(wp_unslash($_GET['message'])),
            );
        }

        private function redirect_with_result(string $redirect_url, string $type, string $message): void
        {
            wp_safe_redirect(add_query_arg(array(
                self::RESULT_QUERY_KEY => sanitize_key($type),
                'message' => $message,
            ), $redirect_url));
            exit;
        }

        private function is_manifest_result_page(): bool
        {
            return ! empty($_GET['tab']) && sanitize_key(wp_unslash($_GET['tab'])) === 'license-update/license-update-manifest';
        }

        private function manifest_page_url(): string
        {
            return admin_url('admin.php?page=onedown-options#tab=license-update/license-update-manifest');
        }

        private function manifest_file(): string
        {
            return trailingslashit(get_template_directory()) . self::MANIFEST_FILE;
        }

        private function remote_manifest_url(): string
        {
            if (function_exists('onedown_get_default_update_manifest_url')) {
                return (string) onedown_get_default_update_manifest_url();
            }

            return '';
        }
    }
endif;

if (! function_exists('onedown_render_version_manifest_panel')) :
    function onedown_render_version_manifest_panel($field = null): void
    {
        if (! Onedown_Theme_Updater::instance()->manifest_exists()) {
            return;
        }

        $nonce = wp_create_nonce('onedown_load_admin_tab_panel');
        echo '<div class="onedown-lazy-admin-panel" data-panel="version-manifest" data-nonce="' . esc_attr($nonce) . '">';
        echo '<p style="color:#999;">进入当前标签后加载 version.json 面板。</p>';
        echo '</div>';
    }
endif;

if (! function_exists('onedown_render_theme_update_panel')) :
    function onedown_render_theme_update_panel($field = null): void
    {
        $nonce = wp_create_nonce('onedown_load_admin_tab_panel');
        echo '<div class="onedown-lazy-admin-panel" data-panel="updater" data-nonce="' . esc_attr($nonce) . '">';
        echo '<p style="color:#999;">进入当前标签后加载更新面板。</p>';
        echo '</div>';
    }
endif;

if (! function_exists('onedown_render_version_manifest_panel_html')) :
    function onedown_render_version_manifest_panel_html(): void
    {
        Onedown_Theme_Updater::instance()->render_manifest_panel();
    }
endif;

if (! function_exists('onedown_render_theme_update_panel_html')) :
    function onedown_render_theme_update_panel_html(): void
    {
        Onedown_Theme_Updater::instance()->render_update_panel();
    }
endif;

if (! function_exists('onedown_should_boot_theme_updater')) :
    function onedown_should_boot_theme_updater(): bool
    {
        if (! is_admin()) {
            return false;
        }

        global $pagenow;

        if ($pagenow === 'admin-post.php' && ! empty($_REQUEST['action']) && $_REQUEST['action'] === 'onedown_save_version_manifest') {
            return true;
        }

        if ($pagenow === 'admin.php' && ! empty($_GET['page']) && $_GET['page'] === 'onedown-options') {
            return true;
        }

        return false;
    }
endif;

if (onedown_should_boot_theme_updater()) {
    Onedown_Theme_Updater::instance();
}
