jQuery(document).on("ready", function() {
    if (jQuery("#woocommerce_sampay_sms").is(":checked")) {
        jQuery("#woocommerce_sampay_sms_url").prop("disabled", false);
        jQuery("#woocommerce_sampay_sms_message").prop("disabled", false);
    } else {
        jQuery("#woocommerce_sampay_sms_url").prop("disabled", true);
        jQuery("#woocommerce_sampay_sms_message").prop("disabled", true);
    }
    jQuery("#woocommerce_sampay_sms").on("click", function() {
        if (jQuery(this).is(":checked")) {
            jQuery("#woocommerce_sampay_sms_url").prop("disabled", false);
            jQuery("#woocommerce_sampay_sms_message").prop("disabled", false);
        } else {
            jQuery("#woocommerce_sampay_sms_url").prop("disabled", true);
            jQuery("#woocommerce_sampay_sms_message").prop("disabled", true);
        }
    });
});