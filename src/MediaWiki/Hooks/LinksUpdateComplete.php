<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\Parser\ParserOutputLinkTypes;
use MediaWiki\Title\Title;
use Psr\Log\LoggerAwareTrait;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\HookListener;
use SMW\MediaWiki\RevisionGuardAwareTrait;
use SMW\NamespaceExaminer;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * LinksUpdateComplete hook is called at the end of LinksUpdate()
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateComplete
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class LinksUpdateComplete implements HookListener {

	use RevisionGuardAwareTrait;
	use LoggerAwareTrait;

	private bool $enabledDeferredUpdate = true;

	private bool $isReady = true;

	/**
	 * @since 3.0
	 */
	public function __construct( private NamespaceExaminer $namespaceExaminer ) {
	}

	/**
	 * @since 3.0
	 *
	 * @param bool $isReady
	 */
	public function isReady( $isReady ): void {
		$this->isReady = (bool)$isReady;
	}

	/**
	 * @since 2.4
	 */
	public function disableDeferredUpdate(): void {
		$this->enabledDeferredUpdate = false;
	}

	/**
	 * @since 1.9
	 *
	 * @param LinksUpdate $linksUpdate
	 *
	 * @return true
	 */
	public function process( $linksUpdate ) {
		if ( $this->isReady === false ) {
			return $this->doAbort();
		}

		$title = $linksUpdate->getTitle();

		if ( $this->revisionGuard->isSkippableUpdate( $title ) ) {
			return true;
		}

		$parserData = ApplicationFactory::getInstance()->newParserData(
			$title,
			$linksUpdate->getParserOutput()
		);

		if ( $this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			// #347 showed that an external process (e.g. RefreshLinksJob) can inject a
			// ParserOutput without/cleared SemanticData which forces the Store updater
			// to create an empty container that will clear all existing data.
			if ( $parserData->getSemanticData()->isEmpty() ) {
				$this->updateSemanticData( $parserData, $title, 'empty data' );
			}
		}

		$opts = [ 'defer' => $this->enabledDeferredUpdate ];

		// Push updates on properties directly without delay
		if ( $title->getNamespace() === SMW_NS_PROPERTY ) {
			$opts['defer'] = false;
		}

		// Scan the ParserOutput for a possible externally set option
		if ( $linksUpdate->getParserOutput()->getExtensionData( $parserData::OPT_FORCED_UPDATE ) === true ) {
			$parserData->setOption( $parserData::OPT_FORCED_UPDATE, true );
		}

		// Update incurred by a template change and is signaled through
		// the following condition
		if ( $linksUpdate->getParserOutput()->getLinkList( ParserOutputLinkTypes::TEMPLATE ) !== [] && $linksUpdate->isRecursive() === false ) {
			$parserData->setOption( $parserData::OPT_FORCED_UPDATE, true );
		}

		$parserData->setOrigin( 'LinksUpdateConstructed' );
		$parserData->updateStore( $opts );

		return true;
	}

	/**
	 * To ensure that for a Title and its current revision a ParserOutput
	 * object is really meant to be "empty" (e.g. delete action initiated by a
	 * human) the content is re-parsed in order to fetch the newest available data
	 *
	 * @note Parsing is expensive but it is more expensive to loose data or to
	 * expect that an external process adheres the object contract
	 */
	private function updateSemanticData( &$parserData, Title $title, string $reason = '' ): void {
		$this->logger->info(
			[
				'LinksUpdateConstructed',
				"Required content re-parse due to $reason",
				$title->getPrefixedDBKey()
			]
		);

		$semanticData = $this->reparseAndFetchSemanticData( $title );

		if ( $semanticData instanceof SemanticData ) {
			$parserData->setSemanticData( $semanticData );
		}
	}

	private function reparseAndFetchSemanticData( Title $title ) {
		$contentParser = ApplicationFactory::getInstance()->newContentParser( $title );
		$parserOutput = $contentParser->parse()->getOutput();

		if ( $parserOutput === null ) {
			return null;
		}

		return $parserOutput->getExtensionData( 'smwdata' );
	}

	private function doAbort(): bool {
		$this->logger->info(
			"LinksUpdateConstructed was invoked but the site isn't ready yet, aborting the processing."
		);

		return false;
	}

}
