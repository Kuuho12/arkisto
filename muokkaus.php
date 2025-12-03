<?php

use Dom\Document;

require_once 'model.php';
$dom = new DOMDocument();

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$params = [];
parse_str($queryString, $params);
$id = (int) $params['id'];
//$id = $_POST["id"];
$result = NULL;

if($_POST) {
    /*$dom2 = new DOMDocument();
    libxml_use_internal_errors(true); 
    @$html_for_dom2 = mb_convert_encoding($_POST["sisalto"], 'HTML-ENTITIES', 'UTF-8');
    $dom->loadHTML($html_for_dom2, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    
    $xml_string = $dom->saveXML($dom->documentElement, LIBXML_NOEMPTYTAG);
    var_dump($xml_string);
    file_put_contents('temp_ai/xml_' . $id . '.txt', $xml_string);*/
    //var_dump($_POST["sisalto"]);
    $sqlModel = new SQLHaku();
    $result = $sqlModel->muokkaaTaulua($id, $_POST["aika"], $_POST["otsikko"], $_POST["sisalto"], $_POST["kirjoittaja"]);
}

$sqlModel = new SQLHaku();
$row = $sqlModel->haeArtikkeliIdlla($id);

//var_dump($row["sisalto"]);

$artikkeli = tulostaMuokkausArtikkeli($row, $id, $result);
/*$file = fopen('temp_ai/check_' . $id . '.txt', 'w');
fwrite($file, $row["sisalto"]);
$row2 = $sqlModel->haeBackUpArtikkeli($id);
$file2 = fopen('temp_ai/backup_' . $id . '.txt', 'w');
fwrite($file2, $row2["sisalto"]);*/


@$html_file = file_get_contents('template/muokkaustemplate.html');
$replace_strings = ['[ARTIKKELI]'];
$html_file = str_replace($replace_strings, [$artikkeli], $html_file);
@$html_for_dom = mb_convert_encoding($html_file, 'HTML-ENTITIES', 'UTF-8');
@$dom->loadHTML($html_for_dom);
echo $dom->saveHTML();
?>