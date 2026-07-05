<?php

/**
 * Onedown 订单系统
 *
 * 订单数据库管理、订单查询、管理员订单管理页面
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

// ──────────────────────────────────────────────
// 订单类型常量
// ──────────────────────────────────────────────
define('ONEDOWN_ORDER_TYPE_POST_READ', 'post_read');
define('ONEDOWN_ORDER_TYPE_POST_DOWNLOAD', 'post_download');
define('ONEDOWN_ORDER_TYPE_VIP', 'vip');
define('ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE', 'balance_recharge');
define('ONEDOWN_ORDER_TYPE_AD', 'ad');

/**
 * 订单状态
 */
define('ONEDOWN_ORDER_STATUS_PENDING', 'pending');
define('ONEDOWN_ORDER_STATUS_PAID', 'paid');
define('ONEDOWN_ORDER_STATUS_CLOSED', 'closed');
define('ONEDOWN_ORDER_STATUS_REFUNDED', 'refunded');

// ──────────────────────────────────────────────
// 1. 数据库安装
// ──────────────────────────────────────────────

/**
 * 创建订单数据库表
 */
function onedown_order_create_db()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'onedown_orders';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `order_id` varchar(50) NOT NULL COMMENT '订单号',
        `user_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID，0=游客',
        `post_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '文章/商品ID',
        `order_type` varchar(30) NOT NULL DEFAULT '' COMMENT '订单类型',
        `order_title` varchar(200) NOT NULL DEFAULT '' COMMENT '订单标题',
        `order_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
        `pay_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
        `pay_type` varchar(30) NOT NULL DEFAULT '' COMMENT '支付方式',
        `pay_trade_no` varchar(100) NOT NULL DEFAULT '' COMMENT '支付平台交易号',
        `pay_detail` longtext COMMENT '支付详情(JSON)',
        `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '订单状态',
        `referrer_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '推广人ID',
        `rebate_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '推广佣金',
        `guest_token` varchar(64) NOT NULL DEFAULT '' COMMENT '访客标识',
        `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `pay_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY (`id`),
        UNIQUE KEY `order_id` (`order_id`),
        KEY `user_id` (`user_id`),
        KEY `post_id` (`post_id`),
        KEY `status` (`status`),
        KEY `guest_token` (`guest_token`),
        KEY `create_time` (`create_time`)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    update_option('onedown_order_db_version', '1.0.0');
}
register_activation_hook(__FILE__, 'onedown_order_create_db');

/**
 * 主题激活时创建表
 */
add_action('after_switch_theme', 'onedown_order_create_db');

/**
 * 获取 orders table name
 */
function onedown_order_table()
{
    global $wpdb;
    return $wpdb->prefix . 'onedown_orders';
}

// ──────────────────────────────────────────────
// 2. 订单号生成
// ──────────────────────────────────────────────

/**
 * 生成唯一订单号
 */
function onedown_generate_order_id($prefix = 'OD')
{
    global $wpdb;
    $table = onedown_order_table();
    $max_attempts = 10;
    $order_id = '';

    for ($i = 0; $i < $max_attempts; $i++) {
        $order_id = $prefix . date('YmdHis') . strtoupper(wp_generate_password(6, false));
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE order_id = %s", $order_id));
        if (!$exists) {
            return $order_id;
        }
    }
    return $prefix . date('YmdHis') . strtoupper(wp_generate_password(8, false));
}

// ──────────────────────────────────────────────
// 3. 订单 CRUD
// ──────────────────────────────────────────────

/**
 * 创建订单
 *
 * @param array $args
 * @return int|false 订单ID 或 false
 */
function onedown_create_order($args = array())
{
    global $wpdb;
    $table = onedown_order_table();

    // 自动检测并创建表
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        onedown_order_create_db();
    }

    $defaults = array(
        'order_id'    => onedown_generate_order_id(),
        'user_id'     => 0,
        'post_id'     => 0,
        'order_type'  => '',
        'order_title' => '',
        'order_price' => 0,
        'pay_price'   => 0,
        'pay_type'    => '',
        'pay_trade_no' => '',
        'pay_detail'  => '',
        'status'      => ONEDOWN_ORDER_STATUS_PENDING,
        'referrer_id' => 0,
        'rebate_price' => 0,
        'guest_token' => '',
    );

    $data = wp_parse_args($args, $defaults);

    // 验证必填
    if (empty($data['order_id']) || $data['order_price'] < 0) {
        return false;
    }

    $data['create_time'] = current_time('mysql');

    $insert = array(
        'order_id'    => $data['order_id'],
        'user_id'     => intval($data['user_id']),
        'post_id'     => intval($data['post_id']),
        'order_type'  => sanitize_text_field($data['order_type']),
        'order_title' => sanitize_text_field($data['order_title']),
        'order_price' => floatval($data['order_price']),
        'pay_price'   => floatval($data['pay_price']),
        'pay_type'    => sanitize_text_field($data['pay_type']),
        'pay_trade_no' => sanitize_text_field($data['pay_trade_no']),
        'pay_detail'  => is_string($data['pay_detail']) ? $data['pay_detail'] : wp_json_encode($data['pay_detail']),
        'status'      => in_array($data['status'], array(ONEDOWN_ORDER_STATUS_PENDING, ONEDOWN_ORDER_STATUS_PAID, ONEDOWN_ORDER_STATUS_CLOSED, ONEDOWN_ORDER_STATUS_REFUNDED), true) ? $data['status'] : ONEDOWN_ORDER_STATUS_PENDING,
        'referrer_id' => intval($data['referrer_id']),
        'rebate_price' => floatval($data['rebate_price']),
        'guest_token' => sanitize_text_field($data['guest_token']),
        'create_time' => $data['create_time'],
    );

    $result = $wpdb->insert($table, $insert);

    if ($result === false) {
        return false;
    }

    return $wpdb->insert_id;
}

/**
 * 获取订单表名（带自动建表）
 */
function onedown_get_order_table()
{
    global $wpdb;
    $table = $wpdb->prefix . 'onedown_orders';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        onedown_order_create_db();
    }
    return $table;
}

/**
 * 根据订单号获取订单
 *
 * @param string $order_id
 * @return object|null
 */
function onedown_get_order($order_id)
{
    global $wpdb;
    $table = onedown_get_order_table();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE order_id = %s", $order_id));
}

/**
 * 根据 ID 获取订单
 *
 * @param int $id
 * @return object|null
 */
function onedown_get_order_by_id($id)
{
    global $wpdb;
    $table = onedown_order_table();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
}

/**
 * 更新订单
 *
 * @param string $order_id
 * @param array $data
 * @return bool
 */
function onedown_update_order($order_id, $data)
{
    global $wpdb;
    $table = onedown_order_table();

    $allowed_fields = array('pay_price', 'pay_type', 'pay_trade_no', 'pay_detail', 'status', 'pay_time', 'referrer_id', 'rebate_price');
    $update = array();

    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            if ($field === 'pay_detail' && is_array($data[$field])) {
                $update[$field] = wp_json_encode($data[$field]);
            } else {
                $update[$field] = $data[$field];
            }
        }
    }

    if (empty($update)) {
        return false;
    }

    return $wpdb->update($table, $update, array('order_id' => $order_id)) !== false;
}

/**
 * 删除订单
 *
 * @param string $order_id
 * @return bool
 */
function onedown_delete_order($order_id)
{
    global $wpdb;
    $table = onedown_order_table();
    return $wpdb->delete($table, array('order_id' => $order_id)) !== false;
}

/**
 * 标记订单为已支付
 *
 * @param string $order_id
 * @param array $pay_data 支付数据
 * @return bool
 */
function onedown_mark_order_paid($order_id, $pay_data = array())
{
    $order = onedown_get_order($order_id);
    if (!$order || $order->status === ONEDOWN_ORDER_STATUS_PAID) {
        return false;
    }

    // 使用 transient 锁防止并发回调重复处理同一订单
    $lock_key = 'od_pay_lock_' . $order_id;
    $lock = get_transient($lock_key);
    if ($lock) {
        return false;
    }
    set_transient($lock_key, 1, 30); // 30 秒锁，防止并发

    $update = array(
        'status'    => ONEDOWN_ORDER_STATUS_PAID,
        'pay_time'  => current_time('mysql'),
        'pay_price' => isset($pay_data['pay_price']) ? floatval($pay_data['pay_price']) : $order->order_price,
        'pay_type'  => isset($pay_data['pay_type']) ? $pay_data['pay_type'] : $order->pay_type,
    );


    if (!empty($pay_data['pay_trade_no'])) {
        $update['pay_trade_no'] = $pay_data['pay_trade_no'];
    }
    if (!empty($pay_data['pay_detail'])) {
        $update['pay_detail'] = is_array($pay_data['pay_detail']) ? wp_json_encode($pay_data['pay_detail']) : $pay_data['pay_detail'];
    }

    $updated = onedown_update_order($order_id, $update);

    if ($updated) {
        // 执行支付成功后的操作
        do_action('onedown_payment_success', $order_id, $order);

        // 根据订单类型执行后续处理
        onedown_process_paid_order($order_id);
    }

    return $updated;
}

/**
 * 处理支付成功后的订单逻辑
 */
function onedown_process_paid_order($order_id)
{
    $order = onedown_get_order($order_id);
    if (!$order || $order->status !== ONEDOWN_ORDER_STATUS_PAID) {
        return;
    }

    $order_type = $order->order_type;

    switch ($order_type) {
        case ONEDOWN_ORDER_TYPE_POST_READ:
        case ONEDOWN_ORDER_TYPE_POST_DOWNLOAD:
            onedown_process_post_order($order);
            break;

        case ONEDOWN_ORDER_TYPE_VIP:
            onedown_process_vip_order($order);
            break;

        case ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE:
            onedown_process_balance_recharge_order($order);
            break;

        case ONEDOWN_ORDER_TYPE_AD:
            onedown_process_ad_order($order);
            break;
    }
}

/**
 * 处理文章付费订单
 */
function onedown_process_post_order($order)
{
    $post_id = intval($order->post_id);
    $user_id = intval($order->user_id);

    // 增加销量
    onedown_increase_pay_sales($post_id);

    if ($user_id > 0) {
        // 登录用户 - 记录已购
        $paid_posts = get_user_meta($user_id, 'onedown_paid_posts', true);
        $paid_posts = is_array($paid_posts) ? $paid_posts : array();
        if (!in_array($post_id, $paid_posts)) {
            $paid_posts[] = $post_id;
            update_user_meta($user_id, 'onedown_paid_posts', $paid_posts);
        }
    } else {
        // 访客 - 设置 cookie
        $cookie_key = 'onedown_paid_' . $post_id;
        setcookie($cookie_key, $order->order_id, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }

    // 推广分成
    if (intval($order->referrer_id) > 0 && floatval($order->rebate_price) > 0) {
        onedown_referral_add_commission($order->referrer_id, $order->order_id, $order->rebate_price, $order->order_type);
    }
}

/**
 * 处理 VIP 订单
 */
function onedown_process_vip_order($order)
{
    $user_id = intval($order->user_id);
    if ($user_id <= 0) {
        return;
    }

    // 从 pay_detail 中读取 plan_id（订单创建时已存入）
    $pay_detail = !empty($order->pay_detail) ? json_decode($order->pay_detail, true) : array();
    $plan_id = !empty($pay_detail['plan_id']) ? $pay_detail['plan_id'] : '';

    // 兼容旧订单：尝试从 post_meta 读取（post_id 为0时无效，保留作为 fallback）
    if (empty($plan_id) && intval($order->post_id) > 0) {
        $plan_id = get_post_meta($order->post_id, '_vip_plan_id', true);
    }

    if (empty($plan_id)) {
        if (function_exists('error_log')) {
            error_log('[onedown-pay] process_vip_order failed: no plan_id for order=' . $order->order_id);
        }
        return;
    }

    // 使用原有的 VIP 处理
    if (function_exists('onedown_update_user_vip')) {
        onedown_update_user_vip($user_id, $plan_id, $order->order_id);
    }

    // 推广分成
    if (intval($order->referrer_id) > 0 && floatval($order->rebate_price) > 0) {
        onedown_referral_add_commission($order->referrer_id, $order->order_id, $order->rebate_price, $order->order_type);
    }
}

/**
 * 处理余额充值订单
 */
function onedown_process_balance_recharge_order($order)
{
    $user_id = intval($order->user_id);
    if ($user_id <= 0) {
        return;
    }

    $amount = floatval($order->pay_price);
    $balance = floatval(get_user_meta($user_id, 'onedown_balance', true));
    $balance += $amount;
    update_user_meta($user_id, 'onedown_balance', $balance);
}

/**
 * 处理广告订单
 */
function onedown_process_ad_order($order)
{
    if (function_exists('onedown_ad_payment_success')) {
        onedown_ad_payment_success($order->order_id);
    }
}

/**
 * 获取用户订单列表
 *
 * @param int   $user_id
 * @param array $args
 * @return array
 */
function onedown_get_user_orders_db($user_id, $args = array())
{
    global $wpdb;
    $table = onedown_order_table();

    $defaults = array(
        'status' => '',
        'limit'  => 20,
        'offset' => 0,
        'order'  => 'DESC',
    );
    $args = wp_parse_args($args, $defaults);

    $where = $wpdb->prepare('WHERE user_id = %d', $user_id);
    if (!empty($args['status'])) {
        $where .= $wpdb->prepare(' AND status = %s', $args['status']);
    }

    $limit = intval($args['limit']);
    $offset = intval($args['offset']);
    $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

    $sql = $wpdb->prepare(
        "SELECT * FROM {$table} {$where} ORDER BY create_time {$order} LIMIT %d, %d",
        $offset,
        $limit
    );
    return $wpdb->get_results($sql);
}

/**
 * 获取用户订单总数
 */
function onedown_get_user_orders_count_db($user_id, $status = '')
{
    global $wpdb;
    $table = onedown_order_table();

    $where = $wpdb->prepare('WHERE user_id = %d', $user_id);
    if (!empty($status)) {
        $where .= $wpdb->prepare(' AND status = %s', $status);
    }

    return intval($wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}"));
}

/**
 * 获取所有订单（管理员）
 */
function onedown_get_all_orders($args = array())
{
    global $wpdb;
    $table = onedown_order_table();

    $defaults = array(
        'status' => '',
        'type'   => '',
        'user_id' => 0,
        'search' => '',
        'limit'  => 20,
        'offset' => 0,
        'order'  => 'DESC',
    );
    $args = wp_parse_args($args, $defaults);

    $where = array('1=1');

    if (!empty($args['status'])) {
        $where[] = $wpdb->prepare('status = %s', $args['status']);
    }
    if (!empty($args['type'])) {
        $where[] = $wpdb->prepare('order_type = %s', $args['type']);
    }
    if (!empty($args['user_id'])) {
        $where[] = $wpdb->prepare('user_id = %d', $args['user_id']);
    }
    if (!empty($args['search'])) {
        $search = '%' . $wpdb->esc_like($args['search']) . '%';
        $where[] = $wpdb->prepare('(order_id LIKE %s OR pay_trade_no LIKE %s)', $search, $search);
    }

    $where_sql = implode(' AND ', $where);
    $limit = intval($args['limit']);
    $offset = intval($args['offset']);
    $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

    $sql = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY create_time {$order} LIMIT %d, %d",
        $offset,
        $limit
    );
    return $wpdb->get_results($sql);
}

/**
 * 统计订单总数
 */
function onedown_get_orders_count($args = array())
{
    global $wpdb;
    $table = onedown_order_table();

    $where = array('1=1');
    if (!empty($args['status'])) {
        $where[] = $wpdb->prepare('status = %s', $args['status']);
    }
    if (!empty($args['type'])) {
        $where[] = $wpdb->prepare('order_type = %s', $args['type']);
    }

    $where_sql = implode(' AND ', $where);
    return intval($wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE {$where_sql}"));
}

/**
 * 统计收入
 */
function onedown_get_total_income($args = array())
{
    global $wpdb;
    $table = onedown_order_table();

    $where = array('status = %s');
    $params = array(ONEDOWN_ORDER_STATUS_PAID);

    if (!empty($args['start_date'])) {
        $where[] = 'pay_time >= %s';
        $params[] = $args['start_date'];
    }
    if (!empty($args['end_date'])) {
        $where[] = 'pay_time <= %s';
        $params[] = $args['end_date'];
    }

    $where_sql = implode(' AND ', $where);
    $sql = $wpdb->prepare("SELECT COALESCE(SUM(pay_price), 0) FROM {$table} WHERE {$where_sql}", $params);
    return floatval($wpdb->get_var($sql));
}

// ──────────────────────────────────────────────
// 4. 订单类型名称
// ──────────────────────────────────────────────

/**
 * 获取订单类型名称
 */
function onedown_order_type_name($type)
{
    $names = array(
        ONEDOWN_ORDER_TYPE_POST_READ       => '付费阅读',
        ONEDOWN_ORDER_TYPE_POST_DOWNLOAD   => '付费下载',
        ONEDOWN_ORDER_TYPE_VIP             => '会员购买',
        ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE => '余额充值',
    );
    return isset($names[$type]) ? $names[$type] : $type;
}

/**
 * 获取订单状态名称
 */
function onedown_order_status_name($status)
{
    $names = array(
        ONEDOWN_ORDER_STATUS_PENDING  => '待付款',
        ONEDOWN_ORDER_STATUS_PAID     => '已完成',
        ONEDOWN_ORDER_STATUS_CLOSED   => '已关闭',
        ONEDOWN_ORDER_STATUS_REFUNDED => '已退款',
    );
    return isset($names[$status]) ? $names[$status] : $status;
}

/**
 * 获取支付方式名称
 */
function onedown_pay_type_name($type)
{
    $names = array(
        'alipay'  => '支付宝',
        'wechat'  => '微信支付',
        'epay'    => '易支付',
        'balance' => '余额支付',
        'offline' => '线下支付',
    );
    return isset($names[$type]) ? $names[$type] : $type;
}

// ──────────────────────────────────────────────
// 5. 管理员订单管理页面
// ──────────────────────────────────────────────

/**
 * 添加管理菜单
 */
add_action('admin_menu', 'onedown_order_admin_menu');
add_action('admin_init', 'onedown_order_admin_actions');
function onedown_order_admin_actions()
{
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        return;
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    $base_url = admin_url('admin.php?page=onedown-orders');

    // 处理批量删除
    if (isset($_POST['batch_delete']) && isset($_POST['order_ids']) && is_array($_POST['order_ids'])) {
        if (wp_verify_nonce($_POST['_wpnonce'] ?? '', 'onedown_batch_action')) {
            $order_ids = array_map('sanitize_text_field', wp_unslash($_POST['order_ids']));
            $count = 0;
            foreach ($order_ids as $oid) {
                if (onedown_delete_order($oid)) {
                    $count++;
                }
            }
            set_transient('onedown_order_msg', array('type' => 'success', 'text' => '成功删除 ' . $count . ' 条订单'), 30);
            wp_redirect($base_url);
            exit;
        }
    }

    // 处理结算佣金
    if (isset($_POST['settle_commissions'])) {
        if (wp_verify_nonce($_POST['_wpnonce'] ?? '', 'onedown_batch_action')) {
            $settled_total = 0;
            $args = array(
                'meta_key'   => 'onedown_referral_commissions',
                'meta_compare' => 'EXISTS',
            );
            $users = get_users($args);
            $cutoff = strtotime('-7 days');

            foreach ($users as $user) {
                $commissions = get_user_meta($user->ID, 'onedown_referral_commissions', true);
                if (!is_array($commissions)) {
                    continue;
                }
                $changed = false;
                foreach ($commissions as $k => $c) {
                    if ($c['status'] === 'pending') {
                        $created = strtotime($c['created_at']);
                        if ($created && $created < $cutoff) {
                            $commissions[$k]['status'] = 'withdrawable';
                            $changed = true;
                            $settled_total++;
                        }
                    }
                }
                if ($changed) {
                    update_user_meta($user->ID, 'onedown_referral_commissions', $commissions);
                }
            }

            set_transient('onedown_order_msg', array('type' => 'success', 'text' => '佣金结算完成，共结算 ' . $settled_total . ' 条佣金'), 30);
            wp_redirect($base_url);
            exit;
        }
    }

    // 处理单条订单操作
    if (isset($_GET['action']) && isset($_GET['order_id']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'onedown_order_action')) {
        $order_id = sanitize_text_field($_GET['order_id']);
        $action = sanitize_text_field($_GET['action']);

        if ($action === 'paid') {
            $order = onedown_get_order($order_id);
            if ($order && $order->status === ONEDOWN_ORDER_STATUS_PENDING) {
                onedown_mark_order_paid($order_id, array(
                    'pay_type' => $order->pay_type ?: 'manual',
                    'pay_trade_no' => 'manual_' . current_time('mysql'),
                ));
                set_transient('onedown_order_msg', array('type' => 'success', 'text' => '订单已标记为已支付'), 30);
            }
        } elseif ($action === 'close') {
            onedown_update_order($order_id, array('status' => ONEDOWN_ORDER_STATUS_CLOSED));
            set_transient('onedown_order_msg', array('type' => 'info', 'text' => '订单已关闭'), 30);
        } elseif ($action === 'delete') {
            onedown_delete_order($order_id);
            set_transient('onedown_order_msg', array('type' => 'success', 'text' => '订单已删除'), 30);
        }
        wp_redirect($base_url);
        exit;
    }
}

function onedown_order_admin_menu()
{
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        return;
    }

    // 父级菜单：OD主题数据（置于主题设置之后）
    add_menu_page(
        __('OD主题数据', 'onedown'),
        __('OD主题数据', 'onedown'),
        'manage_options',
        'onedown-orders',
        'onedown_order_admin_page',
        'dashicons-database',
        82
    );

    // 子菜单：订单管理
    add_submenu_page(
        'onedown-orders',
        __('订单管理', 'onedown'),
        __('订单管理', 'onedown'),
        'manage_options',
        'onedown-orders',
        'onedown_order_admin_page'
    );

    // 子菜单：提现管理
    add_submenu_page(
        'onedown-orders',
        __('提现管理', 'onedown'),
        __('提现管理', 'onedown'),
        'manage_options',
        'onedown-withdrawals',
        'onedown_withdrawal_admin_page'
    );
}

/**
 * 订单管理页面
 */
function onedown_order_admin_page()
{
    // 从 transient 读取闪存消息（仅一次，读取后自动删除）
    $flash = get_transient('onedown_order_msg');
    delete_transient('onedown_order_msg');
    if (!empty($flash) && isset($flash['type'], $flash['text'])) {
        $notice_class = $flash['type'] === 'info' ? 'notice-info' : 'notice-success';
        echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible"><p>' . esc_html($flash['text']) . '</p></div>';
    }

    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    $args = array(
        'limit'  => $per_page,
        'offset' => ($current_page - 1) * $per_page,
    );
    if ($status_filter) {
        $args['status'] = $status_filter;
    }
    if ($type_filter) {
        $args['type'] = $type_filter;
    }
    if ($search) {
        $args['search'] = $search;
    }

    $orders = onedown_get_all_orders($args);
    $total = onedown_get_orders_count(array(
        'status' => $status_filter,
        'type'   => $type_filter,
    ));
    $total_pages = ceil($total / $per_page);

    $statuses = array('' => '全部', ONEDOWN_ORDER_STATUS_PENDING => '待付款', ONEDOWN_ORDER_STATUS_PAID => '已完成', ONEDOWN_ORDER_STATUS_CLOSED => '已关闭', ONEDOWN_ORDER_STATUS_REFUNDED => '已退款');
    $types = array('' => '全部类型', ONEDOWN_ORDER_TYPE_POST_READ => '付费阅读', ONEDOWN_ORDER_TYPE_POST_DOWNLOAD => '付费下载', ONEDOWN_ORDER_TYPE_VIP => '会员购买', ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE => '余额充值');
?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('订单管理', 'onedown'); ?></h1>
        <hr class="wp-header-end">

        <form method="get" style="margin-bottom:12px;">
            <input type="hidden" name="page" value="onedown-orders">
            <select name="status">
                <?php foreach ($statuses as $val => $label) : ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($status_filter, $val); ?>>
                        <?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="type">
                <?php foreach ($types as $val => $label) : ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($type_filter, $val); ?>>
                        <?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="订单号/交易号">
            <button type="submit" class="button">筛选</button>
        </form>

        <form method="post" id="onedown-orders-form">
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('onedown_batch_action')); ?>">
            <div class="tablenav top" style="margin-bottom:12px;">
                <div class="alignleft actions">
                    <button type="submit" name="batch_delete" class="button" style="color:#a00;"
                        onclick="return confirm('确认删除选中的订单？此操作不可撤销！')">批量删除</button>
                    <button type="submit" name="settle_commissions" class="button button-primary"
                        onclick="return confirm('确认结算所有已过结算期（7天）的待结算佣金？')">结算佣金</button>
                </div>
                <br class="clear">
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" id="cb-select-all"></th>
                        <th width="60">ID</th>
                        <th>订单号</th>
                        <th>类型</th>
                        <th>标题</th>
                        <th>金额</th>
                        <th>实付</th>
                        <th>支付方式</th>
                        <th>状态</th>
                        <th>时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)) : foreach ($orders as $order) : ?>
                            <tr>
                                <td><input type="checkbox" name="order_ids[]" value="<?php echo esc_attr($order->order_id); ?>">
                                </td>
                                <td><?php echo intval($order->id); ?></td>
                                <td><code><?php echo esc_html($order->order_id); ?></code></td>
                                <td><?php echo esc_html(onedown_order_type_name($order->order_type)); ?></td>
                                <td><?php echo esc_html(mb_substr($order->order_title, 0, 30)); ?></td>
                                <td>￥<?php echo number_format($order->order_price, 2); ?></td>
                                <td>￥<?php echo number_format($order->pay_price, 2); ?></td>
                                <td><?php echo esc_html(onedown_pay_type_name($order->pay_type)); ?></td>
                                <td>
                                    <span
                                        style="color:<?php echo $order->status === ONEDOWN_ORDER_STATUS_PAID ? 'green' : ($order->status === ONEDOWN_ORDER_STATUS_PENDING ? 'orange' : '#999'); ?>">
                                        <?php echo esc_html(onedown_order_status_name($order->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($order->create_time); ?></td>
                                <td>
                                    <?php if ($order->status === ONEDOWN_ORDER_STATUS_PENDING) : ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=onedown-orders&action=paid&order_id=' . $order->order_id), 'onedown_order_action')); ?>"
                                            class="button button-small" style="color:green;"
                                            onclick="return confirm('确认标记为已支付？')">标记已付</a>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=onedown-orders&action=close&order_id=' . $order->order_id), 'onedown_order_action')); ?>"
                                            class="button button-small" onclick="return confirm('确认关闭订单？')">关闭</a>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=onedown-orders&action=delete&order_id=' . $order->order_id), 'onedown_order_action')); ?>"
                                        class="button button-small" style="color:#a00;"
                                        onclick="return confirm('确认删除订单「<?php echo esc_js($order->order_id); ?>」？此操作不可撤销！')">删除</a>
                                </td>
                            </tr>
                        <?php endforeach;
                    else : ?>
                        <tr>
                            <td colspan="11" style="text-align:center;">暂无订单</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="tablenav bottom" style="margin-top:12px;">
                <div class="alignleft actions">
                    <button type="submit" name="batch_delete" class="button" style="color:#a00;"
                        onclick="return confirm('确认删除选中的订单？此操作不可撤销！')">批量删除</button>
                    <button type="submit" name="settle_commissions" class="button button-primary"
                        onclick="return confirm('确认结算所有已过结算期（7天）的待结算佣金？')">结算佣金</button>
                </div>
                <?php if ($total_pages > 1) : ?>
                    <div class="tablenav-pages" style="float:right;">
                        <?php
                        echo paginate_links(array(
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'   => $total_pages,
                            'current' => $current_page,
                        ));
                        ?>
                    </div>
                <?php endif; ?>
                <br class="clear">
            </div>
        </form>
    </div>
    <script>
        document.getElementById('cb-select-all')?.addEventListener('change', function() {
            var cbs = document.querySelectorAll('#onedown-orders-form input[name="order_ids[]"]');
            cbs.forEach(function(cb) {
                cb.checked = this.checked;
            }, this);
        });
    </script>
<?php
}

// ──────────────────────────────────────────────
// 6. AJAX 查询订单支付状态
// ──────────────────────────────────────────────

/**
 * 检查订单支付状态（含自动查询支付平台）
 *
 * 当订单为 pending 时，自动调用支付宝/微信官方查询 API
 * 确认用户是否已真实付款，无需用户手动点击「验证订单」
 */
add_action('wp_ajax_onedown_check_pay', 'onedown_ajax_check_pay');
add_action('wp_ajax_nopriv_onedown_check_pay', 'onedown_ajax_check_pay');
function onedown_ajax_check_pay()
{
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        wp_send_json_error(array('msg' => '支付功能已禁用'));
    }
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_pay_order_action')) {
        wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
    }

    $order_id = sanitize_text_field($_POST['order_id'] ?? '');
    if (empty($order_id)) {
        wp_send_json_error(array('msg' => '订单号无效'));
    }

    $order = onedown_get_order($order_id);
    if (!$order) {
        wp_send_json_error(array('msg' => '订单不存在'));
    }

    // ── 自动查询支付平台真实状态 ──
    // 如果订单仍为 pending，主动调用支付宝/微信/易支付 API 查询真实支付状态
    // 避免因异步通知延迟/失败导致用户需手动点击「验证订单」
    if ($order->status === ONEDOWN_ORDER_STATUS_PENDING) {
        $pay_type = $order->pay_type;

        // 使用 transient 做速率限制：同一订单至少间隔 10 秒才查一次支付平台 API
        $cache_key = 'od_pay_query_' . md5($order_id);
        $last_query = get_transient($cache_key);
        $now = time();

        if (!$last_query || ($now - $last_query) > 10) {
            set_transient($cache_key, $now, 120);

            // 判断实际使用的支付网关
            // pay_type 可能为 'wechat'/'alipay'，但实际走的可能是易支付通道
            $use_epay_gateway = false;
            if (empty($pay_type)) {
                $use_epay_gateway = true;
                $pay_type = 'epay';
            } elseif ($pay_type === 'wechat' && _pz('wechat_pay_method', 'close') === 'epay') {
                $use_epay_gateway = true;
            } elseif ($pay_type === 'alipay' && _pz('alipay_pay_method', 'close') === 'epay') {
                $use_epay_gateway = true;
            } elseif (in_array($pay_type, array('epay', 'wxpay'), true)) {
                $use_epay_gateway = true;
            }

            if ($use_epay_gateway && function_exists('onedown_epay_query_order')) {
                $query_result = onedown_epay_query_order($order_id);

                if ($query_result['success'] && !empty($query_result['paid'])) {
                    onedown_mark_order_paid($order_id, array(
                        'pay_type'     => $pay_type,
                        'pay_trade_no' => $query_result['trade_no'],
                        'pay_price'    => floatval($query_result['total_amount']),
                        'pay_detail'   => array(
                            'channel'     => 'epay',
                            'trade_no'    => $query_result['trade_no'],
                            'verified_by' => 'auto_poll',
                        ),
                    ));
                    $order = onedown_get_order($order_id);
                }
            } elseif ($pay_type === 'alipay' && function_exists('onedown_alipay_query_order')) {
                $query_result = onedown_alipay_query_order($order_id);

                if ($query_result['success'] && !empty($query_result['paid'])) {
                    onedown_mark_order_paid($order_id, array(
                        'pay_type'     => 'alipay',
                        'pay_trade_no' => $query_result['trade_no'],
                        'pay_price'    => floatval($query_result['total_amount']),
                        'pay_detail'   => array(
                            'channel'      => 'alipay_official',
                            'trade_no'     => $query_result['trade_no'],
                            'buyer_id'     => $query_result['buyer_user_id'],
                            'verified_by'  => 'auto_poll',
                        ),
                    ));
                    $order = onedown_get_order($order_id);
                }
            } elseif ($pay_type === 'wechat' && function_exists('onedown_wechat_query_order')) {
                $query_result = onedown_wechat_query_order($order_id);

                if ($query_result['success'] && !empty($query_result['paid'])) {
                    onedown_mark_order_paid($order_id, array(
                        'pay_type'     => 'wechat',
                        'pay_trade_no' => $query_result['transaction_id'],
                        'pay_price'    => $query_result['total_fee'] / 100,
                        'pay_detail'   => array(
                            'channel'      => 'wechat_official',
                            'trade_no'     => $query_result['transaction_id'],
                            'openid'       => $query_result['openid'],
                            'time_end'     => $query_result['pay_time'],
                            'verified_by'  => 'auto_poll',
                        ),
                    ));
                    $order = onedown_get_order($order_id);
                }
            }
        }
    }

    wp_send_json_success(array(
        'status'     => $order->status,
        'status_name' => onedown_order_status_name($order->status),
        'pay_time'   => $order->pay_time,
        'pay_price'  => $order->pay_price,
    ));
}

/**
 * AJAX: 手动验证支付状态（用于扫码支付后手动验证）
 * 当异步回调未及时到达时，用户可主动触发验证
 */
add_action('wp_ajax_onedown_verify_payment', 'onedown_ajax_verify_payment');
add_action('wp_ajax_nopriv_onedown_verify_payment', 'onedown_ajax_verify_payment');
function onedown_ajax_verify_payment()
{
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        wp_send_json_error(array('msg' => '支付功能已禁用'));
    }

    $order_id = sanitize_text_field($_POST['order_id'] ?? '');
    if (empty($order_id)) {
        wp_send_json_error(array('msg' => '订单号无效'));
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_pay_order_action')) {
        wp_send_json_error(array('msg' => '安全验证失败，请刷新页面重试'));
    }

    $order = onedown_get_order($order_id);
    if (!$order) {
        wp_send_json_error(array('msg' => '订单不存在'));
    }

    // 如果已经支付，直接返回成功
    if ($order->status === ONEDOWN_ORDER_STATUS_PAID) {
        wp_send_json_success(array(
            'paid'  => true,
            'msg'   => '订单已支付成功',
            'order_id' => $order_id,
        ));
    }

    $pay_type = $order->pay_type;

    // 判断实际使用的支付网关
    // pay_type 可能为 'wechat'/'alipay'，但实际走的可能是易支付通道
    $use_epay_gateway = false;
    if (empty($pay_type)) {
        $use_epay_gateway = true;
        $pay_type = 'epay';
    } elseif ($pay_type === 'wechat' && _pz('wechat_pay_method', 'close') === 'epay') {
        $use_epay_gateway = true;
    } elseif ($pay_type === 'alipay' && _pz('alipay_pay_method', 'close') === 'epay') {
        $use_epay_gateway = true;
    } elseif (in_array($pay_type, array('epay', 'wxpay'), true)) {
        $use_epay_gateway = true;
    }

    // ── 易支付网关查询 ──
    if ($use_epay_gateway) {
        if (!function_exists('onedown_epay_query_order')) {
            wp_send_json_error(array('msg' => '验证功能不可用'));
        }

        $query_result = onedown_epay_query_order($order_id);

        if (!$query_result['success']) {
            wp_send_json_error(array('msg' => $query_result['msg']));
        }

        if (!$query_result['paid']) {
            wp_send_json_error(array('msg' => '支付未完成，请确认已支付成功'));
        }

        // 易支付确认已支付，标记订单为已支付
        $pay_data = array(
            'pay_type'     => $pay_type,
            'pay_trade_no' => $query_result['trade_no'],
            'pay_price'    => floatval($query_result['total_amount']),
            'pay_detail'   => array(
                'channel'      => 'epay',
                'trade_no'     => $query_result['trade_no'],
                'verified_by'  => 'manual_query',
            ),
        );
    } elseif ($pay_type === 'alipay') {
        // ── 支付宝官方支付查询 ──
        if (!function_exists('onedown_alipay_query_order')) {
            wp_send_json_error(array('msg' => '验证功能不可用'));
        }

        $query_result = onedown_alipay_query_order($order_id);

        if (!$query_result['success']) {
            wp_send_json_error(array('msg' => $query_result['msg']));
        }

        if (!$query_result['paid']) {
            wp_send_json_error(array('msg' => '支付宝支付未完成，请确认已支付成功'));
        }

        $pay_data = array(
            'pay_type'     => 'alipay',
            'pay_trade_no' => $query_result['trade_no'],
            'pay_price'    => floatval($query_result['total_amount']),
            'pay_detail'   => array(
                'channel'      => 'alipay_official',
                'trade_no'     => $query_result['trade_no'],
                'buyer_id'     => $query_result['buyer_user_id'],
                'verified_by'  => 'manual_query',
            ),
        );
    } elseif ($pay_type === 'wechat') {
        // ── 微信官方支付查询 ──
        if (!function_exists('onedown_wechat_query_order')) {
            wp_send_json_error(array('msg' => '验证功能不可用'));
        }

        $query_result = onedown_wechat_query_order($order_id);

        if (!$query_result['success']) {
            wp_send_json_error(array('msg' => $query_result['msg']));
        }

        if (!$query_result['paid']) {
            wp_send_json_error(array('msg' => '微信支付未完成，请确认已支付成功'));
        }

        $pay_data = array(
            'pay_type'     => 'wechat',
            'pay_trade_no' => $query_result['transaction_id'],
            'pay_price'    => $query_result['total_fee'] / 100,
            'pay_detail'   => array(
                'channel'      => 'wechat_official',
                'trade_no'     => $query_result['transaction_id'],
                'openid'       => $query_result['openid'],
                'time_end'     => $query_result['pay_time'],
                'verified_by'  => 'manual_query',
            ),
        );
    } else {
        wp_send_json_error(array('msg' => '该支付方式不支持手动验证，请确认已支付后刷新页面'));
    }

    // ── 通用标记订单已支付 ──
    $updated = onedown_mark_order_paid($order_id, $pay_data);

    if ($updated) {
        if (intval($order->user_id) <= 0) {
            $cookie_key = 'onedown_paid_' . $order->post_id;
            setcookie($cookie_key, $order_id, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }

        wp_send_json_success(array(
            'paid'  => true,
            'msg'   => '支付验证成功！',
            'order_id' => $order_id,
        ));
    }

    wp_send_json_error(array('msg' => '订单状态更新失败，请联系管理员'));
}

// ──────────────────────────────────────────────
// 7. 提现管理（管理员）
// ──────────────────────────────────────────────

/**
 * 提现管理后台操作处理
 */
add_action('admin_init', 'onedown_withdrawal_admin_actions');
function onedown_withdrawal_admin_actions()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $base_url = admin_url('admin.php?page=onedown-withdrawals');

    // 处理提现审核（GET方式：通过）
    if (isset($_GET['withdrawal_action']) && isset($_GET['withdrawal_id']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'onedown_withdrawal_action')) {
        $withdrawal_id = sanitize_text_field($_GET['withdrawal_id']);
        $action = sanitize_text_field($_GET['withdrawal_action']);

        if ($action === 'approve') {
            $result = onedown_referral_process_withdraw($withdrawal_id, 'approved');
            if ($result) {
                onedown_clear_withdrawals_cache();
                set_transient('onedown_withdrawal_msg', array('type' => 'success', 'text' => '提现申请已通过，佣金已扣除'), 30);
            } else {
                set_transient('onedown_withdrawal_msg', array('type' => 'error', 'text' => '处理失败，未找到该提现记录或已处理'), 30);
            }
            wp_redirect($base_url);
            exit;
        }
    }

    // 处理提现拒绝（POST方式：含拒绝原因）
    if (isset($_POST['withdrawal_reject']) && isset($_POST['withdrawal_id']) && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'onedown_withdrawal_reject')) {
        $withdrawal_id = sanitize_text_field($_POST['withdrawal_id']);
        $reject_reason = sanitize_textarea_field($_POST['reject_reason'] ?? '');

        if (empty($reject_reason)) {
            set_transient('onedown_withdrawal_msg', array('type' => 'error', 'text' => '请填写拒绝原因'), 30);
            wp_redirect($base_url);
            exit;
        }

        $result = onedown_referral_process_withdraw($withdrawal_id, 'rejected', $reject_reason);
        if ($result) {
            onedown_clear_withdrawals_cache();
            set_transient('onedown_withdrawal_msg', array('type' => 'info', 'text' => '提现申请已拒绝，佣金已退回'), 30);
        } else {
            set_transient('onedown_withdrawal_msg', array('type' => 'error', 'text' => '处理失败，未找到该提现记录或已处理'), 30);
        }

        wp_redirect($base_url);
        exit;
    }
}

/**
 * 获取所有用户的提现申请记录
 *
 * @param array $args 筛选参数
 * @return array
 */
function onedown_get_all_withdrawals($args = array())
{
    $defaults = array(
        'status' => '',   // pending | approved | rejected | '' (全部)
        'search' => '',   // 搜索用户名/账号
        'limit'  => 50,
        'offset' => 0,
    );
    $args = wp_parse_args($args, $defaults);

    // 缓存键基于筛选条件
    $cache_key = 'od_withdrawals_' . md5(serialize($args));
    $cached    = get_transient($cache_key);
    if (false !== $cached) {
        return $cached;
    }

    // 查询所有有提现记录的用户
    $user_args = array(
        'meta_key'     => 'onedown_referral_withdrawals',
        'meta_compare' => 'EXISTS',
        'number'       => 500,
        'fields'       => array('ID', 'display_name', 'user_login'),
    );
    $users = get_users($user_args);

    $all_withdrawals = array();

    foreach ($users as $user) {
        $withdrawals = get_user_meta($user->ID, 'onedown_referral_withdrawals', true);
        if (!is_array($withdrawals) || empty($withdrawals)) {
            continue;
        }

        foreach ($withdrawals as $wid => $w) {
            // 状态筛选
            if (!empty($args['status']) && $w['status'] !== $args['status']) {
                continue;
            }

            // 搜索筛选
            if (!empty($args['search'])) {
                $search = strtolower($args['search']);
                $user_match = strpos(strtolower($user->display_name), $search) !== false
                    || strpos(strtolower($user->user_login), $search) !== false
                    || strpos(strtolower($w['account'] ?? ''), $search) !== false
                    || strpos(strtolower($wid), $search) !== false;
                if (!$user_match) {
                    continue;
                }
            }

            $all_withdrawals[] = array(
                'withdrawal_id' => $wid,
                'user_id'       => $user->ID,
                'user_name'     => $user->display_name ?: $user->user_login,
                'user_login'    => $user->user_login,
                'amount'        => floatval($w['amount']),
                'fee'           => floatval($w['fee'] ?? 0),
                'actual'        => floatval($w['actual'] ?? $w['amount']),
                'account'       => $w['account'] ?? '',
                'note'          => $w['note'] ?? '',
                'status'        => $w['status'],
                'created_at'    => $w['created_at'],
                'processed_at'  => $w['processed_at'] ?? '',
                'reject_reason' => $w['reject_reason'] ?? '',
            );
        }
    }

    // 按时间倒序
    usort($all_withdrawals, function ($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });

    $total = count($all_withdrawals);
    $offset = intval($args['offset']);
    $limit = intval($args['limit']);
    $items = array_slice($all_withdrawals, $offset, $limit);

    $result = array(
        'items' => $items,
        'total' => $total,
    );

    // 缓存 30 秒，避免快速翻页时重复全量查询
    // 提现处理时会调用 onedown_clear_withdrawals_cache() 清除缓存
    set_transient($cache_key, $result, 30);

    return $result;
}

/**
 * 清除提现列表缓存（在提现审核/拒绝后调用）
 */
function onedown_clear_withdrawals_cache()
{
    global $wpdb;
    // 清除所有 od_withdrawals_ 开头的缓存
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_od_withdrawals_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_od_withdrawals_%'");
}

/**
 * 提现管理页面
 */
function onedown_withdrawal_admin_page()
{
    // 闪存消息
    $flash = get_transient('onedown_withdrawal_msg');
    delete_transient('onedown_withdrawal_msg');
    if (!empty($flash) && isset($flash['type'], $flash['text'])) {
        $notice_class = $flash['type'] === 'error' ? 'notice-error' : ($flash['type'] === 'info' ? 'notice-info' : 'notice-success');
        echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible"><p>' . esc_html($flash['text']) . '</p></div>';
    }

    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    $result = onedown_get_all_withdrawals(array(
        'status' => $status_filter,
        'search' => $search,
        'limit'  => $per_page,
        'offset' => ($current_page - 1) * $per_page,
    ));

    $withdrawals = $result['items'];
    $total = $result['total'];
    $total_pages = ceil($total / $per_page);

    $statuses = array(
        ''         => '全部状态',
        'pending'  => '待审核',
        'approved' => '已提现',
        'rejected' => '已拒绝',
    );

    $status_labels = array(
        'pending'  => '<span style="color:#f0ad4e;">待审核</span>',
        'approved' => '<span style="color:green;">已提现</span>',
        'rejected' => '<span style="color:#999;">已拒绝</span>',
    );
?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('提现管理', 'onedown'); ?></h1>
        <hr class="wp-header-end">

        <form method="get" style="margin-bottom:12px;">
            <input type="hidden" name="page" value="onedown-withdrawals">
            <select name="status">
                <?php foreach ($statuses as $val => $label) : ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($status_filter, $val); ?>>
                        <?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="用户名/账号/提现编号">
            <button type="submit" class="button">筛选</button>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th>用户</th>
                    <th>提现金额</th>
                    <th>手续费</th>
                    <th>实际到账</th>
                    <th>提现账号</th>
                    <th>备注</th>
                    <th>状态</th>
                    <th>拒绝原因</th>
                    <th>申请时间</th>
                    <th>处理时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($withdrawals)) : foreach ($withdrawals as $w) : ?>
                        <tr>
                            <td><code><?php echo esc_html(mb_substr($w['withdrawal_id'], -10)); ?></code></td>
                            <td>
                                <strong><?php echo esc_html($w['user_name']); ?></strong>
                                <br><small style="color:#999;"><?php echo esc_html($w['user_login']); ?></small>
                            </td>
                            <td><strong>￥<?php echo number_format($w['amount'], 2); ?></strong></td>
                            <td>￥<?php echo number_format($w['fee'], 2); ?></td>
                            <td>￥<?php echo number_format($w['actual'], 2); ?></td>
                            <td><?php echo esc_html($w['account']); ?></td>
                            <td><small><?php echo esc_html(mb_substr($w['note'], 0, 20)); ?></small></td>
                            <td><?php echo $status_labels[$w['status']] ?? esc_html($w['status']); ?></td>
                            <td>
                                <?php if ($w['status'] === 'rejected' && !empty($w['reject_reason'])) : ?>
                                    <span style="color:#a00;"
                                        title="<?php echo esc_attr($w['reject_reason']); ?>"><?php echo esc_html(mb_substr($w['reject_reason'], 0, 15)); ?></span>
                                <?php else : ?>
                                    <span style="color:#999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($w['created_at']); ?></td>
                            <td><?php echo $w['processed_at'] ? esc_html($w['processed_at']) : '-'; ?></td>
                            <td style="white-space:nowrap;">
                                <?php if ($w['status'] === 'pending') : ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=onedown-withdrawals&withdrawal_action=approve&withdrawal_id=' . $w['withdrawal_id']), 'onedown_withdrawal_action')); ?>"
                                        class="button button-small" style="color:green;"
                                        onclick="return confirm('确认通过此提现申请？佣金将从用户余额中扣除。')">标记已提现</a>
                                    <button type="button" class="button button-small" style="color:#a00;"
                                        onclick="openRejectModal('<?php echo esc_js($w['withdrawal_id']); ?>')">拒绝</button>
                                <?php else : ?>
                                    <span style="color:#999;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr>
                        <td colspan="12" style="text-align:center;">暂无提现申请记录</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom" style="margin-top:12px;">
                <div class="tablenav-pages" style="float:right;">
                    <?php
                    echo paginate_links(array(
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total'     => $total_pages,
                        'current'   => $current_page,
                    ));
                    ?>
                </div>
                <br class="clear">
            </div>
        <?php endif; ?>
    </div>

    <!-- 拒绝原因弹窗 -->
    <div id="rejectModal"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:100000;align-items:center;justify-content:center;">
        <div
            style="background:#fff;border-radius:8px;padding:24px;max-width:460px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,0.3);">
            <h3 style="margin:0 0 16px;font-size:18px;color:#a00;"><span style="color:#a00;">✕</span> 拒绝提现申请</h3>
            <form method="post" action="">
                <input type="hidden" name="withdrawal_reject" value="1">
                <input type="hidden" name="withdrawal_id" id="reject_withdrawal_id" value="">
                <?php wp_nonce_field('onedown_withdrawal_reject', '_wpnonce'); ?>
                <p style="margin:0 0 12px;color:#666;font-size:13px;">请填写拒绝原因，提交后佣金将退回用户余额：</p>
                <textarea name="reject_reason" id="reject_reason_input" rows="4"
                    style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;resize:vertical;box-sizing:border-box;"
                    placeholder="请输入拒绝原因..." required></textarea>
                <div style="margin-top:16px;display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" class="button" onclick="closeRejectModal()"
                        style="font-size:14px;padding:6px 18px;">取消</button>
                    <button type="submit" class="button button-primary"
                        style="background:#a00;border-color:#a00;font-size:14px;padding:6px 18px;">确认拒绝</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(withdrawalId) {
            document.getElementById('reject_withdrawal_id').value = withdrawalId;
            document.getElementById('reject_reason_input').value = '';
            var modal = document.getElementById('rejectModal');
            modal.style.display = 'flex';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }
        // 点击遮罩层关闭
        document.getElementById('rejectModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
    </script>
<?php
}

// ──────────────────────────────────────────────
// 8. 自动取消超时未支付订单
// ──────────────────────────────────────────────

/**
 * 添加自定义 cron 间隔（5分钟）
 */
add_filter('cron_schedules', function ($schedules) {
    $schedules['onedown_5min'] = array(
        'interval' => 300,
        'display'  => '每5分钟',
    );
    return $schedules;
});

/**
 * 自动取消超过15分钟未支付的订单
 */
if (!function_exists('onedown_auto_cancel_pending_orders')):
    function onedown_auto_cancel_pending_orders()
    {
        global $wpdb;
        $table = onedown_order_table();

        // 查询超过15分钟仍未支付的订单
        $cutoff = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $pending_orders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE status = %s AND create_time < %s",
                ONEDOWN_ORDER_STATUS_PENDING,
                $cutoff
            )
        );

        if (empty($pending_orders)) {
            return;
        }

        foreach ($pending_orders as $order) {
            onedown_update_order($order->order_id, array('status' => ONEDOWN_ORDER_STATUS_CLOSED));
        }
    }
endif;
add_action('onedown_auto_cancel_cron', 'onedown_auto_cancel_pending_orders');
