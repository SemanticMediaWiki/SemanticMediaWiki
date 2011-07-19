<?php
/**
 *
 * Template test to be filled with PHP using Selenium, e.g., IDE:
 * @author b-kaempgen
 *
 */
class FactboxLinksToSearchByProperty extends SeleniumTestCase {

	/**
	 * Done up-front for setup and not testing
	 */
	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Main_Page");
		$this->type("searchInput", "TestPerson James Orange");
		$this->click("searchGoButton");
		$this->waitForPageToLoad("10000");
		$this->click("link=TestPerson James Orange");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "[[Has dad::TestPerson Mike Orange]]\n[[Has mum::TestPerson Sandra Red-Orange]]\n[[Has half sister::TestPerson Olivia Red]]\n[[Has half sister::TestPerson Michelle Orange]]\n__SHOWFACTBOX__");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
	}

	/**
	 * Each of these methods...
	 * * should have one assertion.
	 * * should start with test, e.g., testTest().
	 */
	public function testTest()
	{
		$this->open($this->getUrl() ."index.php/TestPerson_James_Orange");
		$this->click("//div[@id='mw-data-after-content']/div/table/tbody/tr[2]/td[2]/span[1]/a");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isTextPresent("A list of all pages that have property \"Has half sister\" with value \"TestPerson Olivia Red\""));
		
		$this->click("link=TestPerson James Orange");
		$this->waitForPageToLoad("10000");
		$this->click("link=+");
		$this->waitForPageToLoad("10000");

			$this->assertTrue($this->isTextPresent("A list of all pages that have property \"Has dad\" with value \"TestPerson Mike Orange\""));
		
	}

	/**
	 * Done at the end to purge the test data.
	 */
	public function testTeardown()
	{
		$this->open($this->getUrl() ."index.php/TestPerson_James_Orange");
		$this->click("link=Delete");
		$this->waitForPageToLoad("10000");
		$this->click("wpConfirmB");
		$this->waitForPageToLoad("10000");
		$this->click("link=Main Page");
		$this->waitForPageToLoad("10000");
	}
}
