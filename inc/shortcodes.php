<?php

/**
 * Onedown 内容控制短代码
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================
 *  辅助函数：判断当前用户是否已评论当前文章
 * ============================================================
 */
if (!function_exists('onedown_user_is_commented')):
    function onedown_user_is_commented($user_id = 0, $post_id = 0)
    {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        $where = '';
        if ($user_id) {
            $where = "`user_id` = {$user_id}";
        } elseif (!empty($_COOKIE['comment_author_email_' . COOKIEHASH])) {
            $email = str_replace('%40', '@', $_COOKIE['comment_author_email_' . COOKIEHASH]);
            $where = "`comment_author_email` = '{$email}'";
        } else {
            return false;
        }

        global $wpdb;
        $query = "SELECT `comment_ID` FROM {$wpdb->comments}
                  WHERE `comment_post_ID` = {$post_id}
                    AND `comment_approved` = '1'
                    AND {$where}
                  LIMIT 1";
        return $wpdb->get_var($query);
    }
endif;

/**
 * ============================================================
 *  [reply] — 评论可见
 *  用法：[reply]隐藏内容[/reply]
 * ============================================================
 */
if (!function_exists('onedown_shortcode_reply')):
    function onedown_shortcode_reply($atts, $content = null)
    {
        if (is_null($content)) {
            return '';
        }

        // 管理员/作者/已评论用户直接可见
        if (is_super_admin() || onedown_user_is_commented()) {
            return do_shortcode($content);
        }

        $post = get_post(get_the_ID());
        if ($post && get_current_user_id() === (int) $post->post_author) {
            return do_shortcode($content);
        }

        return '<div class="onedown-hidden-box" data-type="reply">'
            . '<a class="onedown-hidden-text" href="javascript:;" data-scroll-comment>'
            . '<i class="fa fa-exclamation-circle"></i> 此处内容已隐藏，请发表评论'
            . '</a>'
            . '</div>';
    }
endif;
add_shortcode('reply', 'onedown_shortcode_reply');

/**
 * ============================================================
 *  [hidecontent] — 多功能隐藏内容
 *
 *  用法：
 *    [hidecontent type="reply"]...[/hidecontent]    评论可见
 *    [hidecontent type="logged"]...[/hidecontent]   登录可见
 *    [hidecontent type="vip"]...[/hidecontent]       VIP 可见
 *    [hidecontent type="password" password="123"]...[/hidecontent]  密码验证
 * ============================================================
 */
if (!function_exists('onedown_shortcode_hidecontent')):
    function onedown_shortcode_hidecontent($atts, $content = null)
    {
        if (is_null($content)) {
            return '';
        }

        extract(shortcode_atts(array(
            'type'     => 'reply',
            'password' => '',
            'desc'     => '',
            'qrcode'   => '',
            'keyword'  => '',
        ), $atts));

        $content   = do_shortcode($content);
        $user_id   = get_current_user_id();

        $post = get_post(get_the_ID());

        switch ($type) {
            case 'reply':
                // 管理员/作者/已评论用户直接可见
                if (is_super_admin() || ($post && $user_id && $user_id === (int) $post->post_author) || onedown_user_is_commented()) {
                    return $content;
                }
                return '<div class="onedown-hidden-box" data-type="reply">'
                    . '<a class="onedown-hidden-text" href="javascript:;" data-scroll-comment>'
                    . '<i class="fa fa-exclamation-circle"></i> 此处内容已隐藏，请发表评论'
                    . '</a>'
                    . '</div>';

            case 'logged':
                // 管理员/作者直接可见
                if (is_super_admin() || ($post && $user_id && $user_id === (int) $post->post_author) || $user_id > 0) {
                    return $content;
                }
                return '<div class="onedown-hidden-box" data-type="logged">'
                    . '<a class="onedown-hidden-text" href="' . esc_url(wp_login_url(get_permalink())) . '">'
                    . '<i class="fa fa-exclamation-circle"></i> 隐藏内容，请登录后查看'
                    . '</a>'
                    . '</div>';

            case 'vip':
                // 管理员/作者直接可见
                if (is_super_admin() || ($post && $user_id && $user_id === (int) $post->post_author)) {
                    return $content;
                }
                $vip_info = function_exists('onedown_get_user_vip_info') ? onedown_get_user_vip_info($user_id) : array('is_vip' => false);
                if ($user_id > 0 && !empty($vip_info['is_vip'])) {
                    return $content;
                }
                if ($user_id > 0 && empty($vip_info['is_vip'])) {
                    $vip_url = function_exists('onedown_user_center_url') ? onedown_user_center_url(array('tab' => 'vip')) : home_url('/user-center/?tab=vip');
                    return '<div class="onedown-hidden-box" data-type="vip">'
                        . '<a class="onedown-hidden-text" href="' . esc_url($vip_url) . '">'
                        . '<i class="fa fa-exclamation-circle"></i> 此处内容已隐藏，仅限会员查看<br>'
                        . '<i class="fa fa-diamond"></i> 请开通会员后查看'
                        . '</a>'
                        . '</div>';
                }
                return '<div class="onedown-hidden-box" data-type="vip">'
                    . '<a class="onedown-hidden-text" href="' . esc_url(wp_login_url(get_permalink())) . '">'
                    . '<i class="fa fa-exclamation-circle"></i> 此处内容已隐藏，仅限会员查看<br>'
                    . '<i class="fa fa-sign-in"></i> 请登录后查看特权'
                    . '</a>'
                    . '</div>';

            case 'wechat':
                $wx_keyword  = !empty($keyword) ? $keyword : '验证码';
                $wx_qrcode   = !empty($qrcode) ? $qrcode : '';
                if (empty($wx_qrcode)) {
                    // 优先使用微信工具中的公众号二维码，其次使用客服二维码
                    $wx_qrcode_raw = function_exists('_pz') ? _pz('wechat_qrcode') : '';
                    if (is_array($wx_qrcode_raw)) {
                        $wx_qrcode = $wx_qrcode_raw['url'] ?? '';
                    } else {
                        $wx_qrcode = $wx_qrcode_raw;
                    }
                }
                if (empty($wx_qrcode)) {
                    $wx_img_raw = function_exists('_pz') ? _pz('wechat_img') : '';
                    $wx_qrcode  = is_array($wx_img_raw) ? ($wx_img_raw['url'] ?? '') : $wx_img_raw;
                }

                $input_pw  = !empty($_POST['onedown_hide_pw']) ? $_POST['onedown_hide_pw'] : '';
                $pw_token  = !empty($wx_keyword) ? wp_hash($wx_keyword . '|wechat|' . $post->ID) : '';
                $cookie_key = 'odwc_' . $post->ID . '_' . substr(md5($wx_keyword), 0, 10);
                $cookie_verified = !empty($_COOKIE[$cookie_key]);

                if (($input_pw && $input_pw === $wx_keyword) || $cookie_verified) {
                    if ($input_pw && $input_pw === $wx_keyword && !$cookie_verified) {
                        setcookie($cookie_key, '1', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
                    }
                    return $content;
                }

                $html = '<div class="onedown-hidden-box onedown-wechat-box" data-type="wechat" data-pw-token="' . esc_attr($pw_token) . '" data-post-id="' . intval($post->ID) . '">';
                $html .= '<div class="onedown-wechat-inner">';
                if ($wx_qrcode) {
                    $html .= '<div class="onedown-wechat-qrcode"><img src="' . esc_url($wx_qrcode) . '" alt="公众号二维码"></div>';
                } else {
                    $html .= '<div class="onedown-wechat-qrcode onedown-wechat-qrcode-placeholder"><i class="fa fa-wechat"></i></div>';
                }
                $html .= '<div class="onedown-wechat-info">';
                $html .= '<div class="onedown-wechat-title"><i class="fa fa-wechat"></i> 关注公众号获取验证码</div>';
                $html .= '<div class="onedown-wechat-steps">';
                $html .= '<span>1. 扫码关注公众号</span>';
                $html .= '<span>2. 发送关键词 <strong>' . esc_html($wx_keyword) . '</strong></span>';
                $html .= '<span>3. 输入验证码解锁内容</span>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="onedown-wechat-error" style="display:none;">验证码错误，请重新输入</div>';
                $html .= '<div class="onedown-wechat-form">';
                $html .= '<input type="text" class="onedown-wechat-input" placeholder="请输入验证码">';
                $html .= '<button type="button" class="onedown-wechat-submit" data-pw-submit>解锁</button>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                return $html;

            case 'password':
                $input_pw = !empty($_POST['onedown_hide_pw']) ? $_POST['onedown_hide_pw'] : '';
                $pw_token = !empty($password) ? wp_hash($password . '|' . $post->ID) : '';
                $cookie_key = 'odpw_' . $post->ID . '_' . substr(md5($password), 0, 10);
                $cookie_verified = !empty($_COOKIE[$cookie_key]);

                // POST 验证或 Cookie 验证
                if (($input_pw && $input_pw === $password) || $cookie_verified) {
                    // 如果是 POST 验证成功，设置 Cookie
                    if ($input_pw && $input_pw === $password && !$cookie_verified) {
                        setcookie($cookie_key, '1', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
                    }
                    return $content;
                }

                $html = '<div class="onedown-hidden-box onedown-password-box" data-type="password" data-pw-token="' . esc_attr($pw_token) . '" data-post-id="' . intval($post->ID) . '">';
                $html .= '<div class="onedown-password-inner">';
                $html .= '<div class="onedown-password-icon"><i class="fa fa-lock"></i></div>';
                $html .= '<div class="onedown-password-title">隐藏内容，输入密码后查看</div>';
                if ($desc) {
                    $html .= '<div class="onedown-password-desc">' . esc_html($desc) . '</div>';
                }
                $html .= '<div class="onedown-password-error" style="display:none;">密码错误，请重新输入</div>';
                $html .= '<div class="onedown-password-form">';
                $html .= '<input type="text" class="onedown-password-input" placeholder="请输入密码">';
                $html .= '<button type="button" class="onedown-password-submit" data-pw-submit>提交</button>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                return $html;
        }

        return $content;
    }
endif;
add_shortcode('hidecontent', 'onedown_shortcode_hidecontent');

/**
 * ============================================================
 *  AJAX：验证短代码密码（无刷新)
 * ============================================================
 */
add_action('wp_ajax_onedown_verify_password', 'onedown_ajax_verify_password');
add_action('wp_ajax_nopriv_onedown_verify_password', 'onedown_ajax_verify_password');
function onedown_ajax_verify_password()
{
    $post_id  = intval($_POST['post_id'] ?? 0);
    $password = stripslashes($_POST['password'] ?? '');
    $token    = $_POST['token'] ?? '';

    if (!$post_id || !$password || !$token) {
        wp_send_json_error(array('msg' => '参数不完整'));
    }

    $expected1 = wp_hash($password . '|' . $post_id);
    $expected2 = wp_hash($password . '|wechat|' . $post_id);
    if (!hash_equals($expected1, $token) && !hash_equals($expected2, $token)) {
        wp_send_json_error(array('msg' => '验证码错误'));
    }

    // 设置 Cookie 持久化
    $cookie_key = 'odpw_' . $post_id . '_' . substr(md5($password), 0, 10);
    setcookie($cookie_key, '1', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);

    wp_send_json_success(array('msg' => '验证成功'));
}
