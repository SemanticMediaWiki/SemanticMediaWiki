<?php

namespace SMW\Test;

use SMW\BasePropertyAnnotator;
use SMW\SemanticData;
use SMW\DIProperty;

/**
 * Tests for the BasePropertyAnnotator class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\BasePropertyAnnotator
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class BasePropertyAnnotatorTest extends ParserTestCase {

	/**
	 * Use a callback to set the observerStatus in order to verify an approriate
	 * response from a Publisher
	 * @var boolean
	 */
	protected $observerStatus = null;

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\BasePropertyAnnotator';
	}

	/**
	 * Helper method that returns a BasePropertyAnnotator object
	 *
	 * @param SemanticData $semanticData
	 * @param array $settings
	 *
	 * @return BasePropertyAnnotator
	 */
	private function newInstance( SemanticData $semanticData = null, $settings = array() ) {

		if ( $semanticData === null ) {
			$semanticData = $this->newMockBuilder()->newObject( 'SemanticData' );
		}

		return new BasePropertyAnnotator( $semanticData, $this->newSettings( $settings ) );
	}

	/**
	 * @test BasePropertyAnnotator::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test BasePropertyAnnotator::addCategories
	 * @dataProvider categoriesDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param array $expected
	 */
	public function testAddCategories( array $setup, array $expected ) {

		$this->observerStatus = null;

		$semanticData = new SemanticData(
			$this->newSubject( $this->newTitle( $setup['namespace'] ) )
		);

		$instance = $this->newInstance( $semanticData, $setup['settings'] );
		$instance->addCategories( $setup['categories'] );

		$this->assertSemanticData(
			$semanticData,
			$expected,
			'asserts that addCategories() adds expected triples'
		);

	}

	/**
	 * @test BasePropertyAnnotator::addCategories
	 * @dataProvider categoriesDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param array $expected
	 */
	public function testAddCategoriesOnMockObserver( array $setup, array $expected ) {

		$this->observerStatus = null;

		$semanticData = new SemanticData(
			$this->newSubject( $this->newTitle( $setup['namespace'] ) )
		);

		$mockObserver = $this->newMockBuilder()->newObject( 'FakeObserver', array(
			'updateOutput' => array( $this, 'mockObserverCallback' )
		) );

		// Create instance and attach mock Observer
		$instance = $this->newInstance( $semanticData, $setup['settings'] );
		$instance->attach( $mockObserver );
		$instance->addCategories( $setup['categories'] );

		$this->assertSemanticData(
			$semanticData,
			$expected,
			'asserts that addCategories() adds expected triples'
		);

		$this->assertTrue(
			$this->observerStatus,
			'asserts that the invoked Observer received a notification from the Publisher (Subject)'
		);

	}

	/**
	 * @test BasePropertyAnnotator::addCategories
	 * @dataProvider categoriesDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param array $expected
	 */
	public function testAddCategoriesObserverIntegration( array $setup, array $expected ) {

		$subject  = $this->newSubject( $this->newTitle( $setup['namespace'] ) );
		$semanticData = new SemanticData( $subject );

		// Test "real" observer integration
		$title        = $subject->getTitle();
		$parserOutput = $this->newParserOutput();
		$parserData   = $this->newParserData( $title, $parserOutput );

		// Create instance and attach mock Observer
		$instance = $this->newInstance( $parserData->getData(), $setup['settings'] );
		$instance->attach( $parserData );
		$instance->addCategories( $setup['categories'] );

		// Re-read data from the $parserOutput object
		$newParserData = $this->newParserData( $title, $parserOutput );

		$this->assertSemanticData(
			$newParserData->getData(),
			$expected,
			'asserts that addCategories() adds expected triples'
		);

	}

	/**
	 * @test BasePropertyAnnotator::addDefaultSort
	 * @dataProvider defaultSortDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param array $expected
	 */
	public function testAddDefaultSortOnMockObserver( array $setup, array $expected ) {

		$this->observerStatus = null;

		$subject  = $this->newSubject( $setup['title'] );
		$semanticData = new SemanticData( $subject );

		$mockObserver = $this->newMockBuilder()->newObject( 'FakeObserver', array(
			'updateOutput' => array( $this, 'mockObserverCallback' )
		) );

		// Create instance and attach mock Observer
		$instance = $this->newInstance( $semanticData );
		$instance->attach( $mockObserver );
		$instance->addDefaultSort( $setup['sort'] );

		$this->assertSemanticData(
			$semanticData,
			$expected,
			'asserts that addDefaultSort() adds expected triples'
		);

		$this->assertTrue(
			$this->observerStatus,
			'asserts that the invoked Observer received a notification from the Publisher (Subject)'
		);

	}

	/**
	 * @test BasePropertyAnnotator::addSpecialProperties
	 * @dataProvider specialPropertiesDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $setup
	 * @param array $expected
	 */
	public function testAddSpecialPropertiesOnMockObserver( array $setup, array $expected ) {

		$this->observerStatus = null;

		$semanticData = new SemanticData( $setup['subject'] );

		// Setup inidivudal mock objects that will be invoked in order to
		// control and support only needed functions
		$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage', $setup['wikiPage'] );
		$revision = $this->newMockBuilder()->newObject( 'Revision', $setup['revision'] );
		$user     = $this->newMockBuilder()->newObject( 'User', $setup['user'] );

		$mockObserver = $this->newMockBuilder()->newObject( 'FakeObserver', array(
			'updateOutput' => array( $this, 'mockObserverCallback' )
		) );

		// Create instance and attach mock Observer
		$instance = $this->newInstance( $semanticData, $setup['settings'] );
		$instance->attach( $mockObserver );
		$instance->addSpecialProperties( $wikiPage, $revision, $user );

		$this->assertSemanticData(
			$semanticData,
			$expected,
			'asserts that addSpecialProperties() adds expected triples'
		);

		$this->assertTrue(
			$this->observerStatus,
			'asserts that the invoked Observer received a notification from the Publisher (Subject)'
		);

		// Check against pre-existing registered special properties
		$instance->addSpecialProperties( $wikiPage, $revision, $user );

		$this->assertSemanticData(
			$semanticData,
			$expected,
			'asserts that addSpecialProperties() adds expected triples'
		);

	}

	/**
	 * This callback verifies that the Publisher reached the Observer
	 * by comparing the Publisher(Subject) class as its sender
	 */
	public function mockObserverCallback() {
		$this->observerStatus = is_a( func_get_arg( 0 ), $this->getClass() ) ? true : false;
	}

	/**
	 * Provides array of special properties
	 *
	 * @return array
	 */
	public function specialPropertiesDataProvider() {

		$provider = array();

		// Unknown
		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( 'Lala', '_Lula', '-Lila', '' )
				),
				'wikiPage' => array(),
				'revision' => array(),
				'user'     => array()
			),
			array(
				'propertyCount' => 0,
			)
		);

		// TYPE_MODIFICATION_DATE
		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MODIFICATION_DATE )
				),
				'wikiPage' => array( 'getTimestamp' => 1272508903 ),
				'revision' => array(),
				'user'     => array()
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_MDAT',
				'propertyValue' => array( '2010-04-29T02:41:43' ),
			)
		);

		// TYPE_CREATION_DATE
		$revision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getTimestamp' => 1272508903
		) );

		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'         => 'Lula',
			'getNamespace'     => NS_MAIN,
			'getFirstRevision' => $revision
		) );

		$subject = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle' => $this->newMockBuilder()->newObject( 'Title' )
		) );

		$provider[] = array(
			array(
				'subject'  => $subject,
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_CREATION_DATE )
				),
				'wikiPage' => array( 'getTitle' => $title ),
				'revision' => array(),
				'user'     => array()
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_CDAT',
				'propertyValue' => array( '2010-04-29T02:41:43' ),
			)
		);

		// TYPE_NEW_PAGE
		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_NEW_PAGE )
				),
				'wikiPage' => array(),
				'revision' => array( 'getParentId' => 9001 ),
				'user'     => array()
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_NEWP',
				'propertyValue' => array( true ),
			)
		);

		// TYPE_LAST_EDITOR
		$userPage = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'         => 'Lula',
			'getNamespace'     => NS_USER,
		) );

		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_LAST_EDITOR )
				),
				'wikiPage' => array(),
				'revision' => array(),
				'user'     => array( 'getUserPage' => $userPage )
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_LEDT',
				'propertyValue' => array( 'User:Lula' ),
			)
		);

		// Combine entries
		$provider[] = array(
			array(
				'subject'  => $this->newSubject( $this->newTitle() ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( '_MDAT', '_LEDT' )
				),
				'wikiPage' => array( 'getTimestamp' => 1272508903 ),
				'revision' => array(),
				'user'     => array( 'getUserPage' => $userPage )
			),
			array(
				'propertyCount' => 2,
				'propertyKey'   => array( '_MDAT', '_LEDT' ),
				'propertyValue' => array( '2010-04-29T02:41:43', 'User:Lula' ),
			)
		);

		return $provider;
	}

	/**
	 * Provides array of defaultSortkeys
	 *
	 * @return array
	 */
	public function defaultSortDataProvider() {

		$provider = array();

		// Sort entry
		$provider[] = array(
			array(
				'title' => $this->newTitle(),
				'sort'  => 'Lala'
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_SKEY',
				'propertyValue' => array( 'Lala' ),
			)
		);

		// Empty
		$title = $this->newTitle();
		$provider[] = array(
			array(
				'title' => $title,
				'sort'  => ''
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_SKEY',
				'propertyValue' => array( $title->getDBkey() ),
			)
		);

		return $provider;
	}

	/**
	 * Provides array of category names
	 *
	 * @return array
	 */
	public function categoriesDataProvider() {

		$provider = array();

		// Standard category
		$provider[] = array(
			array(
				'namespace'  => NS_MAIN,
				'categories' => array( 'Foo', 'Bar' ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => false,
					'smwgCategoriesAsInstances' => true
				)
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_INST',
				'propertyValue' => array( 'Foo',  'Bar' ),
			)
		);

		// Category hierarchy or Sub-category
		$provider[] = array(
			array(
				'namespace'  => NS_CATEGORY,
				'categories' => array( 'Foo', 'Bar' ),
				'settings'   => array(
					'smwgUseCategoryHierarchy'  => true,
					'smwgCategoriesAsInstances' => false
				)
			),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_SUBC',
				'propertyValue' => array( 'Foo',  'Bar' ),
			)
		);

		return $provider;
	}
}
