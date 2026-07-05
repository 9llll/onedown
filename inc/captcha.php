<?php

/**
 * Onedown 图形验证码
 *
 * 基于 GD 库生成图形验证码，支持直接 URL 输出和 AJAX 获取
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 注册 captcha_img 查询变量
 */
add_filter('query_vars', function ($vars) {
    $vars[] = 'onedown_captcha';
    return $vars;
});

/**
 * 生成唯一 Token
 */
if (!function_exists('onedown_captcha_token')):
    function onedown_captcha_token($id = 'default') {
        return md5(uniqid('od_captcha_' . $id . '_', true) . wp_rand(1000, 9999) . microtime(true));
    }
endif;

/**
 * 直接输出验证码图片
 */
if (!function_exists('onedown_captcha_output_image')):
    function onedown_captcha_output_image($captcha_id = 'default')
    {
        $captcha_id = sanitize_key($captcha_id);
        if ($captcha_id === '') {
            $captcha_id = 'default';
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow', true);

        $charset  = 'abcdefghjklmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $codelen  = 4;
        $width    = 100;
        $height   = 40;
        $fontsize = 20;

        $code = '';
        $_leng = strlen($charset) - 1;
        for ($i = 0; $i < $codelen; $i++) {
            $code .= $charset[mt_rand(0, $_leng)];
        }

        $token = onedown_captcha_token($captcha_id);
        set_transient('od_cc_' . $captcha_id . '_' . $token, strtolower($code), 300);

        if (!empty($_COOKIE['od_cc_' . $captcha_id])) {
            $old_token = sanitize_key($_COOKIE['od_cc_' . $captcha_id]);
            delete_transient('od_cc_' . $captcha_id . '_' . $old_token);
        }

        $cookie_path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
        setcookie('od_cc_' . $captcha_id, $token, time() + 300, $cookie_path, COOKIE_DOMAIN, is_ssl(), true);

        if (!function_exists('imagecreatetruecolor')) {
            header('Content-Type: image/svg+xml; charset=utf-8');
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="40" viewBox="0 0 100 40">';
            echo '<rect width="100" height="40" fill="#f5f7fb"/>';
            echo '<text x="50" y="27" text-anchor="middle" font-family="Arial,sans-serif" font-size="22" font-weight="700" fill="#2d374c" letter-spacing="3">' . esc_html($code) . '</text>';
            echo '</svg>';
            exit;
        }

        header('Content-Type: image/png');
        $img = imagecreatetruecolor($width, $height);

        $bg = imagecolorallocate($img, mt_rand(235, 255), mt_rand(235, 255), mt_rand(235, 255));
        imagefill($img, 0, 0, $bg);

        for ($i = 0; $i < 6; $i++) {
            $line_color = imagecolorallocate($img, mt_rand(150, 220), mt_rand(150, 220), mt_rand(150, 220));
            imageline($img, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $line_color);
        }

        for ($i = 0; $i < 50; $i++) {
            $point_color = imagecolorallocate($img, mt_rand(120, 200), mt_rand(120, 200), mt_rand(120, 200));
            imagesetpixel($img, mt_rand(0, $width), mt_rand(0, $height), $point_color);
        }

        $font_files = array(
            getenv('SystemRoot') . '/Fonts/arial.ttf',
            getenv('SystemRoot') . '/Fonts/verdana.ttf',
            getenv('SystemRoot') . '/Fonts/segoeui.ttf',
            ABSPATH . WPINC . '/fonts/tahoma.ttf',
        );
        $font = '';
        foreach ($font_files as $f) {
            if ($f && file_exists($f)) {
                $font = $f;
                break;
            }
        }

        $code_len = strlen($code);
        $_x = ($width - 10) / $code_len;
        for ($i = 0; $i < $code_len; $i++) {
            $char_color = imagecolorallocate($img, mt_rand(30, 80), mt_rand(30, 80), mt_rand(30, 80));
            $angle = mt_rand(-30, 30);
            $x = (int) (5 + $i * $_x + mt_rand(0, 3));
            $y = mt_rand($height - 15, $height - 8);

            if ($font && function_exists('imagettftext')) {
                imagettftext($img, $fontsize, $angle, $x, $y, $char_color, $font, $code[$i]);
            } else {
                imagestring($img, 5, $x, mt_rand(10, 25), $code[$i], $char_color);
            }
        }

        imagepng($img);
        imagedestroy($img);
        exit;
    }
endif;

add_action('wp_ajax_onedown_captcha_image', 'onedown_ajax_captcha_image');
add_action('wp_ajax_nopriv_onedown_captcha_image', 'onedown_ajax_captcha_image');
if (!function_exists('onedown_ajax_captcha_image')):
    function onedown_ajax_captcha_image()
    {
        $id = !empty($_GET['id']) ? sanitize_key(wp_unslash($_GET['id'])) : 'default';
        onedown_captcha_output_image($id);
    }
endif;

/**
 * template_redirect 拦截验证码图片请求，直接输出 PNG 图片
 */
add_action('template_redirect', function () {
    $captcha_id = get_query_var('onedown_captcha');
    if (!$captcha_id) {
        return;
    }

    onedown_captcha_output_image($captcha_id);
});

/**
 * 获取验证码图片 URL
 */
if (!function_exists('onedown_captcha_url')):
    function onedown_captcha_url($id = 'default')
    {
        return add_query_arg(array(
            'action' => 'onedown_captcha_image',
            'id'     => sanitize_key($id),
            't'      => time(),
        ), admin_url('admin-ajax.php'));
    }
endif;

/**
 * 验证验证码
 */
if (!function_exists('onedown_captcha_verify')):
    function onedown_captcha_verify($id = 'default', $code = '')
    {
        if (empty($code) || strlen($code) < 4) {
            return false;
        }

        // 从 Cookie 获取 Token
        $cookie_key = 'od_cc_' . $id;
        if (empty($_COOKIE[$cookie_key])) {
            return false;
        }

        $token = sanitize_key($_COOKIE[$cookie_key]);
        $transient_key = 'od_cc_' . $id . '_' . $token;
        $stored_code = get_transient($transient_key);

        if (empty($stored_code)) {
            return false;
        }

        $result = strtolower(trim($code)) === $stored_code;

        // 验证后清除（一次性）
        if ($result) {
            delete_transient($transient_key);
        }

        return $result;
    }
endif;

/**
 * AJAX 刷新验证码 - 返回新图片 URL
 */
add_action('wp_ajax_onedown_captcha_refresh', 'onedown_ajax_captcha_refresh');
add_action('wp_ajax_nopriv_onedown_captcha_refresh', 'onedown_ajax_captcha_refresh');
if (!function_exists('onedown_ajax_captcha_refresh')):
    function onedown_ajax_captcha_refresh()
    {
        $id = !empty($_REQUEST['id']) ? sanitize_key($_REQUEST['id']) : 'default';
        // 访问一次 URL 就会生成新图片并更新 Cookie/Transient
        $url = onedown_captcha_url($id);
        wp_send_json_success(array('url' => $url));
    }
endif;

/**
 * 获取验证码输入框 HTML（带直接图片 URL）
 */
if (!function_exists('onedown_captcha_input')):
    function onedown_captcha_input($id = 'default', $placeholder = '图形验证码')
    {
        $img_url = onedown_captcha_url($id);
        ob_start();
?>
<label>
    <span>验证码</span>
    <div class="captcha-row" data-captcha-id="<?php echo esc_attr($id); ?>">
        <div class="captcha-input-wrap">
            <div class="form-control-icon">
                <i class="fa fa-shield"></i>
                <input type="text" name="captcha_code" class="captcha-input"
                    placeholder="<?php echo esc_attr($placeholder); ?>" autocomplete="off" required>
            </div>
        </div>
        <div class="captcha-img-wrap">
            <img class="captcha-img" src="<?php echo esc_url($img_url); ?>" alt="验证码" title="点击刷新">
        </div>
    </div>
</label>
<?php
        return ob_get_clean();
    }
endif;

/**
 * 检查验证码开关
 */
if (!function_exists('onedown_captcha_enabled')):
    function onedown_captcha_enabled($scene = 'login')
    {
        // GD 库不可用时禁用验证码
        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }

        $option_map = array(
            'login'    => 'captcha_login',
            'register' => 'captcha_register',
            'comment'  => 'captcha_comment',
            'resetpwd' => 'captcha_resetpwd',
        );
        $key = isset($option_map[$scene]) ? $option_map[$scene] : 'captcha_login';
        return (bool) _pz($key, true);
    }
endif;

/**
 * 评论表单验证码
 */
add_filter('comment_form_submit_field', function ($submit_field, $args) {
    if (onedown_captcha_enabled('comment') && is_user_logged_in()) {
        $captcha = onedown_captcha_input('comment', '图形验证码');
        $submit_field = $captcha . $submit_field;
    }
    return $submit_field;
}, 10, 2);

/**
 * 输出验证码 CSS
 */
add_action('wp_head', function () {
    if (wp_doing_ajax()) {
        return;
    }
    ?>
<style>
label:has(.captcha-row) {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}

label:has(.captcha-row) > span {
    flex-shrink: 0;
    white-space: nowrap;
    font-size: 14px;
    color: var(--od-text, #333);
}

.captcha-row {
    display: flex;
    flex: 1;
    flex-wrap: nowrap;
    align-items: stretch;
    gap: 8px;
    margin-bottom: 0;
    width: auto;
    min-width: 0;
}

.captcha-row .captcha-input-wrap {
    flex: 1;
    min-width: 0;
}

.captcha-row .captcha-input-wrap .form-control-icon {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}

.captcha-row .captcha-input-wrap .form-control-icon i {
    position: absolute;
    left: 12px;
    color: var(--od-muted, #999);
    font-size: 14px;
    z-index: 1;
    pointer-events: none;
}

.captcha-row .captcha-input-wrap .form-control-icon input {
    flex: 1;
    padding: 8px 12px 8px 32px;
    border-radius: 6px;
    font-size: 14px;
    background: var(--od-input-bg, #fff);
    color: var(--od-text, #333);
    border: none;
    transition: border-color 0.2s;
    box-sizing: border-box;
    height: 36px;
}

.captcha-row .captcha-input-wrap .form-control-icon input:focus {
    border-color: var(--od-primary, #f04494);
}

.captcha-row .captcha-img-wrap {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

.captcha-row .captcha-img-wrap .captcha-img {
    width: 100px;
    height: 36px;
    border-radius: 6px;
    cursor: pointer;
    border: 0px solid var(--od-border, #e2e8f0);
    background: #f5f5f5;
    object-fit: cover;
    display: block;
}

@media (max-width: 767px) {
    label:has(.captcha-row) {
        gap: 6px;
    }

    .captcha-row {
        gap: 6px;
    }

    .captcha-row .captcha-input-wrap .form-control-icon input {
        font-size: 15px;
        padding: 8px 10px 8px 30px;
    }

    .captcha-row .captcha-img-wrap .captcha-img {
        width: 88px;
        height: 36px;
    }
}
        @media (max-width: 480px) {
            label:has(.captcha-row) {
                gap: 4px;
            }

            .captcha-row {
                gap: 4px;
            }

            .captcha-row .captcha-input-wrap .form-control-icon input {
                font-size: 16px;
            }

            .captcha-row .captcha-img-wrap .captcha-img {
                width: 84px;
            }
        }
    </style>
<?php
});
