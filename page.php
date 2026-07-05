<?php get_header(); ?>
<main>
    <?php while ( have_posts() ) : the_post(); ?>
        <section class="content-shell detail-shell">
            <article class="article-card" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="article-head"><h1><?php the_title(); ?></h1></header>
                <div class="article-content"><?php the_content(); ?></div>
            </article>
            <?php get_sidebar(); ?>
        </section>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
