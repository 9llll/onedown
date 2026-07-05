<?php

/**
 * Onedown 固定悬浮按钮
 *
 * 参考自 zibll 主题的 float-right 实现，在 wp_footer 钩子输出。
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 规范化悬浮按钮图标类名，兼容 CSF icon 字段返回值。
 */
function onedown_float_button_icon_class($icon, $default = '')
{
    $icon = trim((string) $icon);
    $icon = preg_replace('/\b(?:fas|far|fab)\s+/', '', $icon);

    if ($icon === '') {
        $icon = $default;
    }

    if ($icon !== '' && strpos($icon, 'fa ') === false && preg_match('/\bfa-[a-z0-9-]+\b/i', $icon, $matches)) {
        $icon = 'fa ' . $matches[0];
    }

    return trim($icon);
}

function onedown_get_float_edit_post_link()
{
    if (! is_single()) {
        return '';
    }

    $post_id = get_queried_object_id();
    if (! $post_id || get_post_type($post_id) !== 'post') {
        return '';
    }

    if (! is_user_logged_in() || ! current_user_can('manage_options') || ! current_user_can('edit_post', $post_id)) {
        return '';
    }

    return (string) get_edit_post_link($post_id, '');
}

/**
 * 输出固定悬浮按钮
 */
function onedown_float_right()
{
    $btn       = '';
    $opt       = _pz('float_btn');
    $is_mobile = wp_is_mobile();
    $edit_post_item = null;

    // 默认按钮配置（无后台配置时使用）
    if (empty($opt) || ! is_array($opt)) {
        $opt = array(
            array('type' => 'pay_vip',  'pc_s' => true, 'm_s' => true),
            array('type' => 'edit_post', 'pc_s' => true, 'm_s' => true),
            array('type' => 'theme_toggle', 'pc_s' => true, 'm_s' => true),
            array('type' => 'back_top', 'pc_s' => true, 'm_s' => true),
        );
    }

    usort($opt, function ($a, $b) {
        $a_type = is_array($a) && ! empty($a['type']) ? $a['type'] : '';
        $b_type = is_array($b) && ! empty($b['type']) ? $b['type'] : '';

        if ($a_type === 'back_top' && $b_type !== 'back_top') {
            return 1;
        }

        if ($b_type === 'back_top' && $a_type !== 'back_top') {
            return -1;
        }

        return 0;
    });

    foreach ($opt as $item) {
        if (! is_array($item) || empty($item['type'])) {
            continue;
        }

        $type = $item['type'];

        if ($type === 'edit_post') {
            $edit_post_item = $item;
            continue;
        }

        // PC/移动端显示检查
        $show_pc = ! empty($item['pc_s']);
        $show_m  = ! empty($item['m_s']);
        if (($is_mobile && ! $show_m) || (! $is_mobile && ! $show_pc)) {
            continue;
        }

        $style = '';
        if (! empty($item['color'])) {
            $style_attr = '';
            if (is_array($item['color'])) {
                // 兼容旧格式：array('color'=>'#xxx', 'bg'=>'#xxx')
                if (! empty($item['color']['color'])) {
                    $style_attr .= '--this-color:' . esc_attr($item['color']['color']) . ';';
                }
                if (! empty($item['color']['bg'])) {
                    $style_attr .= '--this-bg:' . esc_attr($item['color']['bg']) . ';';
                }
            } elseif (is_string($item['color'])) {
                // CSF color 字段返回纯色值字符串
                $color_val = $item['color'];
                $style_attr .= '--this-color:' . esc_attr($color_val) . ';';
                // 生成半透明背景色
                $r = hexdec(substr($color_val, 1, 2));
                $g = hexdec(substr($color_val, 3, 2));
                $b = hexdec(substr($color_val, 5, 2));
                $style_attr .= '--this-bg:rgba(' . $r . ',' . $g . ',' . $b . ',.12);';
            }
            if ($style_attr) {
                $style = ' style="' . $style_attr . '"';
            }
        }

        $btn_title = ! empty($item['title']) ? $item['title'] : '';
        $btn_icon  = ! empty($item['icon']) ? $item['icon'] : '';

        switch ($type) {
            case 'back_top':
                $icon = onedown_float_button_icon_class($btn_icon, 'fa fa-angle-up');
                $title = $btn_title ?: '返回顶部';
                $btn .= '<a' . $style . ' class="float-btn ontop" href="javascript:;" title="' . esc_attr($title) . '" data-float="top"><i class="' . esc_attr($icon) . '"></i></a>' . "\n";
                break;

            case 'theme_toggle':
                $icon = onedown_float_button_icon_class($btn_icon, 'fa fa-moon-o');
                $title = $btn_title ?: '切换深色/浅色模式';
                $btn .= '<button' . $style . ' class="float-btn theme-toggle-btn" type="button" title="' . esc_attr($title) . '" aria-label="' . esc_attr($title) . '" data-theme-toggle><i class="' . esc_attr($icon) . '"></i><i class="fa fa-sun-o"></i></button>' . "\n";
                break;

            case 'pay_vip':
                if (is_user_logged_in()) {
                    $vip_info = onedown_get_user_vip_info(get_current_user_id());
                    $is_vip   = $vip_info['is_vip'];
                } else {
                    $is_vip = false;
                }
                $icon = onedown_float_button_icon_class($btn_icon, 'fa fa-diamond');
                $title = $btn_title ?: ($is_vip ? '升级会员' : '开通会员');
                $btn .= '<a' . $style . ' class="float-btn pay-vip" href="javascript:;" title="' . esc_attr($title) . '" data-vip-modal><i class="' . esc_attr($icon) . '"></i></a>' . "\n";
                break;

            case 'service_qq':
                $qq = ! empty($item['qq']) ? $item['qq'] : '';
                if ($qq) {
                    $icon = onedown_float_button_icon_class($btn_icon, 'fa fa-qq');
                    $title = $btn_title ?: 'QQ客服';
                    $btn .= '<a' . $style . ' class="float-btn service-qq" href="http://wpa.qq.com/msgrd?v=3&uin=' . esc_attr($qq) . '&site=qq&menu=yes" target="_blank" rel="noopener noreferrer" title="' . esc_attr($title) . '"><i class="' . esc_attr($icon) . '"></i></a>' . "\n";
                }
                break;

            case 'service_wechat':
                $wechat_img_raw = ! empty($item['wechat_img']) ? $item['wechat_img'] : '';
                $wechat_img = is_array($wechat_img_raw) ? ($wechat_img_raw['url'] ?? '') : $wechat_img_raw;
                if ($wechat_img) {
                    $icon = onedown_float_button_icon_class($btn_icon, 'fa fa-wechat');
                    $title = $btn_title ?: '扫码添加微信';
                    $hover  = '<div class="float-dropdown">';
                    $hover .= '<img src="' . esc_url($wechat_img) . '" alt="' . esc_attr($title) . '" width="120" height="120">';
                    $hover .= '</div>';
                    $btn   .= '<span' . $style . ' class="float-btn service-wechat hover-show" title="' . esc_attr($title) . '">';
                    $btn   .= '<i class="' . esc_attr($icon) . '"></i>' . $hover . '</span>' . "\n";
                }
                break;

            case 'qrcode':
                $icon = onedown_float_button_icon_class($btn_icon, 'fa fa-qrcode');
                $title = $btn_title ?: '二维码';
                $desc = ! empty($item['desc']) ? '<div class="float-dropdown-desc">' . esc_html($item['desc']) . '</div>' : '';
                $btn .= '<span' . $style . ' class="float-btn qrcode-btn hover-show" title="' . esc_attr($title) . '">';
                $btn .= '<i class="' . esc_attr($icon) . '"></i>';
                $btn .= '<span class="float-dropdown"><span class="float-qrcode" id="floatQrcode"></span>' . $desc . '</span>';
                $btn .= '</span>' . "\n";
                break;

            case 'build_similar':
                $icon = onedown_float_button_icon_class($btn_icon, 'fa fa-code');
                $title = $btn_title ?: '搭建同款';
                $desc = ! empty($item['build_similar_desc']) ? $item['build_similar_desc'] : '';
                $btn .= '<a' . $style . ' class="float-btn build-similar" href="javascript:;" title="' . esc_attr($title) . '" data-contact-modal="build_similar" data-desc="' . esc_attr($desc) . '"><i class="' . esc_attr($icon) . '"></i></a>' . "\n";
                break;

            case 'qq_group':
                $icon = onedown_float_button_icon_class($btn_icon, 'fa fa-group');
                $title = $btn_title ?: 'QQ群';
                $qq_group_number = ! empty($item['qq_group_number']) ? $item['qq_group_number'] : '';
                $qq_group_img_raw = ! empty($item['qq_group_img']) ? $item['qq_group_img'] : '';
                $qq_group_img = is_array($qq_group_img_raw) ? ($qq_group_img_raw['url'] ?? '') : $qq_group_img_raw;
                if ($qq_group_number || $qq_group_img) {
                    $btn .= '<a' . $style . ' class="float-btn qq-group-btn" href="javascript:;" title="' . esc_attr($title) . '" data-contact-modal="qq_group" data-group="' . esc_attr($qq_group_number) . '" data-img="' . esc_url($qq_group_img) . '"><i class="' . esc_attr($icon) . '"></i></a>' . "\n";
                }
                break;

            case 'wechat_group':
                $icon = onedown_float_button_icon_class($btn_icon, 'fa fa-wechat');
                $title = $btn_title ?: '微信群';
                $wechat_group_img_raw = ! empty($item['wechat_group_img']) ? $item['wechat_group_img'] : '';
                $wechat_group_img = is_array($wechat_group_img_raw) ? ($wechat_group_img_raw['url'] ?? '') : $wechat_group_img_raw;
                $wechat_group_name = ! empty($item['wechat_group_name']) ? $item['wechat_group_name'] : '';
                if ($wechat_group_img) {
                    $btn .= '<a' . $style . ' class="float-btn wechat-group-btn" href="javascript:;" title="' . esc_attr($title) . '" data-contact-modal="wechat_group" data-img="' . esc_url($wechat_group_img) . '" data-name="' . esc_attr($wechat_group_name) . '"><i class="' . esc_attr($icon) . '"></i></a>' . "\n";
                }
                break;

            case 'custom_link':
                $url = ! empty($item['custom_link_url']) ? $item['custom_link_url'] : '#';
                $icon = onedown_float_button_icon_class($btn_icon, 'fa fa-link');
                $title = $btn_title ?: '自定义链接';
                $btn .= '<a' . $style . ' class="float-btn custom-link-btn" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr($title) . '"><i class="' . esc_attr($icon) . '"></i></a>' . "\n";
                break;
        }
    }

    if (! is_array($edit_post_item)) {
        $edit_post_item = array(
            'type'  => 'edit_post',
            'pc_s'  => true,
            'm_s'   => true,
            'title' => '编辑文章',
        );
    }

    if (is_array($edit_post_item)) {
        $show_pc = ! empty($edit_post_item['pc_s']);
        $show_m  = ! empty($edit_post_item['m_s']);
        if ((! $is_mobile && $show_pc) || ($is_mobile && $show_m)) {
            $edit_link = onedown_get_float_edit_post_link();
            if ($edit_link) {
                $style = '';
                if (! empty($edit_post_item['color'])) {
                    $style_attr = '';
                    if (is_array($edit_post_item['color'])) {
                        if (! empty($edit_post_item['color']['color'])) {
                            $style_attr .= '--this-color:' . esc_attr($edit_post_item['color']['color']) . ';';
                        }
                        if (! empty($edit_post_item['color']['bg'])) {
                            $style_attr .= '--this-bg:' . esc_attr($edit_post_item['color']['bg']) . ';';
                        }
                    } elseif (is_string($edit_post_item['color'])) {
                        $color_val = $edit_post_item['color'];
                        $style_attr .= '--this-color:' . esc_attr($color_val) . ';';
                        $r = hexdec(substr($color_val, 1, 2));
                        $g = hexdec(substr($color_val, 3, 2));
                        $b = hexdec(substr($color_val, 5, 2));
                        $style_attr .= '--this-bg:rgba(' . $r . ',' . $g . ',' . $b . ',.12);';
                    }
                    if ($style_attr) {
                        $style = ' style="' . $style_attr . '"';
                    }
                }

                $btn_title = ! empty($edit_post_item['title']) ? $edit_post_item['title'] : '';
                $btn_icon  = ! empty($edit_post_item['icon']) ? $edit_post_item['icon'] : '';
                $icon      = onedown_float_button_icon_class($btn_icon, 'fa fa-pencil');
                $title     = $btn_title ?: '编辑文章';
                $edit_post_btn = '<a' . $style . ' class="float-btn edit-post-btn" href="' . esc_url($edit_link) . '" title="' . esc_attr($title) . '"><i class="' . esc_attr($icon) . '"></i></a>' . "\n";
                if (strpos($btn, 'class="float-btn ontop"') !== false) {
                    $btn = preg_replace('/<a[^>]*class="float-btn ontop"[^>]*>.*?<\/a>\n?/s', $edit_post_btn . '$0', $btn, 1);
                } else {
                    $btn .= $edit_post_btn;
                }
            }
        }
    }

    if (! $btn) {
        return;
    }

    $position = _pz('float_btn_position', 'right') === 'left' ? 'left' : 'right';
    $class = 'round is-' . $position;

    $float_filter = (array) _pz('float_btn_filter', array('m_s'));
    if (($is_mobile && in_array('m_s', $float_filter, true)) || (! $is_mobile && in_array('pc_s', $float_filter, true))) {
        $class .= ' filter';
    }

    if (_pz('float_btn_scroll_hide', false)) {
        $class .= ' scrolling-hide';
    }

    echo '<div class="float-right ' . esc_attr($class) . '">' . $btn . '</div>' . "\n";
}
add_action('wp_footer', 'onedown_float_right', 99);
