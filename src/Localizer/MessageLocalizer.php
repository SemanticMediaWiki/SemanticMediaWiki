<?php

namespace SMW\Localizer;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
interface MessageLocalizer {

	/**
	 * @since 3.2
	 *
	 * @param string|array ...$args
	 *
	 * @return string
	 */
	public function msg( ...$args ): string;

}
