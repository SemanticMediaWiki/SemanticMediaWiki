<?php

namespace SMW;

use SMWDINumber;
use SMWDIBlob;
use SMWQuery;

use Title;

/**
 * Class that provides access to the query meta data
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
	 * Sets QueryId
	 *
	 * Generates an unique id (e.g. _QUERYbda2acc317b66b564e39f45e3a18fff3)
	 *
	 * @since 1.9
	 *
	 * @param IdGenerator $generator
	 */
	public function setQueryId( IdGenerator $generator ) {
		$this->queryId = '_QUERY' . $generator->generateId();
	}

	/**
	 * Returns the query meta data property
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getProperty() {
		return new DIProperty( '_ASK' );
	}

	/**
	 * Returns the query data subobject container
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