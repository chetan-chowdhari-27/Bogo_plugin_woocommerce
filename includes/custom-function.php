<?php
/**
 * Custom functions for BOGO Discount Schedule and Cron management
 */

/**
 * Schedule BOGO discount status update cron on plugin/theme load.
 */
    function bogo_schedule_cron() {
        if ( ! wp_next_scheduled( 'bogo_update_status_cron' ) ) {
            // Schedule event every five minutes
            wp_schedule_event( time(), 'five_minutes', 'bogo_update_status_cron' );
        }
    }
    add_action( 'wp', 'bogo_schedule_cron' );

/**
 * Clear scheduled BOGO cron on plugin deactivation.
 */
    function bogo_clear_cron() {
        $timestamp = wp_next_scheduled( 'bogo_update_status_cron' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'bogo_update_status_cron' );
        }
    }
    register_deactivation_hook( __FILE__, 'bogo_clear_cron' );

/**
 * Add custom cron interval of five minutes.
 *
 * @param array $schedules Existing schedules.
 * @return array Modified schedules.
 */
    function bogo_cron_intervals( $schedules ) {
        $schedules['five_minutes'] = array(
            'interval' => 300, // 300 seconds = 5 minutes
            'display'  => __( 'Every Five Minutes', 'your-text-domain' ),
        );
        return $schedules;
    }
    add_filter( 'cron_schedules', 'bogo_cron_intervals' );

/**
 * Callback function to update BOGO deal status based on start/end dates.
 */
    function bogo_update_status_callback() 
    {
        // Query BOGO posts where deal status is 'yes'.
        $args = array(
            'post_type'      => 'wc_bogo',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_bogo_deal_status',
                    'value' => 'yes',
                ),
            ),
            'fields' => 'ids', // Return only post IDs for better performance
        );

        $post_ids = get_posts( $args );
        if ( ! empty( $post_ids ) ) {
            $ist_timezone = new DateTimeZone( 'Asia/Kolkata' );
            $current_time = new DateTime( 'now', $ist_timezone );
            $current_timestamp = $current_time->getTimestamp();

            foreach ( $post_ids as $post_id ) {
                $start_date = get_post_meta( $post_id, '_bogo_start_date', true );
                $end_date   = get_post_meta( $post_id, '_bogo_end_date', true );

                // Safely parse dates; fallback to 0 if empty or invalid
                $start_timestamp = 0;
                if ( ! empty( $start_date ) ) {
                    $start_dt = DateTime::createFromFormat( 'Y-m-d\TH:i', $start_date, $ist_timezone );
                    if ( $start_dt ) {
                        $start_timestamp = $start_dt->getTimestamp();
                    }
                }

                $end_timestamp = 0;
                if ( ! empty( $end_date ) ) {
                    $end_dt = DateTime::createFromFormat( 'Y-m-d\TH:i', $end_date, $ist_timezone );
                    if ( $end_dt ) {
                        $end_timestamp = $end_dt->getTimestamp();
                    }
                }

                // Disable deal if current time is past end date
                if ( $end_timestamp && $current_timestamp >= $end_timestamp ) {
                    update_post_meta( $post_id, '_bogo_deal_status', 'no' );
                }
            }
        }
    }
    add_action( 'bogo_update_status_cron', 'bogo_update_status_callback' );
