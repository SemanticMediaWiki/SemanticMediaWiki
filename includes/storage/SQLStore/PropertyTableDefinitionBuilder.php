<?php

namespace SMW\SQLStore;

use SMW\DataValueFactory;
use SMWDIProperty;

/**
 * Class that generates property table definition
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Class that generates property table definitions
 *
 * @ingroup SQLStore
 */
class PropertyTableDefinitionBuilder {

	/** @var TableDefinition[] */
	protected $propertyTables = array();

	/** @var array */
	protected $fixedPropertyTableIds = array();

	/**
	 * @since 1.9
	 *
	 * @param array $diType
	 * @param array $specialProperties
	 * @param array $fixedProperties
	 */
	public function __construct(
		array $diTypes,
		array $specialProperties,
		array $fixedProperties
	) {
		$this->diTypes = $diTypes;
		$this->specialProperties = $specialProperties;
		$this->fixedProperties = $fixedProperties;
	}

	/**
	 * Build definitions
	 *
	 * @since 1.9
	 */
	public function doBuild() {
		wfProfileIn( __METHOD__ );

		$this->getDITypes( $this->diTypes );
		$this->getSpecialProperties( $this->specialProperties );
		$this->getFixedProperties( $this->fixedProperties );

		wfRunHooks( 'SMW::SQLStore::PropertyTableDefinition', array( &$this->propertyTables ) );

		$this->getFixedPropertyTableIds( $this->propertyTables );

		wfProfileOut( __METHOD__ );
	}

	/**
	 * Returns table prefix
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getTablePrefix() {
		return 'smw_fpt';
	}

	/**
	 * Returns fixed properties table Ids
	 *
	 * @since 1.9
	 *
	 * @return array|null
	 */
	public function getTableIds() {
		return $this->fixedPropertyTableIds;
	}

	/**
	 * Returns property table definitions
	 *
	 * @since 1.9
	 *
	 * @return TableDefinition[]
	 */
	public function getTableDefinitions() {
		return $this->propertyTables;
	}

	/**
	 * Returns new table definition
	 *
	 * @since 1.9
	 *
	 * @param $diType
	 * @param $tableName
	 * @param $fixedProperty
	 *
	 * @return TableDefinition
	 */
	public function getDefinition( $diType, $tableName, $fixedProperty = false ) {
		return new TableDefinition( $diType, $tableName, $fixedProperty );
	}

	/**
	 * Add property table definition
	 *
	 * @since 1.9
	 *
	 * @param $diType
	 * @param $tableName
	 * @param $fixedProperty
	 */
	protected function addPropertyTable( $diType, $tableName, $fixedProperty = false ) {
		$this->propertyTables[$tableName] = $this->getDefinition( $diType, $tableName, $fixedProperty );
	}

	/**
	 * Add DI type table definitions
	 *
	 * @since 1.9
	 *
	 * @param array $diTypes
	 */
	protected function getDITypes( array $diTypes ) {
		foreach( $diTypes as $tableDIType => $tableName ) {
			$this->addPropertyTable( $tableDIType, $tableName );
		}
	}

	/**
	 * Add special properties table definitions
	 *
	 * @since 1.9
	 *
	 * @param array $specialProperties
	 */
	protected function getSpecialProperties( array $specialProperties ) {
		foreach( $specialProperties as $propertyKey ) {
			$this->addPropertyTable(
				DataValueFactory::getDataItemId( SMWDIProperty::getPredefinedPropertyTypeId( $propertyKey ) ),
				$this->getTablePrefix() . strtolower( $propertyKey ),
				$propertyKey
			);
		}

		// Redirect table uses another subject scheme for historic reasons
		// TODO This should be changed if possible
		$redirectTableName = $this->getTablePrefix() . '_redi';
		if ( isset( $this->propertyTables[$redirectTableName]) ) {
			$this->propertyTables[$redirectTableName]->setUsesIdSubject( false );
		}
	}

	/**
	 * Add fixed property table definitions
	 *
	 * Get all the tables for the properties that are declared as fixed
	 * (overly used and thus having separate tables)
	 *
	 * @see $smwgFixedProperties
	 *
	 * @since 1.9
	 *
	 * @param array $fixedProperties
	 */
	protected function getFixedProperties( array $fixedProperties ) {
		foreach( $fixedProperties as $propertyKey => $tableDIType ) {
			$this->addPropertyTable(
				$tableDIType,
				$this->getTablePrefix() . '_' . md5( $propertyKey ),
				$propertyKey
			);
		}
	}

	/**
	 * Build index for fixed property tables Ids
	 *
	 * @since 1.9
	 *
	 * @param array $propertyTables
	 */
	protected function getFixedPropertyTableIds( array $propertyTables ) {

		foreach ( $propertyTables as $tid => $propTable ) {
			if ( $propTable->isFixedPropertyTable() ) {
				$this->fixedPropertyTableIds[$propTable->getFixedProperty()] = $tid;
			}
		}

		// Specifically set properties that must not be stored in any
		// property table to null here. Any function that hits this
		// null unprepared is doing something wrong anyway.
		$this->fixedPropertyTableIds['_SKEY'] = null;
	}

}
