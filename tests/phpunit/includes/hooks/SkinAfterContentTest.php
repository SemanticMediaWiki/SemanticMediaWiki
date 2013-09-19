<?php

namespace SMW\Test;

use SMW\SharedDependencyContainer;
use SMW\SkinAfterContent;
use SMW\CacheIdGenerator;
use SMW\CacheableResultMapper;
use SMW\SimpleDictionary;

/**
 * Tests for the SkinAfterContent class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SkinAfterContent
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SkinAfterContentTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SkinAfterContent';
	}

	/**
	 * Helper method that returns a SkinAfterContent object
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 *
	 * @return SkinAfterContent
	 */
	private function newInstance( &$data = '', $skin = null ) {

		$settings = $this->newSettings( array(
			'smwgFactboxUseCache' => true,
			'smwgCacheType'       => 'hash'
		) );

		if ( $skin === null ) {
			$skin = $this->newMockBuilder()->newObject( 'Skin' );
		}

		$container = new SharedDependencyContainer();
		$container->registerObject( 'Settings', $settings );

		$instance = new SkinAfterContent( $data, $skin );
		$instance->setDependencyBuilder( $this->newDependencyBuilder( $container ) );

		return $instance;
	}

	/**
	 * @test SkinAfterContent::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test SkinAfterContent::process
	 * @dataProvider outputDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcessFactboxPresenterIntegration( $setup, $expected ) {

		$data = '';
		$instance = $this->newInstance( $data, $setup['skin'] );

		// Inject fake content into the FactboxPresenter
		if ( isset( $setup['title'] ) ) {

			$presenter = $instance->getDependencyBuilder()->newObject( 'FactboxPresenter', array(
				'OutputPage' => $setup['skin']->getOutput()
			) );

			$resultMapper = $presenter->getResultMapper( $setup['title']->getArticleID() );
			$resultMapper->recache( array(
				'revId' => null,
				'text'  => $setup['text']
			) );

		}

		$this->assertTrue(
			$instance->process(),
			'Asserts that process() always returns true'
		);

		$this->assertEquals(
			$expected['text'],
			$data,
			'Asserts that data contains expected text alteration'
		);

	}

	/**
	 * @return array
	 */
	public function outputDataProvider() {

		$provider = array();

		// #0 Retrive content from outputPage property
		$text = __METHOD__ . 'text-0';

		$outputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle' => $this->newMockBuilder()->newObject( 'Title' )
		) );

		$outputPage->mSMWFactboxText = $text;

		$skin = $this->newMockBuilder()->newObject( 'Skin', array(
			'getTitle'   => null,
			'getOutput'  => $outputPage,
			'getContext' => $this->newContext()
		) );

		$provider[] = array(
			array( 'skin' => $skin ),
			array( 'text' => $text )
		);

		// #1 Retrive content from cache
		$outputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle' => $this->newMockBuilder()->newObject( 'Title' )
		) );

		$text = __METHOD__ . 'text-1';

		$skin = $this->newMockBuilder()->newObject( 'Skin', array(
			'getTitle'   => $outputPage->getTitle(),
			'getOutput'  => $outputPage,
			'getContext' => $this->newContext()
		) );

		$provider[] = array(
			array( 'skin' => $skin, 'text' => $text, 'title' => $outputPage->getTitle() ),
			array( 'text' => $text )
		);

		// #2 Special page, empty return
		$text  = __METHOD__ . 'text-2';

		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => true
		) );

		$outputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle' => $title
		) );

		$skin = $this->newMockBuilder()->newObject( 'Skin', array(
			'getTitle'   => $outputPage->getTitle(),
			'getOutput'  => $outputPage,
			'getContext' => $this->newContext()
		) );

		$provider[] = array(
			array( 'skin' => $skin, 'text' => $text ),
			array( 'text' => '' )
		);

		// #3 "edit" request, empty return
		$text   = __METHOD__ . 'text-3';

		$outputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle' => $this->newMockBuilder()->newObject( 'Title' )
		) );

		$skin = $this->newMockBuilder()->newObject( 'Skin', array(
			'getTitle'   => $outputPage->getTitle(),
			'getOutput'  => $outputPage,
			'getContext' => $this->newContext( array( 'action' => 'edit' ) )
		) );

		$provider[] = array(
			array( 'skin' => $skin, 'text' => $text ),
			array( 'text' => '' )
		);

		return $provider;
	}

}
