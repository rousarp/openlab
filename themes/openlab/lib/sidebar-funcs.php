<?php

/**
 * Sidebar based functionality
 */
function openlab_bp_sidebar($type, $mobile_dropdown = false, $extra_classes = '') {

    $pull_classes = ($type == 'groups' ? ' pull-right' : '');
    $pull_classes .= ($mobile_dropdown ? ' mobile-dropdown' : '');

    echo '<div id="sidebar" class="sidebar col-sm-6 col-xs-24' . $pull_classes . ' type-' . $type . $extra_classes . '"><div class="sidebar-wrapper">';

    switch ($type) {
        case 'actions':
            openlab_group_sidebar();
            break;
        case 'members':
            bp_get_template_part('members/single/sidebar');
            break;
        case 'register':
            openlab_buddypress_register_actions();
            break;
        case 'groups':
            get_sidebar('group-archive');
            break;
        case 'about':
            $args = array(
                'theme_location' => 'aboutmenu',
                'container' => 'div',
                'container_id' => 'about-menu',
                'menu_class' => 'sidebar-nav clearfix'
            );
            echo '<h2 class="sidebar-title hidden-xs">O projektu</h2>';
            echo '<div class="sidebar-block hidden-xs">';
            wp_nav_menu($args);
            echo '</div>';

			echo '<h2 class="sidebar-title hidden-xs">Další informace</h2>';
            echo '<div class="sidebar-block sidebar-block-learnmore hidden-xs">';
			openlab_learnmore_sidebar();
            echo '</div>';

            break;
        case 'help':
            get_sidebar('help');
            break;
        default:
            get_sidebar();
    }

    echo '</div></div>';
}

/**
 * Mobile sidebar - for when a piece of the sidebar needs to appear above the content in the mobile space
 * @param type $type
 */
function openlab_bp_mobile_sidebar($type) {

    switch ($type) {
        case 'members':
            echo '<div id="sidebar-mobile" class="sidebar group-single-item mobile-dropdown clearfix">';
            openlab_member_sidebar_menu(true);
            echo '</div>';
            break;
        case 'about':
            echo '<div id="sidebar-mobile" class="sidebar clearfix mobile-dropdown">';
            $args = array(
                'theme_location' => 'aboutmenu',
                'container' => 'div',
                'container_id' => 'about-mobile-menu',
                'menu_class' => 'sidebar-nav clearfix'
            );
            echo '<div class="sidebar-block">';
            wp_nav_menu($args);
            echo '</div>';
            echo '</div>';
            break;
    }
}

/**
 * Output the sidebar content for a single group
 */
function openlab_group_sidebar($mobile = false) {

    if (bp_has_groups()) : while (bp_groups()) : bp_the_group();
            ?>
            <div class="sidebar-widget sidebar-widget-wrapper" id="portfolio-sidebar-widget">
                <h2 class="sidebar-header group-single top-sidebar-header">Pracovní nástroje
                    <?php // echo ucwords(groups_get_groupmeta(bp_get_group_id(), "wds_group_type")) . ' Materials'; ?>
                </h2>
                <div class="wrapper-block">
                    <?php openlab_bp_group_site_pages(); ?>
                </div>
                <div id="sidebar-menu-wrapper" class="sidebar-menu-wrapper wrapper-block">
                    <div id="item-buttons" class="profile-nav sidebar-block clearfix">
                        <ul class="sidebar-nav clearfix">
                            <?php bp_get_options_nav(); ?>
                            <?php echo openlab_get_group_profile_mobile_anchor_links(); ?>
                        </ul>
                    </div><!-- #item-buttons -->
                </div>
                <?php do_action('bp_group_options_nav') ?>
            </div>
            <?php
        endwhile;
    endif;
}

/**
 * 'Learn More' sidebar for About pages.
 */
function openlab_learnmore_sidebar() {
	?>
	<div class="learn-more-sidebar">
		<p>Co je nového na našem webu naleznete na webu <a href="<?PHP echo get_site_url( 1, '/openroad/', "http"); ?>">Open Road</a></p>
		<p>Publikujte na serveru <a href="http://otevrenenoviny.cz/">Otevřené noviny</a></p>
		<p>Připojte se k serveru <a href="<?PHP echo get_site_url( 1, '/otrevrenyurad/', "http"); ?>">Otevřený úřad</a></p>
	</div>
	<?php
}

/**
 * Member pages sidebar - modularized for easier parsing of mobile menus
 * @param type $mobile
 */
function openlab_member_sidebar_menu($mobile = false) {

    if (!$dud = bp_displayed_user_domain()) {
        $dud = bp_loggedin_user_domain(); // will always be the logged in user on my-*
    }

    if ($mobile) {
        $classes = 'visible-xs';
    } else {
        $classes = 'hidden-xs';
    }

    if (is_user_logged_in() && openlab_is_my_profile()) :
        ?>

        <div id="item-buttons<?php echo ($mobile ? '-mobile' : '') ?>" class="mol-menu sidebar-block <?php echo $classes; ?>">

            <ul class="sidebar-nav clearfix">

                <li class="sq-bullet <?php if (bp_is_user_activity()) : ?>selected-page<?php endif ?> mol-profile my-profile"><a href="<?php echo $dud ?>">Můj profil</a></li>

                <li class="sq-bullet <?php if (bp_is_user_settings()) : ?>selected-page<?php endif ?> mol-settings my-settings"><a href="<?php echo $dud . bp_get_settings_slug() ?>/">Nastavení</a></li>

                <?php if (openlab_user_has_portfolio(bp_displayed_user_id()) && (!openlab_group_is_hidden(openlab_get_user_portfolio_id()) || openlab_is_my_profile() || groups_is_user_member(bp_loggedin_user_id(), openlab_get_user_portfolio_id()) )) : ?>

                    <li id="portfolios-groups-li<?php echo ($mobile ? '-mobile' : '') ?>" class="visible-xs mobile-anchor-link"><a href="#portfolio-sidebar-inline-widget" id="portfolios<?php echo ($mobile ? '-mobile' : '') ?>">Moje <?php echo (xprofile_get_field_data('Account Type', bp_displayed_user_id()) == 'Student' ? 'ePortfolio' : 'Portfolio') ?></a></li>

                <?php else: ?>

                    <li id="portfolios-groups-li<?php echo ($mobile ? '-mobile' : '') ?>" class="visible-xs mobile-anchor-link"><a href="#portfolio-sidebar-inline-widget" id="portfolios<?php echo ($mobile ? '-mobile' : '') ?>">Vytvořit <?php echo (xprofile_get_field_data('Account Type', bp_displayed_user_id()) == 'Student' ? 'ePortfolio' : 'Portfolio') ?></a></li>

                <?php endif; ?>

                <li class="sq-bullet <?php if (is_page('my-courses') || openlab_is_create_group('course')) : ?>selected-page<?php endif ?> mol-courses my-courses"><a href="<?php echo bp_get_root_domain() ?>/my-courses/">Moje kurzy</a></li>

                <li class="sq-bullet <?php if (is_page('my-projects') || openlab_is_create_group('project')) : ?>selected-page<?php endif ?> mol-projects my-projects"><a href="<?php echo bp_get_root_domain() ?>/my-projects/">Moje projekty</a></li>

                <li class="sq-bullet <?php if (is_page('my-clubs') || openlab_is_create_group('club')) : ?>selected-page<?php endif ?> mol-clubs my-clubs"><a href="<?php echo bp_get_root_domain() ?>/my-clubs/">Moje skupiny</a></li>

                <?php /* Get a friend request count */ ?>
                <?php if (bp_is_active('friends')) : ?>
                    <?php
                    $request_ids = friends_get_friendship_request_user_ids(bp_loggedin_user_id());
                    $request_count = intval(count((array) $request_ids));
                    ?>

                    <li class="sq-bullet <?php if (bp_is_user_friends()) : ?>selected-page<?php endif ?> mol-friends my-friends"><a href="<?php echo $dud . bp_get_friends_slug() ?>/">Moji přátelé <?php echo openlab_get_menu_count_mup($request_count); ?></a></li>
                <?php endif; ?>

                <?php /* Get an unread message count */ ?>
                <?php if (bp_is_active('messages')) : ?>
                    <?php $message_count = bp_get_total_unread_messages_count() ?>

                    <li class="sq-bullet <?php if (bp_is_user_messages()) : ?>selected-page<?php endif ?> mol-messages my-messages"><a href="<?php echo $dud . bp_get_messages_slug() ?>/inbox/">Moje zprávy <?php echo openlab_get_menu_count_mup($message_count); ?></a></li>
                <?php endif; ?>

                <?php /* Get an invitation count */ ?>
                <?php if (bp_is_active('groups')) : ?>
                    <?php
                    $invites = groups_get_invites_for_user();
                    $invite_count = isset($invites['total']) ? (int) $invites['total'] : 0;
                    ?>

                    <li class="sq-bullet <?php if (bp_is_current_action('invites') || bp_is_current_action('sent-invites') || bp_is_current_action('invite-new-members')) : ?>selected-page<?php endif ?> mol-invites my-invites"><a href="<?php echo $dud . bp_get_groups_slug() ?>/invites/">Mé pozvánky <?php echo openlab_get_menu_count_mup($invite_count); ?></a></li>
                <?php endif ?>

                <?php /* odkaz na DW otázky a odpovědi */ ?>
                <?php //if (bp_is_active('messages')) : ?><?php if (1) : ?>
                    <?php $question_count = dw_question_user_count() ?>
                    <li class="sq-bullet <?php if ($question_count) : ?>selected-page<?php endif ?> mol-question my-question"><a href="<?php echo bp_get_dw_questions_user_slug() ?><?php //echo $dud . 'dwqa-questions' ?>">Moje otázky <?php echo openlab_get_menu_count_mup($question_count); ?></a></li>
                <?php endif; ?>






                <?php
                // My Dashboard points to the my-sites.php Dashboard panel for this user. However,
                // this panel only works if looking at a site where the user has Dashboard-level
                // permissions. So we have to find a valid site for the logged in user.
                $primary_site_id = get_user_meta(bp_loggedin_user_id(), 'primary_blog', true);
                $primary_site_url = set_url_scheme(get_blog_option($primary_site_id, 'siteurl'));
                ?>

                <li class="sq-bullet mol-dashboard my-dashboard"><a href="<?php echo $primary_site_url . '/wp-admin/my-sites.php' ?>">Nástěnka <span class="fa fa-chevron-circle-right" aria-hidden="true"></span></a></li>

            </ul>

        </div>

    <?php else : ?>

        <div id="item-buttons<?php echo ($mobile ? '-mobile' : '') ?>" class="mol-menu sidebar-block <?php echo $classes; ?>">

            <ul class="sidebar-nav clearfix">

                <li class="sq-bullet <?php if (bp_is_user_activity()) : ?>selected-page<?php endif ?> mol-profile"><a href="<?php echo $dud ?>/">Profil</a></li>

                <?php if (openlab_user_has_portfolio(bp_displayed_user_id()) && (!openlab_group_is_hidden(openlab_get_user_portfolio_id()) || openlab_is_my_profile() || groups_is_user_member(bp_loggedin_user_id(), openlab_get_user_portfolio_id()) )) : ?>

                    <li id="portfolios-groups-li<?php echo ($mobile ? '-mobile' : '') ?>" class="visible-xs mobile-anchor-link"><a href="#portfolio-sidebar-inline-widget" id="portfolios<?php echo ($mobile ? '-mobile' : '') ?>"><?php echo (xprofile_get_field_data('Account Type', bp_displayed_user_id()) == 'Student' ? 'ePortfolio' : 'Portfolio') ?></a></li>

                <?php endif; ?>

                <?php /* Current page highlighting requires the GET param */ ?>
                <?php $current_group_view = isset($_GET['type']) ? $_GET['type'] : ''; ?>

                <li class="sq-bullet <?php if (bp_is_user_groups() && 'course' == $current_group_view) : ?>selected-page<?php endif ?> mol-courses"><a href="<?php echo $dud . bp_get_groups_slug() ?>/?type=course">Kurzy</a></li>

                <li class="sq-bullet <?php if (bp_is_user_groups() && 'project' == $current_group_view) : ?>selected-page<?php endif ?> mol-projects"><a href="<?php echo $dud . bp_get_groups_slug() ?>/?type=project">Projekty</a></li>

                <li class="sq-bullet <?php if (bp_is_user_groups() && 'club' == $current_group_view) : ?>selected-page<?php endif ?> mol-club"><a href="<?php echo $dud . bp_get_groups_slug() ?>/?type=club">Skupiny</a></li>

                <li class="sq-bullet <?php if (bp_is_user_friends()) : ?>selected-page<?php endif ?> mol-friends"><a href="<?php echo $dud . bp_get_friends_slug() ?>/">Přátelé</a></li>

            </ul>

        </div>

    <?php
    endif;
}

/**
 * Member pages sidebar blocks (portfolio link) - modularized for easier parsing of mobile menus
 */
function openlab_members_sidebar_blocks($mobile_hide = false) {

    $block_classes = '';

    if ($mobile_hide) {
        $block_classes = ' hidden-xs';
    }

    if (is_user_logged_in() && openlab_is_my_profile()):
        ?>
        <h2 class="sidebar-header top-sidebar-header hidden-xs">Můj OpenLab</h2>
    <?php else: ?>
        <h2 class="sidebar-header top-sidebar-header hidden-xs">Profil uživatele</h2>
    <?php endif; ?>

    <?php if (openlab_user_has_portfolio(bp_displayed_user_id()) && (!openlab_group_is_hidden(openlab_get_user_portfolio_id()) || openlab_is_my_profile() || groups_is_user_member(bp_loggedin_user_id(), openlab_get_user_portfolio_id()) )) : ?>

        <?php if (!$mobile_hide): ?>
            <?php if (is_user_logged_in() && openlab_is_my_profile()): ?>
                <h2 class="sidebar-header top-sidebar-header visible-xs">Můj <?php echo (xprofile_get_field_data('Account Type', bp_displayed_user_id()) == 'Student' ? 'ePortfolio' : 'Portfolio') ?></h2>
            <?php else: ?>
                <h2 class="sidebar-header top-sidebar-header visible-xs">Uživatelův <?php echo (xprofile_get_field_data('Account Type', bp_displayed_user_id()) == 'Student' ? 'ePortfolio' : 'Portfolio') ?></h2>
            <?php endif; ?>
        <?php endif; ?>

        <?php /* Abstract the displayed user id, so that this function works properly on my-* pages */ ?>
        <?php $displayed_user_id = bp_is_user() ? bp_displayed_user_id() : bp_loggedin_user_id() ?>

        <div class="sidebar-block<?php echo $block_classes ?>">

            <ul class="sidebar-sublinks portfolio-sublinks inline-element-list">

                <li class="portfolio-profile-link bold">
                    <a class="bold no-deco" href="<?php openlab_user_portfolio_url() ?>">
                        <?php echo (is_user_logged_in() && openlab_is_my_profile() ? 'Moje ' : 'Navštívit '); ?>
                        <?php openlab_portfolio_label('user_id=' . $displayed_user_id . '&case=upper'); ?> stránky <span class="fa fa-chevron-circle-right" aria-hidden="true"></span>
                    </a>
                </li>

                <li class="portfolio-site-link">
                    <a href="<?php openlab_user_portfolio_profile_url() ?>">Profil</a>
                    <?php if (openlab_is_my_profile() && openlab_user_portfolio_site_is_local()) : ?>
                        | <a class="portfolio-dashboard-link" href="<?php openlab_user_portfolio_url() ?>/wp-admin">Nástěnka</a>
                    <?php endif ?>
                </li>

            </ul>
        </div>

    <?php elseif (openlab_is_my_profile() && !bp_is_group_create()) : ?>
        <?php /* Don't show the 'Create a Portfolio' link during group (ie Portfolio) creation */ ?>
        <div class="sidebar-widget" id="portfolio-sidebar-widget">

            <?php if (is_user_logged_in() && openlab_is_my_profile()): ?>
                <h2 class="sidebar-header top-sidebar-header visible-xs">Moje <?php echo (xprofile_get_field_data('Account Type', bp_displayed_user_id()) == 'Student' ? 'ePortfolio' : 'Portfolio') ?></h2>
            <?php endif; ?>

            <div class="sidebar-block<?php echo $block_classes ?>">
                <ul class="sidebar-sublinks portfolio-sublinks inline-element-list">
                    <li>
                        <?php $displayed_user_id = bp_is_user() ? bp_displayed_user_id() : bp_loggedin_user_id(); ?>
                        <a class="bold" href="<?php openlab_portfolio_creation_url() ?>">+ Vytvořit <?php openlab_portfolio_label('leading_a=1&case=upper&user_id=' . $displayed_user_id) ?></a>
                    </li>
                </ul>
            </div>
        </div>

        <?php
    endif;
}
