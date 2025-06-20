<?php
/**
 * Register and manage BOGO custom meta fields:
 * - deal_scope (array of scopes)
 * - bogo_usage_count (integer usage count)
 */

/**
 * Register post meta fields for 'wc_bogo' post type.
 */
function wc_bogo_register_meta_fields() {
    register_post_meta( 'wc_bogo', 'deal_scope', array(
        'type'         => 'array',
        'description'  => __( 'Scope of the BOGO deal (e.g., product, category, tag)', 'wc-bogo' ),
        'single'       => false,
        'show_in_rest' => array(
            'schema' => array(
                'type'  => 'array',
                'items' => array(
                    'type' => 'string',
                ),
            ),
        ),
    ) );

    register_post_meta( 'wc_bogo', 'bogo_usage_count', array(
        'type'         => 'integer',
        'description'  => __( 'Number of times the BOGO deal has been used', 'wc-bogo' ),
        'single'       => true,
        'default'      => 0,
        'show_in_rest' => true,
    ) );
}
add_action( 'init', 'wc_bogo_register_meta_fields' );

/**
 * Display BOGO Deal Scope meta box with checkboxes.
 *
 * @param WP_Post $post Current post object.
 */
function wc_bogo_scope_meta_box( $post ) {
    $selected_scopes = get_post_meta( $post->ID, 'deal_scope', true );

    if ( ! is_array( $selected_scopes ) ) {
        $selected_scopes = array();
    }
    ?>
    <p>
        <label><strong><?php esc_html_e( 'BOGO Deal Scope', 'wc-bogo' ); ?></strong></label><br>

        <label>
            <input type="checkbox" name="bogo_scope[]" value="product" <?php checked( in_array( 'product', $selected_scopes, true ) ); ?> />
            <?php esc_html_e( 'Product', 'wc-bogo' ); ?>
        </label><br>

        <label>
            <input type="checkbox" name="bogo_scope[]" value="category" <?php checked( in_array( 'category', $selected_scopes, true ) ); ?> />
            <?php esc_html_e( 'Category', 'wc-bogo' ); ?>
        </label><br>

        <label>
            <input type="checkbox" name="bogo_scope[]" value="tag" <?php checked( in_array( 'tag', $selected_scopes, true ) ); ?> />
            <?php esc_html_e( 'Tag', 'wc-bogo' ); ?>
        </label>
    </p>
    <?php
}
add_action( 'add_meta_boxes_wc_bogo', function() {
    add_meta_box(
        'bogo_scope_meta_box',
        __( 'BOGO Deal Scope', 'wc-bogo' ),
        'wc_bogo_scope_meta_box',
        'wc_bogo',
        'normal',
        'high'
    );
} );

/**
 * Save meta box data for bogo_scope and initialize bogo_usage_count.
 *
 * @param int $post_id The post ID.
 */
function wc_bogo_save_meta( $post_id ) {
    // Security checks.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! isset( $_POST['post_type'] ) || 'wc_bogo' !== $_POST['post_type'] ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save bogo_scope (array of selected scopes).
    if ( isset( $_POST['bogo_scope'] ) && is_array( $_POST['bogo_scope'] ) ) {
        $sanitized_scopes = array_map( 'sanitize_text_field', $_POST['bogo_scope'] );
        update_post_meta( $post_id, 'deal_scope', $sanitized_scopes );
    } else {
        delete_post_meta( $post_id, 'deal_scope' );
    }

    // Initialize usage count if empty or not set.
    $usage_count = get_post_meta( $post_id, 'bogo_usage_count', true );
    if ( $usage_count === '' || $usage_count === false ) {
        update_post_meta( $post_id, 'bogo_usage_count', 0 );
    }
}
add_action( 'save_post_wc_bogo', 'wc_bogo_save_meta' );

/**
 * Add columns for deal_scope and usage_count to admin list table.
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
add_filter( 'manage_wc_bogo_posts_columns', function( $columns ) {
    $columns['deal_scope']   = __( 'Scope', 'wc-bogo' );
    $columns['usage_count']  = __( 'Usage Count', 'wc-bogo' );
    return $columns;
} );

/**
 * Populate custom columns with meta values.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
add_action( 'manage_wc_bogo_posts_custom_column', function( $column, $post_id ) {
    if ( 'deal_scope' === $column ) {
        $scopes = get_post_meta( $post_id, 'deal_scope', true );
        if ( is_array( $scopes ) && ! empty( $scopes ) ) {
            echo esc_html( implode( ', ', array_map( 'ucfirst', $scopes ) ) );
        } else {
            echo '—';
        }
    }

    if ( 'usage_count' === $column ) {
        $usage = get_post_meta( $post_id, 'bogo_usage_count', true );
        echo intval( $usage );
    }
}, 10, 2 );

/**
 * Increment usage count for a given BOGO deal.
 *
 * @param int $bogo_id The BOGO post ID.
 */
function wc_bogo_increment_usage_count( $bogo_id ) {
    $current_usage = get_post_meta( $bogo_id, 'bogo_usage_count', true );
    $current_usage = $current_usage ? intval( $current_usage ) : 0;
    update_post_meta( $bogo_id, 'bogo_usage_count', $current_usage + 1 );
}

/**
 * Make usage_count column sortable.
 *
 * @param array $columns Columns array.
 * @return array Modified columns.
 */
add_filter( 'manage_edit-wc_bogo_sortable_columns', function( $columns ) {
    $columns['usage_count'] = 'usage_count';
    return $columns;
} );

/**
 * Modify admin query to sort by usage_count meta value.
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 */
add_action( 'pre_get_posts', function( $query ) {
    if ( ! is_admin() || ! $query->is_main_query() ) {
        return;
    }

    if ( 'usage_count' === $query->get( 'orderby' ) ) {
        $query->set( 'meta_key', 'bogo_usage_count' );
        $query->set( 'orderby', 'meta_value_num' );
    }
} );
