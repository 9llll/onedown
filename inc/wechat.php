<?php
/**
 * onedown 微信工具模块
 *
 * 微信公众号接口验证 / 自动回复 / 自定义菜单 / 微信登录 / JS-SDK
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

// ════════════════════════════════════════════
// 工具函数
// ════════════════════════════════════════════

function onedown_wechat_config($key, $default = '')
{
    return function_exists('_pz') ? _pz($key, $default) : $default;
}

// ════════════════════════════════════════════
// Access Token 管理
// ════════════════════════════════════════════

function onedown_wechat_get_access_token()
{
    $app_id     = onedown_wechat_config('wechat_app_id');
    $app_secret = onedown_wechat_config('wechat_app_secret');

    if (empty($app_id) || empty($app_secret)) {
        return false;
    }

    $cached = get_transient('onedown_wechat_access_token');
    if (! empty($cached)) {
        return $cached;
    }

    $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . urlencode($app_id) . '&secret=' . urlencode($app_secret);

    $response = wp_remote_get($url, array('timeout' => 15));
    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['access_token'])) {
        return false;
    }

    set_transient('onedown_wechat_access_token', $body['access_token'], intval($body['expires_in']) - 300);
    return $body['access_token'];
}

// ════════════════════════════════════════════
// JS-SDK 支持
// ════════════════════════════════════════════

function onedown_wechat_get_jsapi_ticket()
{
    $access_token = onedown_wechat_get_access_token();
    if (empty($access_token)) {
        return false;
    }

    $cached = get_transient('onedown_wechat_jsapi_ticket');
    if (! empty($cached)) {
        return $cached;
    }

    $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . urlencode($access_token) . '&type=jsapi';

    $response = wp_remote_get($url, array('timeout' => 15));
    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['ticket'])) {
        return false;
    }

    set_transient('onedown_wechat_jsapi_ticket', $body['ticket'], intval($body['expires_in']) - 300);
    return $body['ticket'];
}

/**
 * 生成 JS-SDK 签名配置
 *
 * @param string $url 当前页面完整 URL，默认自动获取
 * @return array|false
 */
function onedown_wechat_get_jsapi_signature($url = '')
{
    $ticket = onedown_wechat_get_jsapi_ticket();
    if (empty($ticket)) {
        return false;
    }

    $app_id = onedown_wechat_config('wechat_app_id');
    if (empty($app_id)) {
        return false;
    }

    if (empty($url)) {
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    $timestamp = time();
    $nonce_str = wp_generate_password(16, false);
    $param_str = "jsapi_ticket={$ticket}&noncestr={$nonce_str}&timestamp={$timestamp}&url={$url}";

    return array(
        'app_id'     => $app_id,
        'timestamp'  => $timestamp,
        'nonce_str'  => $nonce_str,
        'signature'  => sha1($param_str),
        'url'        => $url,
    );
}

// ════════════════════════════════════════════
// 服务器接口验证 & 消息处理入口
// ════════════════════════════════════════════

add_action('init', 'onedown_wechat_route_init');
function onedown_wechat_route_init()
{
    add_rewrite_rule('^wechat/api/?$', 'index.php?onedown_wechat_api=1', 'top');
}

add_filter('query_vars', 'onedown_wechat_query_vars');
function onedown_wechat_query_vars($vars)
{
    $vars[] = 'onedown_wechat_api';
    return $vars;
}

add_action('template_redirect', 'onedown_wechat_handle_request');
function onedown_wechat_handle_request()
{
    if (! get_query_var('onedown_wechat_api')) {
        return;
    }

    $token = onedown_wechat_config('wechat_token');
    if (empty($token)) {
        status_header(403);
        exit;
    }

    $signature = isset($_GET['signature']) ? $_GET['signature'] : '';
    $timestamp = isset($_GET['timestamp']) ? $_GET['timestamp'] : '';
    $nonce     = isset($_GET['nonce']) ? $_GET['nonce'] : '';
    $echostr   = isset($_GET['echostr']) ? $_GET['echostr'] : '';
    $msg_signature = isset($_GET['msg_signature']) ? $_GET['msg_signature'] : '';

    // 验证签名
    $tmp_arr = array($token, $timestamp, $nonce);
    sort($tmp_arr, SORT_STRING);
    $tmp_str = sha1(implode($tmp_arr));

    if ($tmp_str !== $signature) {
        status_header(403);
        echo 'Invalid signature';
        exit;
    }

    // 验证模式
    if (! empty($echostr)) {
        echo $echostr;
        exit;
    }

    // 处理消息
    $post_data = file_get_contents('php://input');
    if (empty($post_data)) {
        exit;
    }

    // 安全模式解密
    $encoding_aes_key = onedown_wechat_config('wechat_encoding_aes_key', '');
    if (! empty($encoding_aes_key) && ! empty($msg_signature)) {
        $post_data = onedown_wechat_decrypt_msg($post_data, $encoding_aes_key, $msg_signature, $timestamp, $nonce);
        if (empty($post_data)) {
            exit;
        }
    }

    $xml = simplexml_load_string($post_data, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (false === $xml) {
        exit;
    }

    $from_user = (string) $xml->FromUserName;
    $to_user   = (string) $xml->ToUserName;
    $msg_type  = (string) $xml->MsgType;

    switch ($msg_type) {
        case 'event':
            onedown_wechat_handle_event($xml, $from_user, $to_user);
            break;
        case 'text':
            onedown_wechat_handle_text($xml, $from_user, $to_user);
            break;
        case 'image':
        case 'voice':
        case 'video':
        case 'location':
        case 'link':
            onedown_wechat_send_text($from_user, $to_user, onedown_wechat_config('wechat_default_reply', '您好，请输入有效关键词。'));
            break;
        default:
            // 空回复表示"不回复"
            break;
    }

    exit;
}

// ════════════════════════════════════════════
// 安全模式加解密
// ════════════════════════════════════════════

/**
 * 解密微信安全模式消息
 */
function onedown_wechat_decrypt_msg($post_data, $encoding_aes_key, $msg_signature, $timestamp, $nonce)
{
    $xml = simplexml_load_string($post_data, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (false === $xml || empty($xml->Encrypt)) {
        return '';
    }

    $encrypt = (string) $xml->Encrypt;

    // 验证签名
    $tmp_arr = array(onedown_wechat_config('wechat_token'), $timestamp, $nonce, $encrypt);
    sort($tmp_arr, SORT_STRING);
    if (sha1(implode($tmp_arr)) !== $msg_signature) {
        return '';
    }

    // 解密
    $aes_key = base64_decode($encoding_aes_key . '=');
    $decrypted = openssl_decrypt(base64_decode($encrypt), 'AES-256-CBC', $aes_key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($aes_key, 0, 16));
    if (false === $decrypted) {
        return '';
    }

    // 去除 PKCS7 填充
    $pad = ord(substr($decrypted, -1));
    if ($pad > 0 && $pad <= 32) {
        $decrypted = substr($decrypted, 0, -$pad);
    }

    // 提取内容：前4字节为网络字节序的内容长度
    $content_start = 16; // 跳过16字节的随机串
    $net_length = substr($decrypted, $content_start, 4);
    $content_length = unpack('N', $net_length)[1];
    $content = substr($decrypted, $content_start + 4, $content_length);
    // 剩余部分是 AppId，可忽略

    return $content;
}

// ════════════════════════════════════════════
// 事件处理
// ════════════════════════════════════════════

function onedown_wechat_handle_event($xml, $from_user, $to_user)
{
    $event     = (string) $xml->Event;
    $event_key = (string) $xml->EventKey;

    switch ($event) {
        case 'subscribe':
            // 扫码关注：EventKey 格式 qrscene_123
            if (! empty($event_key) && strpos($event_key, 'qrscene_') !== false) {
                $scene_id = str_replace('qrscene_', '', $event_key);
                $scene_id = intval($scene_id);
                if ($scene_id > 0) {
                    // 扫码关注可能是登录场景
                    $scene_user_id = get_transient('onedown_wechat_scan_' . $scene_id);
                    if (! empty($scene_user_id)) {
                        onedown_wechat_send_login_url($from_user, $to_user, $scene_user_id, $scene_id);
                        return;
                    }
                }
                $reply = onedown_wechat_config('wechat_subscribe_scan_reply', '感谢您的关注！');
            } else {
                $reply = onedown_wechat_config('wechat_subscribe_normal_reply', '感谢您的关注！回复「帮助」查看更多功能。');
            }
            onedown_wechat_send_text($from_user, $to_user, $reply);
            break;

        case 'SCAN':
            // 已关注用户扫码
            $scene_id = intval($event_key);
            if ($scene_id > 0) {
                $scene_user_id = get_transient('onedown_wechat_scan_' . $scene_id);
                if (! empty($scene_user_id)) {
                    onedown_wechat_send_login_url($from_user, $to_user, $scene_user_id, $scene_id);
                }
            }
            break;

        case 'CLICK':
            onedown_wechat_handle_menu_click($event_key, $from_user, $to_user);
            break;

        case 'unsubscribe':
            // 静默处理
            break;

        default:
            break;
    }
}

/**
 * 发送登录确认链接
 */
function onedown_wechat_send_login_url($openid, $to_user, $user_id, $scene_id)
{
    $login_enabled = onedown_wechat_config('wechat_login_enabled', false);
    if (! $login_enabled) {
        return;
    }

    $login_code = wp_generate_password(32, false);
    $login_url  = add_query_arg(array(
        'wechat_login' => 1,
        'code'  => $login_code,
        'openid' => $openid,
        'scene'  => $scene_id,
    ), home_url('/'));

    // 存储登录凭证（5分钟有效）
    set_transient('onedown_wechat_login_' . $login_code, array(
        'openid'   => $openid,
        'user_id'  => $user_id,
        'time'     => time(),
    ), 300);

    // 清除已使用的场景值
    delete_transient('onedown_wechat_scan_' . $scene_id);

    $login_url_short = home_url('/?wxlogin=' . $login_code);
    $reply_template = onedown_wechat_config('wechat_scan_login_reply', '您正在登录 {site_name}，请点击以下链接确认登录：{login_url}。如非本人操作请忽略。');
    $reply = str_replace(
        array('{site_name}', '{login_url}'),
        array(get_bloginfo('name'), $login_url_short),
        $reply_template
    );

    onedown_wechat_send_text($openid, $to_user, $reply);
}

function onedown_wechat_handle_menu_click($key, $from_user, $to_user)
{
    switch ($key) {
        case 'help':
            $reply = "欢迎使用！\n常见功能：\n1. 回复关键词获取信息\n2. 点击菜单浏览网站\n3. 扫码登录网站账号";
            break;
        default:
            $reply = onedown_wechat_config('wechat_default_reply', '您好，请输入有效关键词。');
            break;
    }
    onedown_wechat_send_text($from_user, $to_user, $reply);
}

// ════════════════════════════════════════════
// 文本消息 & 关键词自动回复
// ════════════════════════════════════════════

function onedown_wechat_handle_text($xml, $from_user, $to_user)
{
    $content = trim((string) $xml->Content);
    if (empty($content)) {
        onedown_wechat_send_text($from_user, $to_user, onedown_wechat_config('wechat_default_reply', '您好，请输入有效关键词。'));
        return;
    }

    $keyword_enabled = onedown_wechat_config('wechat_keyword_reply_enabled', false);
    if ($keyword_enabled) {
        $rules = onedown_wechat_config('wechat_keyword_replies', array());
        if (is_array($rules) && ! empty($rules)) {
            foreach ($rules as $rule) {
                $keywords = isset($rule['keyword']) ? explode('|', $rule['keyword']) : array();
                foreach ($keywords as $kw) {
                    $kw = trim($kw);
                    if (! empty($kw) && mb_stripos($content, $kw) !== false) {
                        $reply_type = isset($rule['reply_type']) ? $rule['reply_type'] : 'text';
                        if ('news' === $reply_type) {
                            onedown_wechat_send_news($from_user, $to_user, $rule);
                        } else {
                            onedown_wechat_send_text($from_user, $to_user, isset($rule['reply_content']) ? $rule['reply_content'] : '');
                        }
                        return;
                    }
                }
            }
        }
    }

    onedown_wechat_send_text($from_user, $to_user, onedown_wechat_config('wechat_default_reply', '您好，请输入有效关键词。'));
}

// ════════════════════════════════════════════
// 消息发送
// ════════════════════════════════════════════

function onedown_wechat_send_text($to_user, $from_user, $content)
{
    if (empty($content)) {
        return;
    }

    $time = time();
    $xml  = "<xml>
<ToUserName><![CDATA[{$to_user}]]></ToUserName>
<FromUserName><![CDATA[{$from_user}]]></FromUserName>
<CreateTime>{$time}</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[{$content}]]></Content>
</xml>";

    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: text/xml; charset=utf-8');
    echo $xml;
}

function onedown_wechat_send_news($to_user, $from_user, $rule)
{
    $title = isset($rule['reply_news_title']) ? $rule['reply_news_title'] : '';
    $desc  = isset($rule['reply_news_desc']) ? $rule['reply_news_desc'] : '';
    $url   = isset($rule['reply_news_url']) ? $rule['reply_news_url'] : '';
    $pic   = isset($rule['reply_news_pic']) ? $rule['reply_news_pic'] : '';

    if (empty($title) && empty($desc)) {
        onedown_wechat_send_text($to_user, $from_user, $desc);
        return;
    }

    $time = time();
    $xml  = "<xml>
<ToUserName><![CDATA[{$to_user}]]></ToUserName>
<FromUserName><![CDATA[{$from_user}]]></FromUserName>
<CreateTime>{$time}</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>1</ArticleCount>
<Articles>
<item>
<Title><![CDATA[{$title}]]></Title>
<Description><![CDATA[{$desc}]]></Description>
<PicUrl><![CDATA[{$pic}]]></PicUrl>
<Url><![CDATA[{$url}]]></Url>
</item>
</Articles>
</xml>";

    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: text/xml; charset=utf-8');
    echo $xml;
}

// ════════════════════════════════════════════
// 微信登录
// ════════════════════════════════════════════

/**
 * 生成登录场景值并获取二维码 ticket
 *
 * @param int $user_id
 * @return array{success: bool, qrcode_url?: string, scene_id?: int, msg?: string}
 */
function onedown_wechat_generate_login_qrcode($user_id = 0)
{
    if (! $user_id) {
        $user_id = get_current_user_id();
    }
    if (! $user_id) {
        return array('success' => false, 'msg' => '用户未登录');
    }

    $scene_id = intval($user_id % 100000);
    if ($scene_id <= 0) {
        $scene_id = abs(crc32('wx_login_' . $user_id)) % 100000 + 1;
    }

    // 存储场景值 → 用户ID 映射
    set_transient('onedown_wechat_scan_' . $scene_id, $user_id, 300);

    $access_token = onedown_wechat_get_access_token();
    if (empty($access_token)) {
        return array('success' => false, 'msg' => '公众号配置错误，无法获取 Access Token');
    }

    $url  = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token;
    $data = array(
        'expire_seconds' => 300,
        'action_name'    => 'QR_SCENE',
        'action_info'    => array(
            'scene' => array('scene_id' => $scene_id),
        ),
    );

    $response = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => json_encode($data, JSON_UNESCAPED_UNICODE),
        'timeout' => 15,
    ));

    if (is_wp_error($response)) {
        return array('success' => false, 'msg' => '网络请求失败');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['ticket'])) {
        $errmsg = isset($body['errmsg']) ? $body['errmsg'] : '未知错误';
        return array('success' => false, 'msg' => '生成二维码失败：' . $errmsg);
    }

    $qrcode_url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($body['ticket']);

    return array(
        'success'  => true,
        'qrcode_url' => $qrcode_url,
        'scene_id' => $scene_id,
        'expire_seconds' => 300,
    );
}

/**
 * 处理微信登录确认回调
 */
add_action('init', 'onedown_wechat_login_callback');
function onedown_wechat_login_callback()
{
    // 支持短链接模式 /?wxlogin=CODE
    $code = '';
    if (! empty($_GET['wxlogin'])) {
        $code = sanitize_text_field($_GET['wxlogin']);
        $login_data = get_transient('onedown_wechat_login_' . $code);
        if (empty($login_data)) {
            return;
        }
        // 自动确认登录
        $openid  = $login_data['openid'];
        $user_id = $login_data['user_id'];
        delete_transient('onedown_wechat_login_' . $code);

        onedown_wechat_do_login($openid, $user_id);
        return;
    }

    // 兼容旧模式
    if (empty($_GET['wechat_login']) || empty($_GET['code']) || empty($_GET['openid'])) {
        return;
    }

    $code   = sanitize_text_field($_GET['code']);
    $openid = sanitize_text_field($_GET['openid']);

    $login_data = get_transient('onedown_wechat_login_' . $code);
    if (empty($login_data) || $login_data['openid'] !== $openid) {
        wp_die('登录凭证无效或已过期，请重新扫码。');
    }

    delete_transient('onedown_wechat_login_' . $code);
    onedown_wechat_do_login($openid, isset($login_data['user_id']) ? $login_data['user_id'] : 0);
}

/**
 * 执行微信登录
 */
function onedown_wechat_do_login($openid, $user_id = 0)
{
    $bound_user_id = onedown_wechat_get_user_by_openid($openid);

    if (! empty($bound_user_id)) {
        $user_id = $bound_user_id;
    } elseif (! empty($user_id) && is_numeric($user_id)) {
        update_user_meta($user_id, 'wechat_openid', $openid);
    } else {
        $auto_bind = onedown_wechat_config('wechat_bind_after_login', true);
        if ($auto_bind) {
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                update_user_meta($user_id, 'wechat_openid', $openid);
            } else {
                $user_id = onedown_wechat_create_user($openid);
            }
        } else {
            $redirect = wp_login_url(home_url('/'));
            wp_safe_redirect($redirect);
            exit;
        }
    }

    if ($user_id && ! is_user_logged_in()) {
        wp_set_auth_cookie($user_id, true);
        do_action('wp_login', get_userdata($user_id)->user_login, get_userdata($user_id));
    }

    $redirect_url = onedown_wechat_config('wechat_login_redirect', '');
    if (empty($redirect_url)) {
        $redirect_url = home_url('/');
    }
    wp_safe_redirect($redirect_url);
    exit;
}

// ════════════════════════════════════════════
// 用户绑定管理
// ════════════════════════════════════════════

function onedown_wechat_get_user_by_openid($openid)
{
    $users = get_users(array(
        'meta_key'   => 'wechat_openid',
        'meta_value' => $openid,
        'number'     => 1,
        'fields'     => 'ID',
    ));
    return ! empty($users) ? intval($users[0]) : false;
}

function onedown_wechat_create_user($openid)
{
    $username = 'wx_' . substr($openid, 0, 20);
    $password = wp_generate_password();

    $user_id = wp_create_user($username, $password, $username . '@wechat.anon');
    if (is_wp_error($user_id)) {
        $username = 'wx_' . substr($openid, 0, 10) . '_' . wp_rand(1000, 9999);
        $user_id  = wp_create_user($username, $password, $username . '@wechat.anon');
    }

    if (is_wp_error($user_id)) {
        return false;
    }

    update_user_meta($user_id, 'wechat_openid', $openid);
    wp_update_user(array(
        'ID'           => $user_id,
        'display_name' => '微信用户_' . substr($openid, -6),
    ));

    return $user_id;
}

/**
 * 获取当前用户绑定的 OpenID
 */
function onedown_wechat_get_bound_openid($user_id = 0)
{
    if (! $user_id) {
        $user_id = get_current_user_id();
    }
    if (! $user_id) {
        return '';
    }
    return get_user_meta($user_id, 'wechat_openid', true);
}

/**
 * 检查用户是否已绑定微信
 */
function onedown_wechat_is_user_bound($user_id = 0)
{
    $openid = onedown_wechat_get_bound_openid($user_id);
    return ! empty($openid);
}

/**
 * 解除用户微信绑定
 */
function onedown_wechat_unbind_user($user_id = 0)
{
    if (! $user_id) {
        $user_id = get_current_user_id();
    }
    if (! $user_id) {
        return false;
    }
    return delete_user_meta($user_id, 'wechat_openid');
}

// ════════════════════════════════════════════
// AJAX：生成登录二维码
// ════════════════════════════════════════════

add_action('wp_ajax_onedown_wechat_qrcode', 'onedown_wechat_ajax_qrcode');
add_action('wp_ajax_nopriv_onedown_wechat_qrcode', 'onedown_wechat_ajax_qrcode');
function onedown_wechat_ajax_qrcode()
{
    $login_enabled = onedown_wechat_config('wechat_login_enabled', false);
    if (! $login_enabled) {
        wp_send_json_error(array('msg' => '微信登录未启用'));
    }

    $user_id = get_current_user_id();
    if (! $user_id) {
        // 未登录用户：用 session_id 或 IP 做临时标识
        $user_id = -1 * abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'])) % 100000;
    }

    $result = onedown_wechat_generate_login_qrcode($user_id);
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

// ════════════════════════════════════════════
// AJAX：微信绑定管理
// ════════════════════════════════════════════

add_action('wp_ajax_onedown_wechat_bind_status', 'onedown_wechat_ajax_bind_status');
function onedown_wechat_ajax_bind_status()
{
    $user_id = get_current_user_id();
    if (! $user_id) {
        wp_send_json_error(array('msg' => '未登录'));
    }

    $openid = onedown_wechat_get_bound_openid($user_id);
    wp_send_json_success(array(
        'bound'  => ! empty($openid),
        'openid' => $openid ? substr($openid, 0, 6) . '****' . substr($openid, -4) : '',
    ));
}

add_action('wp_ajax_onedown_wechat_unbind', 'onedown_wechat_ajax_unbind');
function onedown_wechat_ajax_unbind()
{
    if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'onedown_wechat_unbind')) {
        wp_send_json_error(array('msg' => '安全验证失败'));
    }

    $user_id = get_current_user_id();
    if (! $user_id) {
        wp_send_json_error(array('msg' => '未登录'));
    }

    if (onedown_wechat_unbind_user($user_id)) {
        wp_send_json_success(array('msg' => '已解除绑定'));
    } else {
        wp_send_json_error(array('msg' => '解绑失败'));
    }
}

// ════════════════════════════════════════════
// 自定义菜单管理
// ════════════════════════════════════════════

/**
 * 发布菜单到微信
 */
function onedown_wechat_menu_publish($menu_items = null)
{
    $access_token = onedown_wechat_get_access_token();
    if (empty($access_token)) {
        return array('success' => false, 'msg' => '无法获取 Access Token');
    }

    if (null === $menu_items) {
        $menu_items = onedown_wechat_config('wechat_menu', array());
    }

    if (empty($menu_items) || ! is_array($menu_items)) {
        return array('success' => false, 'msg' => '菜单配置为空');
    }

    // 最多3个一级菜单
    $menu_items = array_slice($menu_items, 0, 3);

    $wechat_menu = array('button' => array());

    foreach ($menu_items as $item) {
        // 有子菜单
        if (! empty($item['sub_button']) && is_array($item['sub_button'])) {
            $sub_buttons = array();
            // 最多5个子菜单
            $subs = array_slice($item['sub_button'], 0, 5);
            foreach ($subs as $sub) {
                $sub_btn = onedown_wechat_build_menu_button($sub);
                if (! empty($sub_btn['name'])) {
                    $sub_buttons[] = $sub_btn;
                }
            }
            if (! empty($sub_buttons)) {
                $wechat_menu['button'][] = array(
                    'name'       => isset($item['name']) ? $item['name'] : '',
                    'sub_button' => $sub_buttons,
                );
            }
        } else {
            $btn = onedown_wechat_build_menu_button($item);
            if (! empty($btn['name'])) {
                $wechat_menu['button'][] = $btn;
            }
        }
    }

    $url      = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $access_token;
    $response = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => json_encode($wechat_menu, JSON_UNESCAPED_UNICODE),
        'timeout' => 15,
    ));

    if (is_wp_error($response)) {
        return array('success' => false, 'msg' => '网络请求失败：' . $response->get_error_message());
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (! empty($body['errcode']) && 0 !== intval($body['errcode'])) {
        return array(
            'success' => false,
            'msg'     => '发布失败：' . (isset($body['errmsg']) ? $body['errmsg'] : '未知错误') . ' (code: ' . $body['errcode'] . ')',
        );
    }

    return array('success' => true, 'msg' => '菜单发布成功！');
}

/**
 * 查询当前菜单
 */
function onedown_wechat_menu_get_current()
{
    $access_token = onedown_wechat_get_access_token();
    if (empty($access_token)) {
        return array('success' => false, 'msg' => '无法获取 Access Token');
    }

    $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=' . $access_token;
    $response = wp_remote_get($url, array('timeout' => 15));

    if (is_wp_error($response)) {
        return array('success' => false, 'msg' => '网络请求失败');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (! empty($body['errcode']) && 0 !== intval($body['errcode'])) {
        return array('success' => false, 'msg' => '查询失败：' . (isset($body['errmsg']) ? $body['errmsg'] : '未知错误'));
    }

    return array('success' => true, 'menu' => $body);
}

/**
 * 删除当前菜单
 */
function onedown_wechat_menu_delete()
{
    $access_token = onedown_wechat_get_access_token();
    if (empty($access_token)) {
        return array('success' => false, 'msg' => '无法获取 Access Token');
    }

    $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' . $access_token;
    $response = wp_remote_get($url, array('timeout' => 15));

    if (is_wp_error($response)) {
        return array('success' => false, 'msg' => '网络请求失败');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (! empty($body['errcode']) && 0 !== intval($body['errcode'])) {
        return array('success' => false, 'msg' => '删除失败：' . (isset($body['errmsg']) ? $body['errmsg'] : '未知错误'));
    }

    return array('success' => true, 'msg' => '菜单已删除');
}

function onedown_wechat_build_menu_button($item)
{
    $type = isset($item['type']) ? $item['type'] : 'view';
    $btn  = array('name' => isset($item['name']) ? $item['name'] : '');

    if ('click' === $type) {
        $btn['type'] = 'click';
        $btn['key']  = isset($item['key']) ? $item['key'] : '';
    } elseif ('miniprogram' === $type) {
        $btn['type']     = 'miniprogram';
        $btn['url']      = isset($item['url']) ? $item['url'] : '';
        $btn['appid']    = isset($item['appid']) ? $item['appid'] : '';
        $btn['pagepath'] = isset($item['pagepath']) ? $item['pagepath'] : '';
    } else {
        $btn['type'] = 'view';
        $btn['url']  = isset($item['url']) ? $item['url'] : '';
    }

    return $btn;
}

/**
 * 菜单同步按钮回调
 */
if (! function_exists('onedown_wechat_menu_sync_button')) :
    function onedown_wechat_menu_sync_button()
    {
        $publish_url = admin_url('admin-ajax.php?action=onedown_wechat_menu_sync&_wpnonce=' . wp_create_nonce('onedown_wechat_sync'));
        $delete_url  = admin_url('admin-ajax.php?action=onedown_wechat_menu_delete&_wpnonce=' . wp_create_nonce('onedown_wechat_sync'));
        ?>
        <div style="padding-top:10px;">
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="<?php echo esc_url($publish_url); ?>" class="button button-primary" style="background:#07c160;border-color:#07c160;display:inline-flex;align-items:center;gap:6px;">
                    &#x1F4E2; 同步菜单到公众号
                </a>
                <a href="<?php echo esc_url($delete_url); ?>" class="button" style="color:#e74c3c;border-color:#e74c3c;display:inline-flex;align-items:center;gap:6px;" onclick="return confirm('确定要删除公众号菜单吗？');">
                    &#x1F5D1; 删除公众号菜单
                </a>
            </div>
            <p style="color:#666;margin-top:8px;font-size:12px;">
                点击「同步菜单」将当前配置发布到微信公众号；点击「删除菜单」清空公众号菜单。
            </p>
        </div>
        <?php

        // 显示操作结果
        if (! empty($_GET['wechat_sync_result'])) {
            $result_type = sanitize_text_field($_GET['wechat_sync_result']);
            $result_msg  = isset($_GET['wechat_sync_msg']) ? sanitize_text_field($_GET['wechat_sync_msg']) : '';
            if ($result_type === 'success') {
                echo '<div style="margin-top:10px;padding:8px 12px;background:#e8f5e9;border-left:3px solid #07c160;border-radius:3px;color:#2e7d32;">' . esc_html($result_msg) . '</div>';
            } else {
                echo '<div style="margin-top:10px;padding:8px 12px;background:#fbe9e7;border-left:3px solid #e74c3c;border-radius:3px;color:#c62828;">' . esc_html($result_msg) . '</div>';
            }
        }
    }
endif;

/**
 * AJAX 菜单同步
 */
add_action('wp_ajax_onedown_wechat_menu_sync', 'onedown_wechat_ajax_menu_sync');
function onedown_wechat_ajax_menu_sync()
{
    if (! current_user_can('manage_options')) {
        wp_die('权限不足');
    }
    check_admin_referer('onedown_wechat_sync');

    $result = onedown_wechat_menu_publish();

    $redirect = add_query_arg(array(
        'page'              => 'onedown-options',
        'wechat_sync_result' => $result['success'] ? 'success' : 'error',
        'wechat_sync_msg'   => urlencode($result['msg']),
    ), admin_url('admin.php'));

    wp_safe_redirect($redirect);
    exit;
}

/**
 * AJAX 菜单删除
 */
add_action('wp_ajax_onedown_wechat_menu_delete', 'onedown_wechat_ajax_menu_delete');
function onedown_wechat_ajax_menu_delete()
{
    if (! current_user_can('manage_options')) {
        wp_die('权限不足');
    }
    check_admin_referer('onedown_wechat_sync');

    $result = onedown_wechat_menu_delete();

    $redirect = add_query_arg(array(
        'page'              => 'onedown-options',
        'wechat_sync_result' => $result['success'] ? 'success' : 'error',
        'wechat_sync_msg'   => urlencode($result['msg']),
    ), admin_url('admin.php'));

    wp_safe_redirect($redirect);
    exit;
}

// ════════════════════════════════════════════
// OAuth 网页授权
// ════════════════════════════════════════════

function onedown_wechat_get_oauth_url($redirect_uri, $scope = 'snsapi_base')
{
    $app_id = onedown_wechat_config('wechat_app_id');
    if (empty($app_id)) {
        return '';
    }

    return 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . urlencode($app_id)
        . '&redirect_uri=' . urlencode($redirect_uri)
        . '&response_type=code&scope=' . $scope
        . '&state=onedown#wechat_redirect';
}

function onedown_wechat_oauth_get_openid($code)
{
    $app_id     = onedown_wechat_config('wechat_app_id');
    $app_secret = onedown_wechat_config('wechat_app_secret');

    if (empty($app_id) || empty($app_secret) || empty($code)) {
        return false;
    }

    $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . urlencode($app_id)
        . '&secret=' . urlencode($app_secret)
        . '&code=' . urlencode($code)
        . '&grant_type=authorization_code';

    $response = wp_remote_get($url, array('timeout' => 15));
    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return isset($body['openid']) ? $body['openid'] : false;
}

function onedown_wechat_oauth_get_userinfo($code)
{
    $app_id     = onedown_wechat_config('wechat_app_id');
    $app_secret = onedown_wechat_config('wechat_app_secret');

    if (empty($app_id) || empty($app_secret) || empty($code)) {
        return false;
    }

    $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . urlencode($app_id)
        . '&secret=' . urlencode($app_secret)
        . '&code=' . urlencode($code)
        . '&grant_type=authorization_code';

    $response = wp_remote_get($url, array('timeout' => 15));
    if (is_wp_error($response)) {
        return false;
    }

    $token_body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($token_body['access_token']) || empty($token_body['openid'])) {
        return false;
    }

    $info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . urlencode($token_body['access_token'])
        . '&openid=' . urlencode($token_body['openid'])
        . '&lang=zh_CN';

    $info_response = wp_remote_get($info_url, array('timeout' => 15));
    if (is_wp_error($info_response)) {
        return false;
    }

    return json_decode(wp_remote_retrieve_body($info_response), true);
}

// ════════════════════════════════════════════
// 获取关注用户信息（通过 Access Token）
// ════════════════════════════════════════════

/**
 * 通过 OpenID 获取用户基本信息（已关注用户）
 */
function onedown_wechat_get_user_info($openid)
{
    $access_token = onedown_wechat_get_access_token();
    if (empty($access_token) || empty($openid)) {
        return false;
    }

    $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . urlencode($access_token)
        . '&openid=' . urlencode($openid)
        . '&lang=zh_CN';

    $response = wp_remote_get($url, array('timeout' => 15));
    if (is_wp_error($response)) {
        return false;
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

/**
 * 获取公众号二维码 URL
 *
 * @param bool $force_ticket 是否强制生成临时 ticket（false 则优先使用配置的静态图片）
 * @return string
 */
function onedown_wechat_get_qrcode_url($force_ticket = false)
{
    if (! $force_ticket) {
        $qrcode = onedown_wechat_config('wechat_qrcode', '');
        if (is_array($qrcode) && ! empty($qrcode['url'])) {
            return $qrcode['url'];
        }
        if (is_string($qrcode) && ! empty($qrcode)) {
            return $qrcode;
        }
    }

    // 尝试生成临时二维码（需要已配置 AppID/Secret）
    $access_token = onedown_wechat_get_access_token();
    if (empty($access_token)) {
        return '';
    }

    $url  = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $access_token;
    $data = array(
        'expire_seconds' => 2592000,
        'action_name'    => 'QR_STR_SCENE',
        'action_info'    => array(
            'scene' => array('scene_str' => 'subscribe'),
        ),
    );

    $response = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => json_encode($data, JSON_UNESCAPED_UNICODE),
        'timeout' => 15,
    ));

    if (is_wp_error($response)) {
        return '';
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['ticket'])) {
        return '';
    }

    return 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($body['ticket']);
}

// ════════════════════════════════════════════
// 短代码
// ════════════════════════════════════════════

/**
 * [wechat_qrcode] — 显示公众号二维码
 * 用法：[wechat_qrcode size="200" title="扫码关注"]
 */
add_shortcode('wechat_qrcode', 'onedown_wechat_shortcode_qrcode');
function onedown_wechat_shortcode_qrcode($atts)
{
    $atts = shortcode_atts(array(
        'size'  => 200,
        'title' => '扫码关注公众号',
    ), $atts);

    $qrcode_url = onedown_wechat_get_qrcode_url(false);
    if (empty($qrcode_url)) {
        return '<p style="color:#999;">暂未设置公众号二维码</p>';
    }

    $size = intval($atts['size']);
    $title = esc_html($atts['title']);

    return '<div class="onedown-wechat-qrcode-shortcode" style="text-align:center;padding:20px;">'
        . '<div style="font-size:14px;font-weight:600;margin-bottom:12px;color:#333;">'
        . '<i class="fa fa-wechat" style="color:#07c160;"></i> ' . $title
        . '</div>'
        . '<img src="' . esc_url($qrcode_url) . '" alt="公众号二维码" style="width:' . $size . 'px;height:' . $size . 'px;max-width:100%;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.1);">'
        . '</div>';
}

/**
 * [wechat_login] — 微信登录按钮
 * 用法：[wechat_login text="微信扫码登录" btn_text="微信登录"]
 */
add_shortcode('wechat_login', 'onedown_wechat_shortcode_login');
function onedown_wechat_shortcode_login($atts)
{
    $login_enabled = onedown_wechat_config('wechat_login_enabled', false);
    if (! $login_enabled) {
        return '';
    }

    $atts = shortcode_atts(array(
        'text'     => '微信扫码登录',
        'btn_text' => '微信登录',
    ), $atts);

    $text     = esc_html($atts['text']);
    $btn_text = esc_html($atts['btn_text']);

    if (is_user_logged_in()) {
        $bound = onedown_wechat_is_user_bound();
        if ($bound) {
            return '';
        }
        // 已登录但未绑定微信
    }

    $ajax_url = admin_url('admin-ajax.php');

    return '<div class="onedown-wechat-login-shortcode" style="text-align:center;padding:15px;">'
        . '<p style="margin:0 0 10px;color:#666;font-size:13px;">' . $text . '</p>'
        . '<button type="button" class="onedown-wechat-login-btn" data-wechat-login style="'
        . 'background:#07c160;color:#fff;border:none;padding:8px 24px;border-radius:4px;cursor:pointer;font-size:14px;display:inline-flex;align-items:center;gap:6px;">'
        . '<i class="fa fa-wechat"></i> ' . $btn_text
        . '</button>'
        . '<div class="onedown-wechat-login-qr" style="display:none;margin-top:15px;text-align:center;">'
        . '<div style="width:200px;height:200px;margin:0 auto;display:flex;align-items:center;justify-content:center;background:#f5f5f5;border-radius:8px;">'
        . '<i class="fa fa-spinner fa-spin" style="font-size:24px;color:#999;"></i>'
        . '</div>'
        . '<p style="margin:8px 0 0;font-size:12px;color:#999;">请使用微信扫描二维码登录</p>'
        . '</div>'
        . '<script>'
        . '(function(){'
        . 'var btn=document.querySelector("[data-wechat-login]");'
        . 'if(!btn)return;'
        . 'btn.addEventListener("click",function(){'
        . 'var qrBox=this.nextElementSibling;'
        . 'if(qrBox.style.display==="block")return;'
        . 'qrBox.style.display="block";'
        . 'var xhr=new XMLHttpRequest();'
        . 'xhr.open("POST","' . esc_js($ajax_url) . '");'
        . 'xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");'
        . 'xhr.onload=function(){'
        . 'try{var d=JSON.parse(xhr.responseText);'
        . 'if(d.success){'
        . 'qrBox.innerHTML="<img src=\\\""+d.data.qrcode_url+"\\\" style=\\\"width:200px;height:200px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.1);\\\"><p style=\\\"margin:8px 0 0;font-size:12px;color:#999;\\\">请使用微信扫描二维码登录</p>";'
        . '}else{qrBox.innerHTML="<p style=\\\"color:#e74c3c;font-size:13px;\\\">"+d.data.msg+"</p>";}'
        . '}catch(e){qrBox.innerHTML="<p style=\\\"color:#e74c3c;font-size:13px;\\\">请求失败</p>";}'
        . '};'
        . 'xhr.send("action=onedown_wechat_qrcode");'
        . '});'
        . '})();'
        . '</script>'
        . '</div>';
}

// ════════════════════════════════════════════
// 用户中心：账号设置中显示绑定状态
// ════════════════════════════════════════════

/**
 * 在用户中心账号设置中添加微信绑定区块
 * 挂载到 onedown_render_tab_content 中的 profile 区块尾部
 */
add_action('onedown_profile_after_form', 'onedown_wechat_profile_section');
function onedown_wechat_profile_section()
{
    $login_enabled = onedown_wechat_config('wechat_login_enabled', false);
    $user_id       = get_current_user_id();

    if (! $login_enabled || ! $user_id) {
        return;
    }

    $bound    = onedown_wechat_is_user_bound($user_id);
    $openid   = onedown_wechat_get_bound_openid($user_id);
    $unbind_nonce = wp_create_nonce('onedown_wechat_unbind');
    ?>
    <div class="wechat-bind-section" style="border-top:1px solid var(--od-line);padding-top:16px;margin-top:16px;">
        <h4 style="margin:0 0 14px;font-weight:800;color:#252c3a;">
            <i class="fa fa-wechat" style="color:#07c160;"></i> 微信绑定
        </h4>
        <?php if ($bound) : ?>
            <p style="margin:0 0 12px;color:#666;">
                已绑定微信：
                <strong style="color:#333;"><?php echo esc_html(substr($openid, 0, 6) . '****' . substr($openid, -4)); ?></strong>
            </p>
            <button type="button" class="button" data-wechat-unbind style="color:#e74c3c;border-color:#e74c3c;" onclick="if(!confirm('确定解除微信绑定吗？'))return;var xhr=new XMLHttpRequest();xhr.open('POST','<?php echo esc_js(admin_url('admin-ajax.php')); ?>');xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');xhr.onload=function(){try{var d=JSON.parse(xhr.responseText);if(d.success){alert(d.data.msg);location.reload();}else{alert(d.data.msg);}}catch(e){alert('请求失败');}};xhr.send('action=onedown_wechat_unbind&_wpnonce=<?php echo esc_js($unbind_nonce); ?>');">
                <i class="fa fa-unlink"></i> 解除绑定
            </button>
        <?php else : ?>
            <p style="margin:0 0 12px;color:#666;">尚未绑定微信，绑定后可使用微信扫码登录。</p>
            <button type="button" class="button" data-wechat-bind style="background:#07c160;color:#fff;border-color:#07c160;" onclick="var qrDiv=this.nextElementSibling;qrDiv.style.display='block';var xhr=new XMLHttpRequest();xhr.open('POST','<?php echo esc_js(admin_url('admin-ajax.php')); ?>');xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');xhr.onload=function(){try{var d=JSON.parse(xhr.responseText);if(d.success){qrDiv.innerHTML='<img src=\\\"'+d.data.qrcode_url+'\\\" style=\\\"width:200px;height:200px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.1);\\\"><p style=\\\"margin:8px 0 0;font-size:12px;color:#999;\\\">请使用微信扫描二维码绑定</p>';}else{qrDiv.innerHTML='<p style=\\\"color:#e74c3c;font-size:13px;\\\">'+d.data.msg+'</p>';}}catch(e){qrDiv.innerHTML='<p style=\\\"color:#e74c3c;font-size:13px;\\\">请求失败</p>';}};xhr.send('action=onedown_wechat_qrcode');">
                <i class="fa fa-wechat"></i> 绑定微信
            </button>
            <div style="display:none;margin-top:15px;text-align:center;">
                <div style="width:200px;height:200px;margin:0 auto;display:flex;align-items:center;justify-content:center;background:#f5f5f5;border-radius:8px;">
                    <i class="fa fa-spinner fa-spin" style="font-size:24px;color:#999;"></i>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * 将微信绑定区块注入到 profile 标签页表单之后
 */
add_filter('onedown_profile_tab_content', 'onedown_wechat_inject_profile_bind');
function onedown_wechat_inject_profile_bind($content)
{
    ob_start();
    do_action('onedown_profile_after_form');
    $extra = ob_get_clean();
    return $content . $extra;
}

// ════════════════════════════════════════════
// rewrite rules 刷新
// ════════════════════════════════════════════

add_action('after_switch_theme', 'onedown_wechat_flush_rewrite');
function onedown_wechat_flush_rewrite()
{
    onedown_wechat_route_init();
    flush_rewrite_rules();
}
