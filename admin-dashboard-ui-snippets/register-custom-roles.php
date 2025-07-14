/**
 * Custom Role Registration Script
 *
 * This script allows you to define and register multiple custom WordPress roles
 * in a clean, modular, and readable way. Each role is cloned from an existing
 * base role (e.g., 'customer', 'subscriber') and may include extra capabilities.
 *
 * Easy to maintain
 * Easy to extend
 * No nested arrays or messy logic
 *
 * To add a new role:
 * 1. Add a new item inside get_custom_roles()
 * 2. Define its 'key', 'label', 'clone_from', and optional 'extra_caps'
 * 3. That's it — the system handles the rest automatically

 * Define your custom roles here.
 * Each entry contains:
 * - 'key'        → the role ID
 * - 'label'      → how it appears in WP admin
 * - 'clone_from' → an existing role to copy capabilities from
 * - 'extra_caps' → (optional) new capabilities to add
 */
function get_custom_roles() {
    return [

        // Used to gate host-only dashboard tabs
        [
            'key'        => 'hoster',
            'label'      => 'Hoster',
            'clone_from' => 'customer', // WooCommerce role
            'extra_caps' => [
                // 'upload_files' => true,
                // 'read_private_pages' => true,
            ],
        ],

        // Used for vendors who need extra product permissions
        [
            'key'        => 'vendor_plus',
            'label'      => 'Vendor Plus',
            'clone_from' => 'shop_manager',
            'extra_caps' => [
                // 'edit_products' => true,
            ],
        ],

        // Photographer role can upload files but not edit posts
        [
            'key'        => 'photographer',
            'label'      => 'Photographer',
            'clone_from' => 'subscriber',
            'extra_caps' => [
                'upload_files' => true,
            ],
        ],

    ];
}

/**
 * Register all custom roles from above list
 */
add_action('init', function () {
    foreach (get_custom_roles() as $role) {
        $key        = $role['key'];
        $label      = $role['label'];
        $clone_from = $role['clone_from'];
        $extra_caps = $role['extra_caps'] ?? [];

        if (get_role($key)) continue; // Already exists

        $base = get_role($clone_from);
        if (!$base) {
            error_log("⚠️ Role '{$key}' not created — base role '{$clone_from}' not found.");
            continue;
        }

        $caps = array_merge($base->capabilities, $extra_caps);
        add_role($key, $label, $caps);
    }
});
