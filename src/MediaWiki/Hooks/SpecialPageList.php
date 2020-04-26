<?php

namespace SMW\MediaWiki\Hooks;

use Page;
use SMW\MediaWiki\PageFactory;
use SMW\Store;
use Title;
use SMW\MediaWiki\HookListener;
use SMW\OptionsAwareTrait;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialPageList
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SpecialPageList implements HookListener {

	use OptionsAwareTrait;

	/**
	 * @since 3.1
	 *
	 * @param array &$specialPages
	 */
	public function process( array &$specialPages ) {

		if ( $this->getOption( 'SMW_EXTENSION_LOADED' ) === false ) {
			return;
		}

		$specials = [
			'ExportRDF' => [
				'page' => 'SMWSpecialOWLExport'
			],
			'SMWAdmin' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialAdmin'
			],
			'PendingTaskList' => [
				'page' => \SMW\MediaWiki\Specials\SpecialPendingTaskList::class
			],
			'Ask' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialAsk'
			],
			'Browse' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialBrowse'
			],
			'Concepts' => [
				'page' => 'SMW\SpecialConcepts'
			],
			'PageProperty' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialPageProperty'
			],
			'SearchByProperty' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialSearchByProperty'
			],
			'PropertyLabelSimilarity' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialPropertyLabelSimilarity'
			],
			'ProcessingErrorList' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialProcessingErrorList'
			],
			'MissingRedirectAnnotations' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialMissingRedirectAnnotations'
			],
			'ConstraintErrorList' => [
				'page' => \SMW\MediaWiki\Specials\SpecialConstraintErrorList::class
			],
			'Types' => [
				'page' => 'SMWSpecialTypes'
			],
			'URIResolver' => [
				'page' => 'SMW\MediaWiki\Specials\SpecialURIResolver'
			],
			'Properties' => [
				'page' => 'SMW\SpecialProperties'
			],
			'UnusedProperties' => [
				'page' => 'SMW\SpecialUnusedProperties'
			],
			'WantedProperties' => [
				'page' => 'SMW\SpecialWantedProperties'
			]
		];

		// Register data
		foreach ( $specials as $special => $page ) {
			$specialPages[$special] = $page['page'];
		}
	}

}
