<?php

/**
 * Tab切换文字排行榜小组件
 *
 * 支持热门/最新/评论多标签切换的文章排行列表
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Text_Rank');
}, 1);

class OD_Widget_Text_Rank extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_text_rank',
            'description' => 'Tab切换的文章排行榜（热门/最新/评论）',
        );
        parent::__construct('od-text-rank', __('OD 文字排行', 'onedown'), $widget_ops);
    }

    /**
     * 渲染单个面板的 HTML（供 widget 和 AJAX 复用）
     *
     * @param string $key   面板标识：hot|new|comment
     * @param int    $number 文章数量
     * @return string
     */
    public static function render_panel_html($key, $number)
    {
        $cache_key = 'rank_' . $key;

        $query_args = array(
            'posts_per_page'      => $number,
            'ignore_sticky_posts' => 1,
            'post_status'         => 'publish',
        );

        switch ($key) {
            case 'hot':
                $query_args['orderby']  = 'meta_value_num';
                $query_args['meta_key'] = 'views';
                $query_args['order']    = 'DESC';
                break;
            case 'new':
                $query_args['orderby'] = 'date';
                $query_args['order']   = 'DESC';
                break;
            case 'comment':
                $query_args['orderby'] = 'comment_count';
                $query_args['order']   = 'DESC';
                break;
            default:
                return '';
        }

        $posts = onedown_cached_posts($cache_key, $query_args);

        // hot 面板按浏览数查不到时，降级按评论数排序
        if (empty($posts) && 'hot' === $key) {
            $query_args['orderby']  = 'comment_count';
            $query_args['meta_key'] = '';
            $posts = onedown_cached_posts($cache_key . '_fallback', $query_args);
        }

        ob_start();
        if (! empty($posts)) :
            $index = 1;
            foreach ($posts as $post) :
                $permalink = get_permalink($post->ID);
                $title     = get_the_title($post->ID);
                ?>
                <a href="<?php echo esc_url($permalink); ?>">
                    <span><?php echo $index; ?></span>
                    <strong><?php echo esc_html($title); ?></strong>
                </a>
                <?php
                $index++;
            endforeach;
        else : ?>
            <p class="muted-color" style="padding:10px;font-size:12px;">暂无内容</p>
        <?php endif;
        return ob_get_clean();
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : '排行榜';
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $number   = ! empty($instance['number']) ? (int) $instance['number'] : 5;
        $panels   = array(
            'hot'     => __('热门', 'onedown'),
            'new'     => __('最新', 'onedown'),
            'comment' => __('评论', 'onedown'),
        );
        $active_tab = 'hot';
        $nonce      = wp_create_nonce('od_rank_ajax');
?>
        <div class="text-rank-tabs" role="tablist" aria-label="<?php esc_attr_e('文字排行榜', 'onedown'); ?>">
            <?php foreach ($panels as $key => $label) : ?>
                <button class="<?php echo $key === $active_tab ? 'active' : ''; ?>" type="button"
                    data-rank-tab="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($panels as $key => $label) :
            $is_active = $key === $active_tab; ?>
            <div class="text-rank-panel <?php echo $is_active ? 'active' : ''; ?>"
                data-rank-panel="<?php echo esc_attr($key); ?>"
                data-rank-nonce="<?php echo esc_attr($nonce); ?>"
                data-rank-number="<?php echo esc_attr($number); ?>"
                <?php echo $is_active ? 'data-rank-loaded="true"' : ''; ?>>
                <?php
                if ($is_active) {
                    echo self::render_panel_html($key, $number);
                } else {
                    // 加载占位，点击标签后 AJAX 动态加载
                    echo '<div class="rank-panel-loading"><span class="rank-loading-dot"></span><span class="rank-loading-dot"></span><span class="rank-loading-dot"></span></div>';
                }
                ?>
            </div>
        <?php endforeach;

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title  = ! empty($instance['title']) ? $instance['title'] : '';
        $number = ! empty($instance['number']) ? (int) $instance['number'] : 5;
        ?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>"
                    value="<?php echo esc_attr($title); ?>" placeholder="排行榜"></label>
        </p>
        <p>
            <label>每栏显示数量：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('number'); ?>"
                    value="<?php echo esc_attr($number); ?>" min="1" max="20">
            </label>
        </p>
<?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance              = $old_instance;
        $instance['title']     = wp_kses_post($new_instance['title']);
        $instance['number']    = (int) $new_instance['number'];
        return $instance;
    }
}

// ── AJAX 动态加载面板 ──
add_action('wp_ajax_od_rank_panel', 'od_rank_panel_ajax');
add_action('wp_ajax_nopriv_od_rank_panel', 'od_rank_panel_ajax');
function od_rank_panel_ajax()
{
    check_ajax_referer('od_rank_ajax', 'nonce');

    $tab    = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : '';
    $number = isset($_POST['number']) ? min(20, max(1, (int) $_POST['number'])) : 5;

    if (! in_array($tab, array('hot', 'new', 'comment'), true)) {
        wp_send_json_error(array('message' => 'Invalid tab'));
    }

    $html = OD_Widget_Text_Rank::render_panel_html($tab, $number);
    wp_send_json_success(array('html' => $html));
}
