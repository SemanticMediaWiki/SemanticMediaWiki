<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Application;
use SMW\ContentParser;
use SMW\SemanticData;

use LinksUpdate;

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
class LinksUpdateConstructed {

	/**
	 * @var LinksUpdate
	 */
	protected $linksUpdate = null;

	/**
	 * @var Application
	 */
	private $application = null;

	/**
	 * @since  1.9
	 *
	 * @param LinksUpdate $linksUpdate
	 */
	public function __construct( LinksUpdate $linksUpdate ) {
		$this->linksUpdate = $linksUpdate;
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$this->application = Application::getInstance();

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->application
			->newParserData( $this->linksUpdate->getTitle(), $this->linksUpdate->getParserOutput() );

		if ( $parserData->getSemanticData()->isEmpty() &&
			( $semanticData = $this->refetchSemanticData() ) instanceOf SemanticData ) {
			$parserData->setSemanticData( $semanticData );
		}

		$parserData->updateStore();

		return true;
	}

	/**
	 * #347 showed that an external process (e.g. RefreshLinksJob) can inject a
	 * ParserOutput without SemanticData which forces the Store updater to create
	 * an empty container and clear all existing data. To ensure that for a Title
	 * and its current revision are really "empty" (e.g. delete action initiated
	 * by a human) content is re-parsed
	 *
	 * @note Parsing is expensive but it is more expensive to loose data or to
	 * expect that MW adheres the object contract
	 */
	private function refetchSemanticData() {
		wfDebug( __METHOD__ . ' Empty SemanticData / re-parsing: ' . $this->linksUpdate->getTitle()->getPrefixedDBkey() . "\n" );

		$contentParser = $this->application->newContentParser( $this->linksUpdate->getTitle() );
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
