<header class="site-header<?php echo _pz('header_fixed', true) ? ' is-sticky' : ''; ?>">
    <div class="nav-shell">
        <?php
        $logo_data = _pz('logo');
        $logo_url = $logo_data && !empty($logo_data['url']) ? $logo_data['url'] : '';
        $logo_width = intval(_pz('logo_width', 150));
        ?>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="brand" aria-label="<?php bloginfo('name'); ?>">
            <?php if ($logo_url) : ?>
                <img class="brand-logo" src="<?php echo esc_url($logo_url); ?>" alt="<?php bloginfo('name'); ?>"
                    width="<?php echo esc_attr($logo_width); ?>"
                    style="<?php echo !_pz('logo_show', true) ? 'display:none' : ''; ?>">
            <?php endif; ?>
            <?php if (!_pz('logo_show', true) || !$logo_url) : ?>
                <span class="brand-mark"><i class="fa fa-bolt"></i></span>
                <span class="brand-name">
                    <strong><?php bloginfo('name'); ?></strong>
                    <span><?php echo esc_html(get_bloginfo('description')); ?></span>
                </span>
            <?php endif; ?>
        </a>

        <nav class="main-nav" id="mainNav" aria-label="主导航">
            <?php if (has_nav_menu('primary')) :
                class Onedown_Walker_Nav_Div extends Walker_Nav_Menu
                {
                    public function start_lvl(&$output, $depth = 0, $args = array())
                    {
                        $output .= '<div class="nav-dropdown">';
                    }

                    public function end_lvl(&$output, $depth = 0, $args = array())
                    {
                        $output .= '</div>';
                    }

                    public function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
                    {
                        $classes   = empty($item->classes) ? array() : (array) $item->classes;
                        $has_children = in_array('menu-item-has-children', $classes, true);
                        $is_active = in_array('current-menu-item', $classes, true);

                        // Extract fa-icon class from CSS classes
                        $icon_class = '';
                        foreach ($classes as $c) {
                            if (strpos($c, 'fa-') === 0 || strpos($c, 'fa ') === 0) {
                                $icon_class = $c;
                                break;
                            }
                        }
                        // Also check title attribute for icon
                        if (empty($icon_class) && !empty($item->attr_title) && strpos($item->attr_title, 'fa-') !== false) {
                            $icon_class = esc_attr($item->attr_title);
                        }

                        if ($depth === 0) {
                            if ($has_children) {
                                $output .= '<div class="nav-item has-dropdown' . ($is_active ? ' active' : '') . '">';
                            }
                            $output .= '<a ' . onedown_seo_link_attrs($item->url, array('title' => wp_strip_all_tags($item->title), 'aria_label' => wp_strip_all_tags($item->title), 'class' => $is_active ? 'active' : '')) . '>';
                            if ($icon_class) {
                                $output .= '<i class="' . esc_attr($icon_class) . '"></i>';
                            }
                            $output .= '<span>' . wp_kses_post($item->title) . '</span>';
                            if ($has_children) {
                                $output .= '<i class="fa fa-angle-down nav-caret"></i>';
                            }
                            $output .= '</a>';
                        } else {
                            $output .= '<a ' . onedown_seo_link_attrs($item->url, array('title' => wp_strip_all_tags($item->title), 'aria_label' => wp_strip_all_tags($item->title))) . '>';
                            if ($icon_class) {
                                $output .= '<i class="' . esc_attr($icon_class) . '"></i>';
                            }
                            $output .= '<span>' . wp_kses_post($item->title) . '</span>';
                            if ($has_children) {
                                $output .= '<i class="fa fa-angle-down nav-caret"></i>';
                            }
                            $output .= '</a>';
                        }
                    }

                    public function end_el(&$output, $item, $depth = 0, $args = array())
                    {
                        $classes = empty($item->classes) ? array() : (array) $item->classes;
                        if ($depth === 0 && in_array('menu-item-has-children', $classes, true)) {
                            $output .= '</div>';
                        }
                    }
                }

                wp_nav_menu(
                    array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => 'main-nav-inner',
                        'items_wrap'     => '%3$s',
                        'depth'          => 2,
                        'walker'         => new Onedown_Walker_Nav_Div(),
                        'echo'           => true,
                    )
                );
            else : ?>
                <a class="<?php echo is_front_page() ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/')); ?>"><i
                        class="fa fa-home"></i><span>首页</span></a>
                <div class="nav-item has-dropdown">
                    <a href="<?php echo esc_url(home_url('/member')); ?>"><i
                            class="fa fa-shopping-bag"></i><span>购买主题</span><span class="nav-badge">惠</span><i
                            class="fa fa-angle-down nav-caret"></i></a>
                    <div class="nav-dropdown">
                        <a href="<?php echo esc_url(home_url('/member')); ?>"><i
                                class="fa fa-diamond"></i><span>授权购买</span></a>
                        <a href="<?php echo esc_url(home_url('/member')); ?>"><i
                                class="fa fa-gift"></i><span>优惠套餐</span></a>
                        <a href="<?php echo esc_url(home_url('/member')); ?>"><i
                                class="fa fa-list-alt"></i><span>订单查询</span></a>
                    </div>
                </div>
                <div class="nav-item has-dropdown">
                    <a href="<?php echo esc_url(home_url('/category')); ?>"><i
                            class="fa fa-comments"></i><span>社区</span><span class="nav-badge">NEW</span><i
                            class="fa fa-angle-down nav-caret"></i></a>
                    <div class="nav-dropdown">
                        <a href="<?php echo esc_url(home_url('/category')); ?>"><i
                                class="fa fa-comments-o"></i><span>全部话题</span></a>
                        <a href="<?php echo esc_url(home_url('/category')); ?>"><i
                                class="fa fa-question-circle-o"></i><span>问答求助</span></a>
                        <a href="<?php echo esc_url(home_url('/category')); ?>"><i
                                class="fa fa-line-chart"></i><span>热门排行</span></a>
                    </div>
                </div>
                <a href="<?php echo esc_url(home_url('/detail')); ?>"><i class="fa fa-desktop"></i><span>官方演示</span></a>
                <a href="<?php echo esc_url(home_url('/member')); ?>"><i class="fa fa-magic"></i><span>定制服务</span></a>
                <a href="<?php echo esc_url(home_url('/category')); ?>"><i
                        class="fa fa-lightbulb-o"></i><span>需求提交</span></a>
                <div class="nav-item has-dropdown">
                    <a href="<?php echo esc_url(home_url('/category')); ?>"><i class="fa fa-book"></i><span>主题教程</span><i
                            class="fa fa-angle-down nav-caret"></i></a>
                    <div class="nav-dropdown">
                        <a href="<?php echo esc_url(home_url('/category')); ?>"><i
                                class="fa fa-file-text-o"></i><span>安装配置</span></a>
                        <a href="<?php echo esc_url(home_url('/category')); ?>"><i
                                class="fa fa-th-large"></i><span>模块布局</span></a>
                        <a href="<?php echo esc_url(home_url('/category')); ?>"><i
                                class="fa fa-shopping-cart"></i><span>商城功能</span></a>
                        <a href="<?php echo esc_url(home_url('/category')); ?>"><i
                                class="fa fa-plug"></i><span>扩展功能</span></a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>

        <div class="nav-actions">
            <?php if (_pz('header_theme_toggle', true)) : ?>
                <button class="icon-btn theme-toggle-btn" type="button" data-theme-toggle aria-label="切换深色/浅色模式"><i
                        class="fa fa-moon-o"></i><i class="fa fa-sun-o"></i></button>
            <?php endif; ?>
            <?php if (_pz('header_search', true)) : ?>
                <a class="icon-btn nav-search-btn <?php echo is_search() ? 'active' : ''; ?>" href="javascript:;"
                    data-search-toggle aria-label="搜索"><i class="fa fa-search"></i></a>
            <?php endif; ?>
            <?php if (_pz('header_user_menu', true)) :
                if (is_user_logged_in()) :
                    $current_user = wp_get_current_user();
                    $user_name    = $current_user->display_name;
                    $user_initial = strtoupper(substr($user_name ?: 'U', 0, 1));
                    $vip_info     = onedown_get_user_vip_info($current_user->ID);
            ?>
                    <div class="nav-user-card">
                        <a class="nav-avatar" href="<?php echo esc_url(onedown_user_center_url()); ?>" aria-label="用户中心">
                            <img class="nav-avatar-img"
                                src="<?php echo esc_url(get_avatar_url($current_user->ID, array('size' => 32))); ?>"
                                alt="<?php echo esc_attr($user_name); ?>"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <span class="nav-avatar-img init-only"><?php echo esc_html($user_initial); ?></span>
                        </a>
                        <div class="nav-user-menu" aria-label="用户菜单">
                            <div class="nav-user-head">
                                <img class="nav-avatar-img large"
                                    src="<?php echo esc_url(get_avatar_url($current_user->ID, array('size' => 48))); ?>"
                                    alt="<?php echo esc_attr($user_name); ?>"
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <span class="nav-avatar-img large init-only"><?php echo esc_html($user_initial); ?></span>
                                <div>
                                    <div class="nav-user-name-row">
                                        <strong class="nav-user-name"><?php echo esc_html($user_name); ?></strong>
                                        <span class="nav-vip-info <?php echo esc_attr($vip_info['vip_class']); ?>"><i
                                                class="fa fa-diamond"></i>
                                            <?php echo esc_html($vip_info['vip_name']); ?></span>
                                    </div>
                                    <div class="nav-user-meta">
                                        <em
                                            class="nav-vip-expire"><?php echo esc_html('到期：' . ($vip_info['expire_date'] ?: '永久')); ?></em>
                                    </div>
                                </div>
                            </div>
                            <a href="<?php echo esc_url(onedown_user_center_url(array('tab' => 'dashboard'))); ?>"><i
                                    class="fa fa-dashboard"></i><span>用户中心</span></a>
                            <a href="<?php echo esc_url(onedown_vip_page_url()); ?>"><i
                                    class="fa fa-diamond"></i><span>VIP会员</span></a>
                            <a href="<?php echo esc_url(onedown_user_center_url(array('tab' => 'orders'))); ?>"><i
                                    class="fa fa-list-alt"></i><span>我的订单</span></a>
                            <a href="<?php echo esc_url(onedown_user_center_url(array('tab' => 'downloads'))); ?>"><i
                                    class="fa fa-download"></i><span>下载记录</span></a>
                            <a href="<?php echo esc_url(onedown_user_center_url(array('tab' => 'favorites'))); ?>"><i
                                    class="fa fa-star-o"></i><span>我的收藏</span></a>
                            <a href="<?php echo esc_url(onedown_user_center_url(array('tab' => 'profile'))); ?>"><i
                                    class="fa fa-cog"></i><span>账号设置</span></a>
                            <?php if (current_user_can('manage_options')) : ?>
                                <a href="<?php echo esc_url(admin_url('/')); ?>"><i
                                        class="fa fa-tachometer"></i><span>后台管理</span></a>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><i
                                    class="fa fa-sign-out"></i><span>退出登录</span></a>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="nav-user-card">
                        <a class="nav-avatar" href="javascript:;" data-sign-modal aria-label="登录">
                            <img class="nav-avatar-img" src="<?php echo esc_url(onedown_asset_img('avatar/default.png')); ?>"
                                alt="游客" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <span class="nav-avatar-img init-only">G</span>
                            <span class="nav-user-text"><strong>游客</strong><em>点击登录</em></span>
                            <i class="fa fa-angle-down"></i>
                        </a>
                        <div class="nav-user-menu" aria-label="用户菜单">
                            <div class="nav-user-head guest">
                                <div class="guest-card">
                                    <strong>欢迎来到本站</strong>
                                    <p>登录后可查看订单、下载资源、收藏内容等</p>
                                    <div class="guest-actions">
                                        <a class="guest-btn primary" href="javascript:;" data-sign-modal><i
                                                class="fa fa-sign-in"></i> 登录</a>
                                        <a class="guest-btn" href="javascript:;" data-sign-modal="signup"><i
                                                class="fa fa-user-plus"></i> 注册</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?><?php endif; // end header_user_menu 
                                    ?>
                    <button class="menu-btn" id="menuBtn" type="button" aria-label="展开菜单"><i class="fa fa-bars"></i></button>
        </div>
    </div>
</header>