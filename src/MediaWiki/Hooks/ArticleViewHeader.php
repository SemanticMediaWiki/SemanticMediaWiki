<?php

namespace SMW\MediaWiki\Hooks;

use Html;
use Page;
use SMW\DependencyValidator;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\HookListener;
use SMW\MediaWiki\Jobs\ChangePropagationDispatchJob;
use SMW\Message;
use SMW\NamespaceExaminer;
use SMW\OptionsAwareTrait;
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
class ArticleViewHeader implements HookListener {

	use OptionsAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var DependencyValidator
	 */
	private $dependencyValidator;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 * @param NamespaceExaminer $namespaceExaminer
	 * @param DependencyValidator $dependencyValidator
	 */
	public function __construct( Store $store, NamespaceExaminer $namespaceExaminer, DependencyValidator $dependencyValidator ) {
		$this->store = $store;
		$this->namespaceExaminer = $namespaceExaminer;
		$this->dependencyValidator = $dependencyValidator;
	}

	/**
	 * @since 3.0
	 *
	 * @param Page $page
	 * @param bool &$outputDone
	 * @param bool &$useParserCache
	 *
	 * @return bool
	 */
	public function process( Page $page, &$outputDone, &$useParserCache ) {
		$title = $page->getTitle();

		if ( !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return true;
		}

		$subject = DIWikiPage::newFromTitle( $title );

		$changePropagationWatchlist = array_flip(
			$this->getOption( 'smwgChangePropagationWatchlist', [] )
		);

		// Only act when `_SUBC` is maintained as watchable property
		if ( isset( $changePropagationWatchlist['_SUBC'] ) && $title->getNamespace() === NS_CATEGORY ) {
			$useParserCache = $this->updateCategoryTop( $title, $page->getContext()->getOutput() );
		}

		if ( $this->dependencyValidator->hasArchaicDependencies( $subject ) ) {
			$this->dependencyValidator->markTitle( $title );
			// Disable the parser cache even before `RejectParserCacheValue` comes into play
			$useParserCache = false;
		}

		return true;
	}

	private function updateCategoryTop( $title, $output ) {
		$message = '';

		$subject = DIWikiPage::newFromTitle(
			$title
		);

		$semanticData = $this->store->getSemanticData(
			$subject
		);

		if ( $semanticData->hasProperty( new DIProperty( DIProperty::TYPE_CHANGE_PROP ) ) ) {
			$severity = $this->getOption( 'smwgChangePropagationProtection', true ) ? 'error' : 'warning';

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

		$output->addHTML( $message );

		// No Message means `useParserCache`otherwise refresh the output to
		// display the latest update
		return $message === '';
	}

	private function message( $type, array $message ) {
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
