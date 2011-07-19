<?php
/**
 *
 * Template test to be filled with PHP using Selenium, e.g., IDE:
 * @author b-kaempgen
 *
 */
class SpecialAskProvidesGUIForQueries extends SeleniumTestCase {

	/**
	 * Done up-front for setup and not testing
	 */
	public function testSetup()
	{
		;
	}

	/**
	 * Each of these methods...
	 * * should have one assertion.
	 * * should start with test, e.g., testTest().
	 */
	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/Special:Ask");

			$this->assertEquals("Semantic search", $this->getTitle());
		
	}

	public function testTest2()
	{
		$this->open($this->getUrl() ."index.php/Special:Ask");

			$this->assertTrue($this->isElementPresent("q"));
		

			$this->assertTrue($this->isElementPresent("add_property"));
		

			$this->assertTrue($this->isElementPresent("link=[Add sorting condition]"));
		
		$this->select("formatSelector", "label=Enumeration");

			$this->assertTrue($this->isElementPresent("//div[@id='other_options']/div[2]"));
		
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("10000");
		$this->click("//div[@id='p-logo']/a");
		$this->waitForPageToLoad("10000");
	}

	/**
	 * Done at the end to purge the test data.
	 */
	public function testTeardown()
	{
		;
	}
}
