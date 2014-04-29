<?php

namespace SMW\Tests\Cache;

use SMW\Cache\MessageCache;

use Language;
use HashBagOStuff;

/**
 * @uses \SMW\Cache\MessageCache
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-unit
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class MessageCacheTest extends \PHPUnit_Framework_TestCase {

	protected $cacheId = 'smw:foo';
	protected $smwgCacheType = null;

	protected function setUp() {
		parent::setUp();

		$this->smwgCacheType = $GLOBALS['smwgCacheType'];
		$GLOBALS['smwgCacheType'] = 'hash';
	}

	protected function tearDown() {
		$GLOBALS['smwgCacheType'] = $this->smwgCacheType;
		MessageCache::clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$language = $this->getMockBuilder( 'Language' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Cache\MessageCache',
			new MessageCache( $language )
		);
	}

	public function testAccessibilityOnEmptyCache() {

		$instance = new MessageCache(
			Language::factory( 'en' ),
			10001
		);

		$instance->setCache( new HashBagOStuff );
		$this->assertInternalType( 'string', $instance->get( 'foo' ) );
	}

	public function testUnkownMessageWithIncrementalCacheUpdate() {

		$cache = new HashBagOStuff();

		$instance = $this->acquireMockedInstanceWith( 1000 );
		$instance->setCache( $cache );

		$this->assertFalse( $cache->get( $this->cacheId ) );

		$this->assertInternalType( 'string', $instance->get( 'smw-desc' ) );
		$this->assertInternalType( 'array', $cache->get( $this->cacheId ) );

		$this->assertArrayHasKey( 'touched', $cache->get( $this->cacheId ) );
		$this->assertArrayHasKey( 'messages', $cache->get( $this->cacheId ) );
	}

	public function testGetMessageFromCache() {

		$cache = new HashBagOStuff();

		$cacheTimeOffset = 9999;

		$presetCached = array(
			'touched'  => 1000 . $cacheTimeOffset,
			'messages' => array( 'foo' => 'bar' )
		);

		$cache->set( $this->cacheId, $presetCached );

		$instance = $this->acquireMockedInstanceWith( 1000, $cacheTimeOffset );
		$instance->setCache( $cache );

		$this->assertEquals( 'bar', $instance->get( 'foo' ) );
	}

	public function testPurgeCacheByLanguage() {

		$cache = new HashBagOStuff();

		$instanceJa = MessageCache::ByLanguage( Language::factory( 'ja' ) );
		$instanceEn = MessageCache::ByLanguage( Language::factory( 'en' ) );

		$presetCached = array(
			'touched'  => 1000,
			'messages' => array( 'foo' => 'bar' )
		);

		$cache->set( $instanceEn->getCacheId(), $presetCached );
		$cache->set( $instanceJa->getCacheId(), $presetCached );

		$instanceEn->setCache( $cache )->purge();
		$instanceJa->setCache( $cache );

		$this->assertEmpty( $cache->get( $instanceEn->getCacheId() ) );
		$this->assertNotEmpty( $cache->get( $instanceJa->getCacheId() ) );
	}

	public function testGetTextByContentLanguageOnUrlBasedI18NExample() {

		$cache = new HashBagOStuff();

		$instance = MessageCache::ByContentLanguage();
		$instance->setCache( $cache );

		$this->assertInternalType(
			'string',
			$instance->get( 'smw-desc' )
		);

		$this->assertEquals(
			$instance->get( 'smw-desc' ),
			$instance->AsText()->get( 'smw-desc' )
		);

		$this->assertCount(
			1,
			$cache->get( $instance->getCacheId() )['messages']
		);
	}

	public function testGetTextByContentLanguageOnUrlBasedI18NExampleWithDifferentFormatting() {

		$cache = new HashBagOStuff();

		$instance = MessageCache::ByContentLanguage();
		$instance->setCache( $cache );

		$this->assertNotEquals(
			$instance->AsText()->get( 'smw-desc' ),
			$instance->AsEscaped()->get( 'smw-desc' )
		);

		$this->assertNotEquals(
			$instance->AsEscaped()->get( 'smw-desc' ),
			$instance->AsParsed()->get( 'smw-desc' )
		);

		$this->assertCount(
			3,
			$cache->get( $instance->getCacheId() )['messages']
		);
	}

	public function testGetTextByContextOnUrlBasedI18NExample() {

		$cache = new HashBagOStuff();

		$instance = MessageCache::ByContext();
		$instance->setCache( $cache );

		$this->assertInternalType(
			'string',
			$instance->get( 'smw-desc' )
		);
	}

	protected function acquireMockedInstanceWith( $modificationTimeOffset, $cacheTimeOffset = null ) {

		$language = Language::factory( 'en' );

		$instance = $this->getMock( '\SMW\Cache\MessageCache',
			array(
				'getCacheId',
				'getMessageFileModificationTime' ),
			array(
				$language,
				$cacheTimeOffset )
		);

		$instance->expects( $this->atLeastOnce() )
			->method( 'getCacheId' )
			->will( $this->returnValue( $this->cacheId ) );

		$instance->expects( $this->atLeastOnce() )
			->method( 'getMessageFileModificationTime' )
			->will( $this->returnValue( $modificationTimeOffset ) );

		return $instance;
	}

}
