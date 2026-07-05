<?php
if (! defined('ABSPATH')) {
    header('Location: /.onedown-theme-404', true, 302);
    exit;
}

get_header();
?>
<main>
    <h1 class="sr-only"><?php bloginfo('name'); ?> - <?php echo __('最新内容', 'onedown'); ?></h1>
    <?php if (is_active_sidebar('home_top_fluid')) : ?>
    <section class="home-widget-area home-widget-top" aria-label="首页顶部全宽度小工具区">
        <?php dynamic_sidebar('home_top_fluid'); ?>
    </section>
    <?php endif; ?>

    <section class="content-shell home-content-shell">
        <div class="main-column">
            <?php if (is_active_sidebar('home_before_latest')) : ?>
            <div class="widget-area-before-latest">
                <?php dynamic_sidebar('home_before_latest'); ?>
            </div>
            <?php endif; ?>

            <div class="section-card">
                <div class="section-head">
                    <h2 class="section-title"><i class="fa fa-fire"></i> 最新内容</h2>
                </div>
                <div class="post-list">
                    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                    <?php get_template_part('template-parts/content', 'card'); ?>
                    <?php endwhile;
                    else : ?>
                    <div class="empty-state">
                        <i class="fa fa-inbox"></i>
                        <p>暂无内容</p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php onedown_render_pagination(); ?>
            </div>

            <?php if (is_active_sidebar('home_after_latest')) : ?>
            <div class="widget-area-after-latest">
                <?php dynamic_sidebar('home_after_latest'); ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="sidebar-right-area">
            <?php get_sidebar(); ?>
        </div>
    </section>

    <?php if (is_active_sidebar('home_bottom_fluid')) : ?>
    <section class="home-widget-area home-widget-bottom" aria-label="首页底部全宽度小工具区">
        <?php dynamic_sidebar('home_bottom_fluid'); ?>
    </section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
