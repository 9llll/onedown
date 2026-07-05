<?php

/**
 * 文章目录小组件
 *
 * 自动解析文章内容中的标题标签生成目录，仅详情页有效
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_TOC');
}, 1);

class OD_Widget_TOC extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_toc',
            'description' => '自动解析文章标题生成目录，仅详情页有效',
        );
        parent::__construct('od-toc', __('OD 文章目录', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        // 仅在文章详情页显示
        if (! is_singular('post')) {
            return;
        }

        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : '';
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        global $post;
        if (! $post || empty($post->post_content)) {
            echo '<p class="muted-color">暂无目录</p>';
            echo $args['after_widget'];
            return;
        }

        // 匹配 h2-h3 标题
        preg_match_all('/<h([2-3])(?:\s+[^>]*)?>(.*?)<\/h\1>/i', $post->post_content, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            echo '<p class="muted-color">暂无目录</p>';
            echo $args['after_widget'];
            return;
        }

        $min_level = 2;
        echo '<div class="article-toc">';
        foreach ($matches as $match) {
            $level = (int) $match[1];
            $text  = strip_tags($match[2]);
            $anchor = 'heading-' . sanitize_title($text);
            $class  = $level === 3 ? ' class="toc-h3"' : '';
            echo '<a href="#' . esc_attr($anchor) . '"' . $class . '>' . esc_html($text) . '</a>';
        }
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = ! empty($instance['title']) ? $instance['title'] : '';
?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" placeholder="文章目录"></label>
        </p>
        <p>
            <span class="description" style="color:#999;font-size:12px;">自动解析文章中的 h2、h3 标题生成目录，仅在文章详情页侧边栏显示。</span>
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance              = $old_instance;
        $instance['title']     = wp_kses_post($new_instance['title']);
        return $instance;
    }
}
