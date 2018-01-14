<?php
/**
 * funkce pro integraci pluginu DW questions and ansnwers se šablonou OpenLab
 *
 */

/**
 *
 */

 if ( class_exists('DW_Question_Answer') && function_exists('bp_is_active')) {
   /*-----------------------------------------------------------------------------------*/
   /*  Setup Questions in BuddyPress User Profile
   /*-----------------------------------------------------------------------------------*/

// redirect na profil uživatele OpenLab

   function dwqa_redirect_author_archive_to_profile() {
   $user_login = isset( $_GET['user'] ) ? $_GET['user'] : null;
   if( $_GET['user'] && $_GET['post_type'] == 'dwqa' ){

   $user = get_user_by( 'login', $user_login );
   wp_redirect( bp_core_get_user_domain( $user->id ) );
   }
   }


// uživatel má registrované otázky (v profilu uživatele v sidebaru se zobrazí položka Moje otázky včetně počtu)
// vrací počet otázek
     function dw_question_user_count() {

     global $dwqa_options;
     $submit_question_link = get_permalink( $dwqa_options['pages']['submit-question'] );
     $questions = get_posts(  array(
       'posts_per_page' => -1,
       'author'         => bp_displayed_user_id(),
       'post_type'      => 'dwqa-question'
     ));

     if ( ! empty($questions) ) {
       return count($questions);
     }
     else {
      return 0;
      }
    }

    function bp_get_dw_questions_user_slug(){
      return "http://multi.openlab.dev/dwqa-questions/?user=" . get_user_by('id', bp_displayed_user_id())->user_login;
    }

    function questions_list() {
      add_action( 'bp_template_content', 'profile_questions_loop' );
      bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    function answer_list() {
      add_action( 'bp_template_content', 'profile_answers_loop' );
      bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

   function profile_questions_loop() {
     global $dwqa_options;
     $submit_question_link = get_permalink( $dwqa_options['pages']['submit-question'] );
     $questions = get_posts(  array(
       'posts_per_page' => -1,
       'author'         => bp_displayed_user_id(),
       'post_type'      => 'dwqa-question'
     ));

     // přidat sidebar

    openlab_bp_sidebar('members');

     if( ! empty($questions) ) { ?>


     <div class="dwqa-questions-archive">
         <div class="dwqa-questions-list">
             <?php foreach ($questions as $q) { ?>
             <div class="dwqa-question-item">
                 <div class="dwqa-question-title"><a href="<?php echo get_post_permalink($q->ID); ?>"><?php echo $q->post_title; ?></a></div>
                 <div class="dwqa-question-meta">
                     <?php dwqa_question_print_status( $q->ID ) ?>
                     <?php
                         global $post;
                         $user_id = $q->post_author ? $q->post_author : false;
                         $time = human_time_diff(  get_post_time( 'U' , false,  $q->ID ) , current_time('timestamp') );
                         $text = __( 'asked', 'dwqa' );
                         $latest_answer = dwqa_get_latest_answer( $q->ID );
                         if ( $latest_answer ) {
                             $time = human_time_diff( strtotime( $latest_answer->post_date ) );
                             $text = __( 'answered', 'dwqa' );
                         }
                     ?>
                     <?php printf( __( '<span><a href="%s">%s%s</a> %s %s ago</span>', 'dwqa' ), dwqa_get_author_link( $user_id ), get_avatar( $user_id, 48 ), get_the_author_meta( 'display_name', $user_id ), $text, $time ) ?>
                     <?php echo get_the_term_list( $q->ID, 'dwqa-question_category', '<span class="dwqa-question-category">' . __( '&nbsp;&bull;&nbsp;', 'dwqa' ), ', ', '</span>' ); ?>
                 </div>
                 <div class="dwqa-question-stats">
                     <span class="dwqa-views-count">
                         <?php $views_count = dwqa_question_views_count( $q->ID ) ?>
                         <?php printf( __( '<strong>%1$s</strong> views', 'dwqa' ), $views_count ); ?>
                     </span>
                     <span class="dwqa-answers-count">
                         <?php $answers_count = dwqa_question_answers_count( $q->ID ); ?>
                         <?php printf( __( '<strong>%1$s</strong> answers', 'dwqa' ), $answers_count ); ?>
                     </span>
                     <span class="dwqa-votes-count">
                         <?php $vote_count = dwqa_vote_count( $q->ID ) ?>
                         <?php printf( __( '<strong>%1$s</strong> votes', 'dwqa' ), $vote_count ); ?>
                     </span>
                 </div>
             </div>
             <?php } ?>
                     </div>
             </div>
     <?php } else { ?>
     <div class="info" id="message">
       <?php if( get_current_user_id() == bp_displayed_user_id() ) : ?>
         Nemáte žádný dotaz? <a href="<?php echo $submit_question_link ?>">Položte ho</a>!
       <?php else : ?>
         <p><strong>Uživatel <?php bp_displayed_user_fullname(); ?></strong> žádný dotaz doposud nepoložil.</p>
       <?php endif; ?>
     </div>
     <?php }
   }

   function profile_answers_loop() {
     global $dwqa_options;
     $submit_question_link = get_permalink( $dwqa_options['pages']['submit-question'] );
     $answers = get_posts(  array(
       'posts_per_page' => -1,
       'author'         => bp_displayed_user_id(),
       'post_type'      => 'dwqa-answer'
     ));
     if( ! empty($answers) ) { ?>
       <div class="dwqa-questions-archive">
         <div class="dwqa-questions-list">
             <?php foreach ($answers as $a) { ?>
                 <div class="dwqa-question-item" style="padding-left: 20px!important">
                     <a class="dwqa-title" href="<?php echo get_post_permalink($a->ID); ?>" title="Permalink to <?php echo $a->post_title ?>" rel="bookmark"><?php echo $a->post_title ?></a>
                     <div class="dwqa-meta">
                         <span><strong> v čase : </strong><?php echo get_the_time( 'M d, Y, g:i a', $a->ID ); ?></span>
                     </div>
                 </div>
             <?php } ?>
         </div>
       </div>
     <?php } else { ?>
     <div class="info" id="message">
       <?php if( get_current_user_id() == bp_displayed_user_id() ) : ?>
         Nemáte žádný dotaz? <a href="<?php echo $submit_question_link ?>">Položte ho</a>!
       <?php else : ?>
         <p><strong>Uživatel <?php bp_displayed_user_fullname(); ?></strong> žádný dotaz doposud nepoložil.</p>
       <?php endif; ?>
     </div>
     <?php }
   }

   /*-----------------------------------------------------------------------------------*/
   /*  Record Activities
   /*-----------------------------------------------------------------------------------*/
   // Question
   function dw_qa_bb_record_question_activity( $post_id ) {
     $post = get_post($post_id);
     if(($post->post_status != 'publish') && ($post->post_status != 'private'))
       return;

     $user_id = get_current_user_id();
     $post_permalink = get_permalink( $post_id );
     $post_title = get_the_title( $post_id );
     $activity_action = sprintf( __( '%s položil novou otázku: %s', 'dwqa' ), bp_core_get_userlink( $user_id ), '<a href="' . $post_permalink . '">' . $post->post_title . '</a>' );
     $content = $post->post_content;
     $hide_sitewide = ($post->post_status == 'private') ? true : false;

     bp_blogs_record_activity( array(
       'user_id' => $user_id,
       'action' => $activity_action,
       'content' => $content,
       'primary_link' => $post_permalink,
       'type' => 'new_blog_post',
       'item_id' => 0,
       'secondary_item_id' => $post_id,
       'recorded_time' => $post->post_date_gmt,
       'hide_sitewide' => $hide_sitewide,
     ));
   }
   add_action( 'dwqa_add_question', 'dw_qa_bb_record_question_activity');

   //Answer
   function dw_qa_bb_record_answer_activity( $post_id ) {
     $post = get_post($post_id);

     if($post->post_status != 'publish')
       return;

     $user_id = $post->post_author;

     $question_id = get_post_meta( $post_id, '_question', true );
     $question = get_post( $question_id );

     $post_permalink = get_permalink($question_id);
     $activity_action = sprintf( __( '%s odpověděl na otázku: %s', 'dwqa' ), bp_core_get_userlink( $user_id ), '<a href="' . $post_permalink . '">' . $question->post_title . '</a>' );
     $content = $post->post_content;

     $hide_sitewide = ($question->post_status == 'private') ? true : false;

     bp_blogs_record_activity( array(
       'user_id' => $user_id,
       'action' => $activity_action,
       'content' => $content,
       'primary_link' => $post_permalink,
       'type' => 'new_blog_post',
       'item_id' => 0,
       'secondary_item_id' => $post_id,
       'recorded_time' => $post->post_date_gmt,
       'hide_sitewide' => $hide_sitewide,
     ));
   }
   add_action( 'dwqa_add_answer', 'dw_qa_bb_record_answer_activity');
   add_action( 'dwqa_update_answer', 'dw_qa_bb_record_answer_activity');

   //Comment
   function dw_qa_bb_record_comment_activity( $comment_id ) {
     $comment = get_comment($comment_id);
     $user_id = get_current_user_id();
     $post_id = $comment->comment_post_ID;
     $content = $comment->comment_content;

     if(get_post_type($post_id) == 'dwqa-question') {
       $post = get_post( $post_id );
       $post_permalink = get_permalink( $post_id );
       $activity_action = sprintf( __( '%s commented on the question: %s', 'dwqa' ), bp_core_get_userlink( $user_id ), '<a href="' . $post_permalink . '">' . $post->post_title . '</a>' );
       $hide_sitewide = ($post->post_status == 'private') ? true : false;
     } else {
       $post = get_post( $post_id );
       $question_id = get_post_meta( $post_id, '_question', true );
       $question = get_post( $question_id );
       $post_permalink = get_permalink( $question_id );
       $activity_action = sprintf( __( '%s commented on the answer at: %s', 'dwqa' ), bp_core_get_userlink( $user_id ), '<a href="' . $post_permalink . '">' . $question->post_title . '</a>' );
       $hide_sitewide = ($question->post_status == 'private') ? true : false;
     }

     bp_blogs_record_activity( array(
       'user_id' => $user_id,
       'action' => $activity_action,
       'content' => $content,
       'primary_link' => $post_permalink,
       'type' => 'new_blog_comment',
       'item_id' => 0,
       'secondary_item_id' => $comment_id,
       'recorded_time' => $comment->comment_date_gmt,
       'hide_sitewide' => $hide_sitewide,
     ));
   }
   add_action( 'dwqa_add_comment', 'dw_qa_bb_record_comment_activity');
 }
