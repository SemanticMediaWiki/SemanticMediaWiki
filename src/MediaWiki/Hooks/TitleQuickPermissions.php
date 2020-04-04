<?php

namespace SMW\MediaWiki\Hooks;

use SMW\MediaWiki\PermissionsExaminer;
use SMW\NamespaceExaminer;
use Title;
use SMW\MediaWiki\HookListener;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleQuickPermissions
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TitleQuickPermissions implements HookListener {

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var PermissionsExaminer
	 */
	private $permissionsExaminer;

	/**
	 * @since 3.1
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 * @param PermissionsExaminer $permissionsExaminer
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer, PermissionsExaminer $permissionsExaminer ) {
		$this->namespaceExaminer = $namespaceExaminer;
		$this->permissionsExaminer = $permissionsExaminer;
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

		$ret = $this->permissionsExaminer->checkPermissionFor(
			$title,
			$user,
			$action
		);

		$errors = $this->permissionsExaminer->getErrors();

		return $ret;
	}

}
