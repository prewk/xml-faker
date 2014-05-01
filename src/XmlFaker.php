<?php namespace Prewk;

class XmlFaker
{
    const BYTE_COUNT_RESTRICTION_MODE = 0;
    const NODE_COUNT_RESTRICTION_MODE = 1;
    const NODE_CLONE_MODE = 2;

    private $blueprint;
    private $faker;

    private $rootNode;

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
            $flattenedAttributes = array();
            foreach ($node->attributes() as $key => $value) {
                $flattenedAttributes[] = "$key=\"" . $this->escape((string)$value) . "\"";
            }
            return array($node, $nodeName, $flattenedAttributes);
        }
    }

    private function writer($mode, $restriction, $callback)
    {
        list($bluePrintNode, $bluePrintNodeName, $bluePrintNodeAttributes) = $this->extractFirstChild();

        // Set up root node
        $this->rootNode = $this->blueprint->getName();
        $rootAttributes = array();
        foreach ($this->blueprint->attributes() as $key => $value) {
            $rootAttributes[] = "$key=\"" . $this->escape((string)$value) . "\"";
        }

        if (count($rootAttributes) > 0) {
            $rootNodeStr = "<{$this->rootNode} " . implode(" ", $rootAttributes) . ">";
        } else {
            $rootNodeStr = "<{$this->rootNode}>";
        }
        call_user_func_array($callback, array($rootNodeStr));

        if (self::NODE_COUNT_RESTRICTION_MODE === $mode) {
            for ($i = 0; $i < $restriction; $i++) {
                if (count($bluePrintNodeAttributes) > 0) {
                    $nodeOpeningElem = "<$bluePrintNodeName " . implode(" ", $bluePrintNodeAttributes) . ">";
                } else {
                    $nodeOpeningElem = "<$bluePrintNodeName>";
                }

                $nextNode = $nodeOpeningElem . $this->traverse($bluePrintNode) . "</$bluePrintNodeName>";
                call_user_func_array($callback, array($nextNode));
            }
        } else if (self::BYTE_COUNT_RESTRICTION_MODE === $mode) {
            $byteCounter = (strlen($this->rootNode) * 2) + 5;

            while (true) {
                if (count($bluePrintNodeAttributes) > 0) {
                    $nodeOpeningElem = "<$bluePrintNodeName " . implode(" ", $bluePrintNodeAttributes) . ">";
                } else {
                    $nodeOpeningElem = "<$bluePrintNodeName>";
                }

                $nextNode = $nodeOpeningElem . $this->traverse($bluePrintNode) . "</$bluePrintNodeName>";
                $upComingByteCount = strlen($nextNode);

                if ($byteCounter + $upComingByteCount > $restriction) {
                    break;
                }

                call_user_func_array($callback, array($nextNode));

                $byteCounter += $upComingByteCount;
            }
        } else if (self::NODE_CLONE_MODE === $mode) {
            if (count($bluePrintNodeAttributes) > 0) {
                $nodeOpeningElem = "<$bluePrintNodeName " . implode(" ", $bluePrintNodeAttributes) . ">";
            } else {
                $nodeOpeningElem = "<$bluePrintNodeName>";
            }
            $clonee = $nodeOpeningElem . $this->traverse($bluePrintNode) . "</$bluePrintNodeName>";
            for ($i = 0; $i < $restriction; $i++) {
                call_user_func_array($callback, array($clonee));
            }
        }

        call_user_func_array($callback, array("</{$this->rootNode}>"));
    }

    public function asString($mode, $restriction)
    {
        $outputStr = "";

        $this->writer($mode, $restriction, function($xmlData) use(&$outputStr) {
            $outputStr .= $xmlData;
        });

        return $outputStr;
    }

    public function asFile($filename, $mode, $restriction)
    {
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
        $this->writer($mode, $restriction, function($xmlData) {
            fwrite(STDOUT, $xmlData);
        });
    }
}

