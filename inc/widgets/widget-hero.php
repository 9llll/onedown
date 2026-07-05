<?php

/**
 * 首页 Hero 轮播小组件
 *
 * 全宽轮播组件，支持自定义图文或自动从文章取图
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Hero_Carousel');
}, 1);

// 确保后台加载媒体库脚本
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'widgets.php') {
        wp_enqueue_media();
    }
});

class OD_Widget_Hero_Carousel extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_hero_carousel',
            'description' => '全宽 Hero 轮播，支持自定义图文或自动从文章取图',
        );
        parent::__construct('od-hero-carousel', __('OD Hero 轮播', 'onedown'), $widget_ops);
    }

    /**
     * 获取默认轮播项
     */
    private function get_default_slides()
    {
        return array(
            array(
                'img'   => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1300&q=82',
                'link'  => '#',
                'text'  => '简约优雅的内容社区',
                'mode'  => 'auto',
            ),
            array(
                'img'   => 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1300&q=82',
                'link'  => '#',
                'text'  => '把内容站点升级成活跃社区',
                'mode'  => 'auto',
            ),
            array(
                'img'   => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1300&q=82',
                'link'  => '#',
                'text'  => '为资源商城准备完整首页入口',
                'mode'  => 'auto',
            ),
        );
    }

    public function widget($args, $instance)
    {
        if (function_exists('onedown_enqueue_swiper')) {
            onedown_enqueue_swiper();
        }

        $title    = ! empty($instance['title']) ? $instance['title'] : '';
        $source   = ! empty($instance['source']) ? $instance['source'] : 'posts';
        $slides   = ! empty($instance['slides']) ? $instance['slides'] : array();
        $number   = ! empty($instance['number']) ? min(10, max(1, (int) $instance['number'])) : 5;
        $category = ! empty($instance['category']) ? (int) $instance['category'] : 0;

        // 构建轮播数据
        $render_slides = array();

        if ($source === 'custom' && ! empty($slides)) {
            $render_slides = $slides;
        } elseif ($source === 'posts') {
            $query_args = array(
                'posts_per_page'      => $number,
                'ignore_sticky_posts' => 1,
                'post_status'         => 'publish',
                'no_found_rows'       => true,
                'meta_query'          => array(
                    array('key' => '_thumbnail_id', 'compare' => 'EXISTS'),
                ),
            );
            if ($category > 0) {
                $query_args['cat'] = $category;
            }
            $posts = get_posts($query_args);

            if (! empty($posts)) {
                foreach ($posts as $post) {
                    $thumb_url = get_the_post_thumbnail_url($post->ID, 'full');
                    if (! $thumb_url) continue;

                    $cats = get_the_category($post->ID);
                    $cat_name = ! empty($cats) ? $cats[0]->name : '推荐文章';
                    $excerpt  = get_the_excerpt($post);
                    if (! $excerpt) {
                        $excerpt = wp_trim_words(get_the_content('', false, $post), 30, '...');
                    }

                    $render_slides[] = array(
                        'img'      => $thumb_url,
                        'link'     => get_permalink($post->ID),
                        'text'     => get_the_title($post->ID),
                        'excerpt'  => esc_html($excerpt),
                        'category' => esc_html($cat_name),
                        'mode'     => 'auto',
                    );
                }
            }

            // 没有文章时有特色图，使用默认兜底
            if (empty($render_slides)) {
                $render_slides = $this->get_default_slides();
            }
        } else {
            $render_slides = $this->get_default_slides();
        }

        if (empty($render_slides)) {
            $render_slides = $this->get_default_slides();
        }

        // 随机图片兜底池
        $fallback_images = array(
            'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1300&q=82',
            'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1300&q=82',
            'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1300&q=82',
            'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1300&q=82',
            'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1300&q=82',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1300&q=82',
        );
?>
        <section class="hero" aria-label="首页推荐">
            <div class="hero-panel">
                <?php if ($title) : ?>
                    <div class="hero-head">
                        <h2><?php echo esc_html($title); ?></h2>
                    </div>
                <?php endif; ?>
                <div class="swiper-container hero-swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($render_slides as $slide) :
                            $img_url  = ! empty($slide['img']) ? $slide['img'] : $fallback_images[array_rand($fallback_images)];
                            $link_url = ! empty($slide['link']) ? $slide['link'] : '#';
                            $text     = ! empty($slide['text']) ? $slide['text'] : '';
                            $excerpt  = ! empty($slide['excerpt']) ? $slide['excerpt'] : '';
                            $mode     = ! empty($slide['mode']) ? $slide['mode'] : 'auto';
                            $cat_name = ! empty($slide['category']) ? $slide['category'] : '推荐文章';
                            $is_image_only = $mode === 'image' || ($text === '' && $excerpt === '');
                            $hero_slide_class = 'hero-slide' . ($is_image_only ? ' hero-slide-image-only' : '');
                            $background_image = $is_image_only ? 'url(' . esc_url($img_url) . ')' : 'linear-gradient(135deg, rgba(30,38,60,.78), rgba(240,68,148,.28)), url(' . esc_url($img_url) . ')';
                        ?>
                            <div class="swiper-slide">
                                <div class="<?php echo esc_attr($hero_slide_class); ?>"
                                    style="background-image: <?php echo esc_attr($background_image); ?>;">
                                    <?php if (! $is_image_only) : ?>
                                        <div class="hero-content">
                                            <h1><a href="<?php echo esc_url($link_url); ?>"><?php echo esc_html($text); ?></a></h1>
                                            <?php if ($excerpt) : ?><p><?php echo $excerpt; ?></p><?php endif; ?>
                                            <div class="hero-actions">
                                                <a class="primary" href="<?php echo esc_url($link_url); ?>"><i class="fa fa-book"></i>
                                                    阅读全文</a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <a class="hero-arrow prev" href="javascript:;" aria-label="上一张"><i class="fa fa-angle-left"></i></a>
                    <a class="hero-arrow next" href="javascript:;" aria-label="下一张"><i class="fa fa-angle-right"></i></a>
                </div>
            </div>
        </section>
        <?php

        // Swiper 初始化 JS（只输出一次）
        static $swiper_initialized = false;
        if (! $swiper_initialized) {
            $swiper_initialized = true;
        ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Swiper === 'undefined') return;
                    var el = document.querySelector('.hero-swiper');
                    if (!el) return;
                    var slideCount = el.querySelectorAll('.swiper-slide').length;
                    var pagination = el.querySelector('.swiper-pagination');
                    var canLoop = slideCount > 1;
                    if (!canLoop && pagination) {
                        pagination.style.display = 'none';
                    }
                    new Swiper(el, {
                        loop: canLoop,
                        speed: 520,
                        autoHeight: false,
                        observer: true,
                        observeParents: true,
                        resistanceRatio: 0.65,
                        autoplay: canLoop ? {
                            delay: 4200,
                            disableOnInteraction: false
                        } : false,
                        pagination: pagination ? {
                            el: pagination,
                            clickable: true
                        } : false,
                        navigation: {
                            prevEl: el.querySelector('.hero-arrow.prev'),
                            nextEl: el.querySelector('.hero-arrow.next'),
                        },
                    });
                });
            </script>
        <?php
        }
    }

    public function form($instance)
    {
        $title    = ! empty($instance['title']) ? $instance['title'] : '';
        $source   = ! empty($instance['source']) ? $instance['source'] : 'posts';
        $slides   = ! empty($instance['slides']) ? $instance['slides'] : array();
        $number   = ! empty($instance['number']) ? (int) $instance['number'] : 5;
        $category = ! empty($instance['category']) ? (int) $instance['category'] : 0;

        // 自定义模式默认提供 1 个空条目
        if ($source === 'custom' && empty($slides)) {
            $slides = array(array('img' => '', 'link' => '', 'text' => '', 'mode' => 'auto'));
        }

        $widget_id = $this->id;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">
                <?php _e('标题（可选，留空不显示）：', 'onedown'); ?>
                <input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                    name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>"
                    placeholder="推荐内容">
            </label>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('source'); ?>">
                <?php _e('轮播来源：', 'onedown'); ?>
                <select class="widefat od-hero-source-select" id="<?php echo $this->get_field_id('source'); ?>"
                    name="<?php echo $this->get_field_name('source'); ?>"
                    data-posts-field="<?php echo $this->get_field_id('source_posts'); ?>"
                    data-custom-field="<?php echo $this->get_field_id('source_custom'); ?>">
                    <option value="posts" <?php selected($source, 'posts'); ?>><?php _e('自动取文章特色图片', 'onedown'); ?></option>
                    <option value="custom" <?php selected($source, 'custom'); ?>><?php _e('自定义图文', 'onedown'); ?></option>
                </select>
            </label>
        </p>

        <div id="<?php echo $this->get_field_id('source_posts'); ?>" class="od-hero-source-posts"
            <?php echo $source !== 'posts' ? 'style="display:none;"' : ''; ?>>
            <p>
                <label for="<?php echo $this->get_field_id('number'); ?>">
                    <?php _e('显示文章数量：', 'onedown'); ?>
                    <input type="number" class="widefat" id="<?php echo $this->get_field_id('number'); ?>"
                        name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo esc_attr($number); ?>" min="1"
                        max="10" step="1">
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('category'); ?>">
                    <?php _e('分类筛选（可选）：', 'onedown'); ?>
                    <?php
                    wp_dropdown_categories(array(
                        'show_option_none'  => __('全部分类', 'onedown'),
                        'option_none_value' => '0',
                        'name'              => $this->get_field_name('category'),
                        'id'                => $this->get_field_id('category'),
                        'selected'          => $category,
                        'class'             => 'widefat',
                        'hierarchical'      => true,
                        'hide_empty'        => false,
                        'orderby'           => 'name',
                    ));
                    ?>
                </label>
            </p>
            <p style="color:#999;font-size:12px;">
                <?php _e('自动获取有特色图片的文章，无文章时显示默认占位轮播。', 'onedown'); ?>
            </p>
        </div>

        <div id="<?php echo $this->get_field_id('source_custom'); ?>" class="od-hero-source-custom"
            <?php echo $source !== 'custom' ? 'style="display:none;"' : ''; ?>>
            <div class="od-hero-slides" style="margin-top:8px;">
                <p style="font-weight:600;margin-bottom:6px;"><?php _e('轮播项：', 'onedown'); ?></p>
                <?php foreach ($slides as $i => $slide) :
                    $img  = ! empty($slide['img']) ? $slide['img'] : '';
                    $link = ! empty($slide['link']) ? $slide['link'] : '';
                    $text = ! empty($slide['text']) ? $slide['text'] : '';
                    $mode = ! empty($slide['mode']) ? $slide['mode'] : 'auto';
                    $summary = $text ?: ($img ? '（有图）' : '（空）');
                    $has_content = ! empty($text) || ! empty($img);
                ?>
                    <div class="od-hero-slide-item" data-index="<?php echo $i; ?>"
                        style="background:#fff;margin-bottom:6px;border-radius:4px;border:1px solid #ddd;overflow:hidden;">
                        <div class="od-hero-slide-header"
                            style="display:flex;align-items:center;padding:8px 10px;cursor:pointer;user-select:none;gap:8px;background:#fafafa;">
                            <span class="od-hero-slide-toggle"
                                style="font-size:10px;color:#999;transition:transform .2s;">&#9654;</span>
                            <span style="font-weight:500;font-size:12px;color:#666;flex-shrink:0;">#<?php echo $i + 1; ?></span>
                            <span class="od-hero-slide-summary"
                                style="font-size:12px;color:<?php echo $has_content ? '#333' : '#bbb'; ?>;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html($summary); ?></span>
                            <?php if ($i > 0) : ?>
                                <button type="button" class="button button-small od-hero-remove-slide"
                                    style="color:#a00;flex-shrink:0;font-size:11px;line-height:1.4;min-height:0;padding:2px 6px;">删除</button>
                            <?php endif; ?>
                        </div>
                        <div class="od-hero-slide-body" style="display:none;padding:8px 10px;border-top:1px solid #eee;">
                            <div style="display:flex;gap:6px;margin-bottom:6px;align-items:center;">
                                <select name="<?php echo $this->get_field_name('slides'); ?>[<?php echo $i; ?>][mode]"
                                    style="font-size:12px;">
                                    <option value="auto" <?php selected($mode, 'auto'); ?>><?php _e('图文混合', 'onedown'); ?></option>
                                    <option value="image" <?php selected($mode, 'image'); ?>><?php _e('仅图片', 'onedown'); ?></option>
                                    <option value="text" <?php selected($mode, 'text'); ?>><?php _e('仅文字', 'onedown'); ?></option>
                                </select>
                            </div>
                            <p style="margin:0 0 4px;">
                                <label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">
                                    <?php _e('图片（支持上传或输入 URL）：', 'onedown'); ?>
                                </label>
                                <span style="display:flex;gap:4px;flex-wrap:wrap;">
                                    <input type="text" class="widefat od-hero-img-input"
                                        style="flex:1;font-size:13px;min-width:150px;"
                                        name="<?php echo $this->get_field_name('slides'); ?>[<?php echo $i; ?>][img]"
                                        value="<?php echo esc_attr($img); ?>" placeholder="https://...">
                                    <button type="button" class="button od-hero-upload-btn" style="flex-shrink:0;">
                                        <span class="dashicons dashicons-upload"
                                            style="font-size:16px;width:16px;height:16px;margin:0;line-height:1.2;"></span>
                                    </button>
                                    <button type="button" class="button od-hero-random-btn"
                                        style="flex-shrink:0;font-size:11px;">随机</button>
                                </span>
                            </p>
                            <p style="margin:0 0 4px;">
                                <label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">
                                    <?php _e('标题文字：', 'onedown'); ?>
                                </label>
                                <input type="text" class="widefat od-hero-text-input" style="font-size:13px;"
                                    name="<?php echo $this->get_field_name('slides'); ?>[<?php echo $i; ?>][text]"
                                    value="<?php echo esc_attr($text); ?>" placeholder="轮播标题">
                            </p>
                            <p style="margin:0;">
                                <label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">
                                    <?php _e('跳转链接：', 'onedown'); ?>
                                </label>
                                <input type="url" class="widefat" style="font-size:13px;"
                                    name="<?php echo $this->get_field_name('slides'); ?>[<?php echo $i; ?>][link]"
                                    value="<?php echo esc_attr($link); ?>" placeholder="https://...">
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p>
                <button type="button" class="button od-hero-add-slide"
                    data-prefix="<?php echo esc_attr($this->get_field_name('slides')); ?>">
                    + <?php _e('添加轮播项', 'onedown'); ?>
                </button>
            </p>
            <p style="font-size:12px;color:#999;margin:4px 0 0;">
                <?php _e('图片可上传或直接粘贴 URL，留空则使用默认图片。', 'onedown'); ?>
            </p>
        </div>

        <style>
            .od-hero-slide-item p {
                margin-bottom: 4px;
            }

            .od-hero-slide-item input[type="text"],
            .od-hero-slide-item input[type="url"],
            .od-hero-slide-item select {
                font-size: 12px;
            }
        </style>

        <script type="text/javascript">
            jQuery(function($) {
                var widgetEl = '#widget-<?php echo $widget_id; ?>';

                // 先解绑再绑定，防止 AJAX 重绘导致重复绑定
                $(document).off('.odhero').on('change.odhero', '.od-hero-source-select', function() {
                    var val = $(this).val();
                    var $widget = $(this).closest('.widget-content, .widget-inside');
                    $widget.find('.od-hero-source-posts').toggle(val === 'posts');
                    $widget.find('.od-hero-source-custom').toggle(val === 'custom');
                });

                $(document).on('click.odhero', '.od-hero-slide-header', function(e) {
                    if ($(e.target).closest('.od-hero-remove-slide').length) return;
                    var $body = $(this).closest('.od-hero-slide-item').find('.od-hero-slide-body');
                    var $toggle = $(this).find('.od-hero-slide-toggle');
                    var isOpen = $body.is(':visible');
                    $body.stop(true, true).slideToggle(150);
                    $toggle.css('transform', isOpen ? 'rotate(0deg)' : 'rotate(90deg)');
                });

                $(document).on('input.odhero', '.od-hero-text-input, .od-hero-img-input', function() {
                    var $item = $(this).closest('.od-hero-slide-item');
                    var text = $item.find('.od-hero-text-input').val() || '';
                    var img = $item.find('.od-hero-img-input').val() || '';
                    var summary = text || (img ? '（有图）' : '（空）');
                    var $summary = $item.find('.od-hero-slide-summary');
                    $summary.text(summary).css('color', summary === '（空）' ? '#bbb' : '#333');
                });

                $(document).on('click.odhero', widgetEl + ' .od-hero-upload-btn', function(e) {
                    e.preventDefault();
                    var $btn = $(this);
                    var frame = wp.media({
                        title: '<?php echo esc_js(__('选择轮播图片', 'onedown')); ?>',
                        button: {
                            text: '<?php echo esc_js(__('使用此图片', 'onedown')); ?>'
                        },
                        multiple: false
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        $btn.closest('span').find('.od-hero-img-input').val(attachment.url).trigger(
                            'input');
                    });
                    frame.open();
                });

                // ── 随机图片 ──
                var heroRandomImages = <?php echo json_encode(array(
                                            'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1300&q=82',
                                            'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=1300&q=82',
                                            'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1300&q=82',
                                            'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1300&q=82',
                                            'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1300&q=82',
                                            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=1300&q=82',
                                        )); ?>;
                $(document).on('click.odhero', '.od-hero-random-btn', function() {
                    var url = heroRandomImages[Math.floor(Math.random() * heroRandomImages.length)];
                    $(this).closest('span').find('.od-hero-img-input').val(url).trigger('input');
                });

                $(document).on('click.odhero', '.od-hero-add-slide', function() {
                    var namePrefix = $(this).data('prefix');
                    if (!namePrefix) return;
                    var $ctx = $(this).closest('.widget-content, .widget-inside');
                    if (!$ctx.length) return;
                    var $items = $ctx.find('.od-hero-slides');
                    var count = $items.find('.od-hero-slide-item').length;

                    var html =
                        '<div class="od-hero-slide-item" data-index="' + count +
                        '" style="background:#fff;margin-bottom:6px;border-radius:4px;border:1px solid #ddd;overflow:hidden;">' +
                        '<div class="od-hero-slide-header" style="display:flex;align-items:center;padding:8px 10px;cursor:pointer;user-select:none;gap:8px;background:#fafafa;">' +
                        '<span class="od-hero-slide-toggle" style="font-size:10px;color:#999;transition:transform .2s;">&#9654;</span>' +
                        '<span style="font-weight:500;font-size:12px;color:#666;flex-shrink:0;">#' + (count + 1) +
                        '</span>' +
                        '<span class="od-hero-slide-summary" style="font-size:12px;color:#bbb;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">（空）</span>' +
                        '<button type="button" class="button button-small od-hero-remove-slide" style="color:#a00;flex-shrink:0;font-size:11px;line-height:1.4;min-height:0;padding:2px 6px;">删除</button>' +
                        '</div>' +
                        '<div class="od-hero-slide-body" style="display:none;padding:8px 10px;border-top:1px solid #eee;">' +
                        '<div style="display:flex;gap:6px;margin-bottom:6px;align-items:center;">' +
                        '<select name="' + namePrefix + '[' + count + '][mode]" style="font-size:12px;">' +
                        '<option value="auto">图文混合</option>' +
                        '<option value="image">仅图片</option>' +
                        '<option value="text">仅文字</option>' +
                        '</select>' +
                        '</div>' +
                        '<p style="margin:0 0 4px;">' +
                        '<label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">图片（支持上传或输入 URL）：</label>' +
                        '<span style="display:flex;gap:4px;flex-wrap:wrap;">' +
                        '<input type="text" class="widefat od-hero-img-input" style="flex:1;font-size:13px;min-width:150px;" name="' +
                        namePrefix + '[' + count + '][img]" value="" placeholder="https://...">' +
                        '<button type="button" class="button od-hero-upload-btn" style="flex-shrink:0;"><span class="dashicons dashicons-upload" style="font-size:16px;width:16px;height:16px;margin:0;line-height:1.2;"></span></button>' +
                        '<button type="button" class="button od-hero-random-btn" style="flex-shrink:0;font-size:11px;">随机</button>' +
                        '</span>' +
                        '</p>' +
                        '<p style="margin:0 0 4px;">' +
                        '<label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">标题文字：</label>' +
                        '<input type="text" class="widefat od-hero-text-input" style="font-size:13px;" name="' +
                        namePrefix + '[' + count + '][text]" value="" placeholder="轮播标题">' +
                        '</p>' +
                        '<p style="margin:0;">' +
                        '<label style="font-size:12px;color:#666;display:block;margin-bottom:2px;">跳转链接：</label>' +
                        '<input type="url" class="widefat" style="font-size:13px;" name="' + namePrefix + '[' +
                        count + '][link]" value="" placeholder="https://...">' +
                        '</p>' +
                        '</div>' +
                        '</div>';

                    var $newItem = $(html);
                    $items.append($newItem);
                    $newItem.find('.od-hero-slide-header').trigger('click');
                });

                $(document).on('click.odhero', '.od-hero-remove-slide', function() {
                    var $ctx = $(this).closest('.widget-content, .widget-inside');
                    if (!$ctx.length) return;
                    $(this).closest('.od-hero-slide-item').remove();
                    $ctx.find('.od-hero-slide-item').each(function(idx) {
                        $(this).find('> .od-hero-slide-header > span:first').text('#' + (idx + 1));
                        $(this).attr('data-index', idx);
                        $(this).find('input, select').each(function() {
                            var name = $(this).attr('name');
                            if (name) {
                                name = name.replace(/\[\d+\]/, '[' + idx + ']');
                                $(this).attr('name', name);
                            }
                        });
                    });
                });
            });
        </script>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance              = $old_instance;
        $instance['title']     = wp_kses_post($new_instance['title']);
        $instance['source']    = in_array($new_instance['source'], array('posts', 'custom')) ? $new_instance['source'] : 'posts';
        $instance['number']    = min(10, max(1, (int) $new_instance['number']));
        $instance['category']  = (int) $new_instance['category'];

        // 处理自定义轮播
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
