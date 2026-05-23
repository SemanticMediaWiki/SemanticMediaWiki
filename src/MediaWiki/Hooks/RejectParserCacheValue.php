<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\RejectParserCacheValueHook;
use Psr\Log\LoggerInterface;
use SMW\DataItems\WikiPage as DIWikiPage;
use SMW\NamespaceExaminer;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Site;

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

		$applicationFactory = ApplicationFactory::getInstance();
		$parserCache = $applicationFactory->create( 'ParserCache' );

		$dependencyValidator = $applicationFactory->newDependencyValidator(
			$this->getETag( $parserCache, $wikiPage, $parserOptions ),
			Site::getCacheExpireTime( 'parser' )
		);

		$dependencyValidator->setEventDispatcher(
			$applicationFactory->getEventDispatcher()
		);

		$subject = DIWikiPage::newFromTitle( $title );

		if ( $dependencyValidator->canKeepParserCache( $subject ) ) {
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

	private function getETag( $parserCache, $page, $pOpts ): string {
		return 'W/"' . $parserCache->makeParserOutputKey( $page, $pOpts ) .
			"--" . $page->getTouched() . '"';
	}

}
