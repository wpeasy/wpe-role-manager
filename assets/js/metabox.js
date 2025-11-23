/**
 * Content Restrictions Metabox
 *
 * @package WP_Easy\RoleManager
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // Initialize Select2 for capabilities
        $('#wpe_rm_capabilities').select2({
            placeholder: 'Select capabilities...',
            allowClear: true,
            width: '100%'
        });

        // Initialize Select2 for roles
        $('#wpe_rm_roles').select2({
            placeholder: 'Select roles...',
            allowClear: true,
            width: '100%'
        });

        // Fix Select2 remove button position - move it to the end
        function fixSelect2RemoveButtons() {
            $('.wpe-rm-metabox .select2-selection__choice').each(function() {
                const $choice = $(this);
                const $remove = $choice.find('.select2-selection__choice__remove');
                if ($remove.length) {
                    $remove.appendTo($choice);
                }
            });
        }

        // Fix on initial load
        setTimeout(fixSelect2RemoveButtons, 100);

        // Fix whenever selection changes
        $('#wpe_rm_capabilities, #wpe_rm_roles').on('select2:select select2:unselect', function() {
            setTimeout(fixSelect2RemoveButtons, 10);
        });

        // Toggle restrictions fields based on checkbox
        $('input[name="wpe_rm_restrictions_enabled"]').on('change', function () {
            if ($(this).is(':checked')) {
                $('.wpe-rm-restrictions-fields').slideDown(200);
            } else {
                $('.wpe-rm-restrictions-fields').slideUp(200);
            }
        });

        // Toggle message/redirect fields based on radio selection
        $('input[name="wpe_rm_action_type"]').on('change', function () {
            const selectedValue = $(this).val();

            if (selectedValue === 'message') {
                $('.wpe-rm-message-fields').slideDown(200);
                $('.wpe-rm-redirect-field').slideUp(200);
            } else if (selectedValue === 'redirect') {
                $('.wpe-rm-message-fields').slideUp(200);
                $('.wpe-rm-redirect-field').slideDown(200);
            }
        });

        // Toggle HTTP code field based on message type selection
        $('input[name="wpe_rm_message_type"]').on('change', function () {
            const selectedValue = $(this).val();

            if (selectedValue === 'plain_message') {
                $('.wpe-rm-http-code-field').slideDown(200);
            } else {
                $('.wpe-rm-http-code-field').slideUp(200);
            }
        });

        // Toggle capability/role fields based on filter type selection
        $('input[name="wpe_rm_filter_type"]').on('change', function () {
            const selectedValue = $(this).val();

            if (selectedValue === 'capability') {
                $('.wpe-rm-capability-field').slideDown(200);
                $('.wpe-rm-role-field').slideUp(200);
            } else if (selectedValue === 'role') {
                $('.wpe-rm-capability-field').slideUp(200);
                $('.wpe-rm-role-field').slideDown(200);
            }
        });
    });
})(jQuery);
