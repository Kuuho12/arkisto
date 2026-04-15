<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: kirjautuminen.php');
    exit;
}

require_once 'model.php';
$tekoalytestaus = tulostaTekoalytestaus();

$dom = new DOMDocument();
@$html_file = file_get_contents('template\testisivutemplate.html');
$replace_strings = ['[TEKOALYTESTAUS]'];
$html_file = str_replace($replace_strings, [$tekoalytestaus], $html_file);
@$dom->loadHTML($html_file);
echo $dom->saveHTML();