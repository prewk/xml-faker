xml-faker [![Build Status](https://travis-ci.org/prewk/xml-faker.svg?branch=0.0.7)](https://travis-ci.org/prewk/xml-faker)
=========

Create fake XML with the help of [fzaninotto/Faker](https://github.com/fzaninotto/Faker)

Why?
----

Useful for testing stuff.

Usage
-----

Supply the constructor with a valid `SimpleXMLElement` and use the methods to generate XML in the sizes that you wish.

Your supplied XML will be used as a blueprint for creating the random XML. If an attribute as an integer, random integers will be created for those attributes. If your node text consists of a text with linebreaks, a random text with the same amount of linebreaks will be created for those nodes.

Only the first node encountered will be used like this, the rest of your supplied XML will be ignored.

Installation
------------

composer.json:

    "require": {
        "prewk/xml-faker": "*"
    }

Example
-------

Create an example.xml:

    <root-node>
      <node>
        <child-a>OneWord</child-a>
        <child-b>123</child-b>
        <child-c an-integer-attribute="123">
          <grandchild an-float-attribute="456">
            This is some
            text on some
            lines
          </grandchild>
        </child-c>
      </node>
    </root-node>
    
Load it:

    $myXmlBlueprint = simplexml_load_file("example.xml");
    $xmlFaker = new Prewk\XmlFaker($myXmlBlueprint);

Create an XML string with 100 nodes:

    echo $xmlFaker->asString(Prewk\XmlFaker::NODE_COUNT_RESTRICTION_MODE, 100);

Create an XML string of maximum 1 MB (1024 * 1024 chars):

    echo $xmlFaker->asString(Prewk\XmlFaker::BYTE_COUNT_RESTRICTION_MODE, 1024 * 1024);
    
More useful, stream an XML file of 100 MB to disk (will take a while):

    $xmlFaker->asFile("my-new-large-xml-file.xml", Prewk\XmlFaker::BYTE_COUNT_RESTRICTION_MODE, 100 * 1024 * 1024);