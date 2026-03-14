<?php
if (!defined('ABSPATH')) exit;

// ─── Enqueue scripts on product edit pages only ───────────────────
function aiproduct_enqueue_scripts($hook) {
    global $post;
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
    if (!isset($post) || $post->post_type !== 'product') return;

    wp_enqueue_style(
        'aiproduct-admin-css',
        AIPRODUCT_URL . 'assets/css/admin.css',
        [],
        AIPRODUCT_VERSION
    );

    wp_enqueue_script(
        'aiproduct-admin-js',
        AIPRODUCT_URL . 'assets/js/admin.js',
        ['jquery'],
        AIPRODUCT_VERSION,
        true
    );

    wp_localize_script('aiproduct-admin-js', 'aiproduct', [
        'ajax_url'     => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('aiproduct_nonce'),
        'default_tone' => get_option('aiproduct_default_tone', 'professional'),
    ]);
}
add_action('admin_enqueue_scripts', 'aiproduct_enqueue_scripts');

// ─── AI panel on product edit page ───────────────────────────────
function aiproduct_product_panel() {
    global $post;
    if (!isset($post) || $post->post_type !== 'product') return;

    $tones = ['professional', 'casual', 'luxury', 'playful', 'technical'];
    ?>
    <div id="aiproduct-panel" class="postbox">
        <h2 class="hndle">🤖 AI Product Assistant</h2>
        <div class="inside">

            <div class="aiproduct-row">
                <label for="ai-tone"><strong>Tone:</strong></label>
                <select id="ai-tone">
                    <?php foreach ($tones as $tone): ?>
                        <option value="<?php echo esc_attr($tone); ?>">
                            <?php echo ucfirst($tone); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="aiproduct-buttons">
                <button type="button" class="button button-primary" id="ai-gen-desc">
                    ✍️ Generate Description
                </button>
                <button type="button" class="button button-primary" id="ai-gen-short-desc">
                    📝 Short Description
                </button>
                <button type="button" class="button" id="ai-gen-tags">
                    🏷️ Suggest Tags
                </button>
                <button type="button" class="button" id="ai-gen-meta">
                    🔍 SEO Meta
                </button>
            </div>

            <div id="aiproduct-status" style="margin-top:10px; display:none;">
                <span id="aiproduct-status-text"></span>
            </div>

        </div>
    </div>
    <?php
}
add_action('edit_form_after_title', 'aiproduct_product_panel');

// ─── Helper: resolve a prompt template ───────────────────────────
/*
 * This function takes a prompt template (which may contain placeholders
 * like {product_name}, {tone}, {existing_desc}) and replaces them
 * with the real values passed in $vars.
 *
 * str_replace() replaces all occurrences of each key with its value.
 * array_keys() extracts the placeholder names.
 * array_values() extracts the replacement strings.
 *
 * Example:
 *   template: "Write a {tone} description for '{product_name}'."
 *   vars:     ['{tone}' => 'casual', '{product_name}' => 'Blue Jeans']
 *   result:   "Write a casual description for 'Blue Jeans'."
 */
function aiproduct_resolve_prompt($template, $vars) {
    return str_replace(array_keys($vars), array_values($vars), $template);
}

// ─── Helper: load prompt for a given type ────────────────────────
/*
 * Reads the user's saved custom prompt from wp_options.
 * Falls back to the default prompt if nothing is saved.
 * aiproduct_default_prompts() is defined in admin-settings.php.
 */
function aiproduct_get_prompt($type) {
    $defaults = aiproduct_default_prompts();
    $key      = 'aiproduct_prompt_' . $type;
    // get_option returns false if not set, so we use the default as fallback
    return get_option($key, $defaults[$type] ?? '');
}

// ─── AJAX: single product generation ─────────────────────────────
function aiproduct_ajax_generate() {
    check_ajax_referer('aiproduct_nonce', 'nonce');

    if (!current_user_can('edit_products')) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $product_name  = sanitize_text_field($_POST['product_name']  ?? '');
    $existing_desc = sanitize_textarea_field($_POST['existing_desc'] ?? '');
    $tone          = sanitize_text_field($_POST['tone']          ?? 'professional');
    $type          = sanitize_text_field($_POST['type']          ?? 'description');

    if (empty($product_name)) {
        wp_send_json_error(['message' => 'Product name is required']);
        return;
    }

    // Load the prompt template (custom or default)
    $template = aiproduct_get_prompt($type);

    /*
     * Build the variable map for placeholder replacement.
     * {existing_desc} is only meaningful for the description type —
     * for others it will be an empty string which is fine.
     */
    $existing_note = $existing_desc
        ? 'Improve upon this existing description: ' . $existing_desc . ' '
        : '';

    $vars = [
        '{product_name}'  => $product_name,
        '{tone}'          => $tone,
        '{existing_desc}' => $existing_note,
    ];

    $prompt = aiproduct_resolve_prompt($template, $vars);
    $result = aiproduct_call_ai($prompt);

    if ($result['success']) {
        wp_send_json_success(['result' => $result['result'], 'type' => $type]);
    } else {
        wp_send_json_error(['message' => $result['message']]);
    }
}
add_action('wp_ajax_aiproduct_generate', 'aiproduct_ajax_generate');