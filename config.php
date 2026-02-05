<?php
// ... (other configuration and PHP opening tag)

// =================== OneSignal Configuration =======================

// OneSignal App ID (Safe for client-side JS - required for OneSignal SDK)
define('ONESIGNAL_APP_ID', '27f8d4d3-3a69-4a4d-8f7b-113d16763c4b');

// OneSignal REST API Key (SERVER-SIDE ONLY! Never expose this to client-side JS)
define('ONESIGNAL_REST_API_KEY', 'yos_v2_app_e74njuz2nffe3d33ce6rm5r4jobuamioediusjfadwrwiwi53chrv6zoomac3yfthlsb5ws6e4tjhpytgvqzvv5gir44qxfiznor6pi');

/**
 * Helper: Check if OneSignal credentials are configured
 * @return bool
 */
function onesignal_is_configured() {
    return ONESIGNAL_APP_ID !== '27f8d4d3-3a69-4a4d-8f7b-113d16763c4b'
        && ONESIGNAL_REST_API_KEY !== 'os_v2_app_e74njuz2nffe3d33ce6rm5r4jobuamioediusjfadwrwiwi53chrv6zoomac3yfthlsb5ws6e4tjhpytgvqzvv5gir44qxfiznor6pi';
}

/**
 * Helper: Validate OneSignal configuration
 * @param bool $throw_on_error
 * @return bool
 * @throws Exception
 */
function onesignal_validate_config($throw_on_error = false) {
    if (!onesignal_is_configured()) {
        if ($throw_on_error) {
            throw new Exception(
                'OneSignal credentials not configured. ' .
                'Please update config.php with your actual OneSignal App ID and REST API Key. '
            );
        }
        return false;
    }
    return true;
}

// ================= End OneSignal Configuration =====================

// ... (other configuration, functions, or classes)
