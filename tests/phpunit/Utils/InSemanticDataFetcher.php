<?php

namespace SMW\Tests\Utils;

use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\RequestOptions;
use SMW\Store;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class InSemanticDataFetcher {

	/**
	 * @since 2.0
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 2.0
	 *
	 * @return SemanticData
	 */
	public function getSemanticData( WikiPage $subject ) {
		$requestOptions = new RequestOptions();
		$requestOptions->sort = true;

		$semanticData = new SemanticData( $subject );

		$incomingProperties = $this->store->getInProperties( $subject, $requestOptions );

		foreach ( $incomingProperties as $property ) {
			$values = $this->store->getPropertySubjects( $property, null );

			foreach ( $values as $value ) {
				$semanticData->addPropertyObjectValue( $property, $value );
			}
		}

		return $semanticData;
	}

}
