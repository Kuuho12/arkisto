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

$sqlModel = new SQLHaku();

$taulunPituus = $sqlModel->haeCount();
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$all_rows = $sqlModel->haeListatus($listaMaara, $sivu, null);

$tulostukset = tulostaListausJaLinkit($all_rows, $taulunPituus, $listaMaara, $url, $sivu, null);
$listaus = $tulostukset['listaus'];
$linkit = $tulostukset['linkit'];

$html_string = '<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="ISO-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="index.css">
    <title>Arkisto</title>
</head>

<body>
    <div>
        <div id="uusimmat"> ' . $listaus . '
        </div>
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