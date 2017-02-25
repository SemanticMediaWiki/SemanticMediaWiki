<?php

namespace SMW\DataValues\ValueParsers;

use SMWPropertyValue as PropertyValue;
use SMWDataValue as DataValue;

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
	private $requireUpperCase = false;

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
	 * Enforce upper case for the first character that are used within the
	 * property namespace in order to avoid invalid types when the $wgCapitalLinks
	 * setting is disabled.
	 *
	 * @since 2.5
	 *
	 * @param boolean $requireUpperCase
	 */
	public function requireUpperCase( $requireUpperCase ) {
		$this->requireUpperCase = (bool)$requireUpperCase;
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
			return array( null, null );
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

		if ( $this->requireUpperCase ) {
			$value = $this->applyUpperCaseToLeadingCharacter( $value );
		}

		// slightly normalise label
		$propertyName = smwfNormalTitleText( ltrim( rtrim( $value, ' ]' ), ' [' ) );

		if ( ( $propertyName !== '' ) && ( $propertyName { 0 } == '-' ) ) { // property refers to an inverse
			$propertyName = smwfNormalTitleText( (string)substr( $value, 1 ) );
			/// NOTE The cast is necessary at least in PHP 5.3.3 to get string '' instead of boolean false.
			/// NOTE It is necessary to normalize again here, since normalization may uppercase the first letter.
			$inverse = true;
		}

		return array( $propertyName, $inverse );
	}

	private function applyUpperCaseToLeadingCharacter( $value ) {
		// ucfirst is not utf-8 safe hence the reliance on mb_strtoupper
		return mb_strtoupper( mb_substr( $value, 0, 1 ) ) . mb_substr( $value, 1 );
	}

}
