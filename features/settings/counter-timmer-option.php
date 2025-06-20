<?php
// Fetch saved values (defaults to 'no')
?>
<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
    <?php wp_nonce_field( 'wc_bogo_save_counter_settings', 'wc_bogo_counter_nonce' ); ?>
    <input type="hidden" name="action" value="wc_bogo_save_counter_settings">

    <?php 
    $fields = [
      'product'  => 'Enable Counter Timer on products with BOGO Promotions ?',
      'category' => 'Enable Counter Timer on categories with BOGO Promotions ?',
      'tag'      => 'Enable Counter Timer on tags with BOGO Promotions ?',
      'layout'   => 'Show counter timer & specific pages ?'
    ];
    foreach ( $fields as $key => $label ): 
        $option_name = "wc_bogo_counter_on_{$key}";
        $current     = get_option( $option_name, 'no' );
    ?>
      <div class="bogo-toggle-wrapper">
        <label for="<?php echo esc_attr( $option_name ); ?>" class="bogo-toggle-label">
          <?php echo esc_html( $label ); ?>
        </label>
        <label class="bogo-switch">
          <input 
            type="checkbox" 
            id="<?php echo esc_attr( $option_name ); ?>" 
            name="<?php echo esc_attr( $option_name ); ?>" 
            value="yes" 
            <?php checked( $current, 'yes' ); ?>
          >
          <span class="bogo-slider"></span>
        </label>
      </div>
    <?php endforeach; ?>

    <p>
      <em>If you enable the [bogo-counter-timmer], you can customize which products appear in the grid via the “BOGO Products” screen in WooCommerce.</em>
    </p>

    <p><input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'wc-bogo' ); ?>"></p>
</form>
<?php

