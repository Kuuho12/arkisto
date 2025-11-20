<!--<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="ISO-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="index.css">
    <title>Artikkeli</title>
</head>

<body>
    <div id="artikkeli">
        <h1 id="otsikko"></h1>
        <p id="aika"></p>
        <div id="sisalto"></div>
        <p id="kirjoittaja"></p>
    </div>
    <div id="uusimmat">
        </div>
        <div id="linkit">
    </div>
</body>

</html>-->

<?php
require 'model.php';
$listaMaara = 10;

$servername = "localhost";
$username = "esimerkki";
$password = "ikkremise";
$dbname = "localhost";
$tablename = "artikkelit3";

$sqlModel = new SQLModel($servername, $username, $password, $dbname, $tablename);

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$params = [];
parse_str($queryString, $params);
$id = $params['id'];
$sivu = 1;
if(preg_match('/\bsivu\b/', $queryString)) {
$sivu = (int) $params['sivu'];
}

$row = $sqlModel->haeArtikkeliIdlla($id);
ob_start();
require_once 'views/tulostaArtikkeli.php'; 
$artikkeli = ob_get_clean();

$all_rows = $sqlModel->haeListatus($listaMaara, $sivu);
ob_start();
require_once 'views/tulostaListatus.php';
$listaus = ob_get_clean();

$taulunPituus = $sqlModel->haeCount();
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
ob_start();
require_once 'views/tulostaLinkit.php';
$linkit = ob_get_clean();

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
/*$dom = new DOMDocument();
@$dom->loadHTML($html_string);*/
$dom = new DOMDocument();
@$html_for_dom = mb_convert_encoding($html_string, 'HTML-ENTITIES', 'UTF-8');
@$dom->loadHTML($html_for_dom);
echo $dom->saveHTML();
/*$otsikkoElement = $dom->getElementById("otsikko");
$aikaElement = $dom->getElementById("aika");
$sisaltoElement = $dom->getElementById("sisalto");
$kirjoittajaElement = $dom->getElementById("kirjoittaja");
$uusimmatElement =$dom->getElementById("uusimmat");
$linkitElement = $dom->getElementById("linkit");*/

/*require_once 'view.php';
$view = new View($dom);

$row = $sqlModel->haeArtikkeliIdlla($id);
$view->tulostaArtikkeli($row);

$all_rows = $sqlModel->haeListatus($listaMaara, $sivu);
$view->tulostaListaus($sivu, $all_rows);

$taulunPituus = $sqlModel->haeCount();
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$view->tulostaLinkit($url, $taulunPituus, $listaMaara, $sivu, $id);

$view->tulostaDom();*/
/*    $newDate = date_create($row["aika"]);
    $otsikkoElement->nodeValue = $row["otsikko"];
    $aikaElement->nodeValue = (string) $newDate->format('Y-m-d H:i');
    $kirjoittajaElement->nodeValue = $row["kirjoittaja"];

    $temp_dom = new DOMDocument('1.0', 'utf-8');
    
    libxml_use_internal_errors(true);
    
    if (!$temp_dom->loadXML('<root>' . $row["sisalto"] . '</root>')) {
        libxml_clear_errors();
        return false;
    }

    $temp_root = $temp_dom->getElementsByTagName('root')->item(0);

    $nodes_to_append = [];
    foreach ($temp_root->childNodes as $node) {
        $nodes_to_append[] = $node;
    }
    foreach ($nodes_to_append as $node) {
        $imported_node = $dom->importNode($node, true); 
        $sisaltoElement->appendChild($imported_node);
    }
*/
/*
for($x = 1; $x < count($all_rows) + 1; $x++) {
    $uusinDiv = $dom->createElement('div');
    $uusinDiv->setAttribute("id", "uusin$x");
    $uusimmatElement->appendChild($uusinDiv);
    $uusinOtskko = $dom->createElement('p');
    $uusinOtskko->setAttribute("id", "uusin" . $x . "otsikko");
    $uusinAika = $dom->createElement('p');
    $uusinAika->setAttribute("id", "uusin" . $x . "aika");
    $uusinSisalto = $dom->createElement('p');
    $uusinSisalto->setAttribute("id", "uusin" . $x . "sisalto");
    $uusinLinkki = $dom->createElement('a');
    $uusinLinkki->setAttribute("id", "uusin" . $x . "linkki");
    $uusinDiv->appendChild($uusinOtskko);
    $uusinDiv->appendChild($uusinAika);
    $uusinDiv->appendChild($uusinSisalto);
    $uusinDiv->appendChild($uusinLinkki);

    $index = $x-1;
    $row = $all_rows[$index];
    $uusinOtskko->textContent = $row["otsikko"];
    $newDate = date_create($row["aika"]);
    $uusinAika->nodeValue = (string) $newDate->format('Y-m-d H:i');
    $sisaltoXML = simplexml_load_string($row["sisalto"]);
    $uusinSisalto->nodeValue = substr($sisaltoXML->p[0], 0, 120) . "...";
    $uusinLinkki->nodeValue = "Lue lisää";
    $uusinLinkki->setAttribute(
    'href',
    'http://localhost/blogitehtava/artikkeli.php?' . http_build_query(['id' => (string)$row['id'], 'sivu' => $sivu])
    );
}*/

/*
$y = 0;
for($x = $taulunPituus; $x > 0; $x-=$listaMaara) {
    $y++;
    $uusiLinkkiElement = $dom->createElement('a');
    $uusiLinkkiElement->setAttribute("id", "linkki$y");
    $uusiLinkkiElement->setAttribute(
        'href',
        'http://localhost/blogitehtava/artikkeli.php?' . http_build_query(['id' => $id, 'sivu' => $y])
    );
    $uusiLinkkiElement->textContent = "$y";
    $linkitElement->appendChild($uusiLinkkiElement);
}
$nykyinenLinkki = $dom->getElementById("linkki" . $sivu);
$nykyinenLinkki->removeAttribute("href");
$nykyinenLinkki->className = "bold";
*/
?>