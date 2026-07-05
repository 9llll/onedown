<article class="post-item" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php if (_pz('thumbnail_enabled', true)) : ?>
    <a class="post-thumb" href="<?php the_permalink(); ?>">
        <?php onedown_lazyload_img( onedown_post_thumb_url(), get_the_title() ); ?>
        <span class="flag"><?php echo esc_html( onedown_category_name() ); ?></span>
        <?php if ( is_sticky() ) : ?><span
            class="status-tag tag-sticky">置顶</span><?php elseif ( (int) get_post_meta( get_the_ID(), 'views', true ) >= 1000 ) : ?><span
            class="status-tag tag-hot">热门</span><?php elseif ( get_post_time( 'U', true ) >= strtotime( '-3 days' ) ) : ?><span
            class="status-tag tag-new">最新</span><?php endif; ?>
    </a>
    <?php endif; ?>
    <div class="post-body">
        <h3><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
        <?php if (_pz('post_excerpt', true)) : ?>
        <p><?php echo esc_html( onedown_excerpt((int) _pz('post_excerpt_length', 120)) ); ?></p>
        <?php endif; ?>
        <?php if (_pz('post_meta', true)) : ?>
        <?php
        $onedown_author_name = get_the_author();
        if (function_exists('mb_substr')) {
            $onedown_author_short_name = mb_substr($onedown_author_name, 0, 5, get_bloginfo('charset'));
        } else {
            $onedown_author_chars = preg_split('//u', $onedown_author_name, -1, PREG_SPLIT_NO_EMPTY);
            $onedown_author_short_name = is_array($onedown_author_chars) ? implode('', array_slice($onedown_author_chars, 0, 5)) : substr($onedown_author_name, 0, 5);
        }
        ?>
        <div class="post-meta">
            <span class="post-meta-author"><i class="fa fa-user-o"></i>
                <?php echo esc_html($onedown_author_short_name); ?></span>
            <?php if (function_exists('onedown_comments_enabled') && onedown_comments_enabled(get_the_ID())) : ?>
            <span class="post-meta-comments"><i class="fa fa-comment-o"></i>
                <?php comments_number( '0', '1', '%' ); ?></span>
            <?php endif; ?>
            <span class="post-meta-date"><i class="fa fa-clock-o"></i> <?php echo esc_html( get_the_date() ); ?></span>
        </div>
        <?php endif; ?>
    </div>
</article>