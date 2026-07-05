<?php
/**
 * Template Name: 投稿
 *
 * 用户前端投稿/发布文章页面模板
 *
 * @package onedown
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$current_user = wp_get_current_user();
$user_id      = get_current_user_id();

// 未登录跳转
if (! $user_id) :
?>
    <main>
        <h1 class="sr-only"><?php _e('请先登录', 'onedown'); ?></h1>
        <section class="content-shell" style="margin-top:22px;">
            <div class="main-column">
                <div class="section-card" style="padding:80px 40px;text-align:center;">
                    <i class="fa fa-pencil" style="font-size:80px;display:block;margin:0 auto 20px;color:var(--od-muted);"></i>
                    <h2 style="margin:0 0 12px;color:#252c3a;font-weight:800;">请先登录</h2>
                    <p style="margin:0 0 24px;color:var(--od-muted);">登录后可发布文章</p>
                    <a class="pill-btn" href="<?php echo esc_url(wp_login_url(get_permalink())); ?>"><i class="fa fa-sign-in"></i> 立即登录</a>
                </div>
            </div>
        </section>
    </main>
<?php
    get_footer();
    return;
endif;

// 检查发布权限
if (! current_user_can('publish_posts') && ! current_user_can('manage_options')) :
?>
    <main>
        <h1 class="sr-only"><?php _e('暂无发布权限', 'onedown'); ?></h1>
        <section class="content-shell" style="margin-top:22px;">
            <div class="main-column">
                <div class="section-card" style="padding:80px 40px;text-align:center;">
                    <i class="fa fa-lock" style="font-size:80px;display:block;margin:0 auto 20px;color:var(--od-muted);"></i>
                    <h2 style="margin:0 0 12px;color:#252c3a;font-weight:800;">暂无发布权限</h2>
                    <p style="margin:0 0 24px;color:var(--od-muted);">您的账号暂未获得发布文章的权限</p>
                    <a class="pill-btn" href="<?php echo esc_url(home_url('/')); ?>"><i class="fa fa-home"></i> 返回首页</a>
                </div>
            </div>
        </section>
    </main>
<?php
    get_footer();
    return;
endif;

// 获取分类列表
$categories = get_categories(array(
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
));

// AJAX Nonce
$submit_nonce = wp_create_nonce('onedown_submit_post_action');
?>
<style>
    .submit-post-wrap {
        max-width: 860px;
        margin: 0 auto;
        padding: 0 16px;
    }
    .submit-post-wrap .section-card {
        padding: 28px 32px;
    }
    .submit-post-wrap .section-card + .section-card {
        margin-top: 16px;
    }
    .submit-post-field {
        margin-bottom: 20px;
    }
    .submit-post-field:last-child {
        margin-bottom: 0;
    }
    .submit-post-field label {
        display: block;
        font-size: 13px;
        font-weight: 800;
        color: #252c3a;
        margin-bottom: 6px;
    }
    .submit-post-field input[type="text"],
    .submit-post-field select,
    .submit-post-field textarea {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--od-line);
        border-radius: 8px;
        background: #fff;
        font-size: 14px;
        color: #252c3a;
        transition: border-color .25s ease, box-shadow .25s ease;
        box-sizing: border-box;
    }
    .submit-post-field input[type="text"]:focus,
    .submit-post-field select:focus,
    .submit-post-field textarea:focus {
        border-color: var(--od-primary);
        box-shadow: 0 0 0 3px rgba(var(--od-primary-rgb), .1);
        outline: none;
    }
    .submit-post-field textarea {
        resize: vertical;
        min-height: 80px;
    }
    .submit-post-field .hint {
        font-size: 11px;
        color: var(--od-muted);
        margin-top: 4px;
    }
    .submit-post-editor-wrap .wp-editor-tabs {
        display: flex;
        gap: 4px;
    }
    .submit-post-editor-wrap .wp-editor-tabs button {
        border: 1px solid var(--od-line);
        border-bottom: 0;
        border-radius: 6px 6px 0 0;
        background: #f5f6f8;
        padding: 6px 14px;
        font-size: 12px;
        cursor: pointer;
    }
    .submit-post-editor-wrap .wp-editor-tabs .active {
        background: #fff;
    }
    .submit-post-editor-wrap .mce-tinymce {
        border: 1px solid var(--od-line) !important;
        border-radius: 0 0 8px 8px;
    }
    .submit-post-editor-wrap .mce-top-part::before {
        box-shadow: none !important;
    }
    .submit-post-submit {
        text-align: center;
        padding: 24px 32px 28px;
    }
    .submit-post-submit .pill-btn {
        min-height: 42px;
        padding: 0 32px;
        font-size: 15px;
        cursor: pointer;
        border: 0;
    }
    .submit-post-submit .pill-btn:disabled {
        opacity: .5;
        cursor: not-allowed;
    }
    .submit-post-message {
        display: none;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 13px;
        font-weight: 700;
    }
    .submit-post-message.is-show {
        display: block;
    }
    .submit-post-message.success {
        background: rgba(76, 175, 80, .1);
        color: #2e7d32;
        border: 1px solid rgba(76, 175, 80, .2);
    }
    .submit-post-message.error {
        background: rgba(220, 53, 69, .1);
        color: #c62828;
        border: 1px solid rgba(220, 53, 69, .2);
    }
    @media (max-width: 680px) {
        .submit-post-wrap .section-card {
            padding: 20px 16px;
        }
        .submit-post-submit {
            padding: 20px 16px 24px;
        }
    }
</style>

<main>
    <h1 class="sr-only"><?php _e('发布文章', 'onedown'); ?></h1>
    <section class="content-shell" style="margin-top:22px;">
        <div class="main-column">
            <div class="submit-post-wrap">
                <div class="section-card" style="padding:20px 32px 0;">
                    <h2 style="margin:0 0 4px;font-size:18px;font-weight:800;color:#252c3a;">发布文章</h2>
                    <p style="margin:0 0 0;font-size:12px;color:var(--od-muted);">填写以下信息提交文章，审核通过后将发布到网站</p>
                </div>

                <div class="section-card" id="submitPostCard">
                    <div class="submit-post-message" id="submitPostMessage"></div>

                    <form id="submitPostForm">
                        <div class="submit-post-field">
                            <label for="postTitle">文章标题</label>
                            <input type="text" id="postTitle" name="post_title" placeholder="请输入文章标题" required maxlength="50">
                            <div class="hint">标题建议 5-30 个字</div>
                        </div>

                        <div class="submit-post-field">
                            <label for="postCategory">文章分类</label>
                            <select id="postCategory" name="category" required>
                                <option value="">请选择分类</option>
                                <?php foreach ($categories as $cat) : ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="submit-post-field">
                            <label for="postTags">文章标签</label>
                            <input type="text" id="postTags" name="tags" placeholder="多个标签用逗号隔开，如：wordpress, 主题, 教程">
                            <div class="hint">多个标签用英文逗号隔开</div>
                        </div>

                        <div class="submit-post-field submit-post-editor-wrap">
                            <label>文章内容</label>
                            <?php
                            wp_editor('', 'post_content', array(
                                'textarea_rows'  => 16,
                                'media_buttons'  => false,
                                'quicktags'      => false,
                                'teeny'          => true,
                                'editor_height'  => 360,
                            ));
                            ?>
                        </div>
                    </form>
                </div>

                <div class="section-card submit-post-submit">
                    <div id="submitPostMessageBottom" class="submit-post-message"></div>
                    <button type="button" class="pill-btn" id="submitPostBtn"><i class="fa fa-check"></i> 提交审核</button>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
(function() {
    var btn = document.getElementById('submitPostBtn');
    var form = document.getElementById('submitPostForm');
    var msg = document.getElementById('submitPostMessage');
    var msgBottom = document.getElementById('submitPostMessageBottom');

    function showMessage(el, text, type) {
        el.textContent = text;
        el.className = 'submit-post-message is-show ' + type;
    }

    function clearMessages() {
        msg.className = 'submit-post-message';
        msg.textContent = '';
        msgBottom.className = 'submit-post-message';
        msgBottom.textContent = '';
    }

    btn.addEventListener('click', function() {
        clearMessages();

        var title = document.getElementById('postTitle').value.trim();
        var category = document.getElementById('postCategory').value;
        var content;

        // 从 TinyMCE 或 textarea 获取内容
        if (typeof tinymce !== 'undefined' && tinymce.get('post_content')) {
            content = tinymce.get('post_content').getContent();
        } else {
            content = document.getElementById('post_content').value || '';
        }

        if (!title) {
            showMessage(msg, '请填写文章标题', 'error');
            return;
        }
        if (!category) {
            showMessage(msg, '请选择文章分类', 'error');
            return;
        }
        if (!content || content === '<p><br data-mce-bogus="1"></p>') {
            showMessage(msg, '请填写文章内容', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 提交中...';

        var data = new FormData();
        data.append('action', 'onedown_submit_post');
        data.append('_wpnonce', '<?php echo $submit_nonce; ?>');
        data.append('post_title', title);
        data.append('category', category);
        data.append('tags', document.getElementById('postTags').value.trim());
        data.append('post_content', content);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: data
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                showMessage(msg, res.data.msg || '文章已提交，等待审核', 'success');
                btn.innerHTML = '<i class="fa fa-check"></i> 已提交';
                form.reset();
                if (typeof tinymce !== 'undefined' && tinymce.get('post_content')) {
                    tinymce.get('post_content').setContent('');
                }
            } else {
                showMessage(msg, res.data.msg || '提交失败，请重试', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-check"></i> 提交审核';
            }
        })
        .catch(function() {
            showMessage(msg, '网络错误，请重试', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-check"></i> 提交审核';
        });
    });
})();
</script>

<?php get_footer(); ?>