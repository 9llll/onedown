<?php

/**
 * 专题管理
 *
 * 注册"专题"自定义分类法，管理专题图片，文章编辑页专题选择面板
 */

if (! defined('ABSPATH')) {
    exit;
}

// ──────────────────────────────────────────
// 1. 注册专题分类法
// ──────────────────────────────────────────
add_action('init', 'onedown_register_topic_taxonomy');
function onedown_register_topic_taxonomy()
{
    $labels = array(
        'name'              => __('专题', 'onedown'),
        'singular_name'     => __('专题', 'onedown'),
        'search_items'      => __('搜索专题', 'onedown'),
        'all_items'         => __('全部专题', 'onedown'),
        'parent_item'       => __('父级专题', 'onedown'),
        'parent_item_colon' => __('父级专题：', 'onedown'),
        'edit_item'         => __('编辑专题', 'onedown'),
        'update_item'       => __('更新专题', 'onedown'),
        'add_new_item'      => __('添加新专题', 'onedown'),
        'new_item_name'     => __('新专题名称', 'onedown'),
        'menu_name'         => __('专题', 'onedown'),
    );

    register_taxonomy('topic', 'post', array(
        'labels'            => $labels,
        'hierarchical'      => true,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_menu'      => false, // 由下方子菜单控制显示位置
        'rewrite'           => array('slug' => 'topic', 'with_front' => false),
        'show_in_rest'      => true,
        'query_var'         => 'topic',
    ));

    // 触发固定链接刷新
    if (! get_option('onedown_topic_flushed')) {
        add_option('onedown_topic_flushed', 1);
    }
}

/**
 * 首次加载时刷新固定链接，确保专题 URL 生效
 */
add_action('init', 'onedown_flush_topic_rewrite', 20);
function onedown_flush_topic_rewrite()
{
    if (get_option('onedown_topic_flushed')) {
        delete_option('onedown_topic_flushed');
        flush_rewrite_rules();
    }
}

// ──────────────────────────────────────────
// 2. 将专题管理作为 OD 主题数据的子菜单（排在首位）
// ──────────────────────────────────────────
add_action('admin_menu', 'onedown_add_topic_submenu', 30);
function onedown_add_topic_submenu()
{
    global $submenu;

    // 先添加子菜单（此时会被追加到末尾）
    add_submenu_page(
        'onedown-orders',
        __('专题管理', 'onedown'),
        __('专题管理', 'onedown'),
        'manage_categories',
        'edit-tags.php?taxonomy=topic&post_type=post'
    );

    // 将专题菜单移到最前面
    if (isset($submenu['onedown-orders'])) {
        $topic_key = null;
        foreach ($submenu['onedown-orders'] as $k => $item) {
            if ($item[2] === 'edit-tags.php?taxonomy=topic&post_type=post') {
                $topic_key = $k;
                break;
            }
        }
        if ($topic_key !== null) {
            $topic_item = $submenu['onedown-orders'][$topic_key];
            unset($submenu['onedown-orders'][$topic_key]);
            array_unshift($submenu['onedown-orders'], $topic_item);
            // 重设索引
            $submenu['onedown-orders'] = array_values($submenu['onedown-orders']);
        }
    }
}

/**
 * 修复专题管理页面高亮菜单错误
 */
add_filter('parent_file', 'onedown_fix_topic_menu_highlight');
function onedown_fix_topic_menu_highlight($parent_file)
{
    global $current_screen, $submenu_file;

    if ($current_screen && $current_screen->taxonomy === 'topic') {
        $parent_file  = 'onedown-orders';
        $submenu_file = 'edit-tags.php?taxonomy=topic&post_type=post';
    }

    return $parent_file;
}

// ──────────────────────────────────────────
// 3. 专题图片字段（term meta）
// ──────────────────────────────────────────

// 在添加/编辑专题表单中添加图片字段
add_action('topic_add_form_fields', 'onedown_topic_image_field_add');
add_action('topic_edit_form_fields', 'onedown_topic_image_field_edit');

// 保存专题图片
add_action('created_topic', 'onedown_save_topic_image');
add_action('edited_topic',  'onedown_save_topic_image');

/**
 * 添加专题时的图片字段
 */
function onedown_topic_image_field_add()
{
?>
    <div class="form-field term-image-wrap">
        <label for="topic_image"><?php _e('专题图片', 'onedown'); ?></label>
        <div class="topic-image-upload">
            <input type="text" name="topic_image" id="topic_image" value="" class="widefat" style="margin-bottom:6px;"
                placeholder="<?php esc_attr_e('输入图片URL或点击上传', 'onedown'); ?>">
            <button type="button" class="button topic-image-upload-btn"><?php _e('上传图片', 'onedown'); ?></button>
        </div>
        <p class="description"><?php _e('设置专题的封面图片，建议比例 4:3', 'onedown'); ?></p>
    </div>
    <style>
        .topic-image-preview {
            display: inline-block;
            margin-top: 6px;
            max-width: 200px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
        }
    </style>
<?php
}

/**
 * 编辑专题时的图片字段
 */
function onedown_topic_image_field_edit($term)
{
    $image = get_term_meta($term->term_id, 'topic_image', true);
?>
    <tr class="form-field term-image-wrap">
        <th scope="row"><label for="topic_image"><?php _e('专题图片', 'onedown'); ?></label></th>
        <td>
            <div class="topic-image-upload">
                <input type="text" name="topic_image" id="topic_image" value="<?php echo esc_url($image); ?>"
                    class="widefat" style="margin-bottom:6px;"
                    placeholder="<?php esc_attr_e('输入图片URL或点击上传', 'onedown'); ?>">
                <button type="button" class="button topic-image-upload-btn"><?php _e('上传图片', 'onedown'); ?></button>
            </div>
            <?php if ($image) : ?>
                <div class="topic-image-preview">
                    <img src="<?php echo esc_url($image); ?>"
                        style="max-width:200px;max-height:150px;border-radius:4px;margin-top:6px;box-shadow:0 1px 3px rgba(0,0,0,.1);">
                </div>
            <?php endif; ?>
            <p class="description"><?php _e('设置专题的封面图片，建议比例 4:3', 'onedown'); ?></p>
        </td>
    </tr>
<?php
}

/**
 * 保存专题图片
 */
function onedown_save_topic_image($term_id)
{
    if (isset($_POST['topic_image'])) {
        $image = esc_url_raw($_POST['topic_image']);
        if ($image) {
            update_term_meta($term_id, 'topic_image', $image);
        } else {
            delete_term_meta($term_id, 'topic_image');
        }
    }
}

// ──────────────────────────────────────────
// 3. 后台专题列表添加图片列
// ──────────────────────────────────────────
add_filter('manage_edit-topic_columns', 'onedown_topic_columns');
add_filter('manage_topic_custom_column', 'onedown_topic_column_content', 10, 3);

function onedown_topic_columns($columns)
{
    $columns['topic_image'] = __('图片', 'onedown');
    return $columns;
}

function onedown_topic_column_content($content, $column_name, $term_id)
{
    if ('topic_image' === $column_name) {
        $image = get_term_meta($term_id, 'topic_image', true);
        if ($image) {
            $content = '<img src="' . esc_url($image) . '" style="width:50px;height:36px;object-fit:cover;border-radius:3px;">';
        }
    }
    return $content;
}

// ──────────────────────────────────────────
// 4. 文章编辑页专题选择面板
// ──────────────────────────────────────────
add_action('add_meta_boxes', 'onedown_add_topic_meta_box');
add_action('save_post',      'onedown_save_topic_meta_box', 10, 2);

function onedown_add_topic_meta_box()
{
    add_meta_box(
        'onedown_topic_panel',
        __('专题设置', 'onedown'),
        'onedown_topic_meta_box_callback',
        'post',
        'side',
        'default'
    );
}

function onedown_topic_meta_box_callback($post)
{
    wp_nonce_field('onedown_topic_meta_box', 'onedown_topic_meta_nonce');

    $current_terms = wp_get_post_terms($post->ID, 'topic', array('fields' => 'ids'));
    $topics        = get_terms(array(
        'taxonomy'   => 'topic',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ));

    if (empty($topics) || is_wp_error($topics)) {
        echo '<p style="color:#999;">' . __('暂无专题，请先在「专题」中添加。', 'onedown') . '</p>';
        echo '<p><a href="' . admin_url('edit-tags.php?taxonomy=topic&post_type=post') . '" class="button">' . __('管理专题', 'onedown') . '</a></p>';
        return;
    }

    echo '<div style="max-height:300px;overflow-y:auto;">';
    foreach ($topics as $topic) {
        $checked = in_array($topic->term_id, $current_terms) ? 'checked' : '';
        $image   = get_term_meta($topic->term_id, 'topic_image', true);
        echo '<label style="display:flex;align-items:center;gap:8px;padding:6px 4px;border-bottom:1px solid #f0f0f1;cursor:pointer;">';
        echo '<input type="checkbox" name="topic_terms[]" value="' . esc_attr($topic->term_id) . '" ' . $checked . '>';
        if ($image) {
            echo '<img src="' . esc_url($image) . '" style="width:32px;height:24px;object-fit:cover;border-radius:2px;flex-shrink:0;">';
        }
        echo '<span>' . esc_html($topic->name) . '</span>';
        echo '</label>';
    }
    echo '</div>';
    echo '<p style="margin-top:8px;"><a href="' . admin_url('edit-tags.php?taxonomy=topic&post_type=post') . '" class="button button-small">' . __('管理专题', 'onedown') . '</a></p>';
}

function onedown_save_topic_meta_box($post_id, $post)
{
    if (! isset($_POST['onedown_topic_meta_nonce']) || ! wp_verify_nonce($_POST['onedown_topic_meta_nonce'], 'onedown_topic_meta_box')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if ($post->post_type !== 'post') {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $terms = isset($_POST['topic_terms']) ? array_map('intval', $_POST['topic_terms']) : array();
    wp_set_post_terms($post_id, $terms, 'topic', false);
}

// ──────────────────────────────────────────
// 5. 后台加载媒体上传器 JS（专题图片上传）
// ──────────────────────────────────────────
add_action('admin_enqueue_scripts', 'onedown_topic_admin_scripts');
function onedown_topic_admin_scripts($hook)
{
    if (strpos($hook, 'edit-tags.php') === false && strpos($hook, 'term.php') === false) {
        return;
    }

    $screen = get_current_screen();
    if (! $screen || $screen->taxonomy !== 'topic') {
        return;
    }

    wp_enqueue_media();
    wp_add_inline_script('jquery', '
    jQuery(document).ready(function($) {
        $(document).on("click", ".topic-image-upload-btn", function(e) {
            e.preventDefault();
            var button = $(this);
            var container = button.closest(".topic-image-upload");
            var input = container.find("input");

            var frame = wp.media({
                title: "选择专题图片",
                multiple: false,
                library: { type: "image" },
            });

            frame.on("select", function() {
                var attachment = frame.state().get("selection").first().toJSON();
                input.val(attachment.url);

                // 更新或添加预览
                var preview = container.siblings(".topic-image-preview");
                if (preview.length) {
                    preview.find("img").attr("src", attachment.url);
                } else {
                    container.after(
                        "<div class=\"topic-image-preview\">" +
                        "<img src=\"" + attachment.url + "\" style=\"max-width:200px;max-height:150px;border-radius:4px;margin-top:6px;box-shadow:0 1px 3px rgba(0,0,0,.1);\">" +
                        "</div>"
                    );
                }
            });

            frame.open();
        });
    });
    ');
}

// ──────────────────────────────────────────
// 6. 辅助函数：获取专题图片
// ──────────────────────────────────────────
if (! function_exists('onedown_get_topic_image')) :
    function onedown_get_topic_image($term_id, $fallback = '')
    {
        $image = get_term_meta($term_id, 'topic_image', true);
        return $image ? $image : $fallback;
    }
endif;

// ──────────────────────────────────────────
// 7. 全部专题列表页 /topic/
// ──────────────────────────────────────────

/**
 * 全部专题列表重写规则
 */
add_action('generate_rewrite_rules', 'onedown_topic_list_rewrite_rules');
function onedown_topic_list_rewrite_rules($wp_rewrite)
{
    if (get_option('permalink_structure')) {
        $new_rules['topic/?$'] = 'index.php?od_topic_list=1';
        $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
    }
}

/**
 * 注册 od_topic_list 查询变量
 */
add_filter('query_vars', 'onedown_add_topic_list_query_vars');
function onedown_add_topic_list_query_vars($public_query_vars)
{
    if (! is_admin()) {
        $public_query_vars[] = 'od_topic_list';
    }
    return $public_query_vars;
}

/**
 * template_redirect 拦截全部专题列表路由，加载 topic-list.php
 */
add_action('template_redirect', 'onedown_topic_list_template_redirect', 5);
function onedown_topic_list_template_redirect()
{
    if (get_query_var('od_topic_list')) {
        global $wp_query;
        $wp_query->is_home    = false;
        $wp_query->is_404     = false;
        $wp_query->is_page    = true;
        $wp_query->is_singular = true;

        $template = get_theme_file_path('page-templates/topic-list.php');
        if (file_exists($template)) {
            load_template($template);
            exit;
        }
    }
}
