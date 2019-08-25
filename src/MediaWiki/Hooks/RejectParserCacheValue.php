<?php

namespace SMW\MediaWiki\Hooks;

use SMW\DependencyValidator;
use SMW\NamespaceExaminer;
use SMW\DIWikiPage;
use SMW\EntityCache;
use Title;
use Page;

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
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var DependencyValidator
	 */
	private $dependencyValidator;

	/**
	 * @since 3.0
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 * @param DependencyValidator $dependencyValidator
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer, DependencyValidator $dependencyValidator ) {
		$this->namespaceExaminer = $namespaceExaminer;
		$this->dependencyValidator = $dependencyValidator;
	}

	/**
	 * @since 3.0
	 *
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function process( Page $page ) {

		$title = $page->getTitle();

		if ( $this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) === false ) {
			return true;
		}

		$subject = DIWikiPage::newFromTitle( $title );

		if ( $this->dependencyValidator->canKeepParserCache( $subject ) ) {
			return true;
		}

		$this->logger->info(
			[ 'RejectParserCacheValue', 'Rejected, found archaic query dependencies', '{etag}' ],
			[ 'role' => 'user' ]
		);

		// Return false to reject an otherwise usable cached value from the
		// parser cache
		return false;
	}

}
