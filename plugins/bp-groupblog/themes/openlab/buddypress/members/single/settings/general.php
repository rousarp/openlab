<?php
/**
 * Members settings - general
 *
 * */
do_action('bp_before_member_settings_template');
?>

<?php echo openlab_submenu_markup(); ?>


<div id="item-body" role="main">

    <?php do_action('bp_template_content') ?>

    <form action="<?php echo bp_displayed_user_domain() . bp_get_settings_slug() . '/general'; ?>" method="post" class="standard-form form-panel" id="settings-form">

        <div class="panel panel-default">
            <div class="panel-heading">Aktualizace nastavení</div>
                <div class="panel-body">
	            	<?php do_action( 'template_notices' ); ?>

        <div class="form-group settings-section username-section">
            <label for="username">Uživatelské jméno</label>
            <input class="form-control" type="text" id="username" disabled="disabled" value="<?php bp_displayed_user_username() ?>" />
            <p class="description">Vaše uživatelské jméno nelze změnit.</p>
        </div>

        <div class="form-group settings-section email-section">
            <label for="email_visible">E-mailová adresa účtu</label>
            <input class="form-control" type="text" name="email_visible" id="email_visible" value="<?php echo bp_get_displayed_user_email(); ?>" class="settings-input" disabled="disabled" />
            <input type="hidden" name="email" value="<?php echo bp_get_displayed_user_email() ?>" />
            <p class="description">Vaše e-mailová adresa nemůže být změněna. Pokud se změnila e-mailová adresa vašeho City Techu, <a class="underline" href="<?php bp_get_root_domain(); ?>/about/contact-us">kontaktujte nás</a> s žádosí o pomoc.</p>
        </div>

        <div class="form-group settings-section current-pw-section">
            <label for="pwd">Aktuální heslo</label>
            <input class="form-control" type="password" name="pwd" id="pwd" size="16" value="" class="settings-input small" />

            <?php
            $account_type = openlab_get_displayed_user_account_type();
            $include_acct_type = in_array($account_type, array('Student', 'Alumni')) ? ' account type, ' : ' ';
            ?>

            <p class="description">Požadujete změnit<?php echo $include_acct_type ?>aktuální heslo, jméno nebo příjmení. <a class="underline" href="<?php echo site_url(add_query_arg(array('action' => 'lostpassword'), 'wp-login.php'), 'login'); ?>" title="<?php _e('Password Lost and Found', 'buddypress'); ?>"><?php _e('Lost your password?', 'buddypress'); ?></a></p>
        </div>

        <div class="form-group settings-section change-pw-section">
            <label for="pass1">Změnit heslo</label>
            <input class="form-control" type="password" name="pass1" id="pass1" size="16" value="" class="settings-input small" />

            <label for="pass1">Potvrďte změnu hesla</label>
            <input class="form-control" type="password" name="pass2" id="pass2" size="16" value="" class="settings-input small" />

            <p class="description">Ponechte prázdné nevyžadujete-li změnu</p>
        </div>

        <div class="form-group settings-section name-section">
            <label for="fname">Jméno (vyžadováno)</label>
            <input class="form-control" type="text" name="fname" id="fname" value="<?php echo bp_get_profile_field_data(array('field' => 'First Name')) ?>" />

            <label for="lname">Příjmení (vyžadováno)</label>
            <input class="form-control" type="text" name="lname" id="lname" value="<?php echo bp_get_profile_field_data(array('field' => 'Last Name')) ?>" />
        </div>

        <?php if (in_array($account_type, array('Student', 'Alumni'))) : ?>
            <div class="form-group settings-section account-type-section">
                <label for="account_type">Typ účtu</label>
                <select class="form-control" name="account_type">
                    <option value="Student" <?php selected('Student', $account_type) ?>>Student</option>
                    <option value="Alumni" <?php selected('Alumni', $account_type) ?>>Alumni</option>
                </select>
            </div>
        <?php endif ?>

            </div>
        </div>
        <?php do_action('bp_core_general_settings_before_submit'); ?>

        <div class="submit">
            <input class="btn btn-primary btn-margin btn-margin-top" type="submit" name="submit" value="<?php _e('Save Changes', 'buddypress'); ?>" id="submit" class="auto" />
        </div>

        <?php do_action('bp_core_general_settings_after_submit');
        wp_nonce_field('bp_settings_general');
        ?>
    </form>
<?php do_action('bp_after_member_body'); ?>
</div><!-- #item-body -->
<?php
do_action('bp_after_member_settings_template');
