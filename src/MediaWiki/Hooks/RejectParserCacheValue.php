<?php

namespace SMW\MediaWiki\Hooks;

use Page;
use Psr\Log\LoggerAwareTrait;
use SMW\DependencyValidator;
use SMW\DIWikiPage;
use SMW\MediaWiki\HookListener;
use SMW\NamespaceExaminer;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RejectParserCacheValue
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class RejectParserCacheValue implements HookListener {

	use LoggerAwareTrait;

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
	 * @param Page $page
	 *
	 * @return bool
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
