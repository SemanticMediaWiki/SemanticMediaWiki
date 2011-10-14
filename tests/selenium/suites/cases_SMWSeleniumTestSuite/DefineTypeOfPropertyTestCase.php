<?php
/**
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class DefineTypeOfPropertyTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Property:TestNumber");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=exact:Property:TestNumber");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "Test property.");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "ChangeType");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=ChangeType");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[TestNumber::123456]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");

			$this->assertTrue($this->isElementPresent("link=123456"));

	}

	public function test_hastype_DefineTypeOfProperty()
	{
		$this->open($this->getUrl() ."index.php/Property:TestNumber");
		$this->click("link=Edit");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "Test property.\n[[Has type::Number]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function test_verifynolink_DefineTypeOfProperty()
	{
		$this->open($this->getUrl() ."index.php/Property:TestNumber");

			$this->assertFalse($this->isElementPresent("link=123456"));

		$this->click("link=ChangeType");
		$this->waitForPageToLoad("30000");
		$this->click("link=Refresh");
		$this->waitForPageToLoad("30000");

			$this->assertFalse($this->isElementPresent("link=123456"));

	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/ChangeType");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Property:TestNumber");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("30000");
	}
}
