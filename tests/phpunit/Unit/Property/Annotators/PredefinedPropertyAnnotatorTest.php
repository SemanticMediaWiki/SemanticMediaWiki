<?php

namespace SMW\Tests\Unit\Property\Annotators;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Localizer\Localizer;
use SMW\PageInfo;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\PredefinedPropertyAnnotator;
use SMW\Tests\Utils\Mock\MockTitle;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers \SMW\Property\Annotators\PredefinedPropertyAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class PredefinedPropertyAnnotatorTest extends TestCase {

	private $semanticDataFactory;
	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataFactory = UtilityFactory::getInstance()->newSemanticDataFactory();
		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$pageInfo = $this->getMockBuilder( PageInfo::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PredefinedPropertyAnnotator(
			new NullPropertyAnnotator( $semanticData ),
			$pageInfo
		);

		$this->assertInstanceOf(
			PredefinedPropertyAnnotator::class,
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

		$pageInfo = $this->getMockBuilder( PageInfo::class )
			->disableOriginalConstructor()
			->getMock();

		foreach ( $parameters['pageInfo'] as $method => $returnValue ) {
			$pageInfo->expects( $this->any() )
				->method( $method )
				->willReturn( $returnValue );
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
		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
		$provider = [];

		# 0 Unknown
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'UNKNOWN' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ 'Lala', '_Lula', '-Lila', '' ]
				],
				'pageInfo' => [],
			],
			[
				'propertyCount' => 0,
			]
		];

		# 1 TYPE_MODIFICATION_DATE
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'withModificationDate' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_MODIFICATION_DATE ]
				],
				'pageInfo' => [ 'getModificationDate' => '1272508903' ]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_MDAT',
				'propertyValues' => [ '2010-04-29T02:41:43' ],
			]
		];

		# 2 TYPE_CREATION_DATE
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'withCreationDate' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_CREATION_DATE ]
				],
				'pageInfo' => [ 'getCreationDate' => '1272508903' ]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_CDAT',
				'propertyValues' => [ '2010-04-29T02:41:43' ],
			]
		];

		# 3 TYPE_NEW_PAGE
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'NEW_PAGE_isNew' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_NEW_PAGE ]
				],
				'pageInfo' => [ 'isNewPage' => true ]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_NEWP',
				'propertyValues' => [ true ],
			]
		];

		# 4
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'NEW_PAGE_isNotNew' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_NEW_PAGE ]
				],
				'pageInfo' => [ 'isNewPage' => false ]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_NEWP',
				'propertyValues' => [ false ],
			]
		];

		# 5 TYPE_LAST_EDITOR
		$userPage = MockTitle::buildMock( 'Lula' );
		$userNS = Localizer::getInstance()->getNsText( NS_USER );

		$userPage->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_USER );

		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'withLastEditor' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_LAST_EDITOR ]
				],
				'pageInfo' => [ 'getLastEditor' => $userPage ]
			],
			[
				'propertyCount'  => 1,
				'propertyKeys'   => '_LEDT',
				'propertyValues' => [ ":$userNS:Lula" ],
			]
		];

		# 6 Combined entries
		$userPage = MockTitle::buildMock( 'Lula' );
		$userNS = Localizer::getInstance()->getNsText( NS_USER );

		$userPage->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( NS_USER );

		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'withCombinedEntries' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ '_MDAT', '_LEDT' ]
				],
				'pageInfo' => [
					'getModificationDate' => '1272508903',
					'getLastEditor'       => $userPage
				]
			],
			[
				'propertyCount'  => 2,
				'propertyKeys'   => [ '_MDAT', '_LEDT' ],
				'propertyValues' => [ '2010-04-29T02:41:43', ":$userNS:Lula" ],
			]
		];

		# 7 TYPE_MEDIA
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'MimePropertyForFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_MEDIA ]
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

		# 8
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'MediaPropertyForNonFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_MEDIA ]
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

		# 9 TYPE_MIME
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'MimePropertyForFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_MIME ]
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

		# 10
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'MimePropertyForNonFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_MIME ]
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

		# 11 Empty TYPE_MIME
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'EmptyMimePropertyFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_MIME ]
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

		# 12 Empty TYPE_MEDIA
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'EmptyMediaPropertyFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_MEDIA ]
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

		# 13 Null TYPE_MIME
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'NullMimePropertyFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_MIME ]
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

		# 14 Null TYPE_MEDIA
		$provider[] = [
			[
				'subject'  => WikiPage::newFromTitle( $titleFactory->newFromText( 'NullMediaPropertyFilePage' ) ),
				'settings' => [
					'smwgPageSpecialProperties' => [ Property::TYPE_MEDIA ]
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
