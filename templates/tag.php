<?php get_header(); ?>
<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span><?php single_tag_title(); ?></span>
    </nav>

    <section class="page-hero tag-page-hero">
        <div class="page-hero-copy">
            <h1><?php single_tag_title(); ?></h1>
            <p><?php echo esc_html( tag_description() ?: '浏览有关 "' . single_tag_title( '', false ) . '" 标签的所有内容。' ); ?></p>
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
