<?php

namespace SMW\SQLStore\QueryEngine\Interpreter;

use RuntimeException;
use SMW\Query\Language\ValueDescription;
use SMWDIUri as DIUri;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ComparatorMapper {

	/**
	 * @var boolean
	 */
	private $enabledEnhancedRegExMatchSearch = false;

	/**
	 * @var array
	 */
	private $comparatorMap = array(
		SMW_CMP_EQ   => '=',
		SMW_CMP_LESS => '<',
		SMW_CMP_GRTR => '>',
		SMW_CMP_LEQ  => '<=',
		SMW_CMP_GEQ  => '>=',
		SMW_CMP_NEQ  => '!=',
		SMW_CMP_LIKE => ' LIKE ',
		SMW_CMP_NLKE => ' NOT LIKE '
	);

	/**
	 * @since 2.4
	 *
	 * @param boolean $enabledEnhancedRegExMatchSearch
	 */
	public function __construct( $enabledEnhancedRegExMatchSearch = null ) {
		$this->enabledEnhancedRegExMatchSearch = $enabledEnhancedRegExMatchSearch;

		if ( $this->enabledEnhancedRegExMatchSearch === null ) {
			$this->enabledEnhancedRegExMatchSearch = $GLOBALS['smwgEnabledEnhancedRegExMatchSearch'];
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param ValueDescription $description
	 *
	 * @return boolean
	 */
	public function isEnabledEnhancedRegExMatchSearch( ValueDescription $description ) {
		return $this->enabledEnhancedRegExMatchSearch && ( $description->getComparator() === SMW_CMP_LIKE || $description->getComparator() === SMW_CMP_NLKE );
	}

	/**
	 * @since 2.2
	 *
	 * @param ValueDescription $description
	 * @param string &$value
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function mapComparator( ValueDescription $description, &$value ) {

		$comparator = $description->getComparator();

		if ( !isset( $this->comparatorMap[$comparator] ) ) {
			throw new RuntimeException( "Unsupported comparator '" . $comparator . "' in value description." );
		}

		if ( $comparator === SMW_CMP_LIKE || $comparator === SMW_CMP_NLKE ) {

			if ( $description->getDataItem() instanceof DIUri ) {
				$value = str_replace( array( 'http://', 'https://', '%2A', '%2a' ), array( '', '', '*', '*' ), $value );
			}

			// Escape to prepare string matching:
			$value = str_replace(
				array( '\\', '%', '_', '*', '?' ),
				array( '\\\\', '\%', '\_', '%', '_' ),
				$value
			);
		}

		return $this->comparatorMap[$comparator];
	}

}
