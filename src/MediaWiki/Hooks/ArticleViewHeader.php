<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Html\Html;
use MediaWiki\Page\Hook\ArticleViewHeaderHook;
use MediaWiki\Title\Title;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage as DIWikiPage;
use SMW\DependencyValidatorFactory;
use SMW\Localizer\Message;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\NamespaceExaminer;
use SMW\Settings;
use SMW\Store;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleViewHeader
 *
 * Note: This hook is not called on non-article pages (including edit pages) and
 * it is also not called prior to outputting the edit preview.
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleViewHeader implements ArticleViewHeaderHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly Store $store,
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly Settings $settings,
		private readonly DependencyValidatorFactory $dependencyValidatorFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onArticleViewHeader( $article, &$outputDone, &$pcache ) {
		$title = $article->getTitle();

		if ( !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return true;
		}

		$subject = DIWikiPage::newFromTitle( $title );

		$changePropagationWatchlist = array_flip(
			$this->settings->get( 'smwgChangePropagationWatchlist' ) ?: []
		);

		// Only act when `_SUBC` is maintained as watchable property
		if ( isset( $changePropagationWatchlist['_SUBC'] ) && $title->getNamespace() === NS_CATEGORY ) {
			$pcache = $this->updateCategoryTop( $title, $article->getContext()->getOutput() );
		}

		$wikiPage = $article->getPage();

		$dependencyValidator = $this->dependencyValidatorFactory->newFor(
			$wikiPage,
			$wikiPage->makeParserOptions( 'canonical' )
		);

		if ( $dependencyValidator->hasArchaicDependencies( $subject ) ) {
			$dependencyValidator->markTitle( $title );
		}

		return true;
	}

	private function updateCategoryTop( Title $title, $output ): bool {
		$message = '';

		$subject = DIWikiPage::newFromTitle(
			$title
		);

		$semanticData = $this->store->getSemanticData(
			$subject
		);

		if ( $semanticData->hasProperty( new Property( Property::TYPE_CHANGE_PROP ) ) ) {
			$severity = $this->settings->get( 'smwgChangePropagationProtection' ) ? 'error' : 'warning';

			$message .= $this->message(
				$severity,
				[
					'smw-category-change-propagation-locked-' . $severity,
					str_replace( '_', ' ', $subject->getDBKey() )
				]
			);
		}

		if ( $message === '' && ChangePropagationDispatchJob::hasPendingJobs( $subject ) ) {
			$message .= $this->message(
				'warning',
				[
					'smw-category-change-propagation-pending',
					ChangePropagationDispatchJob::getPendingJobsCount( $subject )
				]
			);
		}

		$output->addModuleStyles( [ 'mediawiki.codex.messagebox.styles' ] );
		$output->addHTML( $message );

		// No Message means `useParserCache`otherwise refresh the output to
		// display the latest update
		return $message === '';
	}

	private function message( string $type, array $message ) {
		$content = Message::get( $message, Message::PARSE, Message::USER_LANGUAGE );
		switch ( $type ) {
			case 'error':
				return Html::errorBox( $content );
			case 'warning':
				return Html::warningBox( $content );
			default:
				return Html::noticeBox( $content, '' );
		}
	}
}
