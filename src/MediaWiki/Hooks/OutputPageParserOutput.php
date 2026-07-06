<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Output\Hook\OutputPageParserOutputHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use Psr\Log\LoggerInterface;
use SMW\Factbox\FactboxFactory;
use SMW\Factbox\FactboxText;
use SMW\MediaWiki\IndicatorRegistryFactory;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\PermissionManager;
use SMW\MediaWiki\PostProcHandlerFactory;
use SMW\NamespaceExaminer;
use SMW\Parser\InTextAnnotationParserFactory;
use SMW\ParserData;

/**
 * OutputPageParserOutput hook is called after parse, before the HTML is
 * added to the output
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/OutputPageParserOutput
 *
 * @note This hook copies SMW's custom data from the given ParserOutput object to
 * the given OutputPage object, since otherwise it is not possible to access
 * it later on to build a Factbox.
 *
 * @ingroup FunctionHook
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class OutputPageParserOutput implements OutputPageParserOutputHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly FactboxText $factboxText,
		private readonly FactboxFactory $factboxFactory,
		private readonly UserOptionsLookup $userOptionsLookup,
		private readonly PermissionManager $permissionManager,
		private readonly IndicatorRegistryFactory $indicatorRegistryFactory,
		private readonly PostProcHandlerFactory $postProcHandlerFactory,
		private readonly InTextAnnotationParserFactory $inTextAnnotationParserFactory,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onOutputPageParserOutput( $outputPage, $parserOutput ): void {
		$title = $outputPage->getTitle();

		if ( $title === null || $title->isSpecialPage() || $title->isRedirect() ) {
			return;
		}

		if ( !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return;
		}

		$context = $outputPage->getContext();
		$request = $context->getRequest();
		$user = $outputPage->getUser();

		$permissionExaminer = new PermissionExaminer( $this->permissionManager, $user );

		$indicatorRegistry = $this->indicatorRegistryFactory->newFor(
			(bool)$this->userOptionsLookup->getOption(
				$user,
				GetPreferences::SHOW_ENTITY_ISSUE_PANEL,
				false
			)
		);

		$options = [
			'action' => $request->getVal( 'action' ),
			'diff' => $request->getVal( 'diff' ),
			'isRTL' => $context->getLanguage()->isRTL(),
			'uselang' => $request->getVal( 'uselang' ),
		];

		if (
			$title->exists() &&
			$indicatorRegistry->hasIndicator( $title, $permissionExaminer, $options ) ) {
			$indicatorRegistry->attachIndicators( $outputPage );
		}

		$this->addFactbox( $outputPage, $parserOutput );
		$this->addPostProc( $title, $outputPage, $parserOutput );
	}

	private function addPostProc( Title $title, OutputPage $outputPage, ParserOutput $parserOutput ): ?string {
		$request = $outputPage->getContext()->getRequest();

		if ( in_array( $request->getVal( 'action' ), [ 'delete', 'purge', 'protect', 'unprotect', 'history', 'edit', 'formedit' ] ) ) {
			return '';
		}

		$postProcHandler = $this->postProcHandlerFactory->newFor( $parserOutput );

		$html = $postProcHandler->getHtml(
			$title,
			$request
		);

		if ( $html !== '' ) {
			$outputPage->addModules( $postProcHandler->getModules() );
			$outputPage->addHtml( $html );
		}

		return null;
	}

	protected function addFactbox( OutputPage $outputPage, ParserOutput $parserOutput ): string|bool {
		$request = $outputPage->getContext()->getRequest();

		if ( $this->factboxText->hasText() && $request->getCheck( 'wpPreview' ) ) {
			return '';
		}

		$cachedFactbox = $this->factboxFactory->newCachedFactbox();

		$cachedFactbox->prepare(
			$outputPage,
			$this->getParserOutput( $outputPage, $parserOutput )
		);

		// #4146
		//
		// Due to how MW started to move the `mw-data-after-content` out of the
		// `bodyContent` we need a way to distinguish content from a top level
		// to apply additional CSS rules
		if ( $this->factboxText->hasNonEmptyText() ) {
			$outputPage->addBodyClasses( 'smw-factbox-view' );
		}

		return true;
	}

	protected function getParserOutput( OutputPage $outputPage, ParserOutput $parserOutput ) {
		if ( $outputPage->getContext()->getRequest()->getInt( 'oldid' ) ) {

			$text = $parserOutput->getContentHolderText();

			$parserData = new ParserData( $outputPage->getTitle(), $parserOutput );
			$parserData->setLogger( $this->logger );

			$inTextAnnotationParser = $this->inTextAnnotationParserFactory->newFor( $parserData );
			$inTextAnnotationParser->parse( $text );

			return $parserData->getOutput();
		}

		return $parserOutput;
	}

}
