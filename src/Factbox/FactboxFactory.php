<?php

namespace SMW\Factbox;

use IContextSource;
use OutputPage;
use SMW\ApplicationFactory;
use SMW\ParserData;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class FactboxFactory {

	/**
	 * @since 2.0
	 *
	 * @return CachedFactbox
	 */
	public function newCachedFactbox() {

		$applicationFactory = ApplicationFactory::getInstance();

		$cachedFactbox = new CachedFactbox(
			$applicationFactory->getCache(
				$applicationFactory->getSettings()->get( 'smwgCacheType' )
			)
		);

		// Month = 30 * 24 * 3600
		$cachedFactbox->setExpiryInSeconds( 2592000 );

		$cachedFactbox->isEnabled(
			$applicationFactory->getSettings()->get( 'smwgFactboxUseCache' )
		);

		return $cachedFactbox;
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
