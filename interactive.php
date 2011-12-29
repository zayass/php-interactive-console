#!/usr/bin/php
<?php
include 'interactive_base.php';

$console = new PHPInteractiveConsole();

// если у вас есть список классов, то добавить их к автокомплиту можно так:
// $classes = array('Classname1','Classname2','Classname3');
// $console->addToAutoComplete($classes, 'class-user');

$console->run();

?>
