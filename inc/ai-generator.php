<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 在文章编辑页面添加 AI 生成 Meta Box
 */
add_action( 'add_meta_boxes', 'onedown_ai_add_meta_box' );
function onedown_ai_add_meta_box() {
    // 总开关检测
    if ( ! _pz( 'ai_enabled', true ) ) {
        return;
    }

    add_meta_box(
        'onedown_ai_generator',
        __( 'AI 生成', 'onedown' ),
        'onedown_ai_meta_box_callback',
        'post',
        'side',
        'high'
    );
}

/**
 * Meta Box 回调 - 显示 AI 生成按钮
 */
function onedown_ai_meta_box_callback( $post ) {
    wp_nonce_field( 'onedown_ai_generate', 'onedown_ai_nonce' );
    ?>
    <div class="onedown-ai-generator">
        <p style="margin-top:0;color:#666;">选择要生成的内容：</p>
        <div class="onedown-ai-buttons" style="display:flex;flex-direction:column;gap:8px;">
            <?php if ( _pz( 'ai_enable_title', true ) ) : ?>
            <button type="button" class="button button-primary" data-action="generate_title" style="justify-content:center;">
                <span class="dashicons dashicons-edit" style="margin-right:4px;"></span> 生成标题
            </button>
            <?php endif; ?>
            <?php if ( _pz( 'ai_enable_content', true ) ) : ?>
            <button type="button" class="button" data-action="generate_content" style="justify-content:center;">
                <span class="dashicons dashicons-text" style="margin-right:4px;"></span> 生成内容
            </button>
            <?php endif; ?>
            <?php if ( _pz( 'ai_enable_tags', true ) ) : ?>
            <button type="button" class="button" data-action="generate_tags" style="justify-content:center;">
                <span class="dashicons dashicons-tag" style="margin-right:4px;"></span> 生成标签
            </button>
            <?php endif; ?>
            <?php if ( _pz( 'ai_enable_image', true ) ) : ?>
            <button type="button" class="button" data-action="generate_image" style="justify-content:center;">
                <span class="dashicons dashicons-format-image" style="margin-right:4px;"></span> 生成特色图片
            </button>
            <?php endif; ?>
        </div>
        <div class="onedown-ai-result" style="margin-top:10px;display:none;">
            <textarea rows="4" class="large-text" readonly style="font-size:12px;"></textarea>
        </div>
        <div class="onedown-ai-loading" style="display:none;margin-top:10px;text-align:center;">
            <span class="spinner is-active" style="float:none;"></span>
            <span style="font-size:12px;color:#999;">AI 生成中，请稍候...</span>
        </div>
    </div>
    <script>
    (function($) {
        $('#onedown_ai_generator').on('click', '.onedown-ai-buttons .button', function() {
            var $btn = $(this);
            var action = $btn.data('action');
            var $result = $('.onedown-ai-result');
            var $textarea = $result.find('textarea');
            var $loading = $('.onedown-ai-loading');
            var postId = $('#post_ID').val();
            var currentTitle = onedownAIGetTitle();
            var currentContent = onedownAIGetContent();

            $result.hide();
            $loading.show();
            $btn.prop('disabled', true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'onedown_ai_generate',
                    nonce: $('#onedown_ai_nonce').val(),
                    generate_action: action,
                    post_id: postId,
                    current_title: currentTitle,
                    current_content: currentContent
                },
                success: function(response) {
                    $loading.hide();
                    $btn.prop('disabled', false);
                    if (response.success) {
                        if (action === 'generate_title') {
                            onedownAISetTitle(response.data);
                        } else if (action === 'generate_content') {
                            onedownAISetContent(response.data);
                        } else if (action === 'generate_tags') {
                            var tags = response.data;
                            if (typeof tagBox !== 'undefined') {
                                $('#new-tag-post_tag').val(tags.replace(/，/g, ','));
                                tagBox.flushTags('post_tag');
                            } else if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                                onedownAISetBlockEditorTags(tags);
                            }
                        } else if (action === 'generate_image') {
                            $textarea.val(response.data.message);
                            $result.show();
                            // 自动设置为特色图片
                            if (response.data.attachment_id) {
                                if (typeof wp !== 'undefined' && wp.media && wp.media.featuredImage) {
                                    wp.media.featuredImage.set(response.data.attachment_id);
                                }
                            }
                        }
                    } else {
                        $textarea.val('生成失败：' + response.data);
                        $result.show();
                    }
                },
                error: function(xhr, status, error) {
                    $loading.hide();
                    $btn.prop('disabled', false);
                    $textarea.val('请求失败：' + error);
                    $result.show();
                }
            });
        });

        function onedownAIGetTitle() {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                return wp.data.select('core/editor').getEditedPostAttribute('title') || '';
            }
            return $('#title').val() || '';
        }

        function onedownAISetTitle(title) {
            if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                wp.data.dispatch('core/editor').editPost({ title: title });
                return;
            }
            $('#title').val(title);
        }

        function onedownAIGetContent() {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                return wp.data.select('core/editor').getEditedPostAttribute('content') || '';
            }
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content') && !tinyMCE.get('content').isHidden()) {
                return tinyMCE.get('content').getContent();
            }
            return $('#content').val() || '';
        }

        function onedownAISetContent(content) {
            if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/editor')) {
                wp.data.dispatch('core/editor').editPost({ content: content });
                return;
            }
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                tinyMCE.get('content').setContent(content);
            } else {
                $('#content').val(content);
            }
        }

        function onedownAISetBlockEditorTags(tags) {
            var tagNames = tags.replace(/，/g, ',').split(',').map(function(tag) {
                return tag.trim();
            }).filter(Boolean).slice(0, 5);

            if (!tagNames.length || typeof wp.apiFetch === 'undefined') {
                return;
            }

            Promise.all(tagNames.map(function(name) {
                return wp.apiFetch({ path: '/wp/v2/tags?search=' + encodeURIComponent(name) }).then(function(list) {
                    var found = list && list.find ? list.find(function(item) { return item.name === name; }) : null;
                    if (found) {
                        return found.id;
                    }
                    return wp.apiFetch({
                        path: '/wp/v2/tags',
                        method: 'POST',
                        data: { name: name }
                    }).then(function(created) {
                        return created.id;
                    });
                });
            })).then(function(ids) {
                wp.data.dispatch('core/editor').editPost({ tags: ids.filter(Boolean) });
            });
        }
    })(jQuery);
    </script>
    <style>
    .onedown-ai-buttons .button {
        display: flex !important;
        align-items: center;
        width: 100%;
        text-align: left;
    }
    .onedown-ai-buttons .button .dashicons {
        font-size: 16px;
        width: 16px;
        height: 16px;
    }
    </style>
    <?php
}

/**
 * AJAX 处理 AI 生成请求
 */
add_action( 'wp_ajax_onedown_ai_generate', 'onedown_ai_handle_generate' );
function onedown_ai_handle_generate() {
    if ( ! wp_verify_nonce( $_POST['nonce'], 'onedown_ai_generate' ) ) {
        wp_send_json_error( '安全验证失败' );
    }

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( '权限不足' );
    }

    $action       = sanitize_text_field( $_POST['generate_action'] );
    $post_id      = intval( $_POST['post_id'] );
    $current_title = isset( $_POST['current_title'] ) ? sanitize_text_field( wp_unslash( $_POST['current_title'] ) ) : '';
    $current_content = isset( $_POST['current_content'] ) ? wp_kses_post( wp_unslash( $_POST['current_content'] ) ) : '';
    $post_title   = $current_title ?: ( $post_id ? get_the_title( $post_id ) : '' );

    // 后端开关校验
    if ( ! _pz( 'ai_enabled', true ) ) {
        wp_send_json_error( 'AI 功能已关闭' );
    }

    $action_switch_map = array(
        'generate_title'   => 'ai_enable_title',
        'generate_content' => 'ai_enable_content',
        'generate_tags'    => 'ai_enable_tags',
        'generate_image'   => 'ai_enable_image',
    );

    if ( isset( $action_switch_map[ $action ] ) && ! _pz( $action_switch_map[ $action ], true ) ) {
        wp_send_json_error( '该 AI 生成功能已被管理员关闭' );
    }

    switch ( $action ) {
        case 'generate_title':
            $result = onedown_ai_generate_title( $post_title, $current_content );
            break;
        case 'generate_content':
            $result = onedown_ai_generate_content( $post_title );
            break;
        case 'generate_tags':
            $result = onedown_ai_generate_tags( $post_title, $current_content );
            break;
        case 'generate_image':
            $result = onedown_ai_generate_image( $post_title, $post_id );
            break;
        default:
            wp_send_json_error( '未知操作' );
    }

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    if ( $action === 'generate_image' ) {
        wp_send_json_success( array(
            'message'       => $result['message'],
            'attachment_id' => $result['attachment_id'] ?? 0,
        ) );
    } else {
        wp_send_json_success( $result );
    }
}

/**
 * 调用 AI API（兼容 OpenAI 格式）
 *
 * @param string $api_url    API 端点
 * @param string $api_key    API Key
 * @param string $model      模型名称
 * @param string $prompt     用户提示词
 * @param string $system_msg 系统消息
 * @return string|WP_Error
 */
function onedown_ai_call_api( $api_url, $api_key, $model, $prompt, $system_msg = '你是一个专业的文章助手。' ) {
    $body = array(
        'model'       => $model,
        'messages'    => array(
            array( 'role' => 'system', 'content' => $system_msg ),
            array( 'role' => 'user',   'content' => $prompt ),
        ),
        'temperature' => 0.7,
        'max_tokens'  => 4096,
    );

    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body'    => wp_json_encode( $body ),
        'timeout' => 120,
    );

    $response = wp_remote_post( $api_url, $args );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $status_code !== 200 ) {
        $error_msg = isset( $body['error']['message'] ) ? $body['error']['message'] : 'API 请求失败，状态码：' . $status_code;
        return new WP_Error( 'api_error', $error_msg );
    }

    if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
        return new WP_Error( 'api_error', 'API 返回格式异常' );
    }

    return trim( $body['choices'][0]['message']['content'] );
}

/**
 * AI 生成标题
 */
function onedown_ai_generate_title( $post_title, $post_content = '' ) {
    $api_url  = _pz( 'ai_article_api' );
    $api_key  = _pz( 'ai_article_api_key' );
    $model    = _pz( 'ai_article_model' );
    $prompt   = _pz( 'ai_title_prompt', '请基于当前文章标题"{title}"，生成一个更符合SEO的中文文章标题。要求：保留核心关键词，标题自然吸引点击，长度控制在18-32个汉字，不要输出解释，只输出标题。' );

    if ( empty( $api_url ) || empty( $api_key ) ) {
        return new WP_Error( 'config_error', '请先在主题设置中配置 AI 接口信息' );
    }

    if ( empty( $post_title ) && empty( $post_content ) ) {
        return new WP_Error( 'no_title', '请先输入文章标题再生成SEO标题' );
    }

    $prompt = str_replace(
        array( '{title}', '{content}', '{keyword}' ),
        array( $post_title, wp_trim_words( wp_strip_all_tags( $post_content ), 120, '' ), $post_title ),
        $prompt
    );

    $result = onedown_ai_call_api( $api_url, $api_key, $model, $prompt, '你是一个标题创作专家。' );

    // 清理结果（移除可能的引号）
    if ( ! is_wp_error( $result ) ) {
        $result = trim( $result, '"\'「」『』\n\r\t ' );
    }

    return $result;
}

/**
 * AI 生成文章内容
 */
function onedown_ai_generate_content( $post_title ) {
    $api_url  = _pz( 'ai_article_api' );
    $api_key  = _pz( 'ai_article_api_key' );
    $model    = _pz( 'ai_article_model' );
    $prompt   = _pz( 'ai_article_prompt', '请根据标题"{title}"撰写一篇符合SEO的中文图文文章。要求：结构清晰，包含引言、多个H2小标题、列表要点和总结；内容自然覆盖长尾关键词；输出HTML正文，允许使用h2、h3、p、ul、li、strong标签；不要输出完整html/head/body。' );

    if ( empty( $api_url ) || empty( $api_key ) ) {
        return new WP_Error( 'config_error', '请先在主题设置中配置 AI 接口信息' );
    }

    if ( empty( $post_title ) ) {
        return new WP_Error( 'no_title', '请先输入文章标题再生成内容' );
    }

    $prompt = str_replace( '{title}', $post_title, $prompt );

    $result = onedown_ai_call_api( $api_url, $api_key, $model, $prompt, '你是一个专业的文章撰写专家。' );

    return $result;
}

/**
 * AI 生成标签
 */
function onedown_ai_generate_tags( $post_title, $post_content = '' ) {
    $api_url  = _pz( 'ai_article_api' );
    $api_key  = _pz( 'ai_article_api_key' );
    $model    = _pz( 'ai_article_model' );
    $prompt   = _pz( 'ai_tags_prompt', '请根据文章标题"{title}"和正文内容"{content}"生成5个符合SEO的中文标签。要求：只输出标签，使用英文逗号分隔，不要编号，不要解释。' );

    if ( empty( $api_url ) || empty( $api_key ) ) {
        return new WP_Error( 'config_error', '请先在主题设置中配置 AI 接口信息' );
    }

    if ( empty( $post_title ) && empty( $post_content ) ) {
        return new WP_Error( 'no_title', '请先输入文章标题或正文内容再生成标签' );
    }

    $prompt = str_replace(
        array( '{title}', '{content}' ),
        array( $post_title, wp_trim_words( wp_strip_all_tags( $post_content ), 220, '' ) ),
        $prompt
    );

    return onedown_ai_call_api( $api_url, $api_key, $model, $prompt, '你是一个标签推荐专家。' );
}

/**
 * 调用 AI 图片 API 生成特色图片
 */
function onedown_ai_generate_image( $post_title, $post_id ) {
    $api_url  = _pz( 'ai_image_api' );
    $api_key  = _pz( 'ai_image_api_key' );
    $model    = _pz( 'ai_image_model' );
    $prompt   = _pz( 'ai_image_prompt', '根据文章标题"{title}"生成一张相关的高质量文章配图。要求：现代、清晰、有主题关联，适合作为博客特色图片；图片中不要强制出现标题文字。' );

    // 如果图片 API Key 为空，使用文章接口的 Key
    if ( empty( $api_key ) ) {
        $api_key = _pz( 'ai_article_api_key' );
    }

    if ( empty( $api_url ) || empty( $api_key ) ) {
        return new WP_Error( 'config_error', '请先在主题设置中配置 AI 图片接口信息' );
    }

    if ( empty( $post_title ) ) {
        // 如果没有标题，使用文章 ID 或默认提示
        $post_title = '文章 #' . $post_id;
    }

    $prompt = str_replace( '{title}', $post_title, $prompt );

    $body = array(
        'model'  => $model,
        'prompt' => $prompt,
        'n'      => 1,
        'size'   => '1024x1024',
    );

    $args = array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body'    => wp_json_encode( $body ),
        'timeout' => 120,
    );

    $response = wp_remote_post( $api_url, $args );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $status_code !== 200 ) {
        $error_msg = isset( $body['error']['message'] ) ? $body['error']['message'] : '图片 API 请求失败，状态码：' . $status_code;
        return new WP_Error( 'api_error', $error_msg );
    }

    $image_url = '';
    if ( isset( $body['data'][0]['url'] ) ) {
        $image_url = $body['data'][0]['url'];
    } elseif ( isset( $body['data'][0]['b64_json'] ) ) {
        // 如果是 base64 格式
        $upload_dir = wp_upload_dir();
        $filename   = 'ai-generated-' . $post_id . '-' . time() . '.png';
        $file_path  = $upload_dir['path'] . '/' . $filename;
        file_put_contents( $file_path, base64_decode( $body['data'][0]['b64_json'] ) );
        $image_url = $upload_dir['url'] . '/' . $filename;
    } else {
        return new WP_Error( 'api_error', '图片 API 返回格式异常' );
    }

    // 将图片下载并设置为特色图片
    $attachment_id = onedown_ai_download_and_set_thumbnail( $image_url, $post_id );

    return array(
        'message'       => '图片已生成：' . $image_url,
        'attachment_id' => $attachment_id,
    );
}

/**
 * 下载远程图片并设置为文章特色图片
 */
function onedown_ai_download_and_set_thumbnail( $image_url, $post_id ) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // 下载图片到媒体库
    $tmp = download_url( $image_url );
    if ( is_wp_error( $tmp ) ) {
        return 0;
    }

    $file_array = array(
        'name'     => 'ai-featured-' . $post_id . '-' . time() . '.jpg',
        'tmp_name' => $tmp,
    );

    $attachment_id = media_handle_sideload( $file_array, $post_id, 'AI 生成特色图片' );

    if ( is_wp_error( $attachment_id ) ) {
        @unlink( $tmp );
        return 0;
    }

    // 设置为特色图片
    set_post_thumbnail( $post_id, $attachment_id );

    return $attachment_id;
}

/**
 * 后台 AI 接口测试按钮
 */
function onedown_ai_test_button() {
    $nonce = wp_create_nonce( 'onedown_ai_test_nonce' );
    ?>
    <div class="csf-fieldset" style="padding-top:10px">
        <button type="button" class="button button-primary" id="onedown-ai-test-article-btn" onclick="onedownAITestApi('article', '<?php echo esc_js( $nonce ); ?>')">
            <?php esc_html_e( '测试文章接口', 'onedown' ); ?>
        </button>
        <button type="button" class="button" id="onedown-ai-test-image-btn" onclick="onedownAITestApi('image', '<?php echo esc_js( $nonce ); ?>')" style="margin-left:8px">
            <?php esc_html_e( '测试图片接口', 'onedown' ); ?>
        </button>
        <span id="onedown-ai-test-result" style="margin-left:10px;font-weight:600"></span>
        <p class="csf-text-desc" style="margin-top:8px;color:#888">
            <?php esc_html_e( '测试会使用当前页面填写的接口地址、Key 和模型；修改配置后可先测试，确认无误再保存。', 'onedown' ); ?>
        </p>
    </div>
    <script type="text/javascript">
    function onedownAIFieldValue(id) {
        var field = document.querySelector('[data-depend-id="' + id + '"] input, [data-depend-id="' + id + '"] textarea');
        return field ? field.value.trim() : '';
    }

    function onedownAITestApi(type, nonce) {
        var articleBtn = document.getElementById('onedown-ai-test-article-btn');
        var imageBtn = document.getElementById('onedown-ai-test-image-btn');
        var result = document.getElementById('onedown-ai-test-result');
        var data = new FormData();

        data.append('action', type === 'image' ? 'onedown_ai_test_image' : 'onedown_ai_test_article');
        data.append('_ajax_nonce', nonce);
        data.append('article_api', onedownAIFieldValue('ai_article_api'));
        data.append('article_key', onedownAIFieldValue('ai_article_api_key'));
        data.append('article_model', onedownAIFieldValue('ai_article_model'));
        data.append('image_api', onedownAIFieldValue('ai_image_api'));
        data.append('image_key', onedownAIFieldValue('ai_image_api_key'));
        data.append('image_model', onedownAIFieldValue('ai_image_model'));

        articleBtn.disabled = true;
        imageBtn.disabled = true;
        result.innerHTML = '<span style="color:#888;">测试中...</span>';

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
            result.innerHTML = '<span style="color:#e74c3c;">请求失败，请检查网络或接口地址</span>';
        })
        .finally(function() {
            articleBtn.disabled = false;
            imageBtn.disabled = false;
        });
    }
    </script>
    <?php
}

add_action( 'wp_ajax_onedown_ai_test_article', 'onedown_ai_ajax_test_article' );
function onedown_ai_ajax_test_article() {
    check_ajax_referer( 'onedown_ai_test_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( '权限不足' );
    }

    $api_url = isset( $_POST['article_api'] ) ? esc_url_raw( wp_unslash( $_POST['article_api'] ) ) : '';
    $api_key = isset( $_POST['article_key'] ) ? sanitize_text_field( wp_unslash( $_POST['article_key'] ) ) : '';
    $model   = isset( $_POST['article_model'] ) ? sanitize_text_field( wp_unslash( $_POST['article_model'] ) ) : '';

    if ( empty( $api_url ) || empty( $api_key ) || empty( $model ) ) {
        wp_send_json_error( '请先填写文章接口地址、API Key 和模型名称' );
    }

    $result = onedown_ai_call_api(
        $api_url,
        $api_key,
        $model,
        '请只回复“AI接口测试成功”，不要输出其他内容。',
        '你是接口连通性测试助手。'
    );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( '文章接口测试成功' );
}

add_action( 'wp_ajax_onedown_ai_test_image', 'onedown_ai_ajax_test_image' );
function onedown_ai_ajax_test_image() {
    check_ajax_referer( 'onedown_ai_test_nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( '权限不足' );
    }

    $api_url = isset( $_POST['image_api'] ) ? esc_url_raw( wp_unslash( $_POST['image_api'] ) ) : '';
    $api_key = isset( $_POST['image_key'] ) ? sanitize_text_field( wp_unslash( $_POST['image_key'] ) ) : '';
    $model   = isset( $_POST['image_model'] ) ? sanitize_text_field( wp_unslash( $_POST['image_model'] ) ) : '';

    if ( empty( $api_key ) ) {
        $api_key = isset( $_POST['article_key'] ) ? sanitize_text_field( wp_unslash( $_POST['article_key'] ) ) : '';
    }

    if ( empty( $api_url ) || empty( $api_key ) || empty( $model ) ) {
        wp_send_json_error( '请先填写图片接口地址、API Key 和模型名称' );
    }

    $response = wp_remote_post( $api_url, array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body'    => wp_json_encode( array(
            'model'  => $model,
            'prompt' => '生成一张用于接口连通性测试的简洁博客配图，不包含文字。',
            'n'      => 1,
            'size'   => '1024x1024',
        ) ),
        'timeout' => 120,
    ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( $response->get_error_message() );
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    $body        = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $status_code !== 200 ) {
        $error_msg = isset( $body['error']['message'] ) ? $body['error']['message'] : '图片接口请求失败，状态码：' . $status_code;
        wp_send_json_error( $error_msg );
    }

    if ( empty( $body['data'][0]['url'] ) && empty( $body['data'][0]['b64_json'] ) ) {
        wp_send_json_error( '图片接口返回格式异常' );
    }

    wp_send_json_success( '图片接口测试成功' );
}
