<?php

namespace SMW\MediaWiki;

use Title;
use OutputPage;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class IndicatorRegistry {

	/**
	 * @var IndicatorProvider[]
	 */
	private $indicatorProviders = [];

	/**
	 * @var []
	 */
	private $indicators = [];

	/**
	 * @since 3.1
	 *
	 * @param IndicatorProvider|null $indicatorProvider
	 */
	public function addIndicatorProvider( IndicatorProvider $indicatorProvider = null ) {

		if ( $indicatorProvider === null ) {
			return;
		}

		$this->indicatorProviders[] = $indicatorProvider;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasIndicator( Title $title, $parserOutput ) {

		foreach ( $this->indicatorProviders as $indicatorProvider ) {

			if ( !$indicatorProvider->hasIndicator( $title, $parserOutput ) ) {
				continue;
			}

			$this->indicators = array_merge( $this->indicators, $indicatorProvider->getIndicators() );
		}

		return $this->indicators !== [];
	}

	/**
	 * @since 3.1
	 *
	 * @param OutputPage $outputPage
	 */
	public function attachIndicators( OutputPage $outputPage ) {
		$outputPage->setIndicators( $this->indicators );
	}

}
