/**
 * WooCommerce My Account: Custom Tab Registration
 *
 * This script registers custom endpoints (tabs) in the WooCommerce "My Account" area.
 * You can define multiple tabs using a structured configuration array.
 *
 * Important:
 * After adding new tabs, go to Settings → Permalinks and click "Save" to flush rewrite rules.
 */

/**
 * Custom My Account Tab Configuration
 *
 * Each tab should be defined with the following keys:
 * - 'endpoint' => (string) The URL slug for the tab (e.g., 'host-dashboard')
 * - 'label'    => (string) The label displayed in the account menu
 * - 'content'  => (callable) The function that outputs the tab content
 */
function get_custom_account_tabs() {
    return [

        // Soporte tab with contact form
        [
            'endpoint' => 'customer-support',
            'label'    => 'Soporte',
            'content'  => 'render_support_tab_content',
        ],

        // Recompensas tab with user reward data + chart
        [
            'endpoint' => 'customer-rewards',
            'label'    => 'Recompensas',
            'content'  => 'render_rewards_tab_content',
        ],

        // Add more tabs below as needed:
        // [
        //     'endpoint' => 'payouts',
        //     'label'    => 'Pagos',
        //     'content'  => 'render_payouts_tab_content',
        // ],
    ];
}

/**
 * Register endpoints (used in URLs)
 */
add_action('init', function () {
    foreach (get_custom_account_tabs() as $tab) {
        add_rewrite_endpoint($tab['endpoint'], EP_ROOT | EP_PAGES);
    }
});

/**
 * Register query vars for each tab
 */
add_filter('query_vars', function ($vars) {
    foreach (get_custom_account_tabs() as $tab) {
        $vars[] = $tab['endpoint'];
    }
    return $vars;
}, 0);

/**
 * Add tabs to WooCommerce My Account sidebar menu
 */
add_filter('woocommerce_account_menu_items', function ($items) {
    foreach (get_custom_account_tabs() as $tab) {
        $items[$tab['endpoint']] = $tab['label'];
    }
    return $items;
});

/**
 * Hook tab content to WooCommerce endpoint
 */
add_action('init', function () {
    foreach (get_custom_account_tabs() as $tab) {
        add_action(
            'woocommerce_account_' . $tab['endpoint'] . '_endpoint',
            $tab['content']
        );
    }
});
