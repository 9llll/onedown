<?php

/**
 * 推荐图文小组件
 *
 * 显示带缩略图的推荐文章/资源列表
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Featured');
}, 1);

class OD_Widget_Featured extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_featured',
            'description' => '显示带缩略图的推荐图文列表',
        );
        parent::__construct('od-featured', __('OD 推荐图文', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : '';
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $number    = ! empty($instance['number']) ? (int) $instance['number'] : 4;
        $orderby   = ! empty($instance['orderby']) ? $instance['orderby'] : 'date';
        $cat       = ! empty($instance['cat']) ? (int) $instance['cat'] : 0;

        $query_args = array(
            'posts_per_page'      => $number,
            'ignore_sticky_posts' => 1,
            'post_status'         => 'publish',
        );

        if ($orderby === 'rand') {
            $query_args['orderby'] = 'rand';
        } else {
            $query_args['orderby'] = 'date';
        }

        if ($cat > 0) {
            $query_args['cat'] = $cat;
        }

        $query = new WP_Query($query_args);

        if ($query->have_posts()) :
            echo '<div class="side-news-list">';
            while ($query->have_posts()) : $query->the_post();
                $thumb = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium') : '';
?>
                <a class="side-news-item" href="<?php the_permalink(); ?>">
                    <?php if ($thumb) : ?>
                        <span class="side-news-thumb">
                            <?php onedown_lazyload_img($thumb, get_the_title()); ?>
                        </span>
                    <?php endif; ?>
                    <span class="side-news-body">
                        <strong><?php the_title(); ?></strong>
                        <?php if (_pz('show_post_views', true)) : ?>
                            <span><i class="fa fa-eye"></i> <?php
                                                            $views = get_post_meta(get_the_ID(), 'views', true);
                                                            echo $views ? (int) $views : 0;
                                                            ?></span>
                        <?php endif; ?>
                    </span>
                </a>
        <?php
            endwhile;
            echo '</div>';
        endif;
        wp_reset_postdata();

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title   = ! empty($instance['title']) ? $instance['title'] : '';
        $number  = ! empty($instance['number']) ? (int) $instance['number'] : 4;
        $orderby = ! empty($instance['orderby']) ? $instance['orderby'] : 'date';
        $cat     = ! empty($instance['cat']) ? (int) $instance['cat'] : 0;
        ?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" placeholder="推荐图文"></label>
        </p>
        <p>
            <label>显示数量：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo esc_attr($number); ?>" min="1" max="10">
            </label>
        </p>
        <p>
            <label>排序：
                <select class="widefat" name="<?php echo $this->get_field_name('orderby'); ?>">
                    <option value="date" <?php selected($orderby, 'date'); ?>>最新发布</option>
                    <option value="rand" <?php selected($orderby, 'rand'); ?>>随机推荐</option>
                </select>
            </label>
        </p>
        <p>
            <label>分类限制：
                <?php wp_dropdown_categories(array(
                    'name' => $this->get_field_name('cat'),
                    'selected' => $cat,
                    'show_option_all' => '全部分类',
                    'class' => 'widefat',
                    'hide_empty' => 0,
                )); ?>
            </label>
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance               = $old_instance;
        $instance['title']      = wp_kses_post($new_instance['title']);
        $instance['number']     = (int) $new_instance['number'];
        $instance['orderby']    = $new_instance['orderby'];
        $instance['cat']        = (int) $new_instance['cat'];
        return $instance;
    }
}
