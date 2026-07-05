<?php

/**
 * Onedown 前端付费功能
 *
 * 付费卡片展示、[payshow] 短代码、订单处理、付费状态判断
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

// ──────────────────────────────────────────────
// 1. 辅助函数：获取文章的付费设置
// ──────────────────────────────────────────────

/**
 * 将 skycaiji_wp 插件的 posts_zibpay 结构转换为主题原生结构
 *
 * 插件字段说明：
 *   pay_type         : "no" | "1"(付费阅读) | "2"(付费下载) | "5"(付费图片) | "6"(付费视频)
 *   pay_modo         : "0"(免费) | "1"(付费)
 *   pay_limit        : "0"(所有人) | "1"(登录) | "2"(会员) | "3"(付费会员)
 *   pay_price / pay_original_price
 *   vip_1_price / vip_2_price
 *   pay_download     : [{ link, more }]
 *
 * @param array $src
 * @return array
 */
function onedown_convert_skycaiji_zibpay($src)
{
    if (! is_array($src) || empty($src)) {
        return array();
    }

    $raw_type   = isset($src['pay_type']) ? (string) $src['pay_type'] : '';
    $raw_modo   = isset($src['pay_modo']) ? (string) $src['pay_modo'] : '1';
    $raw_limit  = isset($src['pay_limit']) ? (string) $src['pay_limit'] : '1';

    if ('0' === $raw_modo) {
        $pay_type = 'no';
    } else {
        $type_map = array(
            'no' => 'no',
            '0' => 'no',
            'read' => 'read',
            '1' => 'read',
            'download' => 'download',
            '2' => 'download',
            '5' => 'download',
            '6' => 'download',
        );
        $pay_type = $type_map[$raw_type] ?? 'no';
    }

    $perm_map = array(
        '0' => 'all',
        'all' => 'all',
        '1' => 'logged_in',
        'logged_in' => 'logged_in',
        '2' => 'vip_only',
        '3' => 'vip_only',
        'vip_only' => 'vip_only',
    );
    $buy_permission = $perm_map[$raw_limit] ?? 'all';

    $pay_price = isset($src['pay_price']) ? max(0, floatval($src['pay_price'])) : 0;
    $pay_orig  = isset($src['pay_original_price']) ? max(0, floatval($src['pay_original_price'])) : 0;

    $vip_prices = array();
    if (isset($src['vip_1_price']) && $src['vip_1_price'] !== '' && is_numeric($src['vip_1_price'])) {
        $vip_prices['1'] = max(0, floatval($src['vip_1_price']));
    }
    if (isset($src['vip_2_price']) && $src['vip_2_price'] !== '' && is_numeric($src['vip_2_price'])) {
        $vip_prices['2'] = max(0, floatval($src['vip_2_price']));
    }

    $downloads = array();
    if (! empty($src['pay_download']) && is_array($src['pay_download'])) {
        foreach ($src['pay_download'] as $item) {
            if (empty($item['link'])) continue;
            $downloads[] = array(
                'name' => '立即下载',
                'url'  => esc_url_raw($item['link']),
                'pwd'  => isset($item['more']) ? sanitize_text_field($item['more']) : '',
                'size' => '',
            );
        }
    }

    return array(
        'pay_type'           => $pay_type,
        'pay_price'          => $pay_price,
        'pay_original_price' => $pay_orig,
        'pay_sales'          => 0,
        'buy_permission'     => $buy_permission,
        'pay_vip_prices'     => $vip_prices,
        'pay_downloads'      => $downloads,
    );
}

/**
 * 获取文章的付费设置
 *
 * 优先读取主题原生 _onedown_pay_metabox，
 * 若不存在则 fallback 兼容 skycaiji_wp 插件写入的 posts_zibpay。
 *
 * @param int $post_id
 * @return array
 */
function onedown_get_post_pay_data($post_id = 0)
{
    if (! $post_id) {
        $post_id = get_the_ID();
    }
    if (! $post_id) {
        return array();
    }

    $meta = get_post_meta($post_id, '_onedown_pay_metabox', true);
    if (is_array($meta) && ! empty($meta)) {
        return $meta;
    }

    $zibpay = get_post_meta($post_id, 'posts_zibpay', true);
    if (is_array($zibpay) && ! empty($zibpay)) {
        return onedown_convert_skycaiji_zibpay($zibpay);
    }

    return array();
}

/**
 * 判断主题是否已授权（未授权则禁用所有支付功能）
 *
 * @return bool
 */
function onedown_pay_is_allowed()
{
    return function_exists('onedown_license_is_active') && onedown_license_is_active();
}

/**
 * 判断文章是否开启了付费模式
 *
 * @param int $post_id
 * @return bool
 */
function onedown_post_has_pay($post_id = 0)
{
    if (! onedown_pay_is_allowed()) {
        return false;
    }
    $data = onedown_get_post_pay_data($post_id);
    return ! empty($data['pay_type']) && 'no' !== $data['pay_type'];
}

/**
 * 获取文章付费类型
 *
 * @param int $post_id
 * @return string 'read', 'download', or ''
 */
function onedown_post_pay_type($post_id = 0)
{
    $data = onedown_get_post_pay_data($post_id);
    return ! empty($data['pay_type']) ? $data['pay_type'] : '';
}

/**
 * 获取文章售价（考虑统一售价开关）
 *
 * @param int $post_id
 * @return float
 */
function onedown_post_pay_price($post_id = 0)
{
    // 统一售价开启时，直接返回主题设置中的默认价格
    if (_pz('pay_unified_price', false)) {
        return floatval(_pz('pay_default_price', '9.99'));
    }

    $data = onedown_get_post_pay_data($post_id);
    return isset($data['pay_price']) ? floatval($data['pay_price']) : 0;
}

/**
 * 获取文章的划线价（考虑统一售价开关）
 *
 * @param int $post_id
 * @return float
 */
function onedown_post_pay_original_price($post_id = 0)
{
    // 统一售价开启时，使用默认划线价
    if (_pz('pay_unified_price', false)) {
        $val = _pz('pay_default_orig_price', '');
        return $val !== '' ? floatval($val) : 0;
    }

    $data = onedown_get_post_pay_data($post_id);
    return isset($data['pay_original_price']) ? floatval($data['pay_original_price']) : 0;
}

/**
 * 解析默认VIP会员价格（从文本配置中）
 *
 * @return array
 */
function onedown_parse_default_vip_prices()
{
    $raw = _pz('pay_default_vip_prices', '');
    if (empty($raw)) {
        return array();
    }

    $prices = array();
    $lines  = preg_split("/\r\n|\n|\r/", $raw);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        $parts = explode(':', $line);
        if (count($parts) === 2) {
            $id    = trim($parts[0]);
            $price = trim($parts[1]);
            if ($id !== '' && is_numeric($price)) {
                $prices[$id] = floatval($price);
            }
        }
    }

    return $prices;
}

/**
 * 获取文章的付费数据（考虑统一售价开关）
 *
 * 当统一售价开启时，价格字段被默认值覆盖
 *
 * @param int $post_id
 * @return array
 */
function onedown_get_effective_pay_data($post_id = 0)
{
    if (! $post_id) {
        $post_id = get_the_ID();
    }

    $data = onedown_get_post_pay_data($post_id);

    if (_pz('pay_unified_price', false)) {
        $data['pay_price']          = floatval(_pz('pay_default_price', '9.99'));
        $orig                       = _pz('pay_default_orig_price', '');
        $data['pay_original_price'] = $orig !== '' ? floatval($orig) : 0;
        $data['pay_vip_prices']     = onedown_parse_default_vip_prices();
    }

    return $data;
}

/**
 * 获取用户对此文章的支付状态
 *
 * @param int $post_id
 * @param int $user_id
 * @return bool
 */
function onedown_user_has_paid($post_id = 0, $user_id = 0)
{
    if (! $post_id) {
        $post_id = get_the_ID();
    }
    if (! $user_id) {
        $user_id = get_current_user_id();
    }

    // 管理员和作者免费
    if (is_super_admin()) {
        return true;
    }
    $post = get_post($post_id);
    if ($post && $user_id && (int) $post->post_author === (int) $user_id) {
        return true;
    }

    // 未登录用户检查 cookie + DB（含 guest_token 兜底）
    if (! $user_id) {
        $cookie_key = 'onedown_paid_' . $post_id;
        if (! empty($_COOKIE[$cookie_key])) {
            return true;
        }
        // 再尝试通过 guest_token 查询已支付的订单
        $paid_order = onedown_find_paid_post_order($post_id, $user_id);
        if ($paid_order) {
            // 设置 cookie 以便后续快速验证
            if (! headers_sent()) {
                setcookie($cookie_key, $paid_order->order_id, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            }
            return true;
        }
        return false;
    }

    // 检查购买记录
    $paid_posts = get_user_meta($user_id, 'onedown_paid_posts', true);
    if (is_array($paid_posts) && in_array($post_id, $paid_posts)) {
        return true;
    }

    $paid_order = onedown_find_paid_post_order($post_id, $user_id);
    if ($paid_order) {
        onedown_grant_paid_order_access($paid_order, $user_id);
        return true;
    }

    // 检查 VIP 权益（会员价=0 则免费）
    // 使用有效数据（考虑统一售价开关）
    $data         = onedown_get_effective_pay_data($post_id);
    $vip_prices   = isset($data['pay_vip_prices']) && is_array($data['pay_vip_prices']) ? $data['pay_vip_prices'] : array();
    if (! empty($vip_prices) && $user_id) {
        $vip_info = function_exists('onedown_get_user_vip_info') ? onedown_get_user_vip_info($user_id) : array('is_vip' => false);
        if (! empty($vip_info['is_vip'])) {
            $plan_id = $vip_info['plan_id'];
            if (isset($vip_prices[$plan_id]) && floatval($vip_prices[$plan_id]) === 0.0) {
                return true;
            }
        }
    }

    return false;
}

/**
 * 获取文章的销量
 *
 * @param int $post_id
 * @return int
 */
/**
 * Find a paid post order that can grant access.
 *
 * @param int    $post_id
 * @param int    $user_id
 * @param string $order_id
 * @return object|null
 */
function onedown_find_paid_post_order($post_id, $user_id = 0, $order_id = '')
{
    if (! function_exists('onedown_get_order_table')) {
        return null;
    }

    $post_id  = intval($post_id);
    $user_id  = intval($user_id);
    $order_id = sanitize_text_field($order_id);

    if ($post_id <= 0) {
        return null;
    }

    global $wpdb;
    $table = onedown_get_order_table();

    $where = array(
        'post_id = %d',
        'status = %s',
        'order_type IN (%s, %s)',
    );
    $args = array(
        $post_id,
        ONEDOWN_ORDER_STATUS_PAID,
        ONEDOWN_ORDER_TYPE_POST_READ,
        ONEDOWN_ORDER_TYPE_POST_DOWNLOAD,
    );

    if ($order_id !== '') {
        $where[] = 'order_id = %s';
        $args[]  = $order_id;

        if ($user_id > 0) {
            $where[] = '(user_id = %d OR user_id = 0)';
            $args[]  = $user_id;
        } else {
            $where[] = 'user_id = 0';
        }
    } elseif ($user_id > 0) {
        $where[] = 'user_id = %d';
        $args[]  = $user_id;
    } else {
        // 访客：先尝试通过 cookie 中的 order_id 查询
        $cookie_key = 'onedown_paid_' . $post_id;
        if (! empty($_COOKIE[$cookie_key])) {
            $where[] = 'order_id = %s';
            $args[]  = sanitize_text_field(wp_unslash($_COOKIE[$cookie_key]));
        } else {
            // 再尝试通过 guest_token 查询（异步支付回调时无法设置 cookie）
            $guest_token = ! empty($_COOKIE['onedown_guest_token']) ? sanitize_key($_COOKIE['onedown_guest_token']) : '';
            if (! empty($guest_token)) {
                $where[] = 'guest_token = %s';
                $args[]  = $guest_token;
            } else {
                return null;
            }
        }
    }

    $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . ' ORDER BY pay_time DESC, create_time DESC LIMIT 1';
    return $wpdb->get_row($wpdb->prepare($sql, $args));
}

/**
 * Restore frontend access for a paid post order.
 *
 * @param object $order
 * @param int    $user_id
 * @return void
 */
function onedown_grant_paid_order_access($order, $user_id = 0)
{
    if (! $order || ONEDOWN_ORDER_STATUS_PAID !== $order->status) {
        return;
    }

    $post_id = intval($order->post_id);
    if ($post_id <= 0) {
        return;
    }

    $access_user_id = intval($user_id);
    if ($access_user_id <= 0 && intval($order->user_id) > 0) {
        $access_user_id = intval($order->user_id);
    }

    if ($access_user_id > 0) {
        $paid_posts = get_user_meta($access_user_id, 'onedown_paid_posts', true);
        $paid_posts = is_array($paid_posts) ? $paid_posts : array();
        if (! in_array($post_id, $paid_posts, true)) {
            $paid_posts[] = $post_id;
            update_user_meta($access_user_id, 'onedown_paid_posts', array_values(array_unique(array_map('intval', $paid_posts))));
        }
    }

    if (! headers_sent()) {
        setcookie('onedown_paid_' . $post_id, $order->order_id, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }
}

function onedown_post_pay_sales($post_id = 0)
{
    if (! $post_id) {
        $post_id = get_the_ID();
    }

    $real_sales = (int) get_post_meta($post_id, '_onedown_pay_sales', true);
    $data       = onedown_get_post_pay_data($post_id);
    $base       = isset($data['pay_sales']) ? intval($data['pay_sales']) : 0;

    return $real_sales + $base;
}

/**
 * 增加文章的销量
 *
 * @param int $post_id
 */
function onedown_increase_pay_sales($post_id)
{
    $current = (int) get_post_meta($post_id, '_onedown_pay_sales', true);
    update_post_meta($post_id, '_onedown_pay_sales', $current + 1);
}

// ──────────────────────────────────────────────
// 2. [payshow] 短代码 — 付费阅读隐藏内容
// ──────────────────────────────────────────────

add_shortcode('payshow', 'onedown_shortcode_payshow');
function onedown_shortcode_payshow($atts, $content = null)
{
    if (is_null($content)) {
        return '';
    }

    $post_id = get_the_ID();
    if (! $post_id) {
        return do_shortcode($content);
    }

    // 未开启付费模式，直接显示
    if (! onedown_post_has_pay($post_id)) {
        return do_shortcode($content);
    }

    // 已付费/管理员/作者 直接显示
    if (onedown_user_has_paid($post_id)) {
        return do_shortcode($content);
    }

    // 未付费 — 显示付费提示，锚链接到付费卡片
    return '<div class="onedown-hidden-box" data-type="payshow">'
        . '<a class="onedown-hidden-text" href="#onedown-pay-box">'
        . '<i class="fa fa-lock"></i> 此处内容已隐藏，请付费后查看'
        . '</a>'
        . '</div>';
}

// ──────────────────────────────────────────────
// 3. 付费卡片 — 在文章内容中插入
// ──────────────────────────────────────────────

/**
 * 在文章内容中插入付费卡片
 *
 * 通过 the_content 过滤器在内容开头插入付费卡片
 */
add_filter('the_content', 'onedown_pay_content_filter', 99);
function onedown_pay_content_filter($content)
{
    if (! is_singular('post') || ! in_the_loop() || ! is_main_query()) {
        return $content;
    }

    $post_id = get_the_ID();
    if (! onedown_post_has_pay($post_id)) {
        return $content;
    }

    // 已付费 — 在内容前显示下载链接或已付费标识
    if (onedown_user_has_paid($post_id)) {
        // 付费下载模式：显示下载链接
        if ('download' === onedown_post_pay_type($post_id)) {
            $download_html = onedown_pay_download_box($post_id);
            return $download_html . $content;
        }

        // 付费阅读模式：显示已付费标识
        if ('read' === onedown_post_pay_type($post_id)) {
            $paid_label = '<div class="onedown-paid-box">'
                . '<div class="onedown-paid-icon"><i class="fa fa-check-circle"></i></div>'
                . '<div class="onedown-paid-text">您已付费，感谢您的支持！</div>'
                . '</div>';
            return $paid_label . $content;
        }

        return $content;
    }

    // 未付费 — 在内容开头插入付费卡片
    $pay_box = onedown_pay_box_html($post_id);
    // 付费阅读模式：隐藏正文内容，只显示付费卡片和 [payshow] 之外的简短摘要
    if ('read' === onedown_post_pay_type($post_id)) {
        return $pay_box;
    }
    return $pay_box . $content;
}

/**
 * 生成付费下载的下载链接 HTML
 *
 * @param int $post_id
 * @return string
 */
function onedown_pay_download_box($post_id, $use_redirect = true)
{
    $data = onedown_get_post_pay_data($post_id);
    if (empty($data['pay_downloads']) || ! is_array($data['pay_downloads'])) {
        return '';
    }

    // 获取下载中转页 URL
    $redirect_url = $use_redirect && function_exists('onedown_get_download_redirect_url')
        ? onedown_get_download_redirect_url($post_id, 0)
        : false;
    $redirect_mode = function_exists('onedown_get_download_redirect_mode')
        ? onedown_get_download_redirect_mode()
        : 'normal';
    $is_qrcode_mode = $redirect_url && $redirect_mode === 'qrcode';

    $download_index_labels = array('①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧', '⑨', '⑩');
    $download_color_classes = array(
        'od-btn-dl-tone-1',
        'od-btn-dl-tone-2',
        'od-btn-dl-tone-3',
        'od-btn-dl-tone-4',
        'od-btn-dl-tone-5',
    );

    // 简化模式：二维码模式下仍按资源逐条展示
    $simplified = $redirect_url && function_exists('_pz') && _pz('download_box_simplified', false);
    if ($simplified) {
        $html = '<div class="onedown-download-box">';
        $html .= '<div class="onedown-download-title"><i class="fa fa-download"></i> &#36164;&#28304;&#19979;&#36733;</div>';
        $html .= '<div class="onedown-download-simplified od-download-simplified-list">';

        foreach ($data['pay_downloads'] as $i => $dl) {
            $name = ! empty($dl['name']) ? $dl['name'] : '&#31435;&#21363;&#19979;&#36733;';
            $pwd  = ! empty($dl['pwd']) ? $dl['pwd'] : '';
            $index_label = isset($download_index_labels[$i]) ? $download_index_labels[$i] : ('#' . ($i + 1));
            $tone_class  = $download_color_classes[$i % count($download_color_classes)];
            $url         = ! empty($dl['url']) ? $dl['url'] : '#';
            $is_cloud    = function_exists('onedown_is_cloud_drive_url') ? onedown_is_cloud_drive_url($url) : false;
            $cloud_name  = $is_cloud && function_exists('onedown_cloud_drive_name') ? onedown_cloud_drive_name($url) : '';
            $btn_text    = $cloud_name !== '' ? $cloud_name : $name;
            $btn_href    = function_exists('onedown_get_download_redirect_url')
                ? onedown_get_download_redirect_url($post_id, $i)
                : $redirect_url;

            $html .= '<div class="od-download-simplified-item">';
            $html .= '<div class="od-download-simplified-actions">';
            $html .= '<a class="od-btn-dl od-btn-dl-simplified ' . esc_attr($tone_class) . '" href="' . esc_url($btn_href) . '" target="_blank" rel="nofollow noopener">'
                . '<i class="fa fa-download"></i> ' . esc_html($btn_text) . '</a>';
            if ($pwd) {
                $html .= '<button type="button" class="od-btn-copy od-btn-copy-inline od-btn-copy-inline-compact" data-copy="' . esc_attr($pwd) . '">' . esc_html($pwd) . '</button>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    $html = '<div class="onedown-download-box">';
    $html .= '<div class="onedown-download-title"><i class="fa fa-download"></i> &#36164;&#28304;&#19979;&#36733;</div>';
    $html .= '<div class="onedown-download-list">';

    foreach ($data['pay_downloads'] as $i => $dl) {
        $name = ! empty($dl['name']) ? $dl['name'] : '&#31435;&#21363;&#19979;&#36733;';
        $url  = ! empty($dl['url']) ? $dl['url'] : '#';
        $pwd  = ! empty($dl['pwd']) ? $dl['pwd'] : '';
        $size = ! empty($dl['size']) ? $dl['size'] : '';

        $is_cloud    = function_exists('onedown_is_cloud_drive_url') ? onedown_is_cloud_drive_url($url) : false;
        $cloud_name  = $is_cloud && function_exists('onedown_cloud_drive_name') ? onedown_cloud_drive_name($url) : '';
        $cloud_icon  = $is_cloud && function_exists('onedown_cloud_drive_icon') ? onedown_cloud_drive_icon($url) : 'fa-file-archive-o';
        $cloud_color = $is_cloud && function_exists('onedown_cloud_drive_color') ? onedown_cloud_drive_color($url) : '';

        $icon_style = $cloud_color ? ' style="background:' . esc_attr($cloud_color) . '"' : '';

        // ── 按钮链接逻辑 ──
        if ($redirect_url) {
            // 文章页模式：跳转到下载中转页（所有资源都走此路径）
            $btn_href  = function_exists('onedown_get_download_redirect_url')
                ? onedown_get_download_redirect_url($post_id, $i)
                : $redirect_url;
            $btn_attrs = 'target="_blank" rel="nofollow noopener"';
        } elseif (! $use_redirect && function_exists('onedown_get_download_link')) {
            // 下载页模式（已付费）：使用 nonce 安全下载链接
            $btn_href  = onedown_get_download_link($post_id, $i, $url);
            $btn_attrs = 'target="_blank" rel="nofollow noopener"';
        } else {
            // 未启用中转页时，直接显示下载地址
            $btn_href  = $url;
            $btn_attrs = 'target="_blank" rel="nofollow noopener"';
        }

        $html .= '<div class="onedown-download-item' . ($is_cloud ? ' is-cloud' : '') . '">';

        // 图标
        $html .= '<span class="od-item-icon"' . $icon_style . '><i class="fa ' . $cloud_icon . '"></i></span>';

        // 名称 + 元信息
        $html .= '<span class="od-item-body">';
        $html .= '<span class="od-item-name">' . esc_html($name) . '</span>';
        if ($size || $pwd) {
            $html .= '<span class="od-item-meta">';
            if ($size) {
                $html .= '<span class="od-meta-size">' . esc_html($size) . '</span>';
            }
            if ($pwd) {
                $html .= '<span class="od-meta-pwd">&#25552;&#21462;&#30721; <code>' . esc_html($pwd) . '</code></span>';
            }
            $html .= '</span>';
        }
        $html .= '</span>';

        // 操作按钮
        $html .= '<span class="od-item-actions">';
        if ($is_qrcode_mode && $pwd) {
            $html .= '<button type="button" class="od-btn-copy od-btn-copy-inline" data-copy="' . esc_attr($pwd) . '">' . esc_html($pwd) . '</button>';
        } elseif ($pwd) {
            $html .= '<button type="button" class="od-btn-copy od-btn-copy-inline od-btn-copy-inline-compact" data-copy="' . esc_attr($pwd) . '">' . esc_html($pwd) . '</button>';
        }
        $html .= '<a class="od-btn-dl' . ($is_cloud ? ' is-cloud' : '') . '" href="' . esc_url($btn_href) . '" ' . $btn_attrs . '>'
            . ($is_cloud ? '' : '<i class="fa fa-download"></i> ')
            . ($is_cloud ? '&#25171;&#24320;&#38142;&#25509;' : '&#19979;&#36733;')
            . '</a>';
        $html .= '</span>';

        $html .= '</div>'; // .onedown-download-item
    }
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 * 生成付费卡片 HTML
 *
 * @param int $post_id
 * @return string
 */
function onedown_pay_box_html($post_id)
{
    // 使用有效数据（考虑统一售价开关）
    $data     = onedown_get_effective_pay_data($post_id);
    $price    = onedown_post_pay_price($post_id);
    $sales    = onedown_post_pay_sales($post_id);
    $pay_type = onedown_post_pay_type($post_id);
    $show_sales = _pz('show_post_sales', true);

    $title       = ! empty($data['pay_title']) ? $data['pay_title'] : get_the_title($post_id);
    $desc        = ! empty($data['pay_desc']) ? $data['pay_desc'] : '';
    $orig_price  = ! empty($data['pay_original_price']) ? floatval($data['pay_original_price']) : 0;
    $vip_prices  = isset($data['pay_vip_prices']) && is_array($data['pay_vip_prices']) ? $data['pay_vip_prices'] : array();
    $buy_permission = $data['buy_permission'] ?? 'all';

    $type_label = 'read' === $pay_type ? '付费阅读' : '付费下载';
    $type_class = 'read' === $pay_type ? 'pay-type-read' : 'pay-type-download';

    $vip_info  = function_exists('onedown_get_user_vip_info') ? onedown_get_user_vip_info(get_current_user_id()) : array('is_vip' => false);
    $is_vip    = ! empty($vip_info['is_vip']);

    // 会员价格逻辑
    $show_vip_price = false;
    $effective_price = $price;
    if ($is_vip && ! empty($vip_prices)) {
        $plan_id = $vip_info['plan_id'];
        if (isset($vip_prices[$plan_id])) {
            $v_price = floatval($vip_prices[$plan_id]);
            $show_vip_price = true;
            $effective_price = $v_price;
        }
    }

    $thumb_url = get_the_post_thumbnail_url($post_id, 'medium_large') ?: '';
    if (!$thumb_url) {
        $thumb_url = function_exists('onedown_fallback_thumb_url') ? onedown_fallback_thumb_url($post_id, 800, 400) : '';
    }
    $pay_order_type = 'download' === $pay_type ? 'order-type-2' : 'order-type-1';

    ob_start();
?>
    <div class="onedown-pay-box <?php echo esc_attr($pay_order_type); ?>" id="onedown-pay-box"
        style="background-image: url('<?php echo esc_url($thumb_url); ?>');">
        <div class="pay-overlay"></div>
        <div class="pay-flexbox">
            <div class="pay-info">
                <dt class="pay-title"><?php echo esc_html($title); ?></dt>
                <?php if ($desc) : ?>
                    <div class="pay-desc"><?php echo esc_html($desc); ?></div>
                <?php endif; ?>
                <div class="pay-price-row">
                    <?php if ($show_vip_price && $effective_price == 0) : ?>
                        <b class="pay-price-current vip-free">VIP 免费</b>
                    <?php else : ?>
                        <b class="pay-price-current">￥<?php echo number_format($effective_price, 2); ?></b>
                    <?php endif; ?>
                    <?php if ($orig_price > 0 && $orig_price > $price) : ?>
                        <span class="pay-price-original">￥<?php echo number_format($orig_price, 2); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (! empty($vip_prices) && ! $show_vip_price && ! is_single()) : ?>
                    <div class="pay-vip-row">
                        <?php foreach ($vip_prices as $plan_id => $v_price) :
                            $vip_plan = function_exists('onedown_get_vip_plan') ? onedown_get_vip_plan($plan_id) : array('name' => 'VIP');
                            $vip_name = $vip_plan['name'] ?? ('VIP ' . $plan_id);
                            $vip_free = floatval($v_price) == 0;
                        ?>
                            <a href="<?php echo esc_url(function_exists('onedown_user_center_url') ? onedown_user_center_url(array('tab' => 'vip')) : home_url('/user-center/?tab=vip')); ?>"
                                class="pay-vip-price">
                                <i class="fa fa-diamond"></i> <?php echo esc_html($vip_name); ?>
                                <span><?php echo $vip_free ? '免费' : '￥' . number_format(floatval($v_price), 2); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="pay-actions">
                <?php if ('vip_only' === $buy_permission && (! is_user_logged_in() || ! $is_vip)) : ?>
                    <div class="pay-btn-disabled"><i class="fa fa-lock"></i> 仅会员</div>
                <?php else : ?>
                    <button type="button" class="pay-btn buy" data-post-id="<?php echo intval($post_id); ?>" data-pay-btn>
                        <i class="fa fa-credit-card"></i> 立即购买
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="pay-tag"><?php echo esc_html($type_label); ?></div>
        <?php if ($show_sales) : ?>
            <div class="pay-sales">已售 <?php echo intval($sales); ?></div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

// ──────────────────────────────────────────────
// 4. 前端 JS 和 CSS 资源加载
// ──────────────────────────────────────────────

add_shortcode('download_center', 'onedown_shortcode_download_center');
function onedown_shortcode_download_center()
{
    if (! is_user_logged_in()) {
        return '<div class="onedown-hidden-box" data-type="payshow">'
            . '<a class="onedown-hidden-text" href="' . esc_url(wp_login_url(get_permalink())) . '">'
            . '<i class="fa fa-sign-in"></i> 请登录后查看下载内容'
            . '</a>'
            . '</div>';
    }

    $user_id    = get_current_user_id();
    $paid_posts = get_user_meta($user_id, 'onedown_paid_posts', true);
    $paid_posts = is_array($paid_posts) ? $paid_posts : array();

    ob_start();
?>
    <div class="onedown-dc">
        <div class="onedown-dc-head">
            <h2><i class="fa fa-download"></i> 下载中心</h2>
            <p>管理您已购买的所有资源下载</p>
        </div>

        <?php if (! empty($paid_posts)) :
            $has_download = false;
            foreach ($paid_posts as $paid_post_id) :
                $post = get_post($paid_post_id);
                if (! $post) continue;
                $data = onedown_get_post_pay_data($paid_post_id);
                if (empty($data['pay_downloads']) || ! is_array($data['pay_downloads'])) continue;
                $has_download = true;
                $downloads = $data['pay_downloads'];
                $post_title = get_the_title($paid_post_id);
                $post_url   = get_permalink($paid_post_id);
        ?>
                <div class="onedown-dc-post">
                    <div class="onedown-dc-post-head">
                        <h3><a href="<?php echo esc_url($post_url); ?>"><?php echo esc_html($post_title); ?></a></h3>
                        <span class="onedown-dc-post-count"><?php echo count($downloads); ?> 个文件</span>
                    </div>
                    <div class="onedown-dc-list">
                        <?php foreach ($downloads as $dl) :
                            $name = ! empty($dl['name']) ? $dl['name'] : '立即下载';
                            $url  = ! empty($dl['url']) ? $dl['url'] : '#';
                            $pwd  = ! empty($dl['pwd']) ? '提取码：' . esc_html($dl['pwd']) : '';
                            $size = ! empty($dl['size']) ? esc_html($dl['size']) : '';
                        ?>
                            <div class="onedown-dc-item">
                                <div class="onedown-dc-item-info">
                                    <span class="onedown-dc-item-icon"><i class="fa fa-file-archive-o"></i></span>
                                    <div>
                                        <strong><?php echo esc_html($name); ?></strong>
                                        <?php if ($size || $pwd) : ?>
                                            <span class="onedown-dc-item-meta">
                                                <?php if ($size) : ?><em><i class="fa fa-database"></i>
                                                        <?php echo esc_html($size); ?></em><?php endif; ?>
                                                <?php if ($pwd) : ?><em><i class="fa fa-key"></i> <?php echo $pwd; ?></em><?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="onedown-dc-item-actions">
                                    <?php if ($pwd) : ?>
                                        <button type="button" class="onedown-dc-copy-btn" data-copy="<?php echo esc_attr($dl['pwd']); ?>"><i
                                                class="fa fa-copy"></i> 复制提取码</button>
                                    <?php endif; ?>
                                    <a class="onedown-dc-dl-btn" href="<?php echo esc_url($url); ?>" target="_blank" rel="nofollow"><i
                                            class="fa fa-download"></i> <?php echo esc_html($name); ?></a>
                                </div>
                                <div class="onedown-dc-qrcode">
                                    <canvas class="onedown-dc-qrcode-canvas" data-qr-url="<?php echo esc_attr($url); ?>" width="160"
                                        height="160"></canvas>
                                    <span>扫码下载</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (! $has_download) : ?>
                <div class="onedown-dc-empty">
                    <i class="fa fa-download"></i>
                    <p>暂无下载资源</p>
                </div>
            <?php endif; ?>

        <?php else : ?>
            <div class="onedown-dc-empty">
                <i class="fa fa-shopping-bag"></i>
                <p>您还没有购买任何内容</p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="onedown-dc-home-btn"><i class="fa fa-home"></i> 去首页看看</a>
            </div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}

// ──────────────────────────────────────────────
// 4. 前端 JS 和 CSS 资源加载
// ──────────────────────────────────────────────

add_action('wp_enqueue_scripts', 'onedown_pay_front_assets', 20);

/**
 * 当 skycaiji_wp 插件保存 posts_zibpay 时，自动同步到主题原生 _onedown_pay_metabox
 *
 * 这样插件远程发布/更新文章后，主题的支付功能立即可用，无需手动处理。
 */
add_action('updated_post_meta', 'onedown_sync_skycaiji_zibpay_to_theme', 10, 4);
add_action('added_post_meta',   'onedown_sync_skycaiji_zibpay_to_theme', 10, 4);
function onedown_sync_skycaiji_zibpay_to_theme($meta_id, $post_id, $meta_key, $meta_value)
{
    if ('posts_zibpay' !== $meta_key) {
        return;
    }

    if (! function_exists('onedown_convert_skycaiji_zibpay')) {
        return;
    }

    if ($meta_value === false || $meta_value === null) {
        return;
    }

    $converted = onedown_convert_skycaiji_zibpay($meta_value);
    if (! empty($converted)) {
        update_post_meta($post_id, '_onedown_pay_metabox', $converted);
    }
}

/**
 * 插件远程发布时可能直接调用 wp_insert_post/wp_update_post，
 * 也可能先调用 REST 再通过 REST 流程写入。此处使用 save_post 作为兜底，
 * 在 post 保存后检测是否存在 posts_zibpay 但无 _onedown_pay_metabox 的情况，
 * 存在则自动同步。
 */
add_action('save_post_post', 'onedown_sync_skycaiji_on_save_post', 99, 3);
function onedown_sync_skycaiji_on_save_post($post_id, $post, $update)
{
    if (! function_exists('onedown_convert_skycaiji_zibpay')) {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    $zibpay = get_post_meta($post_id, 'posts_zibpay', true);
    if (! is_array($zibpay) || empty($zibpay)) {
        return;
    }

    $theme_meta = get_post_meta($post_id, '_onedown_pay_metabox', true);
    if (is_array($theme_meta) && ! empty($theme_meta)) {
        return;
    }

    $converted = onedown_convert_skycaiji_zibpay($zibpay);
    if (! empty($converted)) {
        update_post_meta($post_id, '_onedown_pay_metabox', $converted);
    }
}
function onedown_pay_front_assets()
{
    if (! is_singular('post')) {
        return;
    }

    $post_id = get_the_ID();
    if (! onedown_post_has_pay($post_id)) {
        return;
    }

    // 支付 JS 已迁移至 main.js
}

/**
 * AJAX：获取支付 nonce（仅已登录用户可获取，限制非授权调用）
 */
add_action('wp_ajax_onedown_get_pay_nonce', 'onedown_ajax_get_pay_nonce');
function onedown_ajax_get_pay_nonce()
{
    if (! onedown_pay_is_allowed()) {
        wp_send_json_error(array('msg' => '支付功能已禁用'));
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(array('msg' => '请先登录'));
    }
    wp_send_json_success(array(
        'nonce' => wp_create_nonce('onedown_pay_order_action'),
    ));
}

// ──────────────────────────────────────────────
// 6. 用户中心：已购文章标签页
// ──────────────────────────────────────────────

/**
 * 在用户中心添加已购文章数据
 */
add_filter('onedown_user_tab_content', 'onedown_user_paid_posts_tab', 10, 2);
function onedown_user_paid_posts_tab($content, $tab)
{
    if ('paid-posts' !== $tab) {
        return $content;
    }

    $user_id    = get_current_user_id();
    $paid_posts = get_user_meta($user_id, 'onedown_paid_posts', true);
    $paid_posts = is_array($paid_posts) ? $paid_posts : array();

    ob_start();
?>
    <div class="section-card">
        <div class="user-page-toolbar">
            <h3 style="margin:0;font-weight:800;color:#252c3a;"><i class="fa fa-shopping-bag"></i> 已购内容</h3>
        </div>

        <?php if (! empty($paid_posts)) : ?>
            <div class="paid-posts-list">
                <?php foreach ($paid_posts as $paid_post_id) :
                    $post = get_post($paid_post_id);
                    if (! $post) {
                        continue;
                    }
                    $pay_type = onedown_post_pay_type($paid_post_id);
                    $type_label = 'download' === $pay_type ? '付费下载' : '付费阅读';
                ?>
                    <div class="paid-post-item">
                        <div class="paid-post-info">
                            <strong><a
                                    href="<?php echo esc_url(get_permalink($paid_post_id)); ?>"><?php echo esc_html(get_the_title($paid_post_id)); ?></a></strong>
                            <span class="paid-post-type"><?php echo esc_html($type_label); ?></span>
                        </div>
                        <a class="paid-post-view" href="<?php echo esc_url(get_permalink($paid_post_id)); ?>"><i
                                class="fa fa-eye"></i> 查看</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div style="padding:40px;text-align:center;color:var(--od-muted);">
                <i class="fa fa-shopping-bag" style="font-size:32px;display:block;margin-bottom:12px;"></i>
                <p style="margin:0;">暂无已购内容</p>
            </div>
        <?php endif; ?>
    </div>
<?php
    return ob_get_clean();
}
