<?php get_header(); ?>
<main>
    <section class="page-hero category-page-hero">
        <div class="page-hero-copy">
            <i class="fa fa-frown-o" style="font-size:96px;display:block;margin:0 auto 16px;color:var(--od-muted);"></i>
            <h1>页面未找到</h1>
            <p>你访问的页面不存在或已被移动。</p>
            <div class="page-hero-actions"><a class="primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><i class="fa fa-home"></i> 返回首页</a></div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
