<?php
/**
 * 404 template
 *
 */

  get_header(); ?>

  <div id="content" class="hfeed">
  			<div <?php post_class(); ?>>
            	<?php cuny_404(); ?>
            </div><!--hentry-->
  </div><!--#content-->

 <?php get_footer();

function cuny_404() { ?>

	<div class="post hentry">

		<h1 class="entry-title">Stránka nenalezena</h1>
		<div id="openlab-main-content" class="entry-content">
			<p>Stránka, kterou jste požadovali, nebyla nalezena. Použijte výše uvedené menu k nalezení stránky, kterou potřebujete.</p>

		</div><!-- end .entry-content -->

	</div><!-- end .postclass -->

<?php
}
