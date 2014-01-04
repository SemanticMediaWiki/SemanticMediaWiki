<?php

namespace SMW\Test;

use SMW\SkinAfterContent;
use SMW\ExtensionContext;

/**
 * @covers \SMW\SkinAfterContent
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SkinAfterContentTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SkinAfterContent';
	}

	/**
	 * @since 1.9
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

		$context = new ExtensionContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		$instance = new SkinAfterContent( $data, $skin );
		$instance->invokeContext( $context );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider outputDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcessFactboxPresenterIntegration( $setup, $expected ) {

		$data = '';
		$instance = $this->newInstance( $data, $setup['skin'] );

		// Inject fake content into the FactboxPresenter
		if ( isset( $setup['title'] ) ) {

			$factboxCache = $instance->withContext()->getDependencyBuilder()->newObject( 'FactboxCache', array(
				'OutputPage' => $setup['skin']->getOutput()
			) );

			$resultMapper = $factboxCache->newResultMapper( $setup['title']->getArticleID() );
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

		// #2 Special page
		$text  = __METHOD__ . 'text-2';

		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => true
		) );

		$outputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle' => $title
		) );

		$outputPage->mSMWFactboxText = $text;

		$skin = $this->newMockBuilder()->newObject( 'Skin', array(
			'getTitle'   => $outputPage->getTitle(),
			'getOutput'  => $outputPage,
			'getContext' => $this->newContext()
		) );

		$provider[] = array(
			array( 'skin' => $skin, 'text' => $text ),
			array( 'text' => '' )
		);

		// #3 "edit" request
		$text   = __METHOD__ . 'text-3';

		$outputPage = $this->newMockBuilder()->newObject( 'OutputPage', array(
			'getTitle' => $this->newMockBuilder()->newObject( 'Title' )
		) );

		$outputPage->mSMWFactboxText = $text;

		$skin = $this->newMockBuilder()->newObject( 'Skin', array(
			'getTitle'   => $outputPage->getTitle(),
			'getOutput'  => $outputPage,
			'getContext' => $this->newContext( array( 'action' => 'edit' ) )
		) );

		$provider[] = array(
			array( 'skin' => $skin, 'text' => $text ),
			array( 'text' => $text )
		);

		return $provider;
	}

}
