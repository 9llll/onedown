/**
 * Onedown 验证码交互
 *
 * 验证码图片直接通过 PHP 生成的 URL 加载，
 * JS 仅负责点击刷新功能。
 */
(function () {
  'use strict';

  var ajaxUrl = window.onedownData ? onedownData.ajaxUrl : '/wp-admin/admin-ajax.php';

  /**
   * 刷新验证码图片
   */
  function refresh(imgEl, captchaId) {
    if (!imgEl || !captchaId) return;

    var formData = new FormData();
    formData.set('action', 'onedown_captcha_refresh');
    formData.set('id', captchaId);

    fetch(ajaxUrl, { method: 'POST', body: formData })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success && data.data.url) {
          // 加时间戳防止浏览器缓存
          imgEl.src = data.data.url + '&t=' + Date.now();
        }
      })
      .catch(function () {
        // 刷新失败时直接加时间戳重试原 URL
        imgEl.src = imgEl.src.split('&t=')[0] + '&t=' + Date.now();
      });
  }

  function initialUrl(captchaId) {
    var base = ajaxUrl + '?action=onedown_captcha_image&id=' + encodeURIComponent(captchaId);
    return base + '&t=' + Date.now();
  }

  function bindRefresh(container) {
    if (!container) return;

    var rows = container.querySelectorAll('[data-captcha-id]');
    Array.prototype.forEach.call(rows, function (row) {
      var captchaId = row.getAttribute('data-captcha-id');
      var img = row.querySelector('.captcha-img');
      if (!img) return;

      if (!img.getAttribute('src') || img.getAttribute('src').indexOf('data:') === 0) {
        img.src = initialUrl(captchaId);
      }

      img.addEventListener('click', function () {
        refresh(img, captchaId);
      });

      var refreshBtn = row.querySelector('.captcha-refresh');
      if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
          refresh(img, captchaId);
        });
      }
    });
  }

  // 页面加载后初始化已有元素
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      bindRefresh(document);
    });
  } else {
    bindRefresh(document);
  }

  // 暴露全局方法（供弹窗等动态创建的元素使用）
  window.onedownCaptcha = {
    bindElement: function (parentEl) {
      bindRefresh(parentEl);
    },
    refresh: function (imgEl, captchaId) {
      refresh(imgEl, captchaId);
    }
  };
})();
