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
	 * @since 3.1
	 *
	 * @param array $options
	 *
	 * @return CheckMagicWords
	 */
	public function newCheckMagicWords( array $options ) {
		return new CheckMagicWords( $options );
	}

	/**
	 * @since 2.0
	 *
	 * @return CachedFactbox
	 */
	public function newCachedFactbox() {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$cachedFactbox = new CachedFactbox(
			$applicationFactory->getEntityCache()
		);

		// Month = 30 * 24 * 3600
		$cachedFactbox->setCacheTTL( 2592000 );

		$cachedFactbox->isEnabled(
			$settings->isFlagSet( 'smwgFactboxFeatures', SMW_FACTBOX_CACHE )
		);

		$cachedFactbox->setFeatureSet(
			$settings->get( 'smwgFactboxFeatures' )
		);

		$cachedFactbox->setShowFactboxEdit(
			$settings->get( 'smwgShowFactboxEdit' )
		);

		$cachedFactbox->setShowFactbox(
			$settings->get( 'smwgShowFactbox' )
		);

		$cachedFactbox->setLogger(
			$applicationFactory->getMediaWikiLogger()
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
