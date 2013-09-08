<?php

namespace SMW\Test;

use SMW\NewRevisionFromEditComplete;
use SMW\SharedDependencyContainer;
use SMW\DIProperty;

use WikiPage;
use Revision;

/**
 * Tests for the NewRevisionFromEditComplete class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\NewRevisionFromEditComplete
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class NewRevisionFromEditCompleteTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\NewRevisionFromEditComplete';
	}

	/**
	 * Helper method that returns a NewRevisionFromEditComplete object
	 *
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
	 * @test NewRevisionFromEditComplete::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test NewRevisionFromEditComplete::process
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
	 */
	public function testProcess( $setup, $expected ) {

		$instance = $this->newInstance( $setup['wikiPage'], $setup['revision'] );

		$this->assertTrue(
			$instance->process(),
			'asserts that process() always returns true'
		);

	}

	/**
	 * @test NewRevisionFromEditComplete::process
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
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
