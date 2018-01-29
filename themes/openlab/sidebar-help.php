<?php /**
 * The Help Sidebar
 *
 */ ?>

<h2 class="sidebar-title hidden-xs">Nápověda</h2>
<div id="sidebar-menu-wrapper" class="sidebar-menu-wrapper">
<div class="sidebar-block clearfix">
<?php
$args = array(
    'theme_location' => 'helpmenu',
    'container' => 'div',
    'container_id' => 'help-menu',
    'menu_class' => 'sidebar-nav',
);
wp_nav_menu($args);
?>
</div>
</div>
<div class="sidebar-widget-wrapper">
<h2 class="sidebar-help-title help-search-title">Nápověda pro vyhledávání</h2>
<div class="sidebar-block padded-block">
<div id="help-search-copy"><p>Najít odpovědi v nápovědě, které odpovídají vašim hledaným výrazům:</p></div>
<div id="help-search">
    <form method="get" action="<?php echo esc_url( openlab_get_help_search_url() ); ?>">
            <label class="sr-only" for="helpSearch">Help Search</label>
	    <input type="text" name="help-search" class="help-search" id="helpSearch" />
            <button class="btn btn-default btn-block btn-primary" type="submit">Vyhledat<span class="sr-only"> Nápověda</span></button>
    </form>
    <div class="clearfloat"></div>
</div>
</div>

<h2 class="sidebar-help-title support-team-title">Komunitní tým</h2>
<div class="sidebar-block padded-block">
<div id="support-team">
    <div class="help-tags-copy"><p>Komunitní tým zde odpovídá na všechny vaše otázky OpenLab.</p></div>
    <?php
	$args = array(
		'name' => 'contact-us',
		'post_type' => 'help',
		'post_status' => 'publish',
		'numberposts' => 1,
	);
	$my_posts = get_posts( $args );

	if ( $my_posts ) {
		$post_id = $my_posts[0]->ID;
	}
	$args = array(
		'post_type' => 'attachment',
		'numberposts' => -1,
		'post_status' => 'any',
		'post_parent' => $post_id,
		'orderby' => 'name',
		'order' => 'ASC',
	);
	$attachments = get_posts( $args );

    if ($attachments) {
        $i = 0;

        echo '<div id="team-thumbs" class="row">';
        foreach ($attachments as $attachment) {
            $thumb_class = "col-sm-12 col-xs-12 thumb-wrapper";

            echo '<div class="' . $thumb_class . '">';
            echo '<div class="team-thumb">';
            //use wordpress native thumbnail size for hard crop, then resize to fit container requirements
            $src = wp_get_attachment_image_src($attachment->ID, 'full');

            echo '<a href="' . bp_get_root_domain() . '/blog/help/contact-us">';
            echo '<img alt="Kontaktujte nás" class="img-responsive" src="' . $src[0] . '" />';
            echo '</a>';
            echo '</div>';

            echo '<div class="team-name">';
            echo '<a href="' . bp_get_root_domain() . '/blog/help/contact-us">';
            echo $attachment->post_excerpt;
            echo '</a>';
            echo '</div>';
            echo '</div>';
            $i++;
        }//end for each
        echo '</div>';
    } //end if
    ?>
    <a class="btn btn-default btn-block btn-primary link-btn" href="<?php echo bp_get_root_domain() . '/blog/help/contact-us'; ?>"><i class="fa fa-paper-plane-o" aria-hidden="true"></i>Kontaktujte nás</a>
</div><!--support team-->
</div>

<h2 class="sidebar-help-title help-tags-title">Najděte téma nápovědy se štítky</h2>
<div class="sidebar-block padded-block">
<div class="help-tags-copy"><p>Najděte odpovědi v nápovědě, které odpovídají níže uvedeným značkám:</p></div>
<div id="help-tags">
    <?php
    $args = array(
        'orderby' => 'name',
        'order' => 'ASC',
        'hierarchical' => false,
    );


    $terms = get_terms(array('help_tags'),$args);

    $count = count($terms);
    if ($count > 0) {
        foreach ($terms as $term) {
            echo '<a href="' . get_term_link($term) . '" class="btn btn-default btn-primary link-btn tag-btn tag-count-' . $term->count . '">' . $term->name . '</a> ';
        }
    }
    ?>
    <div class="clearfloat"></div>
</div>
</div>

<h2 class="sidebar-title">Creative Commons</h2>
<div class="sidebar-block padded-block">
<p>Naše nápověda je licencována pod autorskými právy Creative Commons.<br />
<span class="italics cc-gloss"> Uveďte původ &mdash; Neužívejte dílo komerčně &mdash; Zachovejte licenci</span></p>
<div id="creative-commons">
    <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/" target="_blank">Creative Commons</a>
</div>
</div>
</div>
