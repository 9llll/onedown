<?php

/**
 * 用户资料卡片小组件
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Profile');
}, 1);

class OD_Widget_Profile extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_profile',
            'description' => '显示当前用户的资料卡片，未登录显示登录入口',
        );
        parent::__construct('od-profile', __('OD 用户资料', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        echo str_replace('class="', 'style="padding:0" class="', $args['before_widget']);

        $show_vip   = ! empty($instance['show_vip']);
        $show_posts = ! empty($instance['show_posts']);
        $show_count = ! empty($instance['show_count']) ? (int) $instance['show_count'] : 5;

        // 背景图模式：random | upload | url
        $bg_mode       = ! empty($instance['bg_mode']) ? $instance['bg_mode'] : 'random';
        $bg_image_id   = ! empty($instance['bg_image_id']) ? (int) $instance['bg_image_id'] : 0;
        $bg_image_url  = ! empty($instance['bg_image_url']) ? $instance['bg_image_url'] : '';

        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $user_id      = $current_user->ID;
            $display_name = $current_user->display_name;
            $user_bio     = get_user_meta($user_id, 'description', true);
            $vip_info     = function_exists('onedown_get_user_vip_info') ? onedown_get_user_vip_info($user_id) : array('vip_name' => '', 'vip_class' => '', 'is_vip' => false);
            $uc_url       = function_exists('onedown_user_center_url') ? 'onedown_user_center_url' : '__return_empty_string';

            // 计算背景图 URL
            $bg_url = '';
            switch ($bg_mode) {
                case 'upload':
                    if ($bg_image_id) {
                        $src = wp_get_attachment_image_url($bg_image_id, 'large');
                        if ($src) {
                            $bg_url = $src;
                        }
                    }
                    break;
                case 'url':
                    if ($bg_image_url) {
                        $bg_url = $bg_image_url;
                    }
                    break;
                case 'random':
                default:
                    $bg_url = get_template_directory_uri() . '/assets/img/avatar/user_t.jpg';
                    break;
            }

            // 兜底：配置为空时自动用随机图
            if (empty($bg_url)) {
                $bg_url = get_template_directory_uri() . '/assets/img/avatar/user_t.jpg';
            }
?>
            <div class="profile-card logged-in">
                <div class="profile-card-head" style="--bg-url: url('<?php echo esc_url($bg_url); ?>')">
                    <div class="profile-avatar-wrap">
                        <?php echo get_avatar($user_id, 70, '', $display_name); ?>
                    </div>
                    <h3><?php echo esc_html($display_name); ?></h3>
                    <?php if ($show_vip && ! empty($vip_info['vip_class'])) : ?>
                        <span class="vip-label <?php echo esc_attr($vip_info['vip_class']); ?>"><i class="fa fa-diamond"></i>
                            <?php echo esc_html($vip_info['vip_name']); ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($user_bio) : ?>
                    <p class="profile-bio"><?php echo esc_html(wp_trim_words($user_bio, 20, '...')); ?></p>
                <?php endif; ?>

                <div class="profile-stats">
                    <div class="profile-stats-item">
                        <strong><?php echo esc_html(count_user_posts($user_id)); ?></strong>
                        <span>文章</span>
                    </div>
                    <div class="profile-stats-item">
                        <strong><?php echo esc_html(get_comments(array('user_id' => $user_id, 'count' => true, 'status' => 'approve'))); ?></strong>
                        <span>评论</span>
                    </div>
                    <div class="profile-stats-item">
                        <strong><?php echo esc_html(function_exists('onedown_get_favorites_count') ? onedown_get_favorites_count($user_id) : 0); ?></strong>
                        <span>收藏</span>
                    </div>
                    <div class="profile-stats-item">
                        <strong><?php echo esc_html(function_exists('onedown_get_download_count') ? onedown_get_download_count($user_id) : 0); ?></strong>
                        <span>下载</span>
                    </div>
                </div>

                <div class="profile-actions icon-only">
                    <a class="primary" href="<?php echo esc_url($uc_url()); ?>" title="用户中心"><i class="fa fa-user-circle-o"></i></a>
                    <a href="<?php echo esc_url($uc_url(array('tab' => 'orders'))); ?>" title="我的订单"><i
                            class="fa fa-file-text-o"></i></a>
                    <a href="<?php echo esc_url($uc_url(array('tab' => 'favorites'))); ?>" title="我的收藏"><i
                            class="fa fa-star-o"></i></a>
                    <a href="<?php echo esc_url($uc_url(array('tab' => 'downloads'))); ?>" title="下载记录"><i
                            class="fa fa-download"></i></a>
                </div>
            </div>

            <?php if ($show_posts) : ?>
                <?php
                $recent_posts = get_posts(array(
                    'author'         => $user_id,
                    'posts_per_page' => $show_count,
                    'post_status'    => 'publish',
                    'no_found_rows'  => true,
                ));
                ?>
                <?php if (! empty($recent_posts)) : ?>
                    <section class="widget profile-posts">
                        <h3><i class="fa fa-file-text-o"></i> 最近发表</h3>
                        <div class="rank-list">
                            <?php foreach ($recent_posts as $post) : setup_postdata($post); ?>
                                <a class="rank-item" href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                                    <span class="rank-body">
                                        <strong><?php the_title(); ?></strong>
                                        <span class="rank-meta">
                                            <span><i class="fa fa-clock-o"></i> <?php echo esc_html(get_the_date('', $post)); ?></span>
                                        </span>
                                    </span>
                                </a>
                            <?php endforeach;
                            wp_reset_postdata(); ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

        <?php } else { ?>
            <div class="profile-card">
                <div class="avatar"><img
                        src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/avatar/default.png'); ?>" alt="游客"
                        style="width:70px;height:70px;border-radius:50%;object-fit:cover;"></div>
                <h3>游客账户</h3>
                <p>登录后可收藏教程、发布问题、查看订单并领取积分。</p>
                <div class="profile-actions">
                    <a class="primary" href="javascript:;" data-sign-modal>登录</a>
                    <a href="javascript:;" data-sign-modal="signup">注册</a>
                </div>
            </div>
        <?php }

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $show_vip   = ! empty($instance['show_vip']);
        $show_posts = ! empty($instance['show_posts']);
        $show_count = ! empty($instance['show_count']) ? (int) $instance['show_count'] : 5;

        $bg_mode       = ! empty($instance['bg_mode']) ? $instance['bg_mode'] : 'random';
        $bg_image_id   = ! empty($instance['bg_image_id']) ? (int) $instance['bg_image_id'] : 0;
        $bg_image_url  = ! empty($instance['bg_image_url']) ? $instance['bg_image_url'] : '';
        ?>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_vip); ?>
                    name="<?php echo $this->get_field_name('show_vip'); ?>"> 显示 VIP 标识</label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_posts); ?>
                    name="<?php echo $this->get_field_name('show_posts'); ?>" class="show-posts-toggle"> 显示最近发表</label>
        </p>
        <p>
            <label>文章数量：<input type="number" class="widefat" name="<?php echo $this->get_field_name('show_count'); ?>"
                    value="<?php echo esc_attr($show_count); ?>" min="1" max="20"></label>
        </p>

        <fieldset style="margin-top:12px;padding:10px;border:1px solid #ddd;border-radius:4px;">
            <legend style="font-weight:600;">背景图设置</legend>

            <p>
                <label><input type="radio" name="<?php echo $this->get_field_name('bg_mode'); ?>" value="random"
                        <?php checked($bg_mode, 'random'); ?>>
                    随机网络图片</label><br>
                <label><input type="radio" name="<?php echo $this->get_field_name('bg_mode'); ?>" value="upload"
                        <?php checked($bg_mode, 'upload'); ?>>
                    上传图片</label><br>
                <label><input type="radio" name="<?php echo $this->get_field_name('bg_mode'); ?>" value="url"
                        <?php checked($bg_mode, 'url'); ?>>
                    网络图片 URL</label>
            </p>

            <div class="bg-upload-field" style="display:<?php echo $bg_mode === 'upload' ? 'block' : 'none'; ?>">
                <p>
                    <button type="button" class="button od-select-bg-image">选择图片</button>
                    <input type="hidden" name="<?php echo $this->get_field_name('bg_image_id'); ?>"
                        value="<?php echo esc_attr($bg_image_id); ?>">
                </p>
                <div class="bg-preview" style="<?php echo $bg_image_id ? 'display:block;' : 'display:none;'; ?>">
                    <img src="<?php echo $bg_image_id ? esc_url(wp_get_attachment_image_url($bg_image_id, 'thumbnail')) : ''; ?>"
                        style="max-width:100%;height:auto;border-radius:4px;">
                    <p><a href="#" class="od-remove-bg-image" style="color:#b32d2e;">删除图片</a></p>
                </div>
            </div>

            <div class="bg-url-field" style="display:<?php echo $bg_mode === 'url' ? 'block' : 'none'; ?>">
                <p>
                    <label>图片 URL：<input type="text" class="widefat" name="<?php echo $this->get_field_name('bg_image_url'); ?>"
                            value="<?php echo esc_attr($bg_image_url); ?>" placeholder="https://example.com/image.jpg"></label>
                </p>
            </div>
        </fieldset>

        <script>
            (function($) {
                var container = $('#widgets-right');
                if (!container.length) container = $('body');

                container.on('change', 'input[name^="<?php echo $this->get_field_name('bg_mode'); ?>"]', function() {
                    var val = $(this).val();
                    var fieldset = $(this).closest('fieldset');
                    fieldset.find('.bg-upload-field').toggle(val === 'upload');
                    fieldset.find('.bg-url-field').toggle(val === 'url');
                });

                container.on('click', '.od-select-bg-image', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var frame = wp.media({
                        title: '选择背景图片',
                        multiple: false,
                        library: {
                            type: 'image'
                        }
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        btn.siblings('input[type="hidden"]').val(attachment.id);
                        var preview = btn.closest('p').siblings('.bg-preview');
                        preview.show().find('img').attr('src', attachment.sizes.thumbnail ? attachment.sizes
                            .thumbnail.url : attachment.url);
                        preview.find('.od-remove-bg-image').show();
                    });
                    frame.open();
                });

                container.on('click', '.od-remove-bg-image', function(e) {
                    e.preventDefault();
                    var preview = $(this).closest('.bg-preview');
                    preview.hide().find('img').attr('src', '');
                    preview.siblings('p').find('input[type="hidden"]').val(0);
                });
            })(jQuery);
        </script>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance                  = $old_instance;
        $instance['show_vip']      = ! empty($new_instance['show_vip']) ? 1 : 0;
        $instance['show_posts']    = ! empty($new_instance['show_posts']) ? 1 : 0;
        $instance['show_count']    = ! empty($new_instance['show_count']) ? (int) $new_instance['show_count'] : 5;
        $instance['bg_mode']       = in_array($new_instance['bg_mode'], array('random', 'upload', 'url')) ? $new_instance['bg_mode'] : 'random';
        $instance['bg_image_id']   = ! empty($new_instance['bg_image_id']) ? (int) $new_instance['bg_image_id'] : 0;
        $instance['bg_image_url']  = ! empty($new_instance['bg_image_url']) ? esc_url_raw($new_instance['bg_image_url']) : '';
        return $instance;
    }
}
