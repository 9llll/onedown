<?php

/**
 * 图文轮播广告小组件
 *
 * 侧边栏图文轮播广告，支持图片+文字、纯图片、纯文字三种模式
 * 图片支持上传或直接输入链接
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Ad_Carousel');
}, 1);

// 确保小组件页面加载了媒体库脚本（支持图片上传）
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'widgets.php') {
        wp_enqueue_media();
    }
});

class OD_Widget_Ad_Carousel extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_ad_carousel',
            'description' => '侧边栏图文轮播广告，支持图片+文字、纯图片、纯文字模式',
        );
        parent::__construct('od-ad-carousel', __('OD 图文轮播广告', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        if (function_exists('onedown_enqueue_swiper')) {
            onedown_enqueue_swiper();
        }

        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : '';
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        } else {
            echo '<style>.od_ad_carousel{padding:0 !important;margin-top:0 !important;}.od_ad_carousel .widget-title,.od_ad_carousel .widget-title-wrap{padding:0 !important;}</style>';
        }

        $slides = ! empty($instance['slides']) ? $instance['slides'] : array();

        if (! empty($slides)) :
            // 纯文字广告的样式
            $has_text_only = false;
            foreach ($slides as $slide) {
                $m = ! empty($slide['mode']) ? $slide['mode'] : 'auto';
                $i = ! empty($slide['img']) ? $slide['img'] : '';
                $t = ! empty($slide['text']) ? $slide['text'] : '';
                if (($m === 'text') || ($m === 'auto' && ! $i && $t)) {
                    $has_text_only = true;
                    break;
                }
            }
            if ($has_text_only) : ?>
                <style>
                    .ad-slide-text-only .sidebar-slide {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        text-align: center;
                        background: var(--od-gradient, linear-gradient(135deg, #667eea, #764ba2));
                        color: #fff;
                    }

                    .ad-slide-text-only .sidebar-slide span {
                        position: relative;
                        left: auto;
                        right: auto;
                        bottom: auto;
                        font-size: 14px;
                        font-weight: 600;
                        text-shadow: 0 1px 3px rgba(0, 0, 0, .2);
                        padding: 10px;
                    }
                </style>
            <?php endif; ?>
            <div
                class="swiper-container sidebar-swiper ad-carousel-swiper<?php echo $has_text_only ? ' ad-slide-text-only' : ''; ?>">
                <div class="swiper-wrapper">
                    <?php foreach ($slides as $slide) :
                        $img   = ! empty($slide['img']) ? $slide['img'] : '';
                        $link  = ! empty($slide['link']) ? $slide['link'] : '#';
                        $text  = ! empty($slide['text']) ? $slide['text'] : '';
                        $mode  = ! empty($slide['mode']) ? $slide['mode'] : 'auto';

                        // 根据模式决定显示内容
                        $show_img  = ($mode === 'text' || ($mode === 'auto' && ! $img)) ? false : true;
                        $show_text = ($mode === 'image' || ($mode === 'auto' && ! $text)) ? false : true;
                    ?>
                        <div class="swiper-slide ad-slide">
                            <a class="sidebar-slide ad-slide-link" href="<?php echo esc_url($link); ?>" target="_blank"
                                rel="nofollow noopener">
                                <?php if ($show_img && $img) : ?>
                                    <?php onedown_lazyload_img($img, $text ?: __('广告', 'onedown')); ?>
                                <?php endif; ?>
                                <?php if ($show_text && $text) : ?>
                                    <span class="ad-slide-text"><?php echo esc_html($text); ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="sidebar-swiper-arrow prev" type="button" aria-label="上一张">
                    <i class="fa fa-angle-left"></i>
                </button>
                <button class="sidebar-swiper-arrow next" type="button" aria-label="下一张">
                    <i class="fa fa-angle-right"></i>
                </button>
                <div class="swiper-pagination sidebar-swiper-pagination"></div>
            </div>

            <?php
            // Swiper 初始化 JS（只输出一次）
            static $sidebar_swiper_initialized = false;
            if (! $sidebar_swiper_initialized) :
                $sidebar_swiper_initialized = true;
            ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof Swiper === 'undefined') return;
                        var els = document.querySelectorAll('.sidebar-swiper');
                        els.forEach(function(el) {
                            var slideCount = el.querySelectorAll('.swiper-slide').length;
                            if (slideCount < 2) return;
                            var pagination = el.querySelector('.sidebar-swiper-pagination');
                            new Swiper(el, {
                                loop: true,
                                speed: 400,
                                observer: true,
                                observeParents: true,
                                autoplay: {
                                    delay: 4000,
                                    disableOnInteraction: false,
                                },
                                pagination: pagination ? {
                                    el: pagination,
                                    clickable: true,
                                } : false,
                                navigation: {
                                    prevEl: el.querySelector('.sidebar-swiper-arrow.prev'),
                                    nextEl: el.querySelector('.sidebar-swiper-arrow.next'),
                                },
                            });
                        });
                    });
                </script>
            <?php endif; ?>
        <?php else : ?>
            <div class="text-ad-empty" style="padding:12px;text-align:center;font-size:12px;color:var(--od-muted);">
                <?php _e('暂无广告', 'onedown'); ?>
            </div>
        <?php endif;

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title   = ! empty($instance['title']) ? $instance['title'] : '';
        $slides  = ! empty($instance['slides']) ? $instance['slides'] : array();

        // 确保至少有 1 个空条目方便添加
        if (empty($slides)) {
            $slides = array(array('img' => '', 'link' => '', 'text' => '', 'mode' => 'auto'));
        }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">
                <?php _e('标题：', 'onedown'); ?>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                    name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>"
                    placeholder="<?php esc_attr_e('广告轮播', 'onedown'); ?>">
            </label>
        </p>

        <div class="od-ad-carousel-slides" style="margin-top:8px;">
            <p style="font-weight:600;margin-bottom:6px;"><?php _e('广告项：', 'onedown'); ?></p>
            <?php foreach ($slides as $i => $slide) :
                $img  = ! empty($slide['img']) ? $slide['img'] : '';
                $link = ! empty($slide['link']) ? $slide['link'] : '';
                $text = ! empty($slide['text']) ? $slide['text'] : '';
                $mode = ! empty($slide['mode']) ? $slide['mode'] : 'auto';
            ?>
                <div class="od-ad-slide-item"
                    style="background:#f5f5f5;padding:10px;margin-bottom:8px;border-radius:4px;border:1px solid #ddd;">
                    <div style="display:flex;gap:6px;margin-bottom:6px;align-items:center;">
                        <span style="font-weight:500;font-size:12px;color:#666;">#<?php echo $i + 1; ?></span>
                        <select name="<?php echo $this->get_field_name('slides'); ?>[<?php echo $i; ?>][mode]"
                            style="font-size:12px;">
                            <option value="auto" <?php selected($mode, 'auto'); ?>><?php _e('图文混合', 'onedown'); ?></option>
                            <option value="image" <?php selected($mode, 'image'); ?>><?php _e('仅图片', 'onedown'); ?></option>
                            <option value="text" <?php selected($mode, 'text'); ?>><?php _e('仅文字', 'onedown'); ?></option>
                        </select>
                    </div>

                    <p style="margin:0 0 4px;">
                        <label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">
                            <?php _e('图片 URL（支持上传）：', 'onedown'); ?>
                        </label>
                        <span style="display:flex;gap:4px;">
                            <input type="text" class="widefat od-ad-img-input" style="flex:1;"
                                name="<?php echo $this->get_field_name('slides'); ?>[<?php echo $i; ?>][img]"
                                value="<?php echo esc_attr($img); ?>" placeholder="https://... 或留空">
                            <button type="button" class="button od-ad-upload-btn" data-target="slides-<?php echo $i; ?>-img"
                                style="flex-shrink:0;">
                                <span class="dashicons dashicons-upload"
                                    style="font-size:16px;width:16px;height:16px;margin:0;line-height:1.2;"></span>
                            </button>
                        </span>
                    </p>

                    <p style="margin:0 0 4px;">
                        <label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">
                            <?php _e('文字描述：', 'onedown'); ?>
                        </label>
                        <input type="text" class="widefat"
                            name="<?php echo $this->get_field_name('slides'); ?>[<?php echo $i; ?>][text]"
                            value="<?php echo esc_attr($text); ?>" placeholder="<?php esc_attr_e('广告文案', 'onedown'); ?>">
                    </p>

                    <p style="margin:0;">
                        <label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">
                            <?php _e('跳转链接：', 'onedown'); ?>
                        </label>
                        <input type="url" class="widefat"
                            name="<?php echo $this->get_field_name('slides'); ?>[<?php echo $i; ?>][link]"
                            value="<?php echo esc_attr($link); ?>" placeholder="https://...">
                    </p>

                    <?php if ($i > 0) : ?>
                        <div style="text-align:right;margin-top:4px;">
                            <button type="button" class="button button-small od-ad-remove-slide" style="color:#a00;">
                                <?php _e('删除此项', 'onedown'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <p>
            <button type="button" class="button od-ad-add-slide" data-widget-id="<?php echo $this->get_field_id('slides'); ?>">
                + <?php _e('添加广告项', 'onedown'); ?>
            </button>
        </p>

        <p style="font-size:12px;color:#999;margin-top:0;">
            <?php _e('提示：选择"仅文字"模式可不填图片；选择"仅图片"模式可不填文字。图片可上传或直接粘贴 URL。', 'onedown'); ?>
        </p>

        <style>
            .od-ad-slide-item p {
                margin-bottom: 4px;
            }

            .od-ad-slide-item input[type="text"],
            .od-ad-slide-item input[type="url"],
            .od-ad-slide-item select {
                font-size: 12px;
            }
        </style>

        <script type="text/javascript">
            if (!window._odAdCarouselInit) {
                window._odAdCarouselInit = true;

                jQuery(function($) {
                    // 上传图片
                    $(document).on('click', '.od-ad-upload-btn', function(e) {
                        e.preventDefault();
                        var $btn = $(this);
                        var frame = wp.media({
                            title: '<?php echo esc_js(__('选择广告图片', 'onedown')); ?>',
                            button: {
                                text: '<?php echo esc_js(__('使用此图片', 'onedown')); ?>'
                            },
                            multiple: false
                        });
                        frame.on('select', function() {
                            var attachment = frame.state().get('selection').first().toJSON();
                            $btn.closest('span').find('.od-ad-img-input').val(attachment.url);
                        });
                        frame.open();
                    });

                    // 添加广告项
                    $(document).on('click', '.od-ad-add-slide', function() {
                        var $btn = $(this);
                        var $widget = $btn.closest('.widget');
                        var $slides = $widget.find('.od-ad-carousel-slides');
                        if (!$slides.length) return;

                        var idx = $slides.find('.od-ad-slide-item').length;
                        var $newItem = $slides.find('.od-ad-slide-item:first').clone();

                        // 重置表单值
                        $newItem.find('input').val('');
                        $newItem.find('select').val('auto');

                        // 更新序号
                        $newItem.find('> div:first > span:first').text('#' + (idx + 1));

                        // 更新 name / data-target 中的索引
                        // 注意 name 格式为 widget-od-ad-carousel[5][slides][0][img]
                        // 只替换 [slides] 后面的数字索引，不碰 widget 编号
                        $newItem.find('input, select, button').each(function() {
                            var $el = $(this);
                            var name = $el.attr('name');
                            if (name) $el.attr('name', name.replace(/\[slides\]\[\d+\]/, '[slides][' + idx + ']'));
                            var target = $el.attr('data-target');
                            if (target) $el.attr('data-target', target.replace(/\d+/, idx));
                        });

                        // 替换删除按钮（新项始终可删除）
                        $newItem.find('.od-ad-remove-slide').remove();
                        $newItem.append(
                            '<div style="text-align:right;margin-top:4px;">' +
                            '<button type="button" class="button button-small od-ad-remove-slide" style="color:#a00;">' +
                            '<?php echo esc_js(__('删除此项', 'onedown')); ?>' +
                            '</button></div>'
                        );

                        $slides.append($newItem);
                    });

                    // 删除广告项
                    $(document).on('click', '.od-ad-remove-slide', function() {
                        var $widget = $(this).closest('.widget');
                        $(this).closest('.od-ad-slide-item').remove();

                        $widget.find('.od-ad-slide-item').each(function(idx) {
                            $(this).find('> div:first > span:first').text('#' + (idx + 1));
                            $(this).find('input, select, button').each(function() {
                                var $el = $(this);
                                var name = $el.attr('name');
                                if (name) $el.attr('name', name.replace(/\[slides\]\[\d+\]/, '[slides][' + idx + ']'));
                                var target = $el.attr('data-target');
                                if (target) $el.attr('data-target', target.replace(/\d+/, idx));
                            });
                        });
                    });
                });
            }
        </script>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance           = $old_instance;
        $instance['title']  = wp_kses_post($new_instance['title']);

        $slides = array();
        if (! empty($new_instance['slides']) && is_array($new_instance['slides'])) {
            foreach ($new_instance['slides'] as $slide) {
                $slide_data = array(
                    'img'  => esc_url_raw($slide['img']),
                    'link' => esc_url_raw($slide['link']),
                    'text' => sanitize_text_field($slide['text']),
                    'mode' => in_array($slide['mode'], array('auto', 'image', 'text')) ? $slide['mode'] : 'auto',
                );
                // 至少有一个有效内容才保存
                if ($slide_data['img'] || $slide_data['text']) {
                    $slides[] = $slide_data;
                }
            }
        }

        $instance['slides'] = $slides;
        return $instance;
    }
}
