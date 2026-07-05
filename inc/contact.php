<?php
/**
 * Onedown 联系我们表单处理
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * AJAX 处理联系表单提交
 */
add_action('wp_ajax_onedown_contact_submit', 'onedown_ajax_contact_submit');
add_action('wp_ajax_nopriv_onedown_contact_submit', 'onedown_ajax_contact_submit');
function onedown_ajax_contact_submit()
{
    check_ajax_referer('onedown_contact_action', 'contact_nonce');

    // 速率限制
    $rate_check = onedown_check_rate_limit('contact', 3, 10);
    if (true !== $rate_check) {
        wp_send_json_error(array('msg' => $rate_check));
    }

    $name    = isset($_POST['contact_name']) ? sanitize_text_field(wp_unslash($_POST['contact_name'])) : '';
    $email   = isset($_POST['contact_email']) ? sanitize_email(wp_unslash($_POST['contact_email'])) : '';
    $subject = isset($_POST['contact_subject']) ? sanitize_text_field(wp_unslash($_POST['contact_subject'])) : '';
    $message = isset($_POST['contact_message']) ? sanitize_textarea_field(wp_unslash($_POST['contact_message'])) : '';

    // 验证必填字段
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        wp_send_json_error(array('msg' => '请填写所有必填字段'));
    }

    if (! is_email($email)) {
        wp_send_json_error(array('msg' => '请输入有效的邮箱地址'));
    }

    // 构建邮件内容
    $admin_email = get_option('admin_email');
    $site_name   = get_bloginfo('name');

    $mail_subject = sprintf('[%s] 新联系消息：%s', $site_name, $subject);
    $mail_message = sprintf(
        "您收到了一条来自 %s 联系表单的新消息：\n\n姓名：%s\n邮箱：%s\nIP：%s\n\n消息内容：\n%s",
        $site_name,
        $name,
        $email,
        isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '未知',
        $message
    );

    $result = onedown_send_mail($admin_email, $mail_subject, $mail_message);

    if ($result) {
        onedown_clear_rate_limit('contact');
        wp_send_json_success(array('msg' => '消息发送成功！我们会尽快回复您。'));
    } else {
        wp_send_json_error(array('msg' => '消息发送失败，请稍后重试或直接发送邮件至：' . $admin_email));
    }
}
