<?php

namespace SMW;

use SMWDINumber;
use SMWDIBlob;
use SMWQuery;

use Title;

/**
 * Class that provides access to the query meta data
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
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Class that provides access to the query meta data
 *
 * @ingroup SMW
 */
class QueryData {

	/** @var Subobject */
	protected $subobject;

	/** @var string */
	protected $queryId = null;

	/**
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
	 * Set QueryId
	 *
	 * Creates an unique id ( e.g. _QUERYbda2acc317b66b564e39f45e3a18fff3)
	 * which normally is based on parameters used in a #ask/#set query
	 *
	 * @since 1.9
	 *
	 * @param array $qualifiers
	 */
	public function setQueryId( array $qualifiers ) {
		$this->queryId = str_replace(
			'_',
			'_QUERY',
			$this->subobject->getAnonymousIdentifier( implode( '|', $qualifiers ) )
		);
	}

	/**
	 * Returns query data property
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getProperty() {
		return new DIProperty( '_ASK' );
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

		if ( $this->queryId === null ) {
			throw new UnknownIdException( '_QUERY Id is not set' );
		}

		// Prepare subobject semantic container
		$this->subobject->setSemanticData( $this->queryId );

		$description = $query->getDescription();

		// Add query string
		$propertyDi = new DIProperty( '_ASKST' );
		$valueDi = new SMWDIBlob( $description->getQueryString() );
		$this->subobject->getSemanticData()->addPropertyObjectValue( $propertyDi, $valueDi );

		// Add query size
		$propertyDi = new DIProperty( '_ASKSI' );
		$valueDi = new SMWDINumber( $description->getSize() );
		$this->subobject->getSemanticData()->addPropertyObjectValue( $propertyDi, $valueDi );

		// Add query depth
		$propertyDi = new DIProperty( '_ASKDE' );
		$valueDi = new SMWDINumber( $description->getDepth() );
		$this->subobject->getSemanticData()->addPropertyObjectValue( $propertyDi, $valueDi );

		// Add query format
		$propertyDi = new DIProperty( '_ASKFO' );
		$valueDi = new SMWDIBlob( $params['format']->getValue() );
		$this->subobject->getSemanticData()->addPropertyObjectValue( $propertyDi, $valueDi );
	}

}