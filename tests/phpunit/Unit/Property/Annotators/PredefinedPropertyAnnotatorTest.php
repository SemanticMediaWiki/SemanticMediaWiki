<?php

namespace SMW\Tests\Property\Annotators;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Localizer;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\PredefinedPropertyAnnotator;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @covers \SMW\Property\Annotators\PredefinedPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PredefinedPropertyAnnotatorTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
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
			'\SMW\Property\Annotators\PredefinedPropertyAnnotator',
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

		$instance = new PredefinedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$pageInfo
		);

		$instance->setPredefinedPropertyList(
			$parameters['settings']['smwgPageSpecialProperties']
		);

		$instance->addAnnotation();

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function specialPropertiesDataProvider() {

		$provider = [];

		#0 Unknown
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'UNKNOWN' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ 'Lala', '_Lula', '-Lila', '' ]
				],
				'pageInfo' => [],
			],
			[
				'propertyCount' => 0,
			]
		];

		#1 TYPE_MODIFICATION_DATE
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withModificationDate' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_MODIFICATION_DATE ]
				],
				'pageInfo' => [ 'getModificationDate' => 1272508903 ]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_MDAT',
				'propertyValues' => [ '2010-04-29T02:41:43' ],
			]
		];

		#2 TYPE_CREATION_DATE
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withCreationDate' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_CREATION_DATE ]
				],
				'pageInfo' => [ 'getCreationDate' => 1272508903 ]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_CDAT',
				'propertyValues' => [ '2010-04-29T02:41:43' ],
			]
		];

		#3 TYPE_NEW_PAGE
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'NEW_PAGE_isNew' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_NEW_PAGE ]
				],
				'pageInfo' => [ 'isNewPage' => true ]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_NEWP',
				'propertyValues' => [ true ],
			]
		];

		#4
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'NEW_PAGE_isNotNew' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_NEW_PAGE ]
				],
				'pageInfo' => [ 'isNewPage' => false ]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_NEWP',
				'propertyValues' => [ false ],
			]
		];

		#5 TYPE_LAST_EDITOR
		$userPage = MockTitle::buildMock( 'Lula' );
		$userNS = Localizer::getInstance()->getNamespaceTextById( NS_USER );

		$userPage->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_USER ) );

		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withLastEditor' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_LAST_EDITOR ]
				],
				'pageInfo' => [ 'getLastEditor' => $userPage ]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_LEDT',
				'propertyValues' => [ ":$userNS:Lula" ],
			]
		];

		#6 Combined entries
		$userPage = MockTitle::buildMock( 'Lula' );
		$userNS = Localizer::getInstance()->getNamespaceTextById( NS_USER );

		$userPage->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_USER ) );

		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'withCombinedEntries' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ '_MDAT', '_LEDT' ]
				],
				'pageInfo' => [
					'getModificationDate' => 1272508903,
					'getLastEditor'       => $userPage
				]
			],
			[
				'propertyCount'  => 2,
				'propertyKeys'   => [ '_MDAT', '_LEDT' ],
				'propertyValues' => [ '2010-04-29T02:41:43', ":$userNS:Lula" ],
			]
		];

		#7 TYPE_MEDIA
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'MimePropertyForFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_MEDIA ]
				],
				'pageInfo' => [
					'isFilePage'   => true,
					'getMediaType' => 'FooMedia'
				]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_MEDIA',
				'propertyValues' => [ 'FooMedia' ],
			]
		];

		#8
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'MediaPropertyForNonFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_MEDIA ]
				],
				'pageInfo' => [
					'isFilePage'   => false,
					'getMediaType' => 'FooMedia'
				]
			],
			[
				'propertyCount'  => 0
			]
		];

		#9 TYPE_MIME
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'MimePropertyForFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_MIME ]
				],
				'pageInfo' => [
					'isFilePage'   => true,
					'getMimeType' => 'FooMime'
				]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_MIME',
				'propertyValues' => [ 'FooMime' ],
			]
		];

		#10
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'MimePropertyForNonFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_MIME ]
				],
				'pageInfo' => [
					'isFilePage'  => false,
					'getMimeType' => 'FooMime'
				]
			],
			[
				'propertyCount'  => 0
			]
		];

		#11 Empty TYPE_MIME
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'EmptyMimePropertyFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_MIME ]
				],
				'pageInfo' => [
					'isFilePage'  => true,
					'getMimeType' => ''
				]
			],
			[
				'propertyCount'  => 0
			]
		];

		#12 Empty TYPE_MEDIA
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'EmptyMediaPropertyFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_MEDIA ]
				],
				'pageInfo' => [
					'isFilePage'   => true,
					'getMediaType' => ''
				]
			],
			[
				'propertyCount'  => 0
			]
		];

		#13 Null TYPE_MIME
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'NullMimePropertyFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_MIME ]
				],
				'pageInfo' => [
					'isFilePage'  => true,
					'getMimeType' => null
				]
			],
			[
				'propertyCount'  => 0
			]
		];

		#14 Null TYPE_MEDIA
		$provider[] = [
			[
				'subject'  => DIWikiPage::newFromTitle( Title::newFromText( 'NullMediaPropertyFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ DIProperty::TYPE_MEDIA ]
				],
				'pageInfo' => [
					'isFilePage'  => true,
					'getMimeType' => null
				]
			],
			[
				'propertyCount'  => 0
			]
		];

		return $provider;
	}

}
