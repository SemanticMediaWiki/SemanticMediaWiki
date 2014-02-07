<?php

namespace SMW\Test;

use SMW\PredefinedPropertyAnnotator;
use SMW\NullPropertyAnnotator;
use SMW\EmptyContext;
use SMW\SemanticData;
use SMW\DIProperty;
use SMW\DIWikiPage;

use Title;

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

	public function getClass() {
		return '\SMW\PredefinedPropertyAnnotator';
	}

	private function newObserver() {
		return $this->newMockBuilder()->newObject( 'FakeObserver', array(
			'updateOutput' => array( $this, 'updateOutputCallbackToVerifyThatObserverIsReachable' )
		) );
	}

	/**
	 * @return PredefinedPropertyAnnotator
	 */
	private function newInstance( $semanticData = null, $settings = array(), $pageInfo = array() ) {

		if ( $semanticData === null ) {
			$semanticData = $this->newMockBuilder()->newObject( 'SemanticData' );
		}

		$predefinedProperty = $this->newMockBuilder()->newObject( 'PageInfoProvider', $pageInfo );

		$settings = $this->newSettings( $settings );

		$context  = new EmptyContext();
		$context->getDependencyBuilder()->getContainer()->registerObject( 'Settings', $settings );

		return new PredefinedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData, $context ), $predefinedProperty
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @depends testCanConstruct
	 * @dataProvider specialPropertiesDataProvider
	 */
	public function testAddSpecialPropertiesOnMockObserver( array $parameters, array $expected ) {

		$semanticData = new SemanticData( $parameters['subject'] );

		$instance = $this->newInstance(
			$semanticData,
			$parameters['settings'],
			$parameters['pageInfo']
		);

		$instance->attach( $this->newObserver() )->addAnnotation();

		$semanticDataValidator = new SemanticDataValidator;

		$semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);

		$this->assertEquals(
			$instance->verifyCallback,
			'updateOutputCallback',
			'Asserts that the invoked Observer was notified'
		);

	}

	public function updateOutputCallbackToVerifyThatObserverIsReachable( $instance ) {
		$this->assertInstanceOf( '\SMW\SemanticData', $instance->getSemanticData() );
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
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'UNKNOWN' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( 'Lala', '_Lula', '-Lila', '' )
				),
				'pageInfo' => array(),
			),
			array(
				'propertyCount' => 0,
			)
		);

		// TYPE_MODIFICATION_DATE
		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withModificationDate' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MODIFICATION_DATE )
				),
				'pageInfo' => array( 'getModificationDate' => 1272508903 )
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_MDAT',
				'propertyValues' => array( '2010-04-29T02:41:43' ),
			)
		);

		// TYPE_CREATION_DATE
		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withCreationDate' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_CREATION_DATE )
				),
				'pageInfo' => array( 'getCreationDate' => 1272508903 )
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_CDAT',
				'propertyValues' => array( '2010-04-29T02:41:43' ),
			)
		);

		// TYPE_NEW_PAGE
		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'NEW_PAGE_isNew' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_NEW_PAGE )
				),
				'pageInfo' => array( 'isNewPage' => true )
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_NEWP',
				'propertyValues' => array( true ),
			)
		);

		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'NEW_PAGE_isNotNew' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_NEW_PAGE )
				),
				'pageInfo' => array( 'isNewPage' => false )
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_NEWP',
				'propertyValues' => array( false ),
			)
		);

		// TYPE_LAST_EDITOR
		$userPage = $this->newMockBuilder()->newObject( 'Title', array(
			'getDBkey'         => 'Lula',
			'getNamespace'     => NS_USER,
		) );

		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withLastEditor' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_LAST_EDITOR )
				),
				'pageInfo' => array( 'getLastEditor' => $userPage )
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_LEDT',
				'propertyValues' => array( 'User:Lula' ),
			)
		);

		// Combined entries
		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withCombinedEntries' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( '_MDAT', '_LEDT' )
				),
				'pageInfo' => array(
					'getModificationDate' => 1272508903,
					'getLastEditor'      => $userPage
				)
			),
			array(
				'propertyCount'  => 2,
				'propertyKeys'   => array( '_MDAT', '_LEDT' ),
				'propertyValues' => array( '2010-04-29T02:41:43', 'User:Lula' ),
			)
		);

		// TYPE_MEDIA
		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withMediaAsFilePage' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MEDIA )
				),
				'pageInfo' => array(
					'isFilePage'   => true,
					'getMediaType' => 'FooMedia'
				)
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_MEDIA',
				'propertyValues' => array( 'FooMedia' ),
			)
		);

		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withMediaNotAsFilePage' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MEDIA )
				),
				'pageInfo' => array(
					'isFilePage'   => false,
					'getMediaType' => 'FooMedia'
				)
			),
			array(
				'propertyCount'  => 0
			)
		);

		// TYPE_MIME
		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withMimeAsFilePage' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MIME )
				),
				'pageInfo' => array(
					'isFilePage'   => true,
					'getMimeType' => 'FooMime'
				)
			),
			array(
				'propertyCount'  => 1,
				'propertyKeys'   => '_MIME',
				'propertyValues' => array( 'FooMime' ),
			)
		);

		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withMimeNotAsFilePage' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( DIProperty::TYPE_MIME )
				),
				'pageInfo' => array(
					'isFilePage'   => false,
					'getMimeType' => 'FooMime'
				)
			),
			array(
				'propertyCount'  => 0
			)
		);

		return $provider;
	}

}
