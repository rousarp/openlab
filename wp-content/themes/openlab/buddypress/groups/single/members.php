<?php if ( bp_group_has_members( 'exclude_admins_mods=0' ) ) : ?>

	<?php do_action( 'bp_before_group_members_content' ) ?>
    <div class="row"><div class="col-md-24">
        <div class="submenu col-sm-16">
		<ul class="nav nav-inline">
			<?php openlab_group_membership_tabs(); ?>
		</ul>
	</div><!-- .item-list-tabs --> 
        <div id="member-count" class="pag-count col-sm-8 align-right">
			<?php bp_group_member_pagination_count() ?>
		</div>

        </div></div>

	<?php do_action( 'bp_before_group_members_list' ) ?>

	<div id="group-list" class="item-list group-members">
		<?php while ( bp_group_members() ) : bp_group_the_member(); ?>

			<div class="group-item col-sm-8 col-xs-12">
                            <div class="group-item-wrapper">
                                <div class="row">
                                <div class="item-avatar col-xs-8">
				<a href="<?php bp_member_permalink() ?>"><img class="img-responsive" src ="<?php echo bp_core_fetch_avatar(array('item_id' => bp_get_member_user_id(), 'object' => 'member', 'type' => 'full', 'html' => false)) ?>" alt="<?php echo $group->name; ?>"/></a>
                                </div>
                                <div class="item col-xs-16">
				<h4><a href="<?php bp_member_permalink() ?>" title="<?php bp_member_name() ?>"><?php bp_member_name() ?></a></h4>
				<span class="activity"><?php bp_group_member_joined_since() ?></span>

				<?php do_action( 'bp_group_members_list_item' ) ?>

				<?php if ( function_exists( 'friends_install' ) ) : ?>

					<div class="action">
						<?php bp_add_friend_button( bp_get_group_member_id(), bp_get_group_member_is_friend() ) ?>

						<?php do_action( 'bp_group_members_list_item_action' ) ?>
					</div>

				<?php endif; ?>
                                </div>
                            </div>
			</div>
                        </div>

		<?php endwhile; ?>

	</div>

	<?php do_action( 'bp_after_group_members_content' ) ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'This group has no members.', 'buddypress' ); ?></p>
	</div>

<?php endif; ?>
