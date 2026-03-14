<?php
if (!defined('ABSPATH')) exit;

// ─── Register settings ───────────────────────────────────────────
function aiproduct_register_settings() {
    register_setting('aiproduct_settings', 'aiproduct_provider',
        ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('aiproduct_settings', 'aiproduct_api_key',
        ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('aiproduct_settings', 'aiproduct_model',
        ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('aiproduct_settings', 'aiproduct_default_tone',
        ['sanitize_callback' => 'sanitize_text_field']);

    // Custom prompt settings — one per generation type
    register_setting('aiproduct_settings', 'aiproduct_prompt_description',
        ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('aiproduct_settings', 'aiproduct_prompt_short_description',
        ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('aiproduct_settings', 'aiproduct_prompt_tags',
        ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('aiproduct_settings', 'aiproduct_prompt_meta',
        ['sanitize_callback' => 'sanitize_textarea_field']);
}
add_action('admin_init', 'aiproduct_register_settings');

// ─── Enqueue settings page CSS from file ────────────────────────
/*
 * We only load this CSS on OUR settings page.
 * $hook is the current admin page identifier.
 * For WooCommerce subpages it looks like: 'woocommerce_page_ai-product-assistant'
 * strpos() checks if our page slug appears anywhere in $hook.
 */
function aiproduct_enqueue_settings_styles($hook) {
    if (strpos($hook, 'ai-product-assistant') === false) return;

    wp_enqueue_style(
        'aiproduct-settings-css',
        AIPRODUCT_URL . 'assets/css/settings.css',
        [],
        AIPRODUCT_VERSION
    );
}
add_action('admin_enqueue_scripts', 'aiproduct_enqueue_settings_styles');

// ─── Add menus under WooCommerce ─────────────────────────────────
function aiproduct_add_menu() {
    add_submenu_page(
        'woocommerce',
        'AI Product Assistant',
        'AI Assistant',
        'manage_options',
        'ai-product-assistant',
        'aiproduct_render_settings_page'
    );
}
add_action('admin_menu', 'aiproduct_add_menu');


// ─── Default prompts ─────────────────────────────────────────────
/*
 * These are the fallback prompts used when the user hasn't
 * saved a custom one. {product_name}, {tone}, {existing_desc}
 * are placeholder variables replaced at generation time in woo-integration.php
 */
function aiproduct_default_prompts() {
    return [
        'description' => "Write a compelling {tone} WooCommerce product description for: '{product_name}'. {existing_desc}Use HTML formatting (p, ul, li tags). Return only the description.",
        'short_description' => "Write a short, punchy {tone} product summary (2-3 sentences max) for: '{product_name}'. Return only the summary.",
        'tags'        => "Suggest 8-10 WooCommerce product tags for: '{product_name}'. Return ONLY a comma-separated list, nothing else.",
        'meta'        => "Write an SEO meta description under 160 characters for a product called '{product_name}'. Return ONLY the meta description.",
    ];
}

// ─── Settings page renderer ──────────────────────────────────────
function aiproduct_render_settings_page() {
    $provider     = get_option('aiproduct_provider', 'claude');
    $api_key      = get_option('aiproduct_api_key', '');
    $model        = get_option('aiproduct_model', '');
    $default_tone = get_option('aiproduct_default_tone', 'professional');

    // Load saved custom prompts, fall back to defaults if empty
    $defaults = aiproduct_default_prompts();
    $prompts  = [
        'description'       => get_option('aiproduct_prompt_description',       $defaults['description']),
        'short_description' => get_option('aiproduct_prompt_short_description', $defaults['short_description']),
        'tags'              => get_option('aiproduct_prompt_tags',              $defaults['tags']),
        'meta'              => get_option('aiproduct_prompt_meta',              $defaults['meta']),
    ];

    $providers = [
        'claude' => [
            'label'    => 'Claude',
            'company'  => 'Anthropic',
            'icon'     => '🟠',
            'color'    => '#D4622A',
            'models'   => ['claude-opus-4-6', 'claude-sonnet-4-6', 'claude-haiku-4-5-20251001'],
            'default'  => 'claude-sonnet-4-6',
            'key_url'  => 'https://console.anthropic.com',
            'key_hint' => 'Starts with sk-ant-...',
            'badge'    => 'Powerful',
        ],
        'openai' => [
            'label'    => 'GPT-4',
            'company'  => 'OpenAI',
            'icon'     => '🟢',
            'color'    => '#10A37F',
            'models'   => ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo'],
            'default'  => 'gpt-4o',
            'key_url'  => 'https://platform.openai.com/api-keys',
            'key_hint' => 'Starts with sk-...',
            'badge'    => 'Popular',
        ],
        'gemini' => [
            'label'    => 'Gemini',
            'company'  => 'Google',
            'icon'     => '🔵',
            'color'    => '#4285F4',
            'models'   => ['gemini-2.0-flash', 'gemini-1.5-pro', 'gemini-1.5-flash'],
            'default'  => 'gemini-2.0-flash',
            'key_url'  => 'https://aistudio.google.com/app/apikey',
            'key_hint' => 'Starts with AIza...',
            'badge'    => 'Free Tier',
        ],
        'groq' => [
            'label'    => 'Groq',
            'company'  => 'Groq',
            'icon'     => '⚡',
            'color'    => '#F55036',
            'models'   => ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant', 'mixtral-8x7b-32768', 'gemma2-9b-it'],
            'default'  => 'llama-3.3-70b-versatile',
            'key_url'  => 'https://console.groq.com/keys',
            'key_hint' => 'Starts with gsk_...',
            'badge'    => 'Ultra Fast & Free',
        ],
    ];

    $tones = [
        'professional' => ['icon' => '💼', 'desc' => 'Clean & business-ready'],
        'casual'       => ['icon' => '😊', 'desc' => 'Friendly & approachable'],
        'luxury'       => ['icon' => '✨', 'desc' => 'Premium & aspirational'],
        'playful'      => ['icon' => '🎉', 'desc' => 'Fun & energetic'],
        'technical'    => ['icon' => '⚙️', 'desc' => 'Detailed & precise'],
    ];

    /*
     * Prompt field definitions — label, icon, setting key, and which
     * variables the user can insert with one click.
     */
    $prompt_fields = [
        'description' => [
            'label' => 'Product Description',
            'icon'  => '✍️',
            'key'   => 'aiproduct_prompt_description',
            'vars'  => ['{product_name}', '{tone}', '{existing_desc}'],
        ],
        'short_description' => [
            'label' => 'Short Description',
            'icon'  => '📝',
            'key'   => 'aiproduct_prompt_short_description',
            'vars'  => ['{product_name}', '{tone}'],
        ],
        'tags' => [
            'label' => 'Product Tags',
            'icon'  => '🏷️',
            'key'   => 'aiproduct_prompt_tags',
            'vars'  => ['{product_name}'],
        ],
        'meta' => [
            'label' => 'SEO Meta Description',
            'icon'  => '🔍',
            'key'   => 'aiproduct_prompt_meta',
            'vars'  => ['{product_name}'],
        ],
    ];
    ?>

    <div id="aiproduct-wrap">

        <!-- Header -->
        <div class="aip-header">
            <div class="aip-logo">🤖</div>
            <div class="aip-header-text">
                <h1>AI Product Assistant</h1>
                <p>Generate product descriptions, tags &amp; SEO meta with AI</p>
            </div>
            <span class="aip-version-badge">v1.0.0</span>
        </div>

        <form method="post" action="options.php" id="aip-form">
            <?php settings_fields('aiproduct_settings'); ?>

            <div class="aip-layout">

                <!-- ── Main Column ── -->
                <div class="aip-main">

                    <!-- Provider Selection -->
                    <div class="aip-card">
                        <div class="aip-card-header">
                            <div class="card-icon">🔌</div>
                            <span class="aip-card-title">AI Provider</span>
                        </div>
                        <div class="aip-card-body">
                            <div class="aip-provider-grid">
                                <?php foreach ($providers as $key => $p): ?>
                                <label class="aip-provider-card <?php echo $provider === $key ? 'selected' : ''; ?>"
                                       style="--provider-color: <?php echo esc_attr($p['color']); ?>"
                                       data-provider="<?php echo esc_attr($key); ?>">
                                    <input type="radio" name="aiproduct_provider"
                                           value="<?php echo esc_attr($key); ?>"
                                           <?php checked($provider, $key); ?>>
                                    <div class="provider-top">
                                        <div class="provider-icon-wrap"><?php esc_html($p['icon']); ?></div>
                                        <div class="provider-check"></div>
                                    </div>
                                    <div class="provider-name"><?php echo esc_html($p['label']); ?></div>
                                    <div class="provider-company"><?php echo esc_html($p['company']); ?></div>
                                    <span class="provider-badge"><?php echo esc_html($p['badge']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- API Key & Model -->
                    <div class="aip-card">
                        <div class="aip-card-header">
                            <div class="card-icon">🔑</div>
                            <span class="aip-card-title">API Configuration</span>
                        </div>
                        <div class="aip-card-body">
                            <div class="aip-field">
                                <label class="aip-label">API Key</label>
                                <div class="aip-input-wrap">
                                    <input type="password"
                                           id="aip-api-key"
                                           name="aiproduct_api_key"
                                           class="aip-input"
                                           value="<?php echo esc_attr($api_key); ?>"
                                           placeholder="Paste your API key here..." />
                                    <button type="button" class="eye-btn" id="toggle-key">👁</button>
                                </div>
                                <p class="aip-hint" id="aip-key-hint"></p>
                            </div>

                            <div class="aip-field">
                                <label class="aip-label">Model</label>
                                <input type="text"
                                       id="aip-model"
                                       name="aiproduct_model"
                                       class="aip-input"
                                       value="<?php echo esc_attr($model); ?>"
                                       placeholder="Select below or type a model name" />
                                <div class="aip-model-chips" id="aip-model-chips"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Default Tone -->
                    <div class="aip-card">
                        <div class="aip-card-header">
                            <div class="card-icon">🎨</div>
                            <span class="aip-card-title">Default Writing Tone</span>
                        </div>
                        <div class="aip-card-body">
                            <div class="aip-tone-grid">
                                <?php foreach ($tones as $key => $t): ?>
                                <label class="aip-tone-btn <?php echo $default_tone === $key ? 'selected' : ''; ?>"
                                       data-tone="<?php echo esc_attr($key); ?>">
                                    <input type="radio" name="aiproduct_default_tone"
                                           value="<?php echo esc_attr($key); ?>"
                                           <?php checked($default_tone, $key); ?>>
                                    <span class="tone-icon"><?php esc_html($t['icon']); ?></span>
                                    <span class="tone-name"><?php echo esc_html(ucfirst($key)); ?></span>
                                    <span class="tone-desc"><?php echo esc_html($t['desc']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ── Custom Prompts ── -->
                    <div class="aip-card">
                        <div class="aip-card-header">
                            <div class="card-icon">✏️</div>
                            <span class="aip-card-title">Custom Prompts</span>
                        </div>
                        <div class="aip-card-body">
                            <p style="font-size:13px; color:var(--muted); margin:0 0 18px;">
                                Customise the AI prompt for each generation type.
                                Click a variable tag to insert it at the cursor position.
                            </p>

                            <?php foreach ($prompt_fields as $type => $field): ?>
                            <div class="aip-prompt-field">
                                <div class="aip-prompt-label">
                                    <strong>
                                        <?php echo esc_html($field['icon']); ?>
                                        <?php echo esc_html($field['label']); ?>
                                    </strong>
                                    <!--
                                        Reset button: JS reads the default prompt
                                        from the data-default attribute and puts it back.
                                        data-target tells JS which textarea to reset.
                                    -->
                                    <button type="button"
                                            class="reset-btn"
                                            data-target="prompt-<?php echo esc_attr($type); ?>"
                                            data-default="<?php echo esc_attr($defaults[$type]); ?>">
                                        ↺ Reset to default
                                    </button>
                                </div>

                                <textarea
                                    id="prompt-<?php echo esc_attr($type); ?>"
                                    name="<?php echo esc_attr($field['key']); ?>"
                                    class="aip-prompt-textarea"
                                    rows="3"
                                ><?php echo esc_textarea($prompts[$type]); ?></textarea>

                                <!-- Available variables for this prompt type -->
                                <div class="aip-prompt-vars">
                                    Available variables:
                                    <?php foreach ($field['vars'] as $var): ?>
                                    <!--
                                        data-var tells JS what text to insert.
                                        data-target tells JS which textarea to insert into.
                                    -->
                                    <span class="aip-prompt-var-tag"
                                          data-var="<?php echo esc_attr($var); ?>"
                                          data-target="prompt-<?php echo esc_attr($type); ?>">
                                        <?php echo esc_html($var); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>

                        </div>
                    </div>

                    <!-- Save -->
                    <button type="submit" class="aip-save-btn">
                        💾 Save Settings
                    </button>

                </div><!-- /.aip-main -->

                <!-- ── Sidebar ── -->
                <div class="aip-sidebar">

                    <!-- Test Connection -->
                    <div class="aip-card">
                        <div class="aip-card-header">
                            <div class="card-icon">🧪</div>
                            <span class="aip-card-title">Test Connection</span>
                        </div>
                        <div class="aip-card-body">
                            <p style="font-size:13px; color:var(--muted); margin:0 0 13px;">
                                Verify your API key and model are working.
                            </p>
                            <button type="button" class="aip-test-btn" id="aip-test-btn">
                                ⚡ Test Connection
                            </button>
                            <div id="aip-test-result"></div>
                        </div>
                    </div>

                    <!-- Quick Guide -->
                    <div class="aip-card">
                        <div class="aip-card-header">
                            <div class="card-icon">💡</div>
                            <span class="aip-card-title">Quick Guide</span>
                        </div>
                        <div class="aip-card-body" style="padding:14px 20px;">
                            <div class="aip-info-item">
                                <div class="info-icon">⚡</div>
                                <div>
                                    <div class="info-title">Free & Fast — Use Groq</div>
                                    <div class="info-desc">Groq has a generous free tier with very fast responses.</div>
                                </div>
                            </div>
                            <div class="aip-info-item">
                                <div class="info-icon">🔵</div>
                                <div>
                                    <div class="info-title">Gemini Free Tier</div>
                                    <div class="info-desc">Gemini 2.0 Flash is free via Google AI Studio.</div>
                                </div>
                            </div>
                            <div class="aip-info-item">
                                <div class="info-icon">🔒</div>
                                <div>
                                    <div class="info-title">Your Key, Your Data</div>
                                    <div class="info-desc">Keys stored only in your database. Never shared.</div>
                                </div>
                            </div>
                            <div class="aip-info-item">
                                <div class="info-icon">✏️</div>
                                <div>
                                    <div class="info-title">Custom Prompts</div>
                                    <div class="info-desc">Use <code>{product_name}</code> and <code>{tone}</code> as placeholders in your prompts.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Get API Keys -->
                    <div class="aip-card">
                        <div class="aip-card-header">
                            <div class="card-icon">🔗</div>
                            <span class="aip-card-title">Get API Keys</span>
                        </div>
                        <div class="aip-card-body" style="padding:12px 20px; display:flex; flex-direction:column; gap:7px;">
                            <?php foreach ($providers as $key => $p): ?>
                            <a href="<?php echo esc_url($p['key_url']); ?>"
                               target="_blank"
                               class="aip-key-link"
                               style="display:flex; align-items:center; gap:9px; padding:9px 11px;
                                      background:var(--surface2); border:1px solid var(--border);
                                      border-radius:8px; text-decoration:none; transition:all 0.18s;
                                      color:var(--text); font-size:13px; font-weight:500;"
                               onmouseover="this.style.borderColor='<?php echo esc_attr($p['color']); ?>'"
                               onmouseout="this.style.borderColor='var(--border)'">
                                <span style="font-size:15px;"><?php echo $p['icon']; ?></span>
                                <span><?php echo esc_html($p['company']); ?> Console</span>
                                <span style="margin-left:auto; color:var(--muted); font-size:11px;">↗</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div><!-- /.aip-sidebar -->

            </div><!-- /.aip-layout -->
        </form>

        <div id="aip-save-notice">✅ Settings saved!</div>

    </div><!-- /#aiproduct-wrap -->

    <script>
    (function() {
        const providers = <?php echo json_encode($providers); ?>;
        // Default prompts available to JS for the reset button
        const defaultPrompts = <?php echo json_encode($defaults); ?>;

        // ── Provider card selection ──
        document.querySelectorAll('.aip-provider-card').forEach(function(card) {
            card.addEventListener('click', function() {
                document.querySelectorAll('.aip-provider-card').forEach(function(c) {
                    c.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
                updateProviderUI(this.dataset.provider);
            });
        });

        function updateProviderUI(providerKey) {
            const p = providers[providerKey];
            if (!p) return;

            // Update API key hint text
            document.getElementById('aip-key-hint').innerHTML =
                '<span style="font-family:Space Mono,monospace;font-size:11px">' + p.key_hint + '</span>'
                + ' &nbsp;·&nbsp; <a href="' + p.key_url + '" target="_blank">Get key →</a>';

            // Rebuild model chips
            const chipsEl = document.getElementById('aip-model-chips');
            chipsEl.innerHTML = p.models.map(function(m) {
                return '<span class="aip-model-chip" data-model="' + m + '">' + m + '</span>';
            }).join('');

            // Attach click handlers to new chips
            chipsEl.querySelectorAll('.aip-model-chip').forEach(function(chip) {
                chip.addEventListener('click', function() {
                    setModel(this.dataset.model);
                });
            });

            // Auto-fill default model only if field is empty
            const modelInput = document.getElementById('aip-model');
            if (!modelInput.value) modelInput.value = p.default;
        }

        function setModel(m) {
            document.getElementById('aip-model').value = m;
            document.querySelectorAll('.aip-model-chip').forEach(function(c) {
                const isActive = c.dataset.model === m;
                c.classList.toggle('active', isActive);
            });
        }

        // ── Tone selection ──
        document.querySelectorAll('.aip-tone-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.aip-tone-btn').forEach(function(b) {
                    b.classList.remove('selected');
                });
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // ── Toggle API key visibility ──
        document.getElementById('toggle-key').addEventListener('click', function() {
            const input = document.getElementById('aip-api-key');
            input.type = input.type === 'password' ? 'text' : 'password';
            this.textContent = input.type === 'password' ? '👁' : '🙈';
        });

        // ── Test connection ──
        document.getElementById('aip-test-btn').addEventListener('click', function() {
            const resultEl = document.getElementById('aip-test-result');
            resultEl.className = '';
            resultEl.style.display = 'block';
            resultEl.textContent = '⏳ Testing...';

            fetch(ajaxurl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'aiproduct_test_connection',
                    nonce:  '<?php echo esc_js(wp_create_nonce("aiproduct_nonce")); ?>'
                })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    resultEl.className = 'success';
                    resultEl.textContent = '✅ Connected! ' + (data.data && data.data.message ? data.data.message : 'Connected successfully!');
                } else {
                    resultEl.className = 'error';
                    resultEl.textContent = data.data && data.data.message
                        ? data.data.message
                        : 'Could not connect. Check your API key and model.';
                }
            })
            .catch(function() {
                resultEl.className = 'error';
                resultEl.textContent = '❌ Request failed. Check your network.';
            });
        });

        // ── Variable tag insertion ──
        /*
         * When user clicks a {variable} tag, we insert it into the
         * textarea at the current cursor position.
         * selectionStart / selectionEnd give us cursor position.
         * We rebuild the string: before cursor + variable + after cursor.
         */
        document.querySelectorAll('.aip-prompt-var-tag').forEach(function(tag) {
            tag.addEventListener('click', function() {
                const varText    = this.dataset.var;
                const targetId   = this.dataset.target;
                const textarea   = document.getElementById(targetId);
                if (!textarea) return;

                const start  = textarea.selectionStart;
                const end    = textarea.selectionEnd;
                const before = textarea.value.substring(0, start);
                const after  = textarea.value.substring(end);

                // Insert variable at cursor
                textarea.value = before + varText + after;

                // Move cursor to end of inserted text
                textarea.selectionStart = start + varText.length;
                textarea.selectionEnd   = start + varText.length;
                textarea.focus();
            });
        });

        // ── Reset prompt to default ──
        /*
         * Each reset button stores the default prompt in data-default
         * and the textarea id in data-target.
         * We just copy that value back into the textarea.
         */
        document.querySelectorAll('.reset-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const textarea = document.getElementById(targetId);
                if (!textarea) return;

                // Confirm before resetting — prevents accidental loss of work
                if (confirm('Reset this prompt to the default? Your custom text will be lost.')) {
                    textarea.value = this.dataset.default;
                }
            });
        });

        // ── Save toast ──
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('settings-updated') === 'true') {
            const notice = document.getElementById('aip-save-notice');
            notice.style.display = 'flex';
            setTimeout(function() { notice.style.display = 'none'; }, 3000);
        }

        // ── Initialise on load ──
        const currentProvider = document.querySelector('input[name="aiproduct_provider"]:checked');
        if (currentProvider) updateProviderUI(currentProvider.value);

        const currentModel = document.getElementById('aip-model').value;
        if (currentModel) {
            setTimeout(function() { setModel(currentModel); }, 40);
        }

    })();
    </script>

<?php
}