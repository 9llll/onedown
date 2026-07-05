<?php

/**
 * Onedown 广告系统
 *
 * 自定义文章类型、自助付费申请、订单处理、管理功能
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

// ──────────────────────────────────────────────
// 1. 注册自定义文章类型
// ──────────────────────────────────────────────

add_action('init', 'onedown_register_ad_post_type');
function onedown_register_ad_post_type()
{
    $labels = array(
        'name'               => __('广告管理', 'onedown'),
        'singular_name'      => __('广告', 'onedown'),
        'add_new'            => __('投放广告', 'onedown'),
        'add_new_item'       => __('新增广告', 'onedown'),
        'edit_item'          => __('编辑广告', 'onedown'),
        'view_item'          => __('查看广告', 'onedown'),
        'search_items'       => __('搜索广告', 'onedown'),
        'not_found'          => __('暂无广告', 'onedown'),
        'not_found_in_trash' => __('回收站中暂无广告', 'onedown'),
        'all_items'          => __('全部广告', 'onedown'),
        'menu_name'          => __('广告管理', 'onedown'),
    );

    $args = array(
        'labels'       => $labels,
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => false, // 通过子菜单添加到OD主题数据下
        'supports'     => array('title', 'excerpt'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'rewrite'      => false,
    );

    register_post_type('onedown_ad', $args);
}

// 将广告管理添加到"OD主题数据"菜单下
add_action('admin_menu', 'onedown_add_ad_submenu', 20);
function onedown_add_ad_submenu()
{
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        return;
    }

    add_submenu_page(
        'onedown-orders',
        __('广告管理', 'onedown'),
        __('广告管理', 'onedown'),
        'manage_options',
        'edit.php?post_type=onedown_ad'
    );
}

// ──────────────────────────────────────────────
// 2. 文章列表管理列
// ──────────────────────────────────────────────

add_filter('manage_onedown_ad_posts_columns', 'onedown_ad_manage_columns');
function onedown_ad_manage_columns($columns)
{
    $columns['ad_url']      = __('目标链接', 'onedown');
    $columns['ad_user']     = __('申请人', 'onedown');
    $columns['ad_expire']   = __('到期时间', 'onedown');
    $columns['ad_order_id'] = __('关联订单', 'onedown');
    unset($columns['date']);
    return $columns;
}

add_action('manage_onedown_ad_posts_custom_column', 'onedown_ad_manage_columns_content', 10, 2);
function onedown_ad_manage_columns_content($column, $post_id)
{
    switch ($column) {
        case 'ad_url':
            $url = get_post_meta($post_id, '_ad_target_url', true);
            echo $url ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($url) . '</a>' : '—';
            break;

        case 'ad_user':
            $user_id = (int) get_post_meta($post_id, '_ad_user_id', true);
            if ($user_id) {
                $user = get_userdata($user_id);
                echo $user ? esc_html($user->display_name) . ' (ID: ' . $user_id . ')' : '—';
            } else {
                echo '—';
            }
            break;

        case 'ad_expire':
            $expire = get_post_meta($post_id, '_ad_expire_date', true);
            echo $expire ? esc_html($expire) : '—';
            break;

        case 'ad_order_id':
            $order_id = get_post_meta($post_id, '_ad_order_id', true);
            echo $order_id ? esc_html($order_id) : '—';
            break;
    }
}

// ──────────────────────────────────────────────
// 3. 广告发布/过期自动处理
// ──────────────────────────────────────────────

/**
 * 广告支付成功后处理
 *
 * @param string $order_id
 */
function onedown_ad_payment_success($order_id)
{
    $order = onedown_get_order($order_id);
    if (! $order || $order->order_type !== 'ad') {
        return;
    }

    $ad_id = (int) $order->post_id;

    // 设置广告到期时间
    $ad_days = (int) _pz('ad_duration_days', 30);
    $expire_date = date('Y-m-d', strtotime('+' . $ad_days . ' days'));

    update_post_meta($ad_id, '_ad_order_id', $order_id);
    update_post_meta($ad_id, '_ad_expire_date', $expire_date);
    update_post_meta($ad_id, '_ad_user_id', $order->user_id);

    // 支付成功后自动审核通过并发布
    wp_update_post(array(
        'ID'          => $ad_id,
        'post_status' => 'publish',
    ));
}

// ──────────────────────────────────────────────
// 4. AJAX: 提交广告申请
// ──────────────────────────────────────────────

add_action('wp_ajax_onedown_submit_ad_apply', 'onedown_ajax_submit_ad_apply');
function onedown_ajax_submit_ad_apply()
{
    if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'onedown_ad_apply_action')) {
        wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
    }

    if (! is_user_logged_in()) {
        wp_send_json_error(array('msg' => '请先登录'));
    }

    if (! _pz('ad_self_service_enabled', false)) {
        wp_send_json_error(array('msg' => '广告自助申请功能未开启'));
    }

    // 验证广告投放协议
    $ad_agreement = _pz('ad_agreement_content', '');
    if (! empty(trim(wp_strip_all_tags($ad_agreement)))) {
        if (! isset($_POST['ad_agreed']) || '1' !== $_POST['ad_agreed']) {
            wp_send_json_error(array('msg' => '请阅读并同意广告投放协议'));
        }
    }

    $ad_text    = isset($_POST['ad_text']) ? sanitize_text_field($_POST['ad_text']) : '';
    $ad_url     = isset($_POST['ad_url']) ? esc_url_raw($_POST['ad_url']) : '';
    $ad_contact = isset($_POST['ad_contact']) ? sanitize_text_field($_POST['ad_contact']) : '';

    if (empty($ad_text)) {
        wp_send_json_error(array('msg' => '请输入广告文字'));
    }

    if (empty($ad_url)) {
        wp_send_json_error(array('msg' => '请输入广告链接'));
    }

    $ad_price = floatval(_pz('ad_price', '19.99'));
    $user_id  = get_current_user_id();

    // 限制每人投放上限（含审核中和投放中的）
    $max_active = (int) _pz('ad_max_per_user', 5);
    $existing_active = new WP_Query(array(
        'post_type'      => 'onedown_ad',
        'post_status'    => array('publish', 'pending'),
        'posts_per_page' => $max_active + 1,
        'meta_query'     => array(
            array(
                'key'   => '_ad_user_id',
                'value' => $user_id,
            ),
        ),
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ));

    if ($existing_active->found_posts >= $max_active) {
        wp_send_json_error(array('msg' => '您当前有 ' . $existing_active->found_posts . ' 个广告正在投放或审核中，最多允许 ' . $max_active . ' 个'));
    }

    // 检查是否有未支付的广告草稿（上一个订单未完成）
    $existing_draft = new WP_Query(array(
        'post_type'      => 'onedown_ad',
        'post_status'    => 'draft',
        'posts_per_page' => 1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'   => '_ad_user_id',
                'value' => $user_id,
            ),
            array(
                'key'     => '_ad_order_id',
                'compare' => 'NOT EXISTS',
            ),
        ),
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ));

    if ($existing_draft->have_posts()) {
        wp_send_json_error(array('msg' => '您有上一笔广告尚未支付，请先完成支付后再提交新的广告'));
    }

    // 创建广告草稿（不创建订单，订单在点击确认支付时生成）
    $ad_id = wp_insert_post(array(
        'post_title'   => $ad_text,
        'post_excerpt' => $ad_contact,
        'post_type'    => 'onedown_ad',
        'post_status'  => 'draft',
        'post_author'  => $user_id,
    ));

    if (is_wp_error($ad_id)) {
        wp_send_json_error(array('msg' => '广告创建失败'));
    }

    // 保存广告元数据
    update_post_meta($ad_id, '_ad_target_url', $ad_url);
    update_post_meta($ad_id, '_ad_contact', $ad_contact);
    update_post_meta($ad_id, '_ad_user_id', $user_id);
    update_post_meta($ad_id, '_ad_price', $ad_price);

    wp_send_json_success(array(
        'msg'      => '广告申请已提交，请完成支付',
        'ad_id'    => $ad_id,
        'price'    => $ad_price,
    ));
}

// ──────────────────────────────────────────────
// 5. AJAX: 取消广告订单
// ──────────────────────────────────────────────

add_action('wp_ajax_onedown_cancel_ad', 'onedown_ajax_cancel_ad');
function onedown_ajax_cancel_ad()
{
    if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'onedown_ad_apply_action')) {
        wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
    }

    if (! is_user_logged_in()) {
        wp_send_json_error(array('msg' => '请先登录'));
    }

    $ad_id = intval($_POST['ad_id'] ?? 0);
    if (! $ad_id) {
        wp_send_json_error(array('msg' => '广告ID无效'));
    }

    $ad = get_post($ad_id);
    if (! $ad || $ad->post_type !== 'onedown_ad') {
        wp_send_json_error(array('msg' => '广告不存在'));
    }

    $user_id = get_current_user_id();
    $ad_user_id = (int) get_post_meta($ad_id, '_ad_user_id', true);
    if ($ad_user_id !== $user_id) {
        wp_send_json_error(array('msg' => '无权操作此广告'));
    }

    // 获取关联订单
    $order_id = get_post_meta($ad_id, '_ad_order_id', true);
    if ($order_id) {
        $order = onedown_get_order($order_id);
        if ($order && $order->status === ONEDOWN_ORDER_STATUS_PENDING) {
            // 关闭订单
            onedown_update_order($order_id, array('status' => ONEDOWN_ORDER_STATUS_CLOSED));
        }
        wp_send_json_success(array('msg' => '广告订单已取消'));
    } else {
        // 新流程：无订单，直接删除广告草稿
        wp_delete_post($ad_id, true);
        wp_send_json_success(array('msg' => '广告已取消并删除'));
    }
}

// ──────────────────────────────────────────────
// 5b. AJAX: 删除已取消的广告记录
// ──────────────────────────────────────────────

add_action('wp_ajax_onedown_delete_ad', 'onedown_ajax_delete_ad');
function onedown_ajax_delete_ad()
{
    if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'onedown_ad_apply_action')) {
        wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
    }

    if (! is_user_logged_in()) {
        wp_send_json_error(array('msg' => '请先登录'));
    }

    $ad_id = intval($_POST['ad_id'] ?? 0);
    if (! $ad_id) {
        wp_send_json_error(array('msg' => '广告ID无效'));
    }

    $ad = get_post($ad_id);
    if (! $ad || $ad->post_type !== 'onedown_ad') {
        wp_send_json_error(array('msg' => '广告不存在'));
    }

    $user_id = get_current_user_id();
    $ad_user_id = (int) get_post_meta($ad_id, '_ad_user_id', true);
    if ($ad_user_id !== $user_id) {
        wp_send_json_error(array('msg' => '无权操作此广告'));
    }

    // 检查关联订单是否已关闭
    $order_id = get_post_meta($ad_id, '_ad_order_id', true);
    if ($order_id) {
        $order = onedown_get_order($order_id);
        if ($order && $order->status !== ONEDOWN_ORDER_STATUS_CLOSED) {
            wp_send_json_error(array('msg' => '仅能删除已取消的广告'));
        }
        // 同时删除关联订单
        onedown_delete_order($order_id);
    }

    // 永久删除广告
    wp_delete_post($ad_id, true);

    wp_send_json_success(array('msg' => '广告记录已删除'));
}

// ──────────────────────────────────────────────
// 6. AJAX: 可提现佣金转余额
// ──────────────────────────────────────────────

add_action('wp_ajax_onedown_transfer_to_balance', 'onedown_ajax_transfer_to_balance');
function onedown_ajax_transfer_to_balance()
{
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        wp_send_json_error(array('msg' => '支付功能已禁用'));
    }
    if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'onedown_ad_apply_action')) {
        wp_send_json_error(array('msg' => '安全验证失败'));
    }

    if (! is_user_logged_in()) {
        wp_send_json_error(array('msg' => '请先登录'));
    }

    $user_id = get_current_user_id();

    // 获取可提现金额
    if (! function_exists('onedown_referral_get_withdrawable')) {
        wp_send_json_error(array('msg' => '推广系统未启用'));
    }

    $withdrawable = onedown_referral_get_withdrawable($user_id);
    if ($withdrawable <= 0) {
        wp_send_json_error(array('msg' => '无可提现金额'));
    }

    // 获取所有可提现佣金记录
    $commissions = onedown_referral_get_commissions($user_id);
    $transfer_total = 0;

    foreach ($commissions as $k => $c) {
        if ($c['status'] === 'withdrawable') {
            $transfer_total += $c['commission'];
            $commissions[$k]['status'] = 'withdrawn';
        }
    }

    if ($transfer_total <= 0) {
        wp_send_json_error(array('msg' => '无可提现金额'));
    }

    // 更新佣金状态
    update_user_meta($user_id, 'onedown_referral_commissions', $commissions);

    // 增加余额
    $balance = floatval(get_user_meta($user_id, 'onedown_balance', true));
    $balance += $transfer_total;
    update_user_meta($user_id, 'onedown_balance', $balance);

    wp_send_json_success(array(
        'msg' => '成功转入 ￥' . number_format($transfer_total, 2) . ' 到余额',
        'amount' => $transfer_total,
    ));
}

// ──────────────────────────────────────────────
// 6. 广告前台样式
// ──────────────────────────────────────────────

add_action('wp_head', 'onedown_ad_frontend_styles');
function onedown_ad_frontend_styles()
{
    if (! is_active_widget(false, false, 'od-text-ad', true)) {
        return;
    }
?>
    <style>
        .text-ad-list {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .text-ad-item {
            display: block;
            min-width: 0;
            padding: 6px;
            border-radius: 3px;
            color: #596170;
            background: rgba(45, 55, 76, .04);
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            transition: color .25s ease, background .25s ease, border-color .25s ease, box-shadow .25s ease;
        }

        .text-ad-item:hover {
            color: var(--od-primary);
            background: rgba(var(--od-primary-rgb), .06);
            border-color: rgba(var(--od-primary-rgb), .15);
        }

        .text-ad-list.is-gradient .text-ad-item:hover {
            color: #fff;
            background: var(--text-ad-hover-bg, var(--od-gradient));
            box-shadow: 0 8px 18px rgba(var(--od-primary-rgb), .18);
        }

        .text-ad-title {
            display: block;
            font-size: inherit;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .text-ad-badge {
            display: inline-block;
            margin-right: 4px;
            padding: 1px 4px;
            border-radius: 3px;
            color: var(--od-primary);
            background: rgba(var(--od-primary-rgb), .1);
            font-size: 10px;
            font-weight: 800;
            line-height: 1.2;
            vertical-align: 1px;
            transition: color .25s ease, background .25s ease;
        }

        .text-ad-list.is-gradient .text-ad-item:hover .text-ad-badge {
            color: var(--od-primary);
            background: rgba(255, 255, 255, .86);
        }

        .text-ad-desc {
            display: block;
            font-size: 11px;
            opacity: .7;
            margin-top: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .text-ad-apply a:hover {
            text-decoration: underline !important;
        }

        @media (max-width: 767px) {
            .text-ad-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
<?php
}
