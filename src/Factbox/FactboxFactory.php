<?php

namespace SMW\Factbox;

use IContextSource;
use OutputPage;
use SMW\ApplicationFactory;
use Title;
use ParserOutput;

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
		$settings = $applicationFactory->getSettings();

		$cachedFactbox = new CachedFactbox(
			$applicationFactory->getCache(
				$settings->get( 'smwgMainCacheType' )
			)
		);

		// Month = 30 * 24 * 3600
		$cachedFactbox->setExpiryInSeconds( 2592000 );

		$cachedFactbox->isEnabled(
			$settings->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_CACHE )
		);

		$cachedFactbox->setFeatureSet(
			$settings->get( 'smwgFactboxFeatures' )
		);

		return $cachedFactbox;
	}

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return Factbox
	 */
	public function newFactbox( Title $title, ParserOutput $parserOutput ) {

		$applicationFactory = ApplicationFactory::getInstance();

		$factbox = new Factbox(
			$applicationFactory->getStore(),
			$applicationFactory->newParserData( $title, $parserOutput )
		);

		$factbox->setFeatureSet(
			$applicationFactory->getSettings()->get( 'smwgFactboxFeatures' )
		);

		return $factbox;
	}

}
