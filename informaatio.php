<?php
require_once 'model.php';

$sqlModel = new SQLHaku();

$taulunPituus =  $sqlModel->haeCount();
$taulunInformaatio = $sqlModel->haeTaulunInformaatio();
$taulunMerkisto = $sqlModel->haeTaulunMerkisto();

$informaatioTulostus = tulostaInformaatio($taulunPituus, $taulunInformaatio, $taulunMerkisto);

$dom = new DOMDocument();
@$html_file = file_get_contents('template/informaatiotemplate.html');
$replace_strings = ['[INFORMAATIO]'];
$html_file = str_replace($replace_strings, [$informaatioTulostus], $html_file);
@$html_for_dom = mb_convert_encoding($html_file, 'HTML-ENTITIES', 'UTF-8');
@$dom->loadHTML($html_for_dom);
echo $dom->saveHTML();
?>