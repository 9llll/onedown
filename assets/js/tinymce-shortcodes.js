(function () {
    'use strict';

    tinymce.PluginManager.add('onedown_shortcodes', function (editor, url) {
        var items = [];

        // 评论可见（使用 hidecontent，功能更全）
        items.push({
            text: '评论可见',
            onclick: function () {
                editor.insertContent('[hidecontent type="reply"]此处内容评论后可见[/hidecontent]');
            }
        });
        items.push({
            text: '登录可见',
            onclick: function () {
                editor.insertContent('[hidecontent type="logged"]此处内容登录后可见[/hidecontent]');
            }
        });
        items.push({
            text: '会员可见',
            onclick: function () {
                editor.insertContent('[hidecontent type="vip"]此处内容仅会员可见[/hidecontent]');
            }
        });
        items.push({
            text: '密码保护',
            onclick: function () {
                editor.insertContent('[hidecontent type="password" password=""]此处内容需密码查看[/hidecontent]');
            }
        });
        items.push({
            text: '付费阅读',
            onclick: function () {
                editor.insertContent('[payshow]此处内容需付费查看[/payshow]');
            }
        });
        items.push({
            text: '微信验证',
            onclick: function () {
                editor.insertContent('[hidecontent type="wechat" keyword="验证码"]此处内容需关注公众号查看[/hidecontent]');
            }
        });

        editor.addButton('onedown_shortcodes', {
            type: 'menubutton',
            text: '短代码',
            icon: '',
            tooltip: '插入短代码',
            menu: items
        });
    });
})();
