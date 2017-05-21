<?php

namespace SMW\DataValues\ValueParsers;

use SMWPropertyValue as PropertyValue;
use SMWDataValue as DataValue;
use SMW\Localizer;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyValueParser implements ValueParser {

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @var array
	 */
	private $invalidCharacterList = array();

	/**
	 * @var boolean
	 */
	private $isCapitalLinks = true;

	/**
	 * @var boolean
	 */
	private $reqCapitalizedFirstChar = false;

	/**
	 * @var boolean
	 */
	private $isQueryContext = false;

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.5
	 *
	 * @param array $invalidCharacterList
	 */
	public function setInvalidCharacterList( array $invalidCharacterList ) {
		$this->invalidCharacterList = $invalidCharacterList;
	}

	/**
	 * Corresponds to the $wgCapitalLinks setting
	 *
	 * @since 3.0
	 *
	 * @param boolean $isCapitalLinks
	 */
	public function isCapitalLinks( $isCapitalLinks ) {
		$this->isCapitalLinks = (bool)$isCapitalLinks;
	}

	/**
	 * Whether upper case for the first character is required or not in case of
	 * $wgCapitalLinks = false.
	 *
	 * @since 2.5
	 *
	 * @param boolean $reqCapitalizedFirstChar
	 */
	public function reqCapitalizedFirstChar( $reqCapitalizedFirstChar ) {
		$this->reqCapitalizedFirstChar = (bool)$reqCapitalizedFirstChar;
	}

	/**
	 * Whether or not the parsing is executed within a query context which may
	 * allow exceptions on the validation of invalid characters.
	 *
	 * @since 2.5
	 *
	 * @param boolean $isQueryContext
	 */
	public function isQueryContext( $isQueryContext ) {
		$this->isQueryContext = (bool)$isQueryContext;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $userValue
	 *
	 * @return array
	 */
	public function parse( $userValue ) {

		$this->errors = array();

		// #1727 <Foo> or <Foo-<Bar> are not permitted but
		// Foo-<Bar will be converted to Foo-
		$userValue = strip_tags(
			htmlspecialchars_decode( $userValue )
		);

		if ( !$this->doCheckValidCharacters( $userValue ) ) {
			return array( null, null, null );
		}

		return $this->getNormalizedValueFrom( $userValue );
	}

	private function doCheckValidCharacters( $value ) {

		if ( trim( $value ) === '' ) {
			$this->errors[] = array( 'smw_emptystring' );
			return false;
		}

		$invalidCharacter = '';

		foreach ( $this->invalidCharacterList as $character ) {
			if ( strpos( $value, $character ) !== false ) {
				$invalidCharacter = $character;
				break;
			}
		}

		// #1567, only on a query context so that |sort=# are allowed
		if ( $invalidCharacter === '' && strpos( $value, '#' ) !== false && !$this->isQueryContext ) {
			$invalidCharacter = '#';
		}

		if ( $invalidCharacter !== '' ) {
			$this->errors[] = array( 'smw-datavalue-property-invalid-character', $value, $invalidCharacter );
			return false;
		}

		// #676, only on a query context allow Foo.Bar
		if ( $invalidCharacter === '' && !$this->isQueryContext && strpos( $value, '.' ) !== false ) {
			$this->errors[] = array( 'smw-datavalue-property-invalid-chain', $value );
			return false;
		}

		return true;
	}

	private function getNormalizedValueFrom( $value ) {

		$inverse = false;
		$capitalizedName = '';

		// slightly normalise label
		$propertyName = $this->doNormalize(
			ltrim( rtrim( $value, ' ]' ), ' [' ),
			$this->isCapitalLinks
		);

		if ( $this->reqCapitalizedFirstChar ) {
			$capitalizedName = $this->doNormalize( $propertyName, true );
		}

		// property refers to an inverse
		if ( ( $propertyName !== '' ) && ( $propertyName { 0 } == '-' ) ) {
			$propertyName = $this->doNormalize( (string)substr( $value, 1 ), $this->isCapitalLinks );
			/// NOTE The cast is necessary at least in PHP 5.3.3 to get string '' instead of boolean false.
			/// NOTE It is necessary to normalize again here, since normalization may uppercase the first letter.
			$inverse = true;
		}

		return array( $propertyName, $capitalizedName, $inverse );
	}

	private function doNormalize( $text, $isCapitalLinks ) {

		$text = trim( $text );

		if ( $isCapitalLinks ) {
			$text = Localizer::getInstance()->getContentLanguage()->ucfirst( $text );
		}

		return str_replace( '_', ' ', $text );
	}

}
