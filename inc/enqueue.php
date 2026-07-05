<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('onedown_adjust_color_lightness')) :
    /**
     * 调整 HEX 颜色明度，生成渐变副色
     *
     * @param string $hex    形如 #f04494 的颜色
     * @param int    $percent 正数变亮，负数变暗（-100 ~ 100）
     * @return string
     */
    function onedown_adjust_color_lightness($hex, $percent)
    {
        $hex = ltrim((string) $hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '#' . $hex;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $adjust = function ($channel) use ($percent) {
            if ($percent >= 0) {
                $channel = $channel + (255 - $channel) * ($percent / 100);
            } else {
                $channel = $channel * (1 + $percent / 100);
            }
            return max(0, min(255, (int) round($channel)));
        };

        return sprintf('#%02x%02x%02x', $adjust($r), $adjust($g), $adjust($b));
    }
endif;

if (! function_exists('onedown_hex_to_rgb_value')) :
    /**
     * 将 HEX 颜色转换为 CSS rgb 变量值
     *
     * @param string $hex 形如 #f04494 的颜色
     * @return string
     */
    function onedown_hex_to_rgb_value($hex)
    {
        $hex = ltrim((string) $hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return '240, 68, 148';
        }

        return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
    }
endif;

if (! function_exists('onedown_build_theme_color_vars')) :
    /**
     * 根据主题主色及渐变配置生成 CSS 变量字符串
     *
     * @param string $primary 主色
     * @return string
     */
    function onedown_build_theme_color_vars($primary)
    {
        $primary = $primary ? $primary : '#f04494';
        $vars    = array();

        if ($primary !== '#f04494') {
            $vars[] = '--od-primary:' . $primary;
            $vars[] = '--od-primary-rgb:' . onedown_hex_to_rgb_value($primary);
        }

        $gradient_enable = (bool) _pz('theme_gradient_enable', true);

        if ($gradient_enable) {
            $color2 = _pz('theme_gradient_color2', '');
            if (empty($color2)) {
                // 自动根据主色生成更亮的副色
                $color2 = onedown_adjust_color_lightness($primary, 28);
            }
            $vars[] = '--od-primary-2:' . $color2;
            $vars[] = '--od-gradient:linear-gradient(135deg, ' . $primary . ', ' . $color2 . ')';
        } else {
            // 纯色模式：渐变退化为主色
            $vars[] = '--od-primary-2:' . $primary;
            $vars[] = '--od-gradient:' . $primary;
        }

        return esc_attr(implode(';', $vars));
    }
endif;

if (! function_exists('onedown_enqueue_assets')) :
    function onedown_enqueue_assets()
    {
        $theme_version = function_exists('onedown_asset_cache_version') ? onedown_asset_cache_version() : wp_get_theme()->get('Version');
        $theme_uri     = get_template_directory_uri();

        $main_deps = array('onedown-bootstrap', 'onedown-fontawesome');

        wp_enqueue_style('onedown-bootstrap', $theme_uri . '/assets/css/bootstrap.min.css', array(), $theme_version);
        wp_enqueue_style('onedown-fontawesome', $theme_uri . '/assets/css/font-awesome.min.css', array(), $theme_version);

        wp_enqueue_style('onedown-main', $theme_uri . '/assets/css/main.css', $main_deps, $theme_version);

        // Swiper 仅注册，由含轮播的组件按需入队（见 onedown_enqueue_swiper）
        wp_register_style('onedown-swiper', $theme_uri . '/assets/css/swiper.min.css', array(), $theme_version);
        wp_register_script('onedown-swiper', $theme_uri . '/assets/js/swiper.min.js', array(), $theme_version, true);

        $performance_enabled = function_exists('onedown_frontend_performance_enabled') && onedown_frontend_performance_enabled();

        if (! $performance_enabled || (function_exists('onedown_is_lazyload_enabled') && onedown_is_lazyload_enabled())) {
            wp_enqueue_script('onedown-lazysizes', $theme_uri . '/assets/js/lazysizes.min.js', array(), $theme_version, true);
        }
        wp_register_script('onedown-qrcode-lib', $theme_uri . '/assets/js/qrcode-lib.min.js', array(), $theme_version, true);

        // 判断当前页面是否需要 qrcode-lib
        $needs_qrcode = false;
        if (is_singular() && function_exists('onedown_post_has_pay') && onedown_post_has_pay()) {
            $needs_qrcode = true;
        }
        if (is_page() && 'page-templates/download.php' === get_page_template_slug()) {
            $needs_qrcode = true;
        }
        if (is_singular()) {
            $needs_qrcode = true;
        }
        if ($needs_qrcode) {
            wp_enqueue_script('onedown-qrcode-lib');
        }

        wp_enqueue_script('onedown-main', $theme_uri . '/assets/js/main.js', array(), $theme_version, true);

        $needs_captcha_script = false;
        if (function_exists('onedown_captcha_enabled')) {
            $needs_captcha_script = (
                onedown_captcha_enabled('login') ||
                onedown_captcha_enabled('register') ||
                onedown_captcha_enabled('resetpwd') ||
                (is_singular() && comments_open() && onedown_captcha_enabled('comment'))
            );
        }
        if (! $performance_enabled || $needs_captcha_script) {
            wp_enqueue_script('onedown-captcha', $theme_uri . '/assets/js/captcha.js', array(), $theme_version, true);
        }

        // 输出自定义主题色 / 渐变色
        $primary_color = _pz('theme_primary_color', '#f04494');
        $css_vars = onedown_build_theme_color_vars($primary_color);
        if ($css_vars !== '') {
            wp_add_inline_style('onedown-main', ':root{' . $css_vars . '}');
        }
        if (_pz('use_hbd_font', true)) {
            $hbd_font_css = '@font-face{'
                . 'font-family:"HBD";'
                . 'src:url("' . esc_url($theme_uri . '/assets/fonts/hbd.woff2') . '") format("woff2");'
                . 'font-weight:400 900;'
                . 'font-style:normal;'
                . 'font-display:swap;'
                . '}'
                . 'body{'
                . 'font-family:"HBD",-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Microsoft YaHei",Arial,sans-serif;'
                . '}';
            wp_add_inline_style('onedown-main', $hbd_font_css);
        }

        // 向 JS 传递数据
        $agreement_page_id = _pz('user_agreement_page');
        $pay_methods = array();
        if (function_exists('onedown_get_available_pay_methods')) {
            $pay_methods = onedown_get_available_pay_methods();
        }
        $logo_data = _pz('logo');
        $logo_url = $logo_data && !empty($logo_data['url']) ? $logo_data['url'] : '';
        $logo_width = intval(_pz('logo_width', 150));

        wp_localize_script('onedown-main', 'onedownData', array(
            'userCenterUrl'      => onedown_user_center_url(),
            'submitPostUrl'      => function_exists('onedown_submit_post_url') ? onedown_submit_post_url() : home_url('/submit-post/'),
            'signUrl'            => onedown_get_sign_url(),
            'logoutUrl'          => wp_logout_url(home_url('/')),
            'ajaxUrl'            => admin_url('admin-ajax.php'),
            'signinNonce'        => wp_create_nonce('onedown_signin_action'),
            'signupNonce'        => wp_create_nonce('onedown_signup_action'),
            'resetNonce'         => wp_create_nonce('onedown_resetpassword_action'),
            'vipNonce'           => wp_create_nonce('onedown_vip_order_action'),
            'favoriteNonce'      => wp_create_nonce('onedown_favorite_action'),
            'likeNonce'          => wp_create_nonce('onedown_like_action'),
            'downloadNonce'      => wp_create_nonce('onedown_download_action'),
            'tabNonce'           => wp_create_nonce('onedown_tab_action'),
            'referralNonce'      => wp_create_nonce('onedown_referral_action'),
            'payNonce'           => wp_create_nonce('onedown_pay_order_action'),
            'commentNonce'       => wp_create_nonce('onedown_comment_action'),
            'assetVersion'       => $theme_version,
            'currentUrl'         => esc_url_raw((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']),
            'vipPlans'           => onedown_vip_levels(),
            'currentVipPlanId'   => is_user_logged_in() ? (onedown_get_user_vip_info(get_current_user_id())['plan_id'] ?? '') : '',
            'vipPrices'          => onedown_vip_plan_price(),
            'vipUpgradePrices'   => is_user_logged_in() && function_exists('onedown_get_user_vip_info') && !empty(onedown_get_user_vip_info(get_current_user_id())['is_vip'])
                ? onedown_get_user_vip_upgrade_prices(get_current_user_id())
                : array(),
            'isLoggedIn'         => is_user_logged_in(),
            'agreementUrl'       => $agreement_page_id ? get_permalink($agreement_page_id) : '',
            'payMethods'         => $pay_methods,
            'guestPurchase'      => (bool)_pz('guest_purchase_enabled', false),
            'balanceEnabled'     => (bool)_pz('pay_balance_enabled', false),
            'siteName'           => get_bloginfo('name'),
            'siteLogo'           => $logo_url,
            'siteLogoWidth'      => $logo_width,
            'homeUrl'            => home_url('/'),
            'disableDevtools'    => (bool) _pz('security_disable_devtools', false),
            'captchaLogin'       => onedown_captcha_enabled('login'),
            'captchaRegister'    => onedown_captcha_enabled('register'),
            'captchaLoginHtml'   => onedown_captcha_enabled('login') ? onedown_captcha_input('signin') : '',
            'captchaRegisterHtml' => onedown_captcha_enabled('register') ? onedown_captcha_input('signup') : '',
            // 用户数据（移动端侧边栏用）
            'userDisplayName'    => is_user_logged_in() ? wp_get_current_user()->display_name : '',
            'userAvatar'         => is_user_logged_in() ? get_avatar_url(get_current_user_id(), array('size' => 48)) : '',
            'vipName'            => is_user_logged_in() ? (onedown_get_user_vip_info(get_current_user_id())['vip_name'] ?? '') : '',
            'vipExpireDate'      => is_user_logged_in() ? (onedown_get_user_vip_info(get_current_user_id())['expire_date'] ?? '') : '',
            'vipClass'           => is_user_logged_in() ? (onedown_get_user_vip_info(get_current_user_id())['vip_class'] ?? '') : '',
            'canPublishPosts'    => is_user_logged_in() ? current_user_can('publish_posts') : false,
            'canManageOptions'   => is_user_logged_in() ? current_user_can('manage_options') : false,
        ));

        wp_add_inline_script('onedown-main', "document.getElementById('menuBtn')?.addEventListener('click',function(){document.getElementById('mainNav')?.classList.toggle('is-open');});");

        if (is_singular() && comments_open() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }
    }
    add_action('wp_enqueue_scripts', 'onedown_enqueue_assets');
endif;

/**
 * 按需入队 Swiper（仅在含轮播的页面/组件中调用）
 */
if (! function_exists('onedown_enqueue_swiper')) :
    function onedown_enqueue_swiper()
    {
        wp_enqueue_style('onedown-swiper');
        wp_enqueue_script('onedown-swiper');

        // 确保 swiper.js 在 main.js 之前加载（defer 按 DOM 顺序执行）
        $scripts = wp_scripts();
        $main    = $scripts->query('onedown-main', 'registered');
        if ($main && ! in_array('onedown-swiper', $main->deps, true)) {
            $main->deps[] = 'onedown-swiper';
        }
    }
endif;

/**
 * 加载后台主题样式
 */
if (! function_exists('onedown_enqueue_admin_assets')) :
    function onedown_enqueue_admin_assets($hook)
    {
        $theme_version = wp_get_theme()->get('Version');
        $theme_uri     = get_template_directory_uri();

        // 仅在有主题样式需求的页面加载（CSF 配置页、文章编辑、小工具、导航菜单、用户资料、评论）
        $needs_admin_theme = (
            strpos($hook, 'onedown-options') !== false ||
            $hook === 'post.php' ||
            $hook === 'post-new.php' ||
            $hook === 'widgets.php' ||
            $hook === 'customize.php' ||
            $hook === 'nav-menus.php' ||
            $hook === 'profile.php' ||
            $hook === 'user-edit.php' ||
            $hook === 'comment.php' ||
            $hook === 'edit-comments.php'
        );

        if (!$needs_admin_theme) {
            return;
        }

        // 内联关键 CSS — 避免首屏白闪（FOUC），等完整 CSS 加载后接管
        $critical_css = ':root{--bg-dark:#1a1a2e;--bg-sidebar:#16162a;--bg-content:#f8f6f3;--gold-1:#F7DC6F;--gold-2:#E8C84A;--gold-3:#D4A843}.csf-theme-dark{background:var(--bg-dark)}.csf-theme-dark .csf-nav-background{background:var(--bg-sidebar)}.csf-theme-dark .csf-content{background:var(--bg-content)}.onedown-field-hidden{display:none!important}';
        wp_add_inline_style('admin-bar', $critical_css);

        $admin_theme_css = get_template_directory() . '/assets/css/admin-theme.min.css';
        $admin_theme_ver = file_exists($admin_theme_css) ? filemtime($admin_theme_css) : $theme_version;
        wp_enqueue_style('onedown-admin-theme', $theme_uri . '/assets/css/admin-theme.min.css', array(), $admin_theme_ver);
        wp_enqueue_style('onedown-fontawesome', $theme_uri . '/assets/css/font-awesome.min.css', array(), $theme_version);

        // 主题设置页面：配色方案选择联动
        if (strpos($hook, 'onedown-options') !== false) {
            wp_add_inline_script('jquery-core', '
            jQuery(document).ready(function($) {
                function onedownRpMd5cycle(x, k) {
                    var a = x[0], b = x[1], c = x[2], d = x[3];
                    a = onedownRpFf(a, b, c, d, k[0], 7, -680876936);
                    d = onedownRpFf(d, a, b, c, k[1], 12, -389564586);
                    c = onedownRpFf(c, d, a, b, k[2], 17, 606105819);
                    b = onedownRpFf(b, c, d, a, k[3], 22, -1044525330);
                    a = onedownRpFf(a, b, c, d, k[4], 7, -176418897);
                    d = onedownRpFf(d, a, b, c, k[5], 12, 1200080426);
                    c = onedownRpFf(c, d, a, b, k[6], 17, -1473231341);
                    b = onedownRpFf(b, c, d, a, k[7], 22, -45705983);
                    a = onedownRpFf(a, b, c, d, k[8], 7, 1770035416);
                    d = onedownRpFf(d, a, b, c, k[9], 12, -1958414417);
                    c = onedownRpFf(c, d, a, b, k[10], 17, -42063);
                    b = onedownRpFf(b, c, d, a, k[11], 22, -1990404162);
                    a = onedownRpFf(a, b, c, d, k[12], 7, 1804603682);
                    d = onedownRpFf(d, a, b, c, k[13], 12, -40341101);
                    c = onedownRpFf(c, d, a, b, k[14], 17, -1502002290);
                    b = onedownRpFf(b, c, d, a, k[15], 22, 1236535329);
                    a = onedownRpGg(a, b, c, d, k[1], 5, -165796510);
                    d = onedownRpGg(d, a, b, c, k[6], 9, -1069501632);
                    c = onedownRpGg(c, d, a, b, k[11], 14, 643717713);
                    b = onedownRpGg(b, c, d, a, k[0], 20, -373897302);
                    a = onedownRpGg(a, b, c, d, k[5], 5, -701558691);
                    d = onedownRpGg(d, a, b, c, k[10], 9, 38016083);
                    c = onedownRpGg(c, d, a, b, k[15], 14, -660478335);
                    b = onedownRpGg(b, c, d, a, k[4], 20, -405537848);
                    a = onedownRpGg(a, b, c, d, k[9], 5, 568446438);
                    d = onedownRpGg(d, a, b, c, k[14], 9, -1019803690);
                    c = onedownRpGg(c, d, a, b, k[3], 14, -187363961);
                    b = onedownRpGg(b, c, d, a, k[8], 20, 1163531501);
                    a = onedownRpGg(a, b, c, d, k[13], 5, -1444681467);
                    d = onedownRpGg(d, a, b, c, k[2], 9, -51403784);
                    c = onedownRpGg(c, d, a, b, k[7], 14, 1735328473);
                    b = onedownRpGg(b, c, d, a, k[12], 20, -1926607734);
                    a = onedownRpHh(a, b, c, d, k[5], 4, -378558);
                    d = onedownRpHh(d, a, b, c, k[8], 11, -2022574463);
                    c = onedownRpHh(c, d, a, b, k[11], 16, 1839030562);
                    b = onedownRpHh(b, c, d, a, k[14], 23, -35309556);
                    a = onedownRpHh(a, b, c, d, k[1], 4, -1530992060);
                    d = onedownRpHh(d, a, b, c, k[4], 11, 1272893353);
                    c = onedownRpHh(c, d, a, b, k[7], 16, -155497632);
                    b = onedownRpHh(b, c, d, a, k[10], 23, -1094730640);
                    a = onedownRpHh(a, b, c, d, k[13], 4, 681279174);
                    d = onedownRpHh(d, a, b, c, k[0], 11, -358537222);
                    c = onedownRpHh(c, d, a, b, k[3], 16, -722521979);
                    b = onedownRpHh(b, c, d, a, k[6], 23, 76029189);
                    a = onedownRpHh(a, b, c, d, k[9], 4, -640364487);
                    d = onedownRpHh(d, a, b, c, k[12], 11, -421815835);
                    c = onedownRpHh(c, d, a, b, k[15], 16, 530742520);
                    b = onedownRpHh(b, c, d, a, k[2], 23, -995338651);
                    a = onedownRpIi(a, b, c, d, k[0], 6, -198630844);
                    d = onedownRpIi(d, a, b, c, k[7], 10, 1126891415);
                    c = onedownRpIi(c, d, a, b, k[14], 15, -1416354905);
                    b = onedownRpIi(b, c, d, a, k[5], 21, -57434055);
                    a = onedownRpIi(a, b, c, d, k[12], 6, 1700485571);
                    d = onedownRpIi(d, a, b, c, k[3], 10, -1894986606);
                    c = onedownRpIi(c, d, a, b, k[10], 15, -1051523);
                    b = onedownRpIi(b, c, d, a, k[1], 21, -2054922799);
                    a = onedownRpIi(a, b, c, d, k[8], 6, 1873313359);
                    d = onedownRpIi(d, a, b, c, k[15], 10, -30611744);
                    c = onedownRpIi(c, d, a, b, k[6], 15, -1560198380);
                    b = onedownRpIi(b, c, d, a, k[13], 21, 1309151649);
                    a = onedownRpIi(a, b, c, d, k[4], 6, -145523070);
                    d = onedownRpIi(d, a, b, c, k[11], 10, -1120210379);
                    c = onedownRpIi(c, d, a, b, k[2], 15, 718787259);
                    b = onedownRpIi(b, c, d, a, k[9], 21, -343485551);
                    x[0] = onedownRpAdd32(a, x[0]);
                    x[1] = onedownRpAdd32(b, x[1]);
                    x[2] = onedownRpAdd32(c, x[2]);
                    x[3] = onedownRpAdd32(d, x[3]);
                }
                function onedownRpCmn(q, a, b, x, s, t) { a = onedownRpAdd32(onedownRpAdd32(a, q), onedownRpAdd32(x, t)); return onedownRpAdd32((a << s) | (a >>> (32 - s)), b); }
                function onedownRpFf(a, b, c, d, x, s, t) { return onedownRpCmn((b & c) | ((~b) & d), a, b, x, s, t); }
                function onedownRpGg(a, b, c, d, x, s, t) { return onedownRpCmn((b & d) | (c & (~d)), a, b, x, s, t); }
                function onedownRpHh(a, b, c, d, x, s, t) { return onedownRpCmn(b ^ c ^ d, a, b, x, s, t); }
                function onedownRpIi(a, b, c, d, x, s, t) { return onedownRpCmn(c ^ (b | (~d)), a, b, x, s, t); }
                function onedownRpMd51(s) {
                    var n = s.length;
                    var state = [1732584193, -271733879, -1732584194, 271733878];
                    var i;
                    for (i = 64; i <= n; i += 64) {
                        onedownRpMd5cycle(state, onedownRpMd5blk(s.substring(i - 64, i)));
                    }
                    s = s.substring(i - 64);
                    var tail = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
                    for (i = 0; i < s.length; i += 1) {
                        tail[i >> 2] |= s.charCodeAt(i) << ((i % 4) << 3);
                    }
                    tail[i >> 2] |= 0x80 << ((i % 4) << 3);
                    if (i > 55) {
                        onedownRpMd5cycle(state, tail);
                        for (i = 0; i < 16; i += 1) {
                            tail[i] = 0;
                        }
                    }
                    tail[14] = n * 8;
                    onedownRpMd5cycle(state, tail);
                    return state;
                }
                function onedownRpMd5blk(s) {
                    var md5blks = [], i;
                    for (i = 0; i < 64; i += 4) {
                        md5blks[i >> 2] = s.charCodeAt(i) + (s.charCodeAt(i + 1) << 8) + (s.charCodeAt(i + 2) << 16) + (s.charCodeAt(i + 3) << 24);
                    }
                    return md5blks;
                }
                function onedownRpRhex(n) {
                    var s = "", j;
                    for (j = 0; j < 4; j += 1) {
                        s += ((n >> (j * 8 + 4)) & 0x0F).toString(16) + ((n >> (j * 8)) & 0x0F).toString(16);
                    }
                    return s;
                }
                function onedownRpHex(x) {
                    for (var i = 0; i < x.length; i += 1) {
                        x[i] = onedownRpRhex(x[i]);
                    }
                    return x.join("");
                }
                function onedownRpMd5(s) { return onedownRpHex(onedownRpMd51(s)); }
                function onedownRpAdd32(a, b) { return (a + b) & 0xFFFFFFFF; }

                function onedownRefreshRemotePublishDoc() {
                    var $doc = $(".onedown-rp-doc");
                    var $apiNode = $doc.find(".onedown-rp-api-url");
                    var $normalTipNode = $doc.find(".onedown-rp-normal-tip");
                    var $keyInput = $("input[name=\"_onedown_options[remote_pub_apikey]\"]");
                    var $typeInput = $("select[name=\"_onedown_options[remote_pub_apitype]\"]");

                    if (!$doc.length || !$apiNode.length || !$normalTipNode.length || !$keyInput.length || !$typeInput.length) {
                        return;
                    }

                    var baseUrl = $doc.data("base-url") || "";
                    var apikey = $keyInput.val() || "";
                    var apitype = $typeInput.val() || "normal";
                    var apiUrl = baseUrl;

                    if (apitype !== "safe" && apikey) {
                        apiUrl += "&apikey=" + onedownRpMd5(apikey);
                        $normalTipNode.html("密钥已拼接到上方接口地址（<code>&apikey=md5(密钥)</code>），可直接调用。");
                    } else {
                        $normalTipNode.html("请求时在 URL 后追加 <code>&apikey=md5(密钥)</code>");
                    }

                    $apiNode.text(apiUrl);
                }

                // ── 主题主色同步函数 ──
                function onedownGetColorInput() {
                    return $("input[name=\'_onedown_options[theme_primary_color]\']");
                }

                // 根据指定颜色高亮对应的色块
                function onedownSyncPalette(color) {
                    if (!color) return;
                    var normalized = color.toLowerCase();
                    $(".csf--palettes .csf--palette").each(function() {
                        var $palette = $(this);
                        var paletteColor = ($palette.data("color") || "").toLowerCase();
                        var inputVal = ($palette.find("input").val() || "").toLowerCase();
                        if (paletteColor === normalized || inputVal === normalized) {
                            $palette.addClass("csf--active").siblings().removeClass("csf--active");
                            return false;
                        }
                    });
                }

                // 色块点击 → 同步到颜色选择器
                $(document).on("click", ".csf--palettes .csf--palette", function(e) {
                    // 防止点击内部的 radio/input 重复触发
                    if ($(e.target).is("input")) return;
                    var $input = $(this).find("input[type=\'radio\']");
                    if ($input.length) {
                        $input.prop("checked", true).trigger("change");
                    }
                });
                $(document).on("change", "input[name=\'_onedown_options[theme_color_presets]\']", function() {
                    var color = $(this).val();
                    $(this).closest(".csf--palettes").find(".csf--palette").removeClass("csf--active");
                    $(this).closest(".csf--palette").addClass("csf--active");
                    var $colorInput = onedownGetColorInput();
                    $colorInput.val(color).trigger("change");
                    if ($colorInput.closest(".wp-picker-container").length) {
                        $colorInput.wpColorPicker("color", color);
                    }
                });

                // 颜色选择器变化 → 同步回色块
                $(document).on("change input", "input[name=\'_onedown_options[theme_primary_color]\']", function() {
                    var color = $(this).val();
                    if (color) onedownSyncPalette(color);
                });

                // ── 添加"默认"重置按钮 ──
                function onedownInitColorDefaultBtn() {
                    var $colorInput = onedownGetColorInput();
                    if (!$colorInput.length) return;

                    var $container = $colorInput.closest(".csf-fieldset");
                    if ($container.find(".onedown-color-default-btn").length) return;

                    var defaultColor = "#f04494";
                    var $btn = $("<a>", {
                        href: "#",
                        class: "button button-small onedown-color-default-btn",
                        text: "默认",
                        title: "恢复为默认颜色 " + defaultColor,
                        click: function(e) {
                            e.preventDefault();
                            $colorInput.val(defaultColor).trigger("change");
                            if ($colorInput.closest(".wp-picker-container").length) {
                                $colorInput.wpColorPicker("color", defaultColor);
                            }
                            // 同步高亮默认色色块
                            onedownSyncPalette(defaultColor);
                        }
                    });
                    $container.append($btn);
                }

                // ── 页面加载初始化 ──
                onedownInitColorDefaultBtn();
                // 加载完成后根据当前颜色值同步色块高亮
                setTimeout(function() {
                    var saved = onedownGetColorInput().val();
                    if (saved) onedownSyncPalette(saved);
                }, 200);

                // 切换选项卡时重新初始化
                $(document).on("click", ".csf-nav a", function() {
                    setTimeout(onedownInitColorDefaultBtn, 100);
                    setTimeout(function() {
                        var saved = onedownGetColorInput().val();
                        if (saved) onedownSyncPalette(saved);
                    }, 300);
                });

                $(document).on("input change", "input[name=\'_onedown_options[remote_pub_apikey]\']", onedownRefreshRemotePublishDoc);
                $(document).on("change", "select[name=\'_onedown_options[remote_pub_apitype]\']", onedownRefreshRemotePublishDoc);
                $(document).on("click", ".onedown-rp-refresh-btn", function(e) {
                    e.preventDefault();
                    onedownRefreshRemotePublishDoc();
                });

                onedownRefreshRemotePublishDoc();

                // ── 悬浮按钮类型切换：显示/隐藏对应字段 ──
                function onedownToggleFloatBtnFields(item) {
                    var $item = $(item);
                    var type = $item.find(\'select[data-depend-id="type"]\').val();
                    // 隐藏所有类型专属字段
                    $item.find(\'.float-btn-svc\').closest(\'.csf-field\').hide();
                    // 显示当前类型对应的字段
                    if (type) {
                        $item.find(\'.float-btn-svc.\' + type).closest(\'.csf-field\').show();
                    }
                }

                // 初始化所有已有的按钮项
                $(\'.csf-field-group .csf-cloneable-item\').each(function() {
                    onedownToggleFloatBtnFields(this);
                });

                // 监听类型切换
                $(document).on(\'change\', \'.csf-cloneable-item select[data-depend-id="type"]\', function() {
                    onedownToggleFloatBtnFields($(this).closest(\'.csf-cloneable-item\'));
                });

                // 监听新增克隆项（CSF 动态添加后触发）
                $(document).on(\'csf-field-group-cloned\', function(e, $cloned) {
                    onedownToggleFloatBtnFields($cloned);
                });
                // 备用：MutationObserver 检测新增项
                var groupObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(m) {
                        m.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1 && $(node).hasClass(\'csf-cloneable-item\')) {
                                onedownToggleFloatBtnFields(node);
                            }
                        });
                    });
                });
                var groupWrap = document.querySelector(\'.csf-field-group\');
                if (groupWrap) {
                    groupObserver.observe(groupWrap, { childList: true, subtree: true });
                }

                // ── 侧边栏折叠/展开 ──
                var STORAGE_KEY = \'onedown_sidebar_folded\';
                var $headerInner = $(\'.csf-header-inner\');
                var $toggle = $(\'<span class="csf-sidebar-toggle" title="折叠侧边栏"><i class="fa fa-indent"></i></span>\');
                $headerInner.find(\'h1\').before($toggle);

                function applySidebarState(folded) {
                    $(\'.csf-wrapper\').toggleClass(\'csf-nav-folded\', folded);
                    $toggle.attr(\'title\', folded ? \'展开侧边栏\' : \'折叠侧边栏\');
                }

                // 恢复上次状态
                var saved = localStorage.getItem(STORAGE_KEY);
                if (saved === \'1\') {
                    applySidebarState(true);
                }

                $toggle.on(\'click\', function() {
                    var isFolded = $(\'.csf-wrapper\').hasClass(\'csf-nav-folded\');
                    var newState = !isFolded;
                    applySidebarState(newState);
                    localStorage.setItem(STORAGE_KEY, newState ? \'1\' : \'0\');
                });
            });
            ');
        }
    }
    add_action('admin_enqueue_scripts', 'onedown_enqueue_admin_assets');
endif;
