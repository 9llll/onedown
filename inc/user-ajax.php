<?php

/**
 * Onedown 用户 AJAX 处理
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 速率限制 - 基于 IP 的失败尝试计数
 *
 * @param string $action  操作标识（signin/signup/resetpwd）
 * @param int    $max     最大允许尝试次数
 * @param int    $minutes 封禁时间（分钟）
 * @return bool|string 允许返回 true，拒绝返回错误消息
 */
function onedown_check_rate_limit($action, $max = 5, $minutes = 15)
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $key = 'onedown_rate_' . $action . '_' . md5($ip);
    $attempts = intval(get_transient($key));

    if ($attempts >= $max) {
        return sprintf('操作过于频繁，请 %d 分钟后再试', $minutes);
    }

    // 递增计数，保留现有过期时间
    set_transient($key, $attempts + 1, $minutes * 60);
    return true;
}

/**
 * 清除速率限制
 */
function onedown_clear_rate_limit($action)
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    $key = 'onedown_rate_' . $action . '_' . md5($ip);
    delete_transient($key);
}

/**
 * 检查密码强度
 *
 * @param string $password
 * @return bool|string 通过返回 true，否则返回错误消息
 */
function onedown_check_password_strength($password)
{
    if (strlen($password) < 8) {
        return '密码至少需要 8 位字符';
    }
    if (!preg_match('/[A-Za-z]/', $password)) {
        return '密码必须包含至少一个字母';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return '密码必须包含至少一个数字';
    }
    return true;
}

/**
 * AJAX 登录
 */
add_action('wp_ajax_onedown_signin', 'onedown_ajax_signin');
add_action('wp_ajax_nopriv_onedown_signin', 'onedown_ajax_signin');
if (!function_exists('onedown_ajax_signin')):
    function onedown_ajax_signin()
    {
        if (is_user_logged_in()) {
            wp_send_json_error(array('msg' => '您已登录，请刷新页面'));
        }

        // 速率限制
        $rate_check = onedown_check_rate_limit('signin', 5, 15);
        if ($rate_check !== true) {
            wp_send_json_error(array('msg' => $rate_check));
        }

        // 验证 Nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_signin_action')) {
            wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
        }

        // 验证码检查
        if (onedown_captcha_enabled('login')) {
            $captcha_code = isset($_POST['captcha_code']) ? trim($_POST['captcha_code']) : '';
            if (!onedown_captcha_verify('signin', $captcha_code)) {
                wp_send_json_error(array('msg' => '验证码错误或已过期，请重新输入'));
            }
        }

        $username = sanitize_user(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            wp_send_json_error(array('msg' => '请输入用户名和密码'));
        }

        // 支持邮箱、用户名登录
        $user_data = null;
        if (is_email($username)) {
            $user_data = get_user_by('email', $username);
        } else {
            $user_data = get_user_by('login', $username);
        }

        if (!$user_data) {
            wp_send_json_error(array('msg' => '账号不存在'));
        }

        $credentials = array(
            'user_login'    => $user_data->user_login,
            'user_password' => $password,
            'remember'      => !empty($_POST['remember']),
        );

        $user = wp_signon($credentials);

        if (is_wp_error($user)) {
            wp_send_json_error(array('msg' => '用户名或密码错误'));
        }

        // 登录成功，清除速率限制
        onedown_clear_rate_limit('signin');

        $redirect_to = !empty($_POST['redirect_to']) ? esc_url_raw($_POST['redirect_to']) : '';
        wp_send_json_success(array(
            'msg'         => '登录成功，正在跳转...',
            'redirect_to' => $redirect_to ?: onedown_user_center_url(),
        ));
    }
endif;

/**
 * AJAX 注册
 */
add_action('wp_ajax_onedown_signup', 'onedown_ajax_signup');
add_action('wp_ajax_nopriv_onedown_signup', 'onedown_ajax_signup');
if (!function_exists('onedown_ajax_signup')):
    function onedown_ajax_signup()
    {
        if (is_user_logged_in()) {
            wp_send_json_error(array('msg' => '您已登录'));
        }

        // 速率限制
        $rate_check = onedown_check_rate_limit('signup', 3, 30);
        if ($rate_check !== true) {
            wp_send_json_error(array('msg' => $rate_check));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_signup_action')) {
            wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
        }

        // 验证码检查
        if (onedown_captcha_enabled('register')) {
            $captcha_code = isset($_POST['captcha_code']) ? trim($_POST['captcha_code']) : '';
            if (!onedown_captcha_verify('signup', $captcha_code)) {
                wp_send_json_error(array('msg' => '验证码错误或已过期，请重新输入'));
            }
        }

        $name       = sanitize_user(trim($_POST['name'] ?? ''));
        $email      = sanitize_email(trim($_POST['email'] ?? ''));
        $password   = $_POST['password'] ?? '';

        // 验证
        if (empty($name)) {
            wp_send_json_error(array('msg' => '请输入用户名'));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('msg' => '请输入有效的邮箱地址'));
        }

        // 密码强度检查
        $pwd_strength = onedown_check_password_strength($password);
        if ($pwd_strength !== true) {
            wp_send_json_error(array('msg' => $pwd_strength));
        }

        if (username_exists($name)) {
            wp_send_json_error(array('msg' => '该用户名已被注册'));
        }

        if (email_exists($email)) {
            wp_send_json_error(array('msg' => '该邮箱已被注册'));
        }

        // 创建用户
        $user_id = wp_create_user($name, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('msg' => $user_id->get_error_message()));
        }

        // 注册成功，清除速率限制
        onedown_clear_rate_limit('signup');

        // 自动登录
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        do_action('wp_login', get_user_by('id', $user_id)->user_login, get_user_by('id', $user_id));

        wp_send_json_success(array(
            'msg'         => '注册成功，欢迎您：' . $name,
            'redirect_to' => onedown_user_center_url(),
        ));
    }
endif;

/**
 * AJAX 找回密码
 */
add_action('wp_ajax_onedown_resetpassword', 'onedown_ajax_resetpassword');
add_action('wp_ajax_nopriv_onedown_resetpassword', 'onedown_ajax_resetpassword');
if (!function_exists('onedown_ajax_resetpassword')):
    function onedown_ajax_resetpassword()
    {
        // 速率限制
        $rate_check = onedown_check_rate_limit('resetpwd', 3, 30);
        if ($rate_check !== true) {
            wp_send_json_error(array('msg' => $rate_check));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_resetpassword_action')) {
            wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
        }

        // 验证码检查
        if (onedown_captcha_enabled('resetpwd')) {
            $captcha_code = isset($_POST['captcha_code']) ? trim($_POST['captcha_code']) : '';
            if (!onedown_captcha_verify('resetpwd', $captcha_code)) {
                wp_send_json_error(array('msg' => '验证码错误或已过期，请重新输入'));
            }
        }

        $email      = sanitize_email(trim($_POST['email'] ?? ''));
        $password   = $_POST['password'] ?? '';
        $repassword = $_POST['repassword'] ?? '';

        if (!is_email($email)) {
            wp_send_json_error(array('msg' => '请输入有效的邮箱地址'));
        }

        // 密码强度检查
        $pwd_strength = onedown_check_password_strength($password);
        if ($pwd_strength !== true) {
            wp_send_json_error(array('msg' => $pwd_strength));
        }

        if ($password !== $repassword) {
            wp_send_json_error(array('msg' => '两次密码输入不一致'));
        }

        $user = get_user_by('email', $email);
        if (!$user) {
            wp_send_json_error(array('msg' => '该邮箱未注册'));
        }

        // 允许管理员通过 filter 控制密码重置
        $allow = apply_filters('allow_password_reset', true, $user->ID);
        if (!$allow) {
            wp_send_json_error(array('msg' => '该账号不允许重置密码'));
        }

        $result = wp_update_user(array(
            'ID'        => $user->ID,
            'user_pass' => $password,
        ));

        if (is_wp_error($result)) {
            wp_send_json_error(array('msg' => $result->get_error_message()));
        }

        // 发送密码更改通知邮件
        $site_name = get_bloginfo('name');
        $subject = sprintf('【%s】您的密码已被修改', $site_name);
        $message = sprintf(
            '<p>您好，%s：</p>
            <p>您的 %s 账号密码已被成功修改。</p>
            <p>如果这不是您本人操作，请立即联系管理员。</p>',
            esc_html($user->display_name),
            esc_html($site_name)
        );
        onedown_send_mail($email, $subject, $message);

        // 清除速率限制
        onedown_clear_rate_limit('resetpwd');

        // 自动登录
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);

        wp_send_json_success(array(
            'msg'         => '密码重置成功！请牢记新密码',
            'redirect_to' => onedown_user_center_url(),
        ));
    }
endif;

/**
 * VIP 订单 AJAX 处理 - 创建订单
 */
add_action('wp_ajax_onedown_create_vip_order', 'onedown_ajax_create_vip_order');
if (!function_exists('onedown_ajax_create_vip_order')):
    function onedown_ajax_create_vip_order()
    {
        if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
            wp_send_json_error(array('msg' => '支付功能已禁用'));
        }
        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_vip_order_action')) {
            wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
        }

        $user_id = get_current_user_id();
        $plan_id = sanitize_key($_POST['plan_id'] ?? '');
        $method  = sanitize_key($_POST['method'] ?? 'wechat');

        // 验证套餐
        $levels = onedown_vip_levels();
        if (!isset($levels[$plan_id])) {
            wp_send_json_error(array('msg' => '无效的会员套餐'));
        }

        // 检查当前VIP状态（已经开通的同等级不再重复创建）
        $current_vip = onedown_get_user_vip_info($user_id);
        if ($current_vip['is_vip'] && $current_vip['plan_id'] === $plan_id) {
            wp_send_json_error(array('msg' => '您当前已是该会员等级'));
        }

        // 创建订单
        $order = onedown_create_vip_order($user_id, $plan_id, $method);

        wp_send_json_success(array(
            'msg'      => '订单创建成功',
            'order_id' => $order['order_id'],
            'amount'   => $order['amount'],
            'method'   => $order['method'],
            'status'   => $order['status'],
        ));
    }
endif;

/**
 * AJAX 收藏切换（添加/取消收藏）
 */
add_action('wp_ajax_onedown_toggle_favorite', 'onedown_ajax_toggle_favorite');
if (!function_exists('onedown_ajax_toggle_favorite')):
    function onedown_ajax_toggle_favorite()
    {
        if (! _pz('show_post_favorites', true)) {
            wp_send_json_error(array('msg' => '收藏功能已关闭'));
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_favorite_action')) {
            wp_send_json_error(array('msg' => '安全验证失败'));
        }

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error(array('msg' => '无效的文章'));
        }

        $favorites = get_user_meta($user_id, 'onedown_favorites', true);
        if (!is_array($favorites)) {
            $favorites = array();
        }

        $key = array_search($post_id, $favorites);
        if ($key !== false) {
            // 取消收藏
            unset($favorites[$key]);
            $favorites = array_values($favorites);
            update_user_meta($user_id, 'onedown_favorites', $favorites);
            wp_send_json_success(array('msg' => '已取消收藏', 'status' => 'removed'));
        } else {
            // 添加收藏
            $favorites[] = $post_id;
            update_user_meta($user_id, 'onedown_favorites', $favorites);
            wp_send_json_success(array('msg' => '收藏成功', 'status' => 'added'));
        }
    }
endif;

/**
 * AJAX 点赞切换
 */
add_action('wp_ajax_onedown_toggle_like', 'onedown_ajax_toggle_like');
if (!function_exists('onedown_ajax_toggle_like')):
    function onedown_ajax_toggle_like()
    {
        if (! _pz('show_post_likes', true)) {
            wp_send_json_error(array('msg' => '点赞功能已关闭'));
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_like_action')) {
            wp_send_json_error(array('msg' => '安全验证失败'));
        }

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error(array('msg' => '无效的文章'));
        }

        $likes = get_user_meta($user_id, 'onedown_likes', true);
        if (!is_array($likes)) {
            $likes = array();
        }

        $likes_count = (int) get_post_meta($post_id, 'likes_count', true);

        $key = array_search($post_id, $likes);
        if ($key !== false) {
            // 取消点赞
            unset($likes[$key]);
            $likes = array_values($likes);
            update_user_meta($user_id, 'onedown_likes', $likes);
            $likes_count = max(0, $likes_count - 1);
            update_post_meta($post_id, 'likes_count', $likes_count);
            wp_send_json_success(array('msg' => '已取消点赞', 'status' => 'removed', 'count' => $likes_count));
        } else {
            // 添加点赞
            $likes[] = $post_id;
            update_user_meta($user_id, 'onedown_likes', $likes);
            $likes_count++;
            update_post_meta($post_id, 'likes_count', $likes_count);
            wp_send_json_success(array('msg' => '点赞成功', 'status' => 'added', 'count' => $likes_count));
        }
    }
endif;

/**
 * AJAX 记录下载
 */
add_action('wp_ajax_onedown_record_download', 'onedown_ajax_record_download');
if (!function_exists('onedown_ajax_record_download')):
    function onedown_ajax_record_download()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_download_action')) {
            wp_send_json_error(array('msg' => '安全验证失败'));
        }

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id'] ?? 0);
        $title   = sanitize_text_field($_POST['title'] ?? '');

        $downloads = get_user_meta($user_id, 'onedown_downloads', true);
        if (!is_array($downloads)) {
            $downloads = array();
        }

        $downloads[] = array(
            'post_id' => $post_id,
            'title'   => $title ?: get_the_title($post_id),
            'time'    => current_time('mysql'),
            'url'     => get_permalink($post_id),
        );

        // 最多保留100条记录
        if (count($downloads) > 100) {
            $downloads = array_slice($downloads, -100);
        }

        update_user_meta($user_id, 'onedown_downloads', $downloads);

        wp_send_json_success(array('msg' => '已记录'));
    }
endif;

/**
 * VIP 支付成功模拟回调（管理员专用）
 * 仅用于展示，真实环境应接入支付宝/微信异步通知
 */
add_action('wp_ajax_onedown_vip_pay_callback', 'onedown_ajax_vip_pay_callback');
if (!function_exists('onedown_ajax_vip_pay_callback')):
    function onedown_ajax_vip_pay_callback()
    {
        if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
            wp_send_json_error(array('msg' => '支付功能已禁用'));
        }
        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_vip_order_action')) {
            wp_send_json_error(array('msg' => '安全验证失败'));
        }

        $user_id  = get_current_user_id();
        $order_id = sanitize_text_field($_POST['order_id'] ?? '');

        if (!$order_id) {
            wp_send_json_error(array('msg' => '订单号无效'));
        }

        // 模拟支付成功 - 实际生产环境应调用支付网关查询接口
        $result = onedown_vip_payment_success($order_id);

        if ($result) {
            wp_send_json_success(array(
                'msg' => '支付成功，会员权益已生效！',
                'redirect_to' => onedown_user_center_url(array('tab' => 'vip')),
            ));
        } else {
            wp_send_json_error(array('msg' => '订单处理失败，请联系管理员'));
        }
    }
endif;

/**
 * AJAX 评论
 */
add_action('wp_ajax_onedown_post_comment', 'onedown_ajax_post_comment');
add_action('wp_ajax_nopriv_onedown_post_comment', 'onedown_ajax_post_comment');
if (!function_exists('onedown_ajax_post_comment')):
    function onedown_ajax_post_comment()
    {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_comment_action')) {
            wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
        }

        // 验证码检查
        if (onedown_captcha_enabled('comment')) {
            $captcha_code = isset($_POST['captcha_code']) ? trim($_POST['captcha_code']) : '';
            if (!onedown_captcha_verify('comment', $captcha_code)) {
                wp_send_json_error(array('msg' => '验证码错误或已过期，请重新输入'));
            }
        }

        $post_id = intval($_POST['comment_post_ID'] ?? 0);
        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error(array('msg' => '无效的文章'));
        }

        if (!comments_open($post_id)) {
            wp_send_json_error(array('msg' => '评论已关闭'));
        }

        $comment_content = trim(wp_unslash($_POST['comment'] ?? ''));
        if (empty($comment_content)) {
            wp_send_json_error(array('msg' => '请输入评论内容'));
        }

        $comment_parent = intval($_POST['comment_parent'] ?? 0);
        if ($comment_parent) {
            $parent = get_comment($comment_parent);
            if (!$parent || (int)$parent->comment_post_ID !== $post_id) {
                $comment_parent = 0;
            }
        }

        $comment_data = array(
            'comment_post_ID'      => $post_id,
            'comment_content'      => $comment_content,
            'comment_parent'       => $comment_parent,
            'comment_type'         => 'comment',
        );

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $comment_data['user_id'] = $user->ID;
            $comment_data['comment_author'] = $user->display_name;
            $comment_data['comment_author_email'] = $user->user_email;
            $comment_data['comment_author_url'] = $user->user_url;
        } else {
            $comment_data['comment_author'] = sanitize_text_field(wp_unslash($_POST['author'] ?? ''));
            $comment_data['comment_author_email'] = sanitize_email(wp_unslash($_POST['email'] ?? ''));
            $comment_data['comment_author_url'] = esc_url_raw(wp_unslash($_POST['url'] ?? ''));

            if (empty($comment_data['comment_author'])) {
                wp_send_json_error(array('msg' => '请输入昵称'));
            }
            if (empty($comment_data['comment_author_email'])) {
                wp_send_json_error(array('msg' => '请输入邮箱'));
            }
        }

        $comment_id = wp_new_comment($comment_data, true);

        if (is_wp_error($comment_id)) {
            wp_send_json_error(array('msg' => $comment_id->get_error_message()));
        }

        $comment = get_comment($comment_id);

        // 渲染单条评论 HTML
        ob_start();
        $GLOBALS['comment_floor'] = 1;
        wp_list_comments(array(
            'style'       => 'div',
            'short_ping'  => true,
            'walker'      => new Onedown_Walker_Comment(),
            'max_depth'   => get_option('thread_comments_depth') ?: 3,
            'avatar_size' => 42,
            'echo'        => true,
        ), array($comment));
        $html = ob_get_clean();

        $comment_count = get_comments_number($post_id);
        $is_approved   = '1' === $comment->comment_approved;

        wp_send_json_success(array(
            'html'          => $html,
            'comment_count' => $comment_count,
            'status'        => $is_approved ? 'approved' : 'pending',
            'msg'           => $is_approved ? '评论发表成功' : '评论发表成功，等待审核',
        ));
    }
endif;

/**
 * AJAX 推广提现申请
 */
add_action('wp_ajax_onedown_referral_withdraw', 'onedown_ajax_referral_withdraw');
if (!function_exists('onedown_ajax_referral_withdraw')):
    function onedown_ajax_referral_withdraw()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!_pz('referral_enabled', false)) {
            wp_send_json_error(array('msg' => '推广功能未启用'));
        }

        if (!_pz('referral_withdraw_enabled', true)) {
            wp_send_json_error(array('msg' => '提现功能已关闭，佣金将自动转入余额'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_referral_action')) {
            wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
        }

        $user_id = get_current_user_id();
        $amount  = floatval($_POST['amount'] ?? 0);
        $account = sanitize_text_field($_POST['account'] ?? '');
        $note    = sanitize_textarea_field($_POST['note'] ?? '');

        $result = onedown_referral_submit_withdraw($user_id, $amount, $account, $note);

        if ($result['success']) {
            wp_send_json_success(array('msg' => $result['msg']));
        } else {
            wp_send_json_error(array('msg' => $result['msg']));
        }
    }
endif;

/**
 * AJAX 加载用户中心标签页内容
 */
add_action('wp_ajax_onedown_load_tab', 'onedown_ajax_load_tab');
if (!function_exists('onedown_ajax_load_tab')):
    function onedown_ajax_load_tab()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_tab_action')) {
            wp_send_json_error(array('msg' => '安全验证失败'));
        }

        $tab = sanitize_key($_POST['tab'] ?? 'dashboard');
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $user_id = get_current_user_id();

        ob_start();
        onedown_render_tab_content($tab, $user_id, $page);
        $html = ob_get_clean();

        wp_send_json_success(array(
            'tab'  => $tab,
            'page' => $page,
            'html' => $html,
        ));
    }
endif;

/**
 * AJAX 获取订单详情
 */
add_action('wp_ajax_onedown_order_detail', 'onedown_ajax_order_detail');
if (!function_exists('onedown_ajax_order_detail')):
    function onedown_ajax_order_detail()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_tab_action')) {
            wp_send_json_error(array('msg' => '安全验证失败'));
        }

        $user_id = get_current_user_id();
        $order_id = sanitize_text_field($_POST['order_id'] ?? '');

        if (empty($order_id)) {
            wp_send_json_error(array('msg' => '订单号无效'));
        }

        $order = onedown_get_order($order_id);
        if (!$order || (int) $order->user_id !== $user_id) {
            wp_send_json_error(array('msg' => '订单不存在'));
        }

        // 订单类型标签
        $type_labels = array(
            ONEDOWN_ORDER_TYPE_POST_READ      => '付费阅读',
            ONEDOWN_ORDER_TYPE_POST_DOWNLOAD   => '付费下载',
            ONEDOWN_ORDER_TYPE_VIP             => '会员开通',
            ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE => '余额充值',
            ONEDOWN_ORDER_TYPE_AD              => '广告投放',
        );
        if (defined('ONEDOWN_ORDER_TYPE_LICENSE')) {
            $type_labels[ONEDOWN_ORDER_TYPE_LICENSE] = '授权购买';
        }
        $order_type_label = isset($type_labels[$order->order_type]) ? $type_labels[$order->order_type] : $order->order_type;

        // 支付方式标签
        $pay_type_labels = array(
            'alipay'  => '支付宝',
            'wechat'  => '微信支付',
            'balance' => '余额支付',
            'epay'    => '易支付',
            'offline' => '线下支付',
        );
        $pay_type_label = isset($pay_type_labels[$order->pay_type]) ? $pay_type_labels[$order->pay_type] : $order->pay_type;

        // 获取文章标题
        $post_title = '';
        if ($order->post_id > 0) {
            $post_obj = get_post($order->post_id);
            $post_title = $post_obj ? $post_obj->post_title : '';
        }

        // 获取授权码
        $license_codes = array();
        $user_license_codes = get_user_meta($user_id, 'onedown_license_codes', true);
        if (!empty($user_license_codes) && is_array($user_license_codes)) {
            foreach ($user_license_codes as $lc) {
                if (isset($lc['order_id']) && $lc['order_id'] === $order_id) {
                    $license_codes[] = $lc['license_code'];
                }
            }
        }

        // 解析 pay_detail
        $pay_detail = !empty($order->pay_detail) ? json_decode($order->pay_detail, true) : array();

        wp_send_json_success(array(
            'order_id'         => $order->order_id,
            'order_title'      => $order->order_title,
            'order_type'       => $order->order_type,
            'order_type_label' => $order_type_label,
            'post_title'       => $post_title,
            'order_price'      => number_format(floatval($order->order_price), 2),
            'pay_price'        => $order->pay_price > 0 ? number_format(floatval($order->pay_price), 2) : '',
            'pay_type_label'   => $pay_type_label,
            'pay_trade_no'     => $order->pay_trade_no,
            'status'           => $order->status,
            'status_label'     => onedown_order_status_name($order->status),
            'create_time'      => date_i18n('Y-m-d H:i:s', strtotime($order->create_time)),
            'pay_time'         => ($order->status === ONEDOWN_ORDER_STATUS_PAID && !empty($order->pay_time) && $order->pay_time !== '0000-00-00 00:00:00') ? date_i18n('Y-m-d H:i:s', strtotime($order->pay_time)) : '',
            'license_codes'    => $license_codes,
            'pay_detail'       => $pay_detail,
        ));
    }
endif;

/**
 * AJAX 取消订单
 */
add_action('wp_ajax_onedown_cancel_order', 'onedown_ajax_cancel_order');
if (!function_exists('onedown_ajax_cancel_order')):
    function onedown_ajax_cancel_order()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_tab_action')) {
            wp_send_json_error(array('msg' => '安全验证失败'));
        }

        $user_id = get_current_user_id();
        $order_id = sanitize_text_field($_POST['order_id'] ?? '');

        if (empty($order_id)) {
            wp_send_json_error(array('msg' => '订单号无效'));
        }

        $order = onedown_get_order($order_id);
        if (!$order || (int) $order->user_id !== $user_id) {
            wp_send_json_error(array('msg' => '订单不存在'));
        }

        if ($order->status !== ONEDOWN_ORDER_STATUS_PENDING) {
            wp_send_json_error(array('msg' => '该订单状态不允许取消'));
        }

        $updated = onedown_update_order($order_id, array('status' => ONEDOWN_ORDER_STATUS_CLOSED));

        if ($updated) {
            wp_send_json_success(array(
                'msg' => '订单已取消',
                'order_id' => $order_id,
            ));
        } else {
            wp_send_json_error(array('msg' => '取消失败，请稍后重试'));
        }
    }
endif;

/**
 * AJAX 批量删除订单
 */
add_action('wp_ajax_onedown_batch_delete_orders', 'onedown_ajax_batch_delete_orders');
if (!function_exists('onedown_ajax_batch_delete_orders')):
    function onedown_ajax_batch_delete_orders()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_tab_action')) {
            wp_send_json_error(array('msg' => '安全验证失败'));
        }

        $user_id = get_current_user_id();
        $order_ids = isset($_POST['order_ids']) ? json_decode(stripslashes($_POST['order_ids']), true) : array();

        if (empty($order_ids) || !is_array($order_ids)) {
            wp_send_json_error(array('msg' => '请选择要删除的订单'));
        }

        $deleted = 0;
        foreach ($order_ids as $order_id) {
            $order_id = sanitize_text_field($order_id);
            $order = onedown_get_order($order_id);
            if ($order && (int) $order->user_id === $user_id) {
                if (onedown_delete_order($order_id)) {
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            wp_send_json_success(array(
                'msg'     => sprintf('成功删除 %d 条订单记录', $deleted),
                'deleted' => $deleted,
            ));
        } else {
            wp_send_json_error(array('msg' => '删除失败，请稍后重试'));
        }
    }
endif;

/**
 * AJAX 加载订单分页（PJAX）
 */
add_action('wp_ajax_onedown_load_order_page', 'onedown_ajax_load_order_page');
if (!function_exists('onedown_ajax_load_order_page')):
    function onedown_ajax_load_order_page()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_tab_action')) {
            wp_send_json_error(array('msg' => '安全验证失败'));
        }

        $user_id = get_current_user_id();
        $page = max(1, intval($_POST['page'] ?? 1));
        $filter = sanitize_text_field($_POST['filter'] ?? 'all');

        $per_page = 10;
        $offset = ($page - 1) * $per_page;

        $status = '';
        if ($filter !== 'all') {
            $status = $filter;
        }

        $orders = onedown_get_user_orders_db($user_id, array(
            'limit'  => $per_page,
            'offset' => $offset,
            'status' => $status,
        ));

        $total = onedown_get_user_orders_count_db($user_id, $status);
        $total_pages = max(1, ceil($total / $per_page));

        ob_start();
        if (!empty($orders)) :
            foreach ($orders as $order) :
                $order_type_class = $order->order_type === ONEDOWN_ORDER_TYPE_VIP ? 'order-icon--vip' : ($order->order_type === ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE ? 'order-icon--recharge' : 'order-icon--post');
                $order_icon = $order->order_type === ONEDOWN_ORDER_TYPE_VIP ? 'fa-diamond' : ($order->order_type === ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE ? 'fa-money' : 'fa-file-text-o');
                $status_label = onedown_order_status_name($order->status);
                $order_time = $order->status === ONEDOWN_ORDER_STATUS_PAID && !empty($order->pay_time) && $order->pay_time !== '0000-00-00 00:00:00' ? date_i18n('Y-m-d H:i', strtotime($order->pay_time)) : date_i18n('Y-m-d', strtotime($order->create_time));
?>
                <div class="order-item" data-status="<?php echo esc_attr($order->status); ?>">
                    <label class="order-checkbox-wrap">
                        <input type="checkbox" class="order-checkbox" value="<?php echo esc_attr($order->order_id); ?>">
                    </label>
                    <span class="order-icon <?php echo esc_attr($order_type_class); ?>"><i
                            class="fa <?php echo esc_attr($order_icon); ?>"></i></span>
                    <div class="order-info">
                        <strong class="order-title"><?php echo esc_html($order->order_title); ?></strong>
                        <span class="order-meta">
                            <span class="order-meta-id">#<?php echo esc_html($order->order_id); ?></span>
                            <span class="order-meta-divider">|</span>
                            <span class="order-meta-time"><?php echo esc_html($order_time); ?></span>
                            <span class="order-meta-divider">|</span>
                            <span
                                class="order-meta-amount">￥<?php echo esc_html(number_format(floatval($order->order_price), 2)); ?></span>
                        </span>
                    </div>
                    <div class="order-actions">
                        <em
                            class="order-status order-status--<?php echo esc_attr($order->status); ?>"><?php echo esc_html($status_label); ?></em>
                        <button type="button" class="order-detail-btn"
                            data-order-id="<?php echo esc_attr($order->order_id); ?>">详情</button>
                        <?php if ($order->status === ONEDOWN_ORDER_STATUS_PENDING) : ?>
                            <button type="button" class="order-cancel-btn"
                                data-order-id="<?php echo esc_attr($order->order_id); ?>">取消</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach;
        else : ?>
            <div class="uc-empty">
                <i class="fa fa-inbox"></i>
                <p>暂无订单记录</p>
            </div>
        <?php endif;
        $html = ob_get_clean();

        // 生成分页 HTML
        ob_start();
        if ($total_pages > 1) : ?>
            <div class="order-pagination" data-order-pagination>
                <?php if ($page > 1) : ?>
                    <a href="#" class="order-page-link" data-page="<?php echo $page - 1; ?>"><i class="fa fa-angle-left"></i> 上一页</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                    <a href="#" class="order-page-link <?php echo $i === $page ? 'active' : ''; ?>"
                        data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($page < $total_pages) : ?>
                    <a href="#" class="order-page-link" data-page="<?php echo $page + 1; ?>">下一页 <i class="fa fa-angle-right"></i></a>
                <?php endif; ?>
            </div>
        <?php endif;
        $pagination = ob_get_clean();

        wp_send_json_success(array(
            'html'       => $html,
            'pagination' => $pagination,
            'page'       => $page,
            'total_pages' => $total_pages,
        ));
    }
endif;

/**
 * 渲染标签页内容（供 AJAX 和初始加载共用）
 */
if (!function_exists('onedown_render_tab_content')):
    function onedown_render_tab_content($tab, $user_id, $page = 1)
    {
        $current_user = get_userdata($user_id);
        if (!$current_user) {
            return;
        }
        $user_email = $current_user->user_email;
        $user_bio   = get_user_meta($user_id, 'description', true);
        $vip_info   = onedown_get_user_vip_info($user_id);
        $page_url   = onedown_user_center_url();

        // 订单分页参数
        $order_per_page = 10;
        $order_page = max(1, intval($page));
        $order_offset = ($order_page - 1) * $order_per_page;
        $order_total = onedown_get_user_orders_count_db($user_id);
        $order_total_pages = max(1, ceil($order_total / $order_per_page));
        $orders     = onedown_get_user_orders_db($user_id, array(
            'limit'  => $order_per_page,
            'offset' => $order_offset,
        ));
        $user_balance = floatval(get_user_meta($user_id, 'onedown_balance', true));
        ?>
        <div class="section-card" data-tab-content="<?php echo esc_attr($tab); ?>">

            <!-- 余额充值弹窗 -->
            <div class="vip-pay-modal" id="ad-recharge-modal" aria-hidden="true">
                <div class="vip-pay-mask"></div>
                <div class="vip-pay-dialog" role="dialog" style="max-width:420px;">
                    <button class="vip-pay-close" type="button" aria-label="关闭" data-ad-recharge-close><i
                            class="fa fa-times"></i></button>
                    <div class="vip-pay-head">
                        <span class="vip-pay-icon"><i class="fa fa-wallet"></i></span>
                        <div>
                            <span class="vip-pay-kicker">BALANCE RECHARGE</span>
                            <h2>余额充值</h2>
                            <p>当前余额：<strong id="recharge-current-balance"
                                    style="color:var(--od-primary);">￥<?php echo number_format($user_balance, 2); ?></strong>
                            </p>
                        </div>
                    </div>
                    <div class="vip-pay-body">
                        <div class="vip-pay-block">
                            <h3>充值金额</h3>
                            <div class="vip-plan-options">
                                <button type="button" class="vip-plan-option active" data-amount="10">
                                    <strong>￥10</strong>
                                </button>
                                <button type="button" class="vip-plan-option" data-amount="50">
                                    <strong>￥50</strong>
                                </button>
                                <button type="button" class="vip-plan-option" data-amount="100">
                                    <strong>￥100</strong>
                                </button>
                                <button type="button" class="vip-plan-option" data-amount="200">
                                    <strong>￥200</strong>
                                </button>
                                <button type="button" class="vip-plan-option" data-amount="500">
                                    <strong>￥500</strong>
                                </button>
                                <input type="number" id="recharge-custom-amount" placeholder="自定义金额" min="1"
                                    style="width:100%;padding:14px;border:1px solid var(--od-line);border-radius:10px;text-align:center;font-weight:800;font-size:15px;color:#252c3a;box-sizing:border-box;background:var(--od-card);outline:none;">
                            </div>
                        </div>
                        <div class="vip-pay-block">
                            <h3>支付方式</h3>
                            <div class="vip-method-options" id="recharge-pay-methods">
                                <button type="button" class="vip-method-option active" data-recharge-pay-method="alipay"><i
                                        class="fa fa-credit-card-alt"></i> 支付宝</button>
                                <button type="button" class="vip-method-option" data-recharge-pay-method="wechat"><i
                                        class="fa fa-wechat"></i> 微信支付</button>
                                <button type="button" class="vip-method-option" data-recharge-pay-method="balance"><i
                                        class="fa fa-money"></i> 余额支付</button>
                            </div>
                        </div>
                        <div class="vip-order-box">
                            <div><span>充值金额</span><strong id="recharge-order-amount">￥10.00</strong></div>
                            <div><span>支付方式</span><strong id="recharge-order-method">支付宝</strong></div>
                            <div class="vip-order-total"><span>应付金额</span><strong>￥<em
                                        id="recharge-order-price">10.00</em></strong>
                            </div>
                        </div>
                        <p class="vip-pay-status" id="recharge-pay-status"></p>
                    </div>
                    <div class="vip-pay-actions">
                        <button class="vip-pay-secondary" type="button" data-ad-recharge-close>取消</button>
                        <button id="ad-recharge-btn" class="vip-pay-primary" type="button">
                            充值</button>
                    </div>
                </div>
            </div>

            <script>
                // ── 全局函数：关闭支付弹窗 ──
                function adClosePayModal() {
                    var m = document.getElementById('ad-pay-modal');
                    if (m) {
                        m.classList.remove('is-show');
                        m.setAttribute('aria-hidden', 'true');
                        document.body.classList.remove('modal-open');
                    }
                }

                // ── 全局函数：充值弹窗 ──
                function showRechargeModal() {
                    var m = document.getElementById('ad-recharge-modal');
                    if (!m) return;
                    m.classList.add('is-show');
                    m.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                    document.getElementById('recharge-current-balance').textContent =
                        '￥<?php echo number_format($user_balance, 2); ?>';
                }

                function transferToBalance() {
                    if (!confirm('确认将全部可提现佣金转入账户余额？')) return;
                    var btns = document.querySelectorAll('[onclick="transferToBalance()"]');
                    btns.forEach(function(b) {
                        b.disabled = true;
                        b.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';
                    });

                    var fd = new FormData();
                    fd.set('action', 'onedown_transfer_to_balance');
                    fd.set('_wpnonce', document.getElementById('ad_apply_nonce').value);

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            body: new URLSearchParams(fd)
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(res) {
                            if (res.success) {
                                alert(res.data.msg);
                                window.location.reload();
                            } else {
                                alert(res.data.msg || '操作失败');
                                btns.forEach(function(b) {
                                    b.disabled = false;
                                    b.innerHTML = '<i class="fa fa-exchange"></i> 转余额';
                                });
                            }
                        })
                        .catch(function() {
                            alert('网络错误，请重试');
                            btns.forEach(function(b) {
                                b.disabled = false;
                                b.innerHTML = '<i class="fa fa-exchange"></i> 转余额';
                            });
                        });
                }

                // ── 充值弹窗交互绑定 ──
                (function() {
                    var rechargeModal = document.getElementById('ad-recharge-modal');
                    if (!rechargeModal) return;

                    var orderAmount = document.getElementById('recharge-order-amount');
                    var orderMethod = document.getElementById('recharge-order-method');
                    var orderPrice = document.getElementById('recharge-order-price');

                    function updateRechargeOrder(amount, methodEl) {
                        var amt = parseFloat(amount) || 0;
                        orderAmount.textContent = '￥' + amt.toFixed(2);
                        orderPrice.textContent = amt.toFixed(2);
                        if (methodEl) {
                            orderMethod.textContent = methodEl.textContent.trim();
                        }
                    }

                    // 金额选择
                    rechargeModal.querySelectorAll('.vip-plan-option[data-amount]').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            rechargeModal.querySelectorAll('.vip-plan-option[data-amount]').forEach(function(
                                b) {
                                b.classList.remove('active');
                            });
                            btn.classList.add('active');
                            document.getElementById('recharge-custom-amount').value = '';
                            var payMethodEl = rechargeModal.querySelector('.vip-method-option.active');
                            updateRechargeOrder(btn.getAttribute('data-amount'), payMethodEl);
                        });
                    });
                    // 自定义金额清空选中态
                    document.getElementById('recharge-custom-amount').addEventListener('focus', function() {
                        rechargeModal.querySelectorAll('.vip-plan-option[data-amount]').forEach(function(b) {
                            b.classList.remove('active');
                        });
                    });
                    document.getElementById('recharge-custom-amount').addEventListener('input', function() {
                        var val = parseFloat(this.value) || 0;
                        var payMethodEl = rechargeModal.querySelector('.vip-method-option.active');
                        updateRechargeOrder(val > 0 ? val : 0, payMethodEl);
                    });
                    // 关闭
                    rechargeModal.addEventListener('click', function(e) {
                        if (e.target.closest('[data-ad-recharge-close]')) {
                            rechargeModal.classList.remove('is-show');
                            if (document.activeElement) document.activeElement.blur();
                            rechargeModal.setAttribute('aria-hidden', 'true');
                            document.body.classList.remove('modal-open');
                        }
                    });
                    // 支付方式选择
                    var rechargePayMethods = document.getElementById('recharge-pay-methods');
                    if (rechargePayMethods) {
                        rechargePayMethods.addEventListener('click', function(e) {
                            var btn = e.target.closest('[data-recharge-pay-method]');
                            if (!btn) return;
                            rechargePayMethods.querySelectorAll('[data-recharge-pay-method]').forEach(function(b) {
                                b.classList.remove('active');
                            });
                            btn.classList.add('active');
                            // 更新订单摘要中的支付方式
                            var activeAmtBtn = rechargeModal.querySelector('.vip-plan-option.active');
                            var amount = activeAmtBtn ? activeAmtBtn.getAttribute('data-amount') : document
                                .getElementById('recharge-custom-amount').value;
                            updateRechargeOrder(amount || 0, btn);
                        });
                    }
                    // 充值按钮
                    document.getElementById('ad-recharge-btn').addEventListener('click', function() {
                        var rechargeBtn = this;
                        var amount = 0;
                        var activeBtn = rechargeModal.querySelector('.vip-plan-option.active');
                        if (activeBtn) {
                            amount = parseFloat(activeBtn.getAttribute('data-amount'));
                        } else {
                            amount = parseFloat(document.getElementById('recharge-custom-amount').value);
                        }
                        if (!amount || amount <= 0) {
                            alert('请选择或输入充值金额');
                            return;
                        }

                        rechargeBtn.disabled = true;
                        rechargeBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';

                        var payMethod = rechargePayMethods ? rechargePayMethods.querySelector(
                            '.vip-method-option.active') : null;
                        var payMethodId = payMethod ? payMethod.getAttribute('data-recharge-pay-method') : 'alipay';

                        var statusEl = document.getElementById('recharge-pay-status');
                        if (statusEl) statusEl.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 正在创建订单...';

                        var fd = new FormData();
                        fd.set('action', 'onedown_initiate_pay');
                        fd.set('order_type', 'balance_recharge');
                        fd.set('pay_method', payMethodId);
                        fd.set('recharge_amount', amount.toFixed(2));
                        fd.set('_wpnonce', window.onedownData ? onedownData.payNonce : '');

                        fetch(window.onedownData ? onedownData.ajaxUrl :
                                '<?php echo admin_url('admin-ajax.php'); ?>', {
                                    method: 'POST',
                                    body: fd
                                })
                            .then(function(r) {
                                return r.json();
                            })
                            .then(function(data) {
                                if (data.success) {
                                    var result = data.data;
                                    if (result.pay_type === 'redirect') {
                                        if (statusEl) statusEl.innerHTML = '正在跳转到支付页面...';
                                        rechargeModal.classList.remove('is-show');
                                        rechargeModal.setAttribute('aria-hidden', 'true');
                                        document.body.classList.remove('modal-open');
                                        setTimeout(function() {
                                            window.location.href = result.pay_url;
                                        }, 200);
                                    } else if (result.pay_type === 'qrcode') {
                                        if (statusEl) statusEl.innerHTML = '';
                                        rechargeModal.classList.remove('is-show');
                                        rechargeModal.setAttribute('aria-hidden', 'true');
                                        document.body.classList.remove('modal-open');
                                        result.pay_method = payMethodId;
                                        result.order_title = '余额充值：￥' + amount.toFixed(2);
                                        if (typeof window.openQrcodeModal === 'function') {
                                            window.openQrcodeModal(result);
                                        } else {
                                            window.location.href = result.pay_url || window.location.href;
                                        }
                                    } else if (result.pay_type === 'success') {
                                        if (statusEl) statusEl.innerHTML =
                                            '<span style="color:#4CAF50;"><i class="fa fa-check-circle"></i> 充值成功！页面即将刷新...</span>';
                                        rechargeBtn.disabled = false;
                                        rechargeBtn.innerHTML = '<i class="fa fa-credit-card"></i> 充值';
                                        setTimeout(function() {
                                            window.location.reload();
                                        }, 800);
                                    } else if (result.pay_type === 'offline') {
                                        if (statusEl) {
                                            statusEl.innerHTML = result.msg || '订单已创建，请线下付款';
                                            if (result.offline_info) {
                                                statusEl.innerHTML += '<br><small>' + result.offline_info +
                                                    '</small>';
                                            }
                                        }
                                        rechargeBtn.disabled = false;
                                        rechargeBtn.innerHTML = '<i class="fa fa-credit-card"></i> 充值';
                                    } else {
                                        if (statusEl) statusEl.innerHTML = result.msg || '支付发起失败';
                                        rechargeBtn.disabled = false;
                                        rechargeBtn.innerHTML = '<i class="fa fa-credit-card"></i> 充值';
                                    }
                                } else {
                                    if (statusEl) statusEl.innerHTML = data.data && data.data.msg ? data.data.msg :
                                        '充值失败';
                                    rechargeBtn.disabled = false;
                                    rechargeBtn.innerHTML = '<i class="fa fa-credit-card"></i> 充值';
                                }
                            })
                            .catch(function() {
                                if (statusEl) statusEl.innerHTML = '操作失败，请重试';
                                rechargeBtn.disabled = false;
                                rechargeBtn.innerHTML = '<i class="fa fa-credit-card"></i> 充值';
                            });
                    });
                })();
            </script>

            <?php if ('vip' === $tab) : ?>
                <div class="user-page-toolbar">
                    <h3 style="margin:0;font-weight:800;color:#252c3a;"><i class="fa fa-diamond"></i> 我的会员</h3>
                </div>
                <div class="vip-tab-content">
                    <?php echo onedown_user_vip_tab_content($user_id); ?>
                </div>

            <?php elseif ('orders' === $tab) : ?>
                <div class="user-page-toolbar order-toolbar">
                    <div class="user-page-tabs" data-order-tabs>
                        <a href="#" class="active" data-filter="all">全部</a>
                        <a href="#" data-filter="pending">待付款</a>
                        <a href="#" data-filter="paid">已完成</a>
                        <a href="#" data-filter="closed">已关闭</a>
                    </div>
                    <div class="order-batch-actions">
                        <label class="order-select-all" title="全选/取消全选">
                            <input type="checkbox" id="order-select-all">
                            <span>全选</span>
                        </label>
                        <button type="button" class="order-batch-delete-btn" id="order-batch-delete-btn" disabled>
                            <i class="fa fa-trash"></i> 批量删除
                        </button>
                    </div>
                </div>
                <div class="order-list uc-list" data-order-list>
                    <?php if (!empty($orders)) : ?>
                        <?php foreach ($orders as $order) :
                            $order_type_class = $order->order_type === ONEDOWN_ORDER_TYPE_VIP ? 'order-icon--vip' : ($order->order_type === ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE ? 'order-icon--recharge' : 'order-icon--post');
                            $order_icon = $order->order_type === ONEDOWN_ORDER_TYPE_VIP ? 'fa-diamond' : ($order->order_type === ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE ? 'fa-money' : 'fa-file-text-o');
                            $status_label = onedown_order_status_name($order->status);
                            $order_time = $order->status === ONEDOWN_ORDER_STATUS_PAID && !empty($order->pay_time) && $order->pay_time !== '0000-00-00 00:00:00' ? date_i18n('Y-m-d H:i', strtotime($order->pay_time)) : date_i18n('Y-m-d', strtotime($order->create_time));
                        ?>
                            <div class="order-item" data-status="<?php echo esc_attr($order->status); ?>">
                                <label class="order-checkbox-wrap">
                                    <input type="checkbox" class="order-checkbox" value="<?php echo esc_attr($order->order_id); ?>">
                                </label>
                                <span class="order-icon <?php echo esc_attr($order_type_class); ?>"><i
                                        class="fa <?php echo esc_attr($order_icon); ?>"></i></span>
                                <div class="order-info">
                                    <strong class="order-title"><?php echo esc_html($order->order_title); ?></strong>
                                    <span class="order-meta">
                                        <span class="order-meta-id">#<?php echo esc_html($order->order_id); ?></span>
                                        <span class="order-meta-divider">|</span>
                                        <span class="order-meta-time"><?php echo esc_html($order_time); ?></span>
                                        <span class="order-meta-divider">|</span>
                                        <span
                                            class="order-meta-amount">￥<?php echo esc_html(number_format(floatval($order->order_price), 2)); ?></span>
                                    </span>
                                </div>
                                <div class="order-actions">
                                    <em
                                        class="order-status order-status--<?php echo esc_attr($order->status); ?>"><?php echo esc_html($status_label); ?></em>
                                    <button type="button" class="order-detail-btn"
                                        data-order-id="<?php echo esc_attr($order->order_id); ?>">详情</button>
                                    <?php if ($order->status === ONEDOWN_ORDER_STATUS_PENDING) : ?>
                                        <button type="button" class="order-repay-btn"
                                            data-order-id="<?php echo esc_attr($order->order_id); ?>">继续支付</button>
                                        <button type="button" class="order-cancel-btn"
                                            data-order-id="<?php echo esc_attr($order->order_id); ?>">取消</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="uc-empty">
                            <i class="fa fa-inbox"></i>
                            <p>暂无订单记录</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($order_total_pages > 1) : ?>
                    <div class="order-pagination" data-order-pagination>
                        <?php if ($order_page > 1) : ?>
                            <a href="#" class="order-page-link" data-page="<?php echo $order_page - 1; ?>"><i class="fa fa-angle-left"></i>
                                上一页</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $order_total_pages; $i++) : ?>
                            <a href="#" class="order-page-link <?php echo $i === $order_page ? 'active' : ''; ?>"
                                data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($order_page < $order_total_pages) : ?>
                            <a href="#" class="order-page-link" data-page="<?php echo $order_page + 1; ?>">下一页 <i
                                    class="fa fa-angle-right"></i></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>





            <?php elseif ('downloads' === $tab) : ?>
                <div class="user-page-toolbar">
                    <h3 class="uc-page-title"><i class="fa fa-download"></i> 下载记录</h3>
                </div>
                <div class="download-list uc-list">
                    <?php
                    $downloads = onedown_get_user_downloads($user_id);
                    if (!empty($downloads)) :
                        foreach ($downloads as $dl) : ?>
                            <div class="download-item">
                                <span class="order-icon"><i class="fa fa-file-archive-o"></i></span>
                                <div>
                                    <strong><?php echo esc_html($dl['title']); ?></strong>
                                    <span><?php echo esc_html($dl['time']); ?></span>
                                </div>
                                <a href="<?php echo esc_url($dl['url']); ?>"><i class="fa fa-download"></i> 下载</a>
                            </div>
                        <?php endforeach;
                    else : ?>
                        <div class="uc-empty">
                            <i class="fa fa-download"></i>
                            <p>暂无下载记录</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ('favorites' === $tab) : ?>
                <div class="user-page-toolbar">
                    <h3 class="uc-page-title"><i class="fa fa-star-o"></i> 我的收藏</h3>
                </div>
                <div class="favorite-grid uc-list">
                    <?php
                    $favorites = get_user_meta($user_id, 'onedown_favorites', true);
                    if (!empty($favorites) && is_array($favorites)) :
                        $fav_posts = get_posts(array(
                            'post__in'       => $favorites,
                            'posts_per_page' => 50,
                            'post_type'      => 'any',
                            'no_found_rows'  => true,
                        ));
                        foreach ($fav_posts as $fav_post) :
                            setup_postdata($fav_post);
                            $cats = get_the_category($fav_post->ID);
                            $cat_name = !empty($cats) ? $cats[0]->name : '文章';
                    ?>
                            <article class="download-item">
                                <span class="order-icon order-icon--fav"><i class="fa fa-star"></i></span>
                                <div>
                                    <strong><?php echo esc_html(get_the_title($fav_post)); ?></strong>
                                    <span><i class="fa fa-file-text-o"></i> <?php echo esc_html($cat_name); ?> · <i
                                            class="fa fa-clock-o"></i> <?php echo esc_html(get_the_date('Y-m-d', $fav_post)); ?></span>
                                </div>
                                <a href="<?php echo esc_url(get_permalink($fav_post)); ?>"><i class="fa fa-eye"></i> 查看</a>
                            </article>
                        <?php endforeach;
                        wp_reset_postdata();
                    else : ?>
                        <div class="uc-empty" style="grid-column:1/-1;">
                            <i class="fa fa-heart-o"></i>
                            <p>暂无收藏内容</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ('comments' === $tab) : ?>
                <div class="user-page-toolbar">
                    <h3 class="uc-page-title"><i class="fa fa-comments-o"></i> 我的评论</h3>
                </div>
                <div class="user-timeline uc-list">
                    <?php
                    $user_comments = get_comments(array(
                        'user_id' => $user_id,
                        'number'  => 20,
                        'status'  => 'approve',
                    ));
                    if (!empty($user_comments)) :
                        foreach ($user_comments as $comment) :
                            $post_title = get_the_title($comment->comment_post_ID);
                            $comment_url = get_permalink($comment->comment_post_ID) . '#comment-' . $comment->comment_ID;
                    ?>
                            <a href="<?php echo esc_url($comment_url); ?>" class="user-timeline-link">
                                <i class="fa fa-comment-o"></i>
                                <strong><?php echo esc_html(wp_trim_words($comment->comment_content, 12, '...')); ?></strong>
                                <em><?php echo esc_html(date_i18n('Y-m-d H:i', strtotime($comment->comment_date))); ?> ·
                                    <?php echo esc_html($post_title ? wp_trim_words($post_title, 6) : '查看'); ?></em>
                            </a>
                        <?php endforeach;
                    else : ?>
                        <div class="uc-empty" style="grid-column:1/-1;">
                            <i class="fa fa-comments-o"></i>
                            <p>暂无评论</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ('profile' === $tab) : ?>
                <div class="user-page-toolbar">
                    <h3 class="uc-page-title"><i class="fa fa-cog"></i> 账号设置</h3>
                </div>
                <?php
                ob_start();
                ?>
                <form class="account-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="onedown_update_profile">
                    <input type="hidden" name="redirect_to"
                        value="<?php echo esc_url(add_query_arg(array('tab' => 'profile', 'profile_updated' => '1'), $page_url)); ?>">
                    <?php wp_nonce_field('onedown_profile_action', 'onedown_profile_nonce'); ?>
                    <label><span>昵称</span><input type="text" name="display_name"
                            value="<?php echo esc_attr($current_user->display_name); ?>"></label>
                    <label><span>用户名</span><input type="text" value="<?php echo esc_attr($current_user->user_login); ?>"
                            readonly></label>
                    <label><span>邮箱</span><input type="email" name="email" value="<?php echo esc_attr($user_email); ?>"></label>
                    <label class="wide"><span>个人简介</span><textarea name="description"
                            rows="4"><?php echo esc_textarea($user_bio); ?></textarea></label>
                    <div class="account-actions">
                        <button type="submit"><i class="fa fa-save"></i> 保存设置</button>
                        <a href="<?php echo esc_url($page_url); ?>"><i class="fa fa-arrow-left"></i> 返回概览</a>
                    </div>
                </form>
                <?php
                $profile_html = ob_get_clean();
                do_action('onedown_profile_after_form');
                echo apply_filters('onedown_profile_tab_content', $profile_html);
                ?>

            <?php elseif ('password' === $tab) : ?>
                <div class="user-page-toolbar">
                    <h3 class="uc-page-title"><i class="fa fa-lock"></i> 修改密码</h3>
                </div>
                <form class="account-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="onedown_update_profile">
                    <input type="hidden" name="redirect_to"
                        value="<?php echo esc_url(add_query_arg(array('tab' => 'password', 'profile_updated' => '1'), $page_url)); ?>">
                    <?php wp_nonce_field('onedown_profile_action', 'onedown_profile_nonce'); ?>
                    <div style="grid-column:1/-1;">
                        <p style="margin:0 0 16px;color:var(--od-muted);font-size:13px;">设置新密码后，下次登录请使用新密码。</p>
                    </div>
                    <label style="grid-column:1/-1;"><span>新密码</span><input type="password" name="pass1" placeholder="输入新密码"
                            required minlength="6" autocomplete="new-password"></label>
                    <label style="grid-column:1/-1;"><span>确认新密码</span><input type="password" name="pass2" placeholder="再次输入新密码"
                            required minlength="6" autocomplete="new-password"></label>
                    <div class="account-actions">
                        <button type="submit"><i class="fa fa-save"></i> 修改密码</button>
                        <a href="<?php echo esc_url($page_url); ?>"><i class="fa fa-arrow-left"></i> 返回概览</a>
                    </div>
                </form>

            <?php elseif ('ad-apply' === $tab) : ?>
                <div class="user-page-toolbar">
                    <h3 class="uc-page-title"><i class="fa fa-bullhorn"></i> 广告申请</h3>
                </div>
                <?php
                $user_balance = floatval(get_user_meta($user_id, 'onedown_balance', true));
                if (!_pz('ad_self_service_enabled', false)) : ?>
                    <div class="uc-empty">
                        <i class="fa fa-bullhorn"></i>
                        <p>广告自助投放功能未启用</p>
                    </div>
                <?php else :
                    $ad_price = floatval(_pz('ad_price', '19.99'));
                    $ad_days  = (int) _pz('ad_duration_days', 30);
                    $my_ads = new WP_Query(array(
                        'post_type'      => 'onedown_ad',
                        'post_status'    => array('draft', 'pending', 'publish'),
                        'posts_per_page' => 20,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                        'meta_query'     => array(
                            array('key' => '_ad_user_id', 'value' => $user_id),
                        ),
                    ));
                    $pay_page_url = function_exists('onedown_user_center_url') ? onedown_user_center_url(array('tab' => 'ad-apply')) : home_url('/user-center/?tab=ad-apply');
                ?>
                    <div style="padding:16px;">
                        <div
                            style="background:linear-gradient(135deg,rgba(240,68,148,.06),rgba(79,124,255,.06));border:1px solid rgba(240,68,148,.12);border-radius:12px;padding:16px 20px;margin-bottom:16px;">
                            <p style="margin:0 0 6px;font-size:13px;color:#596170;">
                                <strong>投放价格：</strong>￥<?php echo number_format($ad_price, 2); ?>
                                &nbsp;·&nbsp; <strong>投放时长：</strong><?php echo $ad_days; ?>天
                                &nbsp;·&nbsp; <strong>审核说明：</strong>支付后需管理员审核通过后展示
                            </p>
                        </div>

                        <form id="ad-apply-form" data-balance="<?php echo $user_balance; ?>" data-price="<?php echo $ad_price; ?>"
                            style="display:grid;gap:12px;margin-bottom:20px;">
                            <?php wp_nonce_field('onedown_ad_apply_action', 'ad_apply_nonce'); ?>
                            <label>
                                <span style="display:block;margin-bottom:4px;font-size:13px;font-weight:700;color:#252c3a;">广告文字
                                    *</span>
                                <input type="text" name="ad_text" class="widefat" placeholder="例如：优质资源下载平台" required
                                    style="width:100%;padding:8px 12px;border:1px solid var(--od-line);border-radius:8px;">
                            </label>
                            <label>
                                <span style="display:block;margin-bottom:4px;font-size:13px;font-weight:700;color:#252c3a;">目标链接
                                    *</span>
                                <input type="url" name="ad_url" class="widefat" placeholder="https://example.com" required
                                    style="width:100%;padding:8px 12px;border:1px solid var(--od-line);border-radius:8px;">
                            </label>
                            <label>
                                <span style="display:block;margin-bottom:4px;font-size:13px;font-weight:700;color:#252c3a;">联系方式</span>
                                <input type="text" name="ad_contact" class="widefat" placeholder="QQ/微信/邮箱（选填）"
                                    style="width:100%;padding:8px 12px;border:1px solid var(--od-line);border-radius:8px;">
                            </label>
                            <?php
                            $ad_agreement = _pz('ad_agreement_content', '');
                            if (!empty(trim(wp_strip_all_tags($ad_agreement)))) :
                            ?>
                                <label
                                    style="display:flex;align-items:flex-start;gap:8px;padding:10px 14px;background:rgba(240,68,148,.04);border:1px solid rgba(240,68,148,.12);border-radius:8px;">
                                    <input type="checkbox" name="ad_agreed" id="ad-agreed" value="1"
                                        style="margin-top:3px;width:16px;height:16px;accent-color:var(--od-primary);flex-shrink:0;">
                                    <span style="font-size:13px;color:#596170;line-height:1.5;">
                                        我已阅读并同意
                                        <a href="javascript:void(0)" id="ad-agreement-link"
                                            style="color:var(--od-primary);font-weight:700;text-decoration:underline;">
                                            《广告投放协议》
                                        </a>
                                    </span>
                                </label>
                            <?php endif; ?>
                            <div>
                                <button type="submit" id="ad-submit-btn" class="pill-btn"
                                    style="background:var(--od-gradient);color:#fff;border:none;padding:8px 24px;border-radius:999px;font-weight:800;cursor:pointer;<?php echo !empty(trim(wp_strip_all_tags($ad_agreement))) ? 'opacity:.5;' : ''; ?>"
                                    <?php echo !empty(trim(wp_strip_all_tags($ad_agreement))) ? 'disabled' : ''; ?>>
                                    <i class="fa fa-credit-card"></i> 提交并支付（￥<?php echo number_format($ad_price, 2); ?>）
                                </button>
                                <span id="ad-apply-msg" style="margin-left:12px;font-size:13px;"></span>
                            </div>
                        </form>

                        <?php if (!empty(trim(wp_strip_all_tags($ad_agreement)))) : ?>
                            <!-- 广告投放协议弹窗 -->
                            <div class="onedown-pay-modal" id="ad-agreement-modal" aria-hidden="true">
                                <div class="onedown-pay-mask"></div>
                                <div class="onedown-pay-dialog" role="dialog" style="max-width:640px;">
                                    <button class="onedown-pay-close" type="button" aria-label="关闭" data-ad-agreement-close><i
                                            class="fa fa-times"></i></button>
                                    <div class="onedown-pay-dialog-head">
                                        <span class="onedown-pay-dialog-icon"><i class="fa fa-file-text"></i></span>
                                        <div>
                                            <span class="onedown-pay-dialog-kicker">AD AGREEMENT</span>
                                            <h2>广告投放协议</h2>
                                        </div>
                                    </div>
                                    <div class="onedown-pay-dialog-body">
                                        <div
                                            style="max-height:400px;overflow-y:auto;padding:4px;font-size:14px;line-height:1.8;color:#252c3a;">
                                            <?php echo wp_kses_post($ad_agreement); ?>
                                        </div>
                                    </div>
                                    <div class="onedown-pay-dialog-actions" style="border-top:1px solid var(--od-line);padding:16px 28px;">
                                        <button class="onedown-pay-secondary" type="button" id="ad-agreement-reject-btn"><i
                                                class="fa fa-times"></i> 我不同意</button>
                                        <button id="ad-agreement-accept-btn" class="onedown-pay-primary" type="button"><i
                                                class="fa fa-check"></i> 我同意</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 我的广告列表 -->
                        <style>
                            .ad-detail-btn,
                            .ad-continue-pay-btn {
                                display: inline-flex;
                                align-items: center;
                                gap: 4px;
                                padding: 4px 12px;
                                font-size: 12px;
                                font-weight: 700;
                                border-radius: 999px;
                                cursor: pointer;
                                transition: all .2s;
                                white-space: nowrap;
                                line-height: 1.4;
                                background: transparent;
                                border: 1px solid var(--od-primary);
                                color: var(--od-primary)
                            }

                            .ad-detail-btn:hover {
                                background: var(--od-primary);
                                color: #fff
                            }

                            .ad-continue-pay-btn {
                                color: #4CAF50;
                                border-color: #4CAF50
                            }

                            .ad-continue-pay-btn:hover {
                                background: #4CAF50;
                                color: #fff
                            }

                            .ad-cancel-btn,
                            .ad-delete-btn {
                                display: inline-flex;
                                align-items: center;
                                gap: 4px;
                                padding: 4px 12px;
                                font-size: 12px;
                                font-weight: 700;
                                border: 1px solid #e74c3c;
                                border-radius: 999px;
                                cursor: pointer;
                                transition: all .2s;
                                white-space: nowrap;
                                line-height: 1.4;
                                color: #e74c3c;
                                background: transparent
                            }

                            .ad-cancel-btn:hover {
                                background: #e74c3c;
                                color: #fff
                            }

                            .ad-delete-btn {
                                border-color: #9aa1ad;
                                color: #9aa1ad
                            }

                            .ad-delete-btn:hover {
                                background: #9aa1ad;
                                color: #fff
                            }

                            .order-icon--draft {
                                color: #9aa1ad;
                                background: rgba(154, 161, 173, .1)
                            }

                            .order-icon--pending {
                                color: #f0a030;
                                background: rgba(240, 160, 48, .1)
                            }

                            .order-icon--paid {
                                color: #4CAF50;
                                background: rgba(76, 175, 80, .1)
                            }
                        </style>
                        <h4 style="margin:20px 0 12px;font-size:14px;font-weight:800;color:#252c3a;"><i class="fa fa-list"></i> 我的广告
                        </h4>
                        <?php if ($my_ads->have_posts()) : ?>
                            <div class="uc-list" style="gap:6px;">
                                <?php while ($my_ads->have_posts()) : $my_ads->the_post();
                                    $status = get_post_status();
                                    $status_labels = array(
                                        'draft'   => '<span style="color:#9aa1ad;">草稿</span>',
                                        'pending' => '<span style="color:#f0a030;">待审核</span>',
                                        'publish' => '<span style="color:#4CAF50;">投放中</span>',
                                    );
                                    $target_url = get_post_meta(get_the_ID(), '_ad_target_url', true);
                                    $expire = get_post_meta(get_the_ID(), '_ad_expire_date', true);
                                    $order_id = get_post_meta(get_the_ID(), '_ad_order_id', true);
                                    $ad_price_meta = get_post_meta(get_the_ID(), '_ad_price', true);
                                    $ad_contact_meta = get_post_meta(get_the_ID(), '_ad_contact', true);
                                    $ad_id = get_the_ID();
                                    $ad_title = get_the_title();

                                    // 获取关联订单状态
                                    $order_status = '';
                                    if ($order_id) {
                                        $order_data = onedown_get_order($order_id);
                                        $order_status = $order_data ? $order_data->status : '';
                                    }
                                    // 状态标签
                                    if ($order_status === 'closed') {
                                        $label = '<span style="color:#e74c3c;">已取消</span>';
                                        $status_class = 'closed';
                                    } elseif ($status === 'draft' && $order_status === 'pending') {
                                        $label = '<span style="color:#f0a030;">待付款</span>';
                                        $status_class = 'pending';
                                    } elseif ($status === 'draft' && !$order_id) {
                                        $label = '<span style="color:#9aa1ad;">未支付</span>';
                                        $status_class = 'draft';
                                    } else {
                                        $label = isset($status_labels[$status]) ? $status_labels[$status] : esc_html($status);
                                        $status_class = $status;
                                    }
                                    // 图标
                                    $ad_icon = ($status === 'publish') ? 'fa-check-circle' : (($status === 'pending') ? 'fa-clock-o' : 'fa-pencil-square-o');
                                    $icon_class = $status === 'publish' ? 'order-icon--paid' : ($status === 'pending' ? 'order-icon--pending' : 'order-icon--draft');
                                ?>
                                    <div class="order-item" data-ad-id="<?php echo $ad_id; ?>" style="border:1px solid var(--od-line);">
                                        <span class="order-icon <?php echo $icon_class; ?>"><i class="fa <?php echo $ad_icon; ?>"></i></span>
                                        <div class="order-info">
                                            <strong class="order-title"><?php echo esc_html($ad_title); ?></strong>
                                            <span class="order-meta">
                                                <?php if ($target_url) : ?><span>链接：<?php echo esc_html($target_url); ?></span>
                                                    <span class="order-meta-divider">|</span><?php endif; ?>
                                                <?php if ($expire) : ?><span>到期：<?php echo esc_html($expire); ?></span>
                                                    <span class="order-meta-divider">|</span><?php endif; ?>
                                                <span>金额：￥<?php echo number_format(floatval($ad_price_meta ?: $ad_price), 2); ?></span>
                                            </span>
                                        </div>
                                        <div class="order-actions">
                                            <em class="order-status order-status--<?php echo $status_class; ?>"><?php echo $label; ?></em>
                                            <!-- 详情按钮 -->
                                            <button type="button" class="ad-detail-btn" data-ad-id="<?php echo $ad_id; ?>"
                                                data-title="<?php echo esc_attr($ad_title); ?>"
                                                data-url="<?php echo esc_attr($target_url ?: ''); ?>"
                                                data-price="<?php echo floatval($ad_price_meta ?: $ad_price); ?>"
                                                data-contact="<?php echo esc_attr($ad_contact_meta ?: ''); ?>"
                                                data-expire="<?php echo esc_attr($expire ?: ''); ?>"
                                                data-status="<?php echo esc_attr($status_class); ?>"
                                                data-order-id="<?php echo esc_attr($order_id ?: ''); ?>" title="查看详情"><i
                                                    class="fa fa-info-circle"></i> 详情</button>
                                            <!-- 未支付：继续支付 + 取消 -->
                                            <?php if (($status === 'draft' && $order_status === 'pending') || ($status === 'draft' && !$order_id)) : ?>
                                                <button type="button" class="ad-continue-pay-btn"
                                                    data-ad-id="<?php echo $ad_id; ?>" data-order-id="<?php echo esc_attr($order_id ?: ''); ?>"
                                                    data-price="<?php echo floatval($ad_price_meta ?: $ad_price); ?>"
                                                    title="继续支付"><i class="fa fa-credit-card"></i>
                                                    继续支付</button>
                                                <button type="button" class="ad-cancel-btn" data-ad-id="<?php echo $ad_id; ?>"
                                                    title="取消订单"><i class="fa fa-times"></i> 取消</button>
                                            <?php endif; ?>
                                            <!-- 已取消：删除 -->
                                            <?php if ($order_status === 'closed') : ?>
                                                <button type="button" class="ad-delete-btn" data-ad-id="<?php echo $ad_id; ?>"
                                                    title="删除记录"><i class="fa fa-trash"></i> 删除</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else : ?>
                            <div class="uc-empty">
                                <i class="fa fa-bullhorn"></i>
                                <p>暂无广告</p>
                            </div>
                        <?php endif;
                        wp_reset_postdata(); ?>

                        <!-- 广告详情弹窗 -->
                        <div class="onedown-pay-modal" id="ad-detail-modal" aria-hidden="true">
                            <div class="onedown-pay-mask"></div>
                            <div class="onedown-pay-dialog" role="dialog" style="max-width:480px;">
                                <button class="onedown-pay-close" type="button" aria-label="关闭" data-ad-detail-close><i
                                        class="fa fa-times"></i></button>
                                <div class="onedown-pay-dialog-head">
                                    <span class="onedown-pay-dialog-icon"><i class="fa fa-bullhorn"></i></span>
                                    <div>
                                        <span class="onedown-pay-dialog-kicker">AD DETAIL</span>
                                        <h2>广告详情</h2>
                                    </div>
                                </div>
                                <div class="onedown-pay-dialog-body">
                                    <div style="display:grid;gap:12px;padding:4px 0;">
                                        <div class="order-detail-row">
                                            <span class="order-detail-label">广告文字</span>
                                            <strong class="order-detail-value" id="ad-detail-title"></strong>
                                        </div>
                                        <div class="order-detail-row">
                                            <span class="order-detail-label">目标链接</span>
                                            <span class="order-detail-value" id="ad-detail-url"></span>
                                        </div>
                                        <div class="order-detail-row">
                                            <span class="order-detail-label">金额</span>
                                            <strong class="order-detail-value" id="ad-detail-price"
                                                style="color:var(--od-primary);"></strong>
                                        </div>
                                        <div class="order-detail-row">
                                            <span class="order-detail-label">联系方式</span>
                                            <span class="order-detail-value" id="ad-detail-contact"></span>
                                        </div>
                                        <div class="order-detail-row">
                                            <span class="order-detail-label">到期时间</span>
                                            <span class="order-detail-value" id="ad-detail-expire"></span>
                                        </div>
                                        <div class="order-detail-row">
                                            <span class="order-detail-label">订单编号</span>
                                            <span class="order-detail-value" id="ad-detail-order-id"></span>
                                        </div>
                                        <div class="order-detail-row">
                                            <span class="order-detail-label">状态</span>
                                            <span class="order-detail-value" id="ad-detail-status"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="onedown-pay-dialog-actions">
                                    <button class="onedown-pay-secondary" type="button" data-ad-detail-close>关闭</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 支付弹窗（VIP风格） -->
                    <div class="vip-pay-modal" id="ad-pay-modal" aria-hidden="true">
                        <div class="vip-pay-mask"></div>
                        <div class="vip-pay-dialog" role="dialog" style="max-width:480px;">
                            <button class="vip-pay-close" type="button" aria-label="关闭" data-ad-pay-close><i
                                    class="fa fa-times"></i></button>
                            <div class="vip-pay-head">
                                <span class="vip-pay-icon"><i class="fa fa-bullhorn"></i></span>
                                <div>
                                    <span class="vip-pay-kicker">AD PAYMENT</span>
                                    <h2>广告投放支付</h2>
                                    <p>请确认订单信息并选择支付方式完成支付。</p>
                                </div>
                            </div>
                            <div class="vip-pay-body">
                                <!-- 订单信息 -->
                                <div class="vip-order-box" data-ad-pay-info>
                                    <div><span>订单金额</span><strong class="pay-amount" id="ad-pay-amount"></strong></div>
                                    <div><span>账户余额</span><strong id="ad-balance-display">￥0.00</strong></div>
                                </div>
                                <!-- 支付方式 -->
                                <h3 style="margin:0 0 10px;color:#252c3a;font-size:16px;font-weight:800;">支付方式</h3>
                                <div class="vip-method-options" id="ad-pay-methods"></div>

                                <!-- 状态提示 -->
                                <p class="vip-pay-status" id="ad-pay-status"></p>
                            </div>
                            <div class="vip-pay-actions">
                                <button class="vip-pay-secondary" type="button" data-ad-pay-close>取消</button>
                                <button id="ad-confirm-pay-btn" class="vip-pay-primary" type="button" disabled><i
                                        class="fa fa-check"></i>
                                    确认支付</button>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

            <?php elseif ('referral' === $tab) : ?>
                <div class="user-page-toolbar">
                    <h3 class="uc-page-title"><i class="fa fa-share-alt"></i> 推广中心</h3>
                </div>
                <?php if (!_pz('referral_enabled', false)) : ?>
                    <div class="uc-empty">
                        <i class="fa fa-share-alt"></i>
                        <p>推广功能未启用</p>
                    </div>
                <?php else : ?>
                    <?php echo onedown_referral_page_content($user_id); ?>
                <?php endif; ?>

            <?php else : ?>
                <!-- 概览 Dashboard -->
                <?php
                $comment_count  = get_comments(array('user_id' => $user_id, 'count' => true));
                $fav_count      = onedown_get_favorites_count($user_id);
                $download_count = onedown_get_download_count($user_id);
                $post_count     = count_user_posts($user_id);
                $downloads      = onedown_get_user_downloads($user_id);
                $recent_dls     = array_slice($downloads, 0, 3);
                $register_days  = max(1, floor((time() - strtotime($current_user->user_registered)) / DAY_IN_SECONDS));
                ?>
                <!-- 欢迎 + 统计 -->
                <div style="padding:16px 16px 0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div>
                            <h3 style="margin:0;font-size:16px;font-weight:800;color:#252c3a;">
                                <i class="fa fa-dashboard" style="color:var(--od-primary);margin-right:6px;"></i>
                                欢迎回来，<?php echo esc_html($current_user->display_name); ?>
                            </h3>
                            <p style="margin:6px 0 0;font-size:13px;color:var(--od-muted);">注册 <?php echo $register_days; ?> 天 ·
                                共发布 <?php echo $post_count; ?> 篇文章</p>
                        </div>
                        <span
                            style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:999px;background:rgba(240,68,148,.09);color:var(--od-primary);font-size:13px;font-weight:800;">
                            <i class="fa fa-diamond"></i> <?php echo esc_html($vip_info['vip_name']); ?>
                        </span>
                    </div>
                </div>

                <!-- VIP 状态 -->
                <?php if ($vip_info['is_vip']) : ?>
                    <div
                        style="display:flex;align-items:center;gap:12px;margin:16px 16px 0;padding:12px 16px;border-radius:10px;background:linear-gradient(135deg,rgba(240,68,148,.06),rgba(255,126,178,.06));border:1px solid rgba(240,68,148,.12);">
                        <span
                            style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:10px;background:var(--od-gradient);color:#fff;font-size:16px;flex-shrink:0;"><i
                                class="fa fa-diamond"></i></span>
                        <div style="flex:1;min-width:0;">
                            <strong style="font-size:14px;color:#252c3a;"><?php echo esc_html($vip_info['vip_name']); ?></strong>
                            <p style="margin:2px 0 0;font-size:12px;color:var(--od-muted);">
                                到期时间：<?php echo $vip_info['expire_date'] ? esc_html($vip_info['expire_date']) : '永久有效'; ?></p>
                        </div>
                        <a href="<?php echo esc_url(add_query_arg('tab', 'vip', $page_url)); ?>"
                            style="font-size:12px;font-weight:800;color:var(--od-primary);white-space:nowrap;">管理 <i
                                class="fa fa-angle-right"></i></a>
                    </div>
                <?php endif; ?>

                <!-- 余额信息 -->
                <?php
                $dashboard_balance = floatval(get_user_meta($user_id, 'onedown_balance', true));
                $dashboard_withdrawable = function_exists('onedown_referral_get_withdrawable') ? onedown_referral_get_withdrawable($user_id) : 0;
                $dashboard_balance_enabled = (bool) _pz('pay_balance_enabled', false);
                ?>
                <?php if ($dashboard_balance_enabled || $dashboard_withdrawable > 0) : ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:16px 16px 0;">
                        <div style="padding:12px 16px;background:var(--od-card);border:1px solid var(--od-line);border-radius:10px;">
                            <div style="font-size:11px;color:var(--od-muted);margin-bottom:4px;"><i class="fa fa-wallet"></i> 账户余额
                            </div>
                            <strong
                                style="font-size:18px;color:var(--od-primary);">￥<?php echo number_format($dashboard_balance, 2); ?></strong>
                            <?php if ($dashboard_balance_enabled) : ?>
                                <button type="button" class="pill-btn"
                                    style="float:right;padding:2px 12px;font-size:11px;border:1px solid var(--od-primary);color:var(--od-primary);background:transparent;border-radius:999px;cursor:pointer;"
                                    onclick="showRechargeModal()">
                                    <i class="fa fa-plus"></i> 充值
                                </button>
                            <?php endif; ?>
                        </div>
                        <div style="padding:12px 16px;background:var(--od-card);border:1px solid var(--od-line);border-radius:10px;">
                            <div style="font-size:11px;color:var(--od-muted);margin-bottom:4px;"><i class="fa fa-money"></i> 可提现佣金
                            </div>
                            <strong
                                style="font-size:18px;color:#f0a030;">￥<?php echo number_format($dashboard_withdrawable, 2); ?></strong>
                            <?php if ($dashboard_withdrawable > 0) : ?>
                                <button type="button" class="pill-btn"
                                    style="float:right;padding:2px 12px;font-size:11px;border:1px solid #f0a030;color:#f0a030;background:transparent;border-radius:999px;cursor:pointer;"
                                    onclick="transferToBalance()">
                                    <i class="fa fa-exchange"></i> 转余额
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 最近订单 -->
                <div class="user-page-toolbar">
                    <h3 class="uc-page-title"><i class="fa fa-list-alt"></i> 最近订单</h3>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'orders', $page_url)); ?>">全部 <i
                            class="fa fa-angle-right"></i></a>
                </div>
                <div class="order-list uc-list">
                    <?php
                    $recent_orders = array_slice($orders, 0, 3);
                    if (!empty($recent_orders)) :
                        foreach ($recent_orders as $order_id => $order) :
                            $order_type_class = $order->order_type === ONEDOWN_ORDER_TYPE_VIP ? 'order-icon--vip' : ($order->order_type === ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE ? 'order-icon--recharge' : 'order-icon--post');
                            $order_icon = $order->order_type === ONEDOWN_ORDER_TYPE_VIP ? 'fa-diamond' : ($order->order_type === ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE ? 'fa-money' : 'fa-file-text-o');
                            $status_label = onedown_order_status_name($order->status);
                            $order_time = $order->status === ONEDOWN_ORDER_STATUS_PAID && !empty($order->pay_time) && $order->pay_time !== '0000-00-00 00:00:00' ? date_i18n('Y-m-d H:i', strtotime($order->pay_time)) : date_i18n('Y-m-d', strtotime($order->create_time));
                    ?>
                            <div class="order-item" data-status="<?php echo esc_attr($order->status); ?>">
                                <span class="order-icon <?php echo esc_attr($order_type_class); ?>"><i
                                        class="fa <?php echo esc_attr($order_icon); ?>"></i></span>
                                <div class="order-info">
                                    <strong class="order-title"><?php echo esc_html($order->order_title); ?></strong>
                                    <span class="order-meta">
                                        <span class="order-meta-id">#<?php echo esc_html($order->order_id); ?></span>
                                        <span class="order-meta-divider">|</span>
                                        <span class="order-meta-time"><?php echo esc_html($order_time); ?></span>
                                        <span class="order-meta-divider">|</span>
                                        <span
                                            class="order-meta-amount">￥<?php echo esc_html(number_format(floatval($order->order_price), 2)); ?></span>
                                    </span>
                                </div>
                                <div class="order-actions">
                                    <em
                                        class="order-status order-status--<?php echo esc_attr($order->status); ?>"><?php echo esc_html($status_label); ?></em>
                                    <button type="button" class="order-detail-btn"
                                        data-order-id="<?php echo esc_attr($order->order_id); ?>">详情</button>
                                    <?php if ($order->status === ONEDOWN_ORDER_STATUS_PENDING) : ?>
                                        <button type="button" class="order-repay-btn"
                                            data-order-id="<?php echo esc_attr($order->order_id); ?>">继续支付</button>
                                        <button type="button" class="order-cancel-btn"
                                            data-order-id="<?php echo esc_attr($order->order_id); ?>">取消</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach;
                    else : ?>
                        <div class="uc-empty">
                            <i class="fa fa-inbox"></i>
                            <p>暂无订单</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 最近下载 -->
                <div class="user-page-toolbar">
                    <h3 class="uc-page-title"><i class="fa fa-download"></i> 最近下载</h3>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'downloads', $page_url)); ?>"
                        style="font-weight:800;color:var(--od-primary);font-size:12px;">全部 <i class="fa fa-angle-right"></i></a>
                </div>
                <div class="download-list" style="padding-top:0;">
                    <?php if (!empty($recent_dls)) : ?>
                        <?php foreach ($recent_dls as $dl) : ?>
                            <div class="download-item">
                                <span class="order-icon" style="background:linear-gradient(135deg,var(--od-green),#65dba3);"><i
                                        class="fa fa-file-archive-o"></i></span>
                                <div>
                                    <strong><?php echo esc_html($dl['title']); ?></strong>
                                    <span><?php echo esc_html($dl['time']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="uc-empty">
                            <i class="fa fa-download"></i>
                            <p>暂无下载记录</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
<?php
    }
endif;

/**
 * AJAX 投稿文章
 */
add_action('wp_ajax_onedown_submit_post', 'onedown_ajax_submit_post');

if (! function_exists('onedown_ajax_submit_post')) :
    function onedown_ajax_submit_post()
    {
        $user_id = get_current_user_id();
        if (! $user_id) {
            wp_send_json_error(array('msg' => '请先登录'));
        }

        // 验证 Nonce
        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'onedown_submit_post_action')) {
            wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
        }

        // 检查权限
        if (! current_user_can('publish_posts') && ! current_user_can('manage_options')) {
            wp_send_json_error(array('msg' => '暂无发布权限'));
        }

        $title   = isset($_POST['post_title']) ? wp_strip_all_tags(trim($_POST['post_title'])) : '';
        $content = isset($_POST['post_content']) ? trim($_POST['post_content']) : '';
        $cat     = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $tags    = isset($_POST['tags']) ? trim($_POST['tags']) : '';

        if (empty($title)) {
            wp_send_json_error(array('msg' => '请填写文章标题'));
        }
        if (mb_strlen($title) > 50) {
            wp_send_json_error(array('msg' => '标题太长，不能超过50个字'));
        }
        if (mb_strlen($title) < 5) {
            wp_send_json_error(array('msg' => '标题太短，至少5个字'));
        }
        if (empty($content)) {
            wp_send_json_error(array('msg' => '请填写文章内容'));
        }
        if (empty($cat)) {
            wp_send_json_error(array('msg' => '请选择文章分类'));
        }

        // 处理标签
        $tags_arr = array();
        if (! empty($tags)) {
            $tags_arr = preg_split('/,|，|\s|\n/', $tags);
            $tags_arr = array_filter(array_map('trim', $tags_arr));
        }

        $postarr = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'pending',
            'post_author'   => $user_id,
            'post_category' => array($cat),
            'tags_input'    => $tags_arr,
        );

        $post_id = wp_insert_post($postarr, true);

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('msg' => $post_id->get_error_message()));
        }
        if (! $post_id) {
            wp_send_json_error(array('msg' => '文章保存失败，请稍后再试'));
        }

        wp_send_json_success(array('msg' => '文章已提交，等待管理员审核'));
    }
endif;
