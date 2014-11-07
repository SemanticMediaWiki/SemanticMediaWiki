<?php

namespace SMW\Factbox;

use SMW\FactboxCache;
use SMW\Factbox;
use SMW\ParserData;
use SMW\ApplicationFactory;

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

		$applicationFactory = ApplicationFactory::getInstance();

		$messageBuilder = $applicationFactory->newMwCollaboratorFactory()->newMessageBuilder();
		$messageBuilder->setLanguageFromContext( $context );

		return new Factbox(
			$applicationFactory->getStore(),
			$parserData,
			$messageBuilder
		);
	}

}
