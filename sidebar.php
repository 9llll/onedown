<aside class="sidebar" aria-label="侧边栏">
    <?php
    // 根据页面类型选择侧边栏
    if (is_front_page() || is_home()) {
        $sidebar_id = 'sidebar_home';
    } elseif (is_singular()) {
        $sidebar_id = 'sidebar_single';
    } elseif (is_category() || is_tag() || is_archive() || is_search()) {
        $sidebar_id = 'sidebar_archive';
    } else {
        $sidebar_id = 'sidebar_global';
    }
    ?>
    <?php if (is_active_sidebar($sidebar_id)) : ?>
        <?php dynamic_sidebar($sidebar_id); ?>
    <?php elseif (is_active_sidebar('sidebar_global')) : ?>
        <?php dynamic_sidebar('sidebar_global'); ?>
    <?php else : ?>
        <?php if (is_user_logged_in()) :
            $current_user = wp_get_current_user();
            $user_id      = $current_user->ID;
            $avatar       = get_avatar($user_id, 70, '', $current_user->display_name);
            $display_name = $current_user->display_name;
            $user_bio     = get_user_meta($user_id, 'description', true);
            $vip_info     = onedown_get_user_vip_info();
            $uc_url       = 'onedown_user_center_url';

            $recent_posts = get_posts(array(
                'author'         => $user_id,
                'posts_per_page' => 5,
                'post_status'    => 'publish',
                'no_found_rows'  => true,
            ));
        ?>
            <section class="widget profile-card logged-in">
                <div class="profile-card-head"
                    style="--bg-url: url('<?php echo get_template_directory_uri(); ?>/assets/img/avatar/user_t.jpg')">
                    <div class="profile-avatar-wrap">
                        <?php echo $avatar; ?>
                    </div>
                    <h3><?php echo esc_html($display_name); ?></h3>
                    <?php if ($vip_info['vip_class']) : ?>
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
                        <strong><?php echo esc_html(onedown_get_favorites_count($user_id)); ?></strong>
                        <span>收藏</span>
                    </div>
                    <div class="profile-stats-item">
                        <strong><?php echo esc_html(onedown_get_download_count($user_id)); ?></strong>
                        <span>下载</span>
                    </div>
                </div>

                <div class="profile-actions icon-only">
                    <a class="primary" href="<?php echo esc_url($uc_url()); ?>" title="用户中心"><i
                            class="fa fa-user-circle-o"></i></a>
                    <a href="<?php echo esc_url($uc_url(array('tab' => 'orders'))); ?>" title="我的订单"><i
                            class="fa fa-file-text-o"></i></a>
                    <a href="<?php echo esc_url($uc_url(array('tab' => 'favorites'))); ?>" title="我的收藏"><i
                            class="fa fa-star-o"></i></a>
                    <a href="<?php echo esc_url($uc_url(array('tab' => 'downloads'))); ?>" title="下载记录"><i
                            class="fa fa-download"></i></a>
                </div>
            </section>

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
        <?php else : ?>
            <section class="widget profile-card">
                <div class="avatar"><img src="<?php echo esc_url(onedown_asset_img('avatar/default.png')); ?>" alt="游客"
                        style="width:70px;height:70px;border-radius:50%;object-fit:cover;"></div>
                <h3>游客账户</h3>
                <p>登录后可收藏教程、发布问题、查看订单并领取积分。</p>
                <div class="profile-actions">
                    <a class="primary" href="javascript:;" data-sign-modal>登录</a>
                    <a href="javascript:;" data-sign-modal="signup">注册</a>
                </div>
            </section>
        <?php endif; ?>

        <section class="widget">
            <h3><i class="fa fa-line-chart"></i> 热门文章</h3>
            <div class="rank-list">
                <?php
                $hot_posts = onedown_cached_posts('sidebar_hot', array(
                    'posts_per_page'      => 5,
                    'ignore_sticky_posts' => true,
                    'post_status'         => 'publish',
                    'orderby'             => 'meta_value_num',
                    'meta_key'            => 'views',
                    'order'               => 'DESC',
                ));
                foreach ($hot_posts as $post) :
                    setup_postdata($post);
                ?>
                    <a class="rank-item" href="<?php the_permalink(); ?>"><span
                            class="rank-body"><strong><?php the_title(); ?></strong><span class="rank-meta"><span><i
                                        class="fa fa-clock-o"></i>
                                    <?php echo esc_html(get_the_date()); ?></span></span></span></a>
                <?php endforeach;
                wp_reset_postdata(); ?>
            </div>
        </section>
        <section class="widget">
            <h3 class="widget-title-with-more"><span><i class="fa fa-tags"></i> 热门标签</span><a class="widget-title-more" href="<?php echo esc_url(home_url('/tags/')); ?>">&#26356;&#22810;</a></h3>
            <div class="tag-cloud">
                <?php wp_tag_cloud(array('smallest' => 12, 'largest' => 12, 'unit' => 'px', 'number' => 12)); ?></div>
        </section>
    <?php endif; ?>
</aside>
