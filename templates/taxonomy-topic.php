<?php
/**
 * 专题页面模板
 *
 * 类似分类页，显示某个专题下的所有文章
 */

get_header();

$term = get_queried_object();
$image = $term ? onedown_get_topic_image($term->term_id) : '';
?>
<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span><?php single_term_title(); ?></span>
    </nav>

    <section class="page-hero category-page-hero">
        <div class="page-hero-copy">
            <h1><?php single_term_title(); ?></h1>
            <p><?php echo esc_html(term_description() ?: '精选内容，汇聚成专题。'); ?></p>
            <?php if ($image) : ?>
                <div class="page-hero-actions"><a class="primary" href="#postList"><i class="fa fa-book"></i> 查看内容</a></div>
            <?php endif; ?>
        </div>
        <div class="page-hero-stats">
            <?php if ($image) : ?>
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(single_term_title('', false)); ?>" style="width:120px;height:90px;object-fit:cover;border-radius:8px;">
            <?php endif; ?>
            <span><strong><?php echo esc_html($wp_query->found_posts); ?></strong>篇内容</span>
        </div>
    </section>

    <section class="content-shell">
        <div class="main-column" id="postList">
            <div class="section-card">
                <div class="section-head"><h2 class="section-title"><i class="fa fa-fire"></i> 专题内容</h2></div>
                <div class="post-list">
                    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
                        <?php get_template_part('template-parts/content', 'card'); ?>
                    <?php endwhile; else : ?>
                        <div class="empty-state">
                            <i class="fa fa-inbox"></i>
                            <p>暂无内容</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php onedown_render_pagination(); ?>
            </div>
        </div>
        <?php get_sidebar(); ?>
    </section>
</main>
<?php get_footer(); ?>
