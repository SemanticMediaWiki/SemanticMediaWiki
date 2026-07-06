<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Permissions\Hook\TitleQuickPermissionsHook;
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
class TitleQuickPermissions implements TitleQuickPermissionsHook {

	/**
	 * @since 3.1
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly TitlePermissions $titlePermissions,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onTitleQuickPermissions( $title, $user, $action, &$errors, $doExpensiveQueries, $short ) {
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
