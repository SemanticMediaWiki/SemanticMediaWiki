<?php

namespace SMW\MediaWiki\Hooks;

use Hooks;
use LinksUpdate;
use SMW\ApplicationFactory;
use SMW\SemanticData;
use SMW\NamespaceExaminer;
use Title;

/**
 * LinksUpdateConstructed hook is called at the end of LinksUpdate()
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateConstructed
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class LinksUpdateConstructed extends HookHandler {

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var boolean
	 */
	private $enabledDeferredUpdate = true;

	/**
	 * @var boolean
	 */
	private $isReadOnly = false;

	/**
	 * @since 3.0
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer ) {
		$this->namespaceExaminer = $namespaceExaminer;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $isReadOnly
	 */
	public function isReadOnly( $isReadOnly ) {
		$this->isReadOnly = (bool)$isReadOnly;
	}

	/**
	 * @since 2.4
	 */
	public function disableDeferredUpdate() {
		$this->enabledDeferredUpdate = false;
	}

	/**
	 * @since 1.9
	 *
	 * @param LinksUpdate $linksUpdate
	 *
	 * @return true
	 */
	public function process( LinksUpdate $linksUpdate ) {

		if ( $this->isReadOnly ) {
			return false;
		}

		$title = $linksUpdate->getTitle();
		$latestRevID = $title->getLatestRevID( Title::GAID_FOR_UPDATE );

		$opts = [ 'defer' => $this->enabledDeferredUpdate ];

		// Allow any third-party extension to suppress the update process
		if ( \Hooks::run( 'SMW::LinksUpdate::ApprovedUpdate', [ $title, $latestRevID ] ) === false ) {
			return true;
		}

		/**
		 * @var ParserData $parserData
		 */
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
		if ( $linksUpdate->mTemplates !== [] && $linksUpdate->mRecursive === false ) {
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
	private function updateSemanticData( &$parserData, $title, $reason = '' ) {

		$this->log(
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

	private function reparseAndFetchSemanticData( $title ) {

		$contentParser = ApplicationFactory::getInstance()->newContentParser( $title );
		$parserOutput = $contentParser->parse()->getOutput();

		if ( $parserOutput === null ) {
			return null;
		}

		if ( method_exists( $parserOutput, 'getExtensionData' ) ) {
			return $parserOutput->getExtensionData( 'smwdata' );
		}

		return $parserOutput->mSMWData;
	}

}
