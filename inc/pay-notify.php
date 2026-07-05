<?php

/**
 * Onedown 支付通知处理
 *
 * 处理各支付渠道的异步通知和同步跳转
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

// ──────────────────────────────────────────────
// 1. 捕获支付通知请求
// ──────────────────────────────────────────────

/**
 * 通过 query var 捕获支付通知
 */
add_action('init', 'onedown_pay_notify_init');
function onedown_pay_notify_init()
{
    // 未授权时不处理任何支付通知
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        return;
    }

    // 支付异步通知
    $notify = isset($_GET['pay_notify']) ? sanitize_text_field($_GET['pay_notify']) : '';
    if ($notify) {
        if (function_exists('error_log')) {
            error_log('[onedown-pay] notify init channel=' . $notify . ' method=' . ($_SERVER['REQUEST_METHOD'] ?? '') . ' uri=' . ($_SERVER['REQUEST_URI'] ?? ''));
        }
        onedown_handle_pay_notify($notify);
        exit;
    }

    // 支付同步跳转
    $return = isset($_GET['pay_return']) ? sanitize_text_field($_GET['pay_return']) : '';
    if ($return) {
        if (function_exists('error_log')) {
            error_log('[onedown-pay] return init channel=' . $return . ' method=' . ($_SERVER['REQUEST_METHOD'] ?? '') . ' uri=' . ($_SERVER['REQUEST_URI'] ?? ''));
        }
        onedown_handle_pay_return($return);
        exit;
    }
}

// ──────────────────────────────────────────────
// 2. 异步通知处理
// ──────────────────────────────────────────────

/**
 * 处理异步通知
 */
function onedown_handle_pay_notify($channel)
{
    switch ($channel) {
        case 'epay':
            onedown_epay_notify();
            break;
        case 'alipay':
            onedown_alipay_notify();
            break;
        case 'wechat':
            onedown_wechat_notify();
            break;
    }
}

/**
 * 处理易支付异步通知
 *
 * 使用 $_REQUEST 兼容 GET（同步返回）和 POST（异步通知）两种方式
 */
function onedown_epay_notify()
{
    $config = onedown_get_epay_config();

    if (empty($config['key'])) {
        status_header(500);
        echo 'config error';
        exit;
    }

    // 验证签名（使用 $_REQUEST，兼容 POST 异步通知）
    if (!onedown_epay_verify($_REQUEST, $config['key'])) {
        status_header(400);
        echo 'sign error';
        exit;
    }

    $order_id = sanitize_text_field($_REQUEST['out_trade_no'] ?? '');
    $trade_no = sanitize_text_field($_REQUEST['trade_no'] ?? '');
    $pay_status = intval($_REQUEST['trade_status'] ?? 0);

    if ($pay_status === 1) {
        // 支付成功
        onedown_mark_order_paid($order_id, array(
            'pay_type'    => 'epay',
            'pay_trade_no' => $trade_no,
            'pay_detail'  => array(
                'channel' => 'epay',
                'trade_no' => $trade_no,
                'notify_data' => $_REQUEST,
            ),
        ));
    }

    // 告诉易支付已收到通知
    echo 'success';
    exit;
}

/**
 * 处理支付宝异步通知
 */
function onedown_alipay_notify()
{
    $alipayConfig = Onedown_Pay_Config::instance()->alipay();
    $public_key = $alipayConfig['public_key'];

    if (empty($public_key)) {
        status_header(500);
        echo 'config error';
        exit;
    }

    // 验证签名
    if (!onedown_alipay_verify($_POST, $public_key)) {
        status_header(400);
        echo 'sign error';
        exit;
    }

    $order_id = sanitize_text_field($_POST['out_trade_no'] ?? '');
    $trade_no = sanitize_text_field($_POST['trade_no'] ?? '');
    $trade_status = sanitize_text_field($_POST['trade_status'] ?? '');

    if ($trade_status === 'TRADE_SUCCESS' || $trade_status === 'TRADE_FINISHED') {
        onedown_mark_order_paid($order_id, array(
            'pay_type'    => 'alipay',
            'pay_trade_no' => $trade_no,
            'pay_detail'  => array(
                'channel' => 'alipay_official',
                'trade_no' => $trade_no,
                'buyer_id' => $_POST['buyer_id'] ?? '',
            ),
        ));
    }

    echo 'success';
    exit;
}

/**
 * 处理微信异步通知
 */
function onedown_wechat_notify()
{
    $api_key = _pz('pay_wechat_key', '');

    if (empty($api_key)) {
        status_header(500);
        echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[config error]]></return_msg></xml>';
        exit;
    }

    $xml_data = file_get_contents('php://input');

    // 验证签名
    if (!onedown_wechat_verify($xml_data, $api_key)) {
        status_header(400);
        echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[sign error]]></return_msg></xml>';
        exit;
    }

    $result = simplexml_load_string($xml_data, 'SimpleXMLElement', LIBXML_NOCDATA);
    $order_id = (string) $result->out_trade_no;
    $trade_no = (string) $result->transaction_id;
    $return_code = (string) $result->return_code;
    $result_code = (string) $result->result_code;

    if ($return_code === 'SUCCESS' && $result_code === 'SUCCESS') {
        onedown_mark_order_paid($order_id, array(
            'pay_type'    => 'wechat',
            'pay_trade_no' => $trade_no,
            'pay_detail'  => array(
                'channel' => 'wechat_official',
                'trade_no' => $trade_no,
                'openid' => (string) $result->openid,
            ),
        ));
    }

    echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    exit;
}

// ──────────────────────────────────────────────
// 3. 同步跳转处理
// ──────────────────────────────────────────────

/**
 * 处理支付成功后的同步跳转
 *
 * 用户从支付平台跳回时，如果订单仍为待付款，主动查询支付平台确认状态
 */
function onedown_handle_pay_return($channel)
{
    $order_id = sanitize_text_field($_GET['order_id'] ?? '');

    if (empty($order_id)) {
        wp_redirect(home_url('/'));
        exit;
    }

    $order = onedown_get_order($order_id);

    if (!$order) {
        wp_die('订单不存在');
    }

    // ── 订单仍为待付款时，优先消费同步返回结果，再回退到主动查单 ──
    if ($order->status === ONEDOWN_ORDER_STATUS_PENDING) {
        $pay_type = $order->pay_type;

        // 判断是否走易支付通道
        $use_epay_gateway = false;
        if ($channel === 'epay') {
            $use_epay_gateway = true;
            if (empty($pay_type)) {
                $pay_type = 'epay';
            }
        } elseif ($pay_type === 'wechat' && _pz('wechat_pay_method', 'close') === 'epay') {
            $use_epay_gateway = true;
        } elseif ($pay_type === 'alipay' && _pz('alipay_pay_method', 'close') === 'epay') {
            $use_epay_gateway = true;
        } elseif (in_array($pay_type, array('epay', 'wxpay'), true)) {
            $use_epay_gateway = true;
        }

        if ($use_epay_gateway) {
            $config = onedown_get_epay_config();
            $return_trade_status = sanitize_text_field($_GET['trade_status'] ?? '');
            $return_trade_no = sanitize_text_field($_GET['trade_no'] ?? '');
            $return_money = floatval($_GET['money'] ?? 0);
            $return_out_trade_no = sanitize_text_field($_GET['out_trade_no'] ?? '');
            $return_sign_valid = !empty($config['key']) && onedown_epay_verify($_GET, $config['key']);

            if (function_exists('error_log')) {
                error_log('[onedown-pay] epay return direct order_id=' . $order_id . ' sign_valid=' . ($return_sign_valid ? '1' : '0') . ' trade_status=' . $return_trade_status . ' out_trade_no=' . $return_out_trade_no . ' trade_no=' . $return_trade_no . ' money=' . $return_money);
            }

            if (
                $return_sign_valid
                && $return_out_trade_no === $order_id
                && in_array($return_trade_status, array('TRADE_SUCCESS', 'TRADE_FINISHED', '1'), true)
            ) {
                onedown_mark_order_paid($order_id, array(
                    'pay_type'     => $pay_type,
                    'pay_trade_no' => $return_trade_no,
                    'pay_price'    => $return_money > 0 ? $return_money : floatval($order->order_price),
                    'pay_detail'   => array(
                        'channel'      => 'epay',
                        'trade_no'     => $return_trade_no,
                        'verified_by'  => 'pay_return_direct',
                        'trade_status' => $return_trade_status,
                        'return_data'  => $_GET,
                    ),
                ));
                $order = onedown_get_order($order_id);
            } elseif (function_exists('onedown_epay_query_order')) {
                $query_result = onedown_epay_query_order($order_id);

                if ($query_result['success'] && !empty($query_result['paid'])) {
                    onedown_mark_order_paid($order_id, array(
                        'pay_type'     => $pay_type,
                        'pay_trade_no' => $query_result['trade_no'],
                        'pay_price'    => floatval($query_result['total_amount']),
                        'pay_detail'   => array(
                            'channel'     => 'epay',
                            'trade_no'    => $query_result['trade_no'],
                            'verified_by' => 'pay_return',
                        ),
                    ));
                    $order = onedown_get_order($order_id);
                }
            }
        }
    }

    // 根据订单类型跳转到不同页面
    // 对于已登录用户且是订单所有者，跳转到用户中心订单页，方便查看更新后的状态
    // 但文章付费仍跳转回文章页，方便用户直接查看内容
    $license_redirect_url = '';
    if (($order->order_type ?? '') === 'license') {
        $license_redirect_url = function_exists('onedown_license_front_url')
            ? onedown_license_front_url()
            : home_url('/od-license.html');

        $license_redirect_url = add_query_arg(array(
            'tab'          => 'purchase',
            'license_paid' => '1',
            'order_id'     => $order_id,
        ), $license_redirect_url);
    }

    $user_id = get_current_user_id();
    if ($order->status === ONEDOWN_ORDER_STATUS_PAID && intval($order->user_id) > 0 && intval($order->user_id) === $user_id) {
        if (!empty($license_redirect_url)) {
            $redirect_url = $license_redirect_url;
        } elseif (in_array($order->order_type, array(ONEDOWN_ORDER_TYPE_POST_READ, ONEDOWN_ORDER_TYPE_POST_DOWNLOAD))) {
            $redirect_url = get_permalink($order->post_id);
        } else {
            $redirect_url = onedown_user_center_url(array('tab' => 'orders'));
        }
    } else {
        switch ($order->order_type) {
            case ONEDOWN_ORDER_TYPE_POST_READ:
            case ONEDOWN_ORDER_TYPE_POST_DOWNLOAD:
                $redirect_url = get_permalink($order->post_id);
                break;

            case ONEDOWN_ORDER_TYPE_VIP:
                $redirect_url = onedown_user_center_url(array('tab' => 'vip'));
                break;

            case 'license':
                $redirect_url = $license_redirect_url ?: home_url('/od-license.html');
                break;

            default:
                $redirect_url = home_url('/');
        }
    }

    // 如果是访客，设置 cookie
    if (intval($order->user_id) <= 0 && $order->guest_token && $order->status === ONEDOWN_ORDER_STATUS_PAID) {
        $cookie_key = 'onedown_paid_' . $order->post_id;
        setcookie($cookie_key, $order->order_id, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }

    wp_redirect($redirect_url);
    exit;
}
