<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Linker\LinkTarget;
use MediaWiki\User\UserIdentity;
use Onoi\EventDispatcher\EventDispatcherAwareTrait;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\NamespaceExaminer;
use SMW\MediaWiki\HookListener;

/**
 * PageMoveComplete occurs whenever a request to move an article
 * is completed
 *
 * This method will be called whenever an article is moved so that
 * semantic properties are moved accordingly.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageMoveComplete
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PageMoveComplete implements HookListener {

	use EventDispatcherAwareTrait;

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @since  1.9
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer ) {
		$this->namespaceExaminer = $namespaceExaminer;
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

		$applicationFactory = ApplicationFactory::getInstance();

		// Delete all data for a non-enabled target NS
		if ( !$this->namespaceExaminer->isSemanticEnabled( $newTitle->getNamespace() ) || $newId == 0 ) {
			$applicationFactory->getStore()->deleteSubject(
				$oldTitle
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
