<?php

namespace SMW\MediaWiki\Hooks;

use SMW\SQLStore\QueryDependency\DependencyLinksUpdateJournal;
use Title;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RejectParserCacheValue
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RejectParserCacheValue extends HookHandler {

	/**
	 * @var DependencyLinksUpdateJournal
	 */
	private $dependencyLinksUpdateJournal;

	/**
	 * @since 3.0
	 *
	 * @param DependencyLinksUpdateJournal $dependencyLinksUpdateJournal
	 */
	public function __construct( DependencyLinksUpdateJournal $dependencyLinksUpdateJournal ) {
		$this->dependencyLinksUpdateJournal = $dependencyLinksUpdateJournal;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function process( Title $title ) {

		if ( $this->dependencyLinksUpdateJournal->has( $title ) ) {
			$this->dependencyLinksUpdateJournal->delete( $title );
			return false;
		}

		return true;
	}

}
