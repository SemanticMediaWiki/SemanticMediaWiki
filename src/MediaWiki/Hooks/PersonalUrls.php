<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\User\Options\UserOptionsLookup;
use SkinTemplate;
use SMW\GroupPermissions;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\PermissionManager;
use SMW\Settings;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PersonalUrls {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly JobQueue $jobQueue,
		private readonly UserOptionsLookup $userOptionsLookup,
		private readonly Settings $settings,
		private readonly PermissionManager $permissionManager,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onPersonalUrls( array &$personal_urls, $title, SkinTemplate $skinTemplate ): bool {
		$user = $skinTemplate->getUser();
		$permissionExaminer = new PermissionExaminer( $this->permissionManager, $user );
		$watchlist = $this->settings->get( 'smwgJobQueueWatchlist' ) ?: [];

		if (
			$this->userOptionsLookup->getOption( $user, GetPreferences::VIEW_JOBQUEUE_WATCHLIST, false ) &&
			$permissionExaminer->hasPermissionOf( GroupPermissions::VIEW_JOBQUEUE_WATCHLIST ) &&
			$watchlist !== [] ) {
			$personal_urls = $this->getJobQueueWatchlist( $skinTemplate, $watchlist, $personal_urls );
		}

		return true;
	}

	private function getJobQueueWatchlist( SkinTemplate $skin, $watchlist, array $personalUrls ): array {
		$queue = [];

		foreach ( $watchlist as $job ) {
			$size = $this->jobQueue->getQueueSize( $job );

			if ( $size > 0 ) {
				$queue[$job] = $size;
			}
		}

		arsort( $queue );

		foreach ( $queue as $job => $size ) {
			$queue[$job] = $this->humanReadable( $size );
		}

		$out = $skin->getOutput();
		$personalUrl = [];

		$out->addModules( 'ext.smw.personal' );
		$out->addJsConfigVars( 'smwgJobQueueWatchlist', $queue );

		$personalUrl['smw-jobqueue-watchlist'] = [
			// @phan-suppress-next-line PhanImpossibleTypeComparison
			'text'   => 'ⅉ [ ' . ( $queue === [] ? '0' : implode( ' | ', $queue ) ) . ' ]',
			'href'   => '#',
			'class'  => 'smw-personal-jobqueue-watchlist is-disabled',
			'active' => true
		];

		$keys = array_keys( $personalUrls );

		// Insert the link before the watchlist
		return $this->splice(
			$personalUrls,
			$personalUrl,
			array_search( 'watchlist', $keys )
		);
	}

	// https://stackoverflow.com/questions/1783089/array-splice-for-associative-arrays
	private function splice( array $array, array $values, int|bool $offset ): array {
		return array_slice( $array, 0, $offset, true ) + $values + array_slice( $array, $offset, null, true );
	}

	private function humanReadable( $num, $decimals = 0 ): string {
		if ( $num < 1000 ) {
			$num = number_format( $num );
		} elseif ( $num < 1000000 ) {
			$num = number_format( $num / 1000, $decimals ) . 'K';
		} else {
			$num = number_format( $num / 1000000, $decimals ) . 'M';
		}

		return $num;
	}

}
