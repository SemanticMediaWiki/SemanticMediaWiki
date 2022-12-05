<?php

namespace SMW\Tests\ParserFunctions;

use ParserOutput;
use ReflectionClass;
use SMW\MessageFormatter;
use SMW\ParserData;
use SMW\RecurringEvents;
use SMW\ParserFunctions\RecurringEventsParserFunction;
use SMW\ParserParameterProcessor;
use SMW\Subobject;
use Title;

/**
 * @covers \SMW\ParserFunctions\RecurringEventsParserFunction
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RecurringEventsParserFunctionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$subobject = new Subobject( Title::newFromText( __METHOD__ ) );

		$parserData = $this->getMockBuilder( '\SMW\ParserData' )
			->disableOriginalConstructor()
			->getMock();

		$messageFormatter = $this->getMockBuilder( '\SMW\MessageFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			RecurringEventsParserFunction::class,
			new RecurringEventsParserFunction(
				$parserData,
				$subobject,
				$messageFormatter,
				new RecurringEvents()
			)
		);
	}

	/**
	 * @dataProvider recurringEventsDataProvider
	 */
	public function testParse( array $params, array $expected ) {

		$recurringEvents = new RecurringEvents();
		$recurringEvents->setDefaultNumRecurringEvents( 100 );
		$recurringEvents->setMaxNumRecurringEvents( 100 );

		$title = Title::newFromText( __METHOD__ );

		$instance = new RecurringEventsParserFunction(
			new ParserData( $title, new ParserOutput() ),
			new Subobject( $title ),
			new MessageFormatter( \Language::factory( 'en' ) ),
			$recurringEvents
		);

		$result = $instance->parse(
			new ParserParameterProcessor( $params )
		);

		$this->assertTrue( $result !== '' ? $expected['errors'] : !$expected['errors'] );

		$this->assertEquals(
			$expected['parameters'],
			$recurringEvents->getParameters()
		);
	}

	public function recurringEventsDataProvider() {

		$provider = [];

		// #0
		// {{#set_recurring_event:property=Has birthday
		// |start=01 Feb 1970
		// |has title= Birthday
		// |unit=year
		// |period=12
		// |limit=3
		// }}
		$provider[] = [
			[
				'property=Has birthday',
				'start=01 Feb 1970',
				'has title=Birthday',
				'unit=month',
				'period=12',
				'limit=3'
			],
			[
				'errors' => false,
				'dates' => [
					'1 February 1970',
					'1 February 1971',
					'1 February 1972',
					'1 February 1973'
				],
				'property' => [
					'Has birthday',
					'Has title'
				],
				'parameters' => [
					'has title' => [ 'Birthday' ]
				]
			]
		];

		// #1
		// {{#set_recurring_event:property=Has birthday
		// |start=01 Feb 1972 02:00
		// |has title=Test 12
		// |unit=week
		// |period=4
		// |limit=3
		// }}
		$provider[] = [
			[
				'property=Has birthday',
				'start=01 Feb 1972 02:00',
				'has title=Test 2',
				'unit=week',
				'period=4',
				'limit=3'
			],
			[
				'errors' => false,
				'dates' => [
					'1 February 1972 02:00:00',
					'29 February 1972 02:00:00',
					'28 March 1972 02:00:00',
					'25 April 1972 02:00:00'
				],
				'property' => [
					'Has birthday',
					'Has title'
				],
				'parameters' => [
					'has title' => [ 'Test 2' ]
				]
			]
		];

		// #2
		// {{#set_recurring_event:property=Has date
		// |start=January 4, 2010
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010
		// |exclude=January 18, 2010;January 25, 2010
		// }}
		$provider[] = [
			[
				'property=Has date',
				'start=January 4, 2010',
				'unit=week',
				'period=1',
				'limit=4',
				'include=March 16, 2010;March 23, 2010',
				'exclude=January 18, 2010;January 25, 2010'
			],
			[
				'errors' => false,
				'dates' => [
					'4 January 2010',
					'11 January 2010',
					'1 February 2010',
					'March 16, 2010',
					'March 23, 2010'
				],
				'property' => 'Has date',
				'parameters' => []
			]
		];


		// #3
		// {{#set_recurring_event:property=Has date
		// |start=January 4, 2010
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010|+sep=;
		// |exclude=January 18, 2010;January 25, 2010|+sep=;
		// }}
		$provider[] = [
			[
				'property=Has date',
				'start=January 4, 2010',
				'unit=week',
				'period=1',
				'limit=4',
				'include=March 16, 2010;March 23, 2010',
				'+sep=;', 'exclude=January 18, 2010;January 25, 2010',
				'+sep=;'
			],
			[
				'errors' => false,
				'dates' => [
					'4 January 2010',
					'11 January 2010',
					'1 February 2010',
					'March 16, 2010',
					'March 23, 2010'
				],
				'property' => 'Has date',
				'parameters' => []
			]
		];

		// #4 Named page reference pointer
		// {{#set_recurring_event:FooBar
		// |property=Has birthday
		// |start=January 4, 2010
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010|+sep=;
		// |exclude=January 18, 2010;January 25, 2010|+sep=;
		// }}
		$provider[] = [
			[
				'FooBar',
				'property=Has birthday',
				'start=January 4, 2010',
				'unit=week',
				'period=1',
				'limit=4',
				'include=March 16, 2010;March 23, 2010',
				'+sep=;', 'exclude=January 18, 2010;January 25, 2010',
				'+sep=;'
			],
			[
				'errors' => false,
				'dates' => [
					'4 January 2010',
					'11 January 2010',
					'1 February 2010',
					'March 16, 2010',
					'March 23, 2010'
				],
				'property' => 'Has birthday',
				'parameters' => []
			]
		];

		// #5 Simulate first parameter starting being - raising an error
		// {{#set_recurring_event:-
		// property=Has date
		// |start=January 4, 2010
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010
		// |exclude=January 18, 2010;January 25, 2010
		// }}
		$provider[] = [
			[
				'-',
				'property=Has date',
				'start=January 4, 2010',
				'unit=week',
				'period=1',
				'limit=4',
				'include=March 16, 2010;March 23, 2010',
				'exclude=January 18, 2010;January 25, 2010'
			],
			[
				'errors' => false,
				'dates' => [],
				'property' => '',
				'parameters' => []
			]
		];

		// #6 Simulate first parameter starting with - raising an error
		// {{#set_recurring_event:-Foo
		// property=Has date
		// |start=January 4, 2010
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010
		// |exclude=January 18, 2010;January 25, 2010
		// }}
		$provider[] = [
			[
				'-Foo',
				'property=Has date',
				'start=January 4, 2010',
				'unit=week',
				'period=1',
				'limit=4',
				'include=March 16, 2010;March 23, 2010',
				'exclude=January 18, 2010;January 25, 2010'
			],
			[
				'errors' => true,
				'dates' => [],
				'property' => '',
				'parameters' => []
			]
		];

		// #7 Simulate first parameter starting with a underscore raising an error
		// {{#set_recurring_event:_FooBar
		// property=Has date
		// |start=January 4, 2010
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010
		// |exclude=January 18, 2010;January 25, 2010
		// }}
		$provider[]  = [
			[
				'_FooBar',
				'property=Has date',
				'start=January 4, 2010',
				'unit=week',
				'period=1',
				'limit=4',
				'include=March 16, 2010;March 23, 2010',
				'exclude=January 18, 2010;January 25, 2010'
			],
			[
				'errors' => true,
				'dates' => [],
				'property' => '',
				'parameters' => []
			]
		];

		// #8 Simulate start date has wrong type
		// {{#set_recurring_event:property=Has date
		// |start=???
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010
		// |exclude=January 18, 2010;January 25, 2010
		// }}
		$provider[] = [
			[
				'property=Has date',
				'start=???',
				'unit=week',
				'period=1',
				'limit=4',
				'include=March 16, 2010;March 23, 2010',
				'exclude=January 18, 2010;January 25, 2010'
			],
			[
				'errors' => true,
				'dates' => [],
				'property' => '',
				'parameters' => []
			]
		];

		// #9 Simulate missing start date
		// {{#set_recurring_event:property=Has date
		// |start=
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010
		// |exclude=January 18, 2010;January 25, 2010
		// }}
		$provider[] = [
			[
				'property=Has date',
				'start=',
				'unit=week',
				'period=1',
				'limit=4',
				'include=March 16, 2010;March 23, 2010',
				'exclude=January 18, 2010;January 25, 2010'
			],
			[
				'errors' => true,
				'dates' => [],
				'property' => '',
				'parameters' => []
			]
		];

		// #10 Simulate missing property
		// {{#set_recurring_event:property=
		// |start=January 4, 2010
		// |unit=week
		// |period=1
		// |limit=4
		// |include=March 16, 2010;March 23, 2010|+sep=;
		// |exclude=January 18, 2010;January 25, 2010|+sep=;
		// }}
		$provider[] = [
			[
				'property=',
				'start=January 4, 2010',
				'unit=week', 'period=1',
				'limit=4',
				'include=March 16, 2010;March 23, 2010',
				'+sep=;',
				'exclude=January 18, 2010;January 25, 2010',
				'+sep=;'
			],
			[
				'errors' => true,
				'dates' => [],
				'property' => '',
				'parameters' => []
			]
		];

		return $provider;
	}

}
