<?php

namespace SMW\MediaWiki\Jobs;

use Hooks;
use SMW\ApplicationFactory;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EntityIdDisposerJob extends JobBase {

	/**
	 * @var PropertyTableIdReferenceDisposer
	 */
	private $propertyTableIdReferenceDisposer;

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = array() ) {
		parent::__construct( 'SMW\EntityIdDisposerJob', $title, $params );

		$this->propertyTableIdReferenceDisposer = new PropertyTableIdReferenceDisposer(
			ApplicationFactory::getInstance()->getStore( '\SMW\SQLStore\SQLStore' )
		);
	}

	/**
	 * @since  2.5
	 *
	 * @return ResultIterator
	 */
	public function newOutdatedEntitiesResultIterator() {
		return $this->propertyTableIdReferenceDisposer->newOutdatedEntitiesResultIterator();
	}

	/**
	 * @since  2.5
	 *
	 * @param integer|stdClass $id
	 */
	public function dispose( $id ) {

		if ( is_int( $id ) ) {
			return $this->propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $id );
		}

		$this->propertyTableIdReferenceDisposer->cleanUpTableEntriesByRow( $id );
	}

	/**
	 * @see Job::run
	 *
	 * @since  2.5
	 */
	public function run() {

		// MW 1.29+ Avoid transaction collisions during Job execution
		$this->propertyTableIdReferenceDisposer->waitOnTransactionIdle();

		if ( $this->hasParameter( 'id' ) ) {
			$this->dispose( $this->getParameter( 'id' ) );
		} else {
			$this->doDisposeAll( $this->newOutdatedEntitiesResultIterator() );
		}

		return true;
	}

	private function doDisposeAll( $outdatedEntitiesResultIterator ) {
		foreach ( $outdatedEntitiesResultIterator as $row ) {
			$this->dispose( $row );
		}
	}

}
