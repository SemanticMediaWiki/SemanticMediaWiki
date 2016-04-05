<?php

namespace SMW;

use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIBlob as DIBlob;
use SMWDIBoolean as DIBoolean;
use SMWDIContainer as DIContainer;
use SMWDIError as DIError;
use SMWDINumber as DINumber;

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
	 * @param string $dbKey
	 * @param integer $namespace
	 * @param string $interwiki
	 * @param string $subobjectName
	 *
	 * @return DIWikiPage
	 */
	public function newDIWikiPage( $dbKey, $namespace = NS_MAIN, $interwiki = '', $subobjectName = '' ) {
		return new DIWikiPage( $dbKey, $namespace, $interwiki, $subobjectName );
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

}
