<?php

/**
 * 文档链接列表小组件
 *
 * 以目录式列表展示文档/专题链接，支持图标和分类分组
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Docs');
}, 1);

class OD_Widget_Docs extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_docs',
            'description' => '显示文档/专题链接列表，支持自定义链接',
        );
        parent::__construct('od-docs', __('OD 文档列表', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : '';
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $links = ! empty($instance['links']) ? explode("\n", $instance['links']) : array();
        $links = array_map('trim', $links);
        $links = array_filter($links);

        if (! empty($links)) : ?>
            <div class="doc-list">
                <?php foreach ($links as $link) :
                    $parts = explode('||', $link);
                    $url   = ! empty($parts[0]) ? trim($parts[0]) : '#';
                    $label = ! empty($parts[1]) ? trim($parts[1]) : $url;
                    $icon  = ! empty($parts[2]) ? trim($parts[2]) : 'fa-file-text-o';
                ?>
                    <a href="<?php echo esc_url($url); ?>">
                        <span><i class="fa <?php echo esc_attr($icon); ?>"></i> <?php echo esc_html($label); ?></span>
                        <i class="fa fa-angle-right"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="doc-list">
                <a href="#"><span><i class="fa fa-file-text-o"></i> 文档示例</span><i class="fa fa-angle-right"></i></a>
            </div>
        <?php endif;

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title = ! empty($instance['title']) ? $instance['title'] : '';
        $links = ! empty($instance['links']) ? $instance['links'] : '';
        ?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" placeholder="主题文档"></label>
        </p>
        <p>
            <label>链接列表（每行一条，格式：URL||名称||图标）：<br>
                <textarea class="widefat" rows="6" name="<?php echo $this->get_field_name('links'); ?>" placeholder="#||安装配置教程||fa-cog"><?php echo esc_textarea($links); ?></textarea>
            </label>
            <span class="description" style="color:#999;font-size:12px;">图标从 Font Awesome 获取，如 fa-cog、fa-shopping-cart</span>
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance             = $old_instance;
        $instance['title']    = wp_kses_post($new_instance['title']);
        $instance['links']    = sanitize_textarea_field($new_instance['links']);
        return $instance;
    }
}
