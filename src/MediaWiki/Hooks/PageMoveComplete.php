<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Linker\LinkTarget;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use SMW\EventDispatcher\EventDispatcherAwareTrait;
use SMW\MediaWiki\HookListener;
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
class PageMoveComplete implements HookListener {

	use EventDispatcherAwareTrait;

	/**
	 * @since  1.9
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly Store $store,
	) {
	}

	/**
	 * @since 1.9
	 */
	public function process(
		LinkTarget $oldTitle,
		LinkTarget $newTitle,
		UserIdentity $user,
		int $oldId,
		int $newId
	): bool {
		// Delete all data for a non-enabled target NS
		if ( !$this->namespaceExaminer->isSemanticEnabled( $newTitle->getNamespace() ) || $newId == 0 ) {
			$this->store->deleteSubject(
				Title::newFromLinkTarget( $oldTitle )
			);
		}

		$context = [
			'context' => 'PageMoveComplete'
		];

		foreach ( [ 'InvalidateResultCache', 'InvalidateEntityCache' ] as $event ) {
			$this->eventDispatcher->dispatch( $event, $context + [ 'title' => $oldTitle ] );
			$this->eventDispatcher->dispatch( $event, $context + [ 'title' => $newTitle ] );
		}

		return true;
	}

}
