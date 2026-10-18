<?php

namespace DavixWP\Modules\Account\Admin;

use DavixWP\Admin\Ui;
use DavixWP\Connection\KeyStore;
use DavixWP\Core\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class ConnectionPage {

    public function render() {
        $has    = KeyStore::has();
        $source = KeyStore::source();
        $conn   = Options::section( 'connection' );

        Ui::panel_open(
            __( 'Connection', 'davix-h2i' ),
            array(
                'lead' => __( 'Use the API key from your Customer Portal. This plugin works with your own account, so every action uses your plan.', 'davix-h2i' ),
            )
        );

        echo '<div data-dvx-connection>';
        Ui::card_open( __( 'API key', 'davix-h2i' ), __( 'Connect this site to your Davix H2I account.', 'davix-h2i' ) );
        ?>
        <table class="form-table davix-h2i-settings-table" role="presentation"><tbody><tr>
            <th scope="row"><label class="davix-h2i-label" for="dvx-api-key"><?php esc_html_e( 'API key', 'davix-h2i' ); ?></label></th>
            <td>
                <div class="dvs-credential-callout"><div class="dvs-connection-test" data-dvx-connection-test data-scope="connection"
                     data-has-stored="<?php echo $has ? '1' : '0'; ?>"
                     data-testing-label="<?php esc_attr_e( 'Testing…', 'davix-h2i' ); ?>"
                     data-neutral-label="<?php esc_attr_e( 'Not tested', 'davix-h2i' ); ?>"
                     data-missing-label="<?php esc_attr_e( 'Enter an API key first.', 'davix-h2i' ); ?>">
                    <div class="dvs-connection-test__row">
                        <span class="dvs-connection-test__field-wrap">
                            <input type="password" id="dvx-api-key" value="" class="davix-h2i-input dvs-connection-test__field" autocomplete="new-password"
                                   placeholder="<?php echo esc_attr( $has ? KeyStore::masked() : 'h2i_xxxxxxxxxxxxxxxx' ); ?>" <?php disabled( 'constant' === $source ); ?> />
                            <button type="button" class="dvs-connection-test__reveal" aria-label="<?php esc_attr_e( 'Show key', 'davix-h2i' ); ?>" aria-pressed="false">
                                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                            </button>
                        </span>
                        <button type="button" class="dvs-connection-test__button"><?php esc_html_e( 'Test', 'davix-h2i' ); ?></button>
                        <span class="dvs-connection-test__indicator dvs-connection-test__indicator--<?php echo $has && $conn['verified_at'] ? 'success' : 'neutral'; ?>" role="status" tabindex="0"></span>
                    </div>
                    <span class="dvs-connection-test__message" role="status" aria-live="polite"></span>
                </div>
                <p class="description">
                    <?php if ( 'constant' === $source ) : ?>
                        <?php esc_html_e( 'The key is set in wp-config.php (DAVIX_H2I_API_KEY). Remove it there to change it here.', 'davix-h2i' ); ?>
                    <?php elseif ( $has ) : ?>
                        <?php esc_html_e( 'A key is saved (shown masked). Leave blank to keep it, or paste a new key to replace it.', 'davix-h2i' ); ?>
                    <?php else : ?>
                        <?php esc_html_e( 'Paste the key from your Customer Portal, test it, then save.', 'davix-h2i' ); ?>
                    <?php endif; ?>
                </p>
                </div>
            </td>
        </tr></tbody></table>
        <?php
        echo '<div class="dvx-toolbar" style="margin-top:16px">';
        if ( 'constant' !== $source ) {
            echo '<button type="button" class="dvs-save-button" data-dvx-save-connection>' . esc_html__( 'Save connection', 'davix-h2i' ) . '</button>';
            if ( $has ) {
                echo '<button type="button" class="dvs-btn dvs-btn--danger" data-dvx-remove-key>' . esc_html__( 'Remove key', 'davix-h2i' ) . '</button>';
            }
        }
        echo '</div>';
        if ( $has && ! empty( $conn['plan_name'] ) ) {
            echo '<p class="description">' . esc_html(
                sprintf(
                    /* translators: 1: plan, 2: email */
                    __( 'Connected as %2$s on the %1$s plan.', 'davix-h2i' ),
                    $conn['plan_name'],
                    $conn['email'] ? $conn['email'] : __( 'your account', 'davix-h2i' )
                )
            ) . '</p>';
        }
        Ui::card_close();
        echo '</div>';
        Ui::panel_close();
    }
}
