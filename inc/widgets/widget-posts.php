<?php

/**
 * 文章列表小组件
 *
 * 支持多种显示模式：标准列表、排行榜、网格卡片
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Posts');
    register_widget('OD_Widget_Hot_Posts');
}, 1);

/**
 * 标准文章列表
 */
class OD_Widget_Posts extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_posts',
            'description' => '显示文章列表，支持分类/排序/多种显示模式',
        );
        parent::__construct('od-posts', __('OD 文章列表', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : '';
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $number    = ! empty($instance['number']) ? (int) $instance['number'] : 6;
        $orderby   = ! empty($instance['orderby']) ? $instance['orderby'] : 'date';
        $cat       = ! empty($instance['cat']) ? (int) $instance['cat'] : 0;
        $style     = ! empty($instance['style']) ? $instance['style'] : 'list';
        $show_date = ! empty($instance['show_date']);
        $show_thumb = ! empty($instance['show_thumb']);
        $days      = ! empty($instance['days']) ? (int) $instance['days'] : 0;

        $query_args = array(
            'posts_per_page'      => $number,
            'ignore_sticky_posts' => 1,
            'post_status'         => 'publish',
            'order'               => 'DESC',
        );

        // 排序
        switch ($orderby) {
            case 'views':
                $query_args['orderby'] = 'meta_value_num';
                $query_args['meta_key'] = 'views';
                break;
            case 'comment_count':
                $query_args['orderby'] = 'comment_count';
                break;
            case 'rand':
                $query_args['orderby'] = 'rand';
                break;
            default:
                $query_args['orderby'] = 'date';
        }

        // 分类筛选
        if ($cat > 0) {
            $query_args['cat'] = $cat;
        }

        // 时间限制
        if ($days > 0) {
            $query_args['date_query'] = array(array(
                'after' => date('Y-m-d H:i:s', strtotime("-{$days} days")),
            ));
        }

        $query = new WP_Query($query_args);

        if ($query->have_posts()) :
            if ($style === 'rank') {
                echo '<div class="rank-list">';
                $index = 1;
                while ($query->have_posts()) : $query->the_post();
                    $style_attr = $index <= 3 ? '' : '';
                    $od_views  = (int) get_post_meta(get_the_ID(), 'views', true);
                    $od_latest = get_post_time('U', true) >= strtotime('-3 days');
?>
                    <a class="rank-item" href="<?php the_permalink(); ?>" <?php echo $style_attr; ?>>
                        <span class="rank-body">
                            <?php if (is_sticky()) : ?><span class="status-tag tag-sticky">置顶</span><?php elseif ($od_views >= 1000) : ?><span class="status-tag tag-hot">热门</span><?php elseif ($od_latest) : ?><span class="status-tag tag-new">最新</span><?php endif; ?>
                            <strong><?php the_title(); ?></strong>
                            <?php if ($show_date) : ?>
                                <span class="rank-meta">
                                    <span><i class="fa fa-clock-o"></i> <?php echo esc_html(get_the_date()); ?></span>
                                </span>
                            <?php endif; ?>
                        </span>
                    </a>
                <?php
                    $index++;
                endwhile;
                echo '</div>';
            } else {
                // list 或 card 模式
                echo '<div class="doc-list">';
                while ($query->have_posts()) : $query->the_post();
                    $od_views  = (int) get_post_meta(get_the_ID(), 'views', true);
                    $od_latest = get_post_time('U', true) >= strtotime('-3 days');
                ?>
                    <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                        <?php if ($show_thumb && has_post_thumbnail()) : ?>
                            <span class="doc-thumb"><?php onedown_lazyload_img(get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'), get_the_title(), array('class' => 'doc-thumb-img')); ?></span>
                        <?php endif; ?>
                        <span class="doc-title">
                            <?php if (is_sticky()) : ?><span class="status-tag tag-sticky">置顶</span><?php elseif ($od_views >= 1000) : ?><span class="status-tag tag-hot">热门</span><?php elseif ($od_latest) : ?><span class="status-tag tag-new">最新</span><?php endif; ?>
                            <?php the_title(); ?>
                        </span>
                        <?php if ($show_date) : ?>
                            <span><?php echo esc_html(get_the_date()); ?></span>
                        <?php endif; ?>
                    </a>
        <?php
                endwhile;
                echo '</div>';
            }
        endif;
        wp_reset_postdata();

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title     = ! empty($instance['title']) ? $instance['title'] : '';
        $number    = ! empty($instance['number']) ? (int) $instance['number'] : 6;
        $orderby   = ! empty($instance['orderby']) ? $instance['orderby'] : 'date';
        $cat       = ! empty($instance['cat']) ? (int) $instance['cat'] : 0;
        $style     = ! empty($instance['style']) ? $instance['style'] : 'list';
        $show_date = ! empty($instance['show_date']);
        $show_thumb = ! empty($instance['show_thumb']);
        $days      = ! empty($instance['days']) ? (int) $instance['days'] : 0;
        ?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" placeholder="文章列表"></label>
        </p>
        <p>
            <label>显示数量：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo esc_attr($number); ?>" min="1" max="20">
            </label>
        </p>
        <p>
            <label>显示样式：
                <select class="widefat" name="<?php echo $this->get_field_name('style'); ?>">
                    <option value="list" <?php selected($style, 'list'); ?>>标准列表</option>
                    <option value="rank" <?php selected($style, 'rank'); ?>>排行榜</option>
                </select>
            </label>
        </p>
        <p>
            <label>排序：
                <select class="widefat" name="<?php echo $this->get_field_name('orderby'); ?>">
                    <option value="date" <?php selected($orderby, 'date'); ?>>最新发布</option>
                    <option value="comment_count" <?php selected($orderby, 'comment_count'); ?>>最多评论</option>
                    <option value="views" <?php selected($orderby, 'views'); ?>>最多阅读</option>
                    <option value="rand" <?php selected($orderby, 'rand'); ?>>随机</option>
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
        <p>
            <label>时间限制（天，0为不限）：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('days'); ?>" value="<?php echo esc_attr($days); ?>" min="0" max="365">
            </label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_date); ?> name="<?php echo $this->get_field_name('show_date'); ?>"> 显示日期</label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_thumb); ?> name="<?php echo $this->get_field_name('show_thumb'); ?>"> 显示缩略图（仅列表模式）</label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance                = $old_instance;
        $instance['title']       = wp_kses_post($new_instance['title']);
        $instance['number']      = (int) $new_instance['number'];
        $instance['orderby']     = $new_instance['orderby'];
        $instance['cat']         = (int) $new_instance['cat'];
        $instance['style']       = $new_instance['style'];
        $instance['show_date']   = ! empty($new_instance['show_date']) ? 1 : 0;
        $instance['show_thumb']  = ! empty($new_instance['show_thumb']) ? 1 : 0;
        $instance['days']        = (int) $new_instance['days'];
        return $instance;
    }
}

/**
 * 热榜文章 - 简化版排行样式
 */
class OD_Widget_Hot_Posts extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_hot_posts',
            'description' => '显示热门文章排行榜',
        );
        parent::__construct('od-hot-posts', __('OD 热榜文章', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : '';
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $number  = ! empty($instance['number']) ? (int) $instance['number'] : 5;
        $orderby = ! empty($instance['orderby']) ? $instance['orderby'] : 'views';
        $days    = ! empty($instance['days']) ? (int) $instance['days'] : 0;

        $cache_key = 'hot_posts_' . $orderby . '_' . $number . '_' . $days;

        $query_args = array(
            'posts_per_page'      => $number,
            'ignore_sticky_posts' => 1,
            'post_status'         => 'publish',
            'orderby'             => 'meta_value_num',
            'meta_key'            => $orderby,
            'order'               => 'DESC',
        );

        if ($days > 0) {
            $query_args['date_query'] = array(array(
                'after' => date('Y-m-d H:i:s', strtotime("-{$days} days")),
            ));
        }

        $query = onedown_cached_posts($cache_key, $query_args);

        if (! empty($query)) :
            echo '<div class="rank-list">';
            $i = 1;
            foreach ($query as $post) : setup_postdata($post);
        ?>
                <a class="rank-item" href="<?php the_permalink(); ?>">
                    <span class="rank-body">
                        <strong><?php the_title(); ?></strong>
                        <?php
                        $show_rank_meta = true;
                        if ('views' === $orderby && !_pz('show_post_views', true)) {
                            $show_rank_meta = false;
                        }
                        if ('likes' === $orderby && !_pz('show_post_likes', true)) {
                            $show_rank_meta = false;
                        }
                        ?>
                        <?php if ($show_rank_meta) : ?>
                            <span class="rank-meta">
                                <span><i class="fa fa-eye"></i> <?php
                                                                $views = get_post_meta(get_the_ID(), $orderby, true);
                                                                echo $views ? (int) $views : 0;
                                                                ?></span>
                            </span>
                        <?php endif; ?>
                    </span>
                </a>
        <?php
                $i++;
            endforeach;
            echo '</div>';
        endif;
        wp_reset_postdata();

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title   = ! empty($instance['title']) ? $instance['title'] : '';
        $number  = ! empty($instance['number']) ? (int) $instance['number'] : 5;
        $orderby = ! empty($instance['orderby']) ? $instance['orderby'] : 'views';
        $days    = ! empty($instance['days']) ? (int) $instance['days'] : 0;
        ?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" placeholder="热榜文章"></label>
        </p>
        <p>
            <label>显示数量：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo esc_attr($number); ?>" min="1" max="20">
            </label>
        </p>
        <p>
            <label>排序依据：
                <select class="widefat" name="<?php echo $this->get_field_name('orderby'); ?>">
                    <option value="views" <?php selected($orderby, 'views'); ?>>阅读量</option>
                    <option value="likes" <?php selected($orderby, 'likes'); ?>>点赞数</option>
                    <option value="favorite" <?php selected($orderby, 'favorite'); ?>>收藏数</option>
                </select>
            </label>
        </p>
        <p>
            <label>时间限制（天，0为不限）：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('days'); ?>" value="<?php echo esc_attr($days); ?>" min="0" max="365">
            </label>
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance              = $old_instance;
        $instance['title']     = wp_kses_post($new_instance['title']);
        $instance['number']    = (int) $new_instance['number'];
        $instance['orderby']   = $new_instance['orderby'];
        $instance['days']      = (int) $new_instance['days'];
        return $instance;
    }
}
