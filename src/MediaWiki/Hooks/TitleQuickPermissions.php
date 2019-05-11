<?php

namespace SMW\MediaWiki\Hooks;

use SMW\MediaWiki\PermissionManager;
use SMW\NamespaceExaminer;
use Title;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleQuickPermissions
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TitleQuickPermissions extends HookHandler {

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var PermissionManager
	 */
	private $permissionManager;

	/**
	 * @since 3.1
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 * @param PermissionManager $permissionManager
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer, PermissionManager $permissionManager ) {
		$this->namespaceExaminer = $namespaceExaminer;
		$this->permissionManager = $permissionManager;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param $user
	 * @param $action
	 * @param &$errors
	 *
	 * @return boolean
	 */
	public function process( Title $title, $user, $action, &$errors ) {

		if ( $this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) === false ) {
			return true;
		}

		$ret = $this->permissionManager->checkQuickPermission(
			$title,
			$user,
			$action,
			$errors
		);

		return $ret;
	}

}
