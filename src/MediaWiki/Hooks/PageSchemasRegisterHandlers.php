<?php

namespace SMW\MediaWiki\Hooks;

/**
 * Registers SMW's PageSchemas handler with Extension:PageSchemas.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class PageSchemasRegisterHandlers {

	/**
	 * @since 7.0.0
	 */
	public function onPageSchemasRegisterHandlers(): bool {
		$GLOBALS['wgPageSchemasHandlerClasses'][] = 'SMW\MediaWiki\PageSchemas';

		return true;
	}

}
