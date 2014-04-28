<?php namespace Prewk;

class XmlFaker
{
    const BYTE_COUNT_RESTRICTION_MODE = 0;
    const NODE_COUNT_RESTRICTION_MODE = 1;

    private $blueprint;
    private $faker;

    private $rootNode = "root-node";

    public function __construct(\SimpleXMLElement $blueprint) {
        $this->blueprint = $blueprint;
        $this->faker = \Faker\Factory::create();
    }

    private function escape($in)
    {
        return str_replace(array("&", "<", ">", "\""), array("&amp;", "&lt;", "&gt;", "&quot;"), $in);
    }

    private function getFakeValue($original) {
        if (is_numeric($original)) {
            if (strpos($original, ".") !== false) {
                return $this->faker->randomFloat();
            } else {
                return $this->faker->randomNumber(strlen($original));
            }
        } else {
            $wordCount = str_word_count($original);
            $newLineCount = substr_count($original, "\n");

            if ($newLineCount > 0 && $wordCount > 5) {
                return implode("\n", $this->faker->paragraphs($newLineCount));
            } else {
                return implode(" ", $this->faker->words($wordCount));
            }
        }
    }

    private function traverse($node)
    {
        $output = "";

        foreach ($node as $child => $childNode) {
            $attributes = $childNode->attributes();
            $children = $childNode->children();

            $fakeAttributes = array();

            foreach ($attributes as $key => $value) {
                $fakeAttributes[] = "$key=\"" . $this->escape($this->getFakeValue((string)$value)) . "\"";
            }

            $output .= count($fakeAttributes) == 0 ? "<$child>" : "<$child " . implode(" ", $fakeAttributes) . ">";
            if (count($children) > 0) {
                $output .= $this->traverse($childNode);
            } else {
                $output .= $this->escape($this->getFakeValue((string)$childNode));
            }
            $output .= "</$child>";
        }

        return $output;
    }

    private function extractFirstChild() {
        foreach ($this->blueprint as $nodeName => $node) {
            return array($node, $nodeName);
        }
    }

    private function writer($mode, $restriction, $callback)
    {
        list($bluePrintNode, $bluePrintNodeName) = $this->extractFirstChild();

        call_user_func_array($callback, array("<{$this->rootNode}>"));

        if ($mode == self::NODE_COUNT_RESTRICTION_MODE) {
            for ($i = 0; $i < $restriction; $i++) {
                $nextNode = "<$bluePrintNodeName>" . $this->traverse($bluePrintNode) . "</$bluePrintNodeName>";
                call_user_func_array($callback, array($nextNode));
            }
        } else if ($mode == self::BYTE_COUNT_RESTRICTION_MODE) {
            $byteCounter = (strlen($this->rootNode) * 2) + 5;

            while (true) {
                $nextNode = "<$bluePrintNodeName>" . $this->traverse($bluePrintNode) . "</$bluePrintNodeName>";
                $upComingByteCount = strlen($nextNode);

                if ($byteCounter + $upComingByteCount > $restriction) {
                    break;
                }

                call_user_func_array($callback, array($nextNode));

                $byteCounter += $upComingByteCount;
            }
        }

        call_user_func_array($callback, array("</{$this->rootNode}>"));
    }

    public function asString($mode, $restriction)
    {   
        list($bluePrintNode, $bluePrintNodeName) = $this->extractFirstChild();

        $outputStr = "";

        $this->writer($mode, $restriction, function($xmlData) use(&$outputStr) {
            $outputStr .= $xmlData;
        });

        return $outputStr;
    }

    public function asFile($filename, $mode, $restriction)
    {
        list($bluePrintNode, $bluePrintNodeName) = $this->extractFirstChild();

        $handle = fopen($filename, "w");

        if ($handle === false) {
            throw new \Exception("Couldn't open file handle");
        }

        $this->writer($mode, $restriction, function($xmlData) use ($handle) {
            fwrite($handle, $xmlData);
        });

        fclose($handle);
    }

    public function asStdout($mode, $restriction)
    {
        list($bluePrintNode, $bluePrintNodeName) = $this->extractFirstChild();

        $this->writer($mode, $restriction, function($xmlData) {
            fwrite(STDOUT, $xmlData);
        });
    }
}

