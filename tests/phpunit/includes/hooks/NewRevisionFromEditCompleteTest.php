<?php

namespace SMW\Test;

use SMW\NewRevisionFromEditComplete;
use SMW\SharedDependencyContainer;
use SMW\DIProperty;

use WikiPage;
use Revision;

/**
 * @covers \SMW\NewRevisionFromEditComplete
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

	// RECYCLE

	/*
	 * @since 1.9
	 *
	public function testOnNewRevisionFromEditComplete( $text ) {
		if ( method_exists( 'WikiPage', 'doEditContent' ) ) {

			$wikiPage = $this->newPage();
			$user = $this->getUser();

			$content = \ContentHandler::makeContent(
				$text,
				$wikiPage->getTitle(),
				CONTENT_MODEL_WIKITEXT
			);

			$wikiPage->doEditContent( $content, "testing", EDIT_NEW );
			$this->assertTrue( $wikiPage->getId() > 0, "WikiPage should have new page id" );
			$revision = $wikiPage->getRevision();

			$result = SMWHooks::onNewRevisionFromEditComplete (
				$wikiPage,
				$revision,
				$wikiPage->getId(),
				$user
			);

			// Always make sure the clean-up
			if ( $wikiPage->exists() ) {
				$wikiPage->doDeleteArticle( "testing done." );
			}

			$this->assertTrue( $result );
		} else {
			$this->markTestSkipped(
				'Skipped test due to missing method (probably MW 1.19 or lower).'
			);
		}
	}*/


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
		$instance->setDependencyBuilder( $this->newDependencyBuilder( new SharedDependencyContainer() ) );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider titleDataProvider
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
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcessAnnotationIntegration( $setup, $expected ) {

		$settings = $this->newSettings( $setup['settings'] );
		$instance = $this->newInstance( $setup['wikiPage'], $setup['revision'] );

		$instance->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		$this->assertTrue(
			$instance->process(),
			'asserts that process() always returns true'
		);

		$parserOutput = $setup['wikiPage']->getParserOutput(
			$setup['wikiPage']->makeParserOptions( $this->newMockUser() ),
			$setup['revision']->getId()
		);

		if ( $parserOutput !== null ) {

			$parserData = $this->newParserData(
				$setup['wikiPage']->getTitle(),
				$parserOutput
			);

			$this->assertSemanticData(
				$parserData->getData(),
				$expected,
				"asserts whether addSpecialProperties() adds the {$expected['propertyKey']} annotation"
			);

		}

	}

	/**
	 * @return array
	 */
	public function titleDataProvider() {

		$provider = array();

		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getId' => 1001
		) );

		// #0 No parserOutput object
		$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage', array(
			'getParserOutput'   => null,
			'makeParserOptions' => $this->newMockBuilder()->newObject( 'ParserOptions' )
		) );

		$provider[] = array(
			array(
				'wikiPage' => $wikiPage,
				'revision' => $revision,
				'settings' => array()
			),
			array()
		);

		// #1
		$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage', array(
			'getTitle'          => $this->newTitle(),
			'getParserOutput'   => $this->newParserOutput(),
			'makeParserOptions' => $this->newMockBuilder()->newObject( 'ParserOptions' ),
			'getTimestamp'      => 1272508903
		) );

		$provider[] = array(
			array(
				'wikiPage' => $wikiPage,
				'revision' => $revision,
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MODIFICATION_DATE )
				)
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_MDAT',
				'propertyValue' => array( '2010-04-29T02:41:43' ),
			)
		);

		return $provider;
	}

}
