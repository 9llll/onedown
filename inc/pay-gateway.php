<?php

/**
 * Onedown 支付网关处理
 *
 * 易支付、支付宝官方、微信官方等支付渠道的集成
 *
 * @package onedown
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Onedown_Pay_Config
{
    private static $instance = null;

    private $options;

    private function __construct()
    {
        $this->options = is_array(get_option('_onedown_options')) ? get_option('_onedown_options') : array();
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function get($key, $default = '')
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
    }

    public function epay(): array
    {
        $apiUrl = untrailingslashit(trim((string) $this->get('pay_epay_api_url', '')));

        return array(
            'api_url' => $apiUrl,
            'pid'     => (string) $this->get('pay_epay_pid', ''),
            'key'     => (string) $this->get('pay_epay_key', ''),
        );
    }

    public function alipay(): array
    {
        return array(
            'method'      => (string) $this->get('alipay_pay_method', 'close'),
            'app_id'      => (string) $this->get('pay_alipay_app_id', ''),
            'private_key' => (string) $this->get('pay_alipay_private_key', ''),
            'public_key'  => (string) $this->get('pay_alipay_public_key', ''),
        );
    }

    public function wechat(): array
    {
        return array(
            'method'  => (string) $this->get('wechat_pay_method', 'close'),
            'app_id'  => (string) $this->get('pay_wechat_app_id', ''),
            'mch_id'  => (string) $this->get('pay_wechat_mch_id', ''),
            'api_key' => (string) $this->get('pay_wechat_key', ''),
        );
    }

    public function isBalanceEnabled(): bool
    {
        return (bool) $this->get('pay_balance_enabled', false);
    }

    public function isOfflineEnabled(): bool
    {
        return (bool) $this->get('pay_offline_enabled', false);
    }

    public function isGuestPurchaseEnabled(): bool
    {
        return (bool) $this->get('guest_purchase_enabled', false);
    }

    public function offlineInfo(): string
    {
        return (string) $this->get('pay_offline_info', '请联系管理员');
    }
}

final class Onedown_Pay_Gateway
{
    public static function getAvailableMethods(): array
    {
        if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
            return array();
        }

        $config = Onedown_Pay_Config::instance();
        $methods = array();

        if ($config->wechat()['method'] !== 'close') {
            $methods['wechat'] = array(
                'id'   => 'wechat',
                'name' => '微信支付',
                'icon' => '',
            );
        }

        if ($config->alipay()['method'] !== 'close') {
            $methods['alipay'] = array(
                'id'   => 'alipay',
                'name' => '支付宝',
                'icon' => '',
            );
        }

        if ($config->isBalanceEnabled()) {
            $methods['balance'] = array(
                'id'   => 'balance',
                'name' => '余额支付',
                'icon' => '',
            );
        }

        if ($config->isOfflineEnabled()) {
            $methods['offline'] = array(
                'id'   => 'offline',
                'name' => '线下支付',
                'icon' => '',
            );
        }

        return apply_filters('onedown_available_pay_methods', $methods);
    }

    public static function getEpayConfig(): array
    {
        return Onedown_Pay_Config::instance()->epay();
    }

    public static function initiate(array $orderData, string $payMethod): array
    {
        $config = Onedown_Pay_Config::instance();

        switch ($payMethod) {
            case 'alipay':
                return self::initiateAlipayByMethod($orderData, $config->alipay()['method']);
            case 'wechat':
                return self::initiateWechatByMethod($orderData, $config->wechat()['method']);
            case 'balance':
                return onedown_initiate_balance($orderData);
            case 'offline':
                return onedown_initiate_offline($orderData);
            default:
                return array('success' => false, 'msg' => '不支持的支付方式');
        }
    }

    private static function initiateAlipayByMethod(array $orderData, string $method): array
    {
        switch ($method) {
            case 'official':
                return onedown_initiate_alipay($orderData);
            case 'epay':
                return onedown_initiate_epay(array_merge($orderData, array('pay_type' => 'alipay')));
            default:
                return array('success' => false, 'msg' => '支付宝支付未开启');
        }
    }

    private static function initiateWechatByMethod(array $orderData, string $method): array
    {
        switch ($method) {
            case 'official':
                return onedown_initiate_wechat($orderData);
            case 'epay':
                return onedown_initiate_epay(array_merge($orderData, array(
                    'pay_type' => 'wxpay',
                    'gateway'  => 'epay',
                )));
            default:
                return array('success' => false, 'msg' => '微信支付未开启');
        }
    }
}

// ──────────────────────────────────────────────
// 1. 获取可用的支付方式
// ──────────────────────────────────────────────

/**
 * 获取已启用的支付方式列表
 */
function onedown_get_available_pay_methods()
{
    return Onedown_Pay_Gateway::getAvailableMethods();
}

/**
 * 获取易支付配置
 */
function onedown_get_epay_config()
{
    return Onedown_Pay_Gateway::getEpayConfig();
}

// ──────────────────────────────────────────────
// 2. 创建支付请求
// ──────────────────────────────────────────────

/**
 * 发起支付请求
 *
 * @param array $order_data 订单数据
 * @param string $pay_method 支付方式
 * @return array 返回支付结果
 */
function onedown_initiate_pay($order_data, $pay_method)
{
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        return array('success' => false, 'msg' => '支付功能已禁用');
    }
    return Onedown_Pay_Gateway::initiate((array) $order_data, (string) $pay_method);
}

/**
 * 发起易支付
 *
 * @param array $order_data
 * @return array
 */
function onedown_initiate_epay($order_data)
{
    $config = onedown_get_epay_config();

    if (empty($config['api_url']) || empty($config['pid']) || empty($config['key'])) {
        return array('success' => false, 'msg' => '易支付未配置');
    }

    $notify_url = home_url('?pay_notify=epay');
    $return_url = home_url('?pay_return=epay&order_id=' . urlencode($order_data['order_id']));

    $pay_type = $order_data['pay_type'] ?? 'alipay';

    if ($pay_type === 'wxpay') {
        $pay_type = 'wxpay';
    }

    $param = array(
        'pid'          => intval($config['pid']),
        'type'         => $pay_type,
        'out_trade_no' => $order_data['order_id'],
        'notify_url'   => $notify_url,
        'return_url'   => $return_url,
        'name'         => $order_data['order_title'],
        'money'        => sprintf('%.2f', $order_data['order_price']),
        'sign_type'    => 'MD5',
    );

    $param['sign'] = onedown_epay_sign($param, $config['key']);
    $pay_url = trailingslashit($config['api_url']) . 'submit.php?' . http_build_query($param);

    return array(
        'success' => true,
        'type'    => 'redirect',
        'url'     => $pay_url,
        'msg'     => '正在跳转到易支付...',
    );
}

/**
 * 易支付签名生成
 */
function onedown_epay_sign($param, $key)
{
    $excluded_keys = array(
        'sign',
        'sign_type',
        'pay_return',
        'pay_notify',
        'order_id',
    );

    ksort($param);
    $sign_str = '';
    foreach ($param as $k => $v) {
        if (!in_array($k, $excluded_keys, true) && $v !== '') {
            $sign_str .= $k . '=' . $v . '&';
        }
    }
    $sign_str = trim($sign_str, '&');

    $sign_str .= $key;
    return md5($sign_str);
}

/**
 * 验证易支付回调签名
 */
function onedown_epay_verify($param, $key)
{
    $sign = $param['sign'] ?? '';
    $cal_sign = onedown_epay_sign($param, $key);

    return strtolower((string) $sign) === strtolower((string) $cal_sign);
}

/**
 * 查询易支付订单状态
 *
 * 调用易支付系统的订单查询 API，确认用户是否已真实付款
 * 适用于大多数易支付系统（彩虹易支付等）的标准 API 格式
 *
 * @param string $order_id 商户订单号
 * @return array
 */
function onedown_epay_query_order($order_id)
{
    $config = onedown_get_epay_config();

    if (empty($config['api_url']) || empty($config['pid']) || empty($config['key'])) {
        return array('success' => false, 'msg' => '易支付未配置');
    }

    $params = array(
        'act'          => 'order',
        'pid'          => intval($config['pid']),
        'key'          => $config['key'],
        'out_trade_no' => $order_id,
    );

    $query_url = trailingslashit($config['api_url']) . 'api.php';

    $response = wp_remote_post($query_url, array(
        'body'      => $params,
        'timeout'   => 15,
        'sslverify' => false,
    ));

    if (is_wp_error($response)) {
        return array('success' => false, 'msg' => '易支付查询请求失败：' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (function_exists('error_log')) {
        error_log('[onedown-pay] epay query order_id=' . $order_id . ' body=' . trim((string) $body));
    }

    if (empty($result)) {
        $body_trim = trim((string) $body);
        if ($body_trim === '1' || stripos($body_trim, 'success') !== false) {
            return array(
                'success'      => true,
                'paid'         => true,
                'trade_status' => 'SUCCESS',
                'trade_no'     => '',
                'total_amount' => 0,
            );
        }
        return array('success' => false, 'msg' => '易支付返回数据解析失败');
    }

    $status = $result['status'] ?? $result['code'] ?? '';
    $status_text = strtolower((string) $status);
    $trade_no = $result['trade_no'] ?? $result['trade_no2'] ?? $result['transaction_id'] ?? '';
    $money = $result['money'] ?? $result['amount'] ?? $result['realmoney'] ?? 0;
    $is_paid = in_array($status_text, array('1', 'ok', 'success', 'paid', 'yes', 'true'), true)
        || in_array((int) $status, array(1, 200), true)
        || (($result['trade_status'] ?? '') === 'TRADE_SUCCESS');

    if (function_exists('error_log')) {
        error_log('[onedown-pay] epay query parsed order_id=' . $order_id . ' status=' . $status_text . ' paid=' . ($is_paid ? '1' : '0') . ' trade_no=' . $trade_no . ' money=' . $money);
    }

    if ($is_paid) {
        return array(
            'success'      => true,
            'paid'         => true,
            'trade_status' => $status,
            'trade_no'     => $trade_no,
            'total_amount' => $money,
        );
    }


    return array(
        'success'      => true,
        'paid'         => false,
        'trade_status' => $status,
        'msg'          => '订单状态：' . ($status !== '' ? $status : 'unknown'),
    );
}

// ──────────────────────────────────────────────
// 3. 支付宝官方支付
// ──────────────────────────────────────────────

/**
 * 发起支付宝官方支付（当面付 - alipay.trade.precreate）
 */
function onedown_initiate_alipay($order_data)
{
    $alipayConfig = Onedown_Pay_Config::instance()->alipay();
    $app_id = $alipayConfig['app_id'];
    $private_key = $alipayConfig['private_key'];

    if (empty($app_id) || empty($private_key)) {
        return array('success' => false, 'msg' => '支付宝未配置');
    }

    $notify_url = home_url('?pay_notify=alipay');

    $biz_content = array(
        'subject'        => $order_data['order_title'],
        'out_trade_no'   => $order_data['order_id'],
        'total_amount'   => sprintf('%.2f', $order_data['order_price']),
    );

    $params = array(
        'app_id'     => $app_id,
        'method'     => 'alipay.trade.precreate',
        'charset'    => 'utf-8',
        'sign_type'  => 'RSA2',
        'timestamp'  => date('Y-m-d H:i:s'),
        'version'    => '1.0',
        'notify_url' => $notify_url,
        'biz_content' => wp_json_encode($biz_content),
    );

    $params['sign'] = onedown_alipay_sign($params, $private_key);
    $gateway = 'https://openapi.alipay.com/gateway.do';

    $response = wp_remote_post($gateway, array(
        'body'        => $params,
        'timeout'     => 15,
        'sslverify'   => false,
    ));

    if (is_wp_error($response)) {
        return array('success' => false, 'msg' => '支付宝请求失败：' . $response->get_error_message());
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (empty($result)) {
        return array('success' => false, 'msg' => '支付宝返回数据解析失败');
    }

    if (!empty($result['error_response'])) {
        $err = $result['error_response'];
        $code = $err['code'] ?? '';
        $msg = $err['sub_msg'] ?? $err['msg'] ?? '未知错误';
        return array('success' => false, 'msg' => $msg . '（' . $code . '）');
    }

    $precreate = $result['alipay_trade_precreate_response'] ?? array();
    if (empty($precreate['code']) || $precreate['code'] !== '10000') {
        $err_msg = $precreate['sub_msg'] ?? $precreate['msg'] ?? '创建订单失败';
        $err_code = $precreate['sub_code'] ?? $precreate['code'] ?? '';
        return array('success' => false, 'msg' => $err_msg . '（' . $err_code . '）');
    }

    $qr_code = $precreate['qr_code'] ?? '';

    if (empty($qr_code)) {
        return array('success' => false, 'msg' => '未获取到支付二维码');
    }

    return array(
        'success'      => true,
        'type'         => 'qrcode',
        'code_url'     => $qr_code,
        'url'          => $qr_code,
        'msg'          => '请使用支付宝扫码支付',
    );
}

/**
 * 支付宝签名
 */
function onedown_alipay_sign($params, $private_key)
{
    ksort($params);
    $sign_str = '';
    foreach ($params as $k => $v) {
        if ($k !== 'sign' && $v !== '') {
            $sign_str .= $k . '=' . $v . '&';
        }
    }
    $sign_str = rtrim($sign_str, '&');

    $pri_key = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($private_key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
    $res = openssl_pkey_get_private($pri_key);
    if (!$res) {
        return '';
    }
    openssl_sign($sign_str, $sign, $res, OPENSSL_ALGO_SHA256);
    return base64_encode($sign);
}

/**
 * 验证支付宝回调签名
 */
function onedown_alipay_verify($params, $public_key)
{
    $sign = $params['sign'] ?? '';
    $sign_type = $params['sign_type'] ?? 'RSA2';

    $sign_params = array();
    foreach ($params as $k => $v) {
        if ($k !== 'sign' && $k !== 'sign_type' && $v !== '') {
            $sign_params[$k] = $v;
        }
    }
    ksort($sign_params);
    $sign_str = '';
    foreach ($sign_params as $k => $v) {
        $sign_str .= $k . '=' . $v . '&';
    }
    $sign_str = rtrim($sign_str, '&');

    $pub_key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($public_key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
    $res = openssl_pkey_get_public($pub_key);
    if (!$res) {
        return false;
    }
    $result = openssl_verify($sign_str, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
    return $result === 1;
}

/**
 * 查询支付宝订单状态（alipay.trade.query）
 *
 * @param string $order_id 订单号
 * @return array
 */
function onedown_alipay_query_order($order_id)
{
    $alipayConfig = Onedown_Pay_Config::instance()->alipay();
    $app_id = $alipayConfig['app_id'];
    $private_key = $alipayConfig['private_key'];

    if (empty($app_id) || empty($private_key)) {
        return array('success' => false, 'msg' => '支付宝未配置');
    }

    $biz_content = array(
        'out_trade_no' => $order_id,
    );

    $params = array(
        'app_id'      => $app_id,
        'method'      => 'alipay.trade.query',
        'charset'     => 'utf-8',
        'sign_type'   => 'RSA2',
        'timestamp'   => date('Y-m-d H:i:s'),
        'version'     => '1.0',
        'biz_content' => wp_json_encode($biz_content),
    );

    $params['sign'] = onedown_alipay_sign($params, $private_key);

    $gateway = 'https://openapi.alipay.com/gateway.do';

    $response = wp_remote_post($gateway, array(
        'body'      => $params,
        'timeout'   => 15,
        'sslverify' => false,
    ));

    if (is_wp_error($response)) {
        return array('success' => false, 'msg' => '支付宝查询请求失败：' . $response->get_error_message());
    }

    $body   = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    if (empty($result)) {
        return array('success' => false, 'msg' => '支付宝返回数据解析失败');
    }

    if (! empty($result['error_response'])) {
        $err = $result['error_response'];
        $code = $err['code'] ?? '';
        $msg  = $err['sub_msg'] ?? $err['msg'] ?? '未知错误';
        return array('success' => false, 'msg' => $msg . '（' . $code . '）');
    }

    $query_resp = $result['alipay_trade_query_response'] ?? array();
    if (empty($query_resp['code']) || $query_resp['code'] !== '10000') {
        $err_msg  = $query_resp['sub_msg'] ?? $query_resp['msg'] ?? '查询失败';
        $err_code = $query_resp['sub_code'] ?? $query_resp['code'] ?? '';
        return array('success' => false, 'msg' => $err_msg . '（' . $err_code . '）');
    }

    $trade_status = $query_resp['trade_status'] ?? '';

    if ($trade_status === 'TRADE_SUCCESS' || $trade_status === 'TRADE_FINISHED') {
        return array(
            'success'        => true,
            'paid'           => true,
            'trade_status'   => $trade_status,
            'trade_no'       => $query_resp['trade_no'] ?? '',
            'buyer_user_id'  => $query_resp['buyer_user_id'] ?? '',
            'total_amount'   => $query_resp['total_amount'] ?? '',
        );
    }

    return array(
        'success'      => true,
        'paid'         => false,
        'trade_status' => $trade_status,
        'msg'          => '订单状态：' . $trade_status,
    );
}

// ──────────────────────────────────────────────
// 4. 微信官方支付 (Native)
// ──────────────────────────────────────────────

/**
 * 发起微信官方支付 (Native 模式)
 */
function onedown_initiate_wechat($order_data)
{
    $wechatConfig = Onedown_Pay_Config::instance()->wechat();
    $app_id = $wechatConfig['app_id'];
    $mch_id = $wechatConfig['mch_id'];
    $api_key = $wechatConfig['api_key'];

    if (empty($app_id) || empty($mch_id) || empty($api_key)) {
        return array('success' => false, 'msg' => '微信支付未配置');
    }

    $notify_url = home_url('?pay_notify=wechat');
    $order_price = intval($order_data['order_price'] * 100);

    $params = array(
        'appid'        => $app_id,
        'mch_id'       => $mch_id,
        'nonce_str'    => wp_generate_password(16, false),
        'body'         => $order_data['order_title'],
        'out_trade_no' => $order_data['order_id'],
        'total_fee'    => $order_price,
        'spbill_create_ip' => onedown_get_client_ip(),
        'notify_url'   => $notify_url,
        'trade_type'   => 'NATIVE',
    );

    $params['sign'] = onedown_wechat_sign($params, $api_key);
    $xml = onedown_array_to_xml($params);
    $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    $response = onedown_wechat_post_xml($url, $xml);

    if (!$response) {
        return array('success' => false, 'msg' => '微信支付请求失败');
    }

    $result = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($result === false || $result->return_code != 'SUCCESS') {
        $err_msg = isset($result->return_msg) ? (string) $result->return_msg : '通信失败';
        return array('success' => false, 'msg' => $err_msg);
    }

    if ($result->result_code != 'SUCCESS') {
        $err_msg = isset($result->err_code_des) ? (string) $result->err_code_des : '业务失败';
        return array('success' => false, 'msg' => $err_msg);
    }

    $code_url = (string) $result->code_url;

    return array(
        'success'  => true,
        'type'     => 'qrcode',
        'code_url' => $code_url,
        'url'      => $code_url,
        'msg'      => '请使用微信扫码支付',
    );
}

/**
 * 微信签名生成
 */
function onedown_wechat_sign($params, $key)
{
    ksort($params);
    $sign_str = '';
    foreach ($params as $k => $v) {
        if ($k !== 'sign' && $v !== '') {
            $sign_str .= $k . '=' . $v . '&';
        }
    }
    $sign_str .= 'key=' . $key;
    return strtoupper(md5($sign_str));
}

/**
 * 验证微信回调签名
 */
function onedown_wechat_verify($xml_data, $key)
{
    $result = simplexml_load_string($xml_data, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($result === false) {
        return false;
    }
    $params = array();
    foreach ($result as $k => $v) {
        $params[(string) $k] = (string) $v;
    }
    $sign = $params['sign'] ?? '';
    $cal_sign = onedown_wechat_sign($params, $key);
    return $sign === $cal_sign;
}

/**
 * 数组转 XML
 */
function onedown_array_to_xml($params)
{
    $xml = '<xml>';
    foreach ($params as $k => $v) {
        if (is_numeric($v)) {
            $xml .= "<{$k}>{$v}</{$k}>";
        } else {
            $xml .= "<{$k}><![CDATA[{$v}]]></{$k}>";
        }
    }
    $xml .= '</xml>';
    return $xml;
}

/**
 * 微信 POST XML 请求
 */
function onedown_wechat_post_xml($url, $xml)
{
    $args = array(
        'body'      => $xml,
        'headers'   => array('Content-Type' => 'text/xml'),
        'timeout'   => 10,
        'sslverify' => false,
    );
    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        return false;
    }
    return wp_remote_retrieve_body($response);
}

// ──────────────────────────────────────────────
// 5. 余额支付
// ──────────────────────────────────────────────

/**
 * 余额支付
 */
function onedown_initiate_balance($order_data)
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        return array('success' => false, 'msg' => '请先登录');
    }

    $balance = floatval(get_user_meta($user_id, 'onedown_balance', true));
    $price = floatval($order_data['order_price']);

    if ($balance < $price) {
        return array('success' => false, 'msg' => '余额不足，当前余额：￥' . number_format($balance, 2) . '，需：￥' . number_format($price, 2));
    }

    // 先扣款，确保扣款成功再标记订单
    $new_balance = $balance - $price;
    update_user_meta($user_id, 'onedown_balance', $new_balance);

    $pay_detail = array(
        'balance_before' => $balance,
        'balance_after'  => $new_balance,
    );

    $paid = onedown_mark_order_paid($order_data['order_id'], array(
        'pay_type'   => 'balance',
        'pay_detail' => $pay_detail,
    ));

    if (!$paid) {
        // 订单标记失败，回退余额
        update_user_meta($user_id, 'onedown_balance', $balance);
        return array('success' => false, 'msg' => '订单处理失败，请稍后重试');
    }

    return array(
        'success' => true,
        'type'    => 'success',
        'msg'     => '余额支付成功',
    );
}

// ──────────────────────────────────────────────
// 6. 线下支付
// ──────────────────────────────────────────────

/**
 * 线下支付（仅展示信息，等待管理员确认）
 */
function onedown_initiate_offline($order_data)
{
    $offline_info = Onedown_Pay_Config::instance()->offlineInfo();
    return array(
        'success' => true,
        'type'    => 'offline',
        'msg'     => '订单已创建，请线下联系管理员付款',
        'offline_info' => $offline_info,
    );
}

// ──────────────────────────────────────────────
// 7. 创建并发起支付 AJAX 接口
// ──────────────────────────────────────────────

/**
 * AJAX: 创建订单并发起支付
 */
add_action('wp_ajax_onedown_initiate_pay', 'onedown_ajax_initiate_pay');
add_action('wp_ajax_nopriv_onedown_initiate_pay', 'onedown_ajax_initiate_pay');
function onedown_ajax_initiate_pay()
{
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        wp_send_json_error(array('msg' => '支付功能已禁用'));
    }
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_pay_order_action')) {
        wp_send_json_error(array('msg' => '安全验证失败'));
    }

    $order_type = sanitize_text_field($_POST['order_type'] ?? '');
    $pay_method = sanitize_text_field($_POST['pay_method'] ?? '');
    $post_id = intval($_POST['post_id'] ?? 0);

    if (empty($order_type) || empty($pay_method)) {
        wp_send_json_error(array('msg' => '参数不完整'));
    }

    $user_id = get_current_user_id();
    $guest_token = '';
    $config = Onedown_Pay_Config::instance();

    if (!$user_id) {
        if (!$config->isGuestPurchaseEnabled()) {
            wp_send_json_error(array('msg' => '请先登录'));
        }
        $guest_token = isset($_COOKIE['onedown_guest_token']) ? sanitize_key($_COOKIE['onedown_guest_token']) : '';
        if (empty($guest_token)) {
            $guest_token = 'gt_' . md5(uniqid('', true) . wp_rand() . time());
            setcookie('onedown_guest_token', $guest_token, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }
    }

    $order_data = array();
    $referrer_id = 0;

    switch ($order_type) {
        case ONEDOWN_ORDER_TYPE_POST_READ:
        case ONEDOWN_ORDER_TYPE_POST_DOWNLOAD:
            $order_data = onedown_prepare_post_order($post_id, $user_id, $order_type);
            break;

        case ONEDOWN_ORDER_TYPE_VIP:
            $plan_id = sanitize_text_field($_POST['plan_id'] ?? '');
            $order_data = onedown_prepare_vip_order($user_id, $plan_id);
            break;

        case ONEDOWN_ORDER_TYPE_LICENSE:
            $order_id = sanitize_text_field($_POST['order_id'] ?? '');
            if (empty($order_id)) {
                wp_send_json_error(array('msg' => '订单号无效'));
            }
            $order_data = onedown_get_order($order_id);
            if (!$order_data || intval($order_data->user_id) !== $user_id) {
                wp_send_json_error(array('msg' => '订单不存在'));
            }
            $order_data = (array) $order_data;
            break;

        case ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE:
            $recharge_amount = floatval($_POST['recharge_amount'] ?? 0);
            if ($recharge_amount <= 0) {
                wp_send_json_error(array('msg' => '充值金额无效'));
            }
            $order_data = array(
                'order_id'    => onedown_generate_order_id('CZ'),
                'user_id'     => $user_id,
                'post_id'     => 0,
                'order_type'  => ONEDOWN_ORDER_TYPE_BALANCE_RECHARGE,
                'order_title' => '余额充值：￥' . number_format($recharge_amount, 2),
                'order_price' => $recharge_amount,
                'pay_price'   => $recharge_amount,
            );
            break;

        case ONEDOWN_ORDER_TYPE_AD:
            $ad_order_id = sanitize_text_field($_POST['ad_order_id'] ?? '');
            if (!empty($ad_order_id)) {
                // 旧流程：订单已存在
                $existing_order = onedown_get_order($ad_order_id);
                if (!$existing_order || (int) $existing_order->user_id !== $user_id) {
                    wp_send_json_error(array('msg' => '广告订单不存在'));
                }
                if ($existing_order->status !== ONEDOWN_ORDER_STATUS_PENDING) {
                    wp_send_json_error(array('msg' => '广告订单状态异常，请刷新重试'));
                }
                $order_data = (array) $existing_order;
                break;
            }
            // 新流程：支付时创建订单
            $ad_id = intval($_POST['ad_id'] ?? 0);
            if (!$ad_id) {
                wp_send_json_error(array('msg' => '广告参数缺失'));
            }
            $ad_post = get_post($ad_id);
            if (!$ad_post || $ad_post->post_type !== 'onedown_ad') {
                wp_send_json_error(array('msg' => '广告不存在'));
            }
            $ad_user_id = (int) get_post_meta($ad_id, '_ad_user_id', true);
            if ($ad_user_id !== $user_id) {
                wp_send_json_error(array('msg' => '无权操作此广告'));
            }
            if ($ad_post->post_status !== 'draft') {
                wp_send_json_error(array('msg' => '广告状态异常，请刷新重试'));
            }
            // 检查是否已有订单（防止重复创建）
            $existing_order_id = get_post_meta($ad_id, '_ad_order_id', true);
            if ($existing_order_id) {
                $existing_order = onedown_get_order($existing_order_id);
                if ($existing_order && $existing_order->status === ONEDOWN_ORDER_STATUS_PENDING) {
                    $order_data = (array) $existing_order;
                    break;
                }
            }
            $ad_price = floatval(get_post_meta($ad_id, '_ad_price', true) ?: _pz('ad_price', '19.99'));
            $order_id_val = onedown_generate_order_id('AD');
            $order_data = array(
                'order_id'    => $order_id_val,
                'user_id'     => $user_id,
                'post_id'     => $ad_id,
                'order_type'  => ONEDOWN_ORDER_TYPE_AD,
                'order_title' => __('广告投放：', 'onedown') . get_the_title($ad_id),
                'order_price' => $ad_price,
                'pay_price'   => $ad_price,
                'status'      => ONEDOWN_ORDER_STATUS_PENDING,
            );
            $insert_id = onedown_create_order($order_data);
            if (!$insert_id) {
                wp_send_json_error(array('msg' => '订单创建失败'));
            }
            // 保存订单ID到广告元数据
            update_post_meta($ad_id, '_ad_order_id', $order_id_val);
            break;

        default:
            wp_send_json_error(array('msg' => '无效的订单类型'));
    }

    if (!$order_data || !empty($order_data['error'])) {
        wp_send_json_error(array('msg' => $order_data['error'] ?? '订单数据准备失败'));
    }

    if (function_exists('onedown_get_referrer_id') && $user_id) {
        $referrer_id = onedown_get_referrer_id();
        if ($referrer_id) {
            $rebate_ratio = onedown_get_referral_rebate_ratio($referrer_id, $order_type);
            $order_data['referrer_id'] = $referrer_id;
            $order_data['rebate_price'] = $order_data['order_price'] * $rebate_ratio / 100;
        }
    }

    $order_data['user_id'] = $user_id;
    $order_data['pay_type'] = $pay_method;
    $order_data['guest_token'] = $guest_token;

    if (!isset($order_data['pay_price']) || floatval($order_data['pay_price']) <= 0) {
        $order_data['pay_price'] = floatval($order_data['order_price']);
    }

    // 如果订单已存在（如广告预创建订单），则更新而非新建
    $existing_order_check = onedown_get_order($order_data['order_id']);
    if ($existing_order_check) {
        $insert_id = $existing_order_check->id;
        onedown_update_order($order_data['order_id'], array(
            'pay_type'     => $pay_method,
            'pay_price'    => floatval($order_data['pay_price']),
            'referrer_id'  => intval($order_data['referrer_id'] ?? 0),
            'rebate_price' => floatval($order_data['rebate_price'] ?? 0),
        ));
    } else {
        $insert_id = onedown_create_order($order_data);
        if (!$insert_id) {
            wp_send_json_error(array('msg' => '订单创建失败'));
        }
    }

    $pay_result = onedown_initiate_pay($order_data, $pay_method);

    if (!$pay_result['success']) {
        onedown_update_order($order_data['order_id'], array('status' => ONEDOWN_ORDER_STATUS_CLOSED));
        wp_send_json_error(array('msg' => $pay_result['msg']));
    }

    wp_send_json_success(array(
        'order_id'    => $order_data['order_id'],
        'amount'      => $order_data['order_price'],
        'pay_type'    => $pay_result['type'],
        'pay_url'     => $pay_result['url'] ?? '',
        'code_url'    => $pay_result['code_url'] ?? '',
        'msg'         => $pay_result['msg'],
        'offline_info' => $pay_result['offline_info'] ?? '',
    ));
}

/**
 * AJAX: 重新发起支付（用于订单列表中的「继续支付」）
 */
add_action('wp_ajax_onedown_repay_order', 'onedown_ajax_repay_order');
function onedown_ajax_repay_order()
{
    if (! function_exists('onedown_pay_is_allowed') || ! onedown_pay_is_allowed()) {
        wp_send_json_error(array('msg' => '支付功能已禁用'));
    }
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'onedown_pay_order_action')) {
        wp_send_json_error(array('msg' => '安全验证失败'));
    }

    $order_id = sanitize_text_field($_POST['order_id'] ?? '');
    $pay_method = sanitize_text_field($_POST['pay_method'] ?? '');

    if (empty($order_id) || empty($pay_method)) {
        wp_send_json_error(array('msg' => '参数不完整'));
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('msg' => '请先登录'));
    }

    $order = onedown_get_order($order_id);
    if (!$order) {
        wp_send_json_error(array('msg' => '订单不存在'));
    }

    if (intval($order->user_id) !== $user_id) {
        wp_send_json_error(array('msg' => '无权操作此订单'));
    }

    if ($order->status !== ONEDOWN_ORDER_STATUS_PENDING) {
        wp_send_json_error(array('msg' => '订单状态不允许重新支付'));
    }

    // 准备订单数据用于重新发起支付
    $order_data = array(
        'order_id'    => $order->order_id,
        'user_id'     => $user_id,
        'post_id'     => intval($order->post_id),
        'order_type'  => $order->order_type,
        'order_title' => $order->order_title,
        'order_price' => floatval($order->order_price),
        'pay_price'   => floatval($order->order_price),
        'pay_type'    => $pay_method,
    );

    onedown_update_order($order_id, array(
        'pay_type'  => $pay_method,
        'pay_price' => floatval($order->order_price),
    ));

    $pay_result = onedown_initiate_pay($order_data, $pay_method);

    if (!$pay_result['success']) {
        wp_send_json_error(array('msg' => $pay_result['msg']));
    }

    wp_send_json_success(array(
        'order_id'    => $order_data['order_id'],
        'amount'      => $order_data['order_price'],
        'pay_type'    => $pay_result['type'],
        'pay_url'     => $pay_result['url'] ?? '',
        'code_url'    => $pay_result['code_url'] ?? '',
        'msg'         => $pay_result['msg'],
        'offline_info' => $pay_result['offline_info'] ?? '',
    ));
}

/**
 * 准备文章订单数据
 */
function onedown_prepare_post_order($post_id, $user_id, $order_type)
{
    $post = get_post($post_id);
    if (!$post) {
        return array('error' => '文章不存在');
    }

    $data = onedown_get_effective_pay_data($post_id);
    if (empty($data['pay_type']) || $data['pay_type'] === 'no') {
        return array('error' => '该文章未开启付费');
    }

    $buy_permission = $data['buy_permission'] ?? 'all';
    if ('vip_only' === $buy_permission && $user_id) {
        $vip_info = function_exists('onedown_get_user_vip_info') ? onedown_get_user_vip_info($user_id) : array('is_vip' => false);
        if (empty($vip_info['is_vip'])) {
            return array('error' => '该内容仅限会员购买');
        }
    } elseif ('vip_only' === $buy_permission && !$user_id) {
        return array('error' => '请先登录');
    }
    if ('logged_in' === $buy_permission && !$user_id) {
        return array('error' => '请先登录后再购买');
    }

    $price = floatval($data['pay_price']);

    $vip_prices = isset($data['pay_vip_prices']) && is_array($data['pay_vip_prices']) ? $data['pay_vip_prices'] : array();
    if (!empty($vip_prices) && $user_id) {
        $vip_info = onedown_get_user_vip_info($user_id);
        if (!empty($vip_info['is_vip'])) {
            $plan_id = $vip_info['plan_id'];
            if (isset($vip_prices[$plan_id])) {
                $v_price = floatval($vip_prices[$plan_id]);
                if ($v_price === 0.0) {
                    return array('error' => 'VIP 会员免费，无需购买');
                }
                $price = $v_price;
            }
        }
    }

    if ($user_id) {
        if (onedown_user_has_paid($post_id, $user_id)) {
            return array('error' => '您已购买过该内容');
        }
    } else {
        $cookie_key = 'onedown_paid_' . $post_id;
        if (!empty($_COOKIE[$cookie_key])) {
            return array('error' => '您已购买过该内容');
        }
    }

    $pay_type_label = $order_type === ONEDOWN_ORDER_TYPE_POST_READ ? '付费阅读' : '付费下载';

    return array(
        'order_id'    => onedown_generate_order_id('OD'),
        'post_id'     => $post_id,
        'order_type'  => $order_type,
        'order_title' => $pay_type_label . '：' . $post->post_title,
        'order_price' => $price,
        'pay_price'   => $price,
    );
}

/**
 * 准备 VIP 订单数据
 */
function onedown_prepare_vip_order($user_id, $plan_id)
{
    if (!$user_id) {
        return array('error' => '请先登录');
    }

    $levels = onedown_vip_levels();
    if (!isset($levels[$plan_id])) {
        return array('error' => '会员套餐不存在');
    }

    $plan = $levels[$plan_id];

    // 检查是否已开通会员
    if (!empty(onedown_get_user_vip_info($user_id)['is_vip'])) {
        // 已开通 -> 判断是否为升级
        if (onedown_vip_can_upgrade($user_id, $plan_id)) {
            $upgrade_price = onedown_vip_calc_upgrade_price($user_id, $plan_id);
            if ($upgrade_price <= 0) {
                return array('error' => '升级无需额外支付');
            }
            return array(
                'order_id'    => onedown_generate_order_id('VIP'),
                'post_id'     => 0,
                'order_type'  => ONEDOWN_ORDER_TYPE_VIP,
                'order_title' => '升级会员：' . $plan['name'],
                'order_price' => $upgrade_price,
                'pay_price'   => $upgrade_price,
                'pay_detail'  => wp_json_encode(array(
                    'plan_id'      => $plan_id,
                    'is_upgrade'   => true,
                    'from_plan'    => onedown_get_user_vip_info($user_id)['plan_id'],
                )),
            );
        }
        return array('error' => '您已是会员，无法重复开通');
    }

    $price = floatval($plan['price']);

    return array(
        'order_id'    => onedown_generate_order_id('VIP'),
        'post_id'     => 0,
        'order_type'  => ONEDOWN_ORDER_TYPE_VIP,
        'order_title' => '开通会员：' . $plan['name'],
        'order_price' => $price,
        'pay_price'   => $price,
        'pay_detail'  => wp_json_encode(array('plan_id' => $plan_id)),
    );
}

// ──────────────────────────────────────────────
// 8. 辅助函数
// ──────────────────────────────────────────────

/**
 * 查询微信支付订单状态
 *
 * 调用微信支付订单查询 API，确认支付是否成功
 *
 * @param string $order_id 订单号
 * @return array 查询结果
 */
function onedown_wechat_query_order($order_id)
{
    $wechatConfig = Onedown_Pay_Config::instance()->wechat();
    $app_id = $wechatConfig['app_id'];
    $mch_id = $wechatConfig['mch_id'];
    $api_key = $wechatConfig['api_key'];

    if (empty($app_id) || empty($mch_id) || empty($api_key)) {
        return array('success' => false, 'msg' => '微信支付未配置');
    }

    $params = array(
        'appid'        => $app_id,
        'mch_id'       => $mch_id,
        'out_trade_no' => $order_id,
        'nonce_str'    => wp_generate_password(16, false),
    );

    $params['sign'] = onedown_wechat_sign($params, $api_key);

    $xml = onedown_array_to_xml($params);
    $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
    $response = onedown_wechat_post_xml($url, $xml);

    if (!$response) {
        return array('success' => false, 'msg' => '查询微信订单失败：无法连接到微信服务器');
    }

    $result = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
    if ($result === false) {
        return array('success' => false, 'msg' => '查询微信订单失败：返回数据解析失败');
    }

    if ((string)$result->return_code !== 'SUCCESS') {
        return array('success' => false, 'msg' => '查询微信订单失败：' . (string)$result->return_msg);
    }

    if ((string)$result->result_code !== 'SUCCESS') {
        $err_msg = isset($result->err_code_des) ? (string)$result->err_code_des : '业务查询失败';
        return array('success' => false, 'msg' => $err_msg);
    }

    $trade_state = (string)$result->trade_state;

    if ($trade_state === 'SUCCESS') {
        return array(
            'success'       => true,
            'paid'          => true,
            'trade_state'   => $trade_state,
            'transaction_id' => (string)$result->transaction_id,
            'openid'        => (string)$result->openid,
            'total_fee'     => intval($result->total_fee),
            'pay_time'      => (string)$result->time_end,
        );
    }

    return array(
        'success'     => true,
        'paid'        => false,
        'trade_state' => $trade_state,
        'msg'         => '订单状态：' . $trade_state,
    );
}

/**
 * 获取客户端 IP
 */
function onedown_get_client_ip()
{
    $ip = '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip ?: '127.0.0.1';
}
