<?php
/**
 * 友情链接小组件
 *
 * 显示友情链接或合作伙伴列表
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('init', 'onedown_register_friend_link_post_type');
function onedown_register_friend_link_post_type()
{
    register_post_type('onedown_friend_link', array(
        'labels' => array(
            'name'          => __('友情链接', 'onedown'),
            'singular_name' => __('友情链接', 'onedown'),
            'add_new_item'  => __('添加友情链接', 'onedown'),
            'edit_item'     => __('编辑友情链接', 'onedown'),
            'all_items'     => __('全部友情链接', 'onedown'),
            'menu_name'     => __('友情链接', 'onedown'),
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => false,
        'supports'     => array('title', 'excerpt'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ));

    register_taxonomy('friend_link_cat', 'onedown_friend_link', array(
        'labels' => array(
            'name'          => __('友链分类', 'onedown'),
            'singular_name' => __('友链分类', 'onedown'),
            'menu_name'     => __('友链分类', 'onedown'),
        ),
        'public'       => false,
        'show_ui'      => true,
        'show_admin_column' => true,
        'hierarchical' => true,
    ));
}

add_action('admin_menu', 'onedown_friend_link_admin_menu', 40);
function onedown_friend_link_admin_menu()
{
    global $submenu;

    add_submenu_page(
        'onedown-orders',
        __('友情链接', 'onedown'),
        __('友情链接', 'onedown'),
        'edit_posts',
        'edit.php?post_type=onedown_friend_link'
    );

    add_submenu_page(
        'onedown-orders',
        __('友链分类', 'onedown'),
        __('友链分类', 'onedown'),
        'manage_categories',
        'edit-tags.php?taxonomy=friend_link_cat&post_type=onedown_friend_link'
    );

    if (empty($submenu['onedown-orders'])) {
        return;
    }

    $order = array(
        'edit-tags.php?taxonomy=topic&post_type=post',
        'edit.php?post_type=onedown_friend_link',
        'onedown-orders',
        'onedown-withdrawals',
        'edit-tags.php?taxonomy=friend_link_cat&post_type=onedown_friend_link',
    );

    usort($submenu['onedown-orders'], function ($a, $b) use ($order) {
        $a_pos = array_search($a[2], $order, true);
        $b_pos = array_search($b[2], $order, true);
        $a_pos = $a_pos === false ? 999 : $a_pos;
        $b_pos = $b_pos === false ? 999 : $b_pos;

        if ($a_pos === $b_pos) {
            return 0;
        }

        return $a_pos < $b_pos ? -1 : 1;
    });
}

add_filter('parent_file', 'onedown_friend_link_menu_highlight');
function onedown_friend_link_menu_highlight($parent_file)
{
    global $current_screen, $submenu_file;

    if (! $current_screen) {
        return $parent_file;
    }

    if ($current_screen->post_type === 'onedown_friend_link') {
        $parent_file  = 'onedown-orders';
        $submenu_file = 'edit.php?post_type=onedown_friend_link';
    }

    if ($current_screen->taxonomy === 'friend_link_cat') {
        $parent_file  = 'onedown-orders';
        $submenu_file = 'edit-tags.php?taxonomy=friend_link_cat&post_type=onedown_friend_link';
    }

    return $parent_file;
}

add_action('add_meta_boxes', 'onedown_friend_link_metaboxes');
function onedown_friend_link_metaboxes()
{
    add_meta_box('onedown_friend_link_info', __('友链信息', 'onedown'), 'onedown_friend_link_metabox', 'onedown_friend_link', 'normal', 'high');
}

function onedown_friend_link_metabox($post)
{
    wp_nonce_field('onedown_friend_link_save', 'onedown_friend_link_nonce');
    $url      = get_post_meta($post->ID, '_friend_link_url', true);
    $contact  = get_post_meta($post->ID, '_friend_link_contact', true);
    $keywords = get_post_meta($post->ID, '_friend_link_keywords', true);
    $favicon  = get_post_meta($post->ID, '_friend_link_favicon', true);
    $sticky   = get_post_meta($post->ID, '_friend_link_sticky', true);
    $featured = get_post_meta($post->ID, '_friend_link_featured', true);
    ?>
    <p>
        <label><?php _e('链接地址', 'onedown'); ?></label>
        <input type="url" class="widefat" name="friend_link_url" value="<?php echo esc_attr($url); ?>" placeholder="https://example.com">
    </p>
    <p>
        <label><?php _e('关键词', 'onedown'); ?></label>
        <input type="text" class="widefat" name="friend_link_keywords" value="<?php echo esc_attr($keywords); ?>" placeholder="keywords">
    </p>
    <p>
        <label><?php _e('站点图标', 'onedown'); ?></label>
        <input type="url" class="widefat" name="friend_link_favicon" value="<?php echo esc_attr($favicon); ?>" placeholder="<?php echo esc_attr(onedown_friend_link_default_icon_url()); ?>">
    </p>
    <p>
        <label><?php _e('联系方式', 'onedown'); ?></label>
        <input type="text" class="widefat" name="friend_link_contact" value="<?php echo esc_attr($contact); ?>" placeholder="QQ / 微信 / 邮箱">
    </p>
    <p>
        <label><input type="checkbox" name="friend_link_featured" value="1" <?php checked($featured, '1'); ?>> <?php _e('推荐', 'onedown'); ?></label>
        &nbsp;&nbsp;
        <label><input type="checkbox" name="friend_link_sticky" value="1" <?php checked($sticky, '1'); ?>> <?php _e('置顶', 'onedown'); ?></label>
    </p>
    <?php
}

add_action('save_post_onedown_friend_link', 'onedown_save_friend_link_meta');
function onedown_save_friend_link_meta($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    if (! empty($_POST['friend_link_quick_edit']) && isset($_POST['_inline_edit']) && wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce')) {
        update_post_meta($post_id, '_friend_link_featured', ! empty($_POST['friend_link_featured']) ? '1' : '0');
        update_post_meta($post_id, '_friend_link_sticky', ! empty($_POST['friend_link_sticky']) ? '1' : '0');
        return;
    }

    if (! empty($_REQUEST['friend_link_bulk_edit']) && isset($_REQUEST['_inline_edit']) && wp_verify_nonce($_REQUEST['_inline_edit'], 'inlineeditnonce')) {
        if (isset($_REQUEST['friend_link_featured']) && $_REQUEST['friend_link_featured'] !== '') {
            update_post_meta($post_id, '_friend_link_featured', $_REQUEST['friend_link_featured'] === '1' ? '1' : '0');
        }
        if (isset($_REQUEST['friend_link_sticky']) && $_REQUEST['friend_link_sticky'] !== '') {
            update_post_meta($post_id, '_friend_link_sticky', $_REQUEST['friend_link_sticky'] === '1' ? '1' : '0');
        }
        return;
    }

    if (! isset($_POST['onedown_friend_link_nonce']) || ! wp_verify_nonce($_POST['onedown_friend_link_nonce'], 'onedown_friend_link_save')) {
        return;
    }

    update_post_meta($post_id, '_friend_link_url', isset($_POST['friend_link_url']) ? esc_url_raw($_POST['friend_link_url']) : '');
    update_post_meta($post_id, '_friend_link_contact', isset($_POST['friend_link_contact']) ? sanitize_text_field($_POST['friend_link_contact']) : '');
    update_post_meta($post_id, '_friend_link_keywords', isset($_POST['friend_link_keywords']) ? sanitize_text_field($_POST['friend_link_keywords']) : '');
    update_post_meta($post_id, '_friend_link_favicon', isset($_POST['friend_link_favicon']) ? esc_url_raw($_POST['friend_link_favicon']) : '');
    update_post_meta($post_id, '_friend_link_featured', ! empty($_POST['friend_link_featured']) ? '1' : '0');
    update_post_meta($post_id, '_friend_link_sticky', ! empty($_POST['friend_link_sticky']) ? '1' : '0');
}

add_filter('manage_onedown_friend_link_posts_columns', 'onedown_friend_link_admin_columns');
function onedown_friend_link_admin_columns($columns)
{
    $date = isset($columns['date']) ? $columns['date'] : null;
    unset($columns['date']);

    $columns['friend_link_featured'] = __('推荐', 'onedown');
    $columns['friend_link_sticky']   = __('置顶', 'onedown');

    if ($date !== null) {
        $columns['date'] = $date;
    }

    return $columns;
}

add_action('manage_onedown_friend_link_posts_custom_column', 'onedown_friend_link_admin_column_content', 10, 2);
function onedown_friend_link_admin_column_content($column, $post_id)
{
    if ($column !== 'friend_link_featured' && $column !== 'friend_link_sticky') {
        return;
    }

    $meta_key = $column === 'friend_link_featured' ? '_friend_link_featured' : '_friend_link_sticky';
    $enabled  = get_post_meta($post_id, $meta_key, true) === '1';
    echo '<span class="' . esc_attr($column) . '-value" data-value="' . esc_attr($enabled ? '1' : '0') . '">' . esc_html($enabled ? '是' : '否') . '</span>';
}

add_action('quick_edit_custom_box', 'onedown_friend_link_quick_edit_fields', 10, 2);
function onedown_friend_link_quick_edit_fields($column_name, $post_type)
{
    if ($post_type !== 'onedown_friend_link' || $column_name !== 'friend_link_featured') {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <input type="hidden" name="friend_link_quick_edit" value="1">
            <label class="alignleft">
                <input type="checkbox" name="friend_link_featured" value="1">
                <span class="checkbox-title"><?php _e('推荐', 'onedown'); ?></span>
            </label>
            <label class="alignleft" style="margin-left:16px;">
                <input type="checkbox" name="friend_link_sticky" value="1">
                <span class="checkbox-title"><?php _e('置顶', 'onedown'); ?></span>
            </label>
        </div>
    </fieldset>
    <?php
}

add_action('bulk_edit_custom_box', 'onedown_friend_link_bulk_edit_fields', 10, 2);
function onedown_friend_link_bulk_edit_fields($column_name, $post_type)
{
    if ($post_type !== 'onedown_friend_link' || $column_name !== 'friend_link_featured') {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <input type="hidden" name="friend_link_bulk_edit" value="1">
            <label>
                <span class="title"><?php _e('推荐', 'onedown'); ?></span>
                <select name="friend_link_featured">
                    <option value=""><?php _e('不变', 'onedown'); ?></option>
                    <option value="1"><?php _e('启用', 'onedown'); ?></option>
                    <option value="0"><?php _e('关闭', 'onedown'); ?></option>
                </select>
            </label>
            <label>
                <span class="title"><?php _e('置顶', 'onedown'); ?></span>
                <select name="friend_link_sticky">
                    <option value=""><?php _e('不变', 'onedown'); ?></option>
                    <option value="1"><?php _e('启用', 'onedown'); ?></option>
                    <option value="0"><?php _e('关闭', 'onedown'); ?></option>
                </select>
            </label>
        </div>
    </fieldset>
    <?php
}

add_action('admin_footer-edit.php', 'onedown_friend_link_quick_edit_script');
function onedown_friend_link_quick_edit_script()
{
    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== 'onedown_friend_link') {
        return;
    }
    ?>
    <script>
    jQuery(function($) {
        var wpInlineEdit = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            wpInlineEdit.apply(this, arguments);

            var postId = 0;
            if (typeof id === 'object') {
                postId = parseInt(this.getId(id), 10);
            }
            if (!postId) {
                return;
            }

            var $row = $('#post-' + postId);
            var $editRow = $('#edit-' + postId);
            $editRow.find('input[name="friend_link_featured"]').prop('checked', $row.find('.friend_link_featured-value').data('value') == 1);
            $editRow.find('input[name="friend_link_sticky"]').prop('checked', $row.find('.friend_link_sticky-value').data('value') == 1);
        };
    });
    </script>
    <?php
}

function onedown_friend_link_default_icon_url()
{
    return get_theme_file_uri('assets/img/friend-link-default.svg');
}

function onedown_friend_link_is_fetchable_url($url)
{
    $parts = wp_parse_url($url);
    if (empty($parts['scheme']) || empty($parts['host']) || ! in_array(strtolower($parts['scheme']), array('http', 'https'), true)) {
        return false;
    }

    $host = strtolower($parts['host']);
    if (in_array($host, array('localhost', '127.0.0.1', '::1'), true)) {
        return false;
    }

    $ips = gethostbynamel($host);
    if (empty($ips)) {
        return true;
    }

    foreach ($ips as $ip) {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
    }

    return true;
}

function onedown_friend_link_abs_url($href, $base_url)
{
    $href = trim((string) $href);
    if ($href === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $href)) {
        return esc_url_raw($href);
    }
    if (strpos($href, '//') === 0) {
        return esc_url_raw('https:' . $href);
    }

    $base = wp_parse_url($base_url);
    if (empty($base['scheme']) || empty($base['host'])) {
        return '';
    }

    $root = $base['scheme'] . '://' . $base['host'];
    if (! empty($base['port'])) {
        $root .= ':' . $base['port'];
    }

    if (strpos($href, '/') === 0) {
        return esc_url_raw($root . $href);
    }

    $path = empty($base['path']) ? '/' : $base['path'];
    $dir  = trailingslashit(dirname($path));
    return esc_url_raw($root . $dir . $href);
}

function onedown_friend_link_fetch_site_meta($url)
{
    $data = array(
        'title'       => '',
        'description' => '',
        'keywords'    => '',
        'favicon'     => '',
    );

    if (! onedown_friend_link_is_fetchable_url($url)) {
        return $data;
    }

    $response = wp_remote_get($url, array(
        'timeout'             => 6,
        'redirection'         => 3,
        'limit_response_size' => 524288,
        'user-agent'          => 'OneDown Friend Link Bot; ' . home_url('/'),
    ));

    if (is_wp_error($response)) {
        return $data;
    }

    $body = wp_remote_retrieve_body($response);
    if ($body === '') {
        return $data;
    }

    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $matches)) {
        $data['title'] = sanitize_text_field(html_entity_decode(wp_strip_all_tags($matches[1]), ENT_QUOTES, get_bloginfo('charset')));
    }

    if (preg_match_all('/<meta\s+[^>]+>/i', $body, $matches)) {
        foreach ($matches[0] as $meta) {
            if (! preg_match('/\s(?:name|property)=["\']([^"\']+)["\']/i', $meta, $name_match)) {
                continue;
            }
            if (! preg_match('/\scontent=["\']([^"\']*)["\']/i', $meta, $content_match)) {
                continue;
            }

            $name    = strtolower(trim($name_match[1]));
            $content = sanitize_text_field(html_entity_decode($content_match[1], ENT_QUOTES, get_bloginfo('charset')));
            if ($content === '') {
                continue;
            }

            if (in_array($name, array('description', 'og:description'), true) && $data['description'] === '') {
                $data['description'] = $content;
            }
            if ($name === 'keywords' && $data['keywords'] === '') {
                $data['keywords'] = $content;
            }
        }
    }

    if (preg_match_all('/<link\s+[^>]+>/i', $body, $matches)) {
        foreach ($matches[0] as $link) {
            if (! preg_match('/\srel=["\']([^"\']+)["\']/i', $link, $rel_match) || stripos($rel_match[1], 'icon') === false) {
                continue;
            }
            if (preg_match('/\shref=["\']([^"\']+)["\']/i', $link, $href_match)) {
                $data['favicon'] = onedown_friend_link_abs_url($href_match[1], $url);
                break;
            }
        }
    }

    if ($data['favicon'] === '') {
        $parts = wp_parse_url($url);
        if (! empty($parts['scheme']) && ! empty($parts['host'])) {
            $data['favicon'] = esc_url_raw($parts['scheme'] . '://' . $parts['host'] . '/favicon.ico');
        }
    }

    return $data;
}

add_action('wp_ajax_onedown_friend_link_apply', 'onedown_ajax_friend_link_apply');
add_action('wp_ajax_nopriv_onedown_friend_link_apply', 'onedown_ajax_friend_link_apply');
add_action('wp_ajax_onedown_friend_link_fetch', 'onedown_ajax_friend_link_fetch');
add_action('wp_ajax_nopriv_onedown_friend_link_fetch', 'onedown_ajax_friend_link_fetch');
function onedown_ajax_friend_link_fetch()
{
    if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'onedown_friend_link_apply')) {
        wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
    }

    $url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    if ($url === '') {
        wp_send_json_error(array('msg' => '请先输入链接地址'));
    }

    $site_meta = onedown_friend_link_fetch_site_meta($url);
    if (empty($site_meta['title']) && empty($site_meta['description']) && empty($site_meta['keywords'])) {
        wp_send_json_error(array('msg' => '获取失败，请手动填写'));
    }

    wp_send_json_success(array(
        'msg'         => '获取成功',
        'title'       => $site_meta['title'],
        'description' => $site_meta['description'],
        'keywords'    => $site_meta['keywords'],
        'favicon'     => $site_meta['favicon'] ? $site_meta['favicon'] : onedown_friend_link_default_icon_url(),
    ));
}

function onedown_ajax_friend_link_apply()
{
    if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'onedown_friend_link_apply')) {
        wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
    }

    $name     = isset($_POST['site_name']) ? sanitize_text_field($_POST['site_name']) : '';
    $url      = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    $desc     = isset($_POST['site_desc']) ? sanitize_textarea_field($_POST['site_desc']) : '';
    $contact  = isset($_POST['contact']) ? sanitize_text_field($_POST['contact']) : '';
    $category = isset($_POST['category']) ? absint($_POST['category']) : 0;

    if ($name === '' || $url === '') {
        wp_send_json_error(array('msg' => '请填写站点名称和链接地址'));
    }

    $site_meta = onedown_friend_link_fetch_site_meta($url);
    $title     = $site_meta['title'] ? $site_meta['title'] : $name;
    $excerpt   = $site_meta['description'] ? $site_meta['description'] : $desc;
    $favicon   = $site_meta['favicon'] ? $site_meta['favicon'] : onedown_friend_link_default_icon_url();

    $post_id = wp_insert_post(array(
        'post_title'   => $title,
        'post_excerpt' => $excerpt,
        'post_type'    => 'onedown_friend_link',
        'post_status'  => 'publish',
    ), true);

    if (is_wp_error($post_id)) {
        wp_send_json_error(array('msg' => '提交失败，请稍后重试'));
    }

    update_post_meta($post_id, '_friend_link_url', $url);
    update_post_meta($post_id, '_friend_link_contact', $contact);
    update_post_meta($post_id, '_friend_link_apply_name', $name);
    update_post_meta($post_id, '_friend_link_keywords', $site_meta['keywords']);
    update_post_meta($post_id, '_friend_link_favicon', $favicon);
    if ($category) {
        wp_set_object_terms($post_id, array($category), 'friend_link_cat');
    }

    wp_send_json_success(array('msg' => '申请已提交，已自动审核通过'));
}

function onedown_friend_links_page_url()
{
    $pages = get_pages(array(
        'meta_key'   => '_wp_page_template',
        'meta_value' => 'page-templates/friend-links.php',
        'number'     => 1,
    ));

    return home_url('/friend-links.html');
}

add_action('init', 'onedown_friend_links_rewrite_rule');
function onedown_friend_links_rewrite_rule()
{
    add_rewrite_rule('^friend-links\.html$', 'index.php?onedown_friend_links_page=1', 'top');
    add_rewrite_rule('^friend-links/?$', 'index.php?onedown_friend_links_page=1', 'top');
}

add_filter('query_vars', 'onedown_friend_links_query_vars');
function onedown_friend_links_query_vars($vars)
{
    $vars[] = 'onedown_friend_links_page';
    return $vars;
}

add_filter('template_include', 'onedown_friend_links_template_include');
function onedown_friend_links_template_include($template)
{
    $request_path = isset($GLOBALS['wp']->request) ? trim($GLOBALS['wp']->request, '/') : '';
    if (! get_query_var('onedown_friend_links_page') && $request_path !== 'friend-links' && $request_path !== 'friend-links.html') {
        return $template;
    }

    if ($request_path === 'friend-links') {
        wp_safe_redirect(onedown_friend_links_page_url(), 301);
        exit;
    }

    global $wp_query;
    if ($wp_query) {
        $wp_query->is_404 = false;
    }
    status_header(200);

    $friend_links_template = locate_template('page-templates/friend-links.php');
    return $friend_links_template ? $friend_links_template : $template;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Links');
}, 1);

function onedown_get_friend_link_widget_items($limit = 9)
{
    $limit = max(1, min(30, (int) $limit));
    $query = new WP_Query(array(
        'post_type'              => 'onedown_friend_link',
        'post_status'            => 'publish',
        'posts_per_page'         => 60,
        'orderby'                => 'date',
        'order'                  => 'DESC',
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ));

    if (empty($query->posts)) {
        return array();
    }

    $items = $query->posts;
    usort($items, function ($a, $b) {
        $a_sticky   = get_post_meta($a->ID, '_friend_link_sticky', true) === '1' ? 1 : 0;
        $b_sticky   = get_post_meta($b->ID, '_friend_link_sticky', true) === '1' ? 1 : 0;
        $a_featured = get_post_meta($a->ID, '_friend_link_featured', true) === '1' ? 1 : 0;
        $b_featured = get_post_meta($b->ID, '_friend_link_featured', true) === '1' ? 1 : 0;

        if ($a_sticky !== $b_sticky) {
            return $b_sticky - $a_sticky;
        }
        if ($a_featured !== $b_featured) {
            return $b_featured - $a_featured;
        }

        return strcmp($b->post_date, $a->post_date);
    });

    return array_slice($items, 0, $limit);
}

class OD_Widget_Links extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_links',
            'description' => '显示友情链接/合作伙伴列表',
        );
        parent::__construct('od-links', __('OD 友情链接', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $limit      = ! empty($instance['limit']) ? (int) $instance['limit'] : 9;
        $items      = onedown_get_friend_link_widget_items($limit);
        $open_new   = ! empty($instance['open_new']);
        $show_more  = ! isset($instance['show_more']) || ! empty($instance['show_more']);
        $more_url   = ! empty($instance['more_url']) ? $instance['more_url'] : onedown_friend_links_page_url();
        $title      = ! empty($instance['title']) ? $instance['title'] : '';
        if ($title) {
            echo '<div class="friend-links-head">';
            echo $args['before_title'] . $title . $args['after_title'];
            if ($show_more) {
                echo '<a class="friend-links-more" href="' . esc_url($more_url) . '">更多</a>';
            }
            echo '</div>';
        }

        if (! empty($items)) : ?>
            <div class="friend-links">
                <?php foreach ($items as $item) :
                    $url  = get_post_meta($item->ID, '_friend_link_url', true);
                    $url  = $url ? $url : '#';
                    $name = get_the_title($item);
                    $target = $open_new ? ' target="_blank" rel="noopener"' : '';
                ?>
                    <a href="<?php echo esc_url($url); ?>"<?php echo $target; ?>><?php echo esc_html($name); ?></a>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="friend-links">
                <a href="#">友情链接</a>
            </div>
        <?php endif;

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title      = ! empty($instance['title']) ? $instance['title'] : '';
        $limit      = ! empty($instance['limit']) ? (int) $instance['limit'] : 9;
        $open_new   = ! empty($instance['open_new']);
        $show_more  = ! isset($instance['show_more']) || ! empty($instance['show_more']);
        $more_url   = ! empty($instance['more_url']) ? $instance['more_url'] : '';
        ?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" placeholder="友情链接"></label>
        </p>

        <p>
            <label>显示数量：
                <input type="number" class="tiny-text" min="1" max="30" name="<?php echo $this->get_field_name('limit'); ?>" value="<?php echo esc_attr($limit); ?>">
            </label>
            <span class="description">默认读取后台友情链接，置顶优先，其次推荐，再按最新添加显示。</span>
        </p>

        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($open_new); ?> name="<?php echo $this->get_field_name('open_new'); ?>"> 新窗口打开</label>
        </p>

        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_more); ?> name="<?php echo $this->get_field_name('show_more'); ?>"> 显示更多入口</label>
        </p>
        <p>
            <label>更多页链接：
                <input type="url" class="widefat" name="<?php echo $this->get_field_name('more_url'); ?>" value="<?php echo esc_attr($more_url); ?>" placeholder="<?php echo esc_attr(onedown_friend_links_page_url()); ?>">
            </label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        $instance['title']      = wp_kses_post($new_instance['title']);
        $instance['limit']      = isset($new_instance['limit']) ? max(1, min(30, (int) $new_instance['limit'])) : 9;
        $instance['open_new']   = ! empty($new_instance['open_new']) ? 1 : 0;
        $instance['show_more']  = ! empty($new_instance['show_more']) ? 1 : 0;
        $instance['more_url']   = ! empty($new_instance['more_url']) ? esc_url_raw($new_instance['more_url']) : '';
        unset($instance['items']);

        return $instance;
    }
}
