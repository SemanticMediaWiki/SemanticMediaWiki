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
	 * @return FactboxCache
	 */
	public function newFactboxCache() {

		$cacheFactory = ApplicationFactory::getInstance()->newCacheFactory();

		$cacheOptions = $cacheFactory->newCacheOptions( array(
			'useCache' => ApplicationFactory::getInstance()->getSettings()->get( 'smwgFactboxUseCache' ),
			'ttl'      => 0
		) );

		$factboxCache = new FactboxCache(
			$cacheFactory->newMediaWikiCompositeCache( $cacheFactory->getMainCacheType() ),
			$cacheOptions
		);

		return $factboxCache;
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
