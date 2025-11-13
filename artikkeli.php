<!--<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="ISO-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <div>
        <h1 id="otsikko"></h1>
        <p id="aika"></p>
        <p id="sisalto"></p>
        <p id="kirjoittaja"></p>
    </div>
</body>

</html>-->

<?php
$html_string = '<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="ISO-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="index.css">
    <title>Document</title>
</head>

<body>
    <div id="artikkeli">
        <h1 id="otsikko"></h1>
        <p id="aika"></p>
        <p id="sisalto"></p>
        <p id="kirjoittaja"></p>
    </div>
    <div id="uusimmat">
            <div id="uusin1">
                <p id="uusin1otsikko"></p>
                <p id="uusin1aika"></p>
                <p id="uusin1sisalto"></p>
                <a id="uusin1linkki" href=""></a>
            </div>
            <div id="uusin2">
                <p id="uusin2otsikko"></p>
                <p id="uusin2aika"></p>
                <p id="uusin2sisalto"></p>
                <a id="uusin2linkki" href=""></a>
            </div>
            <div id="uusin3">
                <p id="uusin3otsikko"></p>
                <p id="uusin3aika"></p>
                <p id="uusin3sisalto"></p>
                <a id="uusin3linkki" href=""></a>
            </div>
            <div id="uusin4">
                <p id="uusin4otsikko"></p>
                <p id="uusin4aika"></p>
                <p id="uusin4sisalto"></p>
                <a id="uusin4linkki" href=""></a>
            </div>
            <div id="uusin5">
                <p id="uusin5otsikko"></p>
                <p id="uusin5aika"></p>
                <p id="uusin5sisalto"></p>
                <a id="uusin5linkki" href=""></a>
            </div>
            <div id="uusin6">
                <p id="uusin6otsikko"></p>
                <p id="uusin6aika"></p>
                <p id="uusin6sisalto"></p>
                <a id="uusin6linkki" href=""></a>
            </div>
            <div id="uusin7">
                <p id="uusin7otsikko"></p>
                <p id="uusin7aika"></p>
                <p id="uusin7sisalto"></p>
                <a id="uusin7linkki" href=""></a>
            </div>
            <div id="uusin8">
                <p id="uusin8otsikko"></p>
                <p id="uusin8aika"></p>
                <p id="uusin8sisalto"></p>
                <a id="uusin8linkki" href=""></a>
            </div>
            <div id="uusin9">
                <p id="uusin9otsikko"></p>
                <p id="uusin9aika"></p>
                <p id="uusin9sisalto"></p>
                <a id="uusin9linkki" href=""></a>
            </div>
            <div id="uusin10">
                <p id="uusin10otsikko"></p>
                <p id="uusin10aika"></p>
                <p id="uusin10sisalto"></p>
                <a id="uusin10linkki" href=""></a>
            </div>
        </div>
        <div id="linkit">
    </div>
</body>

</html>';
$dom = new DOMDocument();
@$dom->loadHTML($html_string);
$otsikkoElement = $dom->getElementById("otsikko");
$aikaElement = $dom->getElementById("aika");
$sisaltoElement = $dom->getElementById("sisalto");
$kirjoittajaElement = $dom->getElementById("kirjoittaja");
$uusimmatElement =$dom->getElementById("uusimmat");
$linkitElement = $dom->getElementById("linkit");
$listaMaara = 10;

$servername = "localhost";
$username = "esimerkki";
$password = "ikkremise";
$dbname = "localhost";

//echo $_SERVER['REQUEST_URI'];
$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$params = [];
parse_str($queryString, $params);
$id = $params['id'];
$sivu = 1;
if(preg_match('/\bsivu\b/', $queryString)) {
$sivu = (int) $params['sivu'];
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT aika, otsikko, sisalto, kirjoittaja FROM artikkelit2
    WHERE id='" . $id . "'";
$result = $conn->query($sql);
//var_dump($result);
while ($row = $result->fetch_assoc()) { 
    $newDate = date_create($row["aika"]);
    $otsikkoElement->nodeValue = $row["otsikko"];
    $aikaElement->nodeValue = (string) $newDate->format('Y-m-d H:i');
    $sisaltoElement->textContent = $row["sisalto"];
    $kirjoittajaElement->nodeValue = $row["kirjoittaja"];
}

$sql = "SELECT id, aika, otsikko, sisalto, kirjoittaja FROM artikkelit2
    WHERE id!= " . $id . "
    ORDER BY aika DESC
    LIMIT " . $listaMaara . " OFFSET " . $listaMaara*$sivu-$listaMaara . "";
    //LIMIT " . (string) 10*$sivu . "";
$result2 = $conn->query($sql);
$all_rows = mysqli_fetch_all($result2, MYSQLI_ASSOC);
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
    $uusinSisalto->nodeValue = substr($row["sisalto"], 0, 120) . "...";
    $uusinLinkki->nodeValue = "Lue lisää";
    $uusinLinkki->setAttribute(
    'href',
    'http://localhost/blogitehtava/artikkeli.php?' . http_build_query(['id' => (string)$row['id'], 'sivu' => $sivu])
    );
    /*$index = $x-1;
    $row = $all_rows[$index];
    $otsikkoElement =$dom->getElementById("uusin" . $x . "otsikko");
    $aikaElement =$dom->getElementById("uusin" . $x . "aika");
    $sisaltoElement =$dom->getElementById("uusin" . $x . "sisalto");
    $linkkiElement =$dom->getElementById("uusin" . $x . "linkki");
    $otsikkoElement->textContent = $row["otsikko"];
    $newDate = date_create($row["aika"]);
    $aikaElement->nodeValue = (string) $newDate->format('Y-m-d H:i');
    $sisaltoElement->nodeValue = substr($row["sisalto"], 0, 120) . "...";
    $linkkiElement->nodeValue = "Lue lisää";
    $linkkiElement->setAttribute(
    'href',
    'http://localhost/blogitehtava/artikkeli.php?' . http_build_query(['id' => (string)$row['id'], 'sivu' => $sivu])
    );*/
    //$uusimmatElement->textContent = $row["otsikko"] . " " . $row["aika"] . "<br>" . substr($row["sisalto"], 0, 120) . "...<br>" . "<a href='http://localhost/blogitehtava/artikkeli.php?q=" . (string) $row["id"] . "'>Lue lisää</a>" . "<br>";
}
$sql = "SELECT COUNT(*) FROM artikkelit2";
$result = $conn->query($sql);
$taulunPituus = mysqli_fetch_all($result, MYSQLI_ASSOC)[0]['COUNT(*)'];
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
/*for($x = 1; $x < 11; $x++) { 
    if(!($x == $sivu)) {
    $linkkiElement =$dom->getElementById("linkki" . $x);
    $linkkiElement->setAttribute(
        'href',
        'http://localhost/blogitehtava/artikkeli.php?' . http_build_query(['id' => $id, 'sivu' => $x])
    );
}
}*/
echo $dom->saveHTML();
?>