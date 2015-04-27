<?php

//content processing functions
//body classes for specific pages - partly legacy from Genesis Connect
add_filter('body_class', 'openlab_conditional_body_classes');

function openlab_conditional_body_classes($classes) {
    global $post;
    $classes[] = 'header-image';

    if (is_front_page() || is_404()) {
        $classes[] = 'full-width-content';
    } else if (isset($post->post_name) && $post->post_name == 'register') {
        $classes[] = 'content-sidebar';
    }
    
    $group_archives = array('people','courses','projects','clubs','portfolios');
    if(in_array($post->post_name,$group_archives)){
        $classes[] = 'group-archive-page';
    }
    
    $about_page_obj = get_page_by_path('about');
    
    if(in_array($post->post_name,$group_archives) || bp_is_single_item() || bp_is_user() || $post->ID == $about_page_obj->ID || $post->post_parent == $about_page_obj->ID || $post->post_type == 'help' || $post->post_type == 'help_glossary'){
        $classes[] = 'sidebar-mobile-dropdown';
    }

    return $classes;
}

//limit text length
// Note: In the future this should be swapped with bp_create_excerpt(),
// which is smarter about stripping tags, etc
function openlab_shortened_text($text, $limit = "55", $echo = true) {

    $text_length = mb_strlen($text);

    $text = trim(mb_substr($text, 0, $limit));

    $text = force_balance_tags($text);

    if ($echo) {

        echo $text;

        if ($text_length > $limit)
            echo "...";
    } else {
        if ($text_length > $limit)
            $text = $text . "...";

        return $text;
    }
}

//truncate links in profile fields - I'm using $field->data->value to just truncate the link name (it was giving odd results when trying to truncate $value)
add_filter('bp_get_the_profile_field_value', 'openlab_filter_profile_fields', 10, 2);

function openlab_filter_profile_fields($value, $type) {
    global $field;
    $truncate_link_candidates = array('Website', 'LinkedIn Profile Link', 'Facebook Profile Link');
    if (in_array($field->name, $truncate_link_candidates)) {
        $args = array(
            'ending' => __('&hellip;', 'buddypress'),
            'exact' => true,
            'html' => false,
            'filter_shortcodes' => $filter_shortcodes_default
        );
        $truncated_link = bp_create_excerpt($field->data->value, 40, $args);
        $full_link = openlab_http_check($field->data->value);
        $value = '<a href="' . $full_link . '">' . openlab_http_check($truncated_link) . '</a>';
    }
    return $value;
}

function openlab_http_check($link) {
    $http_check = strpos($link, "http");

    if ($http_check == false) {
        $link = "http://" . $link;
    }

    return $link;
}

remove_filter('get_the_excerpt', 'wp_trim_excerpt');
add_filter('get_the_excerpt', 'cuny_add_links_wp_trim_excerpt');

function cuny_add_links_wp_trim_excerpt($text) {
    $raw_excerpt = $text;
    if ('' == $text) {
        $text = get_the_content('');

        $text = strip_shortcodes($text);

        $text = apply_filters('the_content', $text);
        $text = str_replace(']]>', ']]>', $text);
        $text = strip_tags($text, '<a>');
        $excerpt_length = apply_filters('excerpt_length', 55);

        $excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
        $words = preg_split('/( <a.*?a> )|\n|\r|\t|\s/', $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        if (count($words) > $excerpt_length) {
            array_pop($words);
            $text = implode(' ', $words);
            $text = $text . $excerpt_more;
        } else {
            $text = implode(' ', $words);
        }
    }
    return apply_filters('new_wp_trim_excerpt', $text, $raw_excerpt);
}

function openlab_get_menu_count_mup($count, $pull_right = ' pull-right'){
    
    if($count < 1){
        return '';
    }else{
        return '<span class="mol-count count-'.$count.$pull_right.'">'.$count.'</span>';
    }
    
}

function openlab_not_empty($content){
    if($content && !ctype_space($content) && $content !== ''){
        return true;
    }else{
        return false;
    }
}