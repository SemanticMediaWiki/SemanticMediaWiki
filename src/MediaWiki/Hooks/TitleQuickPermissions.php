<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Title\Title;
use MediaWiki\User\User;
use SMW\MediaWiki\HookListener;
use SMW\MediaWiki\Permission\TitlePermissions;
use SMW\NamespaceExaminer;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleQuickPermissions
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TitleQuickPermissions implements HookListener {

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly TitlePermissions $titlePermissions,
	) {
	}

	/**
	 * @since 3.1
	 */
	public function process( Title $title, User $user, string $action, array &$errors ): bool {
		if ( !$this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) ) {
			return true;
		}

		$ret = $this->titlePermissions->checkPermissionFor(
			$title,
			$user,
			$action
		);

		$errors = $this->titlePermissions->getErrors();

		return $ret;
	}

}
