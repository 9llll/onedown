<?php get_header(); ?>
<main>
    <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">首页</a>
        <i class="fa fa-angle-right"></i>
        <span>搜索</span>
    </nav>

    <section class="page-hero search-page-hero">
        <div class="page-hero-copy">
            <h1>站内搜索</h1>
            <p>快速检索主题教程、社区问答、商城资源和更新记录。</p>
            <form class="search-page-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get">
                <i class="fa fa-search"></i>
                <input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="输入关键词搜索内容">
                <button type="submit">搜索</button>
            </form>
        </div>
        <div class="page-hero-stats"><span><strong><?php echo esc_html( $wp_query->found_posts ); ?></strong>相关内容</span></div>
    </section>

    <section class="content-shell">
        <div class="main-column">
            <div class="section-card">
                <div class="section-head search-result-head">
                    <h2 class="section-title"><i class="fa fa-search"></i> 搜索结果</h2>
                    <span>关键词：<strong><?php echo esc_html( get_search_query() ); ?></strong></span>
                </div>
                <div class="post-list search-result-list">
                    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
                        <?php get_template_part( 'template-parts/content', 'card' ); ?>
                    <?php endwhile; else : ?>
                        <div style="padding:48px 32px;text-align:center;color:var(--od-muted);">
                            <i class="fa fa-search" style="font-size:80px;display:block;margin:0 auto 16px;color:var(--od-muted);"></i>
                            <p style="margin:0;">没有找到相关内容。</p>
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
