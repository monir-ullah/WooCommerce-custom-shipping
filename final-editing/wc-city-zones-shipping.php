<?php
/*
Plugin Name: WooCommerce City Zones
Plugin URI: https://example.com/
Description: Add a custom City Zones repeater field under WooCommerce → Settings → Shipping.
Version: 1.6
Author: Monir Ullah
*/

if (!defined('ABSPATH'))
    exit;

// -----------------------------
// Add toggle + repeater under Shipping Tab
// -----------------------------
add_action('woocommerce_settings_shipping', function () {
    $screen = get_current_screen();
    if (!isset($screen->id) || $screen->id !== 'woocommerce_page_wc-settings')
        return;
    if (!isset($_GET['tab']) || $_GET['tab'] !== 'shipping' || !empty($_GET['section']))
        return;

    $enabled = get_option('wc_city_zones_enabled', 'yes');
    ?>
    <div id="cz-enable-box" style="margin-top:25px;padding:20px;background:#fff;border:1px solid #ddd;border-radius:6px;">
        <h2 style="margin-top:0;">City Zones Settings</h2>
        <p>Enable or disable City Zones. When enabled, WooCommerce default shipping will be hidden.</p>

        <label class="cz-switch">
            <input type="checkbox" id="cz-enable-toggle" <?php checked($enabled, 'yes'); ?>>
            <span class="cz-slider round"></span>
        </label>
        <span class="cz-status" style="margin-left:10px;">
            <?php echo $enabled === 'yes' ? '✅ Enabled' : '❌ Disabled'; ?>
        </span>
        <span class="cz-saving" style="display:none;margin-left:10px;color:#2271b1;">Saving...</span>
    </div>

    <style>
        .cz-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        .cz-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .cz-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 26px;
        }
        .cz-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked+.cz-slider {
            background-color: #2271b1;
        }
        input:checked+.cz-slider:before {
            transform: translateX(24px);
        }
    </style>

    <script>
        jQuery(function ($) {
            const $toggle = $('#cz-enable-toggle');

            $toggle.on('change', function () {
                const enabled = this.checked ? 'yes' : 'no';
                $('.cz-saving').show();
                $.post(ajaxurl, {
                    action: 'wc_city_zones_toggle_enable',
                    nonce: '<?php echo wp_create_nonce('wc_cz_toggle_nonce'); ?>',
                    enabled
                }, function (res) {
                    $('.cz-saving').fadeOut(300);
                    $('.cz-status').text(enabled === 'yes' ? '✅ Enabled' : '❌ Disabled');
                    if (enabled === 'yes') {
                        $('#cz-main-repeater').slideDown(300);
                    } else {
                        $('#cz-main-repeater').slideUp(300);
                    }
                });
            });
        });
    </script>
    <?php

    if ($enabled !== 'yes')
        return;

    $data = get_option('wc_city_zones_main_repeater', '[]');
    $items = json_decode($data, true);
    if (!is_array($items))
        $items = [];
    ?>
    <div id="cz-main-repeater" style="margin-top:30px;padding:20px;background:#fff;border:1px solid #ddd;border-radius:6px;">
        <h2 style="margin-top:0;">City Zones Shipping Rates</h2>
        <p class="description">Add city-based delivery rates (e.g., Inside Dhaka, Outside Dhaka). These are saved automatically via AJAX.</p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width:40%">Zone Label</th>
                    <th style="width:40%">Shipping Cost (<?php echo get_woocommerce_currency_symbol(); ?>)</th>
                    <th style="width:20%">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $row): ?>
                    <tr>
                        <td><input type="text" class="cz-label" value="<?php echo esc_attr($row['label']); ?>" placeholder="e.g. Inside Dhaka" style="width:100%"></td>
                        <td><input type="number" step="0.01" min="0" class="cz-price" value="<?php echo esc_attr($row['price']); ?>" placeholder="0.00" style="width:100%"></td>
                        <td><button type="button" class="button link-delete cz-remove">Remove</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p>
            <button type="button" class="button button-primary cz-add">Add Zone</button>
            <span class="cz-saving" style="display:none;margin-left:10px;color:#2271b1;">Saving...</span>
        </p>
    </div>

    <script>
        jQuery(function ($) {
            const $wrap = $('#cz-main-repeater');
            const $tbody = $wrap.find('tbody');

            function readRows() {
                let rows = [];
                $tbody.find('tr').each(function () {
                    const label = $(this).find('.cz-label').val() || '';
                    const price = parseFloat($(this).find('.cz-price').val() || '0') || 0;
                    if (label) {
                        rows.push({ label: label, price: price });
                    }
                });
                return rows;
            }

            function saveRows() {
                $wrap.find('.cz-saving').show();
                $.post(ajaxurl, {
                    action: 'wc_city_zones_main_save',
                    nonce: '<?php echo wp_create_nonce('wc_cz_main_nonce'); ?>',
                    data: JSON.stringify(readRows())
                }).always(() => $wrap.find('.cz-saving').fadeOut(300));
            }

            $wrap.on('click', '.cz-add', function () {
                $tbody.append(`<tr>
                    <td><input type="text" class="cz-label" placeholder="e.g. Inside Dhaka" style="width:100%"></td>
                    <td><input type="number" step="0.01" min="0" class="cz-price" placeholder="0.00" style="width:100%"></td>
                    <td><button type="button" class="button link-delete cz-remove">Remove</button></td>
                </tr>`);
                $tbody.find('tr:last .cz-label').focus();
            });

            $wrap.on('click', '.cz-remove', function () {
                $(this).closest('tr').remove();
                saveRows();
            });

            $wrap.on('input change', '.cz-label, .cz-price', function () {
                clearTimeout(window.czSaveTimer);
                window.czSaveTimer = setTimeout(saveRows, 600);
            });
        });
    </script>
    <?php
});

// -----------------------------
// Handle AJAX save
// -----------------------------
add_action('wp_ajax_wc_city_zones_main_save', function () {
    check_ajax_referer('wc_cz_main_nonce', 'nonce');

    $data = isset($_POST['data']) ? wp_unslash($_POST['data']) : '[]';
    $items = json_decode($data, true);
    if (!is_array($items))
        $items = [];

    $clean = array_map(function ($r) {
        return [
            'label' => sanitize_text_field($r['label'] ?? ''),
            'price' => floatval($r['price'] ?? 0)
        ];
    }, $items);

    update_option('wc_city_zones_main_repeater', wp_json_encode($clean));
    wp_send_json_success(['saved' => true]);
});

// -----------------------------
// Handle AJAX toggle
// -----------------------------
add_action('wp_ajax_wc_city_zones_toggle_enable', function () {
    check_ajax_referer('wc_cz_toggle_nonce', 'nonce');
    $enabled = sanitize_text_field($_POST['enabled'] ?? 'no');
    update_option('wc_city_zones_enabled', $enabled === 'yes' ? 'yes' : 'no');
    wp_send_json_success(['enabled' => $enabled]);
});

// -----------------------------
// HIDE WooCommerce default shipping
// -----------------------------
add_filter('woocommerce_cart_ready_to_calc_shipping', function($show_shipping) {
    $enabled = get_option('wc_city_zones_enabled', 'no');
    $data = get_option('wc_city_zones_main_repeater', '[]');
    $zones = json_decode($data, true);
    
    if ($enabled === 'yes' && is_array($zones) && !empty($zones)) {
        return false;
    }
    
    return $show_shipping;
}, 99);

// -----------------------------
// Display shipping zone
// -----------------------------
add_action('woocommerce_review_order_after_order_total', function () {
    $enabled = get_option('wc_city_zones_enabled', 'no');
    if ($enabled !== 'yes')
        return;

    $data = get_option('wc_city_zones_main_repeater', '[]');
    $zones = json_decode($data, true);
    
    if (!is_array($zones) || empty($zones))
        return;

    $selected_zone = WC()->session->get('wc_city_zone');
    
    // Auto-select first zone if nothing selected
    if ($selected_zone === null || $selected_zone === '') {
        $selected_zone = '0';
        WC()->session->set('wc_city_zone', '0');
    }

    $selected_zone_data = $zones[intval($selected_zone)] ?? $zones[0];
    $selected_label = esc_html($selected_zone_data['label'] ?? '');

    ?>
    <tr class="wc-city-zones-display">
        <th><?php echo esc_html("Select Shipping Zone"); ?>:</th>
        <td class="wc-city-zones-selector-sec" data-title="<?php echo esc_attr("Select Shipping Zone"); ?>">
            <div id="wc-city-zones-selector">
                <?php foreach ($zones as $index => $zone): 
                    $zone_label = esc_html($zone['label'] ?? '');
                    $zone_price = floatval($zone['price'] ?? 0);
                    $checked = ($selected_zone === (string)$index) ? 'checked' : '';
                ?>
                    <label style="cursor:pointer;">
                        <input type="radio" 
                               name="wc_city_zone" 
                               class="wc-city-zone-radio"
                               value="<?php echo $index; ?>" 
                               data-label="<?php echo esc_attr($zone_label); ?>"
                               data-cost="<?php echo $zone_price; ?>" 
                               <?php echo $checked; ?> 
                               style="margin-right:5px;">
                        <?php echo $zone_label; ?> - <strong><?php echo wc_price($zone_price); ?></strong>
                    </label>
                <?php endforeach; ?>
            </div>
        </td>
    </tr>
    <?php
}, 10);

// -----------------------------
// Save selected zone in session
// -----------------------------
add_action('woocommerce_checkout_update_order_review', function ($posted_data) {
    parse_str($posted_data, $output);
    
    $enabled = get_option('wc_city_zones_enabled', 'no');
    if ($enabled !== 'yes')
        return;
    
    if (isset($output['wc_city_zone'])) {
        WC()->session->set('wc_city_zone', sanitize_text_field($output['wc_city_zone']));
    } else {
        WC()->session->set('wc_city_zone', '0');
    }
});


// -----------------------------
// Frontend JavaScript and CSS
// -----------------------------
add_action('wp_footer', function () {
    if (!is_checkout())
        return;
    ?>
    <script type="text/javascript">
        jQuery(function ($) {
            // Trigger checkout update when radio changes
            $(document).on('change', 'input.wc-city-zone-radio', function () {
                $('body').trigger('update_checkout');
            });
            
            // Ensure first option is checked on page load
            if ($('input.wc-city-zone-radio:checked').length === 0) {
                $('input.wc-city-zone-radio:first').prop('checked', true).trigger('change');
            }
        });
    </script>
    <style>
        /* Hide WooCommerce default shipping */
        .woocommerce-shipping-totals {
            display: none !important;
        }
        
        /* City zones styling */
        .wc-city-zones-display th {
            font-weight: 600;
            color: #333;
            vertical-align: top;
            padding-top: 15px !important;
        }
        
        .wc-city-zones-display td {
            padding: 12px !important;
        }
        
        #wc-city-zones-selector {
            margin-top: 5px;
        }
        
        #wc-city-zones-selector label {
            white-space: nowrap;
            font-size: 14px;
        }
        
        /* Remove margin and padding from the last radio label */
        #wc-city-zones-selector label:last-child {
            margin-right: 0 !important;
            padding-right: 0 !important;
        }
        
        #wc-city-zones-selector input[type="radio"] {
            cursor: pointer;
            vertical-align: middle;
        }
        
        #wc-city-zones-selector label:hover {
            opacity: 0.8;
        }

        td.wc-city-zones-selector-sec {
            padding-right: 0px !important;
        }
    </style>
    <?php
});

// -----------------------------
// Hide default shipping completely
// -----------------------------
add_filter('woocommerce_cart_shipping_packages', function($packages) {
    $enabled = get_option('wc_city_zones_enabled', 'no');
    $data = get_option('wc_city_zones_main_repeater', '[]');
    $zones = json_decode($data, true);
    
    if ($enabled === 'yes' && is_array($zones) && !empty($zones)) {
        return [];
    }
    
    return $packages;
}, 99);
