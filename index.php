<?php

require_once 'class/SMFLocaleClass.php';

$file = new SMFLocale();

//folders
$english    = 'english';
$input =  'translation';
$output = 'output';

$file->completeTranslation($english , $input, $output)


?>