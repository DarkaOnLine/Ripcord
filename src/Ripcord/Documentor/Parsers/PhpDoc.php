<?php

namespace Ripcord\Parsers;

use Ripcord\Documentator\Contracts\Parser;

/**
 * This class implements the Ripcord_Documentor_Parser interface, parsing the docComment
 * as a phpdoc style docComment.
 */
class PhpDoc implements Parser
{
    public $currentTag;

    /**
     * This method parses a given docComment block and returns an array with information.
     *
     * @param string $commentBlock The docComment block.
     *
     * @return array The parsed information.
     */
    public function parse($commentBlock)
    {
        $this->currentTag = 'description';
        $description = preg_replace('/^(\s*(\/\*\*|\*\/|\*))/m', '', $commentBlock);
        $info = [];
        $lines = explode("\n", $description);
        foreach ($lines as $line) {
            $info = $this->parseLine($line, $info);
        }

        return $info; //array( 'description' => $description );
    }

    /**
     * This method parses a single line from the comment block.
     *
     * @param $line
     * @param $info
     *
     * @return
     */
    private function parseLine($line, $info)
    {
        $handled = false;
        if (preg_match('/^\s*(@[a-z]+)\s(.*)$/i', $line, $matches)) {
            $this->currentTag = substr($matches[1], 1);
            $line = trim(substr($line, strlen($this->currentTag) + 2));
            switch ($this->currentTag) {
                case 'param':
                    if (preg_match('/^\s*([[:alpha:]|]+)\s([[:alnum:]$_]+)(.*)$/i', $line, $matches)) {
                        $info['arguments'][$matches[2]]['type'] = $matches[1];
                        $info['arguments'][$matches[2]]['description'] .= $this->parseDescription($matches[3]);
                    }
                    $handled = true;
                    break;
                case 'return':
                    if (preg_match('/^\s*([[:alpha:]|]+)\s(.*)$/i', $line, $matches)) {
                        $info['return']['type'] = $matches[1];
                        $info['return']['description'] .= $this->parseDescription($matches[2]);
                    }
                    $handled = true;
                    break;
            }
        }
        if (!$handled) {
            switch ($this->currentTag) {
                case 'param':
                case 'return':
                    $info[$this->currentTag]['description'] .= $this->parseDescription($line);
                    break;
                default:
                    $info[$this->currentTag] .= $this->parseDescription($line);
                    break;
            }
        }

        return $info;
    }

    /**
     * This method parses only the text description part of a line of the comment block.
     *
     * @param $line
     *
     * @return mixed
     */
    private function parseDescription($line)
    {
        while (preg_match('/{@([^}]*)}/', $line, $matches)) {
            switch ($matches[1]) {
                case 'internal':
                    $line = str_replace($matches[0], '', $line);
                    break;
                default:
                    $line = str_replace($matches[0], $matches[1], $line);
                    break;
            }
        }
        $line = str_replace(['\@', '{@*}'], ['@', '*/'], $line);

        return $line;
    }
}
