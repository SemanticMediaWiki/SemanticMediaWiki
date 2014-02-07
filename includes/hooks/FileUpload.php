<?php

namespace SMW;

use WikiFilePage;
use File;

/**
 * Fires when a local file upload occurs
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
 *
 * @ingroup FunctionHook
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.4
 *
 * @author mwjames
 */
class FileUpload extends FunctionHook {

	/** @var File */
	protected $file = null;

	/**
	 * @since  1.9.0.4
	 *
	 * @param File $file
	 */
	public function __construct( File $file ) {
		$this->file = $file;
	}

	/**
	 * @see FunctionHook::process
	 *
	 * @since 1.9.0.4
	 *
	 * @return true
	 */
	public function process() {

		$title = $this->file->getTitle();

		$wikiPage = new WikiFilePage( $title );
		$wikiPage->setFile( $this->file );

		$contentParser = $this->withContext()->getDependencyBuilder()->newObject( 'ContentParser', array(
			'Title' => $title
		) );

		$contentParser->parse();

		$parserData = $this->withContext()->getDependencyBuilder()->newObject( 'ParserData', array(
			'Title'        => $title,
			'ParserOutput' => $contentParser->getOutput()
		) );

		$propertyAnnotator = $this->withContext()->getDependencyBuilder()->newObject( 'PredefinedPropertyAnnotator', array(
			'SemanticData' => $parserData->getSemanticData(),
			'WikiPage' => $wikiPage
		) );

		$propertyAnnotator->attach( $parserData )->addAnnotation();

		$parserData->updateStore();

		return true;
	}

}
