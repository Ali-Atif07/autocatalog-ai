jQuery(document).ready(function ($) {

    const showStatus = (message, isError = false) => {
        const $status = $('#aiproduct-status');
        const $text = $('#aiproduct-status-text');
        $status.show();
        $text.html(message);
        $text.css('color', isError ? '#cc0000' : '#00aa00');
    };

    const generate = (type) => {
        // Get product name from title field
        const productName = $('#title').val().trim();
        if (!productName) {
            showStatus('⚠️ Please enter a product name first', true);
            return;
        }

        // Get existing description from editor
        let existingDesc = '';
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
            existingDesc = tinyMCE.get('content').getContent();
        } else {
            existingDesc = $('#content').val();
        }

        const tone = $('#ai-tone').val();
        showStatus('⏳ Generating ' + type + '...');

        $.post(aiproduct.ajax_url, {
            action: 'aiproduct_generate',
            nonce: aiproduct.nonce,
            product_name: productName,
            existing_desc: existingDesc,
            tone: tone,
            type: type
        })
            .done(function (response) {
                if (!response.success) {
                    showStatus('❌ Error: ' + response.data.message, true);
                    return;
                }

                const result = response.data.result;

                if (type === 'description') {
                    // Insert into main WP editor
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                        tinyMCE.get('content').setContent(result);
                    } else {
                        $('#content').val(result);
                    }
                    showStatus('✅ Description generated!');

                } else if (type === 'short_description') {
                    // Insert into WooCommerce short description field
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get('excerpt')) {
                        tinyMCE.get('excerpt').setContent(result);
                    } else {
                        $('#excerpt').val(result);
                    }
                    showStatus('✅ Short description generated!');

                } else if (type === 'tags') {

                    const $tagInput = $('#new-tag-product_tag');
                    if ($tagInput.length) {
                        $tagInput.val(result);
                    }

                    showStatus('🏷️ Suggested tags: <strong>' + result + '</strong>')

                } else if (type === 'meta') {
                    navigator.clipboard.writeText(result).then(() => {
                        showStatus('✅ SEO Meta copied to clipboard: <em>' + result + '</em>');
                    }).catch(() => {
                        showStatus('📋 SEO Meta: <em>' + result + '</em>');
                    });
                }
            })
            .fail(function () {
                showStatus('❌ Request failed. Check your connection.', true);
            });
    };

    // Button click handlers
    $('#ai-gen-desc').on('click', () => generate('description'));
    $('#ai-gen-short-desc').on('click', () => generate('short_description'));
    $('#ai-gen-tags').on('click', () => generate('tags'));
    $('#ai-gen-meta').on('click', () => generate('meta'));

    // Bulk generate button — opens a simple modal
    $('#ai-bulk-generate').on('click', function () {
        // Get all published products via AJAX would need another endpoint
        // For now, open a new page
        window.open(
            ajaxurl.replace('admin-ajax.php', '') + 'admin.php?page=ai-product-assistant&bulk=1',
            '_blank'
        );
    });

    // Set default tone from settings
    if (aiproduct.default_tone) {
        $('#ai-tone').val(aiproduct.default_tone);
    }
});