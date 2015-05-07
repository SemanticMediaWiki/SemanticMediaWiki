<?php

namespace SMW\MediaWiki\Hooks;

use OutputPage;
use ParserOutput;
use SMW\ApplicationFactory;
use SMW\MediaWiki\MessageBuilder;
use Title;

/**
 * InfoAction hook to add text after the action=info page content
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/InfoAction
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author misdre
 */
class InfoAction {

	/**
	 * @var array
	 */
	protected $pageInfo = null;

	/**
	 * @var OutputPage
	 */
	protected $outputPage = null;

	/**
	 * @var ParserOutput
	 */
	protected $parserOutput = null;

	/**
	 * @param OutputPage $outputPage
	 * @param ParserOutput $parserOutput
	 * @param array $pageInfo
	 */
	public function __construct( OutputPage $outputPage, ParserOutput $parserOutput, &$pageInfo ) {
		$this->outputPage = $outputPage;
		$this->parserOutput = $parserOutput;
		$this->pageInfo =& $pageInfo;
	}

	/**
	 * @return true
	 */
	public function process() {
		return $this->canPerformUpdate() ? $this->performUpdate() : true;
	}

	private function canPerformUpdate() {

		$title = $this->outputPage->getTitle();

		if ( $title->isSpecialPage() ||
			$title->isRedirect() ||
			!$this->isSemanticEnabledNamespace( $title ) ) {
			return false;
		}

		return true;
	}

	private function performUpdate() {
		$cachedFactbox = ApplicationFactory::getInstance()->newFactboxFactory()->newCachedFactbox();

		$cachedFactbox->prepareFactboxContent(
			$this->outputPage,
			$this->parserOutput
		);

		return true;
	}

	private function isSemanticEnabledNamespace( Title $title ) {
		return ApplicationFactory::getInstance()->getNamespaceExaminer()->isSemanticEnabled( $title->getNamespace() );
	}


}
