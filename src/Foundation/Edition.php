<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

/**
 * Reports whether the running build is the Free or Pro edition.
 *
 * The Free WordPress.org distribution MUST NOT bundle Pro code. The Pro
 * build flips this signal by registering a constant filter very early.
 */
class Edition {

	public function is_pro(): bool {
		return (bool) apply_filters( 'botcat_is_pro', false );
	}
}
