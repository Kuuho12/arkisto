<?php
require_once 'model.php';
$chatIkkuna = tulostaChattiIkkuna();

$dom = new DOMDocument();
@$html_file = file_get_contents('template/chattitemplate.html');
$replace_strings = ['[CHATIKKUNA]'];
$html_file = str_replace($replace_strings, [$chatIkkuna], $html_file);
@$dom->loadHTML($html_file);
echo $dom->saveHTML();