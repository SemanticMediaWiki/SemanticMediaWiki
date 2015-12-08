<?php

namespace Onoi\Tesa\Tests;

use Onoi\Tesa\StopwordAnalyzer;

/**
 * @covers \Onoi\Tesa\StopwordAnalyzer
 * @group onoi-tesa
 *
 * @license GNU GPL v2+
 * @since 0.1
 *
 * @author mwjames
 */
class StopwordAnalyzerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\Onoi\Tesa\StopwordAnalyzer',
			new StopwordAnalyzer()
		);
	}

	public function testCustomStopwordList() {

		$instance = new StopwordAnalyzer();

		$instance->setCustomStopwordList(
			array(
				'zoo' => array( 'Foo' ),
				'doo' => array( 'bar' )
			)
		);

		$this->assertTrue(
			$instance->isStopWord( 'Foo' )
		);

		$this->assertEquals(
			array( 'zoo', 'doo' ),
			$instance->getLanguageList()
		);
	}

	/**
	 * @dataProvider defaultListStopWordsProvider
	 */
	public function testUncachedIsWordCheckForDefaultList( $word, $expected ) {

		$instance = new StopwordAnalyzer();
		$instance->loadListByDefaultLanguages();

		$this->assertEquals(
			$expected,
			$instance->isStopWord( $word )
		);
	}

	/**
	 * @dataProvider listByLanguageStopWordsProvider
	 */
	public function testListByLanguage( $languageCode, $word, $expected ) {

		$instance = new StopwordAnalyzer();
		$instance->loadListByLanguage( $languageCode );

		$this->assertEquals(
			$expected,
			$instance->isStopWord( $word )
		);

		$this->assertEquals(
			(array)$languageCode,
			$instance->getLanguageList()
		);
	}

	/**
	 * @dataProvider listByLanguageStopWordsProvider
	 */
	public function testListFromCustomLocation( $languageCode, $word, $expected ) {

		$instance = new StopwordAnalyzer();
		$instance->loadListFromCustomLocation( __DIR__ . '/../../../data/stopwords/', $languageCode );

		$this->assertEquals(
			$expected,
			$instance->isStopWord( $word )
		);
	}

	public function testTryToLoadUnlistedLanguage() {

		$instance = new StopwordAnalyzer();
		$instance->loadListByLanguage( 'foo' );

		$this->assertFalse(
			$instance->isStopWord( 'Bar' )
		);
	}

	public function testToLoadFromCache() {

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->setMethods( array( 'contains', 'fetch' ) )
			->getMockForAbstractClass();

		$cache->expects( $this->once() )
			->method( 'contains' )
			->with( $this->stringContains( 'onoi:tesa:stopword' ) )
			->will( $this->returnValue( true ) );

		$cache->expects( $this->once() )
			->method( 'fetch' );

		$instance = new StopwordAnalyzer( $cache );
		$instance->loadListByLanguage( 'foo' );
	}

	public function defaultListStopWordsProvider() {

		$provider[] = array(
			'Foo',
			false
		);

		// en-list
		$provider[] = array(
			'about',
			true
		);

		// de-list
		$provider[] = array(
			'über',
			true
		);

		// fr-list
		$provider[] = array(
			'avoir',
			true
		);

		// es-list
		$provider[] = array(
			'aquellos',
			true
		);

		// ja-list (not default)
		$provider[] = array(
			'かつて',
			false
		);

		return $provider;
	}

	public function listByLanguageStopWordsProvider() {

		// ja-list
		$provider[] = array(
			'ja',
			'かつて',
			true
		);

		return $provider;
	}

}
