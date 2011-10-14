<?php
/**
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class CreatePropertyPageTestCase extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "Property:Create1");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=exact:Property:Create1");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "This is a test property.");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "CreateTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=CreateTest");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "[[Create1::Yes]]\n[[Create2::Yes]]\n__SHOWFACTBOX__");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function test_checkfactbox_CreatePropertyPage()
	{
		$this->open($this->getUrl() ."index.php/CreateTest");

			$this->assertTrue($this->isElementPresent("link=Create1"));


			$this->assertTrue($this->isElementPresent("link=Create2"));

	}

	public function test_checkpropertypages_CreatePropertyPage()
	{
		$this->open($this->getUrl() ."index.php/Property:Create1");

			$this->assertTrue($this->isElementPresent("link=CreateTest"));

		$this->open($this->getUrl() ."index.php/Property:Create2");

			$this->assertTrue($this->isElementPresent("link=CreateTest"));

	}

	public function test_redlink_CreatePropertyPage()
	{
		$this->open($this->getUrl() ."index.php/CreateTest");
		$this->click("link=Create2");
		$this->waitForPageToLoad("30000");
		$this->type("wpTextbox1", "This is also a test property.");
		$this->click("wpSave");
		$this->waitForPageToLoad("30000");
	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/Property:Create1");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "Property:Create2");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->type("searchInput", "CreateTest");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("30000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("30000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("30000");
		$this->click("//div[@id='p-logo']/a");
		$this->waitForPageToLoad("30000");
	}
}
