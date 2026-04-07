<?php
require_once 'model.php';
$linkkilomake = tulostaLinkkilomake();

$dom = new DOMDocument();
@$html_file = file_get_contents('template/linkkihakutemplate.html');
$replace_strings = ['[LINKKILOMAKE]'];
$html_file = str_replace($replace_strings, [$linkkilomake], $html_file);
@$dom->loadHTML($html_file);
echo $dom->saveHTML();