<?php

/**
 * 标签云小组件
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Tags');
}, 1);

class OD_Widget_Tags extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_tags',
            'description' => '显示标签云/分类云',
        );
        parent::__construct('od-tags', __('OD 标签云', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : '';
        $taxonomy  = ! empty($instance['taxonomy']) ? $instance['taxonomy'] : 'post_tag';
        if ($title) {
            $more_url = 'post_tag' === $taxonomy ? home_url('/tags/') : '';
            echo $args['before_title'] . '<span>' . $title . '</span>';
            if ($more_url) {
                echo '<a class="widget-title-more" href="' . esc_url($more_url) . '">&#26356;&#22810;</a>';
            }
            echo $args['after_title'];
        }

        $number    = ! empty($instance['number']) ? (int) $instance['number'] : 12;
        $orderby   = ! empty($instance['orderby']) ? $instance['orderby'] : 'count';
        $show_count = ! empty($instance['show_count']);

        $terms = get_terms(array(
            'taxonomy'   => $taxonomy,
            'orderby'    => $orderby,
            'order'      => 'DESC',
            'number'     => $number,
            'hide_empty' => false,
        ));

        if (! empty($terms) && ! is_wp_error($terms)) {
            echo '<div class="tag-cloud">';
            $i      = 0;
            foreach ($terms as $term) {
                $link = esc_url(get_term_link($term));
                $name = esc_attr($term->name);
                $color_class = 'tag-color-' . ($i % 5);
                $i++;
                $count = $show_count ? ' <span class="em09">(' . $term->count . ')</span>' : '';
                echo '<a href="' . $link . '" class="' . $color_class . '">' . $name . $count . '</a>';
            }
            echo '</div>';
        }

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title      = ! empty($instance['title']) ? $instance['title'] : '';
        $taxonomy   = ! empty($instance['taxonomy']) ? $instance['taxonomy'] : 'post_tag';
        $number     = ! empty($instance['number']) ? (int) $instance['number'] : 12;
        $orderby    = ! empty($instance['orderby']) ? $instance['orderby'] : 'count';
        $show_count = ! empty($instance['show_count']);
?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" placeholder="标签云"></label>
        </p>
        <p>
            <label>显示数量：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo esc_attr($number); ?>" min="1" max="100">
            </label>
        </p>
        <p>
            <label>分类法：
                <select class="widefat" name="<?php echo $this->get_field_name('taxonomy'); ?>">
                    <option value="post_tag" <?php selected($taxonomy, 'post_tag'); ?>>标签</option>
                    <option value="category" <?php selected($taxonomy, 'category'); ?>>分类</option>
                </select>
            </label>
        </p>
        <p>
            <label>排序：
                <select class="widefat" name="<?php echo $this->get_field_name('orderby'); ?>">
                    <option value="count" <?php selected($orderby, 'count'); ?>>按数量</option>
                    <option value="name" <?php selected($orderby, 'name'); ?>>按名称</option>
                </select>
            </label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_count); ?> name="<?php echo $this->get_field_name('show_count'); ?>"> 显示文章数量</label>
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance               = $old_instance;
        $instance['title']      = wp_kses_post($new_instance['title']);
        $instance['number']     = (int) $new_instance['number'];
        $instance['taxonomy']   = $new_instance['taxonomy'];
        $instance['orderby']    = $new_instance['orderby'];
        $instance['show_count'] = ! empty($new_instance['show_count']) ? 1 : 0;
        return $instance;
    }
}
