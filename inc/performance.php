<?php

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('onedown_frontend_performance_enabled')) :
    function onedown_frontend_performance_enabled()
    {
        return (bool) _pz('frontend_performance_optimize', true);
    }
endif;

if (! function_exists('onedown_optimize_wp_head')) :
    function onedown_optimize_wp_head()
    {
        if (! onedown_frontend_performance_enabled()) {
            return;
        }

        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        remove_action('wp_head', 'wp_oembed_add_host_js');
    }
    add_action('init', 'onedown_optimize_wp_head');
endif;

if (! function_exists('onedown_optimize_frontend_assets')) :
    function onedown_optimize_frontend_assets()
    {
        if (! onedown_frontend_performance_enabled()) {
            return;
        }

        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('classic-theme-styles');

        if (! is_user_logged_in()) {
            wp_dequeue_style('dashicons');
        }

        wp_dequeue_script('wp-embed');
        wp_deregister_script('wp-embed');
    }
    add_action('wp_enqueue_scripts', 'onedown_optimize_frontend_assets', 100);
endif;

if (! function_exists('onedown_add_script_defer_attr')) :
    function onedown_add_script_defer_attr($tag, $handle)
    {
        if (! onedown_frontend_performance_enabled()) {
            return $tag;
        }

        $defer_handles = array(
            'onedown-main',
            'onedown-swiper',
            'onedown-lazysizes',
            'onedown-qrcode-lib',
            'onedown-captcha',
        );

        if (in_array($handle, $defer_handles, true) && strpos($tag, ' defer') === false) {
            $tag = str_replace(' src=', ' defer src=', $tag);
        }

        return $tag;
    }
    add_filter('script_loader_tag', 'onedown_add_script_defer_attr', 10, 2);
endif;

if (! function_exists('onedown_optimize_image_attrs')) :
    function onedown_optimize_image_attrs($attr)
    {
        if (! onedown_frontend_performance_enabled()) {
            return $attr;
        }

        if (empty($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }
        if (empty($attr['decoding'])) {
            $attr['decoding'] = 'async';
        }

        return $attr;
    }
    add_filter('wp_get_attachment_image_attributes', 'onedown_optimize_image_attrs');
endif;
