<?php

namespace SMW\Test;

use SMW\Tests\Utils\UtilityFactory;

use SMW\ApplicationFactory;
use SMW\AskParserFunction;
use SMW\Localizer;

use Title;
use ParserOutput;
use ReflectionClass;

/**
 * @covers \SMW\AskParserFunction
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class AskParserFunctionTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $semanticDataValidator;
	private $smwgQMaxLimit;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->applicationFactory->getSettings()->set( 'smwgQueryDurationEnabled', false );

		// FIXME in the Query do not use global scope
		$this->smwgQMaxLimit = $GLOBALS['smwgQMaxLimit'];
		$GLOBALS['smwgQMaxLimit'] = 1000;
	}

	protected function tearDown() {
		$this->applicationFactory->clear();
		$GLOBALS['smwgQMaxLimit'] = $this->smwgQMaxLimit;

		parent::tearDown();
	}

	public function testCanConstruct() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\AskParserFunction',
			new AskParserFunction( $parserData, $messageFormatter, $circularReferenceGuard )
		);
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testParse( array $params ) {

		$parserData = $this->applicationFactory->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AskParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard
		);

		$this->assertInternalType(
			'string',
			$instance->parse( $params )
		);
	}

	public function testIsQueryDisabled() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter->expects( $this->any() )
			->method( 'addFromKey' )
			->will( $this->returnSelf() );

		$messageFormatter->expects( $this->once() )
			->method( 'getHtml' );

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AskParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard
		);

		$instance->isQueryDisabled();
	}

	public function testSetShowMode() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AskParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard
		);

		$reflector = new ReflectionClass( '\SMW\AskParserFunction' );
		$showMode = $reflector->getProperty( 'showMode' );
		$showMode->setAccessible( true );

		$this->assertFalse( $showMode->getValue( $instance ) );
		$instance->setShowMode( true );

		$this->assertTrue( $showMode->getValue( $instance ) );
	}

	public function testCircularGuard() {

		$parserData = $this->applicationFactory->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard->expects( $this->once() )
			->method( 'mark' );

		$circularReferenceGuard->expects( $this->never() )
			->method( 'unmark' );

		$circularReferenceGuard->expects( $this->once() )
			->method( 'isCircularByRecursionFor' )
			->will( $this->returnValue( true ) );

		$instance = new AskParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard
		);

		$params = array();

		$this->assertEmpty(
			$instance->parse( $params )
		);
	}

	public function testQueryIdStabilityForFixedSetOfParameters() {

		$parserData = $this->applicationFactory->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AskParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard
		);

		$params = array(
			'[[Modification date::+]]',
			'?Modification date',
			'format=list'
		);

		$instance->parse( $params );

		$this->assertTrue(
			$parserData->getSemanticData()->hasSubSemanticData( '_QUERY702bb82fc5ac212df176709f98b4f5b9' )
		);

		// Limit is a factor that influences the query id, count uses the
		// max limit available in $GLOBALS['smwgQMaxLimit'] therefore we set
		// the limit to make the test independent from possible other settings

		$params = array(
			'[[Modification date::+]]',
			'?Modification date',
			'format=count'
		);

		$instance->parse( $params );

		$this->assertTrue(
			$parserData->getSemanticData()->hasSubSemanticData( '_QUERYf161b0f405d169d1f038812484619c1f' )
		);
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testInstantiatedQueryData( array $params, array $expected, array $settings ) {

		foreach ( $settings as $key => $value ) {
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		$parserData = $this->applicationFactory->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$circularReferenceGuard = $this->getMockBuilder( '\SMW\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AskParserFunction(
			$parserData,
			$messageFormatter,
			$circularReferenceGuard
		);

		$instance->parse( $params );

		foreach ( $parserData->getSemanticData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );

			$this->semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$containerSemanticData
			);
		}
	}

	public function queryDataProvider() {

		$categoryNS = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );
		$fileNS = Localizer::getInstance()->getNamespaceTextById( NS_FILE );

		$provider = array();

		// #0
		// {{#ask: [[Modification date::+]]
		// |?Modification date
		// |format=list
		// }}
		$provider[] = array(
			array(
				'[[Modification date::+]]',
				'?Modification date',
				'format=list'
			),
			array(
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'list', 1, 1, '[[Modification date::+]]' )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		// #1 Query string with spaces
		// {{#ask: [[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]
		// |?Modification date
		// |?Has title
		// |format=list
		// }}
		$provider[] = array(
			array(
				'[[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]',
				'?Modification date',
				'?Has title',
				'format=list'
			),
			array(
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'list', 4, 1, "[[Modification date::+]] [[$categoryNS:Foo bar]] [[Has title::!Foo bar]]" )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		// #2
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=list
		// }}
		$provider[] = array(
			array(
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=list'
			),
			array(
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'list', 2, 1, "[[Modification date::+]] [[$categoryNS:Foo]]" )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		// #3 Known format
		// {{#ask: [[File:Fooo]]
		// |?Modification date
		// |default=no results
		// |format=feed
		// }}
		$provider[] = array(
			array(
				'[[File:Fooo]]',
				'?Modification date',
				'default=no results',
				'format=feed'
			),
			array(
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'feed', 1, 1, "[[:$fileNS:Fooo]]" )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		// #4 Unknown format, default table
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=bar
		// }}
		$provider[] = array(
			array(
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=lula'
			),
			array(
				'propertyCount'  => 4,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
				'propertyValues' => array( 'table', 2, 1, "[[Modification date::+]] [[$categoryNS:Foo]]" )
			),
			array(
				'smwgQueryDurationEnabled' => false
			)
		);

		// #5 QueryTime enabled
		$provider[] = array(
			array(
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=lula'
			),
			array(
				'propertyCount'  => 5,
				'propertyKeys'   => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO', '_ASKDU' ),
			),
			array(
				'smwgQueryDurationEnabled' => true
			)
		);

		return $provider;
	}

}
