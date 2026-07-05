<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 获取主题图片资源的 URL
 *
 * @param string $path 图片相对路径（如 'thumbnail/thumbnail.svg'）
 * @return string
 */
if (! function_exists('onedown_asset_img')) :
    function onedown_asset_img($path)
    {
        return get_template_directory_uri() . '/assets/img/' . ltrim($path, '/');
    }
endif;

if (! function_exists('onedown_is_lazyload_enabled')) :
    /**
     * 检查是否启用图片懒加载
     *
     * @return bool
     */
    function onedown_is_lazyload_enabled()
    {
        return (bool) onedown_get_option('function_lazyload', true);
    }
endif;

if (! function_exists('onedown_lazyload_attrs')) :
    /**
     * 获取图片懒加载属性
     *
     * 当开关开启时返回 lazysizes 所需的 data-src 和占位 src；
     * 当开关关闭时直接返回常规 src 属性。
     *
     * @param string $img_url 图片 URL
     * @return array{src: string, data_src: string, class: string, extra: string}
     */
    function onedown_lazyload_attrs($img_url)
    {
        if (onedown_is_lazyload_enabled()) {
            return array(
                'src'      => 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==',
                'data_src' => $img_url,
                'class'    => 'lazyload',
                'extra'    => '',
            );
        }

        return array(
            'src'      => $img_url,
            'data_src' => '',
            'class'    => '',
            'extra'    => 'loading="lazy" decoding="async"',
        );
    }
endif;

if (! function_exists('onedown_lazyload_img')) :
    /**
     * 输出带懒加载的 <img> 标签
     *
     * @param string $img_url 图片 URL
     * @param string $alt     ALT 文本
     * @param array  $extra   额外属性（如 data-*、class 等），key-value 对
     */
    function onedown_lazyload_img($img_url, $alt = '', $extra = array())
    {
        $attrs = onedown_lazyload_attrs($img_url);

        $html_attrs = array(
            'src' => $attrs['src'],
            'alt' => esc_attr($alt),
        );

        if ($attrs['data_src']) {
            $html_attrs['data-src'] = $attrs['data_src'];
        }

        if ($attrs['class']) {
            $extra_class = ! empty($extra['class']) ? ' ' . $extra['class'] : '';
            $html_attrs['class'] = $attrs['class'] . $extra_class;
            unset($extra['class']);
        }

        if ($attrs['extra']) {
            // 解析 extra 字符串中的属性
            if (preg_match('/loading="([^"]+)"/', $attrs['extra'], $m)) {
                $html_attrs['loading'] = $m[1];
            }
            if (preg_match('/decoding="([^"]+)"/', $attrs['extra'], $m)) {
                $html_attrs['decoding'] = $m[1];
            }
        }

        // 合并额外属性
        foreach ($extra as $key => $value) {
            $html_attrs[$key] = $value;
        }

        $output = '<img';
        foreach ($html_attrs as $key => $value) {
            $output .= ' ' . $key . '="' . esc_attr($value) . '"';
        }
        $output .= '>';

        echo $output;
    }
endif;

if (! function_exists('onedown_get_avatar_html')) :
    function onedown_get_avatar_html($id_or_email, $size = 96, $default = '', $alt = '', $args = array())
    {
        $args = array_merge(
            array(),
            $args
        );

        if (onedown_is_lazyload_enabled()) {
            $args = array_merge(
                array(
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                ),
                $args
            );
        }

        if ('' === trim((string) $alt)) {
            $alt = __('用户头像', ONEDOWN_TEXT_DOMAIN);
        }

        return get_avatar($id_or_email, $size, $default, $alt, $args);
    }
endif;

if (! function_exists('onedown_seo_link_attrs')) :
    function onedown_seo_link_attrs($url, $args = array())
    {
        $defaults = array(
            'new_tab'   => false,
            'nofollow'  => false,
            'sponsored' => false,
            'ugc'       => false,
            'title'     => '',
            'aria_label' => '',
            'class'     => '',
        );

        $args  = array_merge($defaults, $args);
        $attrs = array('href="' . esc_url($url) . '"');

        if ($args['class']) {
            $attrs[] = 'class="' . esc_attr($args['class']) . '"';
        }

        if ($args['title']) {
            $attrs[] = 'title="' . esc_attr($args['title']) . '"';
        }

        if ($args['aria_label']) {
            $attrs[] = 'aria-label="' . esc_attr($args['aria_label']) . '"';
        }

        if ($args['new_tab']) {
            $attrs[] = 'target="_blank"';
            $rel     = array('noopener', 'noreferrer');

            if ($args['nofollow']) {
                $rel[] = 'nofollow';
            }
            if ($args['sponsored']) {
                $rel[] = 'sponsored';
            }
            if ($args['ugc']) {
                $rel[] = 'ugc';
            }

            $attrs[] = 'rel="' . esc_attr(implode(' ', array_unique($rel))) . '"';
        } elseif ($args['nofollow'] || $args['sponsored'] || $args['ugc']) {
            $rel = array();

            if ($args['nofollow']) {
                $rel[] = 'nofollow';
            }
            if ($args['sponsored']) {
                $rel[] = 'sponsored';
            }
            if ($args['ugc']) {
                $rel[] = 'ugc';
            }

            $attrs[] = 'rel="' . esc_attr(implode(' ', array_unique($rel))) . '"';
        }

        return implode(' ', $attrs);
    }
endif;

if (! function_exists('onedown_fallback_thumb_url')) :
    /**
     * 获取无图时的兜底缩略图 URL
     *
     * @param int|null $post_id 文章 ID，用于生成稳定 seed
     * @param int      $width   图片宽度
     * @param int      $height  图片高度
     * @return string
     */
    function onedown_fallback_thumb_url($post_id = null, $width = 800, $height = 450)
    {
        if (! _pz('thumbnail_random_fallback', true)) {
            $fallback = _pz('thumbnail_fallback', array());
            if (is_array($fallback) && ! empty($fallback['url'])) {
                return $fallback['url'];
            }

            return onedown_asset_img('friend-link-default.svg');
        }

        $post_id = $post_id ? $post_id : get_the_ID();
        $seed    = $post_id ? $post_id : 'page-' . crc32(home_url(add_query_arg(array())));
        return 'https://picsum.photos/seed/' . $seed . '/' . $width . '/' . $height;
    }
endif;

if (! function_exists('onedown_post_thumb_url')) :
    function onedown_post_thumb_url($post_id = null)
    {
        if (! _pz('thumbnail_enabled', true)) {
            return '';
        }

        $post_id = $post_id ? $post_id : get_the_ID();

        // 1. 优先使用特色图片
        if (has_post_thumbnail($post_id)) {
            return get_the_post_thumbnail_url($post_id, _pz('thumbnail_size', 'medium'));
        }

        // 2. 尝试从文章内容中提取第一张图片
        $post = get_post($post_id);
        if ($post && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches)) {
            return esc_url($matches[1]);
        }

        // 3. 兜底：随机缩略图或默认缩略图
        return onedown_fallback_thumb_url($post_id);
    }
endif;

if (! function_exists('onedown_excerpt')) :
    function onedown_excerpt($length = 88)
    {
        return wp_trim_words(get_the_excerpt(), $length, '...');
    }
endif;

if (! function_exists('onedown_category_name')) :
    function onedown_category_name()
    {
        $category = get_the_category();
        return ! empty($category) ? $category[0]->name : __('文章', ONEDOWN_TEXT_DOMAIN);
    }
endif;

/**
 * 带 transient 缓存的文章查询
 *
 * 缓存查询返回的文章 ID 列表，避免每次页面加载都查询数据库。
 * 缓存在文章发布/更新/删除时自动失效（见 onedown_flush_post_query_cache）。
 *
 * @param string $key       缓存键（同一组件用稳定的键）
 * @param array  $query_args WP_Query 参数
 * @param int    $expire     过期时间（秒），默认 1 小时
 * @return WP_Post[]         文章对象数组
 */
if (! function_exists('onedown_cached_posts')) :
    function onedown_cached_posts($key, $query_args, $expire = HOUR_IN_SECONDS)
    {
        if (! _pz('cache_enabled', false)) {
            return get_posts($query_args);
        }

        $cache_expire = max(1, (int) _pz('cache_expire', 60)) * MINUTE_IN_SECONDS;

        if (HOUR_IN_SECONDS === $expire) {
            $expire = $cache_expire;
        }

        $cache_key = 'onedown_pq_' . $key;
        $ids       = get_transient($cache_key);

        if (false === $ids) {
            $args                  = $query_args;
            $args['fields']        = 'ids';
            $args['no_found_rows'] = true;
            $query                 = new WP_Query($args);
            $ids                   = $query->posts;
            set_transient($cache_key, $ids, $expire);
        }

        if (empty($ids)) {
            return array();
        }

        return get_posts(array(
            'post__in'            => $ids,
            'orderby'             => 'post__in',
            'posts_per_page'      => count($ids),
            'ignore_sticky_posts' => 1,
            'post_status'         => 'publish',
        ));
    }
endif;

if (! function_exists('onedown_lazyload_content_images')) :
    /**
     * 为文章内容中的图片添加懒加载属性
     *
     * @param string $content 文章内容 HTML
     * @return string
     */
    function onedown_lazyload_content_images($content)
    {
        if (! onedown_is_lazyload_enabled() || empty($content)) {
            return $content;
        }

        // 替换 <img> 标签，添加 lazysizes 懒加载属性
        $content = preg_replace_callback(
            '/<img\s+([^>]*)src=["\']([^"\']+)["\']([^>]*)>/i',
            function ($matches) {
                $before = $matches[1];
                $src    = $matches[2];
                $after  = $matches[3];

                // 跳过已经含有 lazyload 类的图片
                if (preg_match('/class=["\'][^"\']*\blazyload\b[^"\']*["\']/i', $before . ' ' . $after)) {
                    return $matches[0];
                }

                // 跳过 SVG、data:image、已带 data-src 的图片
                if (preg_match('/\.svg/i', $src) || strpos($src, 'data:image') === 0 || preg_match('/data-src=/i', $before . ' ' . $after)) {
                    return $matches[0];
                }

                // 构建 lazyload 属性
                $placeholder = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

                // 提取并移除已有的 loading/decoding 属性
                $before = preg_replace('/\s+loading=["\'][^"\']*["\']/i', '', $before);
                $after  = preg_replace('/\s+loading=["\'][^"\']*["\']/i', '', $after);
                $before = preg_replace('/\s+decoding=["\'][^"\']*["\']/i', '', $before);
                $after  = preg_replace('/\s+decoding=["\'][^"\']*["\']/i', '', $after);

                // 添加 class
                if (preg_match('/class=["\']([^"\']*)["\']/i', $before . ' ' . $after, $cm)) {
                    // class 属性已存在，追加 lazyload
                    if (strpos($cm[1], 'lazyload') === false) {
                        if (preg_match('/class=["\']([^"\']*)["\']/i', $before, $bm)) {
                            $before = str_replace($bm[0], 'class="' . $cm[1] . ' lazyload"', $before);
                        } else {
                            $after = preg_replace('/class=["\']([^"\']*)["\']/i', 'class="$1 lazyload"', $after);
                        }
                    }
                } else {
                    $before .= ' class="lazyload"';
                }

                // 将 src 改为占位图，设置 data-src
                return '<img ' . $before . 'src="' . $placeholder . '" data-src="' . $src . '"' . $after . '>';
            },
            $content
        );

        return $content;
    }
    add_filter('the_content', 'onedown_lazyload_content_images', 100);
    add_filter('widget_text', 'onedown_lazyload_content_images', 100);
endif;

/**
 * 文章变更时清理查询缓存
 */
if (! function_exists('onedown_flush_post_query_cache')) :
    function onedown_flush_post_query_cache()
    {
        foreach (array('rank_hot', 'rank_new', 'rank_comment', 'sidebar_hot') as $key) {
            delete_transient('onedown_pq_' . $key);
        }
    }
    add_action('save_post',           'onedown_flush_post_query_cache');
    add_action('deleted_post',        'onedown_flush_post_query_cache');
    add_action('transition_post_status', 'onedown_flush_post_query_cache');
endif;

if (! function_exists('onedown_render_pagination')) :
    function onedown_get_pagination_args($query = null)
    {
        if (null === $query) {
            global $wp_query;
            $query = $wp_query;
        }

        $current = max(1, (int) $query->get('paged'), (int) $query->get('page'));
        $args    = array(
            'type'      => 'array',
            'total'     => (int) $query->max_num_pages,
            'current'   => $current,
            'prev_text' => '<i class="fa fa-angle-left"></i>',
            'next_text' => '<i class="fa fa-angle-right"></i>',
            'end_size'  => wp_is_mobile() ? 1 : 2,
            'mid_size'  => wp_is_mobile() ? 1 : 2,
        );

        if (is_category() && function_exists('onedown_build_category_default_path')) {
            $path = onedown_build_category_default_path(get_queried_object());
            if ($path) {
                $args['base']   = home_url('/' . trailingslashit($path) . '%_%');
                $args['format'] = 'page/%#%/';
            }
        }

        return $args;
    }

    function onedown_render_pagination($query = null)
    {
        if (null === $query) {
            global $wp_query;
            $query = $wp_query;
        }

        $total = (int) $query->max_num_pages;

        if ($total < 2) {
            return;
        }

        $links = paginate_links(onedown_get_pagination_args($query));

        if (empty($links)) {
            return;
        }

        echo '<nav class="post-pagination" aria-label="文章分页">';
        foreach ($links as $link) {
            // page-numbers current -> page-btn active (当前页高亮)
            // page-numbers dots   -> page-dot (省略号样式)
            // page-numbers        -> page-btn (普通页码)
            $link = str_replace(
                array('page-numbers current', 'page-numbers dots', 'page-numbers'),
                array('page-btn active',      'page-dot',          'page-btn'),
                $link
            );
            echo wp_kses_post($link);
        }
        echo '</nav>';
    }
endif;

/**
 * Custom Walker for threaded comments.
 */
if (! class_exists('Onedown_Walker_Comment')) :
    class Onedown_Walker_Comment extends Walker_Comment
    {
        protected function html5_comment($comment, $depth, $args)
        {
            $odd_class = ($GLOBALS['comment_alt'] ?? 0) % 2 ? ' comment-odd' : ' comment-even';
            $GLOBALS['comment_alt'] = ($GLOBALS['comment_alt'] ?? 0) + 1;
?>
            <div id="comment-<?php comment_ID(); ?>" <?php comment_class('comment-item' . $odd_class); ?>>
                <?php if (_pz('comments_avatar', true)) : ?>
                <div class="comment-avatar">
                    <?php echo onedown_get_avatar_html($comment, 42, '', get_comment_author(), array('class' => 'comment-avatar-img')); ?>
                </div>
                <?php endif; ?>
                <div class="comment-body">
                    <strong><?php echo get_comment_author_link(); ?></strong>
                    <div class="comment-meta">
                        <span class="comment-date"><?php echo esc_html(get_comment_date('Y-m-d H:i')); ?></span>
                        <span
                            class="comment-floor">#<?php echo esc_html($GLOBALS['comment_floor'] ?? 1);
                                                    $GLOBALS['comment_floor'] = ($GLOBALS['comment_floor'] ?? 1) + 1; ?></span>
                    </div>
                    <?php if ('0' === $comment->comment_approved) : ?>
                        <em class="comment-awaiting">评论等待审核。</em>
                    <?php endif; ?>
                    <div class="comment-text"><?php comment_text(); ?></div>
                    <div class="comment-reply">
                        <?php
                        comment_reply_link(array_merge($args, array(
                            'depth'      => $depth,
                            'max_depth'  => $args['max_depth'],
                            'before'     => '',
                            'after'      => '',
                            'reply_text' => '<i class="fa fa-reply"></i> 回复',
                        )));
                        ?>
                    </div>
                </div>
    <?php
        }

        public function start_lvl(&$output, $depth = 0, $args = array())
        {
            $output .= '<div class="comment-children">';
        }

        public function end_lvl(&$output, $depth = 0, $args = array())
        {
            $output .= '</div>';
        }
    }
endif;

/**
 * 从主题选项渲染页脚链接列表
 *
 * @param string $option_id 主题选项中的链接列表 ID（如 footer_product_links）
 */
if (! function_exists('onedown_footer_links_from_options')) :
    function onedown_footer_links_from_options($option_id)
    {
        $links = onedown_get_option($option_id, array());
        if (empty($links) || ! is_array($links)) {
            return;
        }
        foreach ($links as $link) {
            $text = isset($link['text']) ? trim($link['text']) : '';
            $url  = isset($link['url']) ? trim($link['url']) : '';
            if (empty($text) || empty($url)) {
                continue;
            }
            if (in_array($option_id, array('footer_product_links', 'footer_support_links'), true) && function_exists('onedown_footer_default_html_url')) {
                $url = onedown_footer_default_html_url($url);
            }
            echo '<li class="menu-item"><a href="' . esc_url($url) . '">' . esc_html($text) . '</a></li>';
        }
    }
endif;
