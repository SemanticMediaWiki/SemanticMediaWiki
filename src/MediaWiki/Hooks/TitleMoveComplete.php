<?php

namespace SMW\MediaWiki\Hooks;

use Onoi\EventDispatcher\EventDispatcherAwareTrait;
use SMW\ApplicationFactory;
use SMW\NamespaceExaminer;

/**
 * TitleMoveComplete occurs whenever a request to move an article
 * is completed
 *
 * This method will be called whenever an article is moved so that
 * semantic properties are moved accordingly.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class TitleMoveComplete {

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
	 *
	 * @param $oldTitle
	 * @param $newTitle
	 * @param $user
	 * @param $oldId
	 * @param $newId
	 *
	 * @return true
	 */
	public function process( $oldTitle, $newTitle, $user, $oldId, $newId ) {

		$applicationFactory = ApplicationFactory::getInstance();

		// Delete all data for a non-enabled target NS
		if ( !$this->namespaceExaminer->isSemanticEnabled( $newTitle->getNamespace() ) || $newId == 0 ) {
			$applicationFactory->getStore()->deleteSubject(
				$oldTitle
			);
		}

		$context = [
			'context' => 'TitleMoveComplete'
		];

		foreach ( [ 'InvalidateResultCache', 'InvalidateEntityCache' ] as $event ) {
			$this->eventDispatcher->dispatch( $event, $context + [ 'title' => $oldTitle ] );
			$this->eventDispatcher->dispatch( $event, $context + [ 'title' => $newTitle ] );
		}

		return true;
	}

}
