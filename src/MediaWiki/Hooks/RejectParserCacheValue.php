<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\RejectParserCacheValueHook;
use Psr\Log\LoggerInterface;
use SMW\DataItems\WikiPage as DIWikiPage;
use SMW\DependencyValidatorFactory;
use SMW\NamespaceExaminer;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/RejectParserCacheValue
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class RejectParserCacheValue implements RejectParserCacheValueHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly LoggerInterface $logger,
		private readonly DependencyValidatorFactory $dependencyValidatorFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onRejectParserCacheValue( $parserOutput, $wikiPage, $parserOptions ) {
		$title = $wikiPage->getTitle();

		if ( !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return true;
		}

		$dependencyValidator = $this->dependencyValidatorFactory->newFor( $wikiPage, $parserOptions );

		if ( $dependencyValidator->canKeepParserCache( DIWikiPage::newFromTitle( $title ) ) ) {
			return true;
		}

		$this->logger->info(
			'RejectParserCacheValue Rejected, found archaic query dependencies',
			[
				'role' => 'user'
			]
		);

		// Return false to reject an otherwise usable cached value from the
		// parser cache
		return false;
	}

}
