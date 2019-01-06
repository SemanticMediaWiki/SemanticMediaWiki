<?php

namespace SMW\Tests\ParserFunctions;

use ParserOutput;
use ReflectionClass;
use SMW\ApplicationFactory;
use SMW\Localizer;
use SMW\ParserFunctions\AskParserFunction;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\ParserFunctions\AskParserFunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class AskParserFunctionTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $semanticDataValidator;
	private $messageFormatter;
	private $circularReferenceGuard;
	private $expensiveFuncExecutionWatcher;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();

		$this->testEnvironment->addConfiguration( 'smwgQueryProfiler', true );
		$this->testEnvironment->addConfiguration( 'smwgQMaxLimit', 1000 );

		$this->messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->circularReferenceGuard = $this->getMockBuilder( '\SMW\Utils\CircularReferenceGuard' )
			->disableOriginalConstructor()
			->getMock();

		$this->expensiveFuncExecutionWatcher = $this->getMockBuilder( '\SMW\ParserFunctions\ExpensiveFuncExecutionWatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->expensiveFuncExecutionWatcher->expects( $this->any() )
			->method( 'hasReachedExpensiveLimit' )
			->will( $this->returnValue( false ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( [] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->any() )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserFunctions\AskParserFunction',
			new AskParserFunction( $parserData, $this->messageFormatter, $this->circularReferenceGuard, $this->expensiveFuncExecutionWatcher )
		);
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testParse( array $params ) {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$this->expensiveFuncExecutionWatcher
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

		$this->messageFormatter->expects( $this->any() )
			->method( 'addFromKey' )
			->will( $this->returnSelf() );

		$this->messageFormatter->expects( $this->once() )
			->method( 'getHtml' );

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$this->expensiveFuncExecutionWatcher
		);

		$instance->isQueryDisabled();
	}

	public function testHasReachedExpensiveLimit() {

		$params = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=list'
		];

		$parserData = ApplicationFactory::getInstance()->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$expensiveFuncExecutionWatcher = $this->getMockBuilder( '\SMW\ParserFunctions\ExpensiveFuncExecutionWatcher' )
			->disableOriginalConstructor()
			->getMock();

		$expensiveFuncExecutionWatcher->expects( $this->any() )
			->method( 'hasReachedExpensiveLimit' )
			->will( $this->returnValue( true ) );

		$this->messageFormatter->expects( $this->any() )
			->method( 'addFromKey' )
			->will( $this->returnSelf() );

		$this->messageFormatter->expects( $this->once() )
			->method( 'getHtml' );

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$expensiveFuncExecutionWatcher
		);

		$instance->parse( $params );
	}

	public function testSetShowMode() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$this->expensiveFuncExecutionWatcher
		);

		$reflector = new ReflectionClass( '\SMW\ParserFunctions\AskParserFunction' );
		$showMode = $reflector->getProperty( 'showMode' );
		$showMode->setAccessible( true );

		$this->assertFalse( $showMode->getValue( $instance ) );
		$instance->setShowMode( true );

		$this->assertTrue( $showMode->getValue( $instance ) );
	}

	public function testCircularGuard() {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$this->circularReferenceGuard->expects( $this->once() )
			->method( 'mark' );

		$this->circularReferenceGuard->expects( $this->never() )
			->method( 'unmark' );

		$this->circularReferenceGuard->expects( $this->once() )
			->method( 'isCircular' )
			->will( $this->returnValue( true ) );

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$this->expensiveFuncExecutionWatcher
		);

		$params = [];

		$this->assertEmpty(
			$instance->parse( $params )
		);
	}

	public function testQueryIdStabilityForFixedSetOfParametersWithFingerprintMethod() {

		$parserData = ApplicationFactory::getInstance()->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$this->expensiveFuncExecutionWatcher
		);

		$params = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=list'
		];

		$instance->parse( $params );

		$this->assertTrue(
			$parserData->getSemanticData()->hasSubSemanticData( '_QUERYaa38249db4bc6d3e8133588fb08d0f0d' )
		);

		// Limit is a factor that influences the query id, count uses the
		// max limit available in $GLOBALS['smwgQMaxLimit'] therefore we set
		// the limit to make the test independent from possible other settings

		$params = [
			'[[Modification date::+]]',
			'?Modification date',
			'format=count'
		];

		$instance->parse( $params );

		$this->assertTrue(
			$parserData->getSemanticData()->hasSubSemanticData( '_QUERYb6a190747f7d3c2775730f6bc6c5e469' )
		);
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testInstantiatedQueryData( array $params, array $expected, array $settings ) {

		foreach ( $settings as $key => $value ) {
			$this->testEnvironment->addConfiguration( $key, $value );
		}

		$parserData = ApplicationFactory::getInstance()->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$this->expensiveFuncExecutionWatcher
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

	public function testEmbeddedQueryWithError() {

		$params = [
			'[[--ABC·|DEF::123]]',
			'format=table'
		];

		$expected = [
			'propertyCount'  => 2,
			'propertyKeys'   => [ '_ASK', '_ERRC' ],
		];

		$parserData = ApplicationFactory::getInstance()->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$this->expensiveFuncExecutionWatcher
		);

		$instance->parse( $params );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testWithDisabledQueryProfiler() {

		$params = [
			'[[Modification date::+]]',
			'format=table'
		];

		$expected = [
			'propertyCount'  => 0
		];

		$this->testEnvironment->addConfiguration( 'smwgQueryProfiler', false );

		$parserData = ApplicationFactory::getInstance()->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$this->expensiveFuncExecutionWatcher
		);

		$instance->parse( $params );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testNoQueryProfileOnSpecialPages() {

		$params = [
			'[[Modification date::+]]',
			'format=table'
		];

		$expected = [
			'propertyCount'  => 0
		];

		$this->testEnvironment->addConfiguration( 'smwgQueryProfiler', true );

		$parserData = ApplicationFactory::getInstance()->newParserData(
			Title::newFromText( __METHOD__, NS_SPECIAL ),
			new ParserOutput()
		);

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$this->expensiveFuncExecutionWatcher
		);

		$instance->parse( $params );

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$parserData->getSemanticData()
		);
	}

	public function testQueryWithAnnotationMarker() {

		$params = [
			'[[Modification date::+]]',
			'format=table',
			'@annotation'
		];

		$postProcHandler = $this->getMockBuilder( '\SMW\PostProcHandler' )
			->disableOriginalConstructor()
			->getMock();

		$postProcHandler->expects( $this->once() )
			->method( 'addUpdate' );

		$parserData = ApplicationFactory::getInstance()->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance = new AskParserFunction(
			$parserData,
			$this->messageFormatter,
			$this->circularReferenceGuard,
			$this->expensiveFuncExecutionWatcher
		);

		$instance->setPostProcHandler( $postProcHandler );
		$instance->parse( $params );
	}

	public function queryDataProvider() {

		$categoryNS = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );
		$fileNS = Localizer::getInstance()->getNamespaceTextById( NS_FILE );

		$provider = [];

		// #0
		// {{#ask: [[Modification date::+]]
		// |?Modification date
		// |format=list
		// }}
		$provider[] = [
			[
				'[[Modification date::+]]',
				'?Modification date',
				'format=list'
			],
			[
				'propertyCount'  => 4,
				'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ],
				'propertyValues' => [ 'list', 1, 1, '[[Modification date::+]]' ]
			],
			[
				'smwgQueryProfiler' => true
			]
		];

		// #1 Query string with spaces
		// {{#ask: [[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]
		// |?Modification date
		// |?Has title
		// |format=list
		// }}
		$provider[] = [
			[
				'[[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]',
				'?Modification date',
				'?Has title',
				'format=list'
			],
			[
				'propertyCount'  => 4,
				'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ],
				'propertyValues' => [ 'list', 4, 1, "[[Modification date::+]] [[$categoryNS:Foo bar]] [[Has title::!Foo bar]]" ]
			],
			[
				'smwgCreateProtectionRight' => false,
				'smwgQueryProfiler' => true
			]
		];

		// #2
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=list
		// }}
		$provider[] = [
			[
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=list'
			],
			[
				'propertyCount'  => 4,
				'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ],
				'propertyValues' => [ 'list', 2, 1, "[[Modification date::+]] [[$categoryNS:Foo]]" ]
			],
			[
				'smwgQueryProfiler' => true
			]
		];

		// #3 Known format
		// {{#ask: [[File:Fooo]]
		// |?Modification date
		// |default=no results
		// |format=feed
		// }}
		$provider[] = [
			[
				'[[File:Fooo]]',
				'?Modification date',
				'default=no results',
				'format=feed'
			],
			[
				'propertyCount'  => 4,
				'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ],
				'propertyValues' => [ 'feed', 1, 1, "[[:$fileNS:Fooo]]" ]
			],
			[
				'smwgQueryProfiler' => true
			]
		];

		// #4 Unknown format, default table
		// {{#ask: [[Modification date::+]][[Category:Foo]]
		// |?Modification date
		// |?Has title
		// |format=bar
		// }}
		$provider[] = [
			[
				'[[Modification date::+]][[Category:Foo]]',
				'?Modification date',
				'?Has title',
				'format=lula'
			],
			[
				'propertyCount'  => 4,
				'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ],
				'propertyValues' => [ 'table', 2, 1, "[[Modification date::+]] [[$categoryNS:Foo]]" ]
			],
			[
				'smwgQueryProfiler' => true
			]
		];

		// #6 Invalid parameters
		// {{#ask: [[Modification date::+]]
		// |?Modification date
		// |format=list
		// |someParameterWithoutValue
		// |{{{template}}}
		// |@internal
		// }}
		$provider[] = [
			[
				'[[Modification date::+]]',
				'someParameterWithoutValue',
				'{{{template}}}',
				'format=list',
				'@internal',
				'?Modification date'
			],
			[
				'propertyCount'  => 5,
				'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO', '_ASKPA' ],
				'propertyValues' => [ 'list', 1, 1, '[[Modification date::+]]', '{"limit":50,"offset":0,"sort":[""],"order":["asc"],"mode":1}' ]
			],
			[
				'smwgQueryProfiler' => SMW_QPRFL_PARAMS
			]
		];

		return $provider;
	}

}
