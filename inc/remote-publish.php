<?php

/**
 * Onedown 远程发布接口
 *
 * 内置于「拓展&增强 → 远程发布接口」，提供免登录的文章远程发布/更新接口，
 * 支持鉴权（普通 / 安全签名）、特色图片远程下载、网盘链接有效性检测，
 * 并将付费参数映射到主题原生的 _onedown_pay_metabox 数据结构。
 *
 * 参考：plugins/skycaiji_wp
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_nopriv_onedown_remote_publish', 'onedown_remote_publish_handle');
add_action('wp_ajax_onedown_remote_publish', 'onedown_remote_publish_handle');

/**
 * 接口入口：鉴权 -> 发布/更新文章
 */
function onedown_remote_publish_handle()
{
    if (! _pz('remote_pub_enabled', false)) {
        onedown_remote_publish_json(0, '远程发布接口未启用');
    }

    if (strtolower($_SERVER['REQUEST_METHOD'] ?? '') !== 'post') {
        onedown_remote_publish_json(0, '请求方式错误，必须为 POST');
    }

    $apikey = (string) _pz('remote_pub_apikey', '');
    if ('' === $apikey) {
        onedown_remote_publish_json(0, '接口密钥未配置');
    }

    // 鉴权
    $apitype = (string) _pz('remote_pub_apitype', '');
    if ('safe' === $apitype) {
        $api_time = isset($_POST['api_time']) ? intval($_POST['api_time']) : 0;
        $api_sign = isset($_POST['api_sign']) ? (string) $_POST['api_sign'] : '';
        if ($api_sign !== md5($api_time . $apikey)) {
            onedown_remote_publish_json(0, '签名校验失败');
        }
        if (time() - $api_time > 600) {
            onedown_remote_publish_json(0, '签名已过期：' . date('Y-m-d H:i:s', $api_time));
        }
    } else {
        $key = isset($_GET['apikey']) ? (string) $_GET['apikey'] : '';
        if ($key !== md5($apikey)) {
            onedown_remote_publish_json(0, '密钥校验失败');
        }
    }

    onedown_remote_publish_post();
}

/**
 * 发布 / 更新文章主逻辑
 */
function onedown_remote_publish_post()
{
    if (empty($_POST['title'])) {
        onedown_remote_publish_json(0, '标题为空');
    }
    if (empty($_POST['content'])) {
        onedown_remote_publish_json(0, '内容为空');
    }

    $post = is_array($_POST) ? wp_unslash($_POST) : array();

    // 作者（支持多行随机：每行一个登录名或ID）
    $author_id = onedown_remote_publish_pick_author((string) _pz('remote_pub_author', ''));
    if ($author_id <= 0) {
        onedown_remote_publish_json(0, '作者不存在，请在接口设置中配置有效作者');
    }

    // 更新模式：按原始标题查找文章
    $is_update      = isset($post['is_update']) && '1' == $post['is_update'];
    $original_title = (string) $post['title'];
    $update_post_id = 0;
    if ($is_update) {
        $existing = get_posts(array(
            'post_type'      => 'post',
            'post_status'    => 'any',
            'title'          => $original_title,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ));
        if (! empty($existing)) {
            $update_post_id = intval($existing[0]);
        }
        $post['title'] = $original_title . '【' . date('Ymd') . '更新】';
    }

    // 下载地址校验（网盘域名 + 可选有效性检测）
    $down_url = isset($post['down_url']) ? trim((string) $post['down_url']) : '';
    if ('' !== $down_url) {
        if (! preg_match('/^https?:\/\//i', $down_url)) {
            onedown_remote_publish_json(0, '下载地址无效');
        }
        if (! onedown_remote_publish_is_pan_domain($down_url)) {
            onedown_remote_publish_json(0, '下载地址非网盘链接');
        }
        $check_valid = isset($post['check_link_valid']) ? $post['check_link_valid'] : _pz('remote_pub_check_link_valid', false);
        if ('1' == $check_valid || true === $check_valid) {
            $result = onedown_remote_publish_check_link($down_url);
            if ('invalid' === $result['status']) {
                onedown_remote_publish_json(0, '下载地址失效：' . $result['message']);
            }
        }
    }

    // 组装 REST 请求体
    $body = array(
        'title'   => $post['title'],
        'content' => $post['content'],
        'status'  => ! empty($post['status']) ? $post['status'] : 'publish',
    );

    if (! empty($post['excerpt'])) {
        $body['excerpt'] = $post['excerpt'];
    }
    if (! empty($post['password'])) {
        $body['password'] = $post['password'];
    }
    if (! empty($post['slug'])) {
        $body['slug'] = $post['slug'];
    }
    if (! empty($post['format'])) {
        $body['format'] = $post['format'];
    }
    if (! empty($post['date'])) {
        $ts = strtotime($post['date']);
        if ($ts) {
            $body['date'] = date('Y-m-d\TH:i:s', $ts);
        }
    }
    $body['comment_status'] = (isset($post['comment_status']) && '0' == $post['comment_status']) ? 'closed' : 'open';
    $body['ping_status']    = (isset($post['ping_status']) && '0' == $post['ping_status']) ? 'closed' : 'open';
    if (isset($post['sticky'])) {
        $body['sticky'] = ('1' == $post['sticky']);
    }

    // 分类
    $cat_ids = onedown_remote_publish_resolve_categories($post['categories'] ?? '');
    if (! empty($cat_ids)) {
        $body['categories'] = $cat_ids;
    }
    // 标签
    $tag_ids = onedown_remote_publish_resolve_tags($post['tags'] ?? '');
    if (! empty($tag_ids)) {
        $body['tags'] = $tag_ids;
    }
    // 特色图片
    $featured = onedown_remote_publish_resolve_featured($post['featured_media'] ?? '');
    if ($featured > 0) {
        $body['featured_media'] = $featured;
    }

    // 执行发布 / 更新
    if ($is_update && $update_post_id > 0) {
        $request = new WP_REST_Request('PUT', '/wp/v2/posts/' . $update_post_id);
    } else {
        $request = new WP_REST_Request('POST', '/wp/v2/posts');
    }

    // 先以目标作者身份执行，避免 WP_REST_Posts_Controller 权限校验拒绝
    wp_set_current_user($author_id);
    wp_get_current_user();

    // 给匿名/低权限用户临时放行必要 capability（仅本次请求内生效，不写入数据库）
    add_filter('user_has_cap', function($allcaps, $caps, $args) use ($author_id) {
        if (isset($args[1]) && intval($args[1]) === intval($author_id)) {
            foreach (array('read', 'edit_posts', 'create_posts', 'publish_posts', 'upload_files') as $cap) {
                if (!isset($allcaps[$cap]) || !$allcaps[$cap]) {
                    $allcaps[$cap] = true;
                }
            }
        }
        return $allcaps;
    }, 99, 3);

    // 让 REST 用当前身份作为作者，不再通过 body 覆盖 author（否则触发 "您不能为此用户创建文章"）
    unset($body['author']);

    $request->set_body_params($body);
    $response = rest_do_request($request);

    if ($response->is_error()) {
        $errors = $response->as_error()->errors;
        foreach ($errors as $k => $v) {
            $errors[$k] = implode(', ', $v);
        }
        onedown_remote_publish_json(0, implode('; ', $errors));
    }

    $data = $response->get_data();
    $id   = ! empty($data['id']) ? intval($data['id']) : 0;
    if ($id <= 0) {
        onedown_remote_publish_json(0, $is_update ? '更新失败' : '发布失败');
    }

    // 付费数据映射到 _onedown_pay_metabox
    onedown_remote_publish_save_pay($id, $post, $down_url);

    onedown_remote_publish_json($id, '', get_permalink($id));
}

/**
 * 输出 JSON 并结束请求
 *
 * @param int    $id     文章ID（失败为0）
 * @param string $error  错误信息
 * @param string $target 文章链接
 * @param string $desc   附加描述
 */
function onedown_remote_publish_json($id, $error = '', $target = '', $desc = '')
{
    if (function_exists('ob_get_level') && ob_get_level()) {
        @ob_clean();
    }
    $data = array(
        'id'     => $id,
        'target' => $target,
        'desc'   => $desc,
        'error'  => $error ? ('远程发布接口：' . $error) : '',
    );
    header('Content-Type: application/json; charset=utf-8');
    exit(wp_json_encode($data));
}

/**
 * 从配置（多行）中随机选取一个作者并返回用户ID
 */
function onedown_remote_publish_pick_author($raw)
{
    $lines = preg_split('/[\r\n]+/', $raw);
    $lines = array_values(array_filter(array_map('trim', (array) $lines)));
    if (empty($lines)) {
        return 0;
    }
    $value = $lines[array_rand($lines)];

    if (is_numeric($value)) {
        $user = get_user_by('id', intval($value));
    } else {
        $user = get_user_by('login', $value);
    }
    return $user ? intval($user->ID) : 0;
}

/**
 * 解析分类：支持 id / 名称 / 别名，逗号分隔，不存在的名称自动创建
 *
 * @return int[]
 */
function onedown_remote_publish_resolve_categories($raw)
{
    if (empty($raw)) {
        return array();
    }
    $items = array_filter(array_map('trim', explode(',', $raw)));
    $ids   = array();
    foreach ($items as $item) {
        if (is_numeric($item)) {
            if (term_exists((int) $item, 'category')) {
                $ids[(int) $item] = (int) $item;
            }
            continue;
        }
        $term = get_term_by('name', $item, 'category') ?: get_term_by('slug', $item, 'category');
        if (! $term) {
            $new = wp_insert_term($item, 'category');
            if (! is_wp_error($new)) {
                $ids[$new['term_id']] = (int) $new['term_id'];
            }
        } else {
            $ids[$term->term_id] = (int) $term->term_id;
        }
    }
    return array_values($ids);
}

/**
 * 解析标签：名称逗号分隔，不存在自动创建
 *
 * @return int[]
 */
function onedown_remote_publish_resolve_tags($raw)
{
    if (empty($raw)) {
        return array();
    }
    $items = array_filter(array_map('trim', explode(',', $raw)));
    $ids   = array();
    foreach ($items as $item) {
        $term = get_term_by('name', $item, 'post_tag');
        if (! $term) {
            $new = wp_insert_term($item, 'post_tag');
            if (! is_wp_error($new)) {
                $ids[] = (int) $new['term_id'];
            }
        } else {
            $ids[] = (int) $term->term_id;
        }
    }
    return array_values(array_unique($ids));
}

/**
 * 解析特色图片：图片URL自动下载到媒体库，或直接返回附件ID
 *
 * @return int 附件ID，0表示无
 */
function onedown_remote_publish_resolve_featured($value)
{
    if (empty($value)) {
        return 0;
    }
    if (is_numeric($value)) {
        return intval($value);
    }
    if (! preg_match('/^https?:\/\//i', $value)) {
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($value);
    if (is_wp_error($tmp)) {
        return 0;
    }
    $file_array = array(
        'name'     => wp_basename(parse_url($value, PHP_URL_PATH)) ?: (md5($value) . '.jpg'),
        'tmp_name' => $tmp,
    );
    $attachment_id = media_handle_sideload($file_array, 0);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return 0;
    }
    return intval($attachment_id);
}

/**
 * 将接口付费参数映射并写入主题原生 _onedown_pay_metabox 结构
 *
 * @param int    $post_id  文章ID
 * @param array  $post     POST 数据（已 unslash）
 * @param string $down_url 下载地址
 */
function onedown_remote_publish_save_pay($post_id, $post, $down_url)
{
    // 付费类型：兼容 no/read/download 与数字（0免费 1付费阅读 2付费下载）
    $type_map  = array('0' => 'no', '1' => 'read', '2' => 'download', 'no' => 'no', 'read' => 'read', 'download' => 'download');
    $raw_type  = isset($post['pay_type']) ? (string) $post['pay_type'] : (string) _pz('remote_pub_pay_type', 'no');
    $pay_type  = $type_map[$raw_type] ?? 'no';

    // 未开启付费则不写入
    if ('no' === $pay_type) {
        return;
    }

    $pay_price = isset($post['pay_price']) ? floatval($post['pay_price']) : floatval(_pz('remote_pub_pay_price', 0));
    $pay_orig  = isset($post['pay_original_price']) ? floatval($post['pay_original_price']) : floatval(_pz('remote_pub_pay_original_price', 0));

    // 购买权限：兼容 all/logged_in/vip_only 与数字（0所有人 1登录 2会员）
    $perm_map = array('0' => 'all', '1' => 'logged_in', '2' => 'vip_only', 'all' => 'all', 'logged_in' => 'logged_in', 'vip_only' => 'vip_only');
    $raw_perm = isset($post['buy_permission']) ? (string) $post['buy_permission'] : (string) _pz('remote_pub_buy_permission', 'all');
    $buy_perm = $perm_map[$raw_perm] ?? 'all';

    // 会员价：vip_prices="monthly:10,yearly:20"
    $vip_prices = array();
    $levels     = function_exists('onedown_vip_levels') ? onedown_vip_levels() : array();
    if (! empty($post['vip_prices'])) {
        foreach (explode(',', $post['vip_prices']) as $pair) {
            $parts = explode(':', $pair);
            if (count($parts) === 2) {
                $lid = sanitize_key(trim($parts[0]));
                if (isset($levels[$lid]) && is_numeric(trim($parts[1]))) {
                    $vip_prices[$lid] = max(0, floatval(trim($parts[1])));
                }
            }
        }
    }

    // 下载资源
    $downloads = array();
    if ('' !== $down_url) {
        $downloads[] = array(
            'name' => isset($post['down_name']) && '' !== trim((string) $post['down_name']) ? sanitize_text_field($post['down_name']) : '立即下载',
            'url'  => esc_url_raw($down_url),
            'pwd'  => isset($post['more']) ? sanitize_text_field($post['more']) : '',
            'size' => isset($post['down_size']) ? sanitize_text_field($post['down_size']) : '',
        );
    }

    $data = array(
        'pay_type'           => $pay_type,
        'pay_price'          => max(0, $pay_price),
        'pay_original_price' => max(0, $pay_orig),
        'pay_sales'          => 0,
        'buy_permission'     => $buy_perm,
        'pay_vip_prices'     => $vip_prices,
        'pay_downloads'      => $downloads,
    );

    update_post_meta($post_id, '_onedown_pay_metabox', $data);
}

/**
 * 判断是否为已知网盘域名
 */
function onedown_remote_publish_is_pan_domain($url)
{
    $host = parse_url($url, PHP_URL_HOST);
    if (empty($host)) {
        return false;
    }
    $host    = strtolower($host);
    $domains = array(
        'pan.baidu.com', 'yun.baidu.com', 'd.pcs.baidu.com',
        'aliyundrive.com', 'drive.aliyun.com', 'alipan.com',
        'pan.quark.cn', 'quark.cn', 'd.quark.cn',
        'cloud.189.cn', 'yun.189.cn', '189.cn',
        'yun.139.com', '139.com', 'caiyun.139.com',
        'weiyun.com', 'yun.qq.com',
        '115.com', 'pan.115.com', '115cdn.com',
        'lanzou.com', 'lanzouq.com', 'lanzoux.com', 'lanzoui.com',
        'jianguoyun.com', 'd.jianguoyun.com',
        'pan.xunlei.com', 'xunlei.com',
        '123pan.com', 'd.123pan.com',
        'ctfile.com', 'd.ctfile.com',
        'onedrive.live.com', 'live.com', 'onedrive.com',
        'drive.google.com', 'google.com', 'docs.google.com',
        'icloud.com', 'drive.icloud.com',
        'dropbox.com', 'dl.dropbox.com',
    );
    foreach ($domains as $d) {
        if ($host === $d || substr($host, -strlen($d) - 1) === '.' . $d) {
            return true;
        }
    }
    return false;
}

/**
 * 检测网盘链接有效性
 *
 * @return array{status:string,message:string} status: valid|invalid|private|login|unknown
 */
function onedown_remote_publish_check_link($url)
{
    $response = wp_remote_get($url, array(
        'timeout'     => 15,
        'redirection' => 5,
        'sslverify'   => false,
        'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ));

    if (is_wp_error($response)) {
        return array('status' => 'unknown', 'message' => '请求失败: ' . $response->get_error_message());
    }

    $code    = (int) wp_remote_retrieve_response_code($response);
    $content = strtolower((string) wp_remote_retrieve_body($response));

    if (404 === $code) {
        return array('status' => 'invalid', 'message' => '404链接不存在');
    }
    if (403 === $code || $code >= 500) {
        return array('status' => 'unknown', 'message' => '访问异常，状态码: ' . $code);
    }

    $invalid_keywords = array(
        '文件删除', '链接不存在', '违规下架', '分享取消', '已过期', '资源失效',
        '文件不存在', '该链接已失效', '分享已失效', '无法访问', '已被删除', '违规内容',
        '分享已取消', '链接已过期', '资源不存在', '页面不存在', '访问被拒绝',
        '此链接已失效', '提取失败', '不存在的页面', '已停止分享', '文件已删除',
        'not found', 'forbidden',
    );
    foreach ($invalid_keywords as $kw) {
        if (false !== strpos($content, strtolower($kw))) {
            return array('status' => 'invalid', 'message' => '检测到失效特征: ' . $kw);
        }
    }

    $private_keywords = array('提取码', '访问密码', '请输入密码', '密码保护');
    foreach ($private_keywords as $kw) {
        if (false !== strpos($content, strtolower($kw))) {
            return array('status' => 'private', 'message' => '链接有效，需要提取码');
        }
    }

    $login_keywords = array('请登录', '登录后查看', '登录账号', '强制登录');
    foreach ($login_keywords as $kw) {
        if (false !== strpos($content, strtolower($kw))) {
            return array('status' => 'login', 'message' => '需要登录查看');
        }
    }

    return array('status' => 'valid', 'message' => '链接有效');
}

/**
 * 生成后台「接口信息」文档 HTML（表格形式）
 *
 * @return string
 */
function onedown_remote_publish_doc_html()
{
    $base_api_url = admin_url('admin-ajax.php?action=onedown_remote_publish');

    // 普通模式下，密钥确定后直接拼接到接口地址（安全模式不拼接，改用签名）
    $apikey       = _pz('remote_pub_apikey');
    $apitype      = _pz('remote_pub_apitype');
    $api_url      = $base_api_url;
    if ('safe' !== $apitype && '' !== (string) $apikey) {
        $api_url .= '&apikey=' . md5($apikey);
    }

    $api_url_escaped      = esc_html($api_url);
    $base_api_url_escaped = esc_attr($base_api_url);
    $apikey_escaped       = esc_attr((string) $apikey);
    $apitype_escaped      = esc_attr((string) $apitype);

    // 参数表格行：array(参数名, 必填, 说明)
    $params = array(
        array('title', 'required', '文章标题'),
        array('content', 'required', '文章内容（支持 HTML）'),
        array('excerpt', 'optional', '文章摘要'),
        array('slug', 'optional', '文章别名（URL 缩略名）'),
        array('status', 'optional', '发布状态，默认 publish（可选 draft、pending、private 等）'),
        array('date', 'optional', '发布时间，如 2026-06-13 12:00:00'),
        array('categories', 'optional', '分类，逗号分隔，支持名称 / ID，不存在自动创建'),
        array('tags', 'optional', '标签，逗号分隔，不存在自动创建'),
        array('featured_media', 'optional', '特色图：图片 URL（自动下载）或附件 ID'),
        array('is_update', 'optional', '1 = 更新模式，按原标题匹配已有文章'),
        array('down_url', 'optional', '网盘下载地址（仅限白名单网盘域名）'),
        array('down_name', 'optional', '下载资源名称，默认「立即下载」'),
        array('down_size', 'optional', '资源大小，如 120MB'),
        array('more', 'optional', '网盘提取码 / 解压密码'),
        array('pay_type', 'optional', '付费类型：no / read / download（或 0 / 1 / 2）'),
        array('pay_price', 'optional', '价格'),
        array('pay_original_price', 'optional', '原价'),
        array('buy_permission', 'optional', '购买权限：all / logged_in / vip_only（或 0 / 1 / 2）'),
        array('vip_prices', 'optional', '会员价，如 monthly:10,yearly:20'),
        array('check_link_valid', 'optional', '1 = 发布前检测网盘链接有效性'),
    );

    // 返回字段：array(字段名, 说明)
    $returns = array(
        array('id', '文章 ID，0 表示失败'),
        array('target', '文章访问链接'),
        array('error', '错误信息（成功时为空）'),
    );

    ob_start();
    ?>
    <div class="onedown-rp-doc" data-base-url="<?php echo $base_api_url_escaped; ?>" data-apikey="<?php echo $apikey_escaped; ?>" data-apitype="<?php echo $apitype_escaped; ?>">
        <div class="onedown-rp-alert onedown-rp-alert-warning">
            <?php _e('请先开启「远程发布接口」并设置 <b>API 密钥</b>，再将以下接口配置填入采集 / 发布工具。所有请求必须使用 <b>POST</b> 方式，数据以 <b>UTF-8</b> 编码提交。', 'onedown'); ?>
        </div>

        <h4 class="onedown-rp-title"><?php esc_html_e('基础信息', 'onedown'); ?></h4>
        <table class="onedown-rp-table onedown-rp-base">
            <tbody>
                <tr>
                    <th><?php esc_html_e('接口地址', 'onedown'); ?></th>
                    <td><code class="onedown-rp-api-url"><?php echo $api_url_escaped; ?></code> <span class="onedown-rp-method">POST</span></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('请求方式', 'onedown'); ?></th>
                    <td>POST</td>
                </tr>
                <tr>
                    <th><?php esc_html_e('字符集', 'onedown'); ?></th>
                    <td>UTF-8</td>
                </tr>
                <tr>
                    <th><?php esc_html_e('普通模式', 'onedown'); ?></th>
                    <td class="onedown-rp-normal-tip">
                        <?php if ('safe' !== $apitype && '' !== (string) $apikey) : ?>
                            <?php _e('密钥已拼接到上方接口地址（<code>&apikey=md5(密钥)</code>），可直接调用。', 'onedown'); ?>
                        <?php else : ?>
                            <?php _e('请求时在 URL 后追加 <code>&apikey=md5(密钥)</code>', 'onedown'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('安全模式', 'onedown'); ?></th>
                    <td><?php _e('POST 提交 <code>api_time</code>（当前时间戳）与 <code>api_sign=md5(api_time+密钥)</code>，签名有效期 600 秒', 'onedown'); ?></td>
                </tr>
            </tbody>
        </table>

        <h4 class="onedown-rp-title"><?php esc_html_e('请求参数', 'onedown'); ?></h4>
        <table class="onedown-rp-table">
            <thead>
                <tr>
                    <th class="col-name"><?php esc_html_e('参数', 'onedown'); ?></th>
                    <th class="col-req"><?php esc_html_e('必填', 'onedown'); ?></th>
                    <th><?php esc_html_e('说明', 'onedown'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($params as $row) : ?>
                    <tr>
                        <td><code><?php echo esc_html($row[0]); ?></code></td>
                        <td>
                            <?php if ('required' === $row[1]) : ?>
                                <span class="onedown-rp-tag is-required"><?php esc_html_e('必填', 'onedown'); ?></span>
                            <?php else : ?>
                                <span class="onedown-rp-tag is-optional"><?php esc_html_e('可选', 'onedown'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($row[2]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4 class="onedown-rp-title"><?php esc_html_e('返回字段', 'onedown'); ?></h4>
        <table class="onedown-rp-table">
            <thead>
                <tr>
                    <th class="col-name"><?php esc_html_e('字段', 'onedown'); ?></th>
                    <th><?php esc_html_e('说明', 'onedown'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($returns as $row) : ?>
                    <tr>
                        <td><code><?php echo esc_html($row[0]); ?></code></td>
                        <td><?php echo esc_html($row[1]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="onedown-rp-alert onedown-rp-alert-info">
            <?php _e('成功时返回文章 <code>id</code> 与访问链接 <code>target</code>；失败时 <code>id</code> 为 0，<code>error</code> 返回具体错误信息。', 'onedown'); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
