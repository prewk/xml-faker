<?php

use Prewk\XmlFaker;

class XmlFakerTest extends PHPUnit_Framework_TestCase
{
    private function getXmlFromSimpleBlueprint()
    {
        $faker = new XmlFaker(simplexml_load_file(__dir__ . "/simpleBlueprint.xml"));
        return $faker->asString(XmlFaker::NODE_COUNT_RESTRICTION_MODE, 10);
    }

    public function testSimpleBlueprint()
    {
        $xmlString = $this->getXmlFromSimpleBlueprint();

        $xml = simplexml_load_string($xmlString);
        $counter = 0;
        foreach ($xml as $nodeName => $node) {
            $counter++;
        }

        $this->assertEquals(10, $counter, "Test if generated node count is correct");
    }

    public function testKeepRootNode()
    {
        $blueprintXml = simplexml_load_file(__dir__ . "/simpleBlueprint.xml");
        $generatedXml = simplexml_load_string($this->getXmlFromSimpleBlueprint());

        $this->assertEquals($blueprintXml->getName(), $generatedXml->getName(), "Test if generated root element name is the same as in the blueprint");


        $blueprintRootAttributes = array();
        foreach ($blueprintXml->attributes() as $key => $value) {
            $blueprintRootAttributes[$key] = (string)$value;
        }
        $generatedRootAttributes = array();
        foreach ($generatedXml->attributes() as $key => $value) {
            $generatedRootAttributes[$key] = (string)$value;
        }

        ksort($blueprintRootAttributes);
        ksort($generatedRootAttributes);

        $this->assertEquals($blueprintRootAttributes, $generatedRootAttributes, "Test if generated root element attributes are the same as in the blueprint");
    }

    public function testKeepChildNode()
    {
        $blueprintXml = simplexml_load_file(__dir__ . "/simpleBlueprint.xml");
        $generatedXml = simplexml_load_string($this->getXmlFromSimpleBlueprint());

        $blueprintNode = $blueprintXml->specialNode;
        $generatedNode = $generatedXml->specialNode;

        $this->assertEquals($blueprintNode->getName(), $generatedNode->getName(), "Test if generated child element name is the same as in the blueprint");

        $blueprintNodeAttributes = array();
        foreach ($blueprintNode->attributes() as $key => $value) {
            $blueprintNodeAttributes[$key] = (string)$value;
        }
        
        $generatedNodeAttributes = array();
        foreach ($generatedNode->attributes() as $key => $value) {
            $generatedNodeAttributes[$key] = (string)$value;
        }

        ksort($blueprintNodeAttributes);
        ksort($generatedNodeAttributes);

        $this->assertEquals($blueprintNodeAttributes, $generatedNodeAttributes, "Test if generated child element attributes are the same as in the blueprint");
    }
}
