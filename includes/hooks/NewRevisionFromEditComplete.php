<?php

namespace SMW;

use ParserOutput;
use Title;

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
 * @ingroup FunctionHook
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
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

	/** @var ParserOutput */
	protected $parserOutput = null;

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
	 * @see FunctionHook::process
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return $this->retrieveParserOutput() instanceof ParserOutput ? $this->performUpdate() : true;
	}

	/**
	 * @since 1.9
	 *
	 * @return ParserOutput|null
	 */
	protected function retrieveParserOutput() {

		$editInfo = false;

		if ( $this->hasContentForEditMethod() ) {

			$content  = $this->revision->getContent();

			$editInfo = $this->wikiPage->prepareContentForEdit(
				$content,
				null,
				$this->user,
				$content->getContentHandler()->getDefaultFormat()
			);

		} else {

			$editInfo = $this->wikiPage->prepareTextForEdit(
				$this->revision->getRawText(),
				null,
				$this->user
			);

		}

		return $this->parserOutput = $editInfo ? $editInfo->output : null;
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	protected function performUpdate() {

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->withContext()->getDependencyBuilder()->newObject( 'ParserData', array(
			'Title'        => $this->wikiPage->getTitle(),
			'ParserOutput' => $this->parserOutput
		) );

		/**
		 * @var PropertyAnnotator $propertyAnnotator
		 */
		$propertyAnnotator = $this->withContext()->getDependencyBuilder()->newObject( 'PredefinedPropertyAnnotator', array(
			'SemanticData' => $parserData->getData(),
			'WikiPage' => $this->wikiPage,
			'Revision' => $this->revision,
			'User'     => $this->user
		) );

		$propertyAnnotator->attach( $parserData )->addAnnotation();

		return true;
	}

	/**
	 * @return boolean
	 */
	protected function hasContentForEditMethod() {
		return method_exists( 'WikiPage', 'prepareContentForEdit' );
	}

}
