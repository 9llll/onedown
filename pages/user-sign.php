<?php

/**
 * Template Name: 登录/注册/找回密码
 * Description: 全屏独立登录页面
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

$allowed_tabs = array('signin', 'signup', 'resetpassword');
$current_tab  = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'signin';

if (! in_array($current_tab, $allowed_tabs, true)) {
    $current_tab = 'signin';
}

if ($current_tab === 'signup' && function_exists('onedown_is_close_signup') && onedown_is_close_signup()) {
    $current_tab = 'signin';
}

if (is_user_logged_in()) {
    wp_safe_redirect(function_exists('onedown_user_center_url') ? onedown_user_center_url() : home_url('/user-center/'));
    exit;
}

get_header();
?>
<main class="sign-page-main">
    <div class="sign-page-container">
        <div class="sign-card">
            <div class="sign-card-header">
                <?php echo function_exists('onedown_get_sign_logo') ? onedown_get_sign_logo() : ''; ?>
            </div>

            <div class="sign-tabs">
                <button class="sign-tab<?php echo $current_tab === 'signin' ? ' is-active' : ''; ?>" type="button"
                    data-sign-page-tab="signin">登录</button>
                <?php if (! function_exists('onedown_is_close_signup') || ! onedown_is_close_signup()) : ?>
                <button class="sign-tab<?php echo $current_tab === 'signup' ? ' is-active' : ''; ?>" type="button"
                    data-sign-page-tab="signup">注册</button>
                <?php endif; ?>
                <button class="sign-tab<?php echo $current_tab === 'resetpassword' ? ' is-active' : ''; ?>"
                    type="button" data-sign-page-tab="resetpassword">找回密码</button>
            </div>

            <div class="sign-card-body">
                <div class="sign-panel" <?php echo $current_tab !== 'signin' ? ' style="display:none;"' : ''; ?>
                    data-sign-page-panel="signin">
                    <?php echo function_exists('onedown_signin_form') ? onedown_signin_form() : ''; ?>
                </div>

                <?php if (! function_exists('onedown_is_close_signup') || ! onedown_is_close_signup()) : ?>
                <div class="sign-panel" <?php echo $current_tab !== 'signup' ? ' style="display:none;"' : ''; ?>
                    data-sign-page-panel="signup">
                    <div class="sign-panel-header"><i class="fa fa-user-plus"></i><span>注册账号</span></div>
                    <?php echo function_exists('onedown_signup_form') ? onedown_signup_form() : ''; ?>
                </div>
                <?php endif; ?>

                <div class="sign-panel" <?php echo $current_tab !== 'resetpassword' ? ' style="display:none;"' : ''; ?>
                    data-sign-page-panel="resetpassword">
                    <?php echo function_exists('onedown_resetpassword_form') ? onedown_resetpassword_form() : ''; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<script>
document.addEventListener('click', function(event) {
    var tab = event.target.closest('[data-sign-page-tab]');
    if (!tab) {
        return;
    }

    var name = tab.getAttribute('data-sign-page-tab');
    var tabs = document.querySelectorAll('[data-sign-page-tab]');
    var panels = document.querySelectorAll('[data-sign-page-panel]');

    tabs.forEach(function(item) {
        item.classList.toggle('is-active', item === tab);
    });

    panels.forEach(function(panel) {
        panel.style.display = panel.getAttribute('data-sign-page-panel') === name ? '' : 'none';
    });

    if (window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', name);
        window.history.replaceState({}, '', url.toString());
    }
});
</script>
<?php
get_footer();