<?php

namespace SMW;

use SMWDIProperty;
use SMWDINumber;
use SMWDIBlob;
use SMWQuery;
use Title;

/**
 * Handles query meta data collected in #ask / #show
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
 * @ingroup SMW
 * @ingroup ParserHooks
 *
 * @author mwjames
 */

/**
 * Class that provides access to the meta data about a query
 *
 * @ingroup SMW
 * @ingroup ParserHooks
 */
class QueryData {

	/**
	 * Subobject object
	 * @var $semanticData
	 */
	protected $subobject;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param Title $Title
	 */
	public function __construct( Title $title ) {
		$this->subobject = new Subobject( $title );
	}

	/**
	 * Returns errors collected during processing
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->subobject->getErrors();
	}

	/**
	 * Returns query data property
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getProperty() {
		return new SMWDIProperty( '_ASK' );
	}

	/**
	 * Returns query data subobject container
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getContainer() {
		return $this->subobject->getContainer();
	}

	/**
	 * Adds query data to the subobject container
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function add( SMWQuery $query, array $params ) {

		// Prepare subobject semantic container
		$Id = $this->subobject->getAnonymousIdentifier( serialize( $params ) );
		$this->subobject->setSemanticData( str_replace( '_', '_QUERY', $Id ) );

		$description = $query->getDescription();

		// Add query string
		$propertyDi = new SMWDIProperty( '_ASKST' );
		$valueDi = new SMWDIBlob( $description->getQueryString() );
		$this->subobject->getSemanticData()->addPropertyObjectValue( $propertyDi, $valueDi );

		// Add query size
		$propertyDi = new SMWDIProperty( '_ASKSI' );
		$valueDi = new SMWDINumber( $description->getSize() );
		$this->subobject->getSemanticData()->addPropertyObjectValue( $propertyDi, $valueDi );

		// Add query depth
		$propertyDi = new SMWDIProperty( '_ASKDE' );
		$valueDi = new SMWDINumber( $description->getDepth() );
		$this->subobject->getSemanticData()->addPropertyObjectValue( $propertyDi, $valueDi );

		// Add query format
		$propertyDi = new SMWDIProperty( '_ASKFO' );
		$valueDi = new SMWDIBlob( $params['format']->getValue() );
		$this->subobject->getSemanticData()->addPropertyObjectValue( $propertyDi, $valueDi );
	}

}