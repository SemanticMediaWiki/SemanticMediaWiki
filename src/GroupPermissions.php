<?php

namespace SMW;

use SMW\MediaWiki\HookDispatcherAwareTrait;

/**
 * Administrative class to manage the rights and roles in connection with
 * Semantic MediaWiki.
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class GroupPermissions {

	use HookDispatcherAwareTrait;

	const VIEW_JOBQUEUE_WATCHLIST = 'smw-viewjobqueuewatchlist';
	const VIEW_ENTITY_ASSOCIATEDREVISIONMISMATCH = 'smw-viewentityassociatedrevisionmismatch';
	const VIEW_EDITPAGE_INFO = 'smw-vieweditpageinfo';

	/**
	 * @since 3.2
	 *
	 * @param array $vars
	 *
	 * @return array $newVars
	 */
	public function initPermissions( $vars ) {
		$newVars = [];

		$groups = [
			'smwadministrator' => $this->forAdminRole(),
			'smwcurator' => $this->forCuratorRole(),
			'smweditor' => $this->forEditorRole(),
			'user' => $this->forDefaultUserRole()
		];

		/**
		 * @see HookDispatcher::onGroupPermissionsBeforeInitializationComplete
		 */
		$this->hookDispatcher->onGroupPermissionsBeforeInitializationComplete( $groups );

		foreach ( $groups as $group => $rights ) {

			// Rights
			foreach ( array_keys( $rights ) as $right ) {
				$newVars['wgAvailableRights'][] = $right;
			}

			if ( !isset( $vars['wgGroupPermissions'][$group] ) ) {
				$vars['wgGroupPermissions'][$group] = [];
			}

			$newVars['wgGroupPermissions'][$group] = array_merge( $rights, $vars['wgGroupPermissions'][$group] );
		}

		return $newVars;
	}

	private function forAdminRole() {
		return [
			'smw-admin' => true
		];
	}

	private function forCuratorRole() {
		return [
			'smw-patternedit' => true,
			'smw-schemaedit' => true,
			'smw-pageedit' => true,
			self::VIEW_JOBQUEUE_WATCHLIST => true,
			self::VIEW_ENTITY_ASSOCIATEDREVISIONMISMATCH => true,
			self::VIEW_EDITPAGE_INFO => true
		];
	}

	private function forEditorRole() {
		return [
			self::VIEW_EDITPAGE_INFO => true
		];
	}

	private function forDefaultUserRole() {
		return [
			self::VIEW_EDITPAGE_INFO => true
		];
	}

}
