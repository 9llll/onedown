<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('onedown_setup')) :
    function onedown_setup()
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('responsive-embeds');
        add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
    }
    add_action('after_setup_theme', 'onedown_setup');
endif;

if (! function_exists('onedown_maybe_disable_wp_updates')) :
    function onedown_maybe_disable_wp_updates()
    {
        if (! _pz('disable_wp_update', false)) {
            return;
        }

        remove_action('admin_init', '_maybe_update_core');
        remove_action('admin_init', '_maybe_update_plugins');
        remove_action('admin_init', '_maybe_update_themes');
    }
    add_action('admin_init', 'onedown_maybe_disable_wp_updates', 1);
endif;

// 菜单注册延迟到 init（确保翻译就绪，WP 6.7+ 兼容）
if (! function_exists('onedown_register_menus')) :
    function onedown_register_menus()
    {
        register_nav_menus(
            array(
                'primary' => __('顶部导航', ONEDOWN_TEXT_DOMAIN),
                'footer_product' => __('页脚产品', ONEDOWN_TEXT_DOMAIN),
                'footer_support' => __('页脚支持', ONEDOWN_TEXT_DOMAIN),
            )
        );
    }
    add_action('init', 'onedown_register_menus');
endif;

/**
 * 确保首页分页查询正常工作
 *
 * WordPress 在某些配置下（特别是自定义重写规则场景）
 * 不会自动将 paged 参数传递到主查询，导致 /page/2/ 等分页 URL
 * 无法正确加载下一页内容。
 */
if (! function_exists('onedown_ensure_home_pagination')) :
    function onedown_ensure_home_pagination($query)
    {
        if (! $query->is_main_query()) {
            return;
        }
        // 同时覆盖 is_home（最新文章首页）和 is_front_page（静态首页兼容）
        if ($query->is_home() || ( $query->is_front_page() && $query->is_page() )) {
            $paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
            if ($paged > 1) {
                $query->set('paged', $paged);
            }
        }
    }
    add_action('pre_get_posts', 'onedown_ensure_home_pagination', 1);
endif;

/**
 * 规范化搜索词，兼容全角字符和异常分隔符。
 *
 * @param string $keyword 原始搜索词
 * @param bool   $compact 是否压缩为匹配用字符串
 * @return string
 */
if (! function_exists('onedown_normalize_search_keyword')) :
    function onedown_normalize_search_keyword($keyword, $compact = false)
    {
        $keyword = wp_strip_all_tags((string) $keyword);
        $keyword = html_entity_decode($keyword, ENT_QUOTES, 'UTF-8');
        $keyword = str_replace(array("\r", "\n", "\t", '　'), ' ', $keyword);

        if (function_exists('mb_convert_kana')) {
            $keyword = mb_convert_kana($keyword, 'asKV', 'UTF-8');
        }

        $keyword = trim(preg_replace('/\s+/u', ' ', $keyword));

        if (! $compact) {
            return $keyword;
        }

        if (function_exists('mb_strtolower')) {
            $keyword = mb_strtolower($keyword, 'UTF-8');
        } else {
            $keyword = strtolower($keyword);
        }

        return (string) preg_replace('/[^\p{Han}a-z0-9]+/u', '', $keyword);
    }
endif;

/**
 * 获取后台配置的垃圾搜索 markers。
 *
 * @return array
 */
if (! function_exists('onedown_get_spam_search_markers')) :
    function onedown_get_spam_search_markers()
    {
        $raw = (string) _pz('search_spam_markers', '');
        if ($raw === '') {
            $raw = "引流\n获客\n咨询\n精准\n推广\n客源\n流量\n搜索引流\nyinliu\nhuoke\nzixun\njingzhun\ntuiguang\nkeyuan\nliuliang";
        }

        $markers = preg_split('/\r\n|\r|\n/u', $raw);
        $markers = array_map('trim', (array) $markers);
        $markers = array_filter($markers, static function ($marker) {
            return $marker !== '';
        });

        return array_values(array_unique($markers));
    }
endif;

/**
 * 判断是否为引流类垃圾搜索词。
 *
 * @param string $keyword 搜索词
 * @return bool
 */
if (! function_exists('onedown_is_spam_search_keyword')) :
    function onedown_is_spam_search_keyword($keyword)
    {
        $normalized = onedown_normalize_search_keyword($keyword);
        $compact    = onedown_normalize_search_keyword($keyword, true);

        if ($normalized === '' || $compact === '') {
            return false;
        }

        $has_contact = preg_match('/(?:\btg\b|telegram|电报)/iu', $normalized)
            || preg_match('/\btg[:：]?\s*[a-z0-9_]{5,}\b/i', $normalized);

        $markers = onedown_get_spam_search_markers();

        $hit_count = 0;
        foreach ($markers as $marker) {
            $normalized_marker = onedown_normalize_search_keyword($marker, true);
            if ($normalized_marker !== '' && strpos($compact, $normalized_marker) !== false) {
                $hit_count++;
            }
        }

        if ($has_contact && $hit_count >= 1) {
            return true;
        }

        if ($hit_count >= 3) {
            return true;
        }

        return false;
    }
endif;

/**
 * 清洗或拦截前台主搜索查询。
 *
 * @param WP_Query $query 查询对象
 * @return void
 */
if (! function_exists('onedown_filter_front_search_query')) :
    function onedown_filter_front_search_query($query)
    {
        if (is_admin() || ! $query->is_main_query() || ! $query->is_search()) {
            return;
        }

        $keyword    = (string) $query->get('s');
        $normalized = onedown_normalize_search_keyword($keyword);

        if ($normalized !== $keyword) {
            $query->set('s', $normalized);
        }

        if (_pz('search_post_only', true)) {
            $query->set('post_type', 'post');
        }

        if (! onedown_is_spam_search_keyword($keyword)) {
            return;
        }

        $query->set('s', '');
        $query->set('onedown_blocked_search', 1);
    }
    add_action('pre_get_posts', 'onedown_filter_front_search_query', 2);
endif;

/**
 * 被拦截的垃圾搜索词直接返回空结果，避免命中正文或标题。
 *
 * @param string   $search 搜索 SQL
 * @param WP_Query $query  查询对象
 * @return string
 */
if (! function_exists('onedown_block_spam_search_sql')) :
    function onedown_block_spam_search_sql($search, $query)
    {
        if (! is_admin() && $query->is_main_query() && $query->get('onedown_blocked_search')) {
            return ' AND 1=0 ';
        }

        return $search;
    }
    add_filter('posts_search', 'onedown_block_spam_search_sql', 10, 2);
endif;

/**
 * 前台不回显被拦截的垃圾搜索词。
 *
 * @param string $query 搜索词
 * @return string
 */
if (! function_exists('onedown_mask_blocked_search_query')) :
    function onedown_mask_blocked_search_query($query)
    {
        if (is_admin()) {
            return $query;
        }

        global $wp_query;
        if ($wp_query instanceof WP_Query && $wp_query->get('onedown_blocked_search')) {
            return '';
        }

        return $query;
    }
    add_filter('get_search_query', 'onedown_mask_blocked_search_query');
endif;

/**
 * 浏览量统计（缓冲批量写入优化版）
 *
 * 每次访问先在 transient 缓冲区累加，每 60 秒（或缓冲区阈值）才批量写入数据库。
 * 大幅减少高并发场景下的 postmeta 表写入频率。
 */
if (! function_exists('onedown_track_post_views')) :
    function onedown_track_post_views()
    {
        if (! is_singular('post')) {
            return;
        }

        if (! _pz('show_post_views', true)) {
            return;
        }

        $post_id = get_the_ID();
        if (! $post_id) {
            return;
        }

        // 使用 cookie 防刷，同一个浏览器会话只计一次
        $cookie_key = 'od_viewed_' . $post_id;
        if (! empty($_COOKIE[$cookie_key])) {
            return;
        }
        setcookie($cookie_key, '1', time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

        // ── 缓冲批量写入 ──
        $buffer_key = 'od_views_buffer';
        $buffer     = get_transient($buffer_key);
        if (! is_array($buffer)) {
            $buffer = array();
        }

        if (! isset($buffer[$post_id])) {
            $buffer[$post_id] = 0;
        }
        $buffer[$post_id]++;

        $last_flush = (int) get_option('od_views_last_flush', 0);
        $total      = array_sum($buffer);

        // 每 60 秒 或 缓冲区超过 100 条时 flush
        if (time() - $last_flush >= 60 || $total >= 100) {
            foreach ($buffer as $pid => $count) {
                $views = (int) get_post_meta($pid, 'post_views_count', true);
                update_post_meta($pid, 'post_views_count', $views + $count);
            }
            update_option('od_views_last_flush', time());
            delete_transient($buffer_key);
        } else {
            set_transient($buffer_key, $buffer, 120);
        }
    }
    add_action('wp', 'onedown_track_post_views');
endif;

/**
 * 在文章更新时主动 flush 浏览量缓冲区，避免数据丢失
 */
if (! function_exists('onedown_flush_views_buffer')) :
    function onedown_flush_views_buffer($post_id)
    {
        $buffer_key = 'od_views_buffer';
        $buffer     = get_transient($buffer_key);
        if (! is_array($buffer) || empty($buffer)) {
            return;
        }

        foreach ($buffer as $pid => $count) {
            $views = (int) get_post_meta($pid, 'post_views_count', true);
            update_post_meta($pid, 'post_views_count', $views + $count);
        }
        update_option('od_views_last_flush', time());
        delete_transient($buffer_key);
    }
    add_action('save_post', 'onedown_flush_views_buffer');
endif;

remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

/**
 * 清理 wp_head 中无用的冗余输出，精简首屏 HTML
 */
if (! function_exists('onedown_clean_wp_head')) :
    function onedown_clean_wp_head()
    {
        remove_action('wp_head', 'rsd_link');                      // RSD 链接
        remove_action('wp_head', 'wlwmanifest_link');              // Windows Live Writer
        remove_action('wp_head', 'wp_generator');                  // WP 版本号
        remove_action('wp_head', 'wp_shortlink_wp_head');          // 短链接
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head'); // 上一篇/下一篇 rel
        remove_action('wp_head', 'rest_output_link_wp_head');      // REST API 链接
        remove_action('wp_head', 'wp_oembed_add_discovery_links'); // oEmbed 发现链接
        remove_action('wp_head', 'wp_oembed_add_host_js');         // oEmbed host JS
        remove_action('template_redirect', 'rest_output_link_header', 11);
    }
    add_action('init', 'onedown_clean_wp_head');
endif;

/**
 * 为外部资源添加 dns-prefetch / preconnect 提示
 */
if (! function_exists('onedown_resource_hints')) :
    function onedown_resource_hints($hints, $relation_type)
    {
        if ('dns-prefetch' === $relation_type || 'preconnect' === $relation_type) {
            $hints[] = 'https://cdn.sep.cc';
        }
        return $hints;
    }
    add_filter('wp_resource_hints', 'onedown_resource_hints', 10, 2);
endif;

/**
 * 预加载主题自定义字体，减少首屏文字闪烁（FOUT）
 */
if (! function_exists('onedown_preload_fonts')) :
    function onedown_preload_fonts()
    {
        if (! _pz('use_hbd_font', true)) {
            return;
        }

        $font_uri = get_template_directory_uri() . '/assets/fonts/hbd.woff2';
        printf(
            '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
            esc_url($font_uri)
        );
    }
    add_action('wp_head', 'onedown_preload_fonts', 1);
endif;

/**
 * 推广佣金自动结算定时任务
 */
if (! function_exists('onedown_referral_cron_init')) :
    function onedown_referral_cron_init()
    {
        if (_pz('referral_enabled', false) && ! wp_next_scheduled('onedown_referral_cron_settle')) {
            wp_schedule_event(time(), 'hourly', 'onedown_referral_cron_settle');
        }
    }
    add_action('init', 'onedown_referral_cron_init');
endif;

/**
 * 自动取消超时未支付订单定时任务
 */
if (! function_exists('onedown_auto_cancel_cron_init')) :
    function onedown_auto_cancel_cron_init()
    {
        if (! wp_next_scheduled('onedown_auto_cancel_cron')) {
            wp_schedule_event(time(), 'onedown_5min', 'onedown_auto_cancel_cron');
        }
    }
    add_action('init', 'onedown_auto_cancel_cron_init');
endif;

/**
 * 主题激活时刷新固定链接 & 迁移悬浮按钮配置
 */
if (! function_exists('onedown_theme_activation')) :
    function onedown_theme_activation()
    {
        // 触发 generate_rewrite_rules 注册 user-center/download 路由并刷新
        $rules = get_option('rewrite_rules');
        if (! $rules ||
            ! isset($rules['user-center$']) ||
            ! isset($rules['download/?$']) ||
            ! isset($rules['download/go/(\d+)/(\d+)/([^/]+)/?$']) ||
            ! isset($rules['download/(\d+)\.html$']) ||
            ! isset($rules['download/(\d+)/?$']) ||
            ! isset($rules['download/(\d+)/([^/]+)/?$'])
        ) {
            add_option('onedown_flush_rewrite_rules', 1);
        }
        // 迁移悬浮按钮配置
        onedown_migrate_float_buttons();
    }
    add_action('after_switch_theme', 'onedown_theme_activation');
endif;

/**
 * 迁移悬浮按钮配置：将新的默认按钮合并到已保存的设置中
 */
if (! function_exists('onedown_migrate_float_buttons')) :
    function onedown_migrate_float_buttons()
    {
        $saved = get_option('_onedown_options');
        if (! is_array($saved)) {
            return;
        }

        // 已有的按钮类型
        $existing_types = array();
        if (! empty($saved['float_btn']) && is_array($saved['float_btn'])) {
            foreach ($saved['float_btn'] as $item) {
                if (! empty($item['type'])) {
                    $existing_types[] = $item['type'];
                }
            }
        }

        // 需要添加的新默认按钮
        $new_defaults = array(
            array(
                'type'               => 'build_similar',
                'pc_s'               => true,
                'm_s'                => true,
                'title'              => '搭建同款',
                'icon'               => 'fa-code',
                'build_similar_desc' => '想要搭建同款网站？我们提供专业的网站搭建服务，包括主题定制、功能开发、服务器运维等。如有需求请联系我们！',
            ),
            array(
                'type'  => 'service_qq',
                'pc_s'  => true,
                'm_s'   => true,
                'title' => 'QQ客服',
                'icon'  => 'fa-qq',
                'qq'    => '123456789',
            ),
            array(
                'type'       => 'service_wechat',
                'pc_s'       => true,
                'm_s'        => true,
                'title'      => '微信客服',
                'icon'       => 'fa-wechat',
            ),
            array(
                'type'            => 'custom_link',
                'pc_s'            => true,
                'm_s'             => true,
                'title'           => '友情链接',
                'icon'            => 'fa-link',
                'custom_link_url' => 'https://example.com',
            ),
        );

        $changed = false;
        foreach ($new_defaults as $new_item) {
            if (! in_array($new_item['type'], $existing_types, true)) {
                $saved['float_btn'][] = $new_item;
                $changed = true;
            }
        }

        if ($changed) {
            update_option('_onedown_options', $saved);
        }
    }
endif;

/**
 * 后台管理页面加载时也执行迁移（针对已安装主题后更新代码的情况）
 */
if (! function_exists('onedown_admin_migrate_float_buttons')) :
    function onedown_admin_migrate_float_buttons()
    {
        // 只在主题设置页面执行
        if (! isset($_GET['page']) || $_GET['page'] !== 'onedown-options') {
            return;
        }
        onedown_migrate_float_buttons();
    }
    add_action('admin_init', 'onedown_admin_migrate_float_buttons');
endif;

/**
 * 前台访问时检查是否需要刷新固定链接（仅一次）
 */
if (! function_exists('onedown_maybe_flush_rewrite_rules')) :
    function onedown_maybe_flush_rewrite_rules()
    {
        if (get_option('onedown_flush_rewrite_rules')) {
            delete_option('onedown_flush_rewrite_rules');
            flush_rewrite_rules();
        }
    }
    add_action('init', 'onedown_maybe_flush_rewrite_rules', 999);
endif;

/**
 * 根据后台设置替换 Gravatar 头像来源
 */
add_filter('get_avatar_url', function ($url) {
    $source = onedown_get_option('avatar_source', 'cravatar');

    $sources = array(
        'gravatar' => 'secure.gravatar.com',
        'cravatar' => 'cravatar.cn',
        'lolicdn'  => 'gravatar.loli.net',
        'sepcc'    => 'cdn.sep.cc',
    );

    if ($source === 'custom') {
        $custom_url = onedown_get_option('avatar_custom_url', '');
        if (! empty($custom_url)) {
            $custom_domain = str_replace(array('http://', 'https://'), '', rtrim($custom_url, '/'));
            return str_replace('secure.gravatar.com', $custom_domain, $url);
        }
        // 自定义地址为空时回退到 Gravatar
        return $url;
    }

    if (isset($sources[$source])) {
        return str_replace('secure.gravatar.com', $sources[$source], $url);
    }

    return $url;
});

/**
 * 侧边栏注册已移至 inc/widgets/widgets-init.php
 */

/**
 * 移除 WordPress 顶部工具栏
 */
add_filter('show_admin_bar', '__return_false');
remove_action('wp_head', '_admin_bar_bump_cb');

/**
 * 强制 CSF 图标选择器使用 Font Awesome 4
 *
 * 前台加载的是 Font Awesome 4.7.0（fa fa-xxx），
 * 而 CSF 默认使用 Font Awesome 5（fas/far/fab fa-xxx），
 * 导致前后台图标类名不一致，显示不同。此 filter 让后台
 * 图标选择器也使用 FA4，保证前后台一致。
 */
add_filter('csf_fa4', '__return_true');
