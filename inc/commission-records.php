<?php

/**
 * Onedown 佣金记录系统
 *
 * 集中管理所有佣金记录，提供统一的数据库存储、查询、管理功能
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

// ──────────────────────────────────────────────
// 1. 数据库安装
// ──────────────────────────────────────────────

/**
 * 创建佣金记录数据库表
 */
function onedown_commission_create_db()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'onedown_commissions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `commission_id` varchar(64) NOT NULL DEFAULT '' COMMENT '佣金唯一标识',
        `order_id` varchar(50) NOT NULL DEFAULT '' COMMENT '关联订单号',
        `buyer_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '购买者用户ID',
        `referrer_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '获得佣金用户ID(推荐人)',
        `order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
        `commission_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '佣金金额',
        `commission_ratio` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '佣金比例(%)',
        `order_type` varchar(30) NOT NULL DEFAULT '' COMMENT '订单类型',
        `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '佣金状态: pending|withdrawable|withdrawn',
        `remark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
        `create_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
        `update_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `buyer_id` (`buyer_id`),
        KEY `referrer_id` (`referrer_id`),
        KEY `status` (`status`),
        KEY `create_time` (`create_time`)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    update_option('onedown_commission_db_version', '1.0.0');
}
add_action('after_switch_theme', 'onedown_commission_create_db');

/**
 * 获取佣金记录表名
 */
function onedown_commission_table()
{
    global $wpdb;
    return $wpdb->prefix . 'onedown_commissions';
}

/**
 * 获取佣金记录表名（带自动建表）
 */
function onedown_get_commission_table()
{
    global $wpdb;
    $table = $wpdb->prefix . 'onedown_commissions';
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        onedown_commission_create_db();
    }
    return $table;
}

// ──────────────────────────────────────────────
// 2. 佣金记录 CRUD
// ──────────────────────────────────────────────

/**
 * 添加佣金记录
 *
 * @param array $args
 * @return int|false 记录ID或false
 */
function onedown_add_commission_record($args = array())
{
    global $wpdb;
    $table = onedown_get_commission_table();

    $defaults = array(
        'commission_id'    => '',
        'order_id'         => '',
        'buyer_id'         => 0,
        'referrer_id'      => 0,
        'order_amount'     => 0,
        'commission_amount' => 0,
        'commission_ratio' => 0,
        'order_type'       => '',
        'status'           => 'pending',
        'remark'           => '',
    );

    $data = wp_parse_args($args, $defaults);

    if ($data['referrer_id'] <= 0 || $data['commission_amount'] <= 0) {
        return false;
    }

    // 生成唯一commission_id
    if (empty($data['commission_id'])) {
        $data['commission_id'] = 'CM' . date('YmdHis') . strtoupper(wp_generate_password(6, false));
    }

    $now = current_time('mysql');

    $insert = array(
        'commission_id'     => sanitize_text_field($data['commission_id']),
        'order_id'          => sanitize_text_field($data['order_id']),
        'buyer_id'          => intval($data['buyer_id']),
        'referrer_id'       => intval($data['referrer_id']),
        'order_amount'      => floatval($data['order_amount']),
        'commission_amount' => floatval($data['commission_amount']),
        'commission_ratio'  => floatval($data['commission_ratio']),
        'order_type'        => sanitize_text_field($data['order_type']),
        'status'            => in_array($data['status'], array('pending', 'withdrawable', 'withdrawn')) ? $data['status'] : 'pending',
        'remark'            => sanitize_textarea_field($data['remark']),
        'create_time'       => $now,
        'update_time'       => $now,
    );

    $result = $wpdb->insert($table, $insert);

    if ($result === false) {
        return false;
    }

    return $wpdb->insert_id;
}

/**
 * 更新佣金记录
 *
 * @param int   $id
 * @param array $data
 * @return bool
 */
function onedown_update_commission_record($id, $data)
{
    global $wpdb;
    $table = onedown_commission_table();

    $allowed_fields = array('commission_amount', 'commission_ratio', 'status', 'remark', 'order_amount');
    $update = array();

    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $update[$field] = $data[$field];
        }
    }

    if (empty($update)) {
        return false;
    }

    $update['update_time'] = current_time('mysql');

    return $wpdb->update($table, $update, array('id' => intval($id))) !== false;
}

/**
 * 删除佣金记录
 *
 * @param int $id
 * @return bool
 */
function onedown_delete_commission_record($id)
{
    global $wpdb;
    $table = onedown_commission_table();
    return $wpdb->delete($table, array('id' => intval($id))) !== false;
}

/**
 * 根据ID获取佣金记录
 *
 * @param int $id
 * @return object|null
 */
function onedown_get_commission_record($id)
{
    global $wpdb;
    $table = onedown_commission_table();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
}

/**
 * 根据commission_id获取佣金记录
 *
 * @param string $commission_id
 * @return object|null
 */
function onedown_get_commission_by_commission_id($commission_id)
{
    global $wpdb;
    $table = onedown_commission_table();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE commission_id = %s", $commission_id));
}

/**
 * 获取佣金记录列表（支持筛选分页）
 *
 * @param array $args
 * @return array
 */
function onedown_get_commission_records($args = array())
{
    global $wpdb;
    $table = onedown_commission_table();

    $defaults = array(
        'status'      => '',
        'referrer_id' => 0,
        'buyer_id'    => 0,
        'search'      => '',
        'orderby'     => 'create_time',
        'order'       => 'DESC',
        'limit'       => 20,
        'offset'      => 0,
    );
    $args = wp_parse_args($args, $defaults);

    $where = array('1=1');

    if (!empty($args['status'])) {
        $where[] = $wpdb->prepare('status = %s', $args['status']);
    }
    if (!empty($args['referrer_id'])) {
        $where[] = $wpdb->prepare('referrer_id = %d', $args['referrer_id']);
    }
    if (!empty($args['buyer_id'])) {
        $where[] = $wpdb->prepare('buyer_id = %d', $args['buyer_id']);
    }
    if (!empty($args['search'])) {
        $search = '%' . $wpdb->esc_like($args['search']) . '%';
        $where[] = $wpdb->prepare('(order_id LIKE %s OR commission_id LIKE %s)', $search, $search);
    }

    $where_sql = implode(' AND ', $where);
    $orderby = in_array($args['orderby'], array('create_time', 'commission_amount', 'order_amount', 'id')) ? $args['orderby'] : 'create_time';
    $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
    $limit = intval($args['limit']);
    $offset = intval($args['offset']);

    $sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT {$offset}, {$limit}";
    $items = $wpdb->get_results($sql);

    $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
    $total = intval($wpdb->get_var($count_sql));

    return array(
        'items' => $items,
        'total' => $total,
    );
}

/**
 * 统计佣金汇总
 *
 * @param array $args
 * @return object
 */
function onedown_get_commission_summary($args = array())
{
    global $wpdb;
    $table = onedown_commission_table();

    $where = array('1=1');

    if (!empty($args['status'])) {
        $where[] = $wpdb->prepare('status = %s', $args['status']);
    }
    if (!empty($args['referrer_id'])) {
        $where[] = $wpdb->prepare('referrer_id = %d', $args['referrer_id']);
    }

    $where_sql = implode(' AND ', $where);

    $sql = "SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(commission_amount), 0) as total_commission,
        COALESCE(SUM(order_amount), 0) as total_order_amount
    FROM {$table} WHERE {$where_sql}";

    return $wpdb->get_row($sql);
}

// ──────────────────────────────────────────────
// 3. 兼容旧系统 - 在原有佣金记录时同步写入新表
// ──────────────────────────────────────────────

/**
 * 在原有佣金记录保存后，同步写入新表
 * 钩住 onedown_referral_add_commission 中的佣金记录逻辑
 */
add_action('onedown_commission_recorded', 'onedown_sync_commission_to_db', 10, 3);

/**
 * 从旧系统同步一条佣金记录到新表
 *
 * @param int    $referrer_id 推荐人ID
 * @param string $order_id    订单号
 * @param float  $commission  佣金金额
 * @param string $order_type  订单类型
 */
function onedown_sync_commission_to_db($referrer_id, $order_id, $commission, $order_type)
{
    $order = onedown_get_order($order_id);
    if (!$order) {
        return;
    }

    $ratio = onedown_referral_get_commission_ratio($referrer_id);

    onedown_add_commission_record(array(
        'order_id'          => $order_id,
        'buyer_id'          => intval($order->user_id),
        'referrer_id'       => $referrer_id,
        'order_amount'      => floatval($order->order_price),
        'commission_amount' => floatval($commission),
        'commission_ratio'  => $ratio,
        'order_type'        => $order_type,
        'status'            => 'pending',
    ));
}

// ──────────────────────────────────────────────
// 4. 数据迁移：将旧 user_meta 数据导入新表
// ──────────────────────────────────────────────

/**
 * 迁移旧的佣金数据到新表
 *
 * @return array{total: int, migrated: int}
 */
function onedown_migrate_old_commissions()
{
    global $wpdb;
    $table = onedown_get_commission_table();

    // 查询所有有佣金记录的用户
    $user_args = array(
        'meta_key'     => 'onedown_referral_commissions',
        'meta_compare' => 'EXISTS',
        'number'       => 500,
        'fields'       => array('ID'),
    );
    $users = get_users($user_args);

    $total = 0;
    $migrated = 0;

    foreach ($users as $user) {
        $commissions = get_user_meta($user->ID, 'onedown_referral_commissions', true);
        if (!is_array($commissions) || empty($commissions)) {
            continue;
        }

        foreach ($commissions as $c) {
            $total++;

            // 检查是否已迁移（通过order_id+referrer_id去重）
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE order_id = %s AND referrer_id = %d AND commission_amount = %f",
                $c['order_id'],
                $user->ID,
                floatval($c['commission'])
            ));

            if ($exists) {
                continue;
            }

            // 获取订单信息补充buyer_id和order_amount
            $order = onedown_get_order($c['order_id']);
            $buyer_id = !empty($c['buyer_id']) ? intval($c['buyer_id']) : ($order ? intval($order->user_id) : 0);
            $order_amount = !empty($c['amount']) ? floatval($c['amount']) : ($order ? floatval($order->order_price) : 0);
            $order_type = !empty($c['order_type']) ? $c['order_type'] : ($order ? $order->order_type : '');

            $wpdb->insert($table, array(
                'commission_id'     => 'CM' . date('YmdHis') . strtoupper(wp_generate_password(6, false)),
                'order_id'          => $c['order_id'],
                'buyer_id'          => $buyer_id,
                'referrer_id'       => $user->ID,
                'order_amount'      => $order_amount,
                'commission_amount' => floatval($c['commission']),
                'commission_ratio'  => floatval($c['ratio']),
                'order_type'        => $order_type,
                'status'            => $c['status'],
                'create_time'       => $c['created_at'],
                'update_time'       => current_time('mysql'),
            ));

            $migrated++;
        }
    }

    return array(
        'total'    => $total,
        'migrated' => $migrated,
    );
}

// ──────────────────────────────────────────────
// 5. 管理员佣金管理页面
// ──────────────────────────────────────────────

/**
 * 添加佣金管理子菜单
 */
add_action('admin_menu', 'onedown_commission_admin_menu', 20);
function onedown_commission_admin_menu()
{
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        return;
    }

    add_submenu_page(
        'onedown-orders',
        __('佣金管理', 'onedown'),
        __('佣金管理', 'onedown'),
        'manage_options',
        'onedown-commissions',
        'onedown_commission_admin_page'
    );
}

/**
 * 处理佣金管理操作
 */
add_action('admin_init', 'onedown_commission_admin_actions');
function onedown_commission_admin_actions()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $base_url = admin_url('admin.php?page=onedown-commissions');

    // 编辑佣金
    if (isset($_POST['edit_commission']) && isset($_POST['commission_id'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'onedown_commission_edit')) {
            wp_die('安全验证失败');
        }

        $id = intval($_POST['commission_id']);
        $commission = onedown_get_commission_record($id);

        if (!$commission) {
            set_transient('onedown_commission_msg', array('type' => 'error', 'text' => '记录不存在'), 30);
            wp_redirect($base_url);
            exit;
        }

        $update_data = array();

        if (isset($_POST['commission_amount'])) {
            $update_data['commission_amount'] = floatval($_POST['commission_amount']);
        }
        if (isset($_POST['order_amount'])) {
            $update_data['order_amount'] = floatval($_POST['order_amount']);
        }
        if (isset($_POST['commission_ratio'])) {
            $update_data['commission_ratio'] = floatval($_POST['commission_ratio']);
        }
        if (isset($_POST['status']) && in_array($_POST['status'], array('pending', 'withdrawable', 'withdrawn'))) {
            $update_data['status'] = $_POST['status'];
        }
        if (isset($_POST['remark'])) {
            $update_data['remark'] = sanitize_textarea_field($_POST['remark']);
        }

        if (!empty($update_data)) {
            $updated = onedown_update_commission_record($id, $update_data);

            // 同步更新 user_meta 中的旧数据（保持兼容）
            if ($updated) {
                $record = onedown_get_commission_record($id);
                if ($record) {
                    $commissions = get_user_meta($record->referrer_id, 'onedown_referral_commissions', true);
                    if (is_array($commissions)) {
                        foreach ($commissions as $k => $c) {
                            if ($c['order_id'] === $record->order_id && floatval($c['commission']) === floatval($commission->commission_amount)) {
                                if (isset($update_data['commission_amount'])) {
                                    $commissions[$k]['commission'] = $update_data['commission_amount'];
                                }
                                if (isset($update_data['status'])) {
                                    $commissions[$k]['status'] = $update_data['status'];
                                }
                                break;
                            }
                        }
                        update_user_meta($record->referrer_id, 'onedown_referral_commissions', $commissions);
                    }
                }
            }

            $msg = $updated ? '佣金记录已更新' : '更新失败';
            $type = $updated ? 'success' : 'error';
            set_transient('onedown_commission_msg', array('type' => $type, 'text' => $msg), 30);
        }

        wp_redirect($base_url);
        exit;
    }

    // 删除佣金
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'onedown_commission_delete')) {
            wp_die('安全验证失败');
        }

        $id = intval($_GET['id']);
        $deleted = onedown_delete_commission_record($id);
        $msg = $deleted ? '佣金记录已删除' : '删除失败';
        $type = $deleted ? 'success' : 'error';
        set_transient('onedown_commission_msg', array('type' => $type, 'text' => $msg), 30);

        wp_redirect($base_url);
        exit;
    }

    // 数据迁移
    if (isset($_POST['migrate_commissions'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'onedown_commission_migrate')) {
            wp_die('安全验证失败');
        }

        $result = onedown_migrate_old_commissions();
        set_transient('onedown_commission_msg', array(
            'type' => 'success',
            'text' => '迁移完成！共发现 ' . $result['total'] . ' 条旧记录，成功迁移 ' . $result['migrated'] . ' 条。',
        ), 30);

        wp_redirect($base_url);
        exit;
    }
}

/**
 * 佣金状态标签
 */
function onedown_commission_status_label($status)
{
    $labels = array(
        'pending'      => '<span style="color:#f0ad4e;">待结算</span>',
        'withdrawable' => '<span style="color:green;">可提现</span>',
        'withdrawn'    => '<span style="color:#999;">已提现</span>',
    );
    return isset($labels[$status]) ? $labels[$status] : esc_html($status);
}

/**
 * 佣金管理页面
 */
function onedown_commission_admin_page()
{
    // 闪存消息
    $flash = get_transient('onedown_commission_msg');
    delete_transient('onedown_commission_msg');
    if (!empty($flash) && isset($flash['type'], $flash['text'])) {
        $notice_class = $flash['type'] === 'error' ? 'notice-error' : 'notice-success';
        echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible"><p>' . esc_html($flash['text']) . '</p></div>';
    }

    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    $args = array(
        'limit'  => $per_page,
        'offset' => ($current_page - 1) * $per_page,
    );
    if ($status_filter) {
        $args['status'] = $status_filter;
    }
    if ($search) {
        $args['search'] = $search;
    }

    $result = onedown_get_commission_records($args);
    $records = $result['items'];
    $total = $result['total'];
    $total_pages = ceil($total / $per_page);

    // 汇总统计
    $summary = onedown_get_commission_summary();
    $pending_summary = onedown_get_commission_summary(array('status' => 'pending'));
    $withdrawable_summary = onedown_get_commission_summary(array('status' => 'withdrawable'));

    $statuses = array(
        ''            => '全部状态',
        'pending'     => '待结算',
        'withdrawable' => '可提现',
        'withdrawn'   => '已提现',
    );

    // 编辑弹窗数据
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $edit_record = $edit_id ? onedown_get_commission_record($edit_id) : null;
?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('佣金管理', 'onedown'); ?></h1>
        <hr class="wp-header-end">

        <!-- 统计卡片 -->
        <div style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap;">
            <div style="flex:1;min-width:180px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:16px;text-align:center;">
                <strong style="font-size:24px;color:#2271b1;">￥<?php echo number_format($summary->total_commission, 2); ?></strong>
                <p style="margin:4px 0 0;color:#666;font-size:13px;">累计佣金总额</p>
            </div>
            <div style="flex:1;min-width:180px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:16px;text-align:center;">
                <strong style="font-size:24px;color:#f0ad4e;">￥<?php echo number_format($pending_summary->total_commission, 2); ?></strong>
                <p style="margin:4px 0 0;color:#666;font-size:13px;">待结算佣金</p>
            </div>
            <div style="flex:1;min-width:180px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:16px;text-align:center;">
                <strong style="font-size:24px;color:green;">￥<?php echo number_format($withdrawable_summary->total_commission, 2); ?></strong>
                <p style="margin:4px 0 0;color:#666;font-size:13px;">可提现佣金</p>
            </div>
            <div style="flex:1;min-width:180px;background:#fff;border:1px solid #ccd0d4;border-radius:4px;padding:16px;text-align:center;">
                <strong style="font-size:24px;color:#666;"><?php echo intval($summary->total_count); ?></strong>
                <p style="margin:4px 0 0;color:#666;font-size:13px;">总记录数</p>
            </div>
        </div>

        <!-- 筛选 -->
        <form method="get" style="margin-bottom:12px;">
            <input type="hidden" name="page" value="onedown-commissions">
            <select name="status">
                <?php foreach ($statuses as $val => $label) : ?>
                    <option value="<?php echo esc_attr($val); ?>" <?php selected($status_filter, $val); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="订单号/佣金编号">
            <button type="submit" class="button">筛选</button>
        </form>

        <!-- 数据迁移按钮 -->
        <form method="post" style="display:inline-block;margin-bottom:12px;">
            <?php wp_nonce_field('onedown_commission_migrate', '_wpnonce'); ?>
            <button type="submit" name="migrate_commissions" class="button button-secondary"
                onclick="return confirm('确认从旧数据迁移佣金记录？已迁移的记录不会重复导入。')">
                <i class="dashicons dashicons-migrate" style="line-height:28px;"></i> 迁移旧数据
            </button>
        </form>

        <!-- 佣金列表 -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="60">ID</th>
                    <th>佣金编号</th>
                    <th>订单号</th>
                    <th>购买者</th>
                    <th>获得佣金用户</th>
                    <th>订单金额</th>
                    <th>佣金金额</th>
                    <th>比例</th>
                    <th>状态</th>
                    <th>时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)) : foreach ($records as $r) :
                    $buyer = $r->buyer_id ? get_userdata($r->buyer_id) : null;
                    $referrer = $r->referrer_id ? get_userdata($r->referrer_id) : null;
                ?>
                    <tr>
                        <td><?php echo intval($r->id); ?></td>
                        <td><code><?php echo esc_html($r->commission_id); ?></code></td>
                        <td><code><?php echo esc_html($r->order_id); ?></code></td>
                        <td>
                            <?php if ($buyer) : ?>
                                <strong><?php echo esc_html($buyer->display_name ?: $buyer->user_login); ?></strong>
                                <br><small style="color:#999;">ID:<?php echo $buyer->ID; ?></small>
                            <?php else : ?>
                                <span style="color:#999;">- (ID:<?php echo intval($r->buyer_id); ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($referrer) : ?>
                                <strong><?php echo esc_html($referrer->display_name ?: $referrer->user_login); ?></strong>
                                <br><small style="color:#999;">ID:<?php echo $referrer->ID; ?></small>
                            <?php else : ?>
                                <span style="color:#999;">- (ID:<?php echo intval($r->referrer_id); ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td>￥<?php echo number_format($r->order_amount, 2); ?></td>
                        <td><strong>￥<?php echo number_format($r->commission_amount, 2); ?></strong></td>
                        <td><?php echo floatval($r->commission_ratio); ?>%</td>
                        <td><?php echo onedown_commission_status_label($r->status); ?></td>
                        <td><?php echo esc_html($r->create_time); ?></td>
                        <td style="white-space:nowrap;">
                            <a href="#edit-<?php echo $r->id; ?>" class="button button-small"
                                onclick="openEditModal(<?php echo $r->id; ?>, '<?php echo esc_js($r->commission_id); ?>', '<?php echo esc_js($r->order_id); ?>', '<?php echo $r->commission_amount; ?>', '<?php echo $r->order_amount; ?>', '<?php echo $r->commission_ratio; ?>', '<?php echo esc_js($r->status); ?>', '<?php echo esc_js($r->remark); ?>')">编辑</a>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=onedown-commissions&action=delete&id=' . $r->id), 'onedown_commission_delete')); ?>"
                                class="button button-small" style="color:#a00;"
                                onclick="return confirm('确认删除此佣金记录？')">删除</a>
                        </td>
                    </tr>
                <?php endforeach;
                else : ?>
                    <tr>
                        <td colspan="11" style="text-align:center;padding:32px;">
                            <p style="font-size:16px;color:#999;">暂无佣金记录</p>
                            <p style="font-size:13px;color:#bbb;">如果已有旧佣金数据，请点击上方「迁移旧数据」按钮导入</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- 分页 -->
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

    <!-- 编辑弹窗 -->
    <div id="editCommissionModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:100000;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:8px;padding:24px;max-width:520px;width:90%;box-shadow:0 4px 24px rgba(0,0,0,0.3);">
            <h3 style="margin:0 0 16px;font-size:18px;">编辑佣金记录</h3>
            <form method="post" action="">
                <input type="hidden" name="edit_commission" value="1">
                <input type="hidden" name="commission_id" id="edit_commission_id" value="">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;margin-bottom:4px;font-weight:600;font-size:13px;">订单金额</label>
                        <input type="number" name="order_amount" id="edit_order_amount" step="0.01" min="0"
                            style="width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:4px;font-weight:600;font-size:13px;">佣金金额</label>
                        <input type="number" name="commission_amount" id="edit_commission_amount" step="0.01" min="0"
                            style="width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:4px;font-weight:600;font-size:13px;">佣金比例(%)</label>
                        <input type="number" name="commission_ratio" id="edit_commission_ratio" step="0.01" min="0" max="100"
                            style="width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:4px;font-weight:600;font-size:13px;">状态</label>
                        <select name="status" id="edit_status" style="width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;">
                            <option value="pending">待结算</option>
                            <option value="withdrawable">可提现</option>
                            <option value="withdrawn">已提现</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <label style="display:block;margin-bottom:4px;font-weight:600;font-size:13px;">备注</label>
                    <textarea name="remark" id="edit_remark" rows="2"
                        style="width:100%;padding:6px 8px;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;resize:vertical;"></textarea>
                </div>
                <div style="margin-top:12px;">
                    <p style="font-size:12px;color:#999;margin:0;">
                        佣金编号：<code id="edit_commission_code">-</code>
                        &nbsp;|&nbsp; 订单号：<code id="edit_order_code">-</code>
                    </p>
                </div>
                <?php wp_nonce_field('onedown_commission_edit', '_wpnonce'); ?>
                <div style="margin-top:16px;display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" class="button" onclick="closeEditModal()" style="font-size:14px;padding:6px 18px;">取消</button>
                    <button type="submit" class="button button-primary" style="font-size:14px;padding:6px 18px;">保存修改</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openEditModal(id, commissionId, orderId, commissionAmount, orderAmount, ratio, status, remark) {
        document.getElementById('edit_commission_id').value = id;
        document.getElementById('edit_commission_code').textContent = commissionId;
        document.getElementById('edit_order_code').textContent = orderId;
        document.getElementById('edit_commission_amount').value = commissionAmount;
        document.getElementById('edit_order_amount').value = orderAmount;
        document.getElementById('edit_commission_ratio').value = ratio;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_remark').value = remark;
        document.getElementById('editCommissionModal').style.display = 'flex';
    }
    function closeEditModal() {
        document.getElementById('editCommissionModal').style.display = 'none';
    }
    document.getElementById('editCommissionModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeEditModal();
    });
    </script>
<?php
}

// ──────────────────────────────────────────────
// 6. 钩入原有佣金记录函数，同步写入新表
// ──────────────────────────────────────────────

/**
 * 重写 onedown_referral_add_commission 的部分逻辑
 * 在原有记录基础上，同步写入新表
 */
add_action('onedown_payment_success', 'onedown_record_commission_on_payment', 10, 2);
function onedown_record_commission_on_payment($order_id, $order)
{
    if (!$order || intval($order->referrer_id) <= 0 || floatval($order->rebate_price) <= 0) {
        return;
    }

    // 检查是否已经记录过
    global $wpdb;
    $table = onedown_get_commission_table();
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE order_id = %s AND referrer_id = %d",
        $order_id,
        $order->referrer_id
    ));

    if ($exists) {
        return;
    }

    $ratio = onedown_referral_get_commission_ratio($order->referrer_id);

    onedown_add_commission_record(array(
        'order_id'          => $order_id,
        'buyer_id'          => intval($order->user_id),
        'referrer_id'       => intval($order->referrer_id),
        'order_amount'      => floatval($order->order_price),
        'commission_amount' => floatval($order->rebate_price),
        'commission_ratio'  => $ratio,
        'order_type'        => $order->order_type,
        'status'            => 'pending',
    ));
}

/**
 * 监听旧系统佣金记录，同步写入新表
 * 此函数被钩到修改后的 onedown_referral_calc_commission 中
 */
function onedown_legacy_commission_sync($referrer_id, $order_id, $commission_data = array())
{
    global $wpdb;
    $table = onedown_get_commission_table();

    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE order_id = %s AND referrer_id = %d",
        $order_id,
        $referrer_id
    ));

    if (!$exists) {
        $order = onedown_get_order($order_id);
        onedown_add_commission_record(array(
            'order_id'          => $order_id,
            'buyer_id'          => !empty($commission_data['buyer_id']) ? intval($commission_data['buyer_id']) : ($order ? intval($order->user_id) : 0),
            'referrer_id'       => $referrer_id,
            'order_amount'      => !empty($commission_data['amount']) ? floatval($commission_data['amount']) : ($order ? floatval($order->order_price) : 0),
            'commission_amount' => !empty($commission_data['commission']) ? floatval($commission_data['commission']) : 0,
            'commission_ratio'  => !empty($commission_data['ratio']) ? floatval($commission_data['ratio']) : 0,
            'order_type'        => !empty($commission_data['order_type']) ? $commission_data['order_type'] : ($order ? $order->order_type : ''),
            'status'            => !empty($commission_data['status']) ? $commission_data['status'] : 'pending',
        ));
    }
}

/**
 * 在 onedown_referral_calc_commission 中同步记录
 * 通过修改函数内部，在保存到 user_meta 后同步到新表
 */
// 在 onedown_referral_calc_commission 函数末尾添加同步调用
// 通过钩子实现：在 user-referral.php 中已有 update_user_meta，之后触发此钩子
add_action('onedown_referral_commission_saved', 'onedown_legacy_commission_sync', 10, 3);

/**
 * 初始化时确保表存在
 */
add_action('init', 'onedown_ensure_commission_table');
function onedown_ensure_commission_table()
{
    if (!wp_doing_ajax() && is_admin() && current_user_can('manage_options')) {
        global $wpdb;
        $table = $wpdb->prefix . 'onedown_commissions';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            onedown_commission_create_db();
        }
    }
}
