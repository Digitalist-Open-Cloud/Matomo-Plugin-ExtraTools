<?php

namespace Piwik\Plugins\ExtraTools\Lib;

use Symfony\Component\Console\Output\OutputInterface;

class OutPutFormats
{


    public function text($text, OutputInterface $output)
    {
        echo "foo";
        //$this->output->write("<info>$text</info>");
    }
}
