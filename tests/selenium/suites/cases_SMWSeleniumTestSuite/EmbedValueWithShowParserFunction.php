<?php
/*
 * This test case is part of the SimpleSeleniumTestSuite.
 * Configuration for these tests are documented as part of SimpleSeleniumTestSuite.php
 */
class EmbedValueWithShowParserFunction extends SeleniumTestCase {


	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/TC_Embed_value_with_show_parser_function");
		$this->type("searchInput", "PleaseShowMyValues");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=PleaseShowMyValues");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[EmbedThisValue::succesful]]\n[[EmbedThisValue::prosperous]]");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
	}

	public function test01()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "EmbedValueToThisTestPage");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=EmbedValueToThisTestPage");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "{{#show: PleaseShowMyValues | ?EmbedThisValue}}");
		$this->type("wpTextbox1", "This test was {{#show: PleaseShowMyValues | ?EmbedThisValue}}.");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
	}

	public function test02()
	{
		$this->open($this->getUrl() ."index.php/EmbedValueToThisTestPage");

			$this->assertTrue($this->isElementPresent("link=Succesful"));
		

			$this->assertTrue($this->isElementPresent("link=Prosperous"));
		
	}

	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/EmbedValueToThisTestPage");
		$this->click("//div[@id='p-cactions']/h5/a");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->type("searchInput", "PleaseShowMyValues");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("10000");
	}
}
