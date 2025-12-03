<?php
require_once 'model.php';
$listaMaara = 10;

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$params = [];
$sivu = 1;
if($queryString) {
parse_str($queryString, $params);
$sivu = (int) $params['sivu'];
}
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$sqlModel = new SQLHaku();
if($_POST) {
    //var_dump($_POST);
    $id = $_POST["id"];
    $result = $sqlModel->palautaBackUp($id);
    //var_dump($result);
}

$taulunPituus =  $sqlModel->haeCount();

$all_rows = $sqlModel->haeListatus($listaMaara, $sivu, null);
$listaus = tulostaHallintaListaus($all_rows, $taulunPituus, $listaMaara, $sivu);
$linkit = tulostaLinkit($taulunPituus, $listaMaara, $url, $sivu, null);

$dom = new DOMDocument();
@$html_file = file_get_contents('template/hallintatemplate.html');
$replace_strings = ['[LISTAUS]', '[LINKIT]'];
$html_file = str_replace($replace_strings, [$listaus, $linkit], $html_file);
@$html_for_dom = mb_convert_encoding($html_file, 'HTML-ENTITIES', 'UTF-8');
@$dom->loadHTML($html_for_dom);
echo $dom->saveHTML();
?>