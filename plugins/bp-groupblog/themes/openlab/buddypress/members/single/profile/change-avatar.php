<?php echo openlab_submenu_markup(); ?>
<div id="item-body" role="main">
<?php do_action( 'bp_before_profile_avatar_upload_content' ) ?>

<?php if ( !(int)bp_get_option( 'bp-disable-avatar-uploads' ) ) : ?>

	<form action="" method="post" id="avatar-upload-form" enctype="multipart/form-data" class="form-inline form-panel">

                <div class="panel panel-default">

		<?php if ( 'upload-image' == bp_get_avatar_admin_step() ) : ?>
                <div class="panel-heading">Nahrát náhledový obrázek</div>
                    <div class="panel-body">
                        <?php do_action('template_notices') ?>
                        <div class="row">
                            <div class="col-sm-8">
                                <div id="avatar-wrapper">
                                    <div class="padded-img">
                                        <img class="img-responsive padded" src ="<?php echo get_stylesheet_directory_uri(); ?>/images/avatar_blank.png" alt="avatar-blank"/>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-16">

                                <p class="italics"><?php _e( 'Váš avatar bude použit ve vašem profilu a na celém webu. Pokud je k účtu Gravatar přidružen e-mail s vaším účtem, použijeme to, nebo můžete z počítače načíst obrázek. Klikněte níže pro výběr fotografie ve formátu JPG, GIF nebo PNG z počítače a pokračujte kliknutím na tlačítko "Nahrát obrázek".', 'buddypress') ?></p>

                                <p id="avatar-upload">
                                    <div class="form-group form-inline">
                                            <div class="form-control type-file-wrapper">
                                                <input type="file" name="file" id="file" />
                                            </div>
                                            <input class="btn btn-primary top-align" type="submit" name="upload" id="upload" value="<?php _e( 'Upload Image', 'buddypress' ) ?>" />
                                            <input type="hidden" name="action" id="action" value="bp_avatar_upload" />
                                    </div>
                                </p>

                                <?php if ( bp_get_user_has_avatar() ) : ?>
                                        <p class="italics"><?php _e( 'Chcete-li smazat váš aktuální avatar, ale neinstalovat nový, použijte tlačítko "Smazat náhledový obrázek".'', 'buddypress' ) ?></p>
                                        <a class="btn btn-primary no-deco" href="<?php bp_avatar_delete_link() ?>" title="<?php _e( 'Smazat náhledový obrázek', 'buddypress' ) ?>"><?php _e( 'Smazat náhledový obrázek', 'buddypress' ) ?></a>
                                <?php endif; ?>

                                <?php wp_nonce_field( 'bp_avatar_upload' ) ?>
                            </div>
                        </div>
                </div>

		<?php endif; ?>

		<?php if ( 'crop-image' == bp_get_avatar_admin_step() ) : ?>

                <div class="panel-heading">Oříznout náhledový obrázek</div>
                        <div class="panel-body">
                            <?php do_action('template_notices') ?>
                            <img src="<?php bp_avatar_to_crop() ?>" id="avatar-to-crop" class="avatar" alt="<?php _e('Náhledový obrázek k oříznutí', 'buddypress') ?>" />

                            <div id="avatar-crop-pane">
                                <img src="<?php bp_avatar_to_crop() ?>" id="avatar-crop-preview" class="avatar" alt="<?php _e('Zobrazení náhledového obrázku', 'buddypress') ?>" />
                            </div>

                            <input class="btn btn-primary" type="submit" name="avatar-crop-submit" id="avatar-crop-submit" value="<?php _e('Crop Image', 'buddypress') ?>" />

                            <input type="hidden" name="image_src" id="image_src" value="<?php bp_avatar_to_crop_src() ?>" />
                            <input type="hidden" id="x" name="x" />
                            <input type="hidden" id="y" name="y" />
                            <input type="hidden" id="w" name="w" />
                            <input type="hidden" id="h" name="h" />

                            <?php wp_nonce_field('bp_avatar_cropstore') ?>
                        </div>

		<?php endif; ?>
                </div><!--.panel-->

	</form>

<?php else : ?>

	<p><?php _e( 'Your avatar will be used on your profile and throughout the site. To change your avatar, please create an account with <a href="http://gravatar.com">Gravatar</a> using the same email address as you used to register with this site.', 'buddypress' ) ?></p>

<?php endif; ?>
</div>
<?php do_action( 'bp_after_profile_avatar_upload_content' ) ?>
