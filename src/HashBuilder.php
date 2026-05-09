<?php

namespace SMW;

use MediaWiki\Title\Title;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;

/**
 * Utility class to create unified hash keys for a variety of objects
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class HashBuilder {

	/**
	 * @since 2.4
	 */
	public static function createFromSemanticData( SemanticData $semanticData ): string {
		$hash = [];
		$hash[] = $semanticData->getSubject()->getSerialization();

		foreach ( $semanticData->getProperties() as $property ) {
			$hash[] = $property->getKey();

			foreach ( $semanticData->getPropertyValues( $property ) as $di ) {
				$hash[] = $di->getSerialization();
			}
		}

		foreach ( $semanticData->getSubSemanticData() as $data ) {
			$hash[] = $data->getHash();
		}

		sort( $hash );

		return md5( implode( '#', $hash ) );
	}

	/**
	 * @since 2.1
	 */
	public static function createFromContent( string|array $hashableContent, string $prefix = '' ): string {
		if ( is_string( $hashableContent ) ) {
			$hashableContent = [ $hashableContent ];
		}

		return $prefix . md5( json_encode( $hashableContent ) );
	}

	/**
	 * @since 2.5
	 */
	public static function createFromArray( array $hashableContent, string $prefix = '' ): string {
		return $prefix . md5( json_encode( $hashableContent ) );
	}

	/**
	 * @since 2.4
	 */
	public static function createFromSegments( /* args */ ): string {
		return implode( '#', func_get_args() );
	}

	/**
	 * @since 2.1
	 */
	public static function getHashIdForTitle( Title $title ): string {
		return self::createFromSegments(
			$title->getDBKey(),
			$title->getNamespace(),
			$title->getInterwiki(),
			$title->getFragment()
		);
	}

	/**
	 * @since 2.1
	 */
	public static function getHashIdForDiWikiPage( WikiPage $dataItem ): string {
		return self::createFromSegments(
			$dataItem->getDBKey(),
			$dataItem->getNamespace(),
			$dataItem->getInterwiki(),
			$dataItem->getSubobjectName()
		);
	}

	/**
	 * @since 2.1
	 */
	public static function newTitleFromHash( string $hash ): Title {
		[ $title, $namespace, $interwiki, $fragement ] = explode( '#', $hash, 4 );
		return Title::makeTitle( $namespace, $title, $fragement, $interwiki );
	}

	/**
	 * @note This method does not make additional checks therefore it is assumed
	 * that the input hash is derived or generated from HashBuilder::getSegmentedHashId
	 *
	 * @since 2.1
	 */
	public static function newDiWikiPageFromHash( string $hash ): WikiPage {
		[ $title, $namespace, $interwiki, $subobjectName ] = explode( '#', $hash, 4 );

		// A leading underscore is an internal SMW convention to describe predefined
		// properties and as such need to be transformed into a valid representation
		if ( $title[0] === '_' ) {
			$title = str_replace( ' ', '_', PropertyRegistry::getInstance()->findPropertyLabelById( $title ) );
		}

		return new WikiPage( $title, $namespace, $interwiki, $subobjectName );
	}

}
