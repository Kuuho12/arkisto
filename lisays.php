<?php
require_once 'model.php';
$dom = new DOMDocument();
$result = NULL;
$sqlModel = new SQLHaku();
if($_POST) {
    $result0 = $sqlModel->lisaaArtikkeli($_POST["aika"], $_POST["otsikko"], $_POST["sisalto"], $_POST["kirjoittaja"]);
    if ($result0[0] and $result0[1]) {
        $result = true;
    }
    else {
        $result = false;
        var_dump($result0);
    }
}

$sqlModel = new SQLHaku();

$artikkeli = tulostaLisaysArtikkeli($result);

@$html_file = file_get_contents('template/lisaystemplate.html');
$replace_strings = ['[ARTIKKELI]'];
$html_file = str_replace($replace_strings, [$artikkeli], $html_file);
@$html_for_dom = mb_convert_encoding($html_file, 'HTML-ENTITIES', 'UTF-8');
@$dom->loadHTML($html_for_dom);
echo $dom->saveHTML();
?>