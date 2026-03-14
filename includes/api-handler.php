<?php
if (!defined('ABSPATH')) exit;

function aiproduct_call_ai($prompt) {
    $api_key  = get_option('aiproduct_api_key');
    $model    = get_option('aiproduct_model');
    $provider = get_option('aiproduct_provider', 'claude');

    if (empty($api_key)) {
        return ['success' => false, 'message' => 'No API key set. Go to WooCommerce → AI Assistant to add your key.'];
    }

    switch ($provider) {

        // ── Claude (Anthropic) ──
        case 'claude':
            if (empty($model)) $model = 'claude-sonnet-4-6';

            $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
                'timeout' => 30,
                'headers' => [
                    'x-api-key'         => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type'      => 'application/json',
                ],
                'body' => json_encode([
                    'model'      => $model,
                    'max_tokens' => 1024,
                    'messages'   => [['role' => 'user', 'content' => $prompt]]
                ])
            ]);

            if (is_wp_error($response)) {
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                return ['success' => false, 'message' => $body['error']['message']];
            }

            return ['success' => true, 'result' => $body['content'][0]['text'] ?? ''];

        // ── OpenAI ──
        case 'openai':
            if (empty($model)) $model = 'gpt-4o';

            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body' => json_encode([
                    'model'    => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]]
                ])
            ]);

            if (is_wp_error($response)) {
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                return ['success' => false, 'message' => $body['error']['message']];
            }

            return ['success' => true, 'result' => $body['choices'][0]['message']['content'] ?? ''];

        // ── Google Gemini ──
        case 'gemini':
            if (empty($model)) $model = 'gemini-2.0-flash';

            // Gemini uses API key as query param
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

            $response = wp_remote_post($url, [
                'timeout' => 30,
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => json_encode([
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => 1024,
                        'temperature'     => 0.7,
                    ]
                ])
            ]);

            if (is_wp_error($response)) {
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                return ['success' => false, 'message' => $body['error']['message']];
            }

            $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty($text)) {
                return ['success' => false, 'message' => 'Empty response from Gemini. Check your model name.'];
            }

            return ['success' => true, 'result' => $text];

        // ── Groq ──
        case 'groq':
            if (empty($model)) $model = 'llama-3.3-70b-versatile';

            // Groq is OpenAI-compatible so same API format
            $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body' => json_encode([
                    'model'    => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 1024,
                ])
            ]);

            if (is_wp_error($response)) {
                return ['success' => false, 'message' => $response->get_error_message()];
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                return ['success' => false, 'message' => $body['error']['message']];
            }

            return ['success' => true, 'result' => $body['choices'][0]['message']['content'] ?? ''];

        default:
            return ['success' => false, 'message' => 'Unknown provider: ' . $provider];
    }
}

// ── Test connection AJAX ──
function aiproduct_test_connection() {
    check_ajax_referer('aiproduct_nonce', 'nonce');

    $provider = get_option('aiproduct_provider', 'claude');
    $api_key  = get_option('aiproduct_api_key', '');
    $model    = get_option('aiproduct_model', '');

    if (empty($api_key)) {
        wp_send_json_error(['message' => 'No API key saved. Add your key above and save settings first.']);
        return;
    }

    if (empty($model)) {
        wp_send_json_error(['message' => 'No model selected. Pick a model above and save settings first.']);
        return;
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message'=>'Unauthorized']);
    }

    $result = aiproduct_call_ai('Reply with exactly: "Connection successful" — nothing else.');

    if ($result['success']) {
        wp_send_json_success(['message' => 'Your ' . ucfirst($provider) . ' API key and model are working correctly.']);
        return;
    } 
    $raw     = strtolower($result['message'] ?? '');
    $message = aiproduct_friendly_error($raw, $provider);

    wp_send_json_error(['message' => $message]);
}
add_action('wp_ajax_aiproduct_test_connection', 'aiproduct_test_connection');


/*
 * Takes the raw error string from the API and returns
 * a clean, user-friendly message.
 * strpos() checks if a substring exists anywhere in the string.
 */
function aiproduct_friendly_error($raw, $provider) {

    // Invalid / wrong API key
    if (strpos($raw, 'invalid') !== false
     || strpos($raw, 'incorrect') !== false
     || strpos($raw, 'unauthorized') !== false
     || strpos($raw, 'authentication') !== false
     || strpos($raw, 'api_key') !== false
     || strpos($raw, 'x-api-key') !== false) {
        return 'Invalid API key. Double-check your key in the settings above — it may have been copied incorrectly or revoked.';
    }

    // Wrong model name
    if (strpos($raw, 'model') !== false
     || strpos($raw, 'not found') !== false
     || strpos($raw, 'does not exist') !== false) {
        return 'Model not found. Check that the model name is spelled correctly (e.g. claude-sonnet-4-6 or gpt-4o).';
    }

    // Rate limited
    if (strpos($raw, 'rate') !== false || strpos($raw, 'quota') !== false || strpos($raw, '429') !== false) {
        return 'Rate limit reached. You have hit your API usage limit. Wait a moment and try again, or check your plan.';
    }

    // No credits / billing
    if (strpos($raw, 'billing') !== false
     || strpos($raw, 'credit') !== false
     || strpos($raw, 'payment') !== false
     || strpos($raw, 'insufficient') !== false) {
        return 'Billing issue. Your API account may be out of credits. Check your billing at the provider dashboard.';
    }

    // Network / timeout
    if (strpos($raw, 'timeout') !== false || strpos($raw, 'curl') !== false) {
        return 'Connection timed out. The API did not respond in time. Check your server\'s internet connection.';
    }

    // Fallback — still friendly, no raw text shown
    return 'Could not connect to ' . ucfirst($provider) . '. Please check your API key and model, then try again.';
}