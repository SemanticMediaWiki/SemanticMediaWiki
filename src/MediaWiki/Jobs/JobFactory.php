<?php

namespace SMW\MediaWiki\Jobs;

use Title;

/**
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class JobFactory {

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return UpdateJob
	 */
	public function newUpdateJob( Title $title, array $parameters = array() ) {
		return new UpdateJob( $title, $parameters );
	}

	/**
	 * @since 2.0
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return UpdateDispatcherJob
	 */
	public function newUpdateDispatcherJob( Title $title, array $parameters = array() ) {
		return new UpdateDispatcherJob( $title, $parameters );
	}

	/**
	 * @since 2.3
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return ParserCachePurgeJob
	 */
	public function newParserCachePurgeJob( Title $title, array $parameters = array() ) {
		return new ParserCachePurgeJob( $title, $parameters );
	}

	/**
	 * @since 2.4
	 *
	 * @param Title $title
	 * @param array $parameters
	 *
	 * @return SchemaUpdateJob
	 */
	public function newSchemaUpdateJob( Title $title, array $parameters = array() ) {
		return new SchemaUpdateJob( $title, $parameters );
	}

}
