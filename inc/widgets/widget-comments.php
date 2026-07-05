<?php

/**
 * 最新评论小组件
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Comments');
}, 1);

class OD_Widget_Comments extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_comments',
            'description' => '显示最新网友评论',
        );
        parent::__construct('od-comments', __('OD 最新评论', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : '';
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $number = ! empty($instance['number']) ? (int) $instance['number'] : 6;
        $avatar = ! empty($instance['show_avatar']);
        $excerpt_len = ! empty($instance['excerpt_len']) ? (int) $instance['excerpt_len'] : 30;

        $comments = get_comments(array(
            'number' => $number,
            'status' => 'approve',
            'type'   => 'comment',
        ));

        if ($comments) : ?>
            <div class="comment-list">
                <?php foreach ($comments as $comment) : ?>
                    <div class="comment-item">
                        <?php if ($avatar) : ?>
                            <div class="comment-avatar">
                                <?php echo get_avatar($comment->comment_author_email, 36, '', $comment->comment_author); ?>
                            </div>
                        <?php endif; ?>
                        <div class="comment-body">
                            <div class="comment-author">
                                <strong><?php echo esc_html($comment->comment_author); ?></strong>
                                <span class="comment-date"><?php echo esc_html(human_time_diff(strtotime($comment->comment_date), current_time('timestamp'))) . '前'; ?></span>
                            </div>
                            <div class="comment-text">
                                <a href="<?php echo esc_url(get_comment_link($comment)); ?>" title="查看评论详情" aria-label="查看评论详情">
                                    <?php echo esc_html(mb_substr(strip_tags($comment->comment_content), 0, $excerpt_len)); ?>
                                    <?php if (mb_strlen(strip_tags($comment->comment_content)) > $excerpt_len) : ?>...<?php endif; ?>
                                </a>
                            </div>
                            <div class="comment-post">来自：<?php echo get_the_title($comment->comment_post_ID); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="muted-color">暂无评论</p>
        <?php endif;

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title       = ! empty($instance['title']) ? $instance['title'] : '';
        $number      = ! empty($instance['number']) ? (int) $instance['number'] : 6;
        $avatar      = ! empty($instance['show_avatar']);
        $excerpt_len = ! empty($instance['excerpt_len']) ? (int) $instance['excerpt_len'] : 30;
        ?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" placeholder="最新评论"></label>
        </p>
        <p>
            <label>显示数量：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo esc_attr($number); ?>" min="1" max="20">
            </label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($avatar); ?> name="<?php echo $this->get_field_name('show_avatar'); ?>"> 显示头像</label>
        </p>
        <p>
            <label>评论截取长度：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('excerpt_len'); ?>" value="<?php echo esc_attr($excerpt_len); ?>" min="10" max="100">
            </label>
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance                = $old_instance;
        $instance['title']       = wp_kses_post($new_instance['title']);
        $instance['number']      = (int) $new_instance['number'];
        $instance['show_avatar'] = ! empty($new_instance['show_avatar']) ? 1 : 0;
        $instance['excerpt_len'] = (int) $new_instance['excerpt_len'];
        return $instance;
    }
}
