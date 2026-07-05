<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 主题引导类
 *
 * 集中管理模块文件的加载与加载顺序，替代 functions.php 中分散的 require 语句。
 * 采用单例模式，确保只引导一次。
 */
final class Onedown_Theme
{
    private static ?self $instance = null;

    /**
     * 立即加载的模块（严格按依赖顺序）。
     *
     * - user-vip 依赖 user-functions
     * - user-referral 依赖 user-vip
     * - pay-order / pay-gateway / pay-notify 为支付系统核心
     */
    private const MODULES = [
        'inc/setup.php',
        'inc/post-features.php',
        'inc/performance.php',
        'inc/enqueue.php',
        'inc/template-tags.php',
        'inc/seo.php',
        'inc/captcha.php',
        'inc/comment-spam.php',
        'inc/user-functions.php',
        'inc/user-ajax.php',
        'inc/user-vip.php',
        'inc/user-referral.php',
        'inc/admin.php',
        'inc/mail.php',
        'inc/contact.php',
        'inc/widgets/widgets-init.php',
        'inc/float-buttons.php',
        'inc/shortcodes.php',
        'inc/download.php',
        'inc/download-redirect.php',
        'inc/ai-generator.php',
        'inc/wechat.php',
        'inc/class-license-activator.php',
        'inc/pay-metabox.php',
        'inc/pay-front.php',
        'inc/pay-order.php',
        'inc/ad-system.php',
        'inc/pay-gateway.php',
        'inc/pay-notify.php',
        'inc/theme-updater.php',
        'inc/commission-records.php',
        'inc/theme-homogenization.php',
        'inc/seo-push.php',
        'inc/topic.php',
    ];

    private function __construct() {}

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 引导主题：注册延迟加载钩子并加载核心模块。
     */
    public function boot(): void
    {
        // 加载主题语言文件（WP 6.7+ 要求在 init hook 上加载）
        add_action('init', static function () {
            load_theme_textdomain(ONEDOWN_TEXT_DOMAIN, get_template_directory() . '/languages');
        }, 1);

        // 加载 Codestar Framework — 必须优先加载
        onedown_require_file('inc/codestar-framework/codestar-framework.php');

        // 加载主题设置（延迟到 init 确保翻译就绪）
        add_action('init', static function () {
            onedown_require_file('inc/theme-options.php');
        }, 5);

        $this->load_modules();
    }

    private function load_modules(): void
    {
        foreach (self::MODULES as $module) {
            onedown_require_file($module);
        }
    }
}
