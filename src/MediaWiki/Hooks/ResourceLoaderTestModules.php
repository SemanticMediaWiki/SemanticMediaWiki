<?php

namespace SMW\MediaWiki\Hooks;

use ResourceLoader;

/**
 * Add new JavaScript/QUnit testing modules
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderTestModules
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ResourceLoaderTestModules extends HookHandler {

	/**
	 * @var ResourceLoader
	 */
	private $resourceLoader;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var string
	 */
	private $ip;

	/**
	 * @since 2.0
	 *
	 * @param ResourceLoader $resourceLoader object
	 * @param string $path
	 * @param string $ip
	 */
	public function __construct( ResourceLoader &$resourceLoader, $path = '', $ip = '' ) {
		$this->resourceLoader = $resourceLoader;
		$this->path = $path;
		$this->ip = $ip;
	}

	/**
	 * @since 1.9
	 *
	 * @param array &$testModules
	 *
	 * @return boolean
	 */
	public function process( array &$testModules ) {

		$testModules['qunit']['ext.smw.tests'] = [
			'scripts' => [
				'tests/qunit/smw/ext.smw.test.js',
				'tests/qunit/smw/util/ext.smw.util.tooltip.test.js',

				// dataItem tests
				'tests/qunit/smw/data/ext.smw.dataItem.wikiPage.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.uri.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.time.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.property.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.unknown.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.number.test.js',
				'tests/qunit/smw/data/ext.smw.dataItem.text.test.js',

				// dataValues
				'tests/qunit/smw/data/ext.smw.dataValue.quantity.test.js',

				// Api / Query
				'tests/qunit/smw/data/ext.smw.data.test.js',
				'tests/qunit/smw/api/ext.smw.api.test.js',
				'tests/qunit/smw/query/ext.smw.query.test.js',
			],
			'dependencies' => [
				'ext.smw',
				'ext.smw.tooltip',
				'ext.smw.query',
				'ext.smw.data',
				'ext.smw.api'
			],
			'position' => 'top',
			'localBasePath' => $this->path,
			'remoteExtPath' => 'SemanticMediaWiki',
		];

		return true;
	}

}
