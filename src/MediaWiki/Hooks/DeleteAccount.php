<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;
use SMW\NamespaceExaminer;

/**
 * @see https://github.com/wikimedia/mediawiki-extensions-UserMerge/blob/master/includes/MergeUser.php#L654
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class DeleteAccount {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly ArticleDelete $articleDelete,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onDeleteAccount( $user ): bool {
		if ( !$this->namespaceExaminer->isSemanticEnabled( NS_USER ) ) {
			return false;
		}

		if ( $user instanceof User ) {
			$user = $user->getName();
		}

		$this->articleDelete->setOrigin( 'DeleteAccount' );

		$this->articleDelete->scheduleDeleteFor(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $user, NS_USER )
		);

		return true;
	}

}
