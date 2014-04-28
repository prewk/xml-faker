<?php

use Prewk\XmlFaker;

class XmlFakerTest extends PHPUnit_Framework_TestCase
{
	public function testSimpleBlueprint()
	{
		$faker = new XmlFaker(simplexml_load_file(__dir__ . "/simpleBlueprint.xml"));
		$xmlString = $faker->asString(XmlFaker::NODE_COUNT_RESTRICTION_MODE, 10);

		$xml = simplexml_load_string($xmlString);
		$counter = 0;
		foreach ($xml as $nodeName => $node) {
			$counter++;
		}

		$this->assertEquals(10, $counter);
	}
}
