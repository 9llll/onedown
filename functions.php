<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('ONEDOWN_TEXT_DOMAIN')) {
    define('ONEDOWN_TEXT_DOMAIN', 'onedown');
}

// 永久授权放行核心函数
if (! function_exists('onedown_license_is_active')) :
function onedown_license_is_active(){
    return true;
}
endif;

if (! function_exists('onedown_bootstrap_version')) :
    function onedown_bootstrap_version()
    {
        $default_version = (string) wp_get_theme()->get('Version');
        $version_file = trailingslashit(get_template_directory()) . 'version.json';

        if (! file_exists($version_file) || ! is_readable($version_file)) {
            return $default_version;
        }

        $content = file_get_contents($version_file);
        if (! is_string($content) || $content === '') {
            return $default_version;
        }

        $data = json_decode($content, true);
        if (! is_array($data) || empty($data['version'])) {
            return $default_version;
        }

        return sanitize_text_field((string) $data['version']);
    }
endif;

define('ONEDOWN_PATH', trailingslashit(get_template_directory()));
define('ONEDOWN_URL', trailingslashit(get_template_directory_uri()));
define('ONEDOWN_VERSION', onedown_bootstrap_version());

/**
 * 获取主题设置选项（带静态缓存，每页只从数据库读取一次）
 */
if (! function_exists('onedown_get_option')) :
    function onedown_get_option($key = '', $default = '')
    {
        static $options = null;
        if (null === $options) {
            $options = get_option('_onedown_options');
        }
        if (is_array($options) && $key !== '' && array_key_exists($key, $options)) {
            return $options[$key];
        }
        return $default;
    }
endif;

/**
 * _pz — 主题设置选项的简短别名
 */
if (! function_exists('_pz')) :
    function _pz($key = '', $default = '')
    {
        return onedown_get_option($key, $default);
    }
endif;

/**
 * 获取前端静态资源缓存版本
 */
if (! function_exists('onedown_asset_cache_version')) :
    function onedown_asset_cache_version()
    {
        $version = get_option('onedown_asset_cache_version', '');

        if ($version === '') {
            $version = defined('ONEDOWN_VERSION') ? (string) ONEDOWN_VERSION : (string) wp_get_theme()->get('Version');
        }

        return (string) $version;
    }
endif;

/**
 * 安全 require 主题文件
 */
if (! function_exists('onedown_require_file')) :
    function onedown_require_file($path)
    {
        $file = get_theme_file_path($path);
        if (file_exists($file)) {
            require_once $file;
        }
    }
endif;

if (! function_exists('onedown_is_light_rest_request')) :
    function onedown_is_light_rest_request()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? urldecode((string) $_SERVER['REQUEST_URI']) : '';
        $rest_route = isset($_GET['rest_route']) ? urldecode((string) $_GET['rest_route']) : '';
        $targets = array($request_uri, $rest_route);

        foreach ($targets as $target) {
            if ($target === '') {
                continue;
            }

            if (strpos($target, '/wp-json/wp/v2/users/me') !== false || strpos($target, '/wp/v2/users/me') !== false) {
                return true;
            }
        }

        return false;
    }
endif;

// 注释远程授权发布接口，移除远程校验
// onedown_require_file('/inc/remote-publish.php');

// 加载主题引导类并启动
if (! onedown_is_light_rest_request()) {
    require get_template_directory() . '/inc/class-onedown-theme.php';
    Onedown_Theme::instance()->boot();
}

add_filter('template_include', 'onedown_topic_template_include', 99);
function onedown_topic_template_include($template)
{
    if (is_tax('topic')) {
        $topic_template = locate_template('templates/taxonomy-topic.php');
        if ($topic_template) {
            return $topic_template;
        }
    }

    return $template;
}

/**
 * 缓存版 get_pages — 按页面模板查找页面，结果缓存 1 小时
 */
if (! function_exists('onedown_cached_pages_by_template')) :
    function onedown_cached_pages_by_template($template)
    {
        $cache_key = 'od_template_page_' . md5($template);
        $pages     = get_transient($cache_key);
        if (false === $pages) {
            $pages = get_pages(array(
                'meta_key'   => '_wp_page_template',
                'meta_value' => $template,
                'number'     => 1,
            ));
            set_transient($cache_key, $pages, HOUR_IN_SECONDS);
        }
        return $pages;
    }
endif;

/**
 * 获取用户中心页面 URL
 *
 * @param array $args 查询参数.
 * @return string
 */
if (! function_exists('onedown_user_center_url')) :
    function onedown_user_center_url($args = array())
    {
        $pages = onedown_cached_pages_by_template('page-templates/user-center.php');

        if (! empty($pages)) {
            $url = get_permalink($pages[0]->ID);
        } else {
            $url = home_url('/user-center/');
        }

        if (! empty($args)) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }
endif;

/**
 * 获取VIP会员页面 URL
 *
 * @return string
 */
if (! function_exists('onedown_vip_page_url')) :
    function onedown_vip_page_url()
    {
        $pages = onedown_cached_pages_by_template('page-templates/vip.php');

        if (! empty($pages)) {
            return get_permalink($pages[0]->ID);
        }

        return add_query_arg('od_vip', 1, home_url('/'));
    }
endif;

/**
 * 清理主题页面模板查询缓存
 */
if (! function_exists('onedown_clear_template_page_cache')) :
    function onedown_clear_template_page_cache()
    {
        $templates = array(
            'page-templates/user-center.php',
            'page-templates/vip.php',
            'page-templates/submit-post.php',
            'page-templates/download.php',
        );

        foreach ($templates as $template) {
            delete_transient('od_template_page_' . md5($template));
        }
    }
endif;

/**
 * 强制清空主题缓存
 */
if (! function_exists('onedown_force_clear_cache')) :
    function onedown_force_clear_cache()
    {
        update_option('onedown_asset_cache_version', (string) time(), false);

        if (function_exists('onedown_flush_post_query_cache')) {
            onedown_flush_post_query_cache();
        }

        onedown_clear_template_page_cache();

        delete_transient('onedown_sitemap_cache');
        delete_transient('onedown_theme_update_data');

        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }
endif;

/**
 * 后台渲染强制清空缓存按钮
 *
 * @return void
 */
if (! function_exists('onedown_render_force_clear_cache_action')) :
    function onedown_render_force_clear_cache_action()
    {
        $redirect_url = admin_url('admin.php?page=onedown-options');
        ?>
        <div style="padding:12px 0 4px;">
            <?php if (isset($_GET['cache_cleared']) && $_GET['cache_cleared'] === '1') : ?>
                <div class="notice notice-success inline" style="margin:0 0 12px;">
                    <p><?php esc_html_e('缓存已强制清空。', 'onedown'); ?></p>
                </div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="onedown_force_clear_cache">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_url); ?>">
                <?php wp_nonce_field('onedown_force_clear_cache', 'onedown_force_clear_cache_nonce'); ?>
                <button type="submit" class="button button-secondary"><?php esc_html_e('强制清空缓存', 'onedown'); ?></button>
                <p style="margin:8px 0 0;color:#646970;"><?php esc_html_e('用于立即清理主题查询缓存、页面模板缓存、站点地图缓存和对象缓存。', 'onedown'); ?></p>
            </form>
        </div>
        <?php
    }
endif;

/**
 * 获取全部分类页 URL
 *
 * @return string
 */
if (! function_exists('onedown_cates_list_url')) :
    function onedown_cates_list_url()
    {
        return home_url('/cates/');
    }
endif;

/**
 * 移动端底部 Tabbar 是否启用
 *
 * @return bool
 */
if (! function_exists('onedown_mobile_tabbar_enabled')) :
    function onedown_mobile_tabbar_enabled()
    {
        return (bool) _pz('mobile_tabbar_enabled', true);
    }
endif;

/**
 * 移动端底部 Tabbar 是否显示文字
 *
 * @return bool
 */
if (! function_exists('onedown_mobile_tabbar_show_label')) :
    function onedown_mobile_tabbar_show_label()
    {
        return (bool) _pz('mobile_tabbar_show_label', true);
    }
endif;

/**
 * 规范化图标类名，兼容 CSF icon 字段返回值
 *
 * @param string $icon 原始图标类名
 * @return string
 */
if (! function_exists('onedown_mobile_tabbar_normalize_icon')) :
    function onedown_mobile_tabbar_normalize_icon($icon)
    {
        $icon = trim((string) $icon);
        $icon = preg_replace('/\b(?:fas|far|fab)\s+/', '', $icon);

        if ($icon === '') {
            return '';
        }

        if (strpos($icon, 'fa ') === false && preg_match('/\bfa-[a-z0-9-]+\b/i', $icon, $matches)) {
            $icon = 'fa ' . $matches[0];
        }

        return trim($icon);
    }
endif;

/**
 * 获取移动端底部 Tabbar 默认项
 *
 * @return array
 */
if (! function_exists('onedown_mobile_tabbar_default_items')) :
    function onedown_mobile_tabbar_default_items()
    {
        return array(
            'home'     => array(
                'key'   => 'home',
                'title' => '首页',
                'icon'  => 'fa fa-home',
                'url'   => home_url('/'),
            ),
            'category' => array(
                'key'   => 'category',
                'title' => '分类',
                'icon'  => 'fa fa-th-large',
                'url'   => onedown_cates_list_url(),
            ),
            'vip'      => array(
                'key'   => 'vip',
                'title' => 'VIP',
                'icon'  => 'fa fa-diamond',
                'url'   => onedown_vip_page_url(),
            ),
            'user'     => array(
                'key'   => 'user',
                'title' => '我的',
                'icon'  => 'fa fa-user-o',
                'url'   => onedown_user_center_url(),
            ),
        );
    }
endif;

/**
 * 获取移动端底部 Tabbar 配置项
 *
 * @return array
 */
if (! function_exists('onedown_mobile_tabbar_items')) :
    function onedown_mobile_tabbar_items()
    {
        $items = array();

        foreach (onedown_mobile_tabbar_default_items() as $key => $default) {
            $custom_title = trim((string) _pz('mobile_tabbar_' . $key . '_title', ''));
            $custom_icon  = onedown_mobile_tabbar_normalize_icon(_pz('mobile_tabbar_' . $key . '_icon', ''));
            $custom_url   = trim((string) _pz('mobile_tabbar_' . $key . '_url', ''));

            $item          = $default;
            $item['title'] = $custom_title !== '' ? $custom_title : $default['title'];
            $item['icon']  = $custom_icon !== '' ? $custom_icon : $default['icon'];
            $item['url']   = $custom_url !== '' ? $custom_url : $default['url'];

            if ($key === 'user' && ! is_user_logged_in() && $custom_url === '') {
                $item['url'] = function_exists('onedown_get_sign_url') ? onedown_get_sign_url('signin') : wp_login_url();
            }

            $items[] = $item;
        }

        return $items;
    }
endif;

/**
 * 判断移动端底部 Tabbar 当前项是否激活
 *
 * @param string $key tab 键名
 * @return bool
 */
if (! function_exists('onedown_mobile_tabbar_item_is_active')) :
    function onedown_mobile_tabbar_item_is_active($key)
    {
        switch ($key) {
            case 'home':
                return is_front_page() || is_home();

            case 'category':
                return (bool) get_query_var('od_cates_list') || is_category() || is_tag() || is_tax();

            case 'vip':
                return (bool) get_query_var('od_vip') || is_page_template('page-templates/vip.php');

            case 'user':
                return (bool) get_query_var('user_center')
                    || (bool) get_query_var('od_sign')
                    || is_page_template('page-templates/user-center.php')
                    || is_page_template('pages/user-sign.php');
        }

        return false;
    }
endif;

/**
 * 输出移动端底部 Tabbar
 */
if (! function_exists('onedown_render_mobile_tabbar')) :
    function onedown_render_mobile_tabbar()
    {
        if (! onedown_mobile_tabbar_enabled()) {
            return;
        }

        $items = onedown_mobile_tabbar_items();
        if (empty($items)) {
            return;
        }
        $nav_class = 'mobile-tabbar' . (onedown_mobile_tabbar_show_label() ? ' is-label-visible' : ' is-label-hidden');
        ?>
        <nav class="<?php echo esc_attr($nav_class); ?>" aria-label="移动底部导航">
            <?php foreach ($items as $item) : ?>
                <?php
                $is_active = onedown_mobile_tabbar_item_is_active($item['key']);
                $class     = 'mobile-tabbar__item' . ($is_active ? ' is-active' : '');
                if ($item['key'] === 'user' && ! is_user_logged_in()) {
                    $item['url'] = function_exists('onedown_get_sign_url') ? onedown_get_sign_url('signin') : wp_login_url();
                }
                ?>
                <a class="<?php echo esc_attr($class); ?>" href="<?php echo esc_url($item['url']); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
                    <span class="mobile-tabbar__icon"><i class="<?php echo esc_attr($item['icon']); ?>"></i></span>
                    <span class="mobile-tabbar__label"><?php echo esc_html($item['title']); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }
endif;

add_filter('body_class', 'onedown_mobile_tabbar_body_class');
if (! function_exists('onedown_mobile_tabbar_body_class')) :
    function onedown_mobile_tabbar_body_class($classes)
    {
        if (onedown_mobile_tabbar_enabled()) {
            $classes[] = 'has-mobile-tabbar';
            if (! onedown_mobile_tabbar_show_label()) {
                $classes[] = 'has-mobile-tabbar-no-label';
            }
        }

        return $classes;
    }
endif;

/**
 * VIP 页面重写规则
 */
if (! function_exists('onedown_vip_rewrite_rules')) :
    function onedown_vip_rewrite_rules($wp_rewrite)
    {
        if (get_option('permalink_structure')) {
            $new_rules['vip/?$'] = 'index.php?od_vip=1';
            $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
        }
    }
    add_action('generate_rewrite_rules', 'onedown_vip_rewrite_rules');
endif;

/**
 * 注册 VIP 页面查询变量
 */
if (! function_exists('onedown_add_vip_query_vars')) :
    function onedown_add_vip_query_vars($public_query_vars)
    {
        if (! is_admin()) {
            $public_query_vars[] = 'od_vip';
        }
        return $public_query_vars;
    }
    add_filter('query_vars', 'onedown_add_vip_query_vars');
endif;

/**
 * template_redirect 拦截 VIP 页面路由
 */
if (! function_exists('onedown_vip_template_redirect')) :
    function onedown_vip_template_redirect()
    {
        $od_vip = get_query_var('od_vip');
        if ($od_vip) {
            global $wp_query;
            $wp_query->is_home     = false;
            $wp_query->is_404      = false;
            $wp_query->is_page     = true;
            $wp_query->is_singular = true;

            $template = get_theme_file_path('page-templates/vip.php');
            if (file_exists($template)) {
                load_template($template);
                exit;
            }
        }
    }
    add_action('template_redirect', 'onedown_vip_template_redirect', 5);
endif;

/**
 * 登录页重写规则
 */
if (! function_exists('onedown_sign_rewrite_rules')) :
    function onedown_sign_rewrite_rules($wp_rewrite)
    {
        if (get_option('permalink_structure')) {
            $new_rules['sign/?$'] = 'index.php?od_sign=1';
            $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
        }
    }
    add_action('generate_rewrite_rules', 'onedown_sign_rewrite_rules');
endif;

/**
 * 注册登录页查询变量
 */
if (! function_exists('onedown_add_sign_query_vars')) :
    function onedown_add_sign_query_vars($public_query_vars)
    {
        if (! is_admin()) {
            $public_query_vars[] = 'od_sign';
        }
        return $public_query_vars;
    }
    add_filter('query_vars', 'onedown_add_sign_query_vars');
endif;

/**
 * template_redirect 拦截登录页路由
 */
if (! function_exists('onedown_sign_template_redirect')) :
    function onedown_sign_template_redirect()
    {
        $od_sign = get_query_var('od_sign');
        if ($od_sign) {
            global $wp_query;
            $wp_query->is_home     = false;
            $wp_query->is_404      = false;
            $wp_query->is_page     = true;
            $wp_query->is_singular = true;

            $template = get_theme_file_path('pages/user-sign.php');
            if (file_exists($template)) {
                load_template($template);
                exit;
            }
        }
    }
    add_action('template_redirect', 'onedown_sign_template_redirect', 5);
endif;

/**
 * 用户中心重写规则
 */
if (! function_exists('onedown_user_center_rewrite_rules')) :
    function onedown_user_center_rewrite_rules($wp_rewrite)
    {
        if (get_option('permalink_structure')) {
            $new_rules['user-center$']             = 'index.php?user_center=1';
            $new_rules['user-center/([A-Za-z]+)$'] = 'index.php?user_center=$matches[1]';
            $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
        }
    }
    add_action('generate_rewrite_rules', 'onedown_user_center_rewrite_rules');
endif;

/**
 * 注册 user_center 查询变量
 */
if (! function_exists('onedown_add_user_center_query_vars')) :
    function onedown_add_user_center_query_vars($public_query_vars)
    {
        if (! is_admin()) {
            $public_query_vars[] = 'user_center';
        }
        return $public_query_vars;
    }
    add_filter('query_vars', 'onedown_add_user_center_query_vars');
endif;

/**
 * template_redirect 拦截 user_center 路由
 */
if (! function_exists('onedown_user_center_template_redirect')) :
    function onedown_user_center_template_redirect()
    {
        $user_center = get_query_var('user_center');
        if ($user_center) {
            global $wp_query;
            $wp_query->is_home    = false;
            $wp_query->is_404     = false;
            $wp_query->is_page    = true;
            $wp_query->is_singular = true;

            $template = get_theme_file_path('page-templates/user-center.php');
            if (file_exists($template)) {
                load_template($template);
                exit;
            }
        }
    }
    add_action('template_redirect', 'onedown_user_center_template_redirect', 5);
endif;

/**
 * ═══════════════════════════════════════
 * 投稿页面虚拟路由
 * ═══════════════════════════════════════
 */

/**
 * 获取投稿页面 URL
 *
 * @return string
 */
if (! function_exists('onedown_submit_post_url')) :
    function onedown_submit_post_url()
    {
        $pages = onedown_cached_pages_by_template('page-templates/submit-post.php');

        if (! empty($pages)) {
            return get_permalink($pages[0]->ID);
        }

        return home_url('/submit-post/');
    }
endif;

/**
 * 投稿页面重写规则
 */
if (! function_exists('onedown_submit_post_rewrite_rules')) :
    function onedown_submit_post_rewrite_rules($wp_rewrite)
    {
        if (get_option('permalink_structure')) {
            $new_rules['submit-post$'] = 'index.php?submit_post=1';
            $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
        }
    }
    add_action('generate_rewrite_rules', 'onedown_submit_post_rewrite_rules');
endif;

/**
 * 注册 submit_post 查询变量
 */
if (! function_exists('onedown_add_submit_post_query_vars')) :
    function onedown_add_submit_post_query_vars($public_query_vars)
    {
        if (! is_admin()) {
            $public_query_vars[] = 'submit_post';
        }
        return $public_query_vars;
    }
    add_filter('query_vars', 'onedown_add_submit_post_query_vars');
endif;

/**
 * template_redirect 拦截 submit_post 路由
 */
if (! function_exists('onedown_submit_post_template_redirect')) :
    function onedown_submit_post_template_redirect()
    {
        if (get_query_var('submit_post')) {
            global $wp_query;
            $wp_query->is_home    = false;
            $wp_query->is_404     = false;
            $wp_query->is_page    = true;
            $wp_query->is_singular = true;

            $template = get_theme_file_path('page-templates/submit-post.php');
            if (file_exists($template)) {
                load_template($template);
                exit;
            }
        }
    }
    add_action('template_redirect', 'onedown_submit_post_template_redirect', 5);
endif;

/**
 * ═══════════════════════════════════════
 * 下载页面虚拟路由（内置，无需用户创建页面）
 * ═══════════════════════════════════════
 */

/**
 * 获取下载页面 URL（SEO 友好格式）
 *
 * 生成 /download/{post_id}/{post_slug}/ 格式的 URL，符合 SEO 规范。
 *
 * @param array $args 查询参数.
 * @return string
 */
if (! function_exists('onedown_download_page_url')) :
    function onedown_download_page_url($args = array())
    {
        $mode = function_exists('onedown_get_download_redirect_mode')
            ? onedown_get_download_redirect_mode()
            : 'normal';

        if ($mode === 'custom') {
            $page_id = onedown_get_option('download_redirect_page', '');
            if ($page_id && '__builtin__' !== $page_id) {
                $url = get_permalink(intval($page_id));
            }
        }

        if (empty($url)) {
            $pages = onedown_cached_pages_by_template('page-templates/download.php');
            if (! empty($pages)) {
                $url = get_permalink($pages[0]->ID);
            } else {
                $url = home_url('/download/');
            }
        }

        // 如果传递了 post_id，使用 SEO 友好的 /download/{id}/{slug}/ 格式
        if (! empty($args['post_id'])) {
            $post_id = intval($args['post_id']);
            if ($post_id > 0) {
                $url = home_url('/download/' . $post_id . '.html');
            }
            unset($args['post_id']);
        }
        if (! empty($args)) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }
endif;

/**
 * 下载页面重写规则（SEO 友好）
 *
 * 支持 URL 格式：
 *   /download/                  → 下载页面列表
 *   /download/{id}/{slug}/      → 指定文章的下载落地页
 *   /download/go/{id}/{item}/{key}/ → 安全下载重定向（noindex）
 */
if (! function_exists('onedown_download_rewrite_rules')) :
    function onedown_download_rewrite_rules($wp_rewrite)
    {
        if (get_option('permalink_structure')) {
            // 下载重定向中转：/download/go/{post_id}/{item_index}/{nonce}/
            $new_rules['download/go/(\d+)/(\d+)/([^/]+)/?$'] = 'index.php?od_down=$matches[1]&item=$matches[2]&key=$matches[3]';

            // Short download landing page: /download/{post_id}.html
            $new_rules['download/(\d+)\.html$'] = 'index.php?od_download=1&od_dl_post=$matches[1]';

            // Backward compatibility: /download/{post_id}/
            $new_rules['download/(\d+)/?$'] = 'index.php?od_download=1&od_dl_post=$matches[1]';

            // Backward compatibility: /download/{post_id}/{slug}/
            $new_rules['download/(\d+)/([^/]+)/?$'] = 'index.php?od_download=1&od_dl_post=$matches[1]';

            // 通用下载页面：/download/
            $new_rules['download/?$'] = 'index.php?od_download=1';

            $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
        }
    }
    add_action('generate_rewrite_rules', 'onedown_download_rewrite_rules');
endif;

/**
 * 注册下载相关查询变量
 */
if (! function_exists('onedown_add_download_query_vars')) :
    function onedown_add_download_query_vars($public_query_vars)
    {
        if (! is_admin()) {
            $public_query_vars[] = 'od_download';
            $public_query_vars[] = 'od_dl_post';
            $public_query_vars[] = 'od_down';
            $public_query_vars[] = 'item';
            $public_query_vars[] = 'key';
        }
        return $public_query_vars;
    }
    add_filter('query_vars', 'onedown_add_download_query_vars');
endif;

/**
 * template_redirect 拦截 od_download 路由，加载下载页面
 */
if (! function_exists('onedown_download_template_redirect')) :
    function onedown_download_template_redirect()
    {
        $od_download = get_query_var('od_download');
        if ($od_download) {
            // 从路径中获取 post_id（支持 SEO 友好的 /download/{id}/{slug}/ 格式）
            $dl_post_id = intval(get_query_var('od_dl_post'));
            if ($dl_post_id) {
                set_query_var('od_dl_post', 0);
                $_GET['post_id'] = strval($dl_post_id);
            }

            $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : '';
            if ($view === 'qrcode' && function_exists('onedown_render_download_qrcode_page')) {
                onedown_render_download_qrcode_page();
                exit;
            }

            global $wp_query;
            $wp_query->is_home    = false;
            $wp_query->is_404     = false;
            $wp_query->is_page    = true;
            $wp_query->is_singular = true;

            $template = get_theme_file_path('page-templates/download.php');
            if (file_exists($template)) {
                load_template($template);
                exit;
            }
        }
    }
    add_action('template_redirect', 'onedown_download_template_redirect', 5);
endif;

/**
 * 处理用户资料更新
 */
add_action('admin_post_onedown_update_profile', 'onedown_handle_profile_update');
add_action('admin_post_onedown_clear_post_cache', 'onedown_handle_clear_post_cache');
add_action('admin_post_onedown_force_clear_cache', 'onedown_handle_force_clear_cache');
if (! function_exists('onedown_handle_profile_update')) :
    function onedown_handle_profile_update()
    {
        if (! is_user_logged_in()) {
            wp_safe_redirect(wp_login_url(home_url('/')));
            exit;
        }

        if (! isset($_POST['onedown_profile_nonce']) || ! wp_verify_nonce($_POST['onedown_profile_nonce'], 'onedown_profile_action')) {
            wp_die('安全验证失败，请重试。');
        }

        $user_id   = get_current_user_id();
        $user_data = array('ID' => $user_id);
        $errors    = array();

        // 更新昵称
        if (! empty($_POST['display_name'])) {
            $user_data['display_name'] = sanitize_text_field($_POST['display_name']);
        }

        // 更新邮箱
        if (! empty($_POST['email']) && is_email($_POST['email'])) {
            $user_data['user_email'] = sanitize_email($_POST['email']);
        }

        // 更新个人简介
        if (isset($_POST['description'])) {
            update_user_meta($user_id, 'description', sanitize_textarea_field($_POST['description']));
        }

        // 更新密码
        if (! empty($_POST['pass1']) || ! empty($_POST['pass2'])) {
            if ($_POST['pass1'] !== $_POST['pass2']) {
                $errors[] = '两次密码输入不一致';
            } elseif (strlen($_POST['pass1']) < 6) {
                $errors[] = '密码长度至少 6 位';
            } else {
                $user_data['user_pass'] = $_POST['pass1'];
            }
        }

        if (! empty($errors)) {
            $redirect_url = add_query_arg(array(
                'tab'              => 'profile',
                'profile_error'    => urlencode(implode(', ', $errors)),
            ), onedown_user_center_url());
        } else {
            if (count($user_data) > 1) {
                wp_update_user($user_data);
            }
            $redirect_url = isset($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : onedown_user_center_url(array('tab' => 'profile'));
        }

        wp_safe_redirect($redirect_url);
        exit;
    }
endif;

if (! function_exists('onedown_handle_clear_post_cache')) :
    function onedown_handle_clear_post_cache()
    {
        if (! current_user_can('manage_options')) {
            wp_die('权限不足。');
        }

        if (! isset($_POST['onedown_clear_post_cache_nonce']) || ! wp_verify_nonce($_POST['onedown_clear_post_cache_nonce'], 'onedown_clear_post_cache')) {
            wp_die('安全验证失败，请重试。');
        }

        if (function_exists('onedown_flush_post_query_cache')) {
            onedown_flush_post_query_cache();
        }

        $redirect_url = admin_url('admin.php?page=onedown-options&cache_cleared=1');

        if (! empty($_POST['redirect_to'])) {
            $redirect_url = add_query_arg('cache_cleared', '1', esc_url_raw(wp_unslash($_POST['redirect_to'])));
        }

        wp_safe_redirect($redirect_url);
        exit;
    }
endif;

if (! function_exists('onedown_handle_force_clear_cache')) :
    function onedown_handle_force_clear_cache()
    {
        if (! current_user_can('manage_options')) {
            wp_die('权限不足。');
        }

        if (! isset($_POST['onedown_force_clear_cache_nonce']) || ! wp_verify_nonce($_POST['onedown_force_clear_cache_nonce'], 'onedown_force_clear_cache')) {
            wp_die('安全验证失败，请重试。');
        }

        onedown_force_clear_cache();

        $redirect_url = admin_url('admin.php?page=onedown-options&cache_cleared=1');

        if (! empty($_POST['redirect_to'])) {
            $redirect_url = add_query_arg('cache_cleared', '1', esc_url_raw(wp_unslash($_POST['redirect_to'])));
        }

        wp_safe_redirect($redirect_url);
        exit;
    }
endif;

// ═══════════════════════════════════════
// 外链跳转中转 (go.php) 相关功能
// ═══════════════════════════════════════

/**
 * 注册 golink 查询变量
 */
if (! function_exists('onedown_add_golink_query_vars')) :
    function onedown_add_golink_query_vars($public_query_vars)
    {
        if (! is_admin()) {
            $public_query_vars[] = 'golink';
        }
        return $public_query_vars;
    }
    add_filter('query_vars', 'onedown_add_golink_query_vars');
endif;

/**
 * template_redirect 拦截 golink 路由，加载 go.php
 */
if (! function_exists('onedown_golink_template_redirect')) :
    function onedown_golink_template_redirect()
    {
        $golink = get_query_var('golink');
        if ($golink) {
            global $wp_query;
            $wp_query->is_home = false;
            $wp_query->is_page = true;

            $template = get_theme_file_path('page-templates/go.php');
            if (file_exists($template)) {
                load_template($template);
                exit;
            }
        }
    }
    add_action('template_redirect', 'onedown_golink_template_redirect', 5);
endif;

/**
 * 生成中转跳转 URL
 *
 * @param string $url 原始外部链接
 * @return string 经过 base64 编码的中转链接
 */
if (! function_exists('onedown_get_gourl')) :
    function onedown_get_gourl($url)
    {
        $url = base64_encode($url);
        return esc_url(home_url('?golink=' . $url));
    }
endif;

/**
 * ═══════════════════════════════════════
 * 全部标签列表页 /tags/
 * ═══════════════════════════════════════
 */

/**
 * 全部标签列表重写规则
 */
if (! function_exists('onedown_tags_list_rewrite_rules')) :
    function onedown_tags_list_rewrite_rules($wp_rewrite)
    {
        if (get_option('permalink_structure')) {
            $new_rules['tags/?$'] = 'index.php?od_tags_list=1';
            $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
        }
    }
    add_action('generate_rewrite_rules', 'onedown_tags_list_rewrite_rules');
endif;

/**
 * 注册 od_tags_list 查询变量
 */
if (! function_exists('onedown_add_tags_list_query_vars')) :
    function onedown_add_tags_list_query_vars($public_query_vars)
    {
        if (! is_admin()) {
            $public_query_vars[] = 'od_tags_list';
        }
        return $public_query_vars;
    }
    add_filter('query_vars', 'onedown_add_tags_list_query_vars');
endif;

/**
 * template_redirect 拦截全部标签列表路由
 */
if (! function_exists('onedown_tags_list_template_redirect')) :
    function onedown_tags_list_template_redirect()
    {
        if (get_query_var('od_tags_list')) {
            global $wp_query;
            $wp_query->is_home    = false;
            $wp_query->is_404     = false;
            $wp_query->is_page    = true;
            $wp_query->is_singular = true;

            $template = get_theme_file_path('page-templates/tags-list.php');
            if (file_exists($template)) {
                load_template($template);
                exit;
            }
        }
    }
    add_action('template_redirect', 'onedown_tags_list_template_redirect', 5);
endif;

/**
 * All categories list page: /cates/
 */
if (! function_exists('onedown_cates_list_rewrite_rules')) :
    function onedown_cates_list_rewrite_rules($wp_rewrite)
    {
        if (get_option('permalink_structure')) {
            $new_rules['cates/?$'] = 'index.php?od_cates_list=1';
            $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
        }
    }
    add_action('generate_rewrite_rules', 'onedown_cates_list_rewrite_rules');
endif;

if (! function_exists('onedown_add_cates_list_query_vars')) :
    function onedown_add_cates_list_query_vars($public_query_vars)
    {
        if (! is_admin()) {
            $public_query_vars[] = 'od_cates_list';
        }
        return $public_query_vars;
    }
    add_filter('query_vars', 'onedown_add_cates_list_query_vars');
endif;

if (! function_exists('onedown_cates_list_template_redirect')) :
    function onedown_cates_list_template_redirect()
    {
        if (get_query_var('od_cates_list')) {
            global $wp_query;
            $wp_query->is_home     = false;
            $wp_query->is_404      = false;
            $wp_query->is_page     = true;
            $wp_query->is_singular = true;

            $template = get_theme_file_path('page-templates/cates-list.php');
            if (file_exists($template)) {
                load_template($template);
                exit;
            }
        }
    }
    add_action('template_redirect', 'onedown_cates_list_template_redirect', 5);
endif;

if (! function_exists('onedown_get_url_top_host')) :
    function onedown_get_url_top_host($url = '')
    {
        if (! $url) {
            return $_SERVER['HTTP_HOST'];
        }
        $host = parse_url($url, PHP_URL_HOST);
        return $host ? $host : $url;
    }
endif;

if (! function_exists('onedown_is_go_link')) :
    function onedown_is_go_link($url)
    {
        if (strpos($url, '://') === false) {
            return false;
        }

        if (strpos($url, onedown_get_url_top_host()) !== false) {
            return false;
        }

        $exclude_domain = _pz('go_link_exclude_domain', '');
        if ($exclude_domain) {
            $exclude_domains = preg_split('/,|,|\s|\n/', $exclude_domain);
            $url_host        = onedown_get_url_top_host($url);
            if (in_array($url_host, $exclude_domains, true)) {
                return false;
            }
        }

        return true;
    }
endif;

/**
 * 替换文本/HTML 中的外链为中转链接
 *
 * @param string $text  要处理的文本/HTML
 * @param bool   $link  如果为 true，将 $text 本身当作单个 URL 处理
 * @return string
 */
if (! function_exists('onedown_go_link')) :
    function onedown_go_link($text = '', $link = false)
    {
        if (! $text || ! _pz('go_link_s', false)) {
            return $text;
        }

        if ($link) {
            if (onedown_is_go_link($text)) {
                $text = onedown_get_gourl($text);
            }
            return $text;
        }

        // 替换 HTML 中所有 <a href="..."> 或 <a href='...'> 的外链
        preg_match_all('/<a(.*?)href=(["\'])(.*?)\2(.*?)>/', $text, $matches);
        if ($matches && ! empty($matches[3])) {
            foreach ($matches[3] as $i => $val) {
                if (onedown_is_go_link($val)) {
                    $quote = $matches[2][$i];
                    $text  = str_replace("href=" . $quote . $val . $quote, "href=" . $quote . onedown_get_gourl($val) . $quote, $text);
                }
            }
        }
        return $text;
    }
endif;

/**
 * 文章内容外链替换（过滤器）
 */
if (_pz('go_link_s', false) && _pz('go_link_post', false)) {
    add_filter('the_content', 'onedown_the_content_go_link', 999);
    if (! function_exists('onedown_the_content_go_link')) :
        function onedown_the_content_go_link($content)
        {
            if (! is_singular('post') || ! in_the_loop() || ! is_main_query() || ! class_exists('DOMDocument')) {
                return $content;
            }

            $root_id = 'onedown-go-link-content-root';
            $html = '<?xml encoding="utf-8" ?><div id="' . $root_id . '">' . $content . '</div>';

            $previous_errors = libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $loaded = $dom->loadHTML($html);
            libxml_clear_errors();
            libxml_use_internal_errors($previous_errors);

            if (! $loaded) {
                return $content;
            }

            $xpath = new DOMXPath($dom);
            $links = $xpath->query('//*[@id="' . $root_id . '"]//a[@href and not(ancestor::*[contains(concat(" ", normalize-space(@class), " "), " onedown-download-box ")])]');

            if ($links) {
                foreach ($links as $link) {
                    $url = $link->getAttribute('href');
                    if (onedown_is_go_link($url)) {
                        $link->setAttribute('href', onedown_get_gourl($url));
                        if (_pz('go_link_new_tab', false)) {
                            $link->setAttribute('target', '_blank');
                            $rel = trim($link->getAttribute('rel'));
                            $rel_values = preg_split('/\s+/', $rel, -1, PREG_SPLIT_NO_EMPTY);
                            $rel_values = array_unique(array_merge($rel_values, array('noopener', 'noreferrer')));
                            $link->setAttribute('rel', implode(' ', $rel_values));
                        } else {
                            $link->removeAttribute('target');
                        }
                    }
                }
            }

            $root = $dom->getElementById($root_id);
            if (! $root) {
                return $content;
            }

            $new_content = '';
            foreach ($root->childNodes as $child) {
                $new_content .= $dom->saveHTML($child);
            }

            return $new_content;
        }
    endif;
}

/**
 * 评论者链接重定向
 */
if (_pz('go_link_s', false)) {
    if (! function_exists('onedown_comment_go_link')) :
        function onedown_comment_go_link($text = '')
        {
            return onedown_go_link($text);
        }
    endif;
}

/**
 * ═══════════════════════════════════════
 * 页面模板移动向后兼容
 * ═══════════════════════════════════════
 *
 * 将页面模板从根目录迁移到 page-templates/ 后，已分配旧模板路径的页面
 * 仍可通过此过滤器正常加载。
 */

/**
 * 支持 .html 后缀页面URL — 在 WordPress 解析请求前将 pagename.html 转为 pagename
 */
if (! function_exists('onedown_html_page_request')) :
    add_filter('request', 'onedown_html_page_request');
    function onedown_html_page_request($query_vars)
    {
        if (isset($query_vars['pagename'])) {
            $query_vars['pagename'] = preg_replace('#/default\.html$#', '', $query_vars['pagename']);
            $query_vars['pagename'] = preg_replace('/\.html$/', '', $query_vars['pagename']);
        }
        if (isset($query_vars['name'])) {
            $query_vars['name'] = preg_replace('/\.html$/', '', $query_vars['name']);
        }
        return $query_vars;
    }
endif;

/**
 * 页面链接添加 .html 后缀
 * 使所有页面链接格式为 xxx.com/页面名称.html
 */
if (! function_exists('onedown_page_link_html_suffix')) :
    add_filter('page_link', 'onedown_page_link_html_suffix', 10, 2);
    function onedown_page_link_html_suffix($link, $post_id)
    {
        if (get_post_type($post_id) === 'page') {
            $link = user_trailingslashit($link);
            $link = rtrim($link, '/') . '.html';
        }
        return $link;
    }
endif;

/**
 * 菜单中站内链接自动添加 .html 后缀
 */
if (! function_exists('onedown_build_category_default_path')) :
    function onedown_build_category_default_path($term)
    {
        $term = is_object($term) ? $term : get_category($term);
        if (! $term || is_wp_error($term)) {
            return '';
        }

        $path = $term->slug;
        if (! empty($term->parent) && intval($term->parent) !== intval($term->term_id)) {
            $parents = get_category_parents($term->parent, false, '/', true);
            if (! is_wp_error($parents)) {
                $path = $parents . $path;
            }
        }

        return trim($path, '/');
    }
endif;

if (! function_exists('onedown_category_default_html_link')) :
    add_filter('category_link', 'onedown_category_default_html_link', 10, 2);
    function onedown_category_default_html_link($link, $term_id)
    {
        $path = onedown_build_category_default_path($term_id);
        if (! $path) {
            return $link;
        }

        return home_url('/' . $path . '/default.html');
    }
endif;

if (! function_exists('onedown_category_default_html_rewrite_rules')) :
    function onedown_category_default_html_rewrite_rules($wp_rewrite)
    {
        if (! get_option('permalink_structure')) {
            return;
        }

        $rules      = array();
        $categories = get_categories(array('hide_empty' => false));

        foreach ($categories as $category) {
            $path = onedown_build_category_default_path($category);
            if (! $path) {
                continue;
            }

            $rules['(' . $path . ')/default\.html/page/?([0-9]{1,})/?$'] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
            $rules['(' . $path . ')/page/?([0-9]{1,})/?$']               = 'index.php?category_name=$matches[1]&paged=$matches[2]';
            $rules['(' . $path . ')/default\.html$'] = 'index.php?category_name=$matches[1]';
        }

        if ($rules) {
            $wp_rewrite->rules = $rules + $wp_rewrite->rules;
        }
    }
    add_action('generate_rewrite_rules', 'onedown_category_default_html_rewrite_rules');
endif;

if (! function_exists('onedown_schedule_default_html_rewrite_flush')) :
    function onedown_schedule_default_html_rewrite_flush()
    {
        $version = '20260624';
        if (get_option('onedown_default_html_rewrite_version') === $version) {
            return;
        }

        update_option('onedown_default_html_rewrite_version', $version);
        update_option('onedown_flush_rewrite_rules', 1);
    }
    add_action('init', 'onedown_schedule_default_html_rewrite_flush', 20);
endif;

if (! function_exists('onedown_footer_default_html_url')) :
    function onedown_footer_default_html_url($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return $url;
        }

            $home  = wp_parse_url(home_url('/'));
    $parts = wp_parse_url($url);
    if (! is_array($parts)) {
        return $url;
    }

    if (! empty($parts['host']) && ! empty($home['host']) && strcasecmp($parts['host'], $home['host']) !== 0) {
        return $url;
    }

    $path = isset($parts['path']) ? trim($parts['path'], '/') : '';
    if ($path === '' || preg_match('#^(wp-admin|wp-content|wp-includes|wp-json|sitemap\.xml)#i', $path)) {
        return $url;
    }

    $path = preg_replace('#^category/#i', '', $path);
    $path = preg_replace('#/(default)?index\.html$#i', '', $path);
    $path = preg_replace('#/default\.html$#i', '', $path);
    $path = preg_replace('#\.html$#i', '', $path);
    $path = trim($path, '/');

    if ($path === '') {
        return $url;
    }

    $normalized = home_url('/' . $path . '/default.html');
    if (! empty($parts['query'])) {
        $normalized .= '?' . $parts['query'];
    }
    if (! empty($parts['fragment'])) {
        $normalized .= '#' . $parts['fragment'];
    }

    return $normalized;
}
endif;

if (! function_exists('onedown_footer_menu_default_html_urls')) :
    add_filter('wp_nav_menu_objects', 'onedown_footer_menu_default_html_urls', 9, 2);
    function onedown_footer_menu_default_html_urls($items, $args)
    {
        $theme_location = is_object($args) && isset($args->theme_location) ? $args->theme_location : '';
        if (! in_array($theme_location, array('footer_product', 'footer_support'), true)) {
            return $items;
        }

        foreach ($items as $item) {
            $item->url = onedown_footer_default_html_url($item->url);
        }

        return $items;
    }
endif;

if (! function_exists('onedown_nav_menu_page_html_suffix')) :
    add_filter('wp_nav_menu_objects', 'onedown_nav_menu_page_html_suffix');
    function onedown_nav_menu_page_html_suffix($items)
    {
        $site_url = home_url('/');
        foreach ($items as $item) {
            $url = $item->url;
            // 跳过站外链接
            if (strpos($url, $site_url) !== 0) {
                continue;
            }
            $path = str_replace($site_url, '', $url);
            $path = rtrim($path, '/');
            // 跳过首页、已含 .html 的链接、多级路径
            if (empty($path) || preg_match('/\.html$/', $path) || strpos($path, '/') !== false) {
                continue;
            }
            $item->url = rtrim($url, '/') . '.html';
        }
        return $items;
    }
endif;

if (! function_exists('onedown_render_single_post_edit_fab')) :
    function onedown_render_single_post_edit_fab()
    {
        if (! is_single()) {
            return;
        }

        $post_id = get_queried_object_id();
        if (! $post_id || get_post_type($post_id) !== 'post') {
            return;
        }

        if (! is_user_logged_in() || ! current_user_can('manage_options') || ! current_user_can('edit_post', $post_id)) {
            return;
        }

        $edit_link = get_edit_post_link($post_id, '');
        if (! $edit_link) {
            return;
        }
        ?>
        <style id="onedown-single-edit-fab-style">
            .onedown-single-edit-fab{
                position:fixed;
                right:24px;
                bottom:96px;
                z-index:9999;
                display:inline-flex;
                align-items:center;
                gap:8px;
                padding:12px 16px;
                border-radius:999px;
                background:linear-gradient(135deg,#ff7a18 0%,#ff5722 100%);
                color:#fff;
                box-shadow:0 12px 30px rgba(255,87,34,.28);
                font-size:14px;
                line-height:1;
                font-weight:600;
                text-decoration:none;
                transition:transform .2s ease,box-shadow .2s ease,opacity .2s ease;
            }
            .onedown-single-edit-fab:hover{
                color:#fff;
                opacity:1;
                transform:translateY(-2px);
                box-shadow:0 16px 36px rgba(255,87,34,.36);
            }
            .onedown-single-edit-fab:focus{
                color:#fff;
            }
            .onedown-single-edit-fab i{
                font-size:16px;
            }
            @media (max-width: 767px){
                .onedown-single-edit-fab{
                    right:16px;
                    bottom:84px;
                    padding:11px 14px;
                    font-size:13px;
                }
            }
        </style>
        <a class="onedown-single-edit-fab" href="<?php echo esc_url($edit_link); ?>" aria-label="编辑当前文章">
            <i class="fa fa-pencil"></i>
            <span>编辑文章</span>
        </a>
        <?php
    }
    add_action('wp_footer', 'onedown_render_single_post_edit_fab', 99);
endif;

if (function_exists('onedown_render_single_post_edit_fab')) {
    remove_action('wp_footer', 'onedown_render_single_post_edit_fab', 99);
}
?>
