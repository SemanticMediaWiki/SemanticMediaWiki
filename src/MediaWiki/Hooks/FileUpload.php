<?php

namespace SMW\MediaWiki\Hooks;

use File;
use MediaWiki\Hook\FileUploadHook;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\User\User;
use SMW\Localizer\Localizer;
use SMW\MediaWiki\Jobs\ParserDataFactory;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageCreator;
use SMW\NamespaceExaminer;
use SMW\Property\AnnotatorFactory;

/**
 * Fires when a local file upload occurs
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FileUpload
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class FileUpload implements FileUploadHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly HookContainer $hookContainer,
		private readonly PageCreator $pageCreator,
		private readonly ParserDataFactory $parserDataFactory,
		private readonly MwCollaboratorFactory $mwCollaboratorFactory,
		private readonly AnnotatorFactory $propertyAnnotatorFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onFileUpload( $file, $reupload, $hasDescription ) {
		if ( $this->canProcess( $file->getTitle() ) ) {
			$this->doProcess( $file, (bool)$reupload );
		}

		return true;
	}

	private function canProcess( $title ): bool {
		return $title !== null && $this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() );
	}

	private function doProcess( File $file, bool $reUploadStatus = false ): bool {
		$filePage = $this->makeFilePage( $file );

		// Avoid WikiPage.php: The supplied ParserOptions are not safe to cache.
		// Fix the options or set $forceParse = true.
		$forceParse = true;

		$parserData = $this->parserDataFactory->newParserData(
			$file->getTitle(),
			$filePage->getParserOutput( $this->makeCanonicalParserOptions(), null, $forceParse )
		);

		$pageInfoProvider = $this->mwCollaboratorFactory->newPageInfoProvider(
			$filePage,
			null,
			null,
			$reUploadStatus
		);

		$semanticData = $parserData->getSemanticData();
		$semanticData->setOption( 'is_fileupload', true );

		$propertyAnnotator = $this->propertyAnnotatorFactory->newNullPropertyAnnotator(
			$semanticData
		);

		$propertyAnnotator = $this->propertyAnnotatorFactory->newPredefinedPropertyAnnotator(
			$propertyAnnotator,
			$pageInfoProvider
		);

		$propertyAnnotator->addAnnotation();

		// 2.4+
		$this->hookContainer->run( 'SMW::FileUpload::BeforeUpdate', [ $filePage, $semanticData ] );

		$parserData->setOrigin( 'FileUpload' );

		$parserData->copyToParserOutput();
		$parserData->updateStore( true );

		return true;
	}

	private function makeFilePage( File $file ) {
		$filePage = $this->pageCreator->createFilePage(
			$file->getTitle()
		);

		$filePage->setFile( $file );

		return $filePage;
	}

	/**
	 * Anonymous user with default preferences and content language
	 */
	private function makeCanonicalParserOptions() {
		return ParserOptions::newFromUserAndLang(
			new User(),
			Localizer::getInstance()->getContentLanguage()
		);
	}

}
