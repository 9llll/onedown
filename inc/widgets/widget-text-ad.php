<?php
/**
 * 文字广告小组件
 *
 * 显示已审核通过的文字广告列表，支持自助付费广告申请
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('widgets_init', function () {
    register_widget('OD_Widget_Text_Ad');
}, 1);

class OD_Widget_Text_Ad extends WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'   => 'od_text_ad',
            'description' => '显示文字广告列表，支持自助付费申请',
        );
        parent::__construct('od-text-ad', __('OD 文字广告', 'onedown'), $widget_ops);
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];

        $title = ! empty($instance['title']) ? $instance['title'] : '';
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $number     = ! empty($instance['number']) ? (int) $instance['number'] : 5;
        $show_apply = ! empty($instance['show_apply']);
        $orderby    = ! empty($instance['orderby']) ? $instance['orderby'] : 'rand';
        $gradient_enabled = ! empty($instance['gradient_enabled']);

        // 查询已审核通过（publish状态）的广告
        $query_args = array(
            'post_type'      => 'onedown_ad',
            'posts_per_page' => $number,
            'post_status'    => 'publish',
            'meta_key'       => '_ad_expire_date',
            'orderby'        => 'meta_value',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_ad_expire_date',
                    'value'   => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
        );

        if ($orderby === 'rand') {
            $query_args['orderby'] = 'rand';
        } elseif ($orderby === 'date') {
            unset($query_args['meta_key']);
            $query_args['orderby'] = 'date';
            $query_args['order']   = 'DESC';
        }

        $ad_query = new WP_Query($query_args);
        $ad_index = 0;
        $ad_total = max(1, (int) $ad_query->post_count);

        if ($ad_query->have_posts()) : ?>
            <div class="text-ad-list<?php echo $gradient_enabled ? ' is-gradient' : ''; ?>">
                <?php while ($ad_query->have_posts()) : $ad_query->the_post();
                    $ad_index++;
                    $ad_url = get_post_meta(get_the_ID(), '_ad_target_url', true);
                    $ad_text = get_the_title();
                    $ad_desc = get_the_excerpt();
                    $item_style = $gradient_enabled
                        ? '--text-ad-hover-bg:' . $this->get_auto_gradient_background($ad_index, $ad_total) . ';'
                        : '';
                ?>
                    <a class="text-ad-item" href="<?php echo esc_url($ad_url ?: '#'); ?>"
                       target="_blank" rel="nofollow noopener"
                       <?php echo $item_style ? 'style="' . esc_attr($item_style) . '"' : ''; ?>>
                        <strong class="text-ad-title"><span class="text-ad-badge">AD</span><?php echo esc_html($ad_text); ?></strong>
                        <?php if ($ad_desc) : ?>
                            <span class="text-ad-desc"><?php echo esc_html(wp_trim_words($ad_desc, 10)); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <div class="text-ad-empty" style="padding:12px;text-align:center;font-size:12px;color:var(--od-muted);">
                暂无广告
            </div>
        <?php endif;
        wp_reset_postdata();

        // 显示自助申请入口
        if ($show_apply && _pz('ad_self_service_enabled', false)) {
            $uc_url = function_exists('onedown_user_center_url') ? onedown_user_center_url(array('tab' => 'ad-apply')) : home_url('/user-center/?tab=ad-apply');
            echo '<div class="text-ad-apply" style="padding:8px 0 0;text-align:right;">';
            echo '<a href="' . esc_url($uc_url) . '" style="font-size:12px;color:var(--od-primary);text-decoration:none;">申请投放广告 <i class="fa fa-angle-right"></i></a>';
            echo '</div>';
        }

        echo $args['after_widget'];
    }

    public function form($instance)
    {
        $title      = ! empty($instance['title']) ? $instance['title'] : '';
        $number     = ! empty($instance['number']) ? (int) $instance['number'] : 5;
        $show_apply = ! empty($instance['show_apply']);
        $orderby    = ! empty($instance['orderby']) ? $instance['orderby'] : 'rand';
        $gradient_enabled = ! empty($instance['gradient_enabled']);
        ?>
        <p>
            <label>标题：<input type="text" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($title); ?>" placeholder="广告位"></label>
        </p>
        <p>
            <label>显示数量：
                <input type="number" class="widefat" name="<?php echo $this->get_field_name('number'); ?>" value="<?php echo esc_attr($number); ?>" min="1" max="50">
            </label>
        </p>
        <p>
            <label>排序：
                <select class="widefat" name="<?php echo $this->get_field_name('orderby'); ?>">
                    <option value="rand" <?php selected($orderby, 'rand'); ?>>随机</option>
                    <option value="date" <?php selected($orderby, 'date'); ?>>最新</option>
                </select>
            </label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($show_apply); ?> name="<?php echo $this->get_field_name('show_apply'); ?>"> 显示自助申请入口</label>
        </p>
        <p>
            <label><input type="checkbox" class="checkbox" <?php checked($gradient_enabled); ?> name="<?php echo $this->get_field_name('gradient_enabled'); ?>"> 启用自动渐变色</label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance                = $old_instance;
        $instance['title']       = wp_kses_post($new_instance['title']);
        $instance['number']      = (int) $new_instance['number'];
        $instance['orderby']     = in_array($new_instance['orderby'], array('rand', 'date')) ? $new_instance['orderby'] : 'rand';
        $instance['show_apply']  = ! empty($new_instance['show_apply']) ? 1 : 0;
        $instance['gradient_enabled'] = ! empty($new_instance['gradient_enabled']) ? 1 : 0;
        return $instance;
    }

    private function get_auto_gradient_background($index, $total)
    {
        $gradients = array(
            'linear-gradient(135deg,var(--od-primary),var(--od-primary-2))',
            'linear-gradient(135deg,var(--od-primary),var(--od-blue))',
            'linear-gradient(135deg,var(--od-blue),var(--od-cyan))',
            'linear-gradient(135deg,var(--od-primary),var(--od-yellow))',
        );

        $offset = (max(1, (int) $index) - 1) % count($gradients);
        return $gradients[$offset];
    }
}
