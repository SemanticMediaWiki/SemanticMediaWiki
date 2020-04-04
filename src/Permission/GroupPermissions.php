<?php

namespace SMW\Permission;

use SMWQuery as Query;
use SMWQueryResult as QueryResult;

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

	const JOBQUEUE_WATCHLIST = 'smw-jobqueuewatchlist';

	/**
	 * @since 3.2
	 *
	 * @param array &$vars
	 */
	public function initPermissions( &$vars ) {

		$roles = [
			'smwadministrator' => $this->forAdminRole(),
			'smwcurator' => $this->forCuratorRole()
		];

		foreach ( $roles as $role => $rights ) {

			// Rights
			foreach ($rights as $right ) {
				$vars['wgAvailableRights'][] = $right;
			}

			if ( !isset( $vars['wgGroupPermissions'][$role] ) ) {
				$vars['wgGroupPermissions'][$role] = $rights;
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
			self::JOBQUEUE_WATCHLIST => true
		];
	}

}
