<?php

namespace SMW;

use MediaWiki\Title\Title;
use SMW\DataItems\Blob;
use SMW\DataItems\Boolean;
use SMW\DataItems\Concept;
use SMW\DataItems\Container;
use SMW\DataItems\Error;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\Uri;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DataItemFactory {

	/**
	 * @since 2.4
	 *
	 * @param string $error
	 */
	public function newDIError( $error ): Error {
		return new Error( $error );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 * @param bool $inverse
	 */
	public function newDIProperty( $key, $inverse = false ): Property {
		return new Property( str_replace( ' ', '_', $key ), $inverse );
	}

	/**
	 * @since 2.4
	 *
	 * @param string|Title $title
	 * @param int $namespace
	 * @param string $interwiki
	 * @param string $subobjectName
	 */
	public function newDIWikiPage(
		$title,
		$namespace = NS_MAIN,
		$interwiki = '',
		$subobjectName = ''
	): WikiPage {
		if ( $title instanceof Title ) {
			return WikiPage::newFromTitle( $title );
		}

		return new WikiPage( $title, $namespace, $interwiki, $subobjectName );
	}

	/**
	 * @since 2.4
	 *
	 * @param ContainerSemanticData $containerSemanticData
	 *
	 * @return Container
	 */
	public function newDIContainer( ContainerSemanticData $containerSemanticData ): Container {
		return new Container( $containerSemanticData );
	}

	/**
	 * @since 3.0
	 */
	public function newContainerSemanticData( WikiPage $subject ): ContainerSemanticData {
		return new ContainerSemanticData( $subject );
	}

	/**
	 * @since 2.4
	 *
	 * @param int $number
	 */
	public function newDINumber( $number ): Number {
		return new Number( $number );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $text
	 */
	public function newDIBlob( $text ): Blob {
		return new Blob( $text );
	}

	/**
	 * @since 2.4
	 *
	 * @param bool $boolean
	 */
	public function newDIBoolean( $boolean ): Boolean {
		return new Boolean( $boolean );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $concept
	 * @param string $docu
	 * @param int $queryfeatures
	 * @param int $size
	 * @param int $depth
	 */
	public function newDIConcept(
		$concept,
		$docu = '',
		$queryfeatures = 0,
		$size = 0,
		$depth = 0
	): Concept {
		return new Concept( $concept, $docu, $queryfeatures, $size, $depth );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $scheme
	 * @param string $hierpart
	 * @param string $query
	 * @param string $fragment
	 */
	public function newDIUri(
		$scheme,
		$hierpart,
		$query = '',
		$fragment = ''
	): Uri {
		return new Uri( $scheme, $hierpart, $query, $fragment );
	}

	/**
	 * @since 2.5
	 *
	 * @param int $calendarmodel
	 * @param int $year
	 * @param int|false $month
	 * @param int|false $day
	 * @param int|false $hour
	 * @param int|false $minute
	 * @param int|false $second
	 * @param int|false $timezone
	 */
	public function newDITime(
		$calendarmodel,
		$year,
		$month = false,
		$day = false,
		$hour = false,
		$minute = false,
		$second = false,
		$timezone = false
	): Time {
		return new Time( $calendarmodel, $year, $month, $day, $hour, $minute, $second, $timezone );
	}

}
