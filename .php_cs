<?php

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules(array(
        '@PSR2' => true
    ))
    ->setFinder(
        PhpCsFixer\Finder::create()->in('src')
    );
