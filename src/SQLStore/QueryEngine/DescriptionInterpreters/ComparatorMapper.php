<?php

namespace SMW\SQLStore\QueryEngine\DescriptionInterpreters;

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
	 * @since 2.2
	 *
	 * @param ValueDescription $description
	 * @param string &$value
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function mapComparator( ValueDescription $description, &$value ) {

		$comparatorMap = [
			SMW_CMP_EQ   => '=',
			SMW_CMP_LESS => '<',
			SMW_CMP_GRTR => '>',
			SMW_CMP_LEQ  => '<=',
			SMW_CMP_GEQ  => '>=',
			SMW_CMP_NEQ  => '!=',
			SMW_CMP_LIKE => ' LIKE ',
			SMW_CMP_PRIM_LIKE => ' LIKE ',
			SMW_CMP_NLKE => ' NOT LIKE ',
			SMW_CMP_PRIM_NLKE => ' NOT LIKE '
		];

		$comparator = $description->getComparator();

		if ( !isset( $comparatorMap[$comparator] ) ) {
			throw new RuntimeException( "Unsupported comparator $comparator in value description." );
		}

		if ( $comparator === SMW_CMP_LIKE || $comparator === SMW_CMP_NLKE || $comparator === SMW_CMP_PRIM_LIKE || $comparator === SMW_CMP_PRIM_NLKE ) {

			if ( $description->getDataItem() instanceof DIUri ) {
				$value = str_replace( [ 'http://', 'https://', '%2A' ], [ '*', '*', '*' ], $value );
			}

			// Escape to prepare string matching:
			$value = str_replace(
				[ '\\', '%', '_', '*', '?' ],
				[ '\\\\', '\%', '\_', '%', '_' ],
				$value
			);
		}

		return $comparatorMap[$comparator];
	}

}
