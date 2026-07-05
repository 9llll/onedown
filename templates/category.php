<?php get_header(); ?>
<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span><?php single_cat_title(); ?></span>
    </nav>

    <section class="page-hero category-page-hero">
        <div class="page-hero-copy">
            <h1><?php single_cat_title(); ?></h1>
            <p><?php echo esc_html( category_description() ?: '集中整理安装配置、模块布局、商城能力和扩展开发内容。' ); ?></p>
            <div class="page-hero-actions"><a class="primary" href="#postList"><i class="fa fa-book"></i> 查看内容</a></div>
        </div>
        <div class="page-hero-stats"><span><strong><?php echo esc_html( $wp_query->found_posts ); ?></strong>篇内容</span></div>
    </section>

    <section class="content-shell">
        <div class="main-column" id="postList">
            <div class="section-card">
                <div class="section-head"><h2 class="section-title"><i class="fa fa-fire"></i> 最新内容</h2></div>
                <div class="post-list">
                    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
                        <?php get_template_part( 'template-parts/content', 'card' ); ?>
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
