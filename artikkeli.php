<?php
require 'model.php';
$listaMaara = 10;

$sqlModel = new SQLHaku();

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$params = [];
parse_str($queryString, $params);
$id = $params['id'];
$sivu = 1;
if(preg_match('/\bsivu\b/', $queryString)) {
$sivu = (int) $params['sivu'];
}

$row = $sqlModel->haeArtikkeliIdlla($id);
$artikkeli = tulostaArtikkeli($row);

$all_rows = $sqlModel->haeListatus($listaMaara, $sivu, $id);
$taulunPituus = $sqlModel->haeCount();
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$tulostukset = tulostaListausJaLinkit($all_rows, $taulunPituus, $listaMaara, $url, $sivu, $id);
$listaus = $tulostukset['listaus'];
$linkit = $tulostukset['linkit'];

$html_string = '<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="ISO-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="index.css">
    <title>Artikkeli</title>
</head>

<body>
    <div id="artikkeli"> ' . $artikkeli . '
    </div>
    <div id="uusimmat"> ' . $listaus . '
        </div>
        <div id="linkit"> '. $linkit . '
    </div>
</body>

</html>';
$dom = new DOMDocument();
@$html_for_dom = mb_convert_encoding($html_string, 'HTML-ENTITIES', 'UTF-8');
@$dom->loadHTML($html_for_dom);
echo $dom->saveHTML();
?>