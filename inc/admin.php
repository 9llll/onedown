<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 根据主题设置选项执行安全与系统配置
 */
add_action( 'after_setup_theme', 'onedown_apply_theme_options' );
function onedown_apply_theme_options() {

    // 经典编辑器
    if ( _pz( 'classic_editor', true ) ) {
        add_filter( 'use_block_editor_for_post', '__return_false', 100 );
        add_filter( 'gutenberg_can_edit_post_type', '__return_false', 100 );
    }

    // 经典小工具
    if ( _pz( 'classic_widgets', true ) ) {
        remove_theme_support( 'widgets-block-editor' );
    }

    // 禁用 XML-RPC
    if ( _pz( 'security_disable_xmlrpc', false ) ) {
        add_filter( 'xmlrpc_enabled', '__return_false' );
    }

    // 禁用文件编辑
    if ( _pz( 'security_disable_file_edit', true ) ) {
        if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
            define( 'DISALLOW_FILE_EDIT', true );
        }
    }

    // 强制 HTTPS
    if ( _pz( 'security_https', false ) ) {
        if ( ! is_ssl() && ! is_admin() ) {
            $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            wp_redirect( $redirect_url, 301 );
            exit;
        }
    }

    // ========== WP 优化功能 ==========

    // 删除 Emoji 脚本
    if ( _pz( 'remove_emoji', false ) ) {
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    }

    // 删除 Google 字体
    if ( _pz( 'remove_open_sans', false ) ) {
        add_action( 'init', 'onedown_remove_open_sans' );
        function onedown_remove_open_sans() {
            wp_deregister_style( 'open-sans' );
            wp_register_style( 'open-sans', false );
            wp_enqueue_style( 'open-sans', '' );
        }
    }

    // 清理 wp_head 多余标签
    if ( _pz( 'remove_more_wp_head', false ) ) {
        remove_action( 'wp_head', 'feed_links_extra', 3 );
        remove_action( 'wp_head', 'feed_links', 2 );
        remove_action( 'wp_head', 'rsd_link' );
        remove_action( 'wp_head', 'wlwmanifest_link' );
        remove_action( 'wp_head', 'index_rel_link' );
        remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
        remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
        remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 );
        remove_action( 'wp_head', 'wp_generator' );
        remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
        remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
        remove_action( 'wp_head', 'rest_output_link_wp_head', 10, 0 );
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10, 1 );
    }

    // 禁用 WP 更新检查
    if ( _pz( 'disable_wp_update', false ) ) {
        remove_action( 'admin_init', '_maybe_update_core' );
        remove_action( 'admin_init', '_maybe_update_plugins' );
        remove_action( 'admin_init', '_maybe_update_themes' );
    }

    // 禁用文章 Pingback
    if ( _pz( 'disable_pingback', false ) ) {
        add_action( 'pre_ping', 'onedown_disable_self_ping' );
        function onedown_disable_self_ping( &$links ) {
            $home = get_option( 'home' );
            foreach ( $links as $l => $link ) {
                if ( 0 === strpos( $link, $home ) ) {
                    unset( $links[ $l ] );
                }
            }
        }
    }

    // 禁用文章 Trackback（包括发送、接收及编辑界面）
    if ( _pz( 'disable_trackback', true ) ) {
        // 阻止接收外部 trackback/pingback
        add_filter( 'pings_open', '__return_false' );
        // 移除 wp_head 中的 trackback RDF 声明
        add_action( 'init', function () {
            remove_action( 'wp_head', 'trackback_rdf', 10 );
        } );
        // 移除 trackback feed
        add_action( 'init', function () {
            // 移除发布文章时向外发送 trackback（需在 init 之后才能移除）
            remove_action( 'publish_post', 'do_trackbacks' );
            remove_action( 'publish_future_post', 'do_trackbacks' );
            // 移除文章类型对 trackbacks 的支持，编辑页面将不显示 Trackback 栏目
            remove_post_type_support( 'post', 'trackbacks' );
            remove_post_type_support( 'page', 'trackbacks' );
        }, 20 );
        add_action( 'template_redirect', function () {
            if ( is_trackback() ) {
                wp_die( __( 'Trackback is disabled on this site.', 'onedown' ), '', array( 'response' => 403 ) );
            }
        }, 1 );
    }

    // 禁止非管理员登录后台
    if ( _pz( 'disable_admin_for_non_admin', false ) ) {
        add_action( 'admin_init', 'onedown_block_admin_for_non_admin' );
        function onedown_block_admin_for_non_admin() {
            if ( ! is_super_admin() && ! stristr( $_SERVER['PHP_SELF'], 'admin-ajax.php' ) ) {
                wp_redirect( home_url() );
                exit;
            }
        }
    }

    // 推广佣金自动结算（每小时检查，将7天前的待结算佣金标记为可提现）
    if ( _pz( 'referral_enabled', false ) ) {
        add_action( 'onedown_referral_cron_settle', 'onedown_referral_auto_settle' );
        if ( ! function_exists( 'onedown_referral_auto_settle' ) ) {
            function onedown_referral_auto_settle() {
                $args = array(
                    'meta_key'   => 'onedown_referral_commissions',
                    'meta_compare' => 'EXISTS',
                );
                $users = get_users( $args );
                $cutoff = strtotime( '-7 days' );

                foreach ( $users as $user ) {
                    $commissions = get_user_meta( $user->ID, 'onedown_referral_commissions', true );
                    if ( ! is_array( $commissions ) ) {
                        continue;
                    }
                    $changed = false;
                    foreach ( $commissions as $k => $c ) {
                        if ( $c['status'] === 'pending' ) {
                            $created = strtotime( $c['created_at'] );
                            if ( $created && $created < $cutoff ) {
                                $commissions[ $k ]['status'] = 'withdrawable';
                                $changed = true;
                            }
                        }
                    }
                    if ( $changed ) {
                        update_user_meta( $user->ID, 'onedown_referral_commissions', $commissions );
                    }
                }
            }
        }
    }

    // 分类链接去除 category
    if ( _pz( 'no_category_base', false ) ) {
        add_action( 'init', 'onedown_no_category_base' );
        function onedown_no_category_base() {
            global $wp_rewrite;
            // 移除多余的连续斜杠，避免每次 init 累加 /
            $structure = preg_replace('#/{2,}#', '/', get_option('permalink_structure'));
            $wp_rewrite->set_permalink_structure($structure);
            $wp_rewrite->add_rewrite_tag( '%category%', '(.+?)', 'category=' );
        }
        add_filter( 'category_rewrite_rules', 'onedown_no_category_base_rules' );
        function onedown_no_category_base_rules() {
            $category_rewrite = array();
            $categories       = get_categories( array( 'hide_empty' => false ) );
            foreach ( $categories as $category ) {
                $category_nicename = $category->slug;
                if ( $category->parent == $category->cat_ID ) {
                    $category->parent = 0;
                } elseif ( 0 != $category->parent ) {
                    $category_nicename = get_category_parents( $category->parent, false, '/', true ) . $category_nicename;
                }
                $category_rewrite[ '(' . $category_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
                $category_rewrite[ '(' . $category_nicename . ')/page/?([0-9]{1,})/?$' ]                 = 'index.php?category_name=$matches[1]&paged=$matches[2]';
                $category_rewrite[ '(' . $category_nicename . ')/?$' ]                                    = 'index.php?category_name=$matches[1]';
            }
            return $category_rewrite;
        }
        add_filter( 'request', 'onedown_no_category_base_request', 1 );
        function onedown_no_category_base_request( $query_vars ) {
            if ( isset( $query_vars['category_name'] ) ) {
                $category_name = explode( '/', $query_vars['category_name'] );
                if ( isset( $category_name[1] ) ) {
                    $query_vars['category_name'] = $category_name[1];
                }
            }
            return $query_vars;
        }
    }
}

/**
 * 保存文章时自动获取正文第一张图片作为特色图片
 */
add_action( 'save_post', 'onedown_auto_set_featured_image', 10, 3 );
function onedown_auto_set_featured_image( $post_id, $post, $update ) {
    // 仅对文章类型生效
    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
        return;
    }
    if ( $post->post_type !== 'post' ) {
        return;
    }
    // 检查开关
    if ( ! _pz( 'auto_featured_image', false ) ) {
        return;
    }
    // 已有特色图片则不处理
    if ( has_post_thumbnail( $post_id ) ) {
        return;
    }
    // 从正文提取第一张图片
    $content = $post->post_content;
    if ( ! preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches ) ) {
        return;
    }
    $image_url = $matches[1];

    // 下载图片并设为特色图片
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );
    if ( is_wp_error( $attachment_id ) ) {
        return;
    }
    set_post_thumbnail( $post_id, $attachment_id );
}

/**
 * 在经典编辑器工具栏添加短代码下拉菜单
 */
add_filter( 'mce_buttons', 'onedown_register_tinymce_shortcode_button' );
function onedown_register_tinymce_shortcode_button( $buttons ) {
    $buttons[] = 'onedown_shortcodes';
    return $buttons;
}

add_filter( 'mce_external_plugins', 'onedown_register_tinymce_shortcode_plugin' );
function onedown_register_tinymce_shortcode_plugin( $plugins ) {
    $plugins['onedown_shortcodes'] = get_template_directory_uri() . '/assets/js/tinymce-shortcodes.js';
    return $plugins;
}
