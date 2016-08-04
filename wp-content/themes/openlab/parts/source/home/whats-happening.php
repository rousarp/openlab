<div class="activity-list item-list inline-element-list sidebar-sublinks">
    <?php if (bp_has_activities($activity_args)) : ?>

        <?php while (bp_activities()) : bp_the_activity(); ?>

            <div class="sidebar-block activity-block">
                <div class="activity-row clearfix">
                    <div class="activity-avatar pull-left">
                        <a href="<?php echo openlab_activity_group_link() ?>">
                            <?php echo openlab_activity_group_avatar(); ?>
                        </a>
                    </div>

                    <div class="activity-content overflow-hidden">

                        <div class="activity-header">
                            <?php echo openlab_get_custom_activity_action(); ?>
                        </div>

                    </div>
                </div>
            </div>

        <?php endwhile; ?>
    <?php else: ?>

        <div class="sidebar-block activity-block">
            <div class="row activity-row">
                <div class="activity-avatar col-sm-24">
                    <div class="activity-header">
                        <p>No recent activity</p>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

