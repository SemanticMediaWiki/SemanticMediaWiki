<?php

namespace SMW\Tests\Annotator;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\Mock\MockTitle;

use SMW\Annotator\PredefinedPropertyAnnotator;
use SMW\Annotator\NullPropertyAnnotator;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Application;
use SMW\Settings;

use Title;

/**
 * @covers \SMW\Annotator\PredefinedPropertyAnnotator
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PredefinedPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;
	private $application;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = new SemanticDataFactory();
		$this->semanticDataValidator = new SemanticDataValidator();
		$this->application = Application::getInstance();
	}

	protected function tearDown() {
		$this->application->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$pageInfo = $this->getMockBuilder( '\SMW\PageInfo' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PredefinedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$pageInfo
		);

		$this->assertInstanceOf(
			'\SMW\Annotator\PredefinedPropertyAnnotator',
			$instance
		);
	}

	/**
	 * @dataProvider specialPropertiesDataProvider
	 */
	public function testAddSpecialProperties( array $parameters, array $expected ) {

		$semanticData = $this->semanticDataFactory
			->setSubject( $parameters['subject'] )
			->newEmptySemanticData();

		$pageInfo = $this->getMockBuilder( '\SMW\PageInfo' )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $parameters['pageInfo'] as $method => $returnValue ) {
			$pageInfo->expects( $this->any() )
				->method( $method )
				->will( $this->returnValue( $returnValue ) );
		}

		$this->application->registerObject(
			'Settings',
			Settings::newFromArray( $parameters['settings'] )
		);

		$instance = new PredefinedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$pageInfo
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function specialPropertiesDataProvider() {

		$provider = array();

		#0 Unknown
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

		#1 TYPE_MODIFICATION_DATE
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

		#2 TYPE_CREATION_DATE
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

		#3 TYPE_NEW_PAGE
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

		#4
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

		#5 TYPE_LAST_EDITOR
		$userPage = MockTitle::buildMock( 'Lula' );

		$userPage->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_USER ) );

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
				'propertyValues' => array( ':User:Lula' ),
			)
		);

		#6 Combined entries
		$provider[] = array(
			array(
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withCombinedEntries' ) ),
				'settings' => array(
					'smwgPageSpecialProperties' => array( '_MDAT', '_LEDT' )
				),
				'pageInfo' => array(
					'getModificationDate' => 1272508903,
					'getLastEditor'       => $userPage
				)
			),
			array(
				'propertyCount'  => 2,
				'propertyKeys'   => array( '_MDAT', '_LEDT' ),
				'propertyValues' => array( '2010-04-29T02:41:43', ':User:Lula' ),
			)
		);

		#7 TYPE_MEDIA
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

		#8
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

		#9 TYPE_MIME
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

		#10
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
