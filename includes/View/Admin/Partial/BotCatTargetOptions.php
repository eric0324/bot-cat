<?php

/**
 * Class BotCatTargetOptions
 *
 * Represents the target options for a BotCat class.
 */

namespace BotCat\View\Admin\Partial;

class BotCatTargetOptions {
	private string $service_name;

	public function __construct( $service_name ) {
		$this->service_name = $service_name;
	}

	public function bot_cat_role_can_receive_message(): void {
		global $wp_roles;
		$roles   = $wp_roles->get_names();
		$options = get_option( BOT_CAT_OPTION_PREFIX . $this->service_name );
		?>
        <h3><?php _e( 'Show OAuth Link', 'bot-cat' ) ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Profile', 'bot-cat' ) ?></th>
                <td style="line-height: 30px;">
					<?php
					foreach ( $roles as $name => $role ) {
						?>
                        <input
                                id="oauth_show_profile_<?php echo esc_attr( $name ) ?>"
                                type="checkbox"
                                name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX . $this->service_name . "[oauth_show_profile][$name]" ) ?>"
                                value="1"
							<?php if ( isset( $options['oauth_show_profile'][ $name ] ) )
								echo esc_attr( checked( 1, $options['oauth_show_profile'][ $name ], false ) ) ?>
                        >
                        <label for="oauth_show_profile_<?php echo esc_attr( $name ) ?>"><?php echo translate_user_role( $role ) ?></label>
					<?php } ?>
                </td>
            </tr>
        </table>
        <hr>
        <h3><?php _e( 'WordPress', 'bot-cat' ) ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Post', 'bot-cat' ) ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e( 'Publish', 'bot-cat' ) ?></strong><br>
					<?php
					foreach ( $roles as $name => $role ) {
						?>
                        <input
                                id="publish_post_<?php echo esc_attr( $name ) ?>"
                                type="checkbox"
                                name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX . $this->service_name . "[publish_post][$name]" ) ?>"
                                value="1"
							<?php if ( isset( $options['publish_post'][ $name ] ) )
								echo esc_attr( checked( 1, $options['publish_post'][ $name ], false ) ) ?>
                        >
                        <label for="publish_post_<?php echo esc_attr( $name ) ?>"><?php echo translate_user_role( $role ) ?></label>
					<?php } ?>
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <th scope="row"></th>
                <td style="line-height: 30px;">
                    <strong><?php _e( 'Review', 'bot-cat' ) ?></strong><br>
					<?php
					foreach ( $roles as $name => $role ) {
						?>
                        <input
                                id="pending_post_<?php echo esc_attr( $name ) ?>"
                                type="checkbox"
                                name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX . $this->service_name . "[pending_post][$name]" ) ?>"
                                value="1"
							<?php if ( isset( $options['pending_post'][ $name ] ) )
								echo esc_attr( checked( 1, $options['pending_post'][ $name ], false ) ) ?>
                        >
                        <label for="pending_post_<?php echo esc_attr( $name ) ?>"><?php echo translate_user_role( $role ) ?></label>
					<?php } ?>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Comment', 'bot-cat' ) ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e( 'New Comment', 'bot-cat' ) ?></strong><br>
					<?php
					foreach ( $roles as $name => $role ) {
						$role_attrs = $wp_roles->get_role( $name );
						if ( $role_attrs->has_cap( 'moderate_comments' ) ) {
							?>
                            <input
                                    id="new_comment_<?php echo esc_attr( $name ) ?>"
                                    type="checkbox"
                                    name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX . $this->service_name . "[new_comment][$name]" ) ?>"
                                    value="1"
								<?php if ( isset( $options['new_comment'][ $name ] ) )
									echo esc_attr( checked( 1, $options['new_comment'][ $name ], false ) ) ?>
                            >
                            <label for="new_comment_<?php echo esc_attr( $name ) ?>"><?php echo translate_user_role( $role ) ?></label>
						<?php }
					} ?>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'User', 'bot-cat' ) ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e( 'New user', 'bot-cat' ) ?></strong><br>
					<?php
					foreach ( $roles as $name => $role ) {
						$role_attrs = $wp_roles->get_role( $name );
						if ( $role_attrs->has_cap( 'list_users' ) ) {
							?>
                            <input
                                    id="new_user_<?php echo esc_attr( $name ) ?>"
                                    type="checkbox"
                                    name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX . $this->service_name . "[new_user][$name]" ) ?>"
                                    value="1"
								<?php if ( isset( $options['new_user'][ $name ] ) )
									echo esc_attr( checked( 1, $options['new_user'][ $name ], false ) ) ?>
                            >
                            <label for="new_user_<?php echo esc_attr( $name ) ?>"><?php echo translate_user_role( $role ) ?></label>
						<?php }
					} ?>
                </td>
            </tr>
        </table>

		<?php submit_button(); ?>
        </form>
        </div>

		<?php
	}

	public function bot_cat_webhook_can_receive_message(): void {
		$options = get_option( BOT_CAT_OPTION_PREFIX . $this->service_name );
		?>
        <h3><?php _e( 'WordPress', 'bot-cat' ) ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Post', 'bot-cat' ) ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e( 'The webhook for publish', 'bot-cat' ) ?></strong><br>
                    <input
                            id="publish_post_webhook"
                            style="width: 50%;"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX . $this->service_name . "[publish_post][webhook]" ) ?>"
                            value="<?php echo( isset( $options['publish_post']['webhook'] ) ? esc_attr( $options['publish_post']['webhook'] ) : '' ); ?>"
                            placeholder="<?php _e( 'Please input your Slack webhook URL', 'bot-cat' ) ?>"
                    >
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <th scope="row"></th>
                <td style="line-height: 30px;">
                    <strong><?php _e( 'The webhook for review', 'bot-cat' ) ?></strong><br>
                    <input
                            id="pending_post_webhook"
                            style="width: 50%;"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX . $this->service_name . "[pending_post][webhook]" ) ?>"
                            value="<?php echo( isset( $options['pending_post']['webhook'] ) ? esc_attr( $options['pending_post']['webhook'] ) : '' ); ?>"
                            placeholder="<?php _e( 'Please input your Slack webhook URL', 'bot-cat' ) ?>"
                    >
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Comment', 'bot-cat' ) ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e( 'The webhook for new comment', 'bot-cat' ) ?></strong><br>
                    <input
                            id="new_comment_webhook"
                            style="width: 50%;"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX . $this->service_name . "[new_comment][webhook]" ) ?>"
                            value="<?php echo( isset( $options['new_comment']['webhook'] ) ? esc_attr( $options['new_comment']['webhook'] ) : '' ); ?>"
                            placeholder="<?php _e( 'Please input your Slack webhook URL', 'bot-cat' ) ?>"
                    >
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'User', 'bot-cat' ) ?></th>
                <td style="line-height: 30px;">
                    <strong><?php _e( 'The webhook for new user', 'bot-cat' ) ?></strong><br>
                    <input
                            id="new_user_webhook"
                            style="width: 50%;"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX . $this->service_name . "[new_user][webhook]" ) ?>"
                            value="<?php echo( isset( $options['new_user']['webhook'] ) ? esc_attr( $options['new_user']['webhook'] ) : '' ); ?>"
                            placeholder="<?php _e( 'Please input your Slack webhook URL', 'bot-cat' ) ?>"
                    >
                </td>
            </tr>
        </table>

		<?php submit_button(); ?>
        </form>
        </div>

		<?php
	}
}