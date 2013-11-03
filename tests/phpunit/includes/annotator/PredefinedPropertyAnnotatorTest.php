<?php

namespace SMW\Test;

use SMW\PredefinedPropertyAnnotator;
use SMW\NullPropertyAnnotator;
use SMW\EmptyContext;
use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\DIProperty;

/**
 * @covers \SMW\PredefinedPropertyAnnotator
 * @covers \SMW\PropertyAnnotatorDecorator
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
class PredefinedPropertyAnnotatorTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\PredefinedPropertyAnnotator';
	}

	/**
	 * @since 1.9
	 *
	 * @return Observer
	 */
	private function newObserver() {

		return $this->newMockBuilder()->newObject( 'FakeObserver', array(
			'updateOutput' => array( $this, 'updateOutputCallback' )
		) );

	}

	/**
	 * @since 1.9
	 *
	 * @return PredefinedPropertyAnnotator
	 */
	private function newInstance( $semanticData = null, $settings = array(), $wikiPage = null, $revision = null, $user = null ) {

		if ( $semanticData === null ) {
			$semanticData = $this->newMockBuilder()->newObject( 'SemanticData' );
		}

		if ( $wikiPage === null ) {
			$wikiPage = $this->newMockBuilder()->newObject( 'WikiPage' );
		}

		if ( $revision === null ) {
			$revision = $this->newMockBuilder()->newObject( 'Revision' );
		}

		if ( $user === null ) {
			$user = $this->newMockBuilder()->newObject( 'User' );
		}

		$settings = $this->newSettings( $settings );

		$context  = new EmptyContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		return new PredefinedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData, $context ),
			$wikiPage,
			$revision,
			$user
		);

	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider specialPropertiesDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddSpecialPropertiesOnMockObserver( array $setup, array $expected ) {

		$semanticData = new SemanticData( $setup['subject'] );

		$instance = $this->newInstance(
			$semanticData,
			$setup['settings'],
			$this->newMockBuilder()->newObject( 'WikiPage', $setup['wikiPage'] ),
			$this->newMockBuilder()->newObject( 'Revision', $setup['revision'] ),
			$this->newMockBuilder()->newObject( 'User', $setup['user'] )
		);

		$instance->attach( $this->newObserver() )->addAnnotation();

		$this->assertSemanticData(
			$instance->getSemanticData(),
			$expected,
			'Asserts that addAnnotation() adds expected triples'
		);

		$this->assertEquals(
			$instance->verifyCallback,
			'updateOutputCallback',
			'Asserts that the invoked Observer was notified'
		);

	}

	/**
	 * Verify that the Observer is reachable
	 *
	 * @since 1.9
	 */
	public function updateOutputCallback( $instance ) {

		$this->assertInstanceOf( '\SMW\SemanticData', $instance->getSemanticData() );
		$this->assertInstanceOf( '\SMW\ContextResource', $instance->withContext() );

		return $instance->verifyCallback = 'updateOutputCallback';
	}

	/**
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

}
