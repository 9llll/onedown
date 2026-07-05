<?php get_header(); ?>
<main>
    <?php while (have_posts()) : the_post(); ?>
        <?php
        $breadcrumb_categories = get_the_category();
        $breadcrumb_category   = ! empty($breadcrumb_categories) ? $breadcrumb_categories[0] : null;
        ?>
        <nav class="breadcrumb-line page-breadcrumb" aria-label="面包屑">
            <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
            <i class="fa fa-angle-right"></i>
            <?php if ($breadcrumb_category) : ?>
                <a href="<?php echo esc_url(get_category_link($breadcrumb_category->term_id)); ?>"><?php echo esc_html($breadcrumb_category->name); ?></a>
            <?php else : ?>
                <span>分类</span>
            <?php endif; ?>
            <i class="fa fa-angle-right"></i>
            <span title="<?php the_title_attribute(); ?>">正文</span>
        </nav>

        <?php if ( is_active_sidebar( 'single_top_fluid' ) ) : ?>
            <section class="single-widget-area single-widget-top" aria-label="文章顶部小工具区">
                <?php dynamic_sidebar( 'single_top_fluid' ); ?>
            </section>
        <?php endif; ?>

        <section class="content-shell detail-shell">
            <article class="article-card" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="article-head">
                    <span class="article-category"><i class="fa fa-book"></i> <?php echo esc_html(onedown_category_name()); ?></span>
                    <h1><?php the_title(); ?></h1>
                    <?php if (_pz('post_meta', true)) : ?>
                    <div class="article-meta">
                        <span><i class="fa fa-user-o"></i> <?php the_author(); ?></span>
                        <span><i class="fa fa-clock-o"></i> <?php echo esc_html(get_the_date()); ?></span>
                        <?php if (_pz('show_post_views', true)) : ?>
                        <span><i class="fa fa-eye"></i> <?php echo esc_html(get_post_meta(get_the_ID(), 'post_views_count', true) ?: '0'); ?> 浏览</span>
                        <?php endif; ?>
                        <?php if (function_exists('onedown_comments_enabled') && onedown_comments_enabled(get_the_ID())) : ?>
                        <span><i class="fa fa-comment-o"></i> <?php comments_number('0 评论', '1 评论', '% 评论'); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </header>

                <?php if (_pz('thumbnail_enabled', true) && has_post_thumbnail()) : ?>
                    <?php
                    $cover_id   = get_post_thumbnail_id(get_the_ID());
                    $cover_path = $cover_id ? get_attached_file($cover_id) : '';
                    $cover_url  = ($cover_path && file_exists($cover_path)) ? wp_get_attachment_image_url($cover_id, 'large') : onedown_fallback_thumb_url(get_the_ID(), 1200, 630);
                    ?>
                    <div class="article-cover"><?php onedown_lazyload_img($cover_url, the_title_attribute(array('echo' => false))); ?></div>
                <?php endif; ?>

                <?php $article_excerpt = _pz('post_excerpt', true) ? trim(get_the_excerpt()) : ''; ?>
                <?php if ($article_excerpt) : ?>
                    <div class="article-summary"><i class="fa fa-lightbulb-o"></i>
                        <p><?php echo esc_html(wp_trim_words($article_excerpt, (int) _pz('post_excerpt_length', 120), '...')); ?></p>
                    </div>
                <?php endif; ?>

                <div class="article-content">
                    <?php the_content(); ?>
                    <?php wp_link_pages(array('before' => '<div class="post-pagination">', 'after' => '</div>')); ?>
                </div>

                <?php if (_pz('post_copyright_enabled', true)) : ?>
                    <?php
                    $copyright_text = (string) _pz('post_copyright_text', '版权声明：本文标题为《{title}》，链接为 {url}，转载请注明出处。');
                    $copyright_text = str_replace(
                        array('{title}', '{url}', '{site_name}', '{modified_date}'),
                        array(get_the_title(), get_permalink(), get_bloginfo('name'), get_the_modified_date()),
                        $copyright_text
                    );
                    ?>
                    <div class="article-copyright">
                        <i class="fa fa-shield"></i>
                        <div class="article-copyright__content"><?php echo wp_kses_post($copyright_text); ?></div>
                    </div>
                <?php endif; ?>

                <footer class="article-footer">
                    <div class="article-tags"><?php the_tags('', '', ''); ?></div>
                    <div class="article-actions">
                        <?php 
                        $is_fav = false;
                        if (is_user_logged_in()) {
                            $favorites = get_user_meta(get_current_user_id(), 'onedown_favorites', true);
                            $is_fav = is_array($favorites) && in_array(get_the_ID(), $favorites);
                        }
                        ?>
                        <?php if (_pz('show_post_likes', true)) :
                            $likes_count = (int) get_post_meta(get_the_ID(), 'likes_count', true);
                            $is_liked = is_user_logged_in() && in_array(get_the_ID(), (array) get_user_meta(get_current_user_id(), 'onedown_likes', true));
                        ?>
                        <button type="button" class="like-btn <?php echo $is_liked ? 'active' : ''; ?>" data-post-id="<?php the_ID(); ?>" data-like-toggle>
                            <i class="fa <?php echo $is_liked ? 'fa-thumbs-up' : 'fa-thumbs-o-up'; ?>"></i>
                            <span><?php echo $is_liked ? '已赞' : '点赞'; ?></span>
                            <em class="like-count"><?php echo $likes_count ?: ''; ?></em>
                        </button>
                        <?php endif; ?>
                        <?php if (_pz('show_post_favorites', true)) : ?>
                        <button type="button" class="fav-btn <?php echo $is_fav ? 'active' : ''; ?>" data-post-id="<?php the_ID(); ?>" data-fav-toggle><i class="fa <?php echo $is_fav ? 'fa-star' : 'fa-star-o'; ?>"></i> <span><?php echo $is_fav ? '已收藏' : '收藏'; ?></span></button>
                        <?php endif; ?>
                        <?php if (_pz('show_post_share', true)) : ?>
                        <button type="button" data-share-toggle><i class="fa fa-share-alt"></i> 分享</button>
                        <?php endif; ?>
                    </div>
                </footer>

                <nav class="article-neighbor" aria-label="article navigation">
                    <?php
                    $prev_post = get_previous_post();
                    $next_post = get_next_post();
                    if ($prev_post) :
                        $prev_excerpt = wp_trim_words(get_the_excerpt($prev_post), 20, '...');
                    ?>
                        <a class="article-neighbor-item prev" href="<?php echo esc_url(get_permalink($prev_post)); ?>">
                            <span><i class="fa fa-angle-left"></i> 上一篇</span>
                            <strong><?php echo esc_html(get_the_title($prev_post)); ?></strong>
                            <em><?php echo esc_html($prev_excerpt); ?></em>
                        </a>
                    <?php endif; ?>
                    <?php if ($next_post) :
                        $next_excerpt = wp_trim_words(get_the_excerpt($next_post), 20, '...');
                    ?>
                        <a class="article-neighbor-item next" href="<?php echo esc_url(get_permalink($next_post)); ?>">
                            <span>下一篇 <i class="fa fa-angle-right"></i></span>
                            <strong><?php echo esc_html(get_the_title($next_post)); ?></strong>
                            <em><?php echo esc_html($next_excerpt); ?></em>
                        </a>
                    <?php endif; ?>
                </nav>

                <?php
                $categories = get_the_category();
                if (!empty($categories)) :
                    $category_ids = wp_list_pluck($categories, 'term_id');
                    $related_posts = get_posts(array(
                        'category__in'    => $category_ids,
                        'post__not_in'    => array(get_the_ID()),
                        'posts_per_page'  => 3,
                        'orderby'         => 'rand',
                        'no_found_rows'   => true,
                    ));
                    if ($related_posts) :
                ?>
                    <section class="article-related" aria-labelledby="articleRelatedTitle">
                        <div class="article-related-head">
                            <h2 id="articleRelatedTitle"><i class="fa fa-line-chart"></i> 相关推荐</h2>
                            <a href="<?php echo esc_url(get_category_link($categories[0]->term_id)); ?>">更多教程 <i class="fa fa-angle-right"></i></a>
                        </div>
                        <div class="article-related-grid">
                            <?php foreach ($related_posts as $related) : ?>
                                <a class="related-card" href="<?php echo esc_url(get_permalink($related)); ?>">
                                    <?php if (_pz('thumbnail_enabled', true) && has_post_thumbnail($related)) : ?>
                                        <?php onedown_lazyload_img(get_the_post_thumbnail_url($related, 'medium'), get_the_title($related)); ?>
                                    <?php elseif (_pz('thumbnail_enabled', true)) : ?>
                                        <?php onedown_lazyload_img(onedown_fallback_thumb_url($related->ID, 400, 225), get_the_title($related)); ?>
                                    <?php endif; ?>
                                    <?php
                                    $related_cats = get_the_category($related);
                                    if (!empty($related_cats)) :
                                    ?>
                                        <span><?php echo esc_html($related_cats[0]->name); ?></span>
                                    <?php endif; ?>
                                    <strong><?php echo esc_html(get_the_title($related)); ?></strong>
                                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt($related), 20, '...')); ?></p>
                                    <?php if (_pz('show_post_views', true)) : ?>
                                    <em><i class="fa fa-eye"></i> <?php echo esc_html(get_post_meta($related->ID, 'post_views_count', true) ?: '0'); ?> 浏览</em>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                <?php endif; ?>

                <?php if ((function_exists('onedown_comments_enabled') ? onedown_comments_enabled(get_the_ID()) : true) && (comments_open() || get_comments_number())) : comments_template();
                endif; ?>
            </article>
            <?php get_sidebar(); ?>
        </section>

        <?php if ( is_active_sidebar( 'single_bottom_fluid' ) ) : ?>
            <section class="single-widget-area single-widget-bottom" aria-label="文章底部小工具区">
                <?php dynamic_sidebar( 'single_bottom_fluid' ); ?>
            </section>
        <?php endif; ?>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
