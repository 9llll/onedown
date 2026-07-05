(function () {
  try {
    var assetVersion =
      window.onedownData && onedownData.assetVersion
        ? String(onedownData.assetVersion)
        : "";
    var versionKey = "onedown-asset-version";
    var reloadKey = "onedown-asset-version-reloaded";
    var previousVersion = localStorage.getItem(versionKey) || "";

    if (assetVersion && previousVersion && previousVersion !== assetVersion) {
      var themeValue = localStorage.getItem("onedown-theme");

      try {
        localStorage.clear();
      } catch (e) {}

      try {
        sessionStorage.clear();
      } catch (e) {}

      if (themeValue) {
        try {
          localStorage.setItem("onedown-theme", themeValue);
        } catch (e) {}
      }

      try {
        localStorage.setItem(versionKey, assetVersion);
      } catch (e) {}

      try {
        if (window.caches && typeof window.caches.keys === "function") {
          window.caches.keys().then(function (keys) {
            keys.forEach(function (key) {
              window.caches.delete(key);
            });
          });
        }
      } catch (e) {}

      if (sessionStorage.getItem(reloadKey) !== assetVersion) {
        sessionStorage.setItem(reloadKey, assetVersion);
        window.location.reload(true);
      }
    } else if (assetVersion) {
      localStorage.setItem(versionKey, assetVersion);
      if (sessionStorage.getItem(reloadKey) !== assetVersion) {
        sessionStorage.removeItem(reloadKey);
      }
    }
  } catch (e) {}

  try {
    if (window.onedownData && onedownData.disableDevtools) {
      var devtoolsRedirectUrl = onedownData.homeUrl || "/";
      var devtoolsRedirecting = false;

      function redirectToHome() {
        if (devtoolsRedirecting) return;
        devtoolsRedirecting = true;
        window.location.href = devtoolsRedirectUrl;
      }

      document.addEventListener("contextmenu", function (e) {
        e.preventDefault();
      });

      document.addEventListener("keydown", function (e) {
        var key = (e.key || "").toLowerCase();
        var blocked =
          key === "f12" ||
          (e.ctrlKey && e.shiftKey && (key === "i" || key === "j" || key === "c"));

        if (blocked) {
          e.preventDefault();
          e.stopPropagation();
          redirectToHome();
        }
      });

      window.setInterval(function () {
        var widthGap = window.outerWidth - window.innerWidth;
        var heightGap = window.outerHeight - window.innerHeight;

        if (widthGap > 160 || heightGap > 160) {
          redirectToHome();
        }
      }, 1000);
    }
  } catch (e) {}

  // ── VIP Plans from PHP ──
  var plans = [];
  var prices = (window.onedownData && onedownData.vipPlans) || {};
  var priceVals = (window.onedownData && onedownData.vipPrices) || {};
  var upgradePriceVals = (window.onedownData && onedownData.vipUpgradePrices) || {};
  Object.keys(prices).forEach(function (id) {
    var p = prices[id];
    plans.push({
      id: id,
      name: p.name,
      price: String(priceVals[id] || 0),
      upgradePrice: String(upgradePriceVals[id] !== undefined ? upgradePriceVals[id] : priceVals[id] || 0),
      period: p.months > 0 ? (p.months >= 12 ? "年" : "月") : "永久",
      months: p.months,
      desc: p.desc,
    });
  });
  if (!plans.length) {
    plans = [
      {
        id: "monthly",
        name: "月度会员",
        price: "29",
        upgradePrice: "29",
        period: "月",
        months: 1,
        desc: "适合短期体验",
      },
      {
        id: "yearly",
        name: "年度会员",
        price: "199",
        upgradePrice: "199",
        period: "年",
        months: 12,
        desc: "适合长期运营",
      },
      {
        id: "forever",
        name: "永久会员",
        price: "399",
        upgradePrice: "399",
        period: "永久",
        months: 0,
        desc: "一次性购买",
      },
    ];
  }

  var payMethods = [];
  // 从 PHP 传递的数据构建支付方式列表
  if (window.onedownData && onedownData.payMethods) {
    var pm = onedownData.payMethods;
    var iconMap = {
      epay: "fa-credit-card",
      alipay: "fa-credit-card",
      wechat: "fa-wechat",
      balance: "fa-google-wallet",
      offline: "fa-money",
    };
    Object.keys(pm).forEach(function (id) {
      payMethods.push({
        id: id,
        name: pm[id].name,
        icon: iconMap[id] || "fa-credit-card",
      });
    });
  }
  if (!payMethods.length) {
    payMethods = [
      { id: "wechat", name: "微信支付", icon: "fa-wechat" },
      { id: "alipay", name: "支付宝", icon: "fa-credit-card" },
    ];
  }

  function findPlan(planId) {
    return (
      plans.filter(function (plan) {
        return plan.id === planId;
      })[0] || plans[1]
    );
  }

  function ensureVipModal() {
    var modal = document.getElementById("vipPayModal");
    if (modal) {
      return modal;
    }

    modal = document.createElement("div");
    modal.className = "vip-pay-modal";
    modal.id = "vipPayModal";
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML = [
      '<div class="vip-pay-mask"></div>',
      '<div class="vip-pay-dialog" role="dialog" aria-modal="true" aria-labelledby="vipPayTitle">',
      '<button class="vip-pay-close" type="button" aria-label="关闭" data-vip-close><i class="fa fa-times"></i></button>',
      '<div class="vip-pay-head">',
      '<span class="vip-pay-icon"><i class="fa fa-diamond"></i></span>',
      '<div><span class="vip-pay-kicker">VIP MEMBER</span><h2 id="vipPayTitle">开通会员</h2><p>选择会员套餐和支付方式后确认开通。</p></div>',
      "</div>",
      '<div class="vip-pay-body">',
      '<div class="vip-pay-block"><h3>会员套餐</h3><div class="vip-plan-options" data-vip-plans></div></div>',
      '<div class="vip-pay-block"><h3>支付方式</h3><div class="vip-method-options" data-vip-methods></div></div>',
      '<div class="vip-order-box">',
      "<div><span>当前套餐</span><strong data-vip-order-name></strong></div>",
      "<div><span>支付方式</span><strong data-vip-order-method></strong></div>",
      '<div class="vip-order-total"><span>应付金额</span><strong>￥<em data-vip-order-price></em></strong></div>',
      "</div>",
      '<p class="vip-pay-status" data-vip-status></p>',
      "</div>",
      '<div class="vip-pay-actions">',
      '<button class="vip-pay-secondary" type="button" data-vip-close>取消</button>',
      '<button class="vip-pay-primary" type="button" data-vip-submit>确定开通</button>',
      "</div>",
      "</div>",
    ].join("");
    document.body.appendChild(modal);

    var plansWrap = modal.querySelector("[data-vip-plans]");
    var methodsWrap = modal.querySelector("[data-vip-methods]");
    var currentPlanId =
      (window.onedownData && onedownData.currentVipPlanId) || "";
    var planOrder = Object.keys(prices);
    var currentPlanIdx = planOrder.indexOf(currentPlanId);
    plansWrap.innerHTML = plans
      .map(function (plan) {
        var planIdx = planOrder.indexOf(plan.id);
        var isLower =
          currentPlanId &&
          planIdx >= 0 &&
          currentPlanIdx >= 0 &&
          planIdx < currentPlanIdx;
        var isCurrent = plan.id === currentPlanId;
        var btnText = isCurrent ? "当前会员" : isLower ? "已开通" : "立即开通";
        var cls = "vip-plan-option";
        if (isCurrent) cls += " active current-plan";
        if (isLower) cls += " disabled-plan";
        var disabledAttr = isLower ? " disabled" : "";
        return (
          '<button class="' +
          cls +
          '" type="button" data-plan-id="' +
          plan.id +
          '" data-upgrade-price="' +
          plan.upgradePrice +
          '"' +
          disabledAttr +
          ">" +
          (isCurrent
            ? '<span class="plan-badge current-badge">当前</span>'
            : "") +
          "<span><strong>" +
          plan.name +
          "</strong><small>" +
          plan.desc +
          "</small></span>" +
          "<em>￥" +
          plan.price +
          "<small>/" +
          plan.period +
          "</small></em>" +
          '<span class="plan-btn-text">' +
          btnText +
          "</span>" +
          "</button>"
        );
      })
      .join("");
    methodsWrap.innerHTML = payMethods
      .map(function (method) {
        return (
          '<button class="vip-method-option" type="button" data-method-id="' +
          method.id +
          '">' +
          '<i class="fa ' +
          method.icon +
          '"></i><span>' +
          method.name +
          "</span>" +
          "</button>"
        );
      })
      .join("");

    bindVipModal(modal);
    return modal;
  }

  function setActive(items, attr, value) {
    Array.prototype.forEach.call(items, function (item) {
      item.classList.toggle("active", item.getAttribute(attr) === value);
    });
  }

  function updateOrder(modal) {
    var isUpgrade = modal.hasAttribute("data-vip-upgrade");
    var plan = findPlan(modal.getAttribute("data-selected-plan"));
    var methodId =
      modal.getAttribute("data-selected-method") || payMethods[0].id;
    var method =
      payMethods.filter(function (item) {
        return item.id === methodId;
      })[0] || payMethods[0];

    // 升级模式显示差价，否则显示全价
    var displayPrice = isUpgrade ? plan.upgradePrice : plan.price;

    modal.querySelector("[data-vip-order-name]").textContent = plan.name;
    modal.querySelector("[data-vip-order-method]").textContent = method.name;
    modal.querySelector("[data-vip-order-price]").textContent = displayPrice;
    setActive(
      modal.querySelectorAll("[data-plan-id]"),
      "data-plan-id",
      plan.id,
    );
    setActive(
      modal.querySelectorAll("[data-method-id]"),
      "data-method-id",
      method.id,
    );

    // 更新套餐列表中每个选项的价格显示
    modal.querySelectorAll("[data-plan-id]").forEach(function (btn) {
      var pid = btn.getAttribute("data-plan-id");
      var priceEl = btn.querySelector("em");
      if (!priceEl) return;
      var p = plans.filter(function (pl) { return pl.id === pid; })[0];
      if (!p) return;
      var showPrice = isUpgrade ? p.upgradePrice : p.price;
      var oldHtml = priceEl.innerHTML;
      // 只更新价格数字部分（￥X<small>/Y</small>）
      priceEl.innerHTML = "￥" + showPrice + priceEl.innerHTML.replace(/^￥[\d.]+/, "");
    });
  }

  function showVipToast(message) {
    var toast = document.getElementById("vipToast");
    if (!toast) {
      toast = document.createElement("div");
      toast.className = "vip-toast";
      toast.id = "vipToast";
      toast.setAttribute("role", "status");
      document.body.appendChild(toast);
    }
    toast.innerHTML =
      '<i class="fa fa-check-circle"></i><span>' + message + "</span>";
    toast.classList.add("is-show");
    window.clearTimeout(toast._timer);
    toast._timer = window.setTimeout(function () {
      toast.classList.remove("is-show");
    }, 1800);
  }

  function openVipModal(planId, isUpgrade) {
    if (!window.onedownData || !onedownData.isLoggedIn) {
      window.location.href =
        onedownData.signUrl +
        "&redirect_to=" +
        encodeURIComponent(window.location.href);
      return;
    }
    var modal = ensureVipModal();
    modal.setAttribute("data-selected-plan", planId || "yearly");
    modal.setAttribute("data-selected-method", "wechat");
    modal.querySelector("[data-vip-status]").textContent = "";
    // 重置提交按钮状态
    var submitBtn = modal.querySelector("[data-vip-submit]");
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = isUpgrade ? "确定升级" : "确定开通";
    }
    // 切换升级/开通标题
    var titleEl = modal.querySelector("#vipPayTitle");
    var descEl = modal.querySelector(".vip-pay-head p");
    if (titleEl) titleEl.textContent = isUpgrade ? "升级会员" : "开通会员";
    if (descEl)
      descEl.textContent = isUpgrade
        ? "选择更高级的会员套餐，享受更多权益。"
        : "选择会员套餐和支付方式后确认开通。";
    if (isUpgrade) {
      modal.setAttribute("data-vip-upgrade", "true");
    } else {
      modal.removeAttribute("data-vip-upgrade");
    }
    updateOrder(modal);
    modal.classList.add("is-show");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
  }

  function closeVipModal(modal) {
    modal.classList.remove("is-show");
    if (document.activeElement) document.activeElement.blur();
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
    // 重置为默认开通状态，避免 BFCache 恢复后文本不对
    var submitBtn = modal.querySelector("[data-vip-submit]");
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '确定开通';
    }
    var titleEl = modal.querySelector("#vipPayTitle");
    if (titleEl) titleEl.textContent = "开通会员";
    var descEl = modal.querySelector(".vip-pay-head p");
    if (descEl) descEl.textContent = "选择会员套餐和支付方式后确认开通。";
    modal.removeAttribute("data-vip-upgrade");
    var status = modal.querySelector("[data-vip-status]");
    if (status) {
      status.textContent = "";
    }
  }

  function bindVipModal(modal) {
    modal.addEventListener("click", function (event) {
      var closeBtn = event.target.closest("[data-vip-close]");
      var planBtn = event.target.closest("[data-plan-id]");
      var methodBtn = event.target.closest("[data-method-id]");
      var submitBtn = event.target.closest("[data-vip-submit]");
      var status = modal.querySelector("[data-vip-status]");

      if (closeBtn) {
        closeVipModal(modal);
        return;
      }

      if (planBtn) {
        modal.setAttribute(
          "data-selected-plan",
          planBtn.getAttribute("data-plan-id"),
        );
        status.textContent = "";
        updateOrder(modal);
        return;
      }

      if (methodBtn) {
        modal.setAttribute(
          "data-selected-method",
          methodBtn.getAttribute("data-method-id"),
        );
        status.textContent = "";
        updateOrder(modal);
        return;
      }

      if (submitBtn) {
        var plan = findPlan(modal.getAttribute("data-selected-plan"));
        var methodId =
          modal.getAttribute("data-selected-method") || payMethods[0].id;

        submitBtn.disabled = true;
        status.textContent = "正在创建订单...";

        var formData = new FormData();
        formData.set("action", "onedown_initiate_pay");
        formData.set("order_type", "vip");
        formData.set("pay_method", methodId);
        formData.set("plan_id", plan.id);
        formData.set(
          "_wpnonce",
          window.onedownData ? onedownData.payNonce : "",
        );

        fetch(
          window.onedownData ? onedownData.ajaxUrl : "/wp-admin/admin-ajax.php",
          { method: "POST", body: formData },
        )
          .then(function (r) {
            return r.json();
          })
          .then(function (data) {
            if (data.success) {
              var result = data.data;
              if (result.pay_type === "redirect") {
                // 先关闭弹窗再跳转，避免用户按返回后弹窗卡住
                closeVipModal(modal);
                status.textContent = "正在跳转到支付页面...";
                setTimeout(function () {
                  window.location.href = result.pay_url;
                }, 200);
              } else if (result.pay_type === "qrcode") {
                // 关闭VIP弹窗，打开独立二维码支付弹窗
                result.pay_method =
                  modal.getAttribute("data-selected-method") || "wechat";
                result.order_title = "开通会员：" + plan.name;
                openQrcodeModal(result);
              } else if (result.pay_type === "success") {
                // 直接成功（如余额支付）
                status.textContent = "";
                submitBtn.disabled = false;
                closeVipModal(modal);
                showVipToast("开通成功");
                setTimeout(function () {
                  window.location.href = onedownData.userCenterUrl + "?tab=vip";
                }, 800);
              } else if (result.pay_type === "offline") {
                // 线下支付
                status.textContent = result.msg || "订单已创建，请线下付款";
                submitBtn.disabled = false;
                if (result.offline_info) {
                  status.innerHTML +=
                    "<br><small>" + result.offline_info + "</small>";
                }
              } else {
                throw new Error(result.msg || "支付发起失败");
              }
            } else {
              throw new Error(
                data.data && data.data.msg ? data.data.msg : "订单创建失败",
              );
            }
          })
          .catch(function (err) {
            status.textContent = err.message || "操作失败，请重试";
            submitBtn.disabled = false;
          });
      }
    });
  }

  document.addEventListener("click", function (event) {
    var trigger = event.target.closest("[data-vip-modal]");
    if (!trigger) {
      return;
    }
    event.preventDefault();
    // 自动识别升级模式：如果触发器未指定 data-vip-upgrade 但用户已是会员，自动开启升级模式
    var isUpgrade = trigger.hasAttribute("data-vip-upgrade");
    if (!isUpgrade && window.onedownData && onedownData.currentVipPlanId) {
      isUpgrade = true;
    }
    // 强制清理其他弹窗（BFCache 保护）
    document.body.classList.remove("modal-open");
    closePayModal();
    closeQrcodeModal();
    openVipModal(
      trigger.getAttribute("data-vip-plan"),
      isUpgrade,
    );
  });

  document.addEventListener("keydown", function (event) {
    var modal = document.getElementById("vipPayModal");
    if (
      event.key === "Escape" &&
      modal &&
      modal.classList.contains("is-show")
    ) {
      closeVipModal(modal);
    }
  });

  // ── Signin Modal (登录弹窗) ──
  function ensureSigninModal() {
    var modal = document.getElementById("signinModal");
    if (modal) {
      return modal;
    }

    modal = document.createElement("div");
    modal.className = "vip-pay-modal";
    modal.id = "signinModal";
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML = [
      '<div class="vip-pay-mask"></div>',
      '<div class="vip-pay-dialog" role="dialog" aria-modal="true" style="max-width:440px;">',
      '<button class="vip-pay-close" type="button" aria-label="关闭" data-sign-close><i class="fa fa-times"></i></button>',
      '<div class="vip-pay-head">',
      '<span class="vip-pay-icon"><i class="fa fa-user-circle-o"></i></span>',
      '<div><span class="vip-pay-kicker">ACCOUNT LOGIN</span><h2>欢迎回来</h2><p>使用邮箱或用户名登录</p></div>',
      "</div>",
      '<div class="vip-pay-body">',
      '<form class="auth-form" id="modal-signin-form" method="post">',
      "<label>",
      "<span>账号</span>",
      '<div class="form-control-icon">',
      '<i class="fa fa-user-o"></i>',
      '<input type="text" name="username" placeholder="请输入用户名或邮箱" required>',
      "</div>",
      "</label>",
      "<label>",
      "<span>密码</span>",
      '<div class="form-control-icon">',
      '<i class="fa fa-lock"></i>',
      '<input type="password" name="password" placeholder="请输入登录密码" required autocomplete="current-password">',
      '<span class="input-suffix pass-toggle" data-toggle="password"><i class="fa fa-eye"></i></span>',
      "</div>",
      "</label>",
      window.onedownData && onedownData.captchaLoginHtml
        ? onedownData.captchaLoginHtml
        : "",
      '<div class="auth-row">',
      '<label class="check-line"><input type="checkbox" name="remember" value="forever" checked> <span>记住登录</span></label>',
      '<a href="javascript:;" data-sign-close data-open-signup>找回密码</a>',
      "</div>",
      '<button type="submit" class="auth-submit"><i class="fa fa-sign-in"></i> 登录</button>',
      '<input type="hidden" name="action" value="onedown_signin">',
      "</form>",
      '<div class="sign-switch">还没有账号？<a href="javascript:;" data-sign-close data-open-signup>立即注册</a></div>',
      "</div>",
      "</div>",
    ].join("");
    document.body.appendChild(modal);

    // Bind close
    modal.addEventListener("click", function (e) {
      if (e.target.closest("[data-sign-close]")) {
        modal.classList.remove("is-show");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
      }
      // Open register modal
      if (e.target.closest("[data-open-signup]")) {
        openSignModal("signup");
      }
    });

    // 绑定验证码刷新事件
    if (window.onedownCaptcha) {
      onedownCaptcha.bindElement(modal);
    }

    // Bind form submit
    var form = modal.querySelector("#modal-signin-form");
    if (form) {
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 登录中...';

        var fd = new FormData(form);
        fd.set("action", "onedown_signin");
        if (window.onedownData) {
          fd.set("_wpnonce", onedownData.signinNonce);
          fd.set("redirect_to", window.location.href);
        }

        fetch(
          window.onedownData ? onedownData.ajaxUrl : "/wp-admin/admin-ajax.php",
          {
            method: "POST",
            body: fd,
          },
        )
          .then(function (r) {
            return r.json();
          })
          .then(function (data) {
            if (data.success) {
              showSignToast(data.data.msg || "登录成功", "success");
              setTimeout(function () {
                window.location.reload();
              }, 500);
            } else {
              showSignToast(
                data.data && data.data.msg ? data.data.msg : "登录失败",
                "error",
              );
              btn.disabled = false;
              btn.innerHTML = orig;
            }
          })
          .catch(function () {
            showSignToast("网络错误，请重试", "error");
            btn.disabled = false;
            btn.innerHTML = orig;
          });
      });
    }

    return modal;
  }

  // ── Signup Modal (注册弹窗) ──
  function ensureSignupModal() {
    var modal = document.getElementById("signupModal");
    if (modal) {
      return modal;
    }

    modal = document.createElement("div");
    modal.className = "vip-pay-modal";
    modal.id = "signupModal";
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML = [
      '<div class="vip-pay-mask"></div>',
      '<div class="vip-pay-dialog" role="dialog" aria-modal="true" style="max-width:440px;">',
      '<button class="vip-pay-close" type="button" aria-label="关闭" data-sign-close><i class="fa fa-times"></i></button>',
      '<div class="vip-pay-head">',
      '<span class="vip-pay-icon"><i class="fa fa-user-plus"></i></span>',
      '<div><span class="vip-pay-kicker">JOIN COMMUNITY</span><h2>创建账户</h2><p>注册后可使用会员与社区功能</p></div>',
      "</div>",
      '<div class="vip-pay-body">',
      '<form class="auth-form" id="modal-signup-form" method="post">',
      "<label>",
      "<span>用户名</span>",
      '<div class="form-control-icon">',
      '<i class="fa fa-user-o"></i>',
      '<input type="text" name="name" placeholder="请输入用户名" required minlength="3">',
      "</div>",
      "</label>",
      "<label>",
      "<span>邮箱</span>",
      '<div class="form-control-icon">',
      '<i class="fa fa-envelope-o"></i>',
      '<input type="email" name="email" placeholder="请输入邮箱地址" required>',
      "</div>",
      "</label>",
      "<label>",
      "<span>密码</span>",
      '<div class="form-control-icon">',
      '<i class="fa fa-lock"></i>',
      '<input type="password" name="password" placeholder="设置登录密码" required minlength="6" autocomplete="new-password">',
      '<span class="input-suffix pass-toggle" data-toggle="password"><i class="fa fa-eye"></i></span>',
      "</div>",
      "</label>",
      window.onedownData && onedownData.captchaRegisterHtml
        ? onedownData.captchaRegisterHtml
        : "",
      '<label class="check-line"><input type="checkbox" name="agreement" checked> <span>已阅读并同意 <a href="' +
        (window.onedownData && onedownData.agreementUrl
          ? onedownData.agreementUrl
          : "#") +
        '" target="_blank">用户协议</a></span></label>',
      '<button type="submit" class="auth-submit"><i class="fa fa-user-plus"></i> 注册</button>',
      '<input type="hidden" name="action" value="onedown_signup">',
      "</form>",
      '<div class="sign-switch">已有账号？<a href="javascript:;" data-sign-close data-open-signin>去登录</a></div>',
      "</div>",
      "</div>",
    ].join("");
    document.body.appendChild(modal);

    // Bind close & switch to login
    modal.addEventListener("click", function (e) {
      if (e.target.closest("[data-sign-close]")) {
        modal.classList.remove("is-show");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
      }
      // Open login modal
      if (e.target.closest("[data-open-signin]")) {
        openSignModal("signin");
      }
    });

    // 绑定验证码刷新事件
    if (window.onedownCaptcha) {
      onedownCaptcha.bindElement(modal);
    }

    // Bind form submit
    var form = modal.querySelector("#modal-signup-form");
    if (form) {
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 注册中...';

        var fd = new FormData(form);
        fd.set("action", "onedown_signup");
        if (window.onedownData) {
          fd.set("_wpnonce", onedownData.signupNonce);
          fd.set("redirect_to", window.location.href);
        }

        fetch(
          window.onedownData ? onedownData.ajaxUrl : "/wp-admin/admin-ajax.php",
          {
            method: "POST",
            body: fd,
          },
        )
          .then(function (r) {
            return r.json();
          })
          .then(function (data) {
            if (data.success) {
              showSignToast(data.data.msg || "注册成功", "success");
              setTimeout(function () {
                window.location.reload();
              }, 500);
            } else {
              showSignToast(
                data.data && data.data.msg ? data.data.msg : "注册失败",
                "error",
              );
              btn.disabled = false;
              btn.innerHTML = orig;
            }
          })
          .catch(function () {
            showSignToast("网络错误，请重试", "error");
            btn.disabled = false;
            btn.innerHTML = orig;
          });
      });
    }

    return modal;
  }

  function openSignModal(tab) {
    var modal;
    if (tab === "signup") {
      // Close signin modal if open
      var signinModal = document.getElementById("signinModal");
      if (signinModal && signinModal.classList.contains("is-show")) {
        signinModal.classList.remove("is-show");
        signinModal.setAttribute("aria-hidden", "true");
      }
      modal = ensureSignupModal();
    } else {
      // Close signup modal if open
      var signupModal = document.getElementById("signupModal");
      if (signupModal && signupModal.classList.contains("is-show")) {
        signupModal.classList.remove("is-show");
        signupModal.setAttribute("aria-hidden", "true");
      }
      modal = ensureSigninModal();
    }
    modal.classList.add("is-show");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
  }

  // ── Sign Toast (复用VIP toast风格) ──
  function showSignToast(message, type) {
    var toast = document.getElementById("signToast");
    if (!toast) {
      toast = document.createElement("div");
      toast.id = "signToast";
      toast.className = "sign-toast";
      document.body.appendChild(toast);
    }
    toast.className =
      "sign-toast is-show " +
      (type === "success" ? "toast-success" : "toast-error");
    toast.innerHTML =
      '<i class="fa ' +
      (type === "success" ? "fa-check-circle" : "fa-exclamation-circle") +
      '"></i> ' +
      message;
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
      toast.classList.remove("is-show");
    }, 3000);
  }

  // 监听 data-sign-modal 触发器
  document.addEventListener("click", function (event) {
    var trigger = event.target.closest("[data-sign-modal]");
    if (!trigger) return;
    event.preventDefault();
    var tab = trigger.getAttribute("data-sign-modal");
    openSignModal(tab && tab !== "" ? tab : "signin");
  });

  // ── 用户菜单点击切换（兜底 hover 不生效的情况）──
  document.addEventListener("click", function (e) {
    var card = e.target.closest(".nav-user-card");
    if (!card) {
      // 点击菜单外部关闭
      document.querySelectorAll(".nav-user-card.active").forEach(function (c) {
        c.classList.remove("active");
      });
      return;
    }
    // 点击头像切换菜单；菜单内链接正常跳转
    var avatar = card.querySelector(".nav-avatar");
    if (avatar && avatar.contains(e.target)) {
      e.preventDefault();
      card.classList.toggle("active");
    }
  });

  // ── Mobile Drawer ──
  function createDrawerSectionFromNav(nav) {
    var menu = document.createElement("div");
    menu.className = "mobile-drawer-menu";
    Array.prototype.forEach.call(nav.children, function (item) {
      var link = item.matches("a") ? item : item.querySelector(":scope > a");
      if (!link) {
        return;
      }

      var menuItem = document.createElement("div");
      menuItem.className = "mobile-drawer-item";
      var topLink = link.cloneNode(true);
      topLink.classList.remove("active");
      menuItem.appendChild(topLink);

      var dropdown = item.querySelector(".nav-dropdown");
      if (dropdown) {
        var subMenu = document.createElement("div");
        subMenu.className = "mobile-drawer-submenu";
        Array.prototype.forEach.call(
          dropdown.querySelectorAll("a"),
          function (subLink) {
            subMenu.appendChild(subLink.cloneNode(true));
          },
        );
        menuItem.appendChild(subMenu);
      }
      menu.appendChild(menuItem);
    });
    return menu;
  }

  function createMobileDrawer() {
    var nav = document.getElementById("mainNav");
    var menuBtn = document.getElementById("menuBtn");
    if (!nav || !menuBtn || document.getElementById("mobileDrawer")) {
      return;
    }

    var drawer = document.createElement("div");
    drawer.className = "mobile-drawer";
    drawer.id = "mobileDrawer";
    drawer.setAttribute("aria-hidden", "true");
    var logoHtml = "";
    if (window.onedownData && onedownData.siteLogo) {
      logoHtml =
        '<img class="brand-logo" src="' +
        onedownData.siteLogo +
        '" alt="' +
        (onedownData.siteName || "") +
        '" width="' +
        (onedownData.siteLogoWidth || 150) +
        '">';
    } else {
      logoHtml =
        '<span class="brand-mark"><i class="fa fa-bolt"></i></span><span class="brand-name"><strong>' +
        ((window.onedownData && onedownData.siteName) || "首页") +
        "</strong><span></span></span>";
    }

    drawer.innerHTML = [
      '<div class="mobile-drawer-mask" data-mobile-drawer-close></div>',
      '<aside class="mobile-drawer-panel" aria-label="移动端菜单">',
      '<div class="mobile-drawer-head">',
      '<a href="' +
        ((window.onedownData && onedownData.userCenterUrl) || "./").replace(
          /\/user-center\/?$/,
          "",
        ) +
        '" class="brand">' +
        logoHtml +
        "</a>",
      '<button class="mobile-drawer-close" type="button" aria-label="关闭菜单" data-mobile-drawer-close><i class="fa fa-times"></i></button>',
      "</div>",
      '<div class="mobile-drawer-scroll" data-mobile-drawer-scroll></div>',
      '<div class="mobile-drawer-account" data-mobile-drawer-account></div>',
      "</aside>",
    ].join("");
    drawer
      .querySelector("[data-mobile-drawer-scroll]")
      .appendChild(createDrawerSectionFromNav(nav));
    document.body.appendChild(drawer);

    function renderAccount() {
      var account = drawer.querySelector("[data-mobile-drawer-account]");
      var d = window.onedownData || {};
      if (!d.isLoggedIn) {
        account.classList.add("is-guest");
        account.innerHTML = [
          '<div class="mobile-auth-card">',
          "<strong>未登录</strong>",
          "<p>登录后可查看订单、下载、收藏和会员状态</p>",
          '<div class="mobile-auth-actions">',
          '<a class="primary" href="javascript:;" data-sign-modal><i class="fa fa-sign-in"></i> 登录</a>',
          '<a class="signup" href="javascript:;" data-sign-modal="signup"><i class="fa fa-user-plus"></i> 注册</a>',
          "</div>",
          "</div>",
        ].join("");
        return;
      }

      account.classList.remove("is-guest");

      var userName = d.userDisplayName || "用户";
      var userInitial = userName.charAt(0).toUpperCase();
      var avatarHtml =
        '<img class="mobile-user-avatar-img" src="' +
        (d.userAvatar || "") +
        '" alt="' +
        userName +
        "\" onerror=\"this.style.display='none';this.nextElementSibling.style.display='flex'\">" +
        '<span class="mobile-user-avatar-init">' +
        userInitial +
        "</span>";

      var vipHtml = "";
      if (d.vipName && d.vipName !== "普通会员") {
        vipHtml =
          '<span class="mobile-vip-badge ' +
          (d.vipClass || "") +
          '"><i class="fa fa-diamond"></i> ' +
          d.vipName +
          "</span>";
      }
      var expireHtml =
        '<span class="mobile-vip-expire"><i class="fa fa-clock-o"></i> ' +
        (d.vipExpireDate || "永久") +
        "</span>";

      // 构建操作按钮
      var actions = [];
      var isAdmin = !!d.canManageOptions;

      actions.push(
        '<a href="' +
          (d.userCenterUrl || "#") +
          '" aria-label="用户中心"><i class="fa fa-user"></i><span>用户中心</span></a>',
      );

      if (d.canPublishPosts) {
        var postUrl = isAdmin
          ? d.ajaxUrl.replace("admin-ajax.php", "post-new.php")
          : d.submitPostUrl || "/submit-post/";
        actions.push(
          '<a href="' +
            postUrl +
            '" aria-label="发布文章"><i class="fa fa-pencil"></i><span>发布文章</span></a>',
        );
      }

      if (isAdmin) {
        actions.push(
          '<a href="' +
            d.ajaxUrl.replace("admin-ajax.php", "") +
            '" aria-label="后台管理" class="is-admin"><i class="fa fa-cogs"></i><span>后台管理</span></a>',
        );
      }

      actions.push(
        '<a href="' +
          (d.logoutUrl ||
            d.ajaxUrl.replace(
              "admin-ajax.php",
              "wp-login.php?action=logout&redirect_to=" +
                encodeURIComponent(window.location.href),
            )) +
          '" aria-label="退出登录" class="is-logout"><i class="fa fa-sign-out"></i><span>退出登录</span></a>',
      );

      account.innerHTML = [
        '<div class="mobile-user-card">',
        '<div class="mobile-user-avatar">' + avatarHtml + "</div>",
        '<div class="mobile-user-info">',
        "<strong>" + userName + "</strong>",
        '<div class="mobile-user-meta">' + vipHtml + expireHtml + "</div>",
        "</div>",
        "</div>",
        '<div class="mobile-user-actions">',
        actions.join(""),
        "</div>",
      ].join("");
    }

    function openDrawer() {
      renderAccount();
      drawer.classList.add("is-open");
      drawer.setAttribute("aria-hidden", "false");
      document.body.classList.add("drawer-open");
      if (menuBtn) menuBtn.setAttribute("aria-expanded", "true");
    }

    function closeDrawer() {
      // 关闭前移除焦点，避免 aria-hidden 冲突
      if (document.activeElement && drawer.contains(document.activeElement)) {
        document.activeElement.blur();
      }
      drawer.classList.remove("is-open");
      drawer.setAttribute("aria-hidden", "true");
      document.body.classList.remove("drawer-open");
      if (menuBtn) menuBtn.setAttribute("aria-expanded", "false");
      if (menuBtn) menuBtn.focus();
    }

    if (menuBtn) {
      menuBtn.setAttribute("aria-controls", "mobileDrawer");
      menuBtn.setAttribute("aria-expanded", "false");
      menuBtn.addEventListener(
        "click",
        function (event) {
          if (window.matchMedia("(max-width: 900px)").matches) {
            event.preventDefault();
            event.stopImmediatePropagation();
            drawer.classList.contains("is-open") ? closeDrawer() : openDrawer();
          }
        },
        true,
      );
    }

    drawer.addEventListener("click", function (event) {
      if (event.target.closest("[data-mobile-drawer-close]")) {
        closeDrawer();
        return;
      }
      if (event.target.closest("[data-sign-modal]")) {
        closeDrawer();
        return;
      }
      if (event.target.closest("a")) {
        closeDrawer();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape" && drawer.classList.contains("is-open")) {
        closeDrawer();
      }
    });
  }

  // ── Hero Swiper ──
  (function () {
    if (typeof Swiper === "undefined") return;
    var heroSwiperEl = document.querySelector(".hero-swiper");
    if (!heroSwiperEl) return;
    new Swiper(".hero-swiper", {
      loop: true,
      autoplay: { delay: 4200, disableOnInteraction: false },
      pagination: { el: ".swiper-pagination", clickable: true },
      navigation: { nextEl: ".hero-arrow.next", prevEl: ".hero-arrow.prev" },
    });
  })();

  // ── Sticky Header on Scroll ──
  (function () {
    var header = document.querySelector(".site-header");
    if (!header) return;
    var ticking = false;
    var spacer = null;

    function updateSticky() {
      var shouldStick = window.scrollY > 10;
      if (shouldStick && !header.classList.contains("is-sticky")) {
        spacer = document.createElement("div");
        spacer.className = "site-header-spacer";
        spacer.style.height = header.offsetHeight + "px";
        header.parentNode.insertBefore(spacer, header.nextSibling);
        header.classList.add("is-sticky");
      } else if (!shouldStick && header.classList.contains("is-sticky")) {
        header.classList.remove("is-sticky");
        if (spacer && spacer.parentNode) {
          spacer.parentNode.removeChild(spacer);
        }
        spacer = null;
      }
      ticking = false;
    }

    window.addEventListener(
      "scroll",
      function () {
        if (!ticking) {
          window.requestAnimationFrame(updateSticky);
          ticking = true;
        }
      },
      { passive: true },
    );
    updateSticky();
  })();

  // ── Search Modal ──
  (function () {
    var searchModal = document.getElementById("searchModal");
    var searchToggle = document.querySelector("[data-search-toggle]");
    var searchInput = document.getElementById("searchModalInput");
    var searchClearBtn = document.getElementById("searchClearBtn");
    var searchForm = searchModal
      ? searchModal.querySelector(".search-modal-form")
      : null;

    function openSearchModal() {
      if (!searchModal) return;
      searchModal.classList.add("is-show");
      searchModal.setAttribute("aria-hidden", "false");
      document.body.classList.add("modal-open");
      setTimeout(function () {
        if (searchInput) searchInput.focus();
      }, 200);
    }

    function closeSearchModal() {
      if (!searchModal) return;
      searchModal.classList.remove("is-show");
      searchModal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
      if (searchInput) {
        searchInput.value = "";
        updateClearBtn();
      }
    }

    function updateClearBtn() {
      if (!searchClearBtn || !searchInput) return;
      searchClearBtn.classList.toggle(
        "is-visible",
        searchInput.value.length > 0,
      );
    }

    if (searchClearBtn && searchInput) {
      searchClearBtn.addEventListener("click", function () {
        searchInput.value = "";
        searchInput.focus();
        updateClearBtn();
      });
      searchInput.addEventListener("input", updateClearBtn);
    }

    if (searchToggle) {
      searchToggle.addEventListener("click", function (event) {
        event.preventDefault();
        openSearchModal();
      });
    }

    if (searchModal) {
      searchModal.addEventListener("click", function (event) {
        if (event.target.closest("[data-search-close]")) {
          closeSearchModal();
        }
      });
      searchModal.addEventListener("click", function (event) {
        var hotLink = event.target.closest(".search-hot-tags a");
        if (hotLink && searchInput && searchForm) {
          event.preventDefault();
          var keyword = hotLink.textContent.trim();
          if (keyword) {
            searchInput.value = keyword;
            updateClearBtn();
            searchForm.submit();
          }
        }
      });
    }

    document.addEventListener("keydown", function (event) {
      if (
        event.key === "Escape" &&
        searchModal &&
        searchModal.classList.contains("is-show")
      ) {
        closeSearchModal();
      }
    });
  })();

  // ── Sign Form AJAX (for standalone page) ──
  function handleSignForm(form, actionType) {
    if (!form) return;

    form.addEventListener("submit", function (event) {
      event.preventDefault();

      var submitBtn = form.querySelector('button[type="submit"]');
      var originalText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';

      var formData = new FormData(form);
      formData.set("action", actionType);

      var nonceMap = {
        onedown_signin: onedownData.signinNonce,
        onedown_signup: onedownData.signupNonce,
        onedown_resetpassword: onedownData.resetNonce,
      };
      if (nonceMap[actionType]) {
        formData.set("_wpnonce", nonceMap[actionType]);
      }

      if (!formData.has("redirect_to")) {
        formData.set("redirect_to", onedownData.userCenterUrl);
      }

      fetch(onedownData.ajaxUrl, {
        method: "POST",
        body: formData,
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (data) {
          if (data.success) {
            showSignToast(data.data.msg, "success");
            if (data.data.redirect_to) {
              setTimeout(function () {
                window.location.href = data.data.redirect_to;
              }, 800);
            }
          } else {
            showSignToast(data.data.msg || "操作失败，请重试", "error");
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        })
        .catch(function () {
          showSignToast("网络错误，请重试", "error");
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
        });
    });
  }

  // Bind sign forms
  handleSignForm(
    document.getElementById("onedown-signin-form"),
    "onedown_signin",
  );
  handleSignForm(
    document.getElementById("onedown-signup-form"),
    "onedown_signup",
  );
  handleSignForm(
    document.getElementById("onedown-resetpassword-form"),
    "onedown_resetpassword",
  );

  // ── Password toggle ──
  document.addEventListener("click", function (event) {
    var toggle = event.target.closest('[data-toggle="password"]');
    if (!toggle) return;
    var input = toggle.parentElement.querySelector("input");
    if (!input) return;
    if (input.type === "password") {
      input.type = "text";
      toggle.innerHTML = '<i class="fa fa-eye-slash"></i>';
    } else {
      input.type = "password";
      toggle.innerHTML = '<i class="fa fa-eye"></i>';
    }
  });

  // ── Favorite Toggle ──
  document.addEventListener("click", function (event) {
    var btn = event.target.closest("[data-fav-toggle]");
    if (!btn) return;
    event.preventDefault();

    if (!window.onedownData || !onedownData.isLoggedIn) {
      window.location.href =
        onedownData.signUrl +
        "&redirect_to=" +
        encodeURIComponent(window.location.href);
      return;
    }

    var postId = btn.getAttribute("data-post-id");
    var icon = btn.querySelector(".fa");
    var text = btn.querySelector("span");

    var formData = new FormData();
    formData.set("action", "onedown_toggle_favorite");
    formData.set("post_id", postId);
    formData.set("_wpnonce", onedownData.favoriteNonce);

    fetch(onedownData.ajaxUrl, {
      method: "POST",
      body: formData,
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data.success) {
          if (data.data.status === "added") {
            btn.classList.add("active");
            if (icon) {
              icon.className = "fa fa-star";
            }
            if (text) {
              text.textContent = "已收藏";
            }
            showVipToast("收藏成功");
          } else {
            btn.classList.remove("active");
            if (icon) {
              icon.className = "fa fa-star-o";
            }
            if (text) {
              text.textContent = "收藏";
            }
            showVipToast("已取消收藏");
          }
        } else {
          showVipToast(data.data && data.data.msg ? data.data.msg : "操作失败");
        }
      })
      .catch(function () {
        showVipToast("网络错误，请重试");
      });
  });

  // ── Like Toggle ──
  document.addEventListener("click", function (event) {
    var btn = event.target.closest("[data-like-toggle]");
    if (!btn) return;
    event.preventDefault();

    if (!window.onedownData || !onedownData.isLoggedIn) {
      window.location.href =
        onedownData.signUrl +
        "&redirect_to=" +
        encodeURIComponent(window.location.href);
      return;
    }

    var postId = btn.getAttribute("data-post-id");
    var icon = btn.querySelector(".fa");
    var text = btn.querySelector("span");
    var countEl = btn.querySelector(".like-count");

    // 乐观更新：立即切换 UI
    var wasActive = btn.classList.contains("active");
    if (wasActive) {
      btn.classList.remove("active");
      if (icon) icon.className = "fa fa-thumbs-o-up";
      if (text) text.textContent = "点赞";
      var cur = parseInt(countEl ? countEl.textContent : 0) || 0;
      if (countEl) countEl.textContent = cur > 0 ? cur - 1 : "";
    } else {
      btn.classList.add("active");
      if (icon) icon.className = "fa fa-thumbs-up";
      if (text) text.textContent = "已赞";
      var cur = parseInt(countEl ? countEl.textContent : 0) || 0;
      if (countEl) countEl.textContent = cur + 1;
    }

    var formData = new FormData();
    formData.set("action", "onedown_toggle_like");
    formData.set("post_id", postId);
    formData.set("_wpnonce", onedownData.likeNonce);

    fetch(onedownData.ajaxUrl, {
      method: "POST",
      body: formData,
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data.success) {
          // 服务端确认后更新准确的计数
          if (countEl && data.data.count !== undefined) {
            countEl.textContent = data.data.count > 0 ? data.data.count : "";
          }
        } else {
          // 失败时回滚
          if (wasActive) {
            btn.classList.add("active");
            if (icon) icon.className = "fa fa-thumbs-up";
            if (text) text.textContent = "已赞";
          } else {
            btn.classList.remove("active");
            if (icon) icon.className = "fa fa-thumbs-o-up";
            if (text) text.textContent = "点赞";
          }
          showVipToast(data.data && data.data.msg ? data.data.msg : "操作失败");
        }
      })
      .catch(function () {
        // 网络错误时回滚
        if (wasActive) {
          btn.classList.add("active");
          if (icon) icon.className = "fa fa-thumbs-up";
          if (text) text.textContent = "已赞";
        } else {
          btn.classList.remove("active");
          if (icon) icon.className = "fa fa-thumbs-o-up";
          if (text) text.textContent = "点赞";
        }
        showVipToast("网络错误，请重试");
      });
  });

  // ── ══════════════════════════════════════
  // 小组件 - 滚动公告轮播
  // ── ══════════════════════════════════════
  function initNoticeCarousel() {
    document.querySelectorAll(".widget_ui_notice").forEach(function (widget) {
      var carousel = widget.querySelector(".notice-carousel");
      if (!carousel) return;
      var items = widget.querySelectorAll(".notice-item");
      var dots = widget.querySelectorAll(".notice-dots .dot");
      if (items.length < 2) return;
      var current = 0;
      var interval = parseInt(carousel.getAttribute("data-interval")) || 4000;
      function showItem(index) {
        items.forEach(function (el) {
          el.classList.remove("active");
        });
        dots.forEach(function (el) {
          el.classList.remove("active");
        });
        items[index].classList.add("active");
        if (dots[index]) dots[index].classList.add("active");
      }
      setInterval(function () {
        current = (current + 1) % items.length;
        showItem(current);
      }, interval);
      // 点击圆点切换
      dots.forEach(function (dot) {
        dot.addEventListener("click", function () {
          current = parseInt(dot.getAttribute("data-index"));
          showItem(current);
        });
      });
    });
  }

  // ── 付费购买弹窗（选择支付方式） ──
  var onedownPayModal = null;

  function createPayModal() {
    if (document.getElementById("onedown-pay-modal")) {
      return document.getElementById("onedown-pay-modal");
    }

    var modal = document.createElement("div");
    modal.id = "onedown-pay-modal";
    modal.className = "onedown-pay-modal";
    modal.setAttribute("aria-hidden", "true");
    var methodsHtml = "";
    payMethods.forEach(function (m) {
      methodsHtml +=
        '<button class="pay-method-option" type="button" data-pay-method="' +
        m.id +
        '"><i class="fa ' +
        m.icon +
        '"></i> ' +
        m.name +
        "</button>";
    });
    modal.innerHTML =
      '<div class="onedown-pay-mask"></div>' +
      '<div class="onedown-pay-dialog" role="dialog">' +
      '<button class="onedown-pay-close" type="button" aria-label="关闭" data-pay-close><i class="fa fa-times"></i></button>' +
      '<div class="onedown-pay-dialog-head">' +
      '<span class="onedown-pay-dialog-icon"><i class="fa fa-credit-card"></i></span>' +
      "<div>" +
      '<span class="onedown-pay-dialog-kicker">ORDER PAYMENT</span>' +
      "<h2>确认支付</h2>" +
      "</div>" +
      "</div>" +
      '<div class="onedown-pay-dialog-body">' +
      '<div class="pay-order-info" data-pay-info></div>' +
      '<div class="pay-method-block"><h4>支付方式</h4><div class="pay-method-options">' +
      methodsHtml +
      "</div></div>" +
      '<p class="pay-status" data-pay-status style="min-height:24px;font-size:13px;color:var(--od-muted);margin:10px 0 0;"></p>' +
      "</div>" +
      '<div class="onedown-pay-dialog-actions">' +
      '<button class="onedown-pay-secondary" type="button" data-pay-close>取消</button>' +
      '<button id="onedown-confirm-pay-btn" class="onedown-pay-primary" type="button">确认并支付</button>' +
      "</div>" +
      "</div>";
    document.body.appendChild(modal);

    modal.addEventListener("click", function (e) {
      var methodBtn = e.target.closest("[data-pay-method]");
      if (methodBtn) {
        modal.querySelectorAll("[data-pay-method]").forEach(function (b) {
          b.classList.remove("active");
        });
        methodBtn.classList.add("active");
        modal.setAttribute(
          "data-selected-pay-method",
          methodBtn.getAttribute("data-pay-method"),
        );
      }
      if (e.target.closest("[data-pay-close]")) {
        closePayModal();
      }
    });
    return modal;
  }

  function closePayModal() {
    var modal = document.getElementById("onedown-pay-modal");
    if (modal) {
      modal.classList.remove("is-show");
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
      // 重置按钮状态，避免 BFCache 恢复后按钮仍禁用
      var confirmBtn = document.getElementById("onedown-confirm-pay-btn");
      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '确认并支付';
      }
      var statusEl = modal.querySelector("[data-pay-status]");
      if (statusEl) {
        statusEl.textContent = "";
      }
      // 清除继续支付标记，避免影响下一次普通支付流程
      modal.removeAttribute("data-repay-order-id");
    }
  }

  // ── 独立二维码扫码支付弹窗 ──
  var qrcodePollTimer = null;

  function createQrcodeModal() {
    if (document.getElementById("onedown-qrcode-modal")) {
      return document.getElementById("onedown-qrcode-modal");
    }

    var modal = document.createElement("div");
    modal.id = "onedown-qrcode-modal";
    modal.className = "onedown-pay-modal";
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML =
      '<div class="onedown-pay-mask"></div>' +
      '<div class="onedown-pay-dialog onedown-qrcode-dialog" role="dialog">' +
      '<button class="onedown-pay-close" type="button" aria-label="关闭" data-qrcode-close><i class="fa fa-times"></i></button>' +
      '<div class="onedown-qrcode-body">' +
      '<div class="qrcode-header">' +
      '<span class="qrcode-pay-icon" data-qrcode-icon><i class="fa fa-credit-card"></i></span>' +
      '<span class="qrcode-pay-name" data-qrcode-name>扫码支付</span>' +
      "</div>" +
      '<div class="qrcode-order-title" data-qrcode-title></div>' +
      '<div class="qrcode-price"><span>￥</span><span data-qrcode-price>0.00</span></div>' +
      '<div class="qrcode-img-wrap">' +
      '<canvas class="qrcode-img" width="180" height="180" data-qrcode-canvas></canvas>' +
      "</div>" +
      '<div class="qrcode-tip" data-qrcode-tip>请使用手机扫码支付</div>' +
      '<div class="qrcode-status" data-qrcode-status></div>' +
      '<button class="qrcode-verify-btn" type="button" data-qrcode-verify style="display:none;">' +
      '<i class="fa fa-check-circle"></i> 已完成支付，验证订单' +
      "</button>" +
      "</div>" +
      "</div>";
    document.body.appendChild(modal);

    modal.addEventListener("click", function (e) {
      if (e.target.closest("[data-qrcode-close]")) {
        closeQrcodeModal();
      }
      if (e.target.closest("[data-qrcode-verify]")) {
        var btn = modal.querySelector("[data-qrcode-verify]");
        var orderId = modal.getAttribute("data-qrcode-order-id");
        if (orderId && btn) {
          verifyQrcodePayment(orderId, btn);
        }
      }
    });
    return modal;
  }

  // ── 前台 Canvas 二维码生成函数 ──
  function onedownGenerateQrCode(canvas, text) {
    if (!canvas || !text || typeof qrcode === "undefined") return;
    try {
      var qr = qrcode(0, "M");
      qr.addData(text);
      qr.make();
      var ctx = canvas.getContext("2d");
      var moduleCount = qr.getModuleCount();
      var cellSize = Math.floor(
        Math.min(canvas.width, canvas.height) / moduleCount,
      );
      ctx.fillStyle = "#ffffff";
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      var offset = Math.floor((canvas.width - moduleCount * cellSize) / 2);
      ctx.save();
      ctx.translate(offset, offset);
      qr.renderTo2dContext(ctx, cellSize);
      ctx.restore();
    } catch (e) {
      console.error("QR code generation failed:", e);
    }
  }

  // ── 初始化下载中心 Canvas 二维码 ──
  function onedownInitDownloadCenterQrcodes() {
    var canvases = document.querySelectorAll(".onedown-dc-qrcode-canvas");
    for (var i = 0; i < canvases.length; i++) {
      var url = canvases[i].getAttribute("data-qr-url");
      if (url) onedownGenerateQrCode(canvases[i], url);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener(
      "DOMContentLoaded",
      onedownInitDownloadCenterQrcodes,
    );
  } else {
    onedownInitDownloadCenterQrcodes();
  }

  function openQrcodeModal(result) {
    var modal = createQrcodeModal();
    var payMethod = result.pay_method || "alipay";

    // 设置支付方式图标和名称
    var iconEl = modal.querySelector("[data-qrcode-icon]");
    var nameEl = modal.querySelector("[data-qrcode-name]");
    if (payMethod === "wechat") {
      iconEl.innerHTML = '<i class="fa fa-wechat"></i>';
      iconEl.className = "qrcode-pay-icon is-wechat";
      nameEl.textContent = "微信支付";
    } else {
      iconEl.innerHTML = '<i class="fa fa-credit-card"></i>';
      iconEl.className = "qrcode-pay-icon is-alipay";
      nameEl.textContent = "支付宝";
    }

    modal.querySelector("[data-qrcode-title]").textContent =
      result.order_title || "";
    modal.querySelector("[data-qrcode-price]").textContent = parseFloat(
      result.amount || 0,
    ).toFixed(2);
    var qrCanvas = modal.querySelector("[data-qrcode-canvas]");
    if (qrCanvas) onedownGenerateQrCode(qrCanvas, result.code_url);
    modal.querySelector("[data-qrcode-tip]").textContent =
      "请使用手机扫码支付，支付成功后自动跳转";
    modal.querySelector("[data-qrcode-status]").textContent = "";
    modal.querySelector("[data-qrcode-verify]").style.display = "none";
    modal.setAttribute("data-qrcode-order-id", result.order_id || "");

    // 关闭所有其他弹窗
    closePayModal();
    var vipModal = document.getElementById("vipPayModal");
    if (vipModal && vipModal.classList.contains("is-show")) {
      vipModal.classList.remove("is-show");
      vipModal.setAttribute("aria-hidden", "true");
    }

    modal.classList.add("is-show");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");

    // 开始轮询
    startQrcodePoll(result.order_id);

    // 10秒后显示手动验证按钮（给异步回调一些时间）
    setTimeout(function () {
      var verifyBtn = modal.querySelector("[data-qrcode-verify]");
      if (verifyBtn && modal.classList.contains("is-show")) {
        verifyBtn.style.display = "inline-flex";
      }
    }, 10000);
  }

  window.openQrcodeModal = openQrcodeModal;

  function closeQrcodeModal() {
    stopQrcodePoll();
    var modal = document.getElementById("onedown-qrcode-modal");
    if (modal) {
      modal.classList.remove("is-show");
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
    }
  }

  function startQrcodePoll(orderId) {
    stopQrcodePoll();
    qrcodePollTimer = setInterval(function () {
      var fd = new FormData();
      fd.set("action", "onedown_check_pay");
      fd.set("order_id", orderId);
      fd.set("_wpnonce", onedownData.payNonce);

      fetch(onedownData.ajaxUrl, { method: "POST", body: fd })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.success && data.data.status === "paid") {
            stopQrcodePoll();
            var modal = document.getElementById("onedown-qrcode-modal");
            if (modal) {
              modal.querySelector("[data-qrcode-tip]").textContent =
                "支付成功！页面即将刷新";
              modal.querySelector("[data-qrcode-tip]").style.color = "#10b981";
            }
            showVipToast("支付成功");
            setTimeout(function () {
              window.location.reload();
            }, 800);
          }
        })
        .catch(function () {});
    }, 2000);
    // 5分钟后停止轮询
    setTimeout(function () {
      stopQrcodePoll();
    }, 300000);
  }

  function stopQrcodePoll() {
    if (qrcodePollTimer) {
      clearInterval(qrcodePollTimer);
      qrcodePollTimer = null;
    }
  }

  function verifyQrcodePayment(orderId, verifyBtn) {
    verifyBtn.disabled = true;
    verifyBtn.textContent = "验证中...";

    var fd = new FormData();
    fd.set("action", "onedown_verify_payment");
    fd.set("order_id", orderId);
    fd.set("_wpnonce", onedownData.payNonce);

    fetch(onedownData.ajaxUrl, { method: "POST", body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        verifyBtn.disabled = false;
        verifyBtn.textContent = "已完成支付，验证订单";
        if (data.success && data.data.paid) {
          stopQrcodePoll();
          var modal = document.getElementById("onedown-qrcode-modal");
          if (modal) {
            modal.querySelector("[data-qrcode-tip]").textContent =
              data.data.msg || "支付成功！页面即将刷新";
            modal.querySelector("[data-qrcode-tip]").style.color = "#10b981";
          }
          showVipToast(data.data.msg || "支付成功");
          setTimeout(function () {
            window.location.reload();
          }, 800);
        } else {
          var errMsg =
            data.data && data.data.msg ? data.data.msg : "未检测到支付";
          var modal = document.getElementById("onedown-qrcode-modal");
          if (modal) {
            modal.querySelector("[data-qrcode-status]").innerHTML =
              '<span style="color:#e74c3c;">' + errMsg + "</span>";
          }
        }
      })
      .catch(function () {
        verifyBtn.disabled = false;
        verifyBtn.textContent = "已完成支付，验证订单";
        var modal = document.getElementById("onedown-qrcode-modal");
        if (modal) {
          modal.querySelector("[data-qrcode-status]").innerHTML =
            '<span style="color:#e74c3c;">网络错误，请重试</span>';
        }
      });
  }

  function handlePayResult(result, confirmBtn, modal, statusEl) {
    if (result.pay_type === "redirect") {
      // 先关闭弹窗再跳转，避免用户按返回后弹窗卡住
      closePayModal();
      setTimeout(function () {
        window.location.href = result.pay_url;
      }, 200);
    } else if (result.pay_type === "qrcode") {
      // 关闭当前支付方式选择弹窗，打开独立二维码弹窗
      result.pay_method =
        modal.getAttribute("data-selected-pay-method") || "alipay";
      result.order_title = modal.querySelector("[data-pay-info]")
        ? modal.querySelector("[data-pay-info]").textContent.trim()
        : "";
      openQrcodeModal(result);
    } else if (result.pay_type === "success") {
      statusEl.textContent = "";
      if (confirmBtn) {
        confirmBtn.disabled = false;
      }
      closePayModal();
      showVipToast("支付成功");
      setTimeout(function () {
        window.location.reload();
      }, 800);
    } else if (result.pay_type === "offline") {
      statusEl.innerHTML = result.msg || "订单已创建，请线下付款";
      if (confirmBtn) {
        confirmBtn.disabled = false;
      }
      if (result.offline_info) {
        statusEl.innerHTML += "<br><small>" + result.offline_info + "</small>";
      }
    } else {
      throw new Error(result.msg || "支付发起失败");
    }
  }

  document.addEventListener("click", function (e) {
    var payBtn = e.target.closest("[data-pay-btn]");
    if (!payBtn) return;

    // 强制清理所有残留弹窗状态（解决 BFCache 返回后状态卡死）
    document.body.classList.remove("modal-open");
    closeQrcodeModal();
    var existingVipModal = document.getElementById("vipPayModal");
    if (existingVipModal) {
      closeVipModal(existingVipModal);
    }
    var existingPayModal = document.getElementById("onedown-pay-modal");
    if (existingPayModal) {
      existingPayModal.classList.remove("is-show");
      existingPayModal.setAttribute("aria-hidden", "true");
      var oldConfirmBtn = document.getElementById("onedown-confirm-pay-btn");
      if (oldConfirmBtn) {
        oldConfirmBtn.disabled = false;
        oldConfirmBtn.innerHTML = '确认并支付';
      }
    }

    if (!onedownPayModal) {
      onedownPayModal = createPayModal();
    }

    var postId = payBtn.getAttribute("data-post-id");
    var orderType = payBtn.getAttribute("data-order-type") || "";
    if (!orderType) {
      var payBox = payBtn.closest(".onedown-pay-box");
      if (payBox) {
        orderType = payBox.classList.contains("order-type-2")
          ? "download"
          : "read";
      }
    }

    var firstMethod = payMethods.length > 0 ? payMethods[0].id : "alipay";
    onedownPayModal.setAttribute("data-selected-pay-method", firstMethod);
    onedownPayModal
      .querySelectorAll("[data-pay-method]")
      .forEach(function (b, idx) {
        b.classList.toggle("active", idx === 0);
      });

    document.querySelector("#onedown-pay-modal [data-pay-info]").innerHTML = "";
    document.querySelector("#onedown-pay-modal [data-pay-status]").textContent =
      "";
    // 重置确认按钮状态
    var confirmBtn = document.getElementById("onedown-confirm-pay-btn");
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = '确认并支付';
    }
    onedownPayModal.classList.add("is-show");
    onedownPayModal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
  });

  document.addEventListener("click", function (e) {
    var confirmBtn = e.target.closest("#onedown-confirm-pay-btn");
    if (!confirmBtn) return;

    var modal = document.getElementById("onedown-pay-modal");
    if (!modal) return;

    // 继续支付流程由另一个 handler 处理，此处跳过
    if (modal.getAttribute("data-repay-order-id")) return;

    var payBtn = document.querySelector("[data-pay-btn]");
    if (!payBtn) return;

    var postId = payBtn.getAttribute("data-post-id");
    var orderType = payBtn.getAttribute("data-order-type") || "";
    if (!orderType) {
      var payBox = payBtn.closest(".onedown-pay-box");
      if (payBox) {
        orderType = payBox.classList.contains("order-type-2")
          ? "download"
          : "read";
      }
    }

    var payMethod =
      modal.getAttribute("data-selected-pay-method") || payMethods[0].id;
    var statusEl = modal.querySelector("[data-pay-status]");

    confirmBtn.disabled = true;
    confirmBtn.textContent = "处理中...";
    statusEl.textContent = "正在创建订单...";

    var fd = new FormData();
    fd.set("action", "onedown_initiate_pay");
    fd.set("order_type", "post_" + orderType);
    fd.set("pay_method", payMethod);
    fd.set("post_id", postId);
    fd.set("_wpnonce", onedownData.payNonce);

    fetch(onedownData.ajaxUrl, { method: "POST", body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data.success) {
          var result = data.data;
          document.querySelector(
            "#onedown-pay-modal [data-pay-info]",
          ).innerHTML =
            '<div class="pay-order-row"><span>订单号</span><strong>' +
            result.order_id +
            "</strong></div>" +
            '<div class="pay-order-row"><span>金额</span><strong class="pay-amount">￥' +
            parseFloat(result.amount || 0).toFixed(2) +
            "</strong></div>";
          handlePayResult(result, confirmBtn, modal, statusEl);
        } else {
          confirmBtn.disabled = false;
          confirmBtn.innerHTML = '确认并支付';
          statusEl.textContent =
            data.data && data.data.msg ? data.data.msg : "创建订单失败";
        }
      })
      .catch(function () {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '确认并支付';
        statusEl.textContent = "网络错误，请重试";
      });
  });

  // ── 继续支付（用户中心的待付款订单） ──
  document.addEventListener("click", function (e) {
    var repayBtn = e.target.closest(".order-repay-btn");
    if (!repayBtn) return;

    var orderId = repayBtn.getAttribute("data-order-id");
    if (!orderId) return;

    // 强制清理所有残留弹窗状态
    document.body.classList.remove("modal-open");
    closePayModal();
    closeQrcodeModal();
    var existingVipModal = document.getElementById("vipPayModal");
    if (existingVipModal) {
      closeVipModal(existingVipModal);
    }

    if (!onedownPayModal) {
      onedownPayModal = createPayModal();
    }

    var firstMethod = payMethods.length > 0 ? payMethods[0].id : "alipay";
    onedownPayModal.setAttribute("data-selected-pay-method", firstMethod);
    onedownPayModal
      .querySelectorAll("[data-pay-method]")
      .forEach(function (b, idx) {
        b.classList.toggle("active", idx === 0);
      });

    // 显示订单信息
    var orderItem = repayBtn.closest(".order-item");
    var orderTitle = orderItem
      ? orderItem.querySelector(".order-title")?.textContent || "订单"
      : "订单";
    var orderAmount = orderItem
      ? orderItem.querySelector(".order-meta-amount")?.textContent || ""
      : "";
    onedownPayModal.querySelector("[data-pay-info]").innerHTML =
      '<div class="pay-order-row"><span>订单</span><strong>' +
      orderTitle +
      "</strong></div>" +
      '<div class="pay-order-row"><span>金额</span><strong class="pay-amount">' +
      orderAmount +
      "</strong></div>";
    onedownPayModal.querySelector("[data-pay-status]").textContent = "";

    var confirmBtn = document.getElementById("onedown-confirm-pay-btn");
    if (confirmBtn) {
      confirmBtn.disabled = false;
      confirmBtn.innerHTML = '确认并支付';
    }

    // 存储当前订单ID供确认按钮使用
    onedownPayModal.setAttribute("data-repay-order-id", orderId);

    onedownPayModal.classList.add("is-show");
    onedownPayModal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
  });

  // 继续支付 - 确认按钮处理（复用 onedown-confirm-pay-btn）
  document.addEventListener("click", function (e) {
    var confirmBtn = e.target.closest("#onedown-confirm-pay-btn");
    if (!confirmBtn) return;

    var modal = document.getElementById("onedown-pay-modal");
    if (!modal) return;

    // 仅当 modal 有 data-repay-order-id 时才走继续支付流程
    var orderId = modal.getAttribute("data-repay-order-id");
    if (!orderId) return;

    var payMethod =
      modal.getAttribute("data-selected-pay-method") || payMethods[0].id;
    var statusEl = modal.querySelector("[data-pay-status]");

    confirmBtn.disabled = true;
    confirmBtn.textContent = "处理中...";
    statusEl.textContent = "正在发起支付...";

    var fd = new FormData();
    fd.set("action", "onedown_repay_order");
    fd.set("order_id", orderId);
    fd.set("pay_method", payMethod);
    fd.set("_wpnonce", onedownData.payNonce);

    fetch(onedownData.ajaxUrl, { method: "POST", body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (data.success) {
          var result = data.data;
          if (result.pay_type === "redirect") {
            closePayModal();
            modal.removeAttribute("data-repay-order-id");
            setTimeout(function () {
              window.location.href = result.pay_url;
            }, 200);
          } else if (result.pay_type === "qrcode") {
            modal.removeAttribute("data-repay-order-id");
            closePayModal();
            result.pay_method = payMethod;
            result.order_title = orderId;
            openQrcodeModal(result);
          } else if (result.pay_type === "success") {
            statusEl.textContent = "";
            confirmBtn.disabled = false;
            closePayModal();
            modal.removeAttribute("data-repay-order-id");
            showVipToast("支付成功");
            setTimeout(function () {
              window.location.reload();
            }, 800);
          } else if (result.pay_type === "offline") {
            statusEl.innerHTML = result.msg || "订单已创建，请线下付款";
            confirmBtn.disabled = false;
            if (result.offline_info) {
              statusEl.innerHTML +=
                "<br><small>" + result.offline_info + "</small>";
            }
          } else {
            throw new Error(result.msg || "支付发起失败");
          }
        } else {
          throw new Error(
            data.data && data.data.msg ? data.data.msg : "支付发起失败",
          );
        }
      })
      .catch(function (err) {
        statusEl.textContent = err.message || "操作失败，请重试";
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '确认并支付';
      });
  });

  // ── 处理页面恢复（BFCache 返回时清理弹窗状态） ──
  window.addEventListener("pageshow", function () {
    closePayModal();
    closeQrcodeModal();
    var vipModal = document.getElementById("vipPayModal");
    if (vipModal) {
      closeVipModal(vipModal);
    }
    document.body.classList.remove("modal-open");
  });

  // ── 短代码密码验证（AJAX 无刷新） ──
  document.addEventListener("click", function (e) {
    var submitBtn = e.target.closest("[data-pw-submit]");
    if (!submitBtn) return;

    var box = submitBtn.closest(".onedown-password-box");
    if (!box) return;

    var input = box.querySelector(".onedown-password-input");
    var errorEl = box.querySelector(".onedown-password-error");
    var password = input ? input.value.trim() : "";

    if (!password) {
      input.focus();
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = "验证中...";

    var fd = new FormData();
    fd.set("action", "onedown_verify_password");
    fd.set("post_id", box.getAttribute("data-post-id"));
    fd.set("password", password);
    fd.set("token", box.getAttribute("data-pw-token"));

    fetch(onedownData.ajaxUrl, { method: "POST", body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        submitBtn.disabled = false;
        submitBtn.textContent = "提交";
        if (data.success) {
          submitBtn.textContent = "验证成功，刷新中...";
          setTimeout(function () {
            location.reload();
          }, 300);
        } else {
          errorEl.style.display = "block";
          errorEl.textContent =
            data.data && data.data.msg ? data.data.msg : "密码错误，请重新输入";
          input.value = "";
          input.focus();
        }
      })
      .catch(function () {
        submitBtn.disabled = false;
        submitBtn.textContent = "提交";
        errorEl.style.display = "block";
        errorEl.textContent = "网络错误，请重试";
      });
  });

  // ── 评论可见短代码：点击跳转到评论区 ──
  document.addEventListener("click", function (e) {
    var scrollBtn = e.target.closest("[data-scroll-comment]");
    if (!scrollBtn) return;
    e.preventDefault();
    var form = document.getElementById("commentform");
    if (form) {
      form.scrollIntoView({ behavior: "smooth", block: "center" });
      // 聚焦到评论输入框
      setTimeout(function () {
        var textarea = form.querySelector("textarea");
        if (textarea) textarea.focus();
      }, 400);
    }
  });

  // ── AJAX 评论提交 ──
  (function () {
    var form = document.getElementById("commentform");
    if (!form) return;

    form.addEventListener("submit", function (e) {
      // 不阻止默认行为，除非有 onedownData
      if (!window.onedownData) return;

      e.preventDefault();

      var submitBtn = form.querySelector('button[type="submit"]');
      var origText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 提交中...';

      var fd = new FormData(form);
      fd.set("action", "onedown_post_comment");
      fd.set("_wpnonce", onedownData.commentNonce);

      fetch(onedownData.ajaxUrl, { method: "POST", body: fd })
        .then(function (r) {
          return r.json();
        })
        .then(function (data) {
          if (data.success) {
            var commentHtml = data.data.html;
            var commentCount = data.data.comment_count;
            var status = data.data.status;

            // 更新评论数
            var countEl = document.querySelector(
              ".comment-card h2 .comment-count",
            );
            if (countEl) {
              countEl.textContent = "(" + commentCount + ")";
            }

            // 插入评论
            var list = document.querySelector(".comment-list");
            var parentId = parseInt(
              form.querySelector("[name=comment_parent]")
                ? form.querySelector("[name=comment_parent]").value
                : 0,
            );

            if (status === "approved") {
              if (!list) {
                // 首次评论，创建 comment-list 容器
                var commentCard =
                  form.closest(".comment-card") ||
                  document.getElementById("comments");
                if (commentCard) {
                  list = document.createElement("div");
                  list.className = "comment-list";
                  commentCard.appendChild(list);
                }
              }

              if (list) {
                if (parentId) {
                  // 回复评论：找到父评论的子容器，没有则创建
                  var parentItem = document.getElementById(
                    "comment-" + parentId,
                  );
                  if (parentItem) {
                    var children =
                      parentItem.querySelector(".comment-children");
                    if (!children) {
                      children = document.createElement("div");
                      children.className = "comment-children";
                      parentItem.appendChild(children);
                    }
                    children.insertAdjacentHTML("beforeend", commentHtml);
                  } else {
                    list.insertAdjacentHTML("beforeend", commentHtml);
                  }
                } else {
                  list.insertAdjacentHTML("beforeend", commentHtml);
                }
              }
            } else if (status === "pending") {
              // 待审核，显示提示
              var tip = document.createElement("div");
              tip.className = "comment-pending-tip";
              tip.innerHTML = '<i class="fa fa-clock-o"></i> ' + data.data.msg;
              form.parentNode.insertBefore(tip, form.nextSibling);
              setTimeout(function () {
                tip.classList.add("is-hide");
                setTimeout(function () {
                  tip.remove();
                }, 400);
              }, 4000);
            }

            // 重置表单
            form.reset();

            // 如果是回复模式，取消回复（调用 WP 的 cancel_reply）
            var cancelBtn = document.getElementById(
              "cancel-comment-reply-link",
            );
            if (cancelBtn) {
              cancelBtn.click();
            }

            showSignToast(data.data.msg, "success");
          } else {
            showSignToast(
              data.data && data.data.msg ? data.data.msg : "评论提交失败",
              "error",
            );
          }
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        })
        .catch(function () {
          showSignToast("网络错误，请重试", "error");
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        });
    });
  })();

  // ── 复制提取码 ──
  document.addEventListener("click", function (e) {
    var copyBtn = e.target.closest("[data-copy]");
    if (!copyBtn) return;
    var text = copyBtn.getAttribute("data-copy");
    if (!text) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function () {
        var orig = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fa fa-check"></i> 已复制';
        setTimeout(function () {
          copyBtn.innerHTML = orig;
        }, 1500);
      });
    } else {
      // Fallback
      var ta = document.createElement("textarea");
      ta.value = text;
      ta.style.position = "fixed";
      ta.style.opacity = "0";
      document.body.appendChild(ta);
      ta.select();
      document.execCommand("copy");
      document.body.removeChild(ta);
      var orig = copyBtn.innerHTML;
      copyBtn.innerHTML = '<i class="fa fa-check"></i> 已复制';
      setTimeout(function () {
        copyBtn.innerHTML = orig;
      }, 1500);
    }
  });

  // ── 深色/浅色模式切换 ──
  function initThemeToggle() {
    function apply(theme) {
      document.documentElement.setAttribute("data-theme", theme);
      try {
        localStorage.setItem("onedown-theme", theme);
      } catch (e) {}
    }
    document.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-theme-toggle]");
      if (!btn) return;
      var current =
        document.documentElement.getAttribute("data-theme") === "dark"
          ? "dark"
          : "light";
      apply(current === "dark" ? "light" : "dark");
    });
  }

  // ── ══════════════════════════════════════
  // 分享弹窗
  // ── ══════════════════════════════════════
  function ensureShareModal() {
    var modal = document.getElementById("shareModal");
    if (modal) return modal;

    modal = document.createElement("div");
    modal.className = "vip-pay-modal";
    modal.id = "shareModal";
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML = [
      '<div class="vip-pay-mask"></div>',
      '<div class="vip-pay-dialog share-modal-dialog" role="dialog" aria-modal="true" style="max-width:480px;">',
      '<button class="vip-pay-close" type="button" aria-label="关闭" data-share-close><i class="fa fa-times"></i></button>',
      '<div class="vip-pay-head">',
      '<span class="vip-pay-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 14px 28px rgba(99,102,241,.22);"><i class="fa fa-share-alt"></i></span>',
      '<div><span class="vip-pay-kicker">SHARE</span><h2>分享到</h2><p>选择一种方式分享这篇文章</p></div>',
      "</div>",
      '<div class="vip-pay-body">',
      '<div class="share-platforms" data-share-platforms></div>',
      '<div class="share-qrcode-wrap" data-share-qrcode style="display:none;">',
      '<div class="share-qrcode-head">',
      '<i class="fa fa-wechat" style="font-size:20px;color:#07c160;"></i>',
      "<span>微信扫码分享</span>",
      "</div>",
      '<canvas class="share-qrcode-canvas" width="180" height="180" data-share-qr-canvas></canvas>',
      '<p class="share-qrcode-tip">打开微信扫一扫，分享给好友或朋友圈</p>',
      "</div>",
      "</div>",
      '<div class="vip-pay-actions">',
      '<button class="vip-pay-secondary" type="button" data-share-close>关闭</button>',
      "</div>",
      "</div>",
    ].join("");
    document.body.appendChild(modal);

    modal.addEventListener("click", function (e) {
      if (e.target.closest("[data-share-close]")) {
        closeShareModal();
        return;
      }
      var platformBtn = e.target.closest("[data-share-platform]");
      if (!platformBtn) return;
      var platform = platformBtn.getAttribute("data-share-platform");
      handleShare(platform, modal);
    });

    return modal;
  }

  function closeShareModal() {
    var modal = document.getElementById("shareModal");
    if (modal) {
      modal.classList.remove("is-show");
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
      modal.querySelector("[data-share-platforms]").style.display = "";
      modal.querySelector("[data-share-qrcode]").style.display = "none";
    }
  }

  function handleShare(platform, modal) {
    var url = encodeURIComponent(window.location.href);
    var title = encodeURIComponent(document.title.replace(/ – | - .*$/, ""));
    var shareUrl = "";
    var platformsWrap = modal.querySelector("[data-share-platforms]");
    var qrcodeWrap = modal.querySelector("[data-share-qrcode]");

    switch (platform) {
      case "wechat":
        platformsWrap.style.display = "none";
        qrcodeWrap.style.display = "block";
        var canvas = modal.querySelector("[data-share-qr-canvas]");
        if (canvas && typeof qrcode !== "undefined") {
          onedownGenerateQrCode(canvas, window.location.href);
        }
        return;
      case "weibo":
        shareUrl =
          "https://service.weibo.com/share/share.php?url=" +
          url +
          "&title=" +
          title;
        break;
      case "qq":
        shareUrl =
          "https://connect.qq.com/widget/shareqq/index.html?url=" +
          url +
          "&title=" +
          title;
        break;
      case "qzone":
        shareUrl =
          "https://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=" +
          url +
          "&title=" +
          title;
        break;
      case "copy":
        var text = window.location.href;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(function () {
            showVipToast("链接已复制到剪贴板");
          });
        } else {
          var ta = document.createElement("textarea");
          ta.value = text;
          ta.style.position = "fixed";
          ta.style.opacity = "0";
          document.body.appendChild(ta);
          ta.select();
          document.execCommand("copy");
          document.body.removeChild(ta);
          showVipToast("链接已复制到剪贴板");
        }
        closeShareModal();
        return;
    }

    if (shareUrl) {
      window.open(shareUrl, "_blank", "width=640,height=480");
    }
  }

  function renderSharePlatforms(modal) {
    var wrap = modal.querySelector("[data-share-platforms]");
    var platforms = [
      { id: "wechat", icon: "fa-wechat", name: "微信", color: "#07c160" },
      { id: "weibo", icon: "fa-weibo", name: "微博", color: "#e6162d" },
      { id: "qq", icon: "fa-qq", name: "QQ好友", color: "#12b7f2" },
      { id: "qzone", icon: "fa-star", name: "QQ空间", color: "#f5b800" },
      { id: "copy", icon: "fa-link", name: "复制链接", color: "#6366f1" },
    ];
    wrap.innerHTML = platforms
      .map(function (p) {
        return (
          '<button class="share-platform-btn" type="button" data-share-platform="' +
          p.id +
          '" style="--share-color:' +
          p.color +
          ';">' +
          '<span class="share-platform-icon"><i class="fa ' +
          p.icon +
          '"></i></span>' +
          '<span class="share-platform-name">' +
          p.name +
          "</span>" +
          "</button>"
        );
      })
      .join("");
  }

  // 点击分享按钮
  document.addEventListener("click", function (e) {
    var trigger = e.target.closest("[data-share-toggle]");
    if (!trigger) return;
    e.preventDefault();

    var modal = ensureShareModal();
    renderSharePlatforms(modal);
    modal.querySelector("[data-share-platforms]").style.display = "";
    modal.querySelector("[data-share-qrcode]").style.display = "none";

    modal.classList.add("is-show");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
  });

  // Escape 关闭
  document.addEventListener("keydown", function (e) {
    var modal = document.getElementById("shareModal");
    if (e.key === "Escape" && modal && modal.classList.contains("is-show")) {
      closeShareModal();
    }
  });

  // BFCache 返回清理
  var _origPageshow = window.addEventListener("pageshow", function () {
    closeShareModal();
  });

  // ── Init ──
  createMobileDrawer();
  initNoticeCarousel();
  initThemeToggle();

  // ── PJAX Pagination for Section Card ──
  (function () {
    function loadPage(url) {
      var card = document.querySelector(".section-card");
      if (!card) {
        window.location.href = url;
        return;
      }

      card.classList.add("is-loading");

      fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } })
        .then(function (r) {
          return r.text();
        })
        .then(function (html) {
          var parser = new DOMParser();
          var doc = parser.parseFromString(html, "text/html");
          var newCard = doc.querySelector(".section-card");

          if (!newCard) {
            window.location.href = url;
            return;
          }

          card.outerHTML = newCard.outerHTML;
          history.pushState({ pjax: true, url: url }, "", url);

          // 重新初始化懒加载
          if (window.lazySizes) {
            lazySizes.init();
          }
        })
        .catch(function () {
          window.location.href = url;
        });
    }

    // 代理点击分页链接
    document.addEventListener("click", function (e) {
      var link = e.target.closest(".section-card .post-pagination a.page-btn");
      if (!link || link.classList.contains("active")) return;
      e.preventDefault();
      var url = link.getAttribute("href");
      if (!url) return;
      loadPage(url);
    });

    // 浏览器前进/后退
    window.addEventListener("popstate", function (e) {
      if (e.state && e.state.pjax) {
        loadPage(e.state.url);
      }
    });
  })();
})();

// ── 固定悬浮按钮交互 ──
(function () {
  "use strict";

  // 返回顶部
  var ontop = document.querySelector(".float-btn.ontop");
  if (ontop) {
    function getScrollTop() {
      return (
        window.pageYOffset ||
        document.documentElement.scrollTop ||
        document.body.scrollTop ||
        0
      );
    }

    ontop.addEventListener("click", function (e) {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: "smooth" });
    });

    // 滚动监听显隐
    var ticking = false;
    function updateOntop() {
      if (getScrollTop() > 160) {
        ontop.classList.add("show");
      } else {
        ontop.classList.remove("show");
      }
      ticking = false;
    }

    window.addEventListener(
      "scroll",
      function () {
        if (!ticking) {
          window.requestAnimationFrame(updateOntop);
          ticking = true;
        }
      },
      { passive: true },
    );
    window.addEventListener("resize", updateOntop, { passive: true });
    window.addEventListener("load", updateOntop);
    updateOntop();
  }

  // 滚动时隐藏（scrolling-hide）
  var floatRight = document.querySelector(".float-right.scrolling-hide");
  if (floatRight) {
    var hideTimer = null;
    var scrollTimer = null;

    window.addEventListener(
      "scroll",
      function () {
        document.body.classList.add("scroll-ing");
        clearTimeout(hideTimer);
        clearTimeout(scrollTimer);

        scrollTimer = setTimeout(function () {
          document.body.classList.remove("scroll-ing");
        }, 500);
      },
      { passive: true },
    );
  }

  // hover-show 触发的下拉菜单（用于微信等桌面端hover效果）
  // 对于移动端，点击 hover-show 按钮切换 dropdown 显隐
  if (window.matchMedia("(max-width: 680px)").matches) {
    document.addEventListener("click", function (e) {
      var btn = e.target.closest(".float-btn.hover-show");
      if (!btn) return;
      e.preventDefault();
      var dropdown = btn.querySelector(".float-dropdown");
      if (!dropdown) return;
      var isVisible = dropdown.style.opacity === "1";
      // 关闭所有其他 dropdown
      document
        .querySelectorAll(".float-btn.hover-show .float-dropdown")
        .forEach(function (el) {
          el.style.opacity = "0";
          el.style.visibility = "hidden";
          el.style.pointerEvents = "none";
        });
      if (!isVisible) {
        dropdown.style.opacity = "1";
        dropdown.style.visibility = "visible";
        dropdown.style.pointerEvents = "auto";
      }
    });
    // 点击其他地方关闭
    document.addEventListener("click", function (e) {
      if (!e.target.closest(".float-btn.hover-show")) {
        document
          .querySelectorAll(".float-btn.hover-show .float-dropdown")
          .forEach(function (el) {
            el.style.opacity = "0";
            el.style.visibility = "hidden";
            el.style.pointerEvents = "none";
          });
      }
    });
  }

  // ── 下载资源 — 复制提取码 ──
  (function () {
    // Toast 提示
    function odShowToast(msg, duration) {
      duration = duration || 2000;
      var toast = document.querySelector(".od-toast");
      if (!toast) {
        toast = document.createElement("div");
        toast.className = "od-toast";
        document.body.appendChild(toast);
      }
      toast.textContent = msg;
      toast.classList.add("show");
      if (toast._timer) clearTimeout(toast._timer);
      toast._timer = setTimeout(function () {
        toast.classList.remove("show");
      }, duration);
    }

    // 复制文本
    function odCopyText(text, successMsg) {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(
          function () {
            odShowToast(successMsg || "已复制到剪贴板");
          },
          function () {
            fallbackCopy(text, successMsg);
          },
        );
      } else {
        fallbackCopy(text, successMsg);
      }
    }

    function fallbackCopy(text, successMsg) {
      var ta = document.createElement("textarea");
      ta.value = text;
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      ta.style.top = "-9999px";
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand("copy");
        odShowToast(successMsg || "已复制到剪贴板");
      } catch (e) {
        odShowToast("复制失败，请手动复制");
      }
      document.body.removeChild(ta);
    }

    // 暴露复制函数到全局，供内联 onclick 使用
    window.odCopyText = odCopyText;

    // 复制提取码
    document.addEventListener("click", function (e) {
      var btn = e.target.closest(".od-btn-copy");
      if (!btn) return;
      var text = btn.getAttribute("data-copy");
      if (!text) return;
      odCopyText(text, "提取码已复制");

      // 按钮反馈
      btn.classList.add("copied");
      setTimeout(function () {
        btn.classList.remove("copied");
      }, 1500);
    });

    // ── 推广链接复制 ──
    document.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-referral-copy]");
      if (!btn) return;
      var input = document.getElementById("referralLinkInput");
      if (!input) return;
      var text = input.value;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
          .writeText(text)
          .then(function () {
            referralCopySuccess(btn);
          })
          .catch(function () {
            referralCopyFallback(input, btn);
          });
      } else {
        referralCopyFallback(input, btn);
      }
    });

    function referralCopyFallback(input, btn) {
      input.select();
      input.setSelectionRange(0, 99999);
      try {
        document.execCommand("copy");
        referralCopySuccess(btn);
      } catch (e) {}
    }

    function referralCopySuccess(btn) {
      if (!btn) return;
      btn.innerHTML = '<i class="fa fa-check"></i> 已复制';
      setTimeout(function () {
        btn.innerHTML = '<i class="fa fa-copy"></i> 复制链接';
      }, 2000);
      referralShowToast("链接复制成功");
    }

    function referralShowToast(msg) {
      var toast = document.getElementById("referralToast");
      if (!toast) {
        toast = document.createElement("div");
        toast.id = "referralToast";
        toast.className = "od-toast";
        document.body.appendChild(toast);
      }
      toast.textContent = msg;
      toast.classList.add("show");
      clearTimeout(toast._timer);
      toast._timer = setTimeout(function () {
        toast.classList.remove("show");
      }, 1800);
    }
  })();

  // ── 联系信息弹窗（搭建同款/QQ/微信/QQ群/微信群） ──
  // 采用与 VIP 支付弹窗一致的 UI 风格
  (function () {
    var contactModal = null;

    function ensureContactModal() {
      if (document.getElementById("contactInfoModal")) {
        return document.getElementById("contactInfoModal");
      }

      var modal = document.createElement("div");
      modal.id = "contactInfoModal";
      modal.className = "vip-pay-modal";
      modal.setAttribute("aria-hidden", "true");
      modal.innerHTML =
        '<div class="vip-pay-mask"></div>' +
        '<div class="vip-pay-dialog" role="dialog" aria-modal="true" style="max-width:440px;">' +
        '<button class="vip-pay-close" type="button" aria-label="关闭" data-contact-close><i class="fa fa-times"></i></button>' +
        '<div class="vip-pay-head" data-contact-head></div>' +
        '<div class="vip-pay-body" data-contact-body></div>' +
        '<div class="vip-pay-actions" data-contact-actions></div>' +
        "</div>";
      document.body.appendChild(modal);

      modal.addEventListener("click", function (e) {
        if (e.target.closest("[data-contact-close]")) {
          closeContactModal();
        }
      });

      return modal;
    }

    function openContactModal(btn) {
      var type = btn.getAttribute("data-contact-modal");
      var modal = ensureContactModal();
      var headEl = modal.querySelector("[data-contact-head]");
      var bodyEl = modal.querySelector("[data-contact-body]");
      var actionsEl = modal.querySelector("[data-contact-actions]");
      var headHtml = "",
        bodyHtml = "",
        actionsHtml = "";

      switch (type) {
        case "build_similar":
          var desc =
            btn.getAttribute("data-desc") ||
            "想要搭建同款网站？我们提供专业的网站搭建服务。";
          headHtml =
            '<span class="vip-pay-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);box-shadow:0 14px 28px rgba(99,102,241,.22);"><i class="fa fa-code"></i></span>' +
            '<div><span class="vip-pay-kicker">BUILD SITE</span><h2>搭建同款网站</h2><p>专业的网站搭建服务，从零到一全面支持</p></div>';
          bodyHtml =
            '<div style="padding:8px 0 4px;line-height:1.8;color:var(--od-text);font-size:14px;">' +
            desc +
            "</div>";
          actionsHtml =
            '<button class="vip-pay-secondary" type="button" data-contact-close>关闭</button>' +
            '<button class="vip-pay-primary" type="button" data-contact-close>我知道了</button>';
          break;

        case "qq_group":
          var group = btn.getAttribute("data-group") || "";
          var groupImg = btn.getAttribute("data-img") || "";
          headHtml =
            '<span class="vip-pay-icon" style="background:linear-gradient(135deg,#12b7f2,#0d8ec9);box-shadow:0 14px 28px rgba(18,183,242,.22);"><i class="fa fa-group"></i></span>' +
            '<div><span class="vip-pay-kicker">QQ GROUP</span><h2>QQ群</h2><p>加入 QQ 群与其他用户交流</p></div>';
          bodyHtml = '<div style="text-align:center;padding:12px 0;">';
          if (group) {
            bodyHtml +=
              '<div style="font-size:24px;font-weight:700;color:var(--od-primary);margin-bottom:12px;">群号：' +
              group +
              "</div>";
          }
          if (groupImg) {
            bodyHtml +=
              '<div style="display:inline-flex;padding:8px;background:#fff;border-radius:12px;border:1px solid var(--od-line);margin-bottom:8px;">' +
              '<img src="' +
              groupImg +
              '" alt="QQ群二维码" style="width:180px;height:180px;display:block;border-radius:8px;"></div>';
          }
          bodyHtml +=
            '<p style="margin:8px 0 0;font-size:13px;color:var(--od-muted);">扫码或搜索群号加入QQ群</p></div>';
          actionsHtml = "";
          if (group) {
            actionsHtml +=
              '<button class="vip-pay-secondary" type="button" data-copy="' +
              group +
              '" style="position:static;"><i class="fa fa-copy"></i> 复制群号</button>';
          }
          actionsHtml +=
            '<button class="vip-pay-primary" type="button" data-contact-close>知道了</button>';
          break;

        case "wechat_group":
          var wcImg = btn.getAttribute("data-img") || "";
          var wcName = btn.getAttribute("data-name") || "";
          headHtml =
            '<span class="vip-pay-icon" style="background:linear-gradient(135deg,#07c160,#05a34e);box-shadow:0 14px 28px rgba(7,193,96,.22);"><i class="fa fa-wechat"></i></span>' +
            '<div><span class="vip-pay-kicker">WECHAT GROUP</span><h2>' +
            (wcName || "微信群") +
            "</h2><p>扫码加入微信群，获取最新资讯</p></div>";
          bodyHtml =
            '<div style="text-align:center;padding:12px 0;">' +
            '<div style="display:inline-flex;padding:8px;background:#fff;border-radius:12px;border:1px solid var(--od-line);">' +
            '<img src="' +
            wcImg +
            '" alt="微信群二维码" style="width:200px;height:200px;display:block;border-radius:8px;"></div>' +
            '<p style="margin:12px 0 0;font-size:13px;color:var(--od-muted);">长按或扫码加入微信群</p></div>';
          actionsHtml =
            '<button class="vip-pay-secondary" type="button" data-contact-close>关闭</button>';
          break;
      }

      headEl.innerHTML = headHtml;
      bodyEl.innerHTML = bodyHtml;
      actionsEl.innerHTML = actionsHtml;
      modal.classList.add("is-show");
      modal.setAttribute("aria-hidden", "false");
      document.body.classList.add("modal-open");
    }

    function closeContactModal() {
      var modal = document.getElementById("contactInfoModal");
      if (modal) {
        modal.classList.remove("is-show");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
      }
    }

    // 点击悬浮按钮打开联系弹窗
    document.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-contact-modal]");
      if (!btn) return;
      e.preventDefault();

      // 关闭其他弹出层
      document.body.classList.remove("modal-open");
      var vipModal = document.getElementById("vipPayModal");
      if (vipModal) vipModal.classList.remove("is-show");
      var payModal = document.getElementById("onedown-pay-modal");
      if (payModal) payModal.classList.remove("is-show");
      var qrcodeModal = document.getElementById("onedown-qrcode-modal");
      if (qrcodeModal) qrcodeModal.classList.remove("is-show");

      openContactModal(btn);
    });

    // 弹窗内复制按钮（使用 vip-pay-secondary 样式）
    document.addEventListener("click", function (e) {
      var copyBtn = e.target.closest("[data-copy]");
      if (!copyBtn) return;
      // 仅在联系弹窗内处理
      var modal = document.getElementById("contactInfoModal");
      if (!modal || !modal.classList.contains("is-show")) return;
      if (!modal.contains(copyBtn)) return;
      var text = copyBtn.getAttribute("data-copy");
      if (!text) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
          var orig = copyBtn.innerHTML;
          copyBtn.innerHTML = '<i class="fa fa-check"></i> 已复制';
          setTimeout(function () {
            copyBtn.innerHTML = orig;
          }, 1500);
        });
      }
    });

    // 点击蒙层或关闭按钮
    document.addEventListener("click", function (e) {
      var modal = document.getElementById("contactInfoModal");
      if (!modal || !modal.classList.contains("is-show")) return;
      if (e.target.closest("[data-contact-close]")) {
        closeContactModal();
      }
    });

    // Escape 关闭
    document.addEventListener("keydown", function (e) {
      var modal = document.getElementById("contactInfoModal");
      if (e.key === "Escape" && modal && modal.classList.contains("is-show")) {
        closeContactModal();
      }
    });

    // BFCache 返回清理
    window.addEventListener("pageshow", function () {
      closeContactModal();
    });

    /* ── 联系表单提交 ── */
    var contactForm = document.getElementById("contactForm");
    if (contactForm) {
      contactForm.addEventListener("submit", function (e) {
        e.preventDefault();
        var btn = document.getElementById("contactSubmitBtn");
        var feedback = document.getElementById("contactFeedback");
        if (!btn || !feedback) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 发送中...';
        feedback.className = "contact-feedback";
        feedback.textContent = "";

        var fd = new FormData(contactForm);

        fetch(onedownData.ajaxUrl, { method: "POST", body: fd })
          .then(function (r) {
            return r.json();
          })
          .then(function (data) {
            if (data.success) {
              feedback.className = "contact-feedback success";
              feedback.textContent = data.data.msg || "发送成功！";
              contactForm.reset();
            } else {
              feedback.className = "contact-feedback error";
              feedback.textContent = data.data.msg || "发送失败，请重试";
            }
          })
          .catch(function () {
            feedback.className = "contact-feedback error";
            feedback.textContent = "网络错误，请稍后重试";
          })
          .finally(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-send"></i> 发送消息';
          });
      });
    }
  })();

  // ── 文字排行榜 Tab 切换（AJAX 动态加载 + PJAX 兼容） ──
  (function () {
    var loadingPanels = {};

    function loadRankPanel(panel, tab, number, nonce) {
      var panelId = panel.getAttribute("data-rank-panel") || tab;

      // 防止重复加载
      if (loadingPanels[panelId]) return;
      loadingPanels[panelId] = true;

      // 添加 loading 状态
      panel.classList.add("loading");

      var xhr = new XMLHttpRequest();
      xhr.open("POST", onedownData.ajaxUrl, true);
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
      xhr.onload = function () {
        if (xhr.status === 200) {
          try {
            var res = JSON.parse(xhr.responseText);
            if (res.success) {
              panel.innerHTML = res.data.html;
              panel.setAttribute("data-rank-loaded", "true");
            }
          } catch (e) {
            // 解析失败，保留占位
          }
        }
        panel.classList.remove("loading");
        delete loadingPanels[panelId];
      };
      xhr.onerror = function () {
        panel.classList.remove("loading");
        delete loadingPanels[panelId];
      };
      xhr.send(
        "action=od_rank_panel&tab=" +
          encodeURIComponent(tab) +
          "&number=" +
          encodeURIComponent(number) +
          "&nonce=" +
          encodeURIComponent(nonce),
      );
    }

    function initTextRankTabs() {
      document.querySelectorAll(".text-rank-tabs").forEach(function (tabs) {
        tabs.querySelectorAll("button[data-rank-tab]").forEach(function (btn) {
          btn.addEventListener("click", function (e) {
            var tab = btn.getAttribute("data-rank-tab");
            if (!tab) return;

            // 切换按钮 active 状态
            tabs
              .querySelectorAll("button[data-rank-tab]")
              .forEach(function (b) {
                b.classList.remove("active");
              });
            btn.classList.add("active");

            // 切换到对应面板
            var parent = tabs.parentNode;
            if (!parent) return;
            parent
              .querySelectorAll(".text-rank-panel")
              .forEach(function (panel) {
                var isTarget =
                  panel.getAttribute("data-rank-panel") === tab;
                panel.classList.toggle("active", isTarget);

                // 目标面板且未加载 → AJAX 加载
                if (
                  isTarget &&
                  panel.getAttribute("data-rank-loaded") !== "true"
                ) {
                  var number = panel.getAttribute("data-rank-number") || 5;
                  var nonce = panel.getAttribute("data-rank-nonce") || "";
                  loadRankPanel(panel, tab, number, nonce);
                }
              });
          });
        });
      });
    }

    // 初始执行
    initTextRankTabs();

    // PJAX 内容替换后重新绑定（用 MutationObserver 监听 DOM 变化）
    var observer = new MutationObserver(function () {
      initTextRankTabs();
    });
    observer.observe(document.body, { childList: true, subtree: true });
  })();

  // ── 代码复制 ──
  (function () {
    function initCodeCopy() {
      document.querySelectorAll(".article-content pre").forEach(function (pre) {
        if (pre.querySelector(".code-copy-btn")) return;
        var code = pre.querySelector("code");
        if (!code || !code.textContent.trim()) return;

        var btn = document.createElement("button");
        btn.className = "code-copy-btn";
        btn.type = "button";
        btn.innerHTML = '<i class="fa fa-copy"></i> 复制';
        btn.setAttribute("aria-label", "复制代码");

        btn.addEventListener("click", function (e) {
          e.stopPropagation();
          var text = code.textContent;
          if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(
              function () {
                showCopied(btn);
              },
              function () {
                fallbackCopyCode(text, btn);
              },
            );
          } else {
            fallbackCopyCode(text, btn);
          }
        });

        pre.style.position = "relative";
        pre.appendChild(btn);
      });
    }

    function showCopied(btn) {
      btn.classList.add("is-copied");
      btn.innerHTML = '<i class="fa fa-check"></i> 已复制';
      setTimeout(function () {
        btn.classList.remove("is-copied");
        btn.innerHTML = '<i class="fa fa-copy"></i> 复制';
      }, 2000);
    }

    function fallbackCopyCode(text, btn) {
      var ta = document.createElement("textarea");
      ta.value = text;
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      ta.style.top = "-9999px";
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand("copy");
        showCopied(btn);
      } catch (e) {
        btn.innerHTML = '<i class="fa fa-times"></i> 复制失败';
        setTimeout(function () {
          btn.innerHTML = '<i class="fa fa-copy"></i> 复制';
        }, 2000);
      }
      document.body.removeChild(ta);
    }

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initCodeCopy);
    } else {
      initCodeCopy();
    }

    // PJAX / 动态内容加载后重新绑定
    var copyObserver = new MutationObserver(function () {
      initCodeCopy();
    });
    copyObserver.observe(document.body, { childList: true, subtree: true });
  })();
})();
