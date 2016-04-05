<?php

namespace SMW;

use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIBlob as DIBlob;
use SMWDIContainer as DIContainer;

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
	 * @param array|string $errorMsg
	 *
	 * @return DIContainer
	 */
	public function getContainerFor( DIProperty $property = null, $errorMsg = '' ) {

		if ( $property !== null && $property->isInverse() ) {
			$property = new DIProperty( $property->getKey() );
		}

		$errorMsg = is_array( $errorMsg ) ? implode( ' ', $errorMsg ) : $errorMsg;

		$subject = new DIWikiPage(
			$this->subject->getDBkey(),
			$this->subject->getNamespace(),
			$this->subject->getInterwiki(),
			'_ERR' . md5( ( $property !== null ? $property->getKey() : 'UNKNOWN' ) . $errorMsg )
		);

		// Encode brackets to avoid an annotion is created/included
		return $this->newDiContainer( $subject, $property, InTextAnnotationParser::obscureAnnotation( $errorMsg ) );
	}

	private function newDiContainer( $subject, $property, $errorMsg ) {
		$containerSemanticData = new ContainerSemanticData( $subject );

		if (  $property !== null ) {
			$containerSemanticData->addPropertyObjectValue(
				new DIProperty( '_ERRP' ),
				$property->getDiWikiPage()
			);
		}

		$containerSemanticData->addPropertyObjectValue(
			new DIProperty( '_ERRT' ),
			new DIBlob( $errorMsg )
		);

		return new DIContainer( $containerSemanticData );
	}

}
