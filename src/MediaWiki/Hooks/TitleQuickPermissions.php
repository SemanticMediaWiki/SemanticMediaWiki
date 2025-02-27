<?php

namespace SMW\MediaWiki\Hooks;

use SMW\MediaWiki\HookListener;
use SMW\MediaWiki\Permission\TitlePermissions;
use SMW\NamespaceExaminer;
use Title;

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
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var TitlePermissions
	 */
	private $titlePermissions;

	/**
	 * @since 3.1
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 * @param TitlePermissions $titlePermissions
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer, TitlePermissions $titlePermissions ) {
		$this->namespaceExaminer = $namespaceExaminer;
		$this->titlePermissions = $titlePermissions;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param $user
	 * @param $action
	 * @param &$errors
	 *
	 * @return bool
	 */
	public function process( Title $title, $user, $action, &$errors ) {
		if ( $this->namespaceExaminer->isSemanticEnabled( $title->getNamespace() ) === false ) {
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
