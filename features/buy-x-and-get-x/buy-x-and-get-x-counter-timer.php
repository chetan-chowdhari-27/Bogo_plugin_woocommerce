<?php 

// Hook to display the BOGO countdown timer on the single product page
add_action('woocommerce_single_product_summary', 'display_bogo_frontend_timer', 25); // Priority 25 is usually before add to cart
function display_bogo_frontend_timer() {
    global $product;

    if (!$product) {
        return; // Exit if no product object is available
    }

    $product_id = $product->get_id();

    // Find the active BOGO rule for this product
    $bogo_rule_post_id = false;

    // 1. Check for product-specific rule
    $args_product = array(
        'post_type'      => 'wc_bogo',
        'posts_per_page' => 1,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_bogo_deal_status',
                'value'   => 'yes',
                'compare' => '='
            ),
            array(
                'key'     => '_wc_bogo_filter_type',
                'value'   => 'product',
                'compare' => '='
            ),
            array(
                'key'     => '_selected_products',
                'value'   => (string)$product_id,
                'compare' => 'LIKE'
            )
        )
    );
    $query_product = new WP_Query($args_product);
    if ($query_product->have_posts()) {
        $query_product->the_post();
        $bogo_rule_post_id = get_the_ID();
        wp_reset_postdata();
    }

    // 2. If no product rule, check for category rule
    if (!$bogo_rule_post_id) {
        $terms_cat = get_the_terms($product_id, 'product_cat');
        if ($terms_cat && !is_wp_error($terms_cat)) {
            foreach ($terms_cat as $term) {
                $args_category = array(
                    'post_type'      => 'wc_bogo',
                    'posts_per_page' => 1,
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array(
                            'key'     => '_bogo_deal_status',
                            'value'   => 'yes',
                            'compare' => '='
                        ),
                        array(
                            'key'     => '_wc_bogo_filter_type',
                            'value'   => 'category',
                            'compare' => '='
                        ),
                        array(
                            'key'     => '_selected_categories',
                            'value'   => (string)$term->term_id,
                            'compare' => 'LIKE'
                        )
                    )
                );
                $query_category = new WP_Query($args_category);
                if ($query_category->have_posts()) {
                    $query_category->the_post();
                    $bogo_rule_post_id = get_the_ID();
                    wp_reset_postdata();
                    break; // Found a category rule, no need to check others
                }
            }
        }
    }

    // 3. If no product or category rule, check for tag rule
    if (!$bogo_rule_post_id) {
        $terms_tag = get_the_terms($product_id, 'product_tag');
        if ($terms_tag && !is_wp_error($terms_tag)) {
            foreach ($terms_tag as $term) {
                $args_tag = array(
                    'post_type'      => 'wc_bogo',
                    'posts_per_page' => 1,
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array(
                            'key'     => '_bogo_deal_status',
                            'value'   => 'yes',
                            'compare' => '='
                        ),
                        array(
                            'key'     => '_wc_bogo_filter_type',
                            'value'   => 'tag',
                            'compare' => '='
                        ),
                        array(
                            'key'     => '_selected_tags',
                            'value'   => (string)$term->term_id,
                            'compare' => 'LIKE'
                        )
                    )
                );
                $query_tag = new WP_Query($args_tag);
                if ($query_tag->have_posts()) {
                    $query_tag->the_post();
                    $bogo_rule_post_id = get_the_ID();
                    wp_reset_postdata();
                    break; // Found a tag rule, no need to check others
                }
            }
        }
    }


    // If an active BOGO rule is found for this product
    if ($bogo_rule_post_id) {
        $schedule_enabled = get_post_meta($bogo_rule_post_id, '_bogo_schedule_enabled', true);
        $end_date         = get_post_meta($bogo_rule_post_id, '_bogo_end_date', true);

        // Check if scheduling is enabled and an end date is set
        if ($schedule_enabled === 'yes' && !empty($end_date)) {

            // Check if the deal is currently active based on dates
            $ist_timezone = new DateTimeZone('Asia/Kolkata');
            $now_ist      = new DateTime('now', $ist_timezone);
            $end_datetime = new DateTime($end_date, $ist_timezone);

            if ($now_ist < $end_datetime) {
                // Deal is active, display the timer container
                ?>
                <div id="frontend_bogo_timer_<?php echo esc_attr($product_id); ?>" class="bogo-timer-container" style="margin-bottom: 15px; font-weight: bold; color: #0073aa;">
                    <!-- Timer will be displayed here by JavaScript -->
                </div>

                <script type="text/javascript">
                    // Use a unique ID for each product timer
                    const timerContainer_<?php echo esc_js($product_id); ?> = document.getElementById('frontend_bogo_timer_<?php echo esc_js($product_id); ?>');
                    const endTimeString_<?php echo esc_js($product_id); ?> = "<?php echo esc_js($end_date); ?>";

                    function updateFrontendCountdown_<?php echo esc_js($product_id); ?>() {
                        if (!endTimeString_<?php echo esc_js($product_id); ?> || !timerContainer_<?php echo esc_js($product_id); ?>) {
                            return; // Exit if no end date or container
                        }

                        // Convert end time string to Date object
                        const endTime_<?php echo esc_js($product_id); ?> = new Date(endTimeString_<?php echo esc_js($product_id); ?>);
                        const now = new Date();
                        const distance = endTime_<?php echo esc_js($product_id); ?>.getTime() - now.getTime(); // Get time in milliseconds

                        if (distance <= 0) {
                            timerContainer_<?php echo esc_js($product_id); ?>.innerHTML = '⏰ Deal has ended.';
                            // Stop the interval once the deal ends
                            clearInterval(timerInterval_<?php echo esc_js($product_id); ?>);
                            return;
                        }

                        // Calculate time components
                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        // Display the countdown
                        timerContainer_<?php echo esc_js($product_id); ?>.innerHTML = `⏳ Deal Ends in: ${days}d ${hours}h ${minutes}m ${seconds}s`;
                    }

                    // Update the countdown every second
                    const timerInterval_<?php echo esc_js($product_id); ?> = setInterval(updateFrontendCountdown_<?php echo esc_js($product_id); ?>, 1000);
                    updateFrontendCountdown_<?php echo esc_js($product_id); ?>(); // Run once initially
                </script>
                <?php
            }
        }
    }
}
