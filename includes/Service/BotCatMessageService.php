<?php
/**
 * Class BotCatMessageService
 *
 * This class is responsible for generating various types of messages for different actions.
 */

namespace BotCat\Service;

use WP_Post;


class BotCatMessageService {

	private mixed $bot_cat_message;

	public function __construct() {
		$this->bot_cat_message = get_option( BOT_CAT_OPTION_PREFIX . 'messages' );
	}

	/**
	 * Generate post type text
	 *
	 * @param string $action_name The action name.
	 * @param WP_Post $post The post object.
	 *
	 * @return array The generated post type text.
	 */
	public function bot_cat_generate_post_type_text( string $action_name, WP_Post $post ): array {

		$userdata = get_userdata( $post->post_author );

		$keyword_text = [
			'[title]'   => $post->post_title,
			'[content]' => $post->post_content,
			'[date]'    => $post->post_date,
			'[id]'      => $post->ID,
			'[link]'    => get_permalink( $post->ID ),
			'[author]'  => $userdata->display_name
		];

		return $this->bot_cat_str_replace_message(
			__( '[Admin] Post type message', 'bot-cat' ),
			__( 'Post type message', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['user'][ $action_name ]
		);
	}

	/**
	 * Replaces keywords in the admin and user messages with corresponding values from the keyword list.
	 *
	 * @param string $admin_message The admin message to replace keywords in.
	 * @param string $user_message The user message to replace keywords in.
	 * @param array $keyword_list The list of keywords and their corresponding values.
	 * @param string|null $admin_message_template The admin message template that contains keywords to be replaced.
	 * @param string|null $user_message_template The user message template that contains keywords to be replaced.
	 *
	 * @return array The admin and user messages with replaced keywords.
	 */
	private function bot_cat_str_replace_message(
		string $admin_message,
		string $user_message,
		array $keyword_list,
		?string $admin_message_template,
		?string $user_message_template
	): array {
		if ( $admin_message_template ) {
			$admin_message = str_ireplace( array_keys( $keyword_list ), $keyword_list, $admin_message_template );
		}

		if ( $user_message_template ) {
			$user_message = str_ireplace( array_keys( $keyword_list ), $keyword_list, $user_message_template );
		}

		return [
			'admin' => $admin_message,
			'user'  => $user_message
		];
	}

	/**
	 * Generate comment type text
	 *
	 * @param string $action_name The action name.
	 * @param object $comment The comment object.
	 *
	 * @return array An array of keyword text replacements.
	 */
	public function bot_cat_generate_comment_type_text( string $action_name, object $comment ): array {

		$userdata = get_userdata( $comment->user_id );

		$keyword_text = [
			'[author_email]' => $comment->comment_author_email,
			'[content]'      => $comment->comment_content,
			'[date]'         => $comment->comment_date,
			'[id]'           => $comment->comment_ID,
			'[author_ip]'    => $comment->comment_author_ip,
			'[author_name]'  => $userdata->display_name
		];

		return $this->bot_cat_str_replace_message(
			__( '[Admin] Comment type message', 'bot-cat' ),
			__( 'Comment type message', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['user'][ $action_name ]
		);
	}

	/**
	 * Generate user type text
	 *
	 * @param string $action_name The name of the action
	 * @param object $user The user object
	 *
	 * @return array The generated user type text
	 */
	public function bot_cat_generate_user_type_text( string $action_name, object $user ): array {
		$keyword_text = [
			'[username]'        => $user->user_nicename,
			'[name]'            => $user->display_name,
			'[id]'              => $user->ID,
			'[registered_date]' => $user->user_registered,
			'[email]'           => $user->user_email
		];

		return $this->bot_cat_str_replace_message(
			__( '[Admin] User type message', 'bot-cat' ),
			__( 'User type message', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['user'][ $action_name ]
		);
	}
}