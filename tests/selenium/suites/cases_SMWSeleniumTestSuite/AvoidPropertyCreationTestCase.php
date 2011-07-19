<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class AvoidPropertyCreationTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "AvoidPropertyCreationTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=AvoidPropertyCreationTest");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "This is a test page.\n\n[[:Avoid::Property]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/AvoidPropertyCreationTest");

			$this->assertEquals("Avoid::Property", $this->getText("link=exact:Avoid::Property"));
		
	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/AvoidPropertyCreationTest");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("30000");
	}
}
