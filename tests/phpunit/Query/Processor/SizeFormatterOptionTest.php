<?php

namespace SMW\Tests\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\SizeFormatterOption;

class SizeFormatterOptionTest extends TestCase {

    /**
     * Test the addPrintRequestHandleParams method
     */
    public function testAddPrintRequestHandleParams() {
        $formatter = new SizeFormatterOption();

        // Test case 1: check width
        $serialization = [
            'printouts' => [
                'Main Image' => [
                    'label' => 'Main Image'
                ],
            ],
        ];
        $result = $formatter->addPrintRequestHandleParams( 'Main Image', '+width=50px', 'Main Image', $serialization );

        $expectedSerialization = [
            'printouts' => [
                'Main Image' => [
                    'label' => 'Main Image #50px', 
                    'params' => []
                ],
            ],
        ];
        $this->assertEquals( $expectedSerialization, $result['serialization'] );

        // Test case 2: check height
        $serialization = [
            'printouts' => [
                'Main Image' => [
                    'label' => 'Main Image'
                ],
            ],
        ];
        $result = $formatter->addPrintRequestHandleParams( 'Main Image', '+height=90px', 'Main Image', $serialization );

        $expectedSerialization = [
            'printouts' => [
                'Main Image' => [
                    'label' => 'Main Image #x90px', 
                    'params' => []
                ],
            ],
        ];
        $this->assertEquals( $expectedSerialization, $result['serialization'] );
    }
}