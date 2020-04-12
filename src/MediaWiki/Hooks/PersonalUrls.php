<?php

namespace SMW\MediaWiki\Hooks;

use SkinTemplate;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\HookListener;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Preference\PreferenceExaminer;
use SMW\GroupPermissions;
use SMW\OptionsAwareTrait;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PersonalUrls
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class PersonalUrls implements HookListener {

	use OptionsAwareTrait;

	/**
	 * @var SkinTemplate
	 */
	private $skin;

	/**
	 * @var JobQueue
	 */
	private $jobQueue;

	/**
	 * @var PermissionExaminer
	 */
	private $permissionExaminer;

	/**
	 * @var PreferenceExaminer
	 */
	private $preferenceExaminer;

	/**
	 * @since 3.0
	 *
	 * @param SkinTemplate $skin
	 * @param JobQueue $jobQueue
	 * @param PermissionExaminer $permissionExaminer
	 * @param PreferenceExaminer $preferenceExaminer
	 */
	public function __construct( SkinTemplate $skin, JobQueue $jobQueue, PermissionExaminer $permissionExaminer, PreferenceExaminer $preferenceExaminer ) {
		$this->skin = $skin;
		$this->jobQueue = $jobQueue;
		$this->permissionExaminer = $permissionExaminer;
		$this->preferenceExaminer = $preferenceExaminer;
	}

	/**
	 * @since 3.0
	 *
	 * @param array &$personalUrls
	 *
	 * @return true
	 */
	public function process( array &$personalUrls ) {

		$watchlist = $this->getOption( 'smwgJobQueueWatchlist', [] );

		if (
			$this->preferenceExaminer->hasPreferenceOf( GetPreferences::VIEW_JOBQUEUE_WATCHLIST ) &&
			$this->permissionExaminer->hasPermissionOf( GroupPermissions::VIEW_JOBQUEUE_WATCHLIST ) &&
			$watchlist !== [] ) {
			$personalUrls = $this->getJobQueueWatchlist( $watchlist, $personalUrls );
		}

		return true;
	}

	private function getJobQueueWatchlist( $watchlist, $personalUrls ) {

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

		$out = $this->skin->getOutput();
		$personalUrl = [];

		$out->addModules( 'ext.smw.personal' );
		$out->addJsConfigVars( 'smwgJobQueueWatchlist', $queue );

		$personalUrl['smw-jobqueue-watchlist'] = [
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
	private function splice( $array, $values, $offset ) {
		return array_slice( $array, 0, $offset, true ) + $values + array_slice( $array, $offset, NULL, true );
	}

	private function humanReadable( $num, $decimals = 0 ) {

		if ( $num < 1000 ) {
			$num = number_format( $num );
		} else if ( $num < 1000000) {
			$num = number_format( $num / 1000, $decimals ) . 'K';
		} else {
			$num = number_format( $num / 1000000, $decimals ) . 'M';
		}

		return $num;
	}

}
