<?php

namespace SMW;

use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDIContainer as DIContainer;
use SMWDIError as DIError;
use SMWDINumber as DINumber;
use SMWDIUri as DIUri;
use SMWDITime  as DITime;
use Title;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DataItemFactory {

	/**
	 * @since 2.4
	 *
	 * @param string $error
	 *
	 * @return DIError
	 */
	public function newDIError( $error ) {
		return new DIError( $error );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 * @param boolean $inverse
	 *
	 * @return DIProperty
	 */
	public function newDIProperty( $key, $inverse = false ) {
		return new DIProperty( str_replace( ' ', '_', $key ), $inverse );
	}

	/**
	 * @since 2.4
	 *
	 * @param string|Title $title
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobjectName
	 *
	 * @return DIWikiPage
	 */
	public function newDIWikiPage( $title, $namespace = NS_MAIN, $interwiki = '', $subobjectName = '' ) {

		if ( $title instanceof Title ) {
			return DIWikiPage::newFromTitle( $title );
		}

		return new DIWikiPage( $title, $namespace, $interwiki, $subobjectName );
	}

	/**
	 * @since 2.4
	 *
	 * @param ContainerSemanticData $containerSemanticData
	 *
	 * @return DIContainer
	 */
	public function newDIContainer( ContainerSemanticData $containerSemanticData ) {
		return new DIContainer( $containerSemanticData );
	}

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return ContainerSemanticData
	 */
	public function newContainerSemanticData( DIWikiPage $subject ) {
		return new ContainerSemanticData( $subject );
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $number
	 *
	 * @return DINumber
	 */
	public function newDINumber( $number ) {
		return new DINumber( $number );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 *
	 * @return DIBlob
	 */
	public function newDIBlob( $text ) {
		return new DIBlob( $text );
	}

	/**
	 * @since 2.4
	 *
	 * @param boolean $boolean
	 *
	 * @return DIBoolean
	 */
	public function newDIBoolean( $boolean ) {
		return new DIBoolean( $boolean );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $concept
	 * @param string $docu
	 * @param integer $queryfeatures
	 * @param integer $size
	 * @param integer $depth
	 *
	 * @return DIConcept
	 */
	public function newDIConcept( $concept, $docu = '', $queryfeatures = 0, $size = 0, $depth = 0 ) {
		return new DIConcept( $concept, $docu, $queryfeatures, $size, $depth );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $scheme
	 * @param string $hierpart
	 * @param string $query
	 * @param string $fragment
	 *
	 * @return DIUri
	 */
	public function newDIUri( $scheme, $hierpart, $query = '', $fragment = '' ) {
		return new DIUri( $scheme, $hierpart, $query, $fragment );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $calendarmodel
	 * @param integer $year
	 * @param integer|false $month
	 * @param integer|false $day
	 * @param integer|false $hour
	 * @param integer|false $minute
	 * @param integer|false $second
	 * @param integer|false $timezone
	 *
	 * @return DITime
	 */
	public function newDITime( $calendarmodel, $year, $month = false, $day = false, $hour = false, $minute = false, $second = false, $timezone = false ) {
		return new DITime( $calendarmodel, $year, $month, $day, $hour, $minute, $second, $timezone );
	}

}
