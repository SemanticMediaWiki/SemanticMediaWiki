<?php

namespace SMW;

use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIContainer as DIContainer;
use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class Error {

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * @since 2.4
	 *
	 * @param DIWikiPage $subject
	 */
	public function __construct( DIWikiPage $subject ) {
		$this->subject = $subject;
	}

	/**
	 * @since 2.4
	 *
	 * @return DIProperty
	 */
	public function getProperty() {
		return new DIProperty( '_ERRC' );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 * @param string $errorMsg
	 *
	 * @return DIContainer
	 */
	public function getContainerFor( DIProperty $property, $errorMsg = '' ) {

		$subWikiPage = new DIWikiPage(
			$this->subject->getDBkey(),
			$this->subject->getNamespace(),
			$this->subject->getInterwiki(),
			'_ERR' . md5( $property->getKey() )
		);

		$containerSemanticData = new ContainerSemanticData( $subWikiPage );

		$containerSemanticData->addPropertyObjectValue(
			new DIProperty( '_ERRP' ),
			$property->getDiWikiPage()
		);

		$containerSemanticData->addPropertyObjectValue(
			new DIProperty( '_ERRT' ),
			new DIBlob( $errorMsg )
		);

		return new DIContainer( $containerSemanticData );
	}

}
