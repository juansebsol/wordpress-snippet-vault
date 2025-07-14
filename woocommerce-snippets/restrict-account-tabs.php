/**
 * Conditionally Show WooCommerce My Account Tabs Based on User Role
 *
 * This script restricts access to specific WooCommerce My Account tabs
 * by removing them from the sidebar for unauthorized users.
 *
 * Works modularly with custom-my-account-tabs.php
 * Ensure this file is loaded *after* the tab registration file
 * Must use a higher priority (e.g. 999) to override default tab additions
 *
 * To add more restricted tabs, update the `get_tab_visibility_rules()` array below.
 */

/**
 * Role-based visibility rules for account tabs
 * Format: 'tab_slug' => ['allowed_role1', 'allowed_role2']
 */
function get_tab_visibility_rules() {
    return [
        'customer-rewards' => ['administrator', 'shop_manager', 'hoster'],
        // 'payouts'         => ['hoster', 'vendor_plus'],
        // 'analytics'       => ['administrator'],
    ];
}

/**
 * Remove tabs the current user should not see
 */
function filter_account_tabs_by_role($items) {
    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;
    $rules = get_tab_visibility_rules();

    foreach ($rules as $tab_slug => $allowed_roles) {
        if (!array_intersect($allowed_roles, $user_roles)) {
            unset($items[$tab_slug]);
        }
    }

    return $items;
}
add_filter('woocommerce_account_menu_items', 'filter_account_tabs_by_role', 999); // High priority!
