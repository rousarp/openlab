(function ($) {

// Parsley validation rules.
    window.Parsley.addValidator('lowercase', {
        validateString: function (value) {
            return value === value.toLowerCase();
        },
        messages: {
            en: 'Toto pole podporuje pouze malá písmena.'
        }
    });

    window.Parsley.addValidator('nospecialchars', {
        validateString: function (value) {
            return !value.match(/[^a-zA-Z0-9]/);
        },
        messages: {
            en: 'Toto pole podporuje pouze alfanumerické znaky.'
        }
    });

    var iffRecursion = false;
    window.Parsley.addValidator('iff', {
        validateString: function (value, requirement, instance) {
            var $partner = $(requirement);
            var isValid = $partner.val() == value;

            if (iffRecursion) {
                iffRecursion = false;
            } else {
                iffRecursion = true;
                $partner.parsley().validate();
            }

            return isValid;
        }
    });

    function checkPasswordStrength(pw, blacklist) {
        var score = window.wp.passwordStrength.meter(pw, blacklist, '');

        var message = window.pwsL10n.short;
        switch (score) {
            case 2 :
                return window.pwsL10n.bad;

            case 3 :
                return window.pwsL10n.good;

            case 4 :
                return window.pwsL10n.strong;
        }
    }

    jQuery(document).ready(function () {
        var $signup_form = $('#signup_form');

        var registrationFormValidation = $signup_form.parsley({
            errorsWrapper: '<ul class="parsley-errors-list"></ul>'
        }).on('field:error', function (formInstance) {

            this.$element.closest('.form-group')
                    .find('.other-errors').remove();

            this.$element.closest('.form-group')
                    .addClass('has-error')
                    .find('.error-container').addClass('error');

            var errorMsg = this.$element.prevAll("div.error-container:first").find('li:first');

            console.log('errorMsg', errorMsg.text());

            //in some cases errorMsg is further up the chain
            if (errorMsg.length === 0) {
                errorMsg = this.$element.parent().prevAll("div.error-container:first").find('li:first');
            }

            var jsElem = errorMsg[0];
            jsElem.style.clip = 'auto';
            var alertText = document.createTextNode(" ");
            jsElem.appendChild(alertText);
            jsElem.style.display = 'none';
            jsElem.style.display = 'inline';

            console.log('jsElem', jsElem);

            if (errorMsg.attr('role') !== 'alert') {
                errorMsg.attr('role', 'alert');
            }

        }).on('field:success', function (formInstance) {

            this.$element.closest('.form-group')
                    .removeClass('has-error')
                    .find('.error-container').removeClass('error');

            var errorMsg = this.$element.prevAll("div.error-container:first").find('li:first');

            //in some cases errorMsg is further up the chain
            if (errorMsg.length === 0) {
                errorMsg = this.$element.parent().prevAll("div.error-container:first").find('li:first');
            }

            errorMsg.attr('role', '');
        });

        var inputBlacklist = [
            'signup_username',
            'field_1', // Display Name
            'field_241', // First Name
            'field_3'    // Last Name
        ];

        var $password_strength_notice = $('#password-strength-notice');
        $('body').on('keyup', '#signup_password', function (e) {
            var blacklistValues = [];
            for (var i = 0; i < inputBlacklist.length; i++) {
                var fieldValue = document.getElementById(inputBlacklist[ i ]).value;
                if (4 <= fieldValue.length) {
                    // Exclude short items. See password-strength-meter.js.
                    blacklistValues.push(fieldValue);
                }
            }

            var score = window.wp.passwordStrength.meter(e.target.value, blacklistValues, '');

            var message = window.pwsL10n.short;
            switch (score) {
                case 2 :
                    message = window.pwsL10n.bad;
                    break;

                case 3 :
                    message = window.pwsL10n.good;
                    break;

                case 4 :
                    message = window.pwsL10n.strong;
                    break;
            }

            $password_strength_notice
                    .show()
                    .html(message)
                    .removeClass('strength-0 strength-1 strength-2 strength-3 strength-4').
                    addClass('strength-' + score);
        });

        var initValidation = false;
        var asyncValidation = false;
        var asyncLoaded = false;
        formValidation($signup_form);

        $('#signup_email').on('blur', function (e) {
            var email = $(e.target).val().toLowerCase();
            var re = /^[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+(?:[a-z]{2}|com|org|net|gov|mil|biz|info|mobi|name|aero|jobs|museum)/;

            if (!email.length) {
                return;
            }

            var emailtype = 'nonovm';
            var $emaillabel = $('#signup_email_error');
            var $validationdiv = $('#validation-code');
            var $emailconfirm = $('#signup_email_confirm');
            var errorcode = "1";
            var message = '';

            $emaillabel.html('<p class="parsley-errors-list other-errors">&mdash; Kontrola emailové adresy...</p>');
            $emaillabel.css('color', '#000');
            $emaillabel.fadeIn();
            $emaillabel.addClass('error');
            if ( $validationdiv.length ) {
                $validationdiv.remove();
            }

            if ( ! re.test(email)) {
                message = 'E-mailová adresa není ve správném tvaru. Prosím zkuste to znovu.';
                errorcode = "3";
            } else {
                $.post(ajaxurl, {
                    action: 'cac_ajax_email_check',
                    'email': email
                    },
                    function (response) {
                        var show_validation = false;
                        errorcode = response;
                        switch (errorcode) {
                            /*
                             * Return values:
                             *   1: Non OVM
                             *   2: no email provided
                             *   3: not a valid email address
                             *   4: unsafe
                             *   5: Is in OVM domains
                             *   6: email exists
                             *   7: Is a student email
                             */
                            case "6" :
                                message = 'Účet s touto e-mailovou adresou již existuje.';
                                break;
                            case "5" :
                                message = 'Zadaná emailová adresa nemá doménu subjektu státní správy.';
                                emailtype = 'nonovm';
                                show_validation = true;
                                break;
                            case "4" :
                                message = 'E-mailová adresa obsahuje zakázanou doménu.';
                                show_validation = true;
                                break;
                            case "3" :
                                message = 'E-mailová adresa není ve správném tvaru. Prosím zkuste to znovu.';
                                break;
                            case "2" :
                                message = 'Zadání e-mailové adresy je povinné.';
                                break;

                            case '1' :
                                message = '&mdash; Zadaná emailová adresa má doménu subjektu státní správy.';
                                emailtype = 'ovm';
                                break;
                            default :
                                message = '';
                                break;
                        }
                        message = '<ul class="parsley-errors-list filled other-errors"><li role="alert">' + message + '</li></ul>';

                        if (response != '1' && response != '5' ) {
                            $emaillabel.fadeOut(function () {
                                $emaillabel.html(message);
                                $emaillabel.fadeIn();
                            });
                        } else if (response == '1') {
                            $emaillabel.fadeOut(function () {
                                $emaillabel.html(message);
                                $emaillabel.fadeIn();
                            });
                        } else {
                            $emaillabel.fadeOut();
                            $emaillabel.html(message);
                            // Don't add more than one
                            if (!$validationdiv.length) {
                                var valbox = '<div id="validation-code" style="display:none"><label for="signup_validation_code" role="alert">Registrační kód <em aria-hidden="true">(povinné)</em> <span>Vyžadováno pro uživatele mimo státní správu.</span></label><input name="signup_validation_code" id="signup_validation_code" type="text" val="" /></div>';
                                $('input#signup_email').before(valbox);
                                $validationdiv = $('#validation-code');
                            }
                        }
                        if (show_validation) {
                            $validationdiv.show();
                        } else {
                            $validationdiv.hide();
                            //                           $emailconfirm.focus();
                        }

                        set_account_type_fields();
                    });
                }

                function set_account_type_fields() {
                    var newtypes = '';

                    if ('student' == emailtype) {
                        newtypes += '<option value="Student">Student</option>';
                        newtypes += '<option value="Alumni">Alumni</option>';
                    }

                    if ('fs' == emailtype) {
                        newtypes += '<option value="">----</option>';
                        newtypes += '<option value="Faculty">Faculty</option>';
                        newtypes += '<option value="Staff">Staff</option>';
                    }

                    if ('nonct' == emailtype) {
                        newtypes += '<option value="Non-City Tech">Non-City Tech</option>';
                    }

                    if ('ovm' == emailtype) {
                        newtypes += '<option value="Uživatel z veřejné správy">Uživatel z veřejné správy</option>';
                    }

                    if ('nonovm' == emailtype) {
                        newtypes += '<option value="Běžný uživatel">Běžný uživatel</option>';
                    }

                    if ('' == emailtype) {
                        newtypes += '<option value="">----</option>';
                    }

                    var $typedrop = $('#field_2');
                    $typedrop.html(newtypes);

                    /*
                     * Because there is no alternative in the dropdown, the 'change' event never
                     * fires. So we trigger it manually.
                     */
                    load_account_type_fields();
                }
        });

        $('input#signup_validation_code').live('blur', function () {
            var code = $(this).val();

            var vcodespan = $('#signup_email_error');

            $(vcodespan).fadeOut(function () {
                $(vcodespan).html('<p class="parsley-errors-list">&mdash; Kontrola registračního kódu...</p>');
                $(vcodespan).css('color', '#000');
                $(vcodespan).fadeIn();
                $(vcodespan).addClass('error');
            });

            /* Handle email verification server side because there we have the functions for it */
            $.post(ajaxurl, {
                action: 'cac_ajax_vcode_check',
                'code': code
            },
                    function (response) {
                        if ('1' == response) {
                            $(vcodespan).fadeOut(function () {
                                $(vcodespan).html('&mdash; OK!');
                                $(vcodespan).css('color', '#000');
                                $(vcodespan).fadeIn();
                                $('div#submit')
                            });
                        } else {
                            $(vcodespan).fadeOut(function () {
                                $(vcodespan).html('&mdash; Required for non-CUNY addresses');
                                $(vcodespan).css('color', '#f00');
                                $(vcodespan).fadeIn();
                            });
                        }
                    });
        });

        var $account_type_field = $('#field_' + OLReg.account_type_field);

        // Ensure that the account type field is set properly from the post
        $account_type_field.val(OLReg.post_data.field_7);
        $account_type_field.children('option').each(function () {
            if (OLReg.post_data.field_7 == $(this).val()) {
                $(this).attr('selected', 'selected');
            }
        });

        $account_type_field.on('change', function () {
            load_account_type_fields();
        });
        load_account_type_fields();

        //load register account type
        function load_account_type_fields() {
            var default_type = '';
            var selected_account_type = $account_type_field.val();

            if (document.getElementById('signup_submit')) {

                $('#signup_submit').on('click', function (e) {

                    var thisElem = $(this);

                    if (thisElem.hasClass('btn-disabled')) {
                        e.preventDefault();
                        var message = 'Pro pokračování vyplňte požadovaná pole';
                        $('#submitSrMessage').text(message);
                    }

                });

                $.ajax(ajaxurl, {
                    data: {
                        action: 'wds_load_account_type',
                        account_type: selected_account_type,
                        post_data: OLReg.post_data
                    },
                    method: 'POST',
                    success: function (response) {

                        var $wds_fields = $('#wds-account-type');

                        $wds_fields.html(response);

                        load_error_messages();

                        if (response !== 'Zvolte typ účtu.') {

                            asyncLoaded = true;
                            //reset validation
                            initValidation = false;
                            formValidation($wds_fields);
                            updateSubmitButtonStatus();
                        }

                    }
                });
            }
        }

        function formValidation(fieldParent) {

            fieldParent.find('input').on('input blur', function (e) {

                evaluateFormValidation();

            });

            fieldParent.find('select').on('change blur', function (e) {

                evaluateFormValidation();

            });


        }

        function updateSubmitButtonStatus() {

            if (initValidation) {
                $('#signup_submit').removeClass('btn-disabled');
                $('#signup_submit').val('Dokončit registraci');
            } else if (!$('#signup_submit').hasClass('btn-disabled')) {
                $('#signup_submit').addClass('btn-disabled');
                $('#signup_submit').val('Pro pokračování vyplňte požadovaná pole');
            }

        }

        function evaluateFormValidation() {

            if (asyncLoaded && registrationFormValidation.isValid()) {
                initValidation = true;
            } else {
                initValidation = false;
            }

            updateSubmitButtonStatus();
        }

        /**
         * Put registration error messages into the template dynamically.
         *
         * See openlab_registration_errors_object().
         */
        function load_error_messages() {
            jQuery.each(OpenLab_Registration_Errors, function (k, v) {
                $('#' + k).before(v);
            });
        }
    });

}(jQuery));
