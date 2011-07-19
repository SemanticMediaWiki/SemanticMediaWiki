<?php
/**
 *
 * Template test to be filled with PHP using Selenium, e.g., IDE:
 * @author b-kaempgen
 *
 */
class CopyQuerySyntaxFromSpecialAsk extends SeleniumTestCase {

	/**
	 * Done up-front for setup and not testing
	 */
	public function testSetup()
	{
		$this->open($this->getUrl() ."index.php/Special:Ask");
		$this->type("q", "[[");
		$this->type("q", "[[We want::to test]]\n[[The query::syntax]]\n[[Category:Test case]]");
		$this->type("add_property", "?Question\n?Mark");
		$this->click("link=[Add sorting condition]");
		$this->type("sort[0]", "?Mark");
		$this->select("formatSelector", "label=Table");
		$this->type("p[limit]", "50");
		$this->select("p[headers]", "label=show");
		$this->type("p[mainlabel]", "Test table");
		$this->type("p[default]", "Test default");
		$this->click("//input[@value='Find results']");
		$this->waitForPageToLoad("10000");
	}

	/**
	 * Each of these methods...
	 * * should have one assertion.
	 * * should start with test, e.g., testTest().
	 */
	public function testTest()
	{
		$this->click("link=Show embed code");

			$this->assertTrue($this->isTextPresent("exact:{{#ask:[[We want::to test]] [[The query::syntax]] [[Category:Test case]] |?Question |?Mark |format=broadtable |limit=50 |headers=show |mainlabel=Test table |link=all |default=Test default |order=ASC |sort=?Mark |offset=0 }}"));
		
	}

	/**
	 * Done at the end to purge the test data.
	 */
	public function testTeardown()
	{
		$this->click("//div[@id='p-logo']/a");
		$this->waitForPageToLoad("10000");
	}
}
