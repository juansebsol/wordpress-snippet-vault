<?php
/**
 * 🎯 Conditionally Show WooCommerce My Account Tabs Based on User Role
 *
 * This script allows you to restrict specific dashboard tabs in the 
 * WooCommerce My Account area based on user roles.
 *
 * ✅ Supports multiple tabs
 * ✅ Supports multiple roles per tab
 * ✅ Easy to extend — just update the config array
 *
 * Example: Only 'hoster', 'admin' and 'shop_manager' can see the 'customer-rewards' tab
 */

/**
 * 🧾 Define which roles can see which tabs
 * Key = WooCommerce tab slug
 * Value = Array of roles that can access that tab
 */
function get_tab_visibility_rules() {
    return [
        'customer-rewards' => ['administrator', 'shop_manager', 'hoster'],
        // 'my-custom-tab'  => ['vendor_plus', 'editor'],
        // Add more tab slugs and allowed roles as needed
    ];
}

/**
 * 🚫 Remove tabs if the current user doesn't have access
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
add_filter('woocommerce_account_menu_items', 'filter_account_tabs_by_role');

