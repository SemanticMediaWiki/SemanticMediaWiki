<?php

namespace SMW\Test;

use SMW\NewRevisionFromEditComplete;
use SMW\ExtensionContext;
use SMW\DIProperty;

use ParserOutput;
use WikiPage;
use Revision;

/**
 * @covers \SMW\NewRevisionFromEditComplete
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
class NewRevisionFromEditCompleteTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\NewRevisionFromEditComplete';
	}

	/**
	 * @since 1.9
	 *
	 * @return NewRevisionFromEditComplete
	 */
	private function newInstance( WikiPage $wikiPage = null, Revision $revision = null ) {

		$baseId = 9001;
		$user   = $this->newMockUser();

		if ( $wikiPage === null ) {
			$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage' );
		}

		if ( $revision === null ) {
			$revision = $this->newMockBuilder()->newObject( 'Revision' );
		}

		$instance = new NewRevisionFromEditComplete( $wikiPage, $revision, $baseId, $user );
		$instance->invokeContext( new ExtensionContext() );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider wikiPageDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcess( $setup, $expected ) {

		$instance = $this->newInstance( $setup['wikiPage'], $setup['revision'] );

		$this->assertTrue(
			$instance->process(),
			'asserts that process() always returns true'
		);

	}

	/**
	 * @dataProvider wikiPageDataProvider
	 *
	 * @since 1.9.0.2
	 */
	public function testProcessWithDisabledContentHandler( $setup, $expected ) {

		$instance = $this->getMock( $this->getClass(),
			array( 'hasContentForEditMethod' ),
			array(
				$setup['wikiPage'],
				$setup['revision'],
				1001,
				$this->newMockUser()
			)
		);

		$instance->expects( $this->any() )
			->method( 'hasContentForEditMethod' )
			->will( $this->returnValue( false ) );

		$instance->invokeContext( new ExtensionContext() );

		$this->assertTrue(
			$instance->process(),
			'Asserts that process() always returns true'
		);

	}

	/**
	 * @dataProvider wikiPageDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcessAnnotationIntegration( $setup, $expected ) {

		$settings = $this->newSettings( $setup['settings'] );
		$instance = $this->newInstance( $setup['wikiPage'], $setup['revision'] );

		$instance->withContext()->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		$this->assertTrue(
			$instance->process(),
			'Asserts that process() always returns true'
		);

		$editInfo = $setup['editInfo'];

		if ( $editInfo && $editInfo->output instanceof ParserOutput ) {

			$parserData = $this->newParserData(
				$setup['wikiPage']->getTitle(),
				$editInfo->output
			);

			$semanticDataValidator = new SemanticDataValidator;
			$semanticDataValidator->assertThatPropertiesAreSet( $expected, $parserData->getSemanticData() );

		}

	}

	/**
	 * @return array
	 */
	public function wikiPageDataProvider() {

		$provider = array();

		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getRawText' => 'Foo',
			'getContent' => $this->newMockContent()
		) );

		// #0 No parserOutput object
		$editInfo = (object)array();
		$editInfo->output = null;

		$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage', array(
			'prepareContentForEdit' => $editInfo,
			'prepareTextForEdit'    => $editInfo
		) );

		$provider[] = array(
			array(
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $revision,
				'settings' => array()
			),
			array()
		);

		// #1
		$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage', array(
			'prepareContentForEdit' => false,
			'prepareTextForEdit'    => false
		) );

		$provider[] = array(
			array(
				'editInfo' => false,
				'wikiPage' => $wikiPage,
				'revision' => $revision,
				'settings' => array()
			),
			array()
		);

		// #2
		$editInfo = (object)array();
		$editInfo->output = $this->newParserOutput();

		$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage', array(
			'prepareContentForEdit' => $editInfo,
			'prepareTextForEdit'    => $editInfo,
			'getTitle'     => $this->newTitle(),
			'getTimestamp' => 1272508903
		) );

		$provider[] = array(
			array(
				'editInfo' => $editInfo,
				'wikiPage' => $wikiPage,
				'revision' => $revision,
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MODIFICATION_DATE )
				)
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_MDAT',
				'propertyValues' => array( '2010-04-29T02:41:43' ),
			)
		);

		return $provider;
	}


	/**
	 * @return Content|null
	 */
	public function newMockContent() {

		$content = null;

		if ( class_exists( 'ContentHandler' ) ) {

			$contentHandler = $this->newMockBuilder()->newObject( 'ContentHandler', array(
				'getDefaultFormat' => 'Foo'
			) );

			$content = $this->newMockBuilder()->newObject( 'Content', array(
				'getContentHandler' => $contentHandler,
			) );
		}

		return $content;
	}


}
