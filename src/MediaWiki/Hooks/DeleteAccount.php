<?php

namespace SMW\MediaWiki\Hooks;

use SMW\Store;
use SMW\NamespaceExaminer;
use SMW\MediaWiki\HookListener;
use SMW\DIWikiPage;
use Title;
use User;

/**
 * @see https://github.com/wikimedia/mediawiki-extensions-UserMerge/blob/master/includes/MergeUser.php#L654
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DeleteAccount implements HookListener {

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var ArticleDelete
	 */
	private $articleDelete;

	/**
	 * @since 3.2
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 * @param ArticleDelete $articleDelete
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer, ArticleDelete $articleDelete ) {
		$this->namespaceExaminer = $namespaceExaminer;
		$this->articleDelete = $articleDelete;
	}

	/**
	 * @since 3.2
	 *
	 * @param User|string $user
	 */
	public function process( $user ) {

		if ( !$this->namespaceExaminer->isSemanticEnabled( NS_USER ) ) {
			return false;
		}

		if ( $user instanceof User ) {
			$user = $user->getName();
		}

		$this->articleDelete->setOrigin( 'DeleteAccount' );

		$this->articleDelete->process(
			Title::newFromText( $user, NS_USER )
		);

		return true;
	}

}
