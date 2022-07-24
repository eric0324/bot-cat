<?php

class BotCatNotificationOptions
{
    private $service_name;

    public function __construct($service_name)
    {
        $this->service_name = $service_name;
    }

    public function init(): void
    {

        global $wp_roles;
        $roles = $wp_roles->get_names();
        $options = get_option(BOT_CAT_OPTION_PREFIX . $this->service_name);

        ?>
        <h3><?php _e('WordPress', 'bot-cat') ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Post', 'bot-cat') ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e('Publish', 'bot-cat') ?></strong><br>
                    <?php
                    foreach ($roles as $name => $role) {
                        ?>
                        <input
                                id="publish_post_<?php echo esc_attr($name) ?>"
                                type="checkbox"
                                name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX . $this->service_name . "[publish_post][$name]") ?>"
                                value="1"
                            <?php if (isset($options['publish_post'][$name]))
                                echo esc_attr(checked(1, $options['publish_post'][$name], false)) ?>
                        >
                        <label for="publish_post_<?php echo esc_attr($name) ?>"><?php echo translate_user_role($role) ?></label>
                    <?php } ?>
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <th scope="row"></th>
                <td style="line-height: 30px;">
                    <strong><?php _e('Review', 'bot-cat') ?></strong><br>
                    <?php
                    foreach ($roles as $name => $role) {
                        ?>
                        <input
                                id="pending_post_<?php echo esc_attr($name) ?>"
                                type="checkbox"
                                name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX . $this->service_name . "[pending_post][$name]") ?>"
                                value="1"
                            <?php if (isset($options['pending_post'][$name]))
                                echo esc_attr(checked(1, $options['pending_post'][$name], false)) ?>
                        >
                        <label for="pending_post_<?php echo esc_attr($name) ?>"><?php echo translate_user_role($role) ?></label>
                    <?php } ?>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Comment', 'bot-cat') ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e('New Comment', 'bot-cat') ?></strong><br>
                    <?php
                    foreach ($roles as $name => $role) {
                        $role_attrs = $wp_roles->get_role($name);
                        if ($role_attrs->has_cap('moderate_comments')) {
                            ?>
                            <input
                                    id="new_comments_<?php echo esc_attr($name) ?>"
                                    type="checkbox"
                                    name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX . $this->service_name . "[new_comments][$name]") ?>"
                                    value="1"
                                <?php if (isset($options['new_comments'][$name]))
                                    echo esc_attr(checked(1, $options['new_comments'][$name], false)) ?>
                            >
                            <label for="new_comments_<?php echo esc_attr($name) ?>"><?php echo translate_user_role($role) ?></label>
                        <?php }
                    } ?>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('User', 'bot-cat') ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e('New user', 'bot-cat') ?></strong><br>
                    <?php
                    foreach ($roles as $name => $role) {
                        $role_attrs = $wp_roles->get_role($name);
                        if ($role_attrs->has_cap('list_users')) {
                            ?>
                            <input
                                    id="new_users_<?php echo esc_attr($name) ?>"
                                    type="checkbox"
                                    name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX . $this->service_name . "[new_users][$name]") ?>"
                                    value="1"
                                <?php if (isset($options['new_users'][$name]))
                                    echo esc_attr(checked(1, $options['new_users'][$name], false)) ?>
                            >
                            <label for="new_users_<?php echo esc_attr($name) ?>"><?php echo translate_user_role($role) ?></label>
                        <?php }
                    } ?>
                </td>
            </tr>
        </table>

        <hr>

        <h3><?php _e('Woocommerce', 'bot-cat') ?></h3>
        <?php if (!is_plugin_active('woocommerce/woocommerce.php')) { ?>
        <div>
            <p><?php _e('This plugin is not install or active.', 'bot-cat') ?></p>
        </div>
    <?php } else { ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Product', 'bot-cat') ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e('New product', 'bot-cat') ?></strong><br>
                    <?php
                    foreach ($roles as $name => $role) {
                        ?>
                        <input
                                id="new_product_<?php echo esc_attr($name) ?>"
                                type="checkbox"
                                name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX . $this->service_name . "[new_product][$name]") ?>"
                                value="1"
                            <?php if (isset($options['new_product'][$name]))
                                echo esc_attr(checked(1, $options['new_product'][$name], false)) ?>
                        >
                        <label for="new_product_<?php echo esc_attr($name) ?>"><?php echo translate_user_role($role) ?></label>
                    <?php } ?>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"></th>
                <td style="line-height: 30px;">
                    <strong><?php _e('Low stock', 'bot-cat') ?></strong><br>
                    <?php
                    foreach ($roles as $name => $role) {
                        ?>
                        <input
                                id="low_stock_product_<?php echo esc_attr($name) ?>"
                                type="checkbox"
                                name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX . $this->service_name . "[low_stock_product][$name]") ?>"
                                value="1"
                            <?php if (isset($options['low_stock_product'][$name]))
                                echo esc_attr(checked(1, $options['low_stock_product'][$name], false)) ?>
                        >
                        <label for="low_stock_product_<?php echo esc_attr($name) ?>"><?php echo translate_user_role($role) ?></label>
                    <?php } ?>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"></th>
                <td style="line-height: 30px;">
                    <strong><?php _e('No stock', 'bot-cat') ?> </strong><br>
                    <?php
                    foreach ($roles as $name => $role) {
                        ?>
                        <input
                                id="out_stock_product_<?php echo esc_attr($name) ?>"
                                type="checkbox"
                                name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX . $this->service_name . "[out_stock_product][$name]") ?>"
                                value="1"
                            <?php if (isset($options['out_stock_product'][$name]))
                                echo esc_attr(checked(1, $options['out_stock_product'][$name], false)) ?>
                        >
                        <label for="out_stock_product_<?php echo esc_attr($name) ?>"><?php echo translate_user_role($role) ?></label>
                    <?php } ?>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Order', 'bot-cat') ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e('New order', 'bot-cat') ?></strong><br>
                    <div>
                        <strong>(<?php _e('Note: Customers are notified when only their own order is created.', 'bot-cat') ?>
                            )</strong>
                    </div>
                    <?php
                    foreach ($roles as $name => $role) {
                        $role_attrs = $wp_roles->get_role($name);
                        if ($role_attrs->has_cap('manage_woocommerce')) {
                            ?>
                            <input
                                    id="new_order_<?php echo esc_attr($name) ?>"
                                    type="checkbox"
                                    name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX . $this->service_name . "[new_order][$name]") ?>"
                                    value="1"
                                <?php if (isset($options['new_order'][$name]))
                                    echo esc_attr(checked(1, $options['new_order'][$name], false))
                                ?>
                            >
                            <label for="new_order_<?php echo esc_attr($name) ?>"><?php echo translate_user_role($role) ?></label>
                        <?php }
                    } ?>
                    <input
                            id="new_order_customer"
                            type="checkbox"
                            name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX . $this->service_name . "[new_order][customer]") ?>"
                            value="1"
                        <?php if (isset($options['new_order']['customer']))
                            echo esc_attr(checked(1, $options['new_order']['customer'], false))
                        ?>
                    >
                    <label for="new_order_customer"><?php echo translate_user_role('Customer') ?></label>
                </td>
            </tr>
        </table>

    <?php } ?>

        <?php submit_button(); ?>
        </form>
        </div>

        <?php
    }
}