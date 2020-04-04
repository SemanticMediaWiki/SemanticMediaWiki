<?php

namespace SMW\Permission;

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

	/**
	 * @since 3.2
	 *
	 * @param array &$vars
	 */
	public function initPermissions( &$vars ) {

		$groups = [
			'smwadministrator' => $this->forAdminRole(),
			'smwcurator' => $this->forCuratorRole()
		];

		/**
		 * @see HookDispatcher::onGroupPermissionsBeforeInitializationComplete
		 */
		$this->hookDispatcher->onGroupPermissionsBeforeInitializationComplete( $groups );

		foreach ( $groups as $group => $rights ) {

			// Rights
			foreach ( $rights as $right ) {
				$vars['wgAvailableRights'][] = $right;
			}

			if ( !isset( $vars['wgGroupPermissions'][$group] ) ) {
				$vars['wgGroupPermissions'][$group] = $rights;
			}
		}
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
			self::VIEW_ENTITY_ASSOCIATEDREVISIONMISMATCH => true
		];
	}

}
