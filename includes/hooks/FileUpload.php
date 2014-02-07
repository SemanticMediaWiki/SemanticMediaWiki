<?php

namespace SMW;

use WikiFilePage;
use ParserOutput;
use File;

/**
 * Fires when a local file upload occurs
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
 *
 * @ingroup FunctionHook
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class FileUpload extends FunctionHook {

	/** @var File */
	protected $file = null;

	/**
	 * @since  1.9.0.3
	 *
	 * @param File $file
	 */
	public function __construct( File $file, $reupload = false ) {
		$this->file = $file;
		$this->reupload = $reupload;
	}

	/**
	 * @see FunctionHook::process
	 *
	 * @since 1.9.0.3
	 *
	 * @return true
	 */
	public function process() {
		return $this->performUpdate();
	}

	protected function performUpdate() {

		/**
		 * @var ParserData $parserData
		 */
		$parserData = $this->withContext()->getDependencyBuilder()->newObject( 'ParserData', array(
			'Title'        => $this->file->getTitle(),
			'ParserOutput' => $this->makeParserOutput()
		) );

		/**
		 * @var PredefinedPropertyAnnotator $propertyAnnotator
		 */
		$propertyAnnotator = $this->withContext()->getDependencyBuilder()->newObject( 'PredefinedPropertyAnnotator', array(
			'SemanticData' => $parserData->getSemanticData(),
			'WikiPage'     => $this->makeFilePage()
		) );

		$propertyAnnotator->attach( $parserData )->addAnnotation();

		$parserData->updateStore();

		return true;
	}

	protected function makeParserOutput() {

		/**
		 * @var ContentParser $contentParser
		 */
		$contentParser = $this->withContext()->getDependencyBuilder()->newObject( 'ContentParser', array(
			'Title' => $this->file->getTitle()
		) );

		$contentParser->parse();

		return $contentParser->getOutput();
	}

	protected function makeFilePage() {

		$wikiPage = new WikiFilePage( $this->file->getTitle() );
		$wikiPage->setFile( $this->file );

		$wikiPage->smwFileReUploadStatus = $this->reupload;

		return $wikiPage;
	}

}
