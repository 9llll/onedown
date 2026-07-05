<?php
/**
 * 下载中转处理器
 *
 * 验证用户已付费后，重定向到真实下载地址
 * 通过 ?od_down=POST_ID&item=INDEX&key=NONCE 访问
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 增加下载次数统计
 *
 * @param int $post_id
 * @param int $item_index
 */
function onedown_increase_download_count($post_id, $item_index)
{
    $counts = get_post_meta($post_id, '_onedown_download_counts', true);
    if (! is_array($counts)) {
        $counts = array();
    }
    if (! isset($counts[$item_index])) {
        $counts[$item_index] = 0;
    }
    $counts[$item_index]++;
    update_post_meta($post_id, '_onedown_download_counts', $counts);
}

/**
 * 生成安全下载链接（SEO 友好格式）
 *
 * 使用 /download/go/{post_id}/{item_index}/{nonce}/ 路径格式，
 * 避免查询参数，更符合 SEO 规范（自动 noindex）。
 *
 * @param int    $post_id 文章 ID
 * @param int    $item_index 下载项索引
 * @param string $url      真实下载地址
 * @return string
 */
function onedown_get_download_link($post_id, $item_index, $url)
{
    $key = wp_create_nonce('od_down_' . $post_id . '_' . $item_index);
    return home_url('/download/go/' . intval($post_id) . '/' . intval($item_index) . '/' . $key . '/');
}

/**
 * 注册 od_down 查询变量
 */
add_filter('query_vars', function ($vars) {
    $vars[] = 'od_down';
    return $vars;
});

/**
 * 拦截 od_down 路由，执行下载
 */
add_action('template_redirect', function () {
    $post_id = intval(get_query_var('od_down'));
    if (! $post_id) {
        return;
    }

    $item_index = intval(get_query_var('item'));
    if (isset($_GET['item'])) {
        $item_index = intval(wp_unslash($_GET['item']));
    }

    $key = sanitize_text_field((string) get_query_var('key'));
    if (isset($_GET['key'])) {
        $key = sanitize_text_field(wp_unslash($_GET['key']));
    }

    // 验证 nonce
    if (! wp_verify_nonce($key, 'od_down_' . $post_id . '_' . $item_index)) {
        wp_die('下载链接已失效，请刷新页面重新获取', '链接失效', array('response' => 403));
        exit;
    }

    // 验证文章存在且是付费下载类型
    $post = get_post($post_id);
    if (! $post || 'download' !== onedown_post_pay_type($post_id)) {
        wp_die('文章不存在或未开启付费下载', '参数错误', array('response' => 404));
        exit;
    }

    // 验证用户已付费
    if (! onedown_user_has_paid($post_id)) {
        wp_die('您还没有购买该资源，请先购买', '无权限', array('response' => 403));
        exit;
    }

    // 获取下载数据
    $data = onedown_get_post_pay_data($post_id);
    if (empty($data['pay_downloads'][$item_index]['url'])) {
        wp_die('未找到下载资源', '参数错误', array('response' => 404));
        exit;
    }

    $file_url = $data['pay_downloads'][$item_index]['url'];
    $file_url = str_replace('&amp;', '&', trim($file_url));
    if (! wp_http_validate_url($file_url)) {
        wp_die('Invalid download URL.', 'Invalid URL', array('response' => 400));
        exit;
    }

    // 增加下载次数统计
    onedown_increase_download_count($post_id, $item_index);

    // 重定向到真实地址
    wp_redirect($file_url);
    exit;
}, 5);
