<?php

/**
 * 分类文章网格小工具
 *
 * 以卡片网格形式展示各分类的文章，支持分类筛选和标签过滤
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Category_Posts');
}, 1);

class OD_Widget_Category_Posts extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_category_posts',
            'description' => '以卡片网格展示分类文章，支持按分类筛选',
        );
        parent::__construct('od-category-posts', __('OD 分类文章网格', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        $widget_title = ! empty($instance['title']) ? $instance['title'] : '';

        $number     = ! empty($instance['number']) ? (int) $instance['number'] : 8;
        $cat        = ! empty($instance['cat']) ? (int) $instance['cat'] : 0;
        $show_head  = ! empty($instance['show_head']);
        $head_title = ! empty($instance['head_title']) ? $instance['head_title'] : '分类文章';
        $head_link  = ! empty($instance['head_link']) ? $instance['head_link'] : '';
        $orderby    = ! empty($instance['orderby']) ? $instance['orderby'] : 'date';
        $show_tag   = ! empty($instance['show_tag']);
        $show_meta  = ! empty($instance['show_meta']);

        $show_pagination = ! empty($instance['show_pagination']);
        $paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));

        // 自动生成"更多"链接
        if ($head_link) {
            $more_link = $head_link;
        } elseif ($cat > 0) {
            $more_link = get_category_link($cat);
        } else {
            $more_link = '';
        }

        $query_args = array(
            'posts_per_page'      => $number,
            'ignore_sticky_posts' => 0,
            'post_status'         => 'publish',
            'paged'               => $paged,
        );

        if ($cat > 0) {
            $query_args['cat'] = $cat;
        }

        switch ($orderby) {
            case 'rand':
                $query_args['orderby'] = 'rand';
                break;
            case 'views':
                $query_args['orderby'] = 'meta_value_num';
                $query_args['meta_key'] = 'views';
                break;
            case 'comment_count':
                $query_args['orderby'] = 'comment_count';
                break;
            default:
                $query_args['orderby'] = 'date';
        }

        $query = new WP_Query($query_args);

        if ($query->have_posts()) :
?>
            <section class="category-posts" aria-label="<?php echo esc_attr($head_title ? $head_title : '分类文章列表'); ?>">
                <div class="category-posts-head">
                    <h2><i class="fa fa-folder-open-o"></i>
                        <?php echo esc_html($show_head ? $head_title : ($widget_title ? $widget_title : '分类文章')); ?></h2>
                    <?php if ($more_link) : ?>
                        <a href="<?php echo esc_url($more_link); ?>">更多分类 <i class="fa fa-angle-right"></i></a>
                    <?php endif; ?>
                </div>
                <div class="category-post-grid">
                    <?php while ($query->have_posts()) : $query->the_post();
                        $cat_names = wp_get_post_categories(get_the_ID(), array('fields' => 'names'));
                        $cat_name  = ! empty($cat_names) ? $cat_names[0] : '';
                        $thumb     = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium_large') : '';
                        $sticky    = is_sticky();
                        $latest    = get_post_time('U', true) >= strtotime('-3 days');
                        $views     = (int) get_post_meta(get_the_ID(), 'views', true);
                        $comments  = get_comments_number();
                        $meta_time = human_time_diff(get_the_time('U'), current_time('timestamp'));
                        $meta_time = $meta_time ? $meta_time . '前' : '刚刚';
                    ?>
                        <article class="category-post-card">
                            <a class="category-post-cover" href="<?php the_permalink(); ?>">
                                <?php if ($thumb) : ?>
                                    <?php onedown_lazyload_img($thumb, the_title_attribute(array('echo' => false))); ?>
                                <?php else : ?>
                                    <?php onedown_lazyload_img(onedown_fallback_thumb_url(get_the_ID(), 700, 394), the_title_attribute(array('echo' => false))); ?>
                                <?php endif; ?>
                                <?php if ($show_tag && $cat_name) : ?>
                                    <span><?php echo esc_html($cat_name); ?></span>
                                <?php endif; ?>
                                <?php if ($sticky) : ?>
                                    <span class="status-tag tag-sticky">置顶</span>
                                <?php elseif ($views >= 1000) : ?>
                                    <span class="status-tag tag-hot">热门</span>
                                <?php elseif ($latest) : ?>
                                    <span class="status-tag tag-new">最新</span>
                                <?php endif; ?>
                            </a>
                            <div class="category-post-body">
                                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <?php if ($show_meta) : ?>
                                    <div class="category-post-meta">
                                        <span><i class="fa fa-clock-o"></i> <?php echo esc_html($meta_time); ?></span>
                                        <?php if ($views && _pz('show_post_views', true)) : ?>
                                            <span><i class="fa fa-eye"></i> <?php echo esc_html(number_format($views)); ?></span>
                                        <?php endif; ?>
                                        <?php if ($comments) : ?>
                                            <span><i class="fa fa-comment-o"></i> <?php echo esc_html($comments); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                <?php if ($show_pagination && $query->max_num_pages > 1) : ?>
                    <div class="category-pagination">
                        <?php onedown_render_pagination($query); ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php
        endif;
        wp_reset_postdata();
    }

    public function form($instance)
    {
        $title      = ! empty($instance['title']) ? $instance['title'] : '';
        $number     = ! empty($instance['number']) ? (int) $instance['number'] : 8;
        $cat        = ! empty($instance['cat']) ? (int) $instance['cat'] : 0;
        $show_head  = ! empty($instance['show_head']);
        $head_title = ! empty($instance['head_title']) ? $instance['head_title'] : '分类文章';
        $head_link  = ! empty($instance['head_link']) ? $instance['head_link'] : '';
        $orderby    = ! empty($instance['orderby']) ? $instance['orderby'] : 'date';
        $show_tag   = ! empty($instance['show_tag']);
        $show_meta  = ! empty($instance['show_meta']);
        $show_pagination = ! empty($instance['show_pagination']);
        ?>
        <p>
            <label>标题（仅后台标识）：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>"
                    value="<?php echo esc_attr($title); ?>" placeholder="分类文章网格"></label>
        </p>
        <p>
            <label>显示数量：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('number'); ?>"
                    value="<?php echo esc_attr($number); ?>" min="1" max="20">
            </label>
        </p>
        <p>
            <label>排序：
                <select class="widefat" name="<?php echo $this->get_field_name('orderby'); ?>">
                    <option value="date" <?php selected($orderby, 'date'); ?>>最新发布</option>
                    <option value="rand" <?php selected($orderby, 'rand'); ?>>随机</option>
                    <option value="views" <?php selected($orderby, 'views'); ?>>最多阅读</option>
                    <option value="comment_count" <?php selected($orderby, 'comment_count'); ?>>最多评论</option>
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
            <label><input type="checkbox" class="checkbox" <?php checked($show_head); ?>
                    name="<?php echo $this->get_field_name('show_head'); ?>"> 显示头部标题栏</label>
        </p>
        <p>
            <label>头部标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('head_title'); ?>"
                    value="<?php echo esc_attr($head_title); ?>" placeholder="分类文章"></label>
        </p>
        <p>
            <label>头部更多链接：<input type="url" class="widefat" name="<?php echo $this->get_field_name('head_link'); ?>"
                    value="<?php echo esc_attr($head_link); ?>" placeholder="https://"></label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_tag); ?>
                    name="<?php echo $this->get_field_name('show_tag'); ?>"> 显示分类标签</label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_meta); ?>
                    name="<?php echo $this->get_field_name('show_meta'); ?>"> 显示文章元信息</label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_pagination); ?>
                    name="<?php echo $this->get_field_name('show_pagination'); ?>"> 显示分页</label>
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance               = $old_instance;
        $instance['title']      = wp_kses_post($new_instance['title']);
        $instance['number']     = (int) $new_instance['number'];
        $instance['cat']        = (int) $new_instance['cat'];
        $instance['show_head']  = ! empty($new_instance['show_head']) ? 1 : 0;
        $instance['head_title'] = wp_kses_post($new_instance['head_title']);
        $instance['head_link']   = esc_url_raw($new_instance['head_link']);
        $instance['orderby']    = in_array($new_instance['orderby'], array('date', 'rand', 'views', 'comment_count'), true) ? $new_instance['orderby'] : 'date';
        $instance['show_tag']   = ! empty($new_instance['show_tag']) ? 1 : 0;
        $instance['show_meta']  = ! empty($new_instance['show_meta']) ? 1 : 0;
        $instance['show_pagination'] = ! empty($new_instance['show_pagination']) ? 1 : 0;
        return $instance;
    }
}
