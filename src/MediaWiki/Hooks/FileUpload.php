<?php

namespace SMW\MediaWiki\Hooks;

use File;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\User\User;
use SMW\Localizer\Localizer;
use SMW\MediaWiki\HookListener;
use SMW\NamespaceExaminer;
use SMW\Services\ServicesFactory as ApplicationFactory;

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
class FileUpload implements HookListener {

	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly HookContainer $hookContainer,
	) {
	}

	/**
	 * @since 3.0
	 *
	 * @param File $file
	 * @param bool $reUploadStatus
	 *
	 * @return true
	 */
	public function process( File $file, $reUploadStatus = false ): bool {
		if ( $this->canProcess( $file->getTitle() ) ) {
			$this->doProcess( $file, $reUploadStatus );
		}

		return true;
	}

	private function canProcess( $title ): bool {
		return $title !== null && $this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() );
	}

	private function doProcess( File $file, $reUploadStatus = false ): bool {
		$applicationFactory = ApplicationFactory::getInstance();
		$filePage = $this->makeFilePage( $file );

		// Avoid WikiPage.php: The supplied ParserOptions are not safe to cache.
		// Fix the options or set $forceParse = true.
		$forceParse = true;

		$parserData = $applicationFactory->newParserData(
			$file->getTitle(),
			$filePage->getParserOutput( $this->makeCanonicalParserOptions(), null, $forceParse )
		);

		$pageInfoProvider = $applicationFactory->newMwCollaboratorFactory()->newPageInfoProvider(
			$filePage,
			null,
			null,
			$reUploadStatus
		);

		$propertyAnnotatorFactory = $applicationFactory->singleton( 'PropertyAnnotatorFactory' );

		$semanticData = $parserData->getSemanticData();
		$semanticData->setOption( 'is_fileupload', true );

		$propertyAnnotator = $propertyAnnotatorFactory->newNullPropertyAnnotator(
			$semanticData
		);

		$propertyAnnotator = $propertyAnnotatorFactory->newPredefinedPropertyAnnotator(
			$propertyAnnotator,
			$pageInfoProvider
		);

		$propertyAnnotator->addAnnotation();

		// 2.4+
		$this->hookContainer->run( 'SMW::FileUpload::BeforeUpdate', [ $filePage, $semanticData ] );

		$parserData->setOrigin( 'FileUpload' );

		$parserData->pushSemanticDataToParserOutput();
		$parserData->updateStore( true );

		return true;
	}

	private function makeFilePage( File $file ) {
		$filePage = ApplicationFactory::getInstance()->newPageCreator()->createFilePage(
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
