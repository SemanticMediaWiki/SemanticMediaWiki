<?php

namespace SMW\Test;

use SMW\Tests\Util\UtilityFactory;

use SMW\Application;
use SMW\AskParserFunction;

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

	private $application;
	private $semanticDataValidator;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();

		$this->application = Application::getInstance();
		$this->application->getSettings()->set( 'smwgQueryDurationEnabled', false );
	}

	protected function tearDown() {
		$this->application->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\AskParserFunction',
			new AskParserFunction( $parserData, $messageFormatter )
		);
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testParse( array $params, array $expected ) {

		$parserData = $this->application->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AskParserFunction(
			$parserData,
			$messageFormatter
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

		$instance = new AskParserFunction(
			$parserData,
			$messageFormatter
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

		$instance = new AskParserFunction( $parserData, $messageFormatter );

		$reflector = new ReflectionClass( '\SMW\AskParserFunction' );
		$showMode = $reflector->getProperty( 'showMode' );
		$showMode->setAccessible( true );

		$this->assertFalse( $showMode->getValue( $instance ) );
		$instance->setShowMode( true );

		$this->assertTrue( $showMode->getValue( $instance ) );
	}

	/**
	 * @dataProvider queryDataProvider
	 */
	public function testInstantiatedQueryData( array $params, array $expected, array $settings ) {

		foreach ( $settings as $key => $value ) {
			$this->application->getSettings()->set( $key, $value );
		}

		$parserData = $this->application->newParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new AskParserFunction(
			$parserData,
			$messageFormatter
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
				'propertyValues' => array( 'list', 4, 1, '[[Modification date::+]] [[Category:Foo bar]] [[Has title::!Foo bar]]' )
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
				'propertyValues' => array( 'list', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
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
				'propertyValues' => array( 'feed', 1, 1, '[[:File:Fooo]]' )
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
				'propertyValues' => array( 'table', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
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
