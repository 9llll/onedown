<?php

/**
 * Onedown 用户相关辅助函数
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 获取 Tab 标题
 */
if (!function_exists('onedown_get_tab_title')):
    function onedown_get_tab_title($tab)
    {
        $titles = array(
            'dashboard'   => '用户中心',
            'vip'         => '我的会员',
            'orders'      => '我的订单',
            'downloads'   => '下载记录',
            'favorites'   => '我的收藏',
            'comments'    => '我的评论',
            'profile'     => '账号设置',
            'password'    => '修改密码',
            'referral'    => '推广中心',
            'ad-apply'    => '广告申请',
        );
        return isset($titles[$tab]) ? $titles[$tab] : '用户中心';
    }
endif;

/**
 * 获取收藏数量
 */
if (!function_exists('onedown_get_favorites_count')):
    function onedown_get_favorites_count($user_id)
    {
        $favorites = get_user_meta($user_id, 'onedown_favorites', true);
        return is_array($favorites) ? count($favorites) : 0;
    }
endif;

/**
 * 获取下载记录
 */
if (!function_exists('onedown_get_user_downloads')):
    function onedown_get_user_downloads($user_id)
    {
        $downloads = get_user_meta($user_id, 'onedown_downloads', true);
        return is_array($downloads) ? array_reverse($downloads) : array();
    }
endif;

/**
 * 获取下载数量
 */
if (!function_exists('onedown_get_download_count')):
    function onedown_get_download_count($user_id)
    {
        $downloads = get_user_meta($user_id, 'onedown_downloads', true);
        return is_array($downloads) ? count($downloads) : 0;
    }
endif;

/**
 * 获取登录/注册页面 URL
 */
if (!function_exists('onedown_get_sign_url')):
    function onedown_get_sign_url($tab = 'signin')
    {
        $pages = function_exists('onedown_cached_pages_by_template')
            ? onedown_cached_pages_by_template('pages/user-sign.php')
            : get_pages(array(
                'meta_key'   => '_wp_page_template',
                'meta_value' => 'pages/user-sign.php',
                'number'     => 1,
            ));

        if (!empty($pages)) {
            $url = get_permalink($pages[0]->ID);
        } else {
            $url = add_query_arg('od_sign', 1, home_url('/'));
        }

        return add_query_arg('tab', $tab, $url);
    }
endif;

/**
 * 获取找回密码链接
 */
if (!function_exists('onedown_get_repas_link')):
    function onedown_get_repas_link($class = 'muted-color')
    {
        return '<a class="' . $class . '" href="' . esc_url(onedown_get_sign_url('resetpassword')) . '">找回密码</a>';
    }
endif;

/**
 * 获取登录表单 HTML
 */
if (!function_exists('onedown_signin_form')):
    function onedown_signin_form()
    {
        ob_start();
?>
<form id="onedown-signin-form" class="auth-form" method="post">
    <label>
        <span>账号</span>
        <div class="form-control-icon">
            <i class="fa fa-user-o"></i>
            <input type="text" name="username" placeholder="请输入用户名或邮箱" required>
        </div>
    </label>
    <label>
        <span>密码</span>
        <div class="form-control-icon">
            <i class="fa fa-lock"></i>
            <input type="password" name="password" placeholder="请输入登录密码" required autocomplete="current-password">
        </div>
    </label>
    <?php if (onedown_captcha_enabled('login')) : ?>
    <?php echo onedown_captcha_input('signin', '图形验证码'); ?>
    <?php endif; ?>
    <div class="auth-row">
        <label class="check-line">
            <input type="checkbox" name="remember" value="forever" checked>
            <span>记住登录</span>
        </label>
        <?php echo onedown_get_repas_link(); ?>
    </div>
    <input type="hidden" name="action" value="onedown_signin">
    <?php wp_nonce_field('onedown_signin_action', '_wpnonce'); ?>
    <button type="submit" class="auth-submit"><i class="fa fa-sign-in"></i> 登录</button>
</form>
<?php
        return ob_get_clean();
    }
endif;

/**
 * 获取注册表单 HTML
 */
if (!function_exists('onedown_signup_form')):
    function onedown_signup_form()
    {
        ob_start();
    ?>
<form id="onedown-signup-form" class="auth-form" method="post">
    <label>
        <span>用户名</span>
        <div class="form-control-icon">
            <i class="fa fa-user-o"></i>
            <input type="text" name="name" placeholder="请输入用户名" required minlength="3">
        </div>
    </label>
    <label>
        <span>邮箱</span>
        <div class="form-control-icon">
            <i class="fa fa-envelope-o"></i>
            <input type="email" name="email" placeholder="请输入邮箱地址" required>
        </div>
    </label>
    <label>
        <span>密码</span>
        <div class="form-control-icon">
            <i class="fa fa-lock"></i>
            <input type="password" name="password" placeholder="设置登录密码" required minlength="6"
                autocomplete="new-password">
        </div>
    </label>
    <label>
        <span>确认密码</span>
        <div class="form-control-icon">
            <i class="fa fa-shield"></i>
            <input type="password" name="repassword" placeholder="再次输入密码" required minlength="6"
                autocomplete="new-password">
        </div>
    </label>
    <?php if (onedown_captcha_enabled('register')) : ?>
    <?php echo onedown_captcha_input('signup', '图形验证码'); ?>
    <?php endif; ?>
    <?php if (_pz('user_agreement_page')) : ?>
    <label class="check-line">
        <input type="checkbox" name="agreement" checked>
        <span>已阅读并同意 <a href="<?php echo esc_url(get_permalink(_pz('user_agreement_page'))); ?>"
                target="_blank">用户协议</a></span>
    </label>
    <?php endif; ?>
    <input type="hidden" name="action" value="onedown_signup">
    <?php wp_nonce_field('onedown_signup_action', '_wpnonce'); ?>
    <button type="submit" class="auth-submit"><i class="fa fa-user-plus"></i> 注册</button>
</form>
<?php
        return ob_get_clean();
    }
endif;

/**
 * 获取找回密码表单 HTML
 */
if (!function_exists('onedown_resetpassword_form')):
    function onedown_resetpassword_form()
    {
        ob_start();
    ?>
<form id="onedown-resetpassword-form" class="auth-form" method="post">
    <label>
        <span>邮箱</span>
        <div class="form-control-icon">
            <i class="fa fa-envelope-o"></i>
            <input type="email" name="email" placeholder="请输入注册邮箱" required>
        </div>
    </label>
    <label>
        <span>新密码</span>
        <div class="form-control-icon">
            <i class="fa fa-lock"></i>
            <input type="password" name="password" placeholder="设置新密码" required minlength="6"
                autocomplete="new-password">
        </div>
    </label>
    <label>
        <span>确认新密码</span>
        <div class="form-control-icon">
            <i class="fa fa-shield"></i>
            <input type="password" name="repassword" placeholder="再次输入新密码" required minlength="6"
                autocomplete="new-password">
        </div>
    </label>
    <?php if (onedown_captcha_enabled('resetpwd')) : ?>
    <?php echo onedown_captcha_input('resetpwd', '图形验证码'); ?>
    <?php endif; ?>
    <input type="hidden" name="action" value="onedown_resetpassword">
    <?php wp_nonce_field('onedown_resetpassword_action', '_wpnonce'); ?>
    <button type="submit" class="auth-submit"><i class="fa fa-unlock-alt"></i> 重置密码</button>
</form>
<?php
        return ob_get_clean();
    }
endif;

/**
 * 获取登录页面 Logo
 */
if (!function_exists('onedown_get_sign_logo')):
    function onedown_get_sign_logo()
    {
        $logo = _pz('logo');
        if ($logo && !empty($logo['url'])) {
            return '<div class="sign-logo"><a href="' . esc_url(home_url('/')) . '"><img src="' . esc_url($logo['url']) . '" alt="' . esc_attr(get_bloginfo('name')) . '"></a></div>';
        }
        return '<div class="sign-logo"><a href="' . esc_url(home_url('/')) . '">' . get_bloginfo('name') . '</a></div>';
    }
endif;

/**
 * 判断是否关闭注册
 */
if (!function_exists('onedown_is_close_signup')):
    function onedown_is_close_signup()
    {
        if (_pz('close_signup')) {
            return true;
        }
        return apply_filters('onedown_close_signup', get_option('users_can_register') ? false : true);
    }
endif;

/**
 * 获取用户中心Tab内容（VIP等级页面）
 */
if (!function_exists('onedown_user_vip_tab_content')):
    function onedown_user_vip_tab_content($user_id)
    {
        $vip_info = onedown_get_user_vip_info($user_id);
        $levels   = onedown_vip_levels();
        ob_start();
    ?>

<!-- 会员状态 Hero -->
<div class="member-hero">
    <div class="member-hero-copy">
        <span class="member-kicker"><i class="fa fa-diamond"></i> VIP MEMBER</span>
        <h1><?php echo $vip_info['is_vip'] ? '会员已开通' : '开通会员，解锁全部权益'; ?></h1>
        <p><?php echo $vip_info['is_vip'] ? '当前会员：' . esc_html($vip_info['vip_name']) . '，享受专属资源与技术支持。' : '开通后即可下载会员资源、阅读付费内容并获得积分福利。'; ?>
        </p>
        <div class="member-hero-actions">
            <a class="primary" href="javascript:;" data-vip-modal><i class="fa fa-shopping-bag"></i> 选择套餐</a>
        </div>
    </div>
    <div class="member-account">
        <div class="member-avatar"><i class="fa fa-diamond"></i></div>
        <h2><?php echo esc_html($vip_info['vip_name']); ?></h2>
        <p>查看会员期限与下载权益</p>
        <div class="member-account-grid">
            <span><strong data-vip-status-text><?php echo $vip_info['is_vip'] ? '已开通' : '未开通'; ?></strong>会员状态</span>
            <span><strong
                    data-vip-expire-text><?php echo $vip_info['is_vip'] ? esc_html($vip_info['expire_date']) : '-'; ?></strong>到期时间</span>
        </div>
        <?php
                // 找出最高等级
                $highest_plan_id = '';
                $max_weight = 0;
                foreach ($levels as $id => $level) {
                    $weight = onedown_vip_level_weight($id);
                    if ($weight > $max_weight) {
                        $max_weight = $weight;
                        $highest_plan_id = $id;
                    }
                }
                $can_upgrade = $vip_info['is_vip'] && $vip_info['plan_id'] !== $highest_plan_id;
                ?>
        <?php if (!$vip_info['is_vip']) : ?>
        <a href="javascript:;" data-vip-modal>立即开通</a>
        <?php elseif ($can_upgrade) : ?>
        <a href="javascript:;" data-vip-modal data-vip-upgrade="true" class="member-upgrade-btn">升级会员 <i
                class="fa fa-angle-right"></i></a>
        <?php else : ?>
        <a href="#" style="background:rgba(255,255,255,.18);cursor:default;">已激活 <i class="fa fa-check-circle"></i></a>
        <?php endif; ?>
    </div>
</div>

<?php
        return ob_get_clean();
    }
endif;
