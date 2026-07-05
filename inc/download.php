<?php

/**
 * Onedown 独立下载页面功能
 *
 * 提供下载资源管理 metabox、网盘地址检测、二维码显示
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

// ═══════════════════════════════════════════
// 1. 网盘地址检测
// ═══════════════════════════════════════════

/**
 * 获取已知网盘域名列表
 */
function onedown_cloud_drive_domains()
{
    return array(
        'pan.baidu.com',
        'aliyundrive.com',
        'aliyunpan.com',
        '115.com',
        'pan.xunlei.com',
        '123pan.com',
        '123684.com',
        'quark.cn',
        'sharepoint.com',
        'drive.google.com',
        '1drv.ms',
    );
}

/**
 * 检测是否为网盘地址
 *
 * @param string $url
 * @return bool
 */
function onedown_is_cloud_drive_url($url)
{
    $host = parse_url($url, PHP_URL_HOST);
    if (! $host) {
        return false;
    }
    $host = strtolower($host);
    $domains = onedown_cloud_drive_domains();
    foreach ($domains as $domain) {
        if ($host === $domain || strpos($host, '.' . $domain) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * 获取网盘显示名称
 *
 * @param string $url
 * @return string
 */
function onedown_cloud_drive_name($url)
{
    $host = parse_url($url, PHP_URL_HOST);
    if (! $host) {
        return '';
    }
    $host = strtolower($host);

    $map = array(
        'pan.baidu.com'   => '百度网盘',
        'aliyundrive.com' => '阿里云盘',
        'aliyunpan.com'   => '阿里云盘',
        '115.com'         => '115网盘',
        'pan.xunlei.com'  => '迅雷云盘',
        '123pan.com'      => '123云盘',
        '123684.com'      => '123云盘',
        'quark.cn'        => '夸克网盘',
        'sharepoint.com'  => 'OneDrive',
        'drive.google.com' => 'Google Drive',
        '1drv.ms'         => 'OneDrive',
    );

    foreach ($map as $domain => $name) {
        if ($host === $domain || strpos($host, '.' . $domain) !== false) {
            return $name;
        }
    }
    return '网盘';
}

/**
 * 获取网盘对应的 Font Awesome 图标类
 *
 * @param string $url
 * @return string
 */
function onedown_cloud_drive_icon($url)
{
    $host = parse_url($url, PHP_URL_HOST);
    if (! $host) {
        return 'fa-cloud';
    }
    $host = strtolower($host);

    $map = array(
        'pan.baidu.com'   => 'fa-paw',
        'aliyundrive.com' => 'fa-cloud-upload',
        'aliyunpan.com'   => 'fa-cloud-upload',
        '115.com'         => 'fa-hdd-o',
        'pan.xunlei.com'  => 'fa-bolt',
        '123pan.com'      => 'fa-archive',
        '123684.com'      => 'fa-archive',
        'quark.cn'        => 'fa-superpowers',
        'sharepoint.com'  => 'fa-share-alt',
        'drive.google.com' => 'fa-google',
        '1drv.ms'         => 'fa-windows',
    );

    foreach ($map as $domain => $icon) {
        if ($host === $domain || strpos($host, '.' . $domain) !== false) {
            return $icon;
        }
    }
    return 'fa-cloud';
}

/**
 * 获取网盘对应的主题色
 *
 * @param string $url
 * @return string
 */
function onedown_cloud_drive_color($url)
{
    $host = parse_url($url, PHP_URL_HOST);
    if (! $host) {
        return '';
    }
    $host = strtolower($host);

    $map = array(
        'pan.baidu.com'     => '#2359e0',
        'aliyundrive.com'   => '#1677ff',
        'aliyunpan.com'     => '#1677ff',
        '115.com'           => '#e60012',
        'pan.xunlei.com'    => '#0085e6',
        '123pan.com'        => '#07c160',
        '123684.com'        => '#07c160',
        'quark.cn'          => '#7c3aed',
        'sharepoint.com'    => '#0078d4',
        'drive.google.com'  => '#4285f4',
        '1drv.ms'           => '#0078d4',
    );

    foreach ($map as $domain => $color) {
        if ($host === $domain || strpos($host, '.' . $domain) !== false) {
            return $color;
        }
    }
    return '';
}

/**
 * 获取所有使用了"下载页面"模板的页面，供主题设置下拉框使用
 *
 * @return array
 */
function onedown_get_download_pages()
{
    $pages = get_pages(array(
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'page-templates/download.php',
    ));

    $options = array(
        ''   => __('— 内置下载页（推荐） —', 'onedown'),
        '__builtin__' => __('内置下载页（/download/）', 'onedown'),
    );

    if (! empty($pages)) {
        foreach ($pages as $page) {
            $options[$page->ID] = $page->post_title . ' (' . __('自定义页面', 'onedown') . ')';
        }
    }

    return $options;
}

/**
 * 获取下载跳转模式
 *
 * @return string
 */
function onedown_get_download_redirect_mode()
{
    if (! function_exists('_pz')) {
        return 'normal';
    }

    $mode = (string) _pz('download_redirect_mode', 'normal');
    if (! in_array($mode, array('normal', 'qrcode', 'custom'), true)) {
        $mode = 'normal';
    }

    return $mode;
}

/**
 * 获取二维码跳转页地址
 *
 * @param int $post_id
 * @param int $item_index
 * @return string
 */
function onedown_get_download_qrcode_url($post_id, $item_index = 0)
{
    $args = array(
        'post_id' => intval($post_id),
        'item'    => max(0, intval($item_index)),
        'view'    => 'qrcode',
    );

    if (function_exists('onedown_download_page_url')) {
        return onedown_download_page_url($args);
    }

    return add_query_arg($args, home_url('/download/'));
}

// ═══════════════════════════════════════════
// 2. 文章编辑 Metabox
// ═══════════════════════════════════════════

add_action('add_meta_boxes', 'onedown_register_download_metabox');
/**
 * 注册下载资源 Metabox
 */
function onedown_register_download_metabox()
{
    add_meta_box(
        'onedown_download_metabox',
        '下载资源（独立下载页面）',
        'onedown_render_download_metabox',
        'page',
        'normal',
        'high'
    );
}

/**
 * 渲染下载资源 Metabox
 */
function onedown_render_download_metabox($post)
{
    $template = get_page_template_slug($post->ID);
    if ('page-templates/download.php' !== $template) {
        echo '<p style="color:#666;">当前页面模板不是"下载页面"，不显示下载资源设置。</p>';
        return;
    }

    $data = get_post_meta($post->ID, '_onedown_page_downloads', true);
    $data = is_array($data) ? $data : array();

    $downloads = isset($data['downloads']) && is_array($data['downloads']) ? $data['downloads'] : array();
    if (empty($downloads)) {
        $downloads[] = array('name' => '', 'url' => '', 'pwd' => '', 'size' => '', 'desc' => '');
    }

    $subtitle = isset($data['subtitle']) ? $data['subtitle'] : '';

    wp_nonce_field('onedown_save_download_metabox', 'onedown_download_metabox_nonce');
?>
<style>
.od-dl-grid {
    display: grid;
    grid-template-columns: 160px 1fr;
    gap: 12px 18px;
    align-items: start
}

.od-dl-grid label.olabel {
    font-weight: 600
}

.od-dl-grid input[type=text],
.od-dl-grid textarea {
    width: 100%;
    max-width: 640px
}

.od-dl-grid .desc {
    color: #666;
    font-size: 12px;
    margin-top: 4px
}

.od-dl-section {
    grid-column: 1/-1;
    margin: 12px 0 0;
    padding: 10px 12px;
    background: #f6f7f7;
    border-left: 4px solid #2271b1;
    font-weight: 700
}

.od-dl-table {
    width: 100%;
    max-width: 860px;
    border-collapse: collapse
}

.od-dl-table th,
.od-dl-table td {
    border: 1px solid #ddd;
    padding: 8px
}

.od-dl-table th {
    background: #f6f7f7;
    text-align: left
}

.od-dl-row-remove {
    color: #a00;
    cursor: pointer;
    text-decoration: none;
    font-size: 18px;
    line-height: 1
}

.od-dl-row-remove:hover {
    color: #dc3232
}

.od-dl-add-row {
    margin-top: 8px
}

.od-dl-tip {
    font-size: 12px;
    color: #d63638;
    margin-top: 4px
}
</style>
<div class="od-dl-grid">
    <div class="od-dl-section">页面设置</div>

    <label class="olabel">页面副标题</label>
    <div>
        <input type="text" name="onedown_download[subtitle]" value="<?php echo esc_attr($subtitle); ?>"
            placeholder="例如：软件 / 文档 / 资源">
        <div class="desc">显示在页面标题下方</div>
    </div>

    <div class="od-dl-section">下载资源列表</div>

    <label class="olabel">资源列表</label>
    <div>
        <table class="od-dl-table" id="od-downloads-table">
            <thead>
                <tr>
                    <th>资源名称</th>
                    <th>下载地址</th>
                    <th>提取码</th>
                    <th>文件大小</th>
                    <th>说明</th>
                    <th style="width:32px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($downloads as $index => $item) : ?>
                <tr class="od-dl-row">
                    <td><input type="text" name="onedown_download[downloads][<?php echo esc_attr($index); ?>][name]"
                            value="<?php echo esc_attr($item['name'] ?? ''); ?>" placeholder="资源名称"></td>
                    <td><input type="text" name="onedown_download[downloads][<?php echo esc_attr($index); ?>][url]"
                            value="<?php echo esc_attr($item['url'] ?? ''); ?>"
                            placeholder="https://pan.baidu.com/s/xxx"></td>
                    <td><input type="text" name="onedown_download[downloads][<?php echo esc_attr($index); ?>][pwd]"
                            value="<?php echo esc_attr($item['pwd'] ?? ''); ?>"></td>
                    <td><input type="text" name="onedown_download[downloads][<?php echo esc_attr($index); ?>][size]"
                            value="<?php echo esc_attr($item['size'] ?? ''); ?>" placeholder="10MB"></td>
                    <td><input type="text" name="onedown_download[downloads][<?php echo esc_attr($index); ?>][desc]"
                            value="<?php echo esc_attr($item['desc'] ?? ''); ?>" placeholder="可选说明"></td>
                    <td><a class="od-dl-row-remove" onclick="this.closest('tr').remove()">&times;</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="od-dl-add-row">
            <button type="button" class="button" id="od-dl-add-btn">+ 添加资源</button>
        </div>
        <div class="od-dl-tip">检测到网盘地址（如 pan.baidu.com）将自动隐藏直接链接，显示为二维码供扫码访问</div>
    </div>
</div>

<script>
jQuery(function($) {
    var dlIndex = <?php echo count($downloads); ?>;
    $('#od-dl-add-btn').on('click', function() {
        var row = '<tr class="od-dl-row">' +
            '<td><input type="text" name="onedown_download[downloads][' + dlIndex +
            '][name]" value="" placeholder="资源名称"></td>' +
            '<td><input type="text" name="onedown_download[downloads][' + dlIndex +
            '][url]" value="" placeholder="https://pan.baidu.com/s/xxx"></td>' +
            '<td><input type="text" name="onedown_download[downloads][' + dlIndex +
            '][pwd]" value=""></td>' +
            '<td><input type="text" name="onedown_download[downloads][' + dlIndex +
            '][size]" value="" placeholder="10MB"></td>' +
            '<td><input type="text" name="onedown_download[downloads][' + dlIndex +
            '][desc]" value="" placeholder="可选说明"></td>' +
            '<td><a class="od-dl-row-remove" onclick="this.closest(\'tr\').remove()">&times;</a></td>' +
            '</tr>';
        $('#od-downloads-table tbody').append(row);
        dlIndex++;
    });
});
</script>
<?php
}

// ═══════════════════════════════════════════
// 3. 保存 Metabox 数据
// ═══════════════════════════════════════════

add_action('save_post_page', 'onedown_save_download_metabox', 10, 2);
/**
 * 保存下载资源数据
 */
function onedown_save_download_metabox($post_id, $post)
{
    if (! isset($_POST['onedown_download_metabox_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['onedown_download_metabox_nonce'])), 'onedown_save_download_metabox')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if ('page' !== $post->post_type || ! current_user_can('edit_post', $post_id)) {
        return;
    }

    $raw = isset($_POST['onedown_download']) && is_array($_POST['onedown_download']) ? wp_unslash($_POST['onedown_download']) : array();
    if (empty($raw)) {
        delete_post_meta($post_id, '_onedown_page_downloads');
        return;
    }

    $data = array(
        'subtitle'  => sanitize_text_field($raw['subtitle'] ?? ''),
        'downloads' => array(),
    );

    if (! empty($raw['downloads']) && is_array($raw['downloads'])) {
        foreach ($raw['downloads'] as $item) {
            $url = isset($item['url']) ? esc_url_raw($item['url']) : '';
            if ('' === $url) {
                continue;
            }
            $data['downloads'][] = array(
                'name' => sanitize_text_field($item['name'] ?? ''),
                'url'  => $url,
                'pwd'  => sanitize_text_field($item['pwd'] ?? ''),
                'size' => sanitize_text_field($item['size'] ?? ''),
                'desc' => sanitize_text_field($item['desc'] ?? ''),
            );
        }
    }

    update_post_meta($post_id, '_onedown_page_downloads', $data);
}

// ═══════════════════════════════════════════
// 4+. 前端渲染
// ═══════════════════════════════════════════

/**
 * 获取下载中转页 URL（自动检测或手动配置）
 *
 * 已配置：使用主题设置中指定的下载页面
 * 未配置：自动查找第一个使用"下载页面"模板的页面或使用内置路由
 *
 * @param int $post_id 文章 ID
 * @return string|false 中转页 URL 或 false
 */
function onedown_get_download_redirect_url($post_id, $item_index = 0)
{
    if (! function_exists('_pz')) {
        return false;
    }

    $enabled = _pz('download_redirect_enabled', false);
    if (! $enabled) {
        return false;
    }

    $mode = onedown_get_download_redirect_mode();
    if ($mode === 'qrcode') {
        return onedown_get_download_qrcode_url($post_id, $item_index);
    }

    if ($mode === 'custom') {
        $page_id = _pz('download_redirect_page', '');
        if ($page_id && '__builtin__' !== $page_id) {
            return add_query_arg(array(
                'post_id' => intval($post_id),
                'item'    => max(0, intval($item_index)),
            ), get_permalink(intval($page_id)));
        }
    }

    if (function_exists('onedown_download_page_url')) {
        return onedown_download_page_url(array(
            'post_id' => intval($post_id),
            'item'    => max(0, intval($item_index)),
        ));
    }

    return false;
}

// ═══════════════════════════════════════════
// 4+. 前端样式
// ═══════════════════════════════════════════

add_action('wp_head', 'onedown_download_page_css');
function onedown_download_page_css()
{
    // 仅在下载页面模板、虚拟下载路由或单篇文章（有付费下载）时输出
    $is_download_page = is_page() && 'page-templates/download.php' === get_page_template_slug();
    $is_virtual_page  = intval(get_query_var('od_download')) === 1;
    $is_pay_post      = is_singular('post') && function_exists('onedown_post_has_pay') && onedown_post_has_pay();

    if (! $is_download_page && ! $is_virtual_page && ! $is_pay_post) {
        return;
    }
?>
<style>
/* 网盘地址项 — 二维码为主要交互方式，保持可见 */
.od-dl-item-cloud .onedown-download-qrcode {
    border-left-color: var(--od-primary, #f04494);
}

.od-dl-item-cloud .onedown-download-qrcode-tip {
    color: var(--od-primary, #f04494);
    font-size: 11px;
}

/* 网盘资源仅二维码提示 */
.onedown-download-qr-only-tip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 8px 14px;
    border-radius: 8px;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
}

@media (max-width: 680px) {
    .od-dl-item-cloud .onedown-download-qrcode {
        display: block;
        padding-left: 0;
        border-left: none;
        padding-top: 10px;
        border-top: 1px solid #f3f4f6;
    }

    .od-dl-item-cloud .onedown-download-qrcode .onedown-download-qrcode-img {
        margin: 0 auto;
        width: 80px;
        height: 80px;
    }
}
</style>
<script>
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.od-btn-dl');
    if (btn && btn.getAttribute('data-url')) {
        e.preventDefault();
        window.open(btn.getAttribute('data-url'), '_blank');
    }
});
</script>
<?php
}

/**
 * 渲染下载资源区域
 *
 * @param int $post_id
 * @return string
 */
function onedown_render_page_downloads($post_id)
{
    $data = get_post_meta($post_id, '_onedown_page_downloads', true);
    $data = is_array($data) ? $data : array();

    if (empty($data['downloads']) || ! is_array($data['downloads'])) {
        return '';
    }

    $downloads = $data['downloads'];
    $html = '<div class="onedown-download-box">';
    $html .= '<div class="onedown-download-title"><i class="fa fa-download"></i> &#36164;&#28304;&#19979;&#36733;</div>';
    $html .= '<div class="onedown-download-list">';

    foreach ($downloads as $i => $dl) {
        $name = ! empty($dl['name']) ? $dl['name'] : ('&#36164;&#28304; ' . ($i + 1));
        $url  = ! empty($dl['url']) ? $dl['url'] : '#';
        $pwd  = ! empty($dl['pwd']) ? $dl['pwd'] : '';
        $size = ! empty($dl['size']) ? $dl['size'] : '';
        $desc = ! empty($dl['desc']) ? $dl['desc'] : '';

        $is_cloud    = onedown_is_cloud_drive_url($url);
        $cloud_icon  = $is_cloud ? onedown_cloud_drive_icon($url) : 'fa-file-archive-o';
        $cloud_color = $is_cloud ? onedown_cloud_drive_color($url) : '';
        $icon_style  = $cloud_color ? ' style="background:' . esc_attr($cloud_color) . '"' : '';
        $redirect_enabled = function_exists('_pz') ? (bool) _pz('download_redirect_enabled', false) : false;
        $go_url           = function_exists('onedown_get_gourl') ? onedown_get_gourl($url) : $url;
        if ($redirect_enabled && function_exists('onedown_get_download_redirect_url')) {
            $go_url = onedown_get_download_redirect_url($post_id, $i);
        }

        $html .= '<div class="onedown-download-item' . ($is_cloud ? ' is-cloud' : '') . '">';
        $html .= '<span class="od-item-icon"' . $icon_style . '><i class="fa ' . esc_attr($cloud_icon) . '"></i></span>';
        $html .= '<span class="od-item-body">';
        $html .= '<span class="od-item-name">' . esc_html($name) . '</span>';
        if ($desc) {
            $html .= '<span class="od-item-desc">' . esc_html($desc) . '</span>';
        }
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
        $html .= '<span class="od-item-actions">';
        if ($pwd) {
            $html .= '<button type="button" class="od-btn-copy" data-copy="' . esc_attr($pwd) . '"><i class="fa fa-copy"></i></button>';
        }
        $html .= '<a class="od-btn-dl' . ($is_cloud ? ' is-cloud' : '') . '" href="' . esc_url($go_url) . '" target="_blank" rel="nofollow noopener">'
            . ($is_cloud ? '' : '<i class="fa fa-download"></i> ')
            . ($is_cloud ? '&#25171;&#24320;&#38142;&#25509;' : '&#19979;&#36733;')
            . '</a>';
        $html .= '</span>';
        $html .= '</div>';
    }

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 * 获取下载页当前资源项
 *
 * @param int $post_id
 * @param int $item_index
 * @return array
 */
function onedown_get_download_page_item($post_id, $item_index = 0)
{
    $post_id    = intval($post_id);
    $item_index = max(0, intval($item_index));

    if ($post_id <= 0) {
        return array();
    }

    if (function_exists('onedown_post_pay_type') && function_exists('onedown_get_post_pay_data') && 'download' === onedown_post_pay_type($post_id)) {
        if (! function_exists('onedown_user_has_paid') || ! onedown_user_has_paid($post_id)) {
            return array();
        }

        $pay_data = onedown_get_post_pay_data($post_id);
        if (! empty($pay_data['pay_downloads'][$item_index]) && is_array($pay_data['pay_downloads'][$item_index])) {
            return $pay_data['pay_downloads'][$item_index];
        }

        if (! empty($pay_data['pay_downloads'][0]) && is_array($pay_data['pay_downloads'][0])) {
            return $pay_data['pay_downloads'][0];
        }
    }

    $page_data = get_post_meta($post_id, '_onedown_page_downloads', true);
    if (is_array($page_data) && ! empty($page_data['downloads'][$item_index]) && is_array($page_data['downloads'][$item_index])) {
        return $page_data['downloads'][$item_index];
    }

    if (is_array($page_data) && ! empty($page_data['downloads'][0]) && is_array($page_data['downloads'][0])) {
        return $page_data['downloads'][0];
    }

    return array();
}

/**
 * 二维码下载页渲染
 *
 * @return void
 */
function onedown_render_download_qrcode_page()
{
    $post_id    = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
    $item_index = isset($_GET['item']) ? intval($_GET['item']) : 0;

    if ($post_id <= 0) {
        wp_die('参数错误');
    }

    $item = onedown_get_download_page_item($post_id, $item_index);
    $url  = isset($item['url']) ? trim((string) $item['url']) : '';

    if ($url === '') {
        wp_die('下载资源不存在或无权访问');
    }

    $title         = isset($item['name']) && $item['name'] !== '' ? $item['name'] : get_the_title($post_id);
    $platform_name = onedown_is_cloud_drive_url($url) ? onedown_cloud_drive_name($url) : '网盘资源';
    $back_url      = get_permalink($post_id);
    $home_url      = home_url('/');
    $qrcode_lib    = trailingslashit(get_template_directory_uri()) . 'assets/js/qrcode-lib.min.js';

    status_header(200);
    nocache_headers();
    ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo esc_html($title); ?></title>
    <script src="<?php echo esc_url($qrcode_lib); ?>"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #8b5cf6;
            --secondary: #0ea5e9;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-900: #0f172a;
            --gray-800: #1e293b;
            --gray-700: #334155;
            --gray-600: #475569;
            --gray-500: #64748b;
            --gray-400: #94a3b8;
            --gray-300: #cbd5e1;
            --gray-200: #e2e8f0;
            --gray-100: #f1f5f9;
            --gray-50: #f8fafc;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            background-attachment: fixed;
            min-height: 100vh;
            color: var(--gray-800);
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 20% 80%, rgba(99, 102, 241, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.2) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }

        .main-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 28px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 400px;
            max-width: 100%;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            padding: 28px 24px;
            text-align: center;
        }

        .platform-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 30px;
            margin-bottom: 16px;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
        }

        .file-name {
            color: white;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.4;
            word-break: break-all;
        }

        .card-body {
            padding: 32px 28px;
        }

        .scan-tip {
            text-align: center;
            margin-bottom: 28px;
        }

        .scan-tip-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: white;
            font-size: 22px;
        }

        .scan-tip h3 {
            color: var(--gray-800);
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .scan-tip p {
            color: var(--gray-500);
            font-size: 14px;
            line-height: 1.6;
        }

        .highlight {
            color: var(--primary);
            font-weight: 600;
        }

        .qrcode-wrapper {
            background: linear-gradient(135deg, var(--gray-50), var(--gray-100));
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            border: 2px dashed var(--gray-200);
            margin-bottom: 24px;
        }

        #qrcode {
            width: 180px;
            height: 180px;
            margin: 0 auto;
            background: white;
            padding: 12px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        #qrcode img,
        #qrcode canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .qrcode-label {
            margin-top: 16px;
            color: var(--gray-500);
            font-size: 13px;
        }

        .steps {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .step {
            flex: 1;
            text-align: center;
            padding: 16px 8px;
            background: var(--gray-50);
            border-radius: 14px;
        }

        .step-num {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            margin: 0 auto 10px;
        }

        .step-text {
            color: var(--gray-600);
            font-size: 12px;
            line-height: 1.4;
        }

        .btn-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14px 20px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
        }

        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 900px) {
            .container {
                padding: 20px 16px;
            }

            .main-card {
                width: 100%;
                max-width: 420px;
            }

            .card-header {
                padding: 24px 20px;
            }

            .file-name {
                font-size: 18px;
            }

            .card-body {
                padding: 24px 20px;
            }

            .steps {
                flex-direction: column;
                gap: 8px;
            }

            .step {
                display: flex;
                align-items: center;
                gap: 12px;
                text-align: left;
                padding: 12px 16px;
            }

            .step-num {
                margin: 0;
                flex-shrink: 0;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-card">
            <div class="card-header">
                <div class="platform-badge"><?php echo esc_html($platform_name); ?></div>
                <div class="file-name"><?php echo esc_html($title); ?></div>
            </div>

            <div class="card-body">
                <div class="scan-tip">
                    <div class="scan-tip-icon">QR</div>
                    <h3>扫码获取资源</h3>
                    <p>请使用 <span class="highlight"><?php echo esc_html($platform_name); ?></span> App、微信或手机浏览器扫描二维码</p>
                </div>

                <div class="qrcode-wrapper">
                    <div id="qrcode"></div>
                    <div class="qrcode-label">手机扫码即可访问</div>
                </div>

                <div class="steps">
                    <div class="step">
                        <div class="step-num">1</div>
                        <div class="step-text">打开 App</div>
                    </div>
                    <div class="step">
                        <div class="step-num">2</div>
                        <div class="step-text">点击扫一扫</div>
                    </div>
                    <div class="step">
                        <div class="step-num">3</div>
                        <div class="step-text">扫描二维码</div>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="<?php echo esc_url($back_url); ?>" class="btn btn-secondary">返回文章</a>
                    <a href="<?php echo esc_url($home_url); ?>" class="btn btn-primary">返回首页</a>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">© 2024-2030 分享资源 · 滴水石穿</div>

    <script>
        (function () {
            var url = <?php echo wp_json_encode($url); ?>;
            var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

            if (isMobile) {
                window.location.href = url;
                return;
            }

            var qrcodeContainer = document.getElementById('qrcode');
            if (!qrcodeContainer || typeof qrcode === 'undefined') {
                return;
            }

            qrcodeContainer.innerHTML = '';
            qrcode.stringToBytes = qrcode.stringToBytesFuncs['UTF-8'];
            var qr = qrcode(0, 'H');
            qr.addData(url);
            qr.make();
            qrcodeContainer.innerHTML = qr.createImgTag(6, 12, '下载二维码');
        })();
    </script>
</body>
</html>
<?php
}
