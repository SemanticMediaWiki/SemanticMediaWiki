<?php
/**
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class SearchByPropertyWithStringValueTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "FirstPageToMatch");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=FirstPageToMatch");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[TestSBP::Yes]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "SecondPageToMatch");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=SecondPageToMatch");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[TestSBP::Yes]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/Special:SearchByProperty");
		$this->type("property", "TestSBP");
		$this->type("value", "Yes");
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("30000");

			$this->assertEquals("TestSBP Yes", $this->getText("firstHeading"));


			$this->assertTrue($this->isElementPresent("link=FirstPageToMatch"));


			$this->assertTrue($this->isElementPresent("link=SecondPageToMatch"));


			$this->assertEquals("TestSBP", $this->getValue("property"));


			$this->assertEquals("Yes", $this->getValue("value"));

	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/FirstPageToMatch");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "SecondPageToMatch");
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
