<?php

/**
 * 最近动态小组件
 *
 * 显示垂直滚动的动态更新列表，支持手动输入或自动获取最新文章
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Dynamic');
}, 1);

class OD_Widget_Dynamic extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_dynamic',
            'description' => '显示垂直滚动的最近动态列表',
        );
        parent::__construct('od-dynamic', __('OD 最近动态', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title    = ! empty($instance['title']) ? $instance['title'] : '';
        $subtitle = ! empty($instance['subtitle']) ? $instance['subtitle'] : '';
        $source   = ! empty($instance['source']) ? $instance['source'] : 'manual';
        $speed    = ! empty($instance['speed']) ? (int) $instance['speed'] : 30;

        // 获取动态项
        $items = array();

        if ($source === 'auto') {
            // 自动模式：从最新文章获取
            $number   = ! empty($instance['number']) ? (int) $instance['number'] : 8;
            $category = ! empty($instance['category']) ? (int) $instance['category'] : 0;

            $query_args = array(
                'posts_per_page'      => $number,
                'ignore_sticky_posts' => 1,
                'post_status'         => 'publish',
                'orderby'             => 'date',
                'order'               => 'DESC',
                'no_found_rows'       => true,
            );

            if ($category > 0) {
                $query_args['cat'] = $category;
            }

            $posts = get_posts($query_args);

            foreach ($posts as $post) {
                $cats = get_the_category($post->ID);
                $cat_name = ! empty($cats) ? $cats[0]->name : '未分类';
                $time_diff = human_time_diff(strtotime($post->post_date), current_time('timestamp'));

                $items[] = array(
                    'text'    => get_the_title($post->ID),
                    'timecat' => sprintf('%s前 · %s', $time_diff, $cat_name),
                    'url'     => get_permalink($post->ID),
                );
            }

            // 复制一份实现无缝滚动
            if (! empty($items)) {
                $items = array_merge($items, $items);
            }
        } else {
            // 手动模式：解析文本输入
            $raw = ! empty($instance['items']) ? explode("\n", $instance['items']) : array();
            $raw = array_map('trim', $raw);
            $raw = array_filter($raw);

            foreach ($raw as $line) {
                $parts = explode('||', $line);
                $item = array(
                    'text'    => ! empty($parts[0]) ? trim($parts[0]) : '',
                    'timecat' => ! empty($parts[1]) ? trim($parts[1]) : '',
                    'url'     => ! empty($parts[2]) ? trim($parts[2]) : '#',
                );
                if (! empty($item['text'])) {
                    $items[] = $item;
                }
            }
        }

        // 默认数据
        if (empty($items)) {
            $items = array(
                array('text' => '发布了首页轮播与统计卡片优化方案', 'timecat' => '2 分钟前 · 主题教程', 'url' => '#'),
                array('text' => '新增会员资源商城的权益展示示例',     'timecat' => '18 分钟前 · 资源商城', 'url' => '#'),
                array('text' => '社区收到 12 条关于导航模块的配置反馈', 'timecat' => '1 小时前 · 社区论坛', 'url' => '#'),
                array('text' => '更新了搜索横幅与分类分区模块样式',   'timecat' => '2 小时前 · 版本更新', 'url' => '#'),
                array('text' => '发布了首页轮播与统计卡片优化方案',   'timecat' => '2 分钟前 · 主题教程', 'url' => '#'),
                array('text' => '新增会员资源商城的权益展示示例',     'timecat' => '18 分钟前 · 资源商城', 'url' => '#'),
                array('text' => '社区收到 12 条关于导航模块的配置反馈', 'timecat' => '1 小时前 · 社区论坛', 'url' => '#'),
                array('text' => '更新了搜索横幅与分类分区模块样式',   'timecat' => '2 小时前 · 版本更新', 'url' => '#'),
            );
        }

        if ($title || $subtitle) : ?>
            <div class="dynamic-head">
                <?php if ($title) : ?>
                    <h2><i class="fa fa-bolt"></i> <?php echo esc_html($title); ?></h2>
                <?php endif; ?>
                <?php if ($subtitle) : ?>
                    <span><?php echo esc_html($subtitle); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="dynamic-viewport">
            <div class="dynamic-track" data-speed="<?php echo esc_attr($speed); ?>">
                <?php foreach ($items as $item) : ?>
                    <a class="dynamic-item" href="<?php echo esc_url($item['url']); ?>">
                        <span class="dynamic-dot"></span>
                        <div>
                            <strong><?php echo esc_html($item['text']); ?></strong>
                            <span><?php echo esc_html($item['timecat']); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title    = ! empty($instance['title']) ? $instance['title'] : '最近动态';
        $subtitle = ! empty($instance['subtitle']) ? $instance['subtitle'] : '社区与资源更新';
        $source   = ! empty($instance['source']) ? $instance['source'] : 'manual';
        $items    = ! empty($instance['items']) ? $instance['items'] : '';
        $speed    = ! empty($instance['speed']) ? (int) $instance['speed'] : 30;
        $number   = ! empty($instance['number']) ? (int) $instance['number'] : 8;
        $category = ! empty($instance['category']) ? (int) $instance['category'] : 0;
    ?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>"></label>
        </p>
        <p>
            <label>副标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('subtitle'); ?>" value="<?php echo esc_attr($subtitle); ?>"></label>
        </p>
        <p>
            <label>数据来源：
                <select class="widefat od-dynamic-source" name="<?php echo $this->get_field_name('source'); ?>">
                    <option value="manual" <?php selected($source, 'manual'); ?>>手动输入</option>
                    <option value="auto" <?php selected($source, 'auto'); ?>>自动获取最新文章</option>
                </select>
            </label>
        </p>
        <div class="od-dynamic-manual" <?php echo $source !== 'manual' ? 'style="display:none;"' : ''; ?>>
            <p>
                <label>动态列表（每行一条，格式：文本||时间·分类||链接）：<br>
                    <textarea class="widefat" rows="8" name="<?php echo $this->get_field_name('items'); ?>" placeholder="发布了首页轮播与统计卡片优化方案||2 分钟前 · 主题教程||#"><?php echo esc_textarea($items); ?></textarea>
                </label>
            </p>
        </div>
        <div class="od-dynamic-auto" <?php echo $source !== 'auto' ? 'style="display:none;"' : ''; ?>>
            <p>
                <label>显示数量：
                    <input type="number" class="widefat" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo esc_attr($number); ?>" min="1" max="20">
                </label>
            </p>
            <p>
                <label>分类限制：
                    <?php
                    wp_dropdown_categories(array(
                        'name'              => $this->get_field_name('category'),
                        'selected'          => $category,
                        'show_option_all'   => '全部分类',
                        'class'             => 'widefat',
                        'hide_empty'        => 0,
                    ));
                    ?>
                </label>
            </p>
        </div>
        <p>
            <label>滚动速度（数值越大越慢，0 为不滚动）：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('speed'); ?>" value="<?php echo esc_attr($speed); ?>" min="0" max="100">
            </label>
        </p>
        <script>
            jQuery(function($) {
                $(document).on('change', '.od-dynamic-source', function() {
                    var val = $(this).val();
                    var $widget = $(this).closest('.widget-content, .widget-inside');
                    $widget.find('.od-dynamic-manual').toggle(val === 'manual');
                    $widget.find('.od-dynamic-auto').toggle(val === 'auto');
                });
            });
        </script>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance                = $old_instance;
        $instance['title']       = wp_kses_post($new_instance['title']);
        $instance['subtitle']    = wp_kses_post($new_instance['subtitle']);
        $instance['source']      = in_array($new_instance['source'], array('manual', 'auto')) ? $new_instance['source'] : 'manual';
        $instance['items']       = sanitize_textarea_field($new_instance['items']);
        $instance['speed']       = min(100, max(0, (int) $new_instance['speed']));
        $instance['number']      = min(20, max(1, (int) $new_instance['number']));
        $instance['category']    = (int) $new_instance['category'];
        return $instance;
    }
}
