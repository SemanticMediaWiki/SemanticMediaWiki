<?php

namespace SMW;

use ParserOutput;
use Title;

/**
 * NewRevisionFromEditComplete hook
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Hook: NewRevisionFromEditComplete called when a revision was inserted
 * due to an edit
 *
 * Fetch additional information that is related to the saving that has just happened,
 * e.g. regarding the last edit date. In runs where this hook is not triggered, the
 * last DB entry (of MW) will be used to fill such properties.
 *
 * Called from LocalFile.php, SpecialImport.php, Article.php, Title.php
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/NewRevisionFromEditComplete
 *
 * @ingroup Hook
 */
class NewRevisionFromEditComplete extends FunctionHook {

	/** @var Parser */
	protected $wikiPage = null;

	/** @var Parser */
	protected $revision = null;

	/** @var Parser */
	protected $baseId = null;

	/** @var Parser */
	protected $user = null;

	/**
	 * @since  1.9
	 *
	 * @param WikiPage $article the article edited
	 * @param Revision $rev the new revision. Revision object
	 * @param $baseId the revision ID this was based off, if any
	 * @param User $user the revision author. User object
	 */
	public function __construct( $wikiPage, $revision, $baseId, $user ) {
		$this->wikiPage = $wikiPage;
		$this->revision = $revision;
		$this->baseId = $baseId;
		$this->user = $user;
	}

	/**
	 * @since 1.9
	 *
	 * @param ParserOutput $parserOutput
	 *
	 * @return true
	 */
	protected function performUpdate( ParserOutput $parserOutput ) {

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->getDependencyBuilder()->newObject( 'ParserData', array(
			'Title'        => $this->wikiPage->getTitle(),
			'ParserOutput' => $parserOutput
		) );

		/**
		 * @var BasePropertyAnnotator $propertyAnnotator
		 */
		$propertyAnnotator = $this->getDependencyBuilder()->newObject( 'BasePropertyAnnotator', array(
			'SemanticData' => $parserData->getData(),
		) );

		$propertyAnnotator->attach( $parserData )
			->addSpecialProperties( $this->wikiPage, $this->revision, $this->user );

		return true;
	}

	/**
	 * @see FunctionHook::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$parserOutput = $this->wikiPage->getParserOutput(
			$this->wikiPage->makeParserOptions( $this->user ),
			$this->revision->getId()
		);

		return $parserOutput instanceof ParserOutput ? $this->performUpdate( $parserOutput ) : true;
	}

}
