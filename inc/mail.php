<?php
/**
 * Onedown 邮件系统
 *
 * SMTP 配置、管理员/用户邮件通知、测试邮件
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

// ──────────────────────────────────────────────
// 1. SMTP 配置
// ──────────────────────────────────────────────

/**
 * 配置 PHPMailer 使用 SMTP
 */
add_action('phpmailer_init', 'onedown_smtp_config');
function onedown_smtp_config($phpmailer)
{
    if (! _pz('mail_smtp_enabled', false)) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = _pz('mail_smtp_host', '');
    $phpmailer->Port       = _pz('mail_smtp_port', '465');
    $phpmailer->SMTPSecure = _pz('mail_smtp_secure', 'ssl');
    $phpmailer->SMTPAuth   = (bool) _pz('mail_smtp_auth', true);
    $phpmailer->Username   = _pz('mail_smtp_username', '');
    $phpmailer->Password   = _pz('mail_smtp_password', '');
    $phpmailer->From       = _pz('mail_from_email', get_option('admin_email'));
    $phpmailer->FromName   = _pz('mail_from_name', get_bloginfo('name'));

    // 开启调试日志
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $phpmailer->SMTPDebug = 2;
        $phpmailer->Debugoutput = function ($str, $level) {
            onedown_mail_debug_log($str);
        };
    }
}

// ──────────────────────────────────────────────
// 2. 发件人设置
// ──────────────────────────────────────────────

add_filter('wp_mail_from', 'onedown_mail_from');
function onedown_mail_from($email)
{
    if (! _pz('mail_smtp_enabled', false)) {
        return $email;
    }
    $from = _pz('mail_from_email', '');
    return $from ?: $email;
}

add_filter('wp_mail_from_name', 'onedown_mail_from_name');
function onedown_mail_from_name($name)
{
    if (! _pz('mail_smtp_enabled', false)) {
        return $name;
    }
    $from_name = _pz('mail_from_name', '');
    return $from_name ?: $name;
}

// ──────────────────────────────────────────────
// 3. 邮件内容渲染
// ──────────────────────────────────────────────

/**
 * 渲染邮件模板，替换变量
 *
 * @param string $template 模板内容
 * @param array  $vars     变量键值对
 * @return string
 */
function onedown_render_mail_template($template, $vars = array())
{
    $defaults = array(
        '{site_name}' => get_bloginfo('name'),
        '{site_url}'  => home_url(),
    );
    $vars = wp_parse_args($vars, $defaults);

    return str_replace(array_keys($vars), array_values($vars), $template);
}

/**
 * 发送邮件（封装 wp_mail，自动处理模板渲染）
 *
 * @param string $to      收件人
 * @param string $subject 邮件主题
 * @param string $message 邮件内容（支持模板变量）
 * @param array  $vars    模板变量
 * @return bool
 */
function onedown_send_mail($to, $subject, $message, $vars = array())
{
    $message = onedown_render_mail_template($message, $vars);
    $subject = onedown_render_mail_template($subject, $vars);

    $headers = array('Content-Type: text/html; charset=UTF-8');

    // 构建 HTML 邮件
    $html_message = onedown_mail_html_wrapper($subject, $message);

    $result = wp_mail($to, $subject, $html_message, $headers);

    if (!$result) {
        onedown_mail_debug_log(
            sprintf('邮件发送失败 -> 收件人: %s, 主题: %s', $to, $subject)
        );
    }

    return $result;
}

/**
 * 邮件 HTML 包装
 *
 * @param string $subject 邮件主题
 * @param string $content 邮件正文
 * @return string
 */
function onedown_mail_html_wrapper($subject, $content)
{
    $site_name = get_bloginfo('name');
    $logo      = _pz('logo', '');
    $logo_url  = '';
    if (! empty($logo) && is_array($logo)) {
        $logo_url = $logo['url'] ?? '';
    }

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style type="text/css">
            body,table,td,p,a,li,blockquote{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
            body{margin:0;padding:0;background:#f4f6f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif}
            .mail-wrapper{max-width:600px;margin:0 auto;padding:30px 20px}
            .mail-header{text-align:center;padding:30px 20px 20px;background:#fff;border-radius:12px 12px 0 0}
            .mail-header img{max-height:50px;margin-bottom:10px;border:0;outline:none;text-decoration:none}
            .mail-header h2{margin:0;color:#1a1a2e;font-size:20px;font-weight:600}
            .mail-body{background:#fff;padding:0 30px 30px;border-radius:0 0 12px 12px}
            .mail-body p{color:#555;line-height:1.8;font-size:14px;margin:0 0 15px}
            .mail-body .content-box{background:#f8f9fc;border-left:4px solid #f04494;padding:15px 20px;margin:15px 0;border-radius:4px;color:#333}
            .mail-footer{text-align:center;padding:20px;color:#999;font-size:12px}
            .mail-footer a{color:#f04494;text-decoration:none}
            .btn{display:inline-block;padding:10px 25px;background:#f04494;color:#fff!important;text-decoration:none;border-radius:6px;font-size:14px;margin:10px 0}
            @media only screen and (max-width:480px){
                .mail-wrapper{padding:15px 10px!important}
                .mail-body{padding:0 15px 20px!important}
                .mail-header{padding:20px 15px 15px!important}
            }
        </style>
    </head>
    <body>
        <div class="mail-wrapper">
            <div class="mail-header">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>">
                <?php endif; ?>
                <h2><?php echo esc_html($subject); ?></h2>
            </div>
            <div class="mail-body">
                <?php echo wpautop(wp_kses_post($content)); ?>
            </div>
            <div class="mail-footer">
                <p>&copy; <?php echo date('Y'); ?> <a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_html($site_name); ?></a>
                <?php esc_html_e(' - 版权所有', 'onedown'); ?></p>
                <p style="margin-top:5px;font-size:11px;color:#bbb">
                    <?php esc_html_e('此邮件由系统自动发送，请勿回复。', 'onedown'); ?>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

// ──────────────────────────────────────────────
// 4. 邮件调试日志
// ──────────────────────────────────────────────

/**
 * 记录邮件调试日志
 *
 * @param string $message 日志信息
 */
function onedown_mail_debug_log($message)
{
    if (! defined('WP_DEBUG') || ! WP_DEBUG) {
        return;
    }

    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }

    error_log('[Onedown Mail] ' . $message);
}

// ──────────────────────────────────────────────
// 5. 管理员通知
// ──────────────────────────────────────────────

/**
 * 新订单通知管理员
 *
 * @param string $order_id 订单号
 * @param object $order    订单对象
 */
add_action('onedown_payment_success', 'onedown_notify_admin_new_order', 10, 2);
function onedown_notify_admin_new_order($order_id, $order)
{
    if (! _pz('mail_admin_new_order', false)) {
        return;
    }

    $admin_email = get_option('admin_email');
    $template    = _pz('mail_template_new_order', '新订单通知：用户 {user_name} 已成功下单，订单号 {order_id}，金额 {order_price} 元。');

    $user = $order->user_id ? get_userdata($order->user_id) : null;

    $vars = array(
        '{order_id}'    => $order->order_id,
        '{order_title}' => $order->order_title,
        '{order_price}' => $order->order_price,
        '{user_name}'   => $user ? $user->display_name : __('游客', 'onedown'),
    );

    onedown_send_mail($admin_email, sprintf(__('[%s] 新订单通知', 'onedown'), get_bloginfo('name')), $template, $vars);
}

/**
 * 新评论通知管理员
 */
add_action('wp_insert_comment', 'onedown_notify_admin_new_comment', 99, 2);
function onedown_notify_admin_new_comment($comment_id, $comment)
{
    if (! _pz('mail_admin_new_comment', false)) {
        return;
    }

    // 不通知垃圾评论
    if (wp_get_comment_status($comment_id) === 'spam') {
        return;
    }

    // 如果是管理员自己的评论，不通知
    $post_author_id = (int) get_post_field('post_author', $comment->comment_post_ID);
    if ((int) $comment->user_id === $post_author_id) {
        return;
    }

    $admin_email = get_option('admin_email');
    $template    = _pz('mail_template_admin_comment', __('用户 {comment_author} 在文章《{post_title}》中发表评论：{comment_content}', 'onedown'));
    $post_title  = get_the_title($comment->comment_post_ID);

    $vars = array(
        '{comment_author}'  => $comment->comment_author,
        '{comment_content}' => wp_trim_words($comment->comment_content, 100),
        '{post_title}'      => $post_title,
    );

    onedown_send_mail($admin_email, sprintf(__('[%s] 新评论通知', 'onedown'), get_bloginfo('name')), $template, $vars);
}

/**
 * 待审核内容通知管理员
 */
add_action('transition_post_status', 'onedown_notify_admin_pending_review', 10, 3);
function onedown_notify_admin_pending_review($new_status, $old_status, $post)
{
    if (! _pz('mail_admin_content_review', false)) {
        return;
    }

    // 只发送一次：从非 pending 变为 pending
    if ($new_status !== 'pending' || $old_status === 'pending') {
        return;
    }

    // 排除自动草稿
    if ($post->post_status === 'auto-draft') {
        return;
    }

    $admin_email = get_option('admin_email');
    $template    = _pz('mail_template_admin_review', __('有新的待审核内容：《{post_title}》，作者：{author_name}，类型：{post_type}。请前往后台审核。', 'onedown'));
    $author      = get_userdata($post->post_author);

    $vars = array(
        '{post_title}'  => $post->post_title,
        '{author_name}' => $author ? $author->display_name : __('未知', 'onedown'),
        '{post_type}'   => get_post_type_object($post->post_type)->labels->singular_name ?? $post->post_type,
    );

    onedown_send_mail($admin_email, sprintf(__('[%s] 内容审核通知', 'onedown'), get_bloginfo('name')), $template, $vars);
}

// ──────────────────────────────────────────────
// 6. 用户通知
// ──────────────────────────────────────────────

/**
 * 评论审核结果通知用户
 */
add_action('comment_unapproved_to_approved', 'onedown_notify_user_comment_approved');
function onedown_notify_user_comment_approved($comment)
{
    if (! _pz('mail_user_comment_review', false)) {
        return;
    }

    if (empty($comment->comment_author_email) || ! is_email($comment->comment_author_email)) {
        return;
    }

    $template = _pz('mail_template_comment_approve', '您好 {comment_author}，您在文章《{post_title}》的评论已{status}。');

    $vars = array(
        '{comment_author}'  => $comment->comment_author,
        '{comment_content}' => wp_trim_words($comment->comment_content, 50),
        '{post_title}'      => get_the_title($comment->comment_post_ID),
        '{status}'          => __('审核通过', 'onedown'),
    );

    onedown_send_mail(
        $comment->comment_author_email,
        sprintf(__('[%s] 评论审核通过', 'onedown'), get_bloginfo('name')),
        $template,
        $vars
    );
}

/**
 * 评论回复通知用户
 */
add_action('wp_insert_comment', 'onedown_notify_user_comment_reply', 99, 2);
function onedown_notify_user_comment_reply($comment_id, $comment)
{
    if (! _pz('mail_user_comment_reply', false)) {
        return;
    }

    // 只处理有父级评论的回复
    if (! $comment->comment_parent) {
        return;
    }

    $parent_comment = get_comment($comment->comment_parent);
    if (! $parent_comment || empty($parent_comment->comment_author_email)) {
        return;
    }

    // 不通知自己回复自己
    if ($parent_comment->comment_author_email === $comment->comment_author_email) {
        return;
    }

    $template = _pz('mail_template_comment_reply', '您好 {comment_author}，用户 {reply_author} 回复了您在文章《{post_title}》中的评论。');

    $vars = array(
        '{comment_author}' => $parent_comment->comment_author,
        '{reply_author}'   => $comment->comment_author,
        '{reply_content}'  => wp_trim_words($comment->comment_content, 50),
        '{post_title}'     => get_the_title($comment->comment_post_ID),
    );

    onedown_send_mail(
        $parent_comment->comment_author_email,
        sprintf(__('[%s] 评论回复通知', 'onedown'), get_bloginfo('name')),
        $template,
        $vars
    );
}

/**
 * 用户内容审核结果通知
 *
 * 当用户文章从 pending 变为 published（通过）或 trash（未通过）时通知作者
 */
add_action('transition_post_status', 'onedown_notify_user_content_review', 10, 3);
function onedown_notify_user_content_review($new_status, $old_status, $post)
{
    if (! _pz('mail_user_content_review', false)) {
        return;
    }

    // 只处理从 pending 状态的变化
    if ($old_status !== 'pending') {
        return;
    }

    // 只处理文章或页面类型
    if (! in_array($post->post_type, array('post', 'page'), true)) {
        return;
    }

    $author = get_userdata($post->post_author);
    if (! $author || empty($author->user_email)) {
        return;
    }

    // 通过
    if ($new_status === 'publish') {
        $subject = sprintf(__('[%s] 内容审核通过', 'onedown'), get_bloginfo('name'));
        $message = sprintf(
            /* translators: 1: post title, 2: site name */
            __('您好！您提交的内容《%1$s》已审核通过并发布，感谢您的贡献！<br><br>查看链接：<a href="%2$s">%1$s</a>', 'onedown'),
            $post->post_title,
            get_permalink($post->ID)
        );

        onedown_send_mail($author->user_email, $subject, $message);
        return;
    }

    // 未通过（被退回或删除）
    if (in_array($new_status, array('draft', 'trash'), true)) {
        $subject = sprintf(__('[%s] 内容审核未通过', 'onedown'), get_bloginfo('name'));
        $message = sprintf(
            /* translators: 1: post title */
            __('您好！您提交的内容《%1$s》未通过审核，请检查修改后重新提交。<br><br>如有疑问请联系管理员。', 'onedown'),
            $post->post_title
        );

        onedown_send_mail($author->user_email, $subject, $message);
    }
}

// ──────────────────────────────────────────────
// 7. 测试邮件
// ──────────────────────────────────────────────

/**
 * 后台测试邮件按钮渲染
 */
function onedown_mail_test_button()
{
    $nonce = wp_create_nonce('onedown_test_mail_nonce');
    ?>
    <div class="csf-fieldset" style="padding-top:10px">
        <button type="button" class="button button-primary" id="onedown-test-mail-btn"
                onclick="onedownSendTestMail('<?php echo esc_js($nonce); ?>')">
            <?php esc_html_e('发送测试邮件', 'onedown'); ?>
        </button>
        <span id="onedown-test-mail-result" style="margin-left:10px;font-weight:600"></span>
        <p class="csf-text-desc" style="margin-top:8px;color:#888">
            <?php esc_html_e('点击发送测试邮件到上方填写的测试邮箱地址，以验证 SMTP 配置是否正确。', 'onedown'); ?>
        </p>
    </div>
    <script type="text/javascript">
    function onedownSendTestMail(nonce) {
        var btn = document.getElementById('onedown-test-mail-btn');
        var result = document.getElementById('onedown-test-mail-result');
        var testEmail = document.querySelector('[data-depend-id="mail_test_email"] input[type="text"]');

        if (!testEmail || !testEmail.value.trim()) {
            result.innerHTML = '<span style="color:#e74c3c;">请先填写测试邮箱地址</span>';
            return;
        }

        btn.disabled = true;
        result.innerHTML = '<span style="color:#888;">发送中...</span>';

        var data = new FormData();
        data.append('action', 'onedown_test_mail');
        data.append('test_email', testEmail.value.trim());
        data.append('_ajax_nonce', nonce);

        fetch(ajaxurl, {
            method: 'POST',
            body: data
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            result.innerHTML = res.success
                ? '<span style="color:#27ae60;">' + res.data + '</span>'
                : '<span style="color:#e74c3c;">' + res.data + '</span>';
        })
        .catch(function() {
            result.innerHTML = '<span style="color:#e74c3c;">请求失败，请重试</span>';
        })
        .finally(function() {
            btn.disabled = false;
        });
    }
    </script>
    <?php
}

/**
 * AJAX 处理测试邮件发送
 */
add_action('wp_ajax_onedown_test_mail', 'onedown_ajax_test_mail');
function onedown_ajax_test_mail()
{
    check_ajax_referer('onedown_test_mail_nonce');

    $test_email = isset($_POST['test_email']) ? sanitize_email(wp_unslash($_POST['test_email'])) : '';
    if (! is_email($test_email)) {
        wp_send_json_error(__('请输入有效的邮箱地址', 'onedown'));
    }

    $subject = sprintf(__('[%s] 测试邮件', 'onedown'), get_bloginfo('name'));
    $message = sprintf(
        /* translators: 1: site name, 2: current time */
        __('这是一封来自 %1$s 的测试邮件。<br><br>如果收到此邮件，说明您的 SMTP 配置正确，邮件系统工作正常。<br><br>发送时间：%2$s', 'onedown'),
        get_bloginfo('name'),
        current_time('Y-m-d H:i:s')
    );

    $result = onedown_send_mail($test_email, $subject, $message);

    if ($result) {
        wp_send_json_success(__('测试邮件发送成功！请检查收件箱。', 'onedown'));
    } else {
        wp_send_json_error(__('测试邮件发送失败，请检查 SMTP 配置。', 'onedown'));
    }
}
