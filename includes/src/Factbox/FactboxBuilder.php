<?php

namespace SMW\Factbox;

use SMW\FactboxCache;
use SMW\Factbox;
use SMW\ParserData;
use SMW\Application;

use OutputPage;
use IContextSource;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class FactboxBuilder {

	/**
	 * @since 2.0
	 *
	 * @param OutputPage $outputPage
	 *
	 * @return FactboxCache
	 */
	public function newFactboxCache( OutputPage $outputPage ) {
		return new FactboxCache( $outputPage );
	}

	/**
	 * @since 2.0
	 *
	 * @param ParserData $parserData
	 * @param IContextSource $context
	 *
	 * @return Factbox
	 */
	public function newFactbox( ParserData $parserData, IContextSource $context ) {
		return new Factbox(
			Application::getInstance()->getStore(),
			$parserData,
			Application::getInstance()->getSettings(),
			$context
		);
	}

}
