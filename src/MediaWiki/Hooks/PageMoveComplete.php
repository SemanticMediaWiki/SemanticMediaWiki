<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\PageMoveCompleteHook;
use MediaWiki\Title\Title;
use SMW\EventDispatcher\EventDispatcher;
use SMW\NamespaceExaminer;
use SMW\Store;

/**
 * PageMoveComplete occurs whenever a request to move an article
 * is completed
 *
 * This method will be called whenever an article is moved so that
 * semantic properties are moved accordingly.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageMoveComplete
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class PageMoveComplete implements PageMoveCompleteHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly Store $store,
		private readonly EventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onPageMoveComplete( $old, $new, $user, $pageid, $redirid, $reason, $revision ) {
		// Delete all data for a non-enabled target NS, or when the move
		// did not leave a redirect behind ($redirid is 0 in that case).
		if ( !$this->namespaceExaminer->isSemanticEnabled( $new->getNamespace() ) || $redirid == 0 ) {
			$this->store->deleteSubject(
				Title::newFromLinkTarget( $old )
			);
		}

		$context = [
			'context' => 'PageMoveComplete'
		];

		foreach ( [ 'InvalidateResultCache', 'InvalidateEntityCache' ] as $event ) {
			$this->eventDispatcher->dispatch( $event, $context + [ 'title' => $old ] );
			$this->eventDispatcher->dispatch( $event, $context + [ 'title' => $new ] );
		}

		return true;
	}

}
