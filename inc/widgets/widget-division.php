<?php

/**
 * 专题展示小组件
 *
 * 显示专题网格（缩略图+名称+描述），数据来自"专题"分类法
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Division');
}, 1);

class OD_Widget_Division extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_division',
            'description' => '显示专题网格（取自「专题」分类法）',
        );
        parent::__construct('od-division', __('OD 专题展示', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        $title    = ! empty($instance['title']) ? $instance['title'] : '';
        $more_url = ! empty($instance['more_url']) ? $instance['more_url'] : home_url('/topic/');
        $limit    = ! empty($instance['limit']) ? min(12, max(1, (int) $instance['limit'])) : 4;
        $selected = ! empty($instance['selected_topics']) ? (array) $instance['selected_topics'] : array();

        // 获取专题列表
        $topics = get_terms(array(
            'taxonomy'   => 'topic',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'number'     => $limit,
            'include'    => ! empty($selected) ? array_map('intval', $selected) : array(),
        ));

        if (empty($topics) || is_wp_error($topics)) {
            $topics = array();
        }

        if (empty($selected)) {
            $topics = array_slice($topics, 0, $limit);
        }
?>
        <section class="division" aria-label="文章专题">
            <div class="division-head">
                <?php if ($title) : ?>
                    <h2><i class="fa fa-th-large"></i> <?php echo esc_html($title); ?></h2>
                <?php endif; ?>
                <?php if ($more_url && ! is_wp_error($more_url)) : ?>
                    <a href="<?php echo esc_url($more_url); ?>">全部专题 <i class="fa fa-angle-right"></i></a>
                <?php endif; ?>
            </div>
            <div class="division-grid">
                <?php foreach ($topics as $topic) :
                    $image = onedown_get_topic_image($topic->term_id);
                    $link  = get_term_link($topic);
                    if (is_wp_error($link)) {
                        $link = '#';
                    }
                    $desc = term_description($topic->term_id);
                    $desc = $desc ? wp_trim_words(wp_strip_all_tags($desc), 10, '...') : '';
                    $count = $topic->count;
                ?>
                    <a class="division-item" href="<?php echo esc_url($link); ?>">
                        <span class="division-icon">
                            <?php if ($image) : ?>
                                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($topic->name); ?>"
                                    style="width:24px;height:24px;object-fit:cover;border-radius:4px;">
                            <?php else : ?>
                                <i class="fa fa-folder-open"></i>
                            <?php endif; ?>
                        </span>
                        <strong><?php echo esc_html($topic->name); ?></strong>
                        <span><?php echo $desc ? esc_html($desc) : sprintf(__('%d 篇内容', 'onedown'), $count); ?></span>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($topics)) : ?>
                    <a class="division-item" style="cursor:default;opacity:0.5;">
                        <span class="division-icon"><i class="fa fa-folder-open"></i></span>
                        <strong><?php _e('暂无专题', 'onedown'); ?></strong>
                        <span><?php _e('请先在后台添加专题', 'onedown'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </section>
    <?php
    }

    public function form($instance)
    {
        $title    = ! empty($instance['title']) ? $instance['title'] : '文章专题';
        $more_url = ! empty($instance['more_url']) ? $instance['more_url'] : '';
        $limit    = ! empty($instance['limit']) ? (int) $instance['limit'] : 4;
        $selected = ! empty($instance['selected_topics']) ? (array) $instance['selected_topics'] : array();

        $all_topics = get_terms(array(
            'taxonomy'   => 'topic',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));
    ?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>"
                    value="<?php echo esc_attr($title); ?>"></label>
        </p>
        <p>
            <label>"全部专题"链接：<input type="url" class="widefat" name="<?php echo $this->get_field_name('more_url'); ?>"
                    value="<?php echo esc_attr($more_url); ?>" placeholder="留空自动使用专题首页"></label>
        </p>
        <p>
            <label>显示数量：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('limit'); ?>"
                    value="<?php echo esc_attr($limit); ?>" min="1" max="12">
            </label>
        </p>
        <?php if (! empty($all_topics) && ! is_wp_error($all_topics)) : ?>
            <p><strong>选择要显示的专题（不选则按数量显示最新）</strong></p>
            <div style="max-height:200px;overflow-y:auto;border:1px solid #ddd;padding:6px;border-radius:3px;">
                <?php foreach ($all_topics as $topic) : ?>
                    <label style="display:block;padding:3px 0;">
                        <input type="checkbox" name="<?php echo $this->get_field_name('selected_topics'); ?>[]"
                            value="<?php echo esc_attr($topic->term_id); ?>" <?php checked(in_array($topic->term_id, $selected)); ?>>
                        <?php echo esc_html($topic->name); ?> (<?php echo $topic->count; ?>)
                    </label>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p style="color:#999;">暂无专题，请先在「专题」中添加。</p>
        <?php endif; ?>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance                 = $old_instance;
        $instance['title']        = wp_kses_post($new_instance['title']);
        $instance['more_url']     = esc_url_raw($new_instance['more_url']);
        $instance['limit']        = min(12, max(1, (int) $new_instance['limit']));
        $instance['selected_topics'] = ! empty($new_instance['selected_topics']) ? array_map('intval', (array) $new_instance['selected_topics']) : array();
        return $instance;
    }
}
