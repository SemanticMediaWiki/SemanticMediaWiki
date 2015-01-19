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
class ResourceLoaderTestModules {

	/**
	 * @var ResourceLoader
	 */
	private $resourceLoader;

	/**
	 * @var array
	 */
	private $testModules;

	/**
	 * @var string
	 */
	private $installPath;

	/**
	 * @var string
	 */
	private $basePath;

	/**
	 * @since  2.0
	 *
	 * @param  ResourceLoader $resourceLoader object
	 * @param  array $testModules array of JavaScript testing modules
	 * @param  string $basePath
	 * @param  string $installPath
	 */
	public function __construct( ResourceLoader &$resourceLoader, array &$testModules, $basePath, $installPath ) {
		$this->resourceLoader = $resourceLoader;
		$this->testModules =& $testModules;
		$this->basePath = $basePath;
		$this->installPath = $installPath;
	}

	/**
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function process() {

		$this->testModules['qunit']['ext.smw.tests'] = array(
			'scripts' => array(
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
			),
			'dependencies' => array(
				'ext.smw',
				'ext.smw.tooltip',
				'ext.smw.query',
				'ext.smw.data',
				'ext.smw.api'
			),
			'position' => 'top',
			'localBasePath' => $this->basePath,
			'remoteExtPath' => '..' . substr( $this->basePath, strlen( $this->installPath ) ),
		);

		return true;
	}

}
