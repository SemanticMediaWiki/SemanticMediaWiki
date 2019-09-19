<?php

namespace SMW;

use Title;

/**
 * Utility class to create unified hash keys for a variety of objects
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class HashBuilder {

	/**
	 * @since 2.4
	 *
	 * @param SemanticData $semanticData
	 *
	 * @return string
	 */
	public static function createFromSemanticData( SemanticData $semanticData ) {

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
	 *
	 * @param string|array $hashableContent
	 * @param string $prefix
	 *
	 * @return string
	 */
	public static function createFromContent( $hashableContent, $prefix = '' ) {

		if ( is_string( $hashableContent ) ) {
			$hashableContent = [ $hashableContent ];
		}

		return $prefix . md5( json_encode( $hashableContent ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param array $hashableContent
	 * @param string $prefix
	 *
	 * @return string
	 */
	public static function createFromArray( array $hashableContent, $prefix = '' ) {
		return $prefix . md5( json_encode( $hashableContent ) );
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public static function createFromSegments( /* args */ ) {
		return implode( '#', func_get_args() );
	}

	/**
	 * @deprecated since 2.4, use Hash::createFromSegments
	 * @since 2.1
	 *
	 * @param string $title
	 * @param string $namespace
	 * @param string $interwiki
	 * @param string $fragment
	 *
	 * @return string
	 */
	public static function createHashIdFromSegments( $title, $namespace, $interwiki = '', $fragment = '' ) {
		return self::createFromSegments( $title, $namespace, $interwiki, $fragment );
	}

	/**
	 * @since 2.1
	 *
	 * @param Title $title
	 *
	 * @return string
	 */
	public static function getHashIdForTitle( Title $title ) {
		return self::createFromSegments(
			$title->getDBKey(),
			$title->getNamespace(),
			$title->getInterwiki(),
			$title->getFragment()
		);
	}

	/**
	 * @since 2.1
	 *
	 * @param DIWikiPage $dataItem
	 *
	 * @return string
	 */
	public static function getHashIdForDiWikiPage( DIWikiPage $dataItem ) {
		return self::createFromSegments(
			$dataItem->getDBKey(),
			$dataItem->getNamespace(),
			$dataItem->getInterwiki(),
			$dataItem->getSubobjectName()
		);
	}

	/**
	 * @since 2.1
	 *
	 * @param string $hash
	 *
	 * @return Title|null
	 */
	public static function newTitleFromHash( $hash ) {
		list( $title, $namespace, $interwiki, $fragement ) = explode( '#', $hash, 4 );
		return Title::makeTitle( $namespace, $title, $fragement, $interwiki );
	}

	/**
	 * @note This method does not make additional checks therefore it is assumed
	 * that the input hash is derived or generated from HashBuilder::getSegmentedHashId
	 *
	 * @since 2.1
	 *
	 * @param string
	 *
	 * @return DIWikiPage|null
	 */
	public static function newDiWikiPageFromHash( $hash ) {

		list( $title, $namespace, $interwiki, $subobjectName ) = explode( '#', $hash, 4 );

		// A leading underscore is an internal SMW convention to describe predefined
		// properties and as such need to be transformed into a valid representation
		if ( $title[0] === '_' ) {
			$title = str_replace( ' ', '_', PropertyRegistry::getInstance()->findPropertyLabelById( $title ) );
		}

		return new DIWikiPage( $title, $namespace, $interwiki, $subobjectName );
	}

}
