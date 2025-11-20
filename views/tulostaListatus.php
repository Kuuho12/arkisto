<?php
/*
$uusimmatElement = $this->dom->getElementById("uusimmat");
//$all_rows = $sqlModel->haeListatus($listaMaara, $sivu);
for ($x = 1; $x < count($all_rows) + 1; $x++) {
    $uusinDiv = $this->dom->createElement('div');
    $uusinDiv->setAttribute("id", "uusin$x");
    $uusimmatElement->appendChild($uusinDiv);
    $uusinOtskko = $this->dom->createElement('p');
    $uusinOtskko->setAttribute("id", "uusin" . $x . "otsikko");
    $uusinAika = $this->dom->createElement('p');
    $uusinAika->setAttribute("id", "uusin" . $x . "aika");
    $uusinSisalto = $this->dom->createElement('p');
    $uusinSisalto->setAttribute("id", "uusin" . $x . "sisalto");
    $uusinLinkki = $this->dom->createElement('a');
    $uusinLinkki->setAttribute("id", "uusin" . $x . "linkki");
    $uusinDiv->appendChild($uusinOtskko);
    $uusinDiv->appendChild($uusinAika);
    $uusinDiv->appendChild($uusinSisalto);
    $uusinDiv->appendChild($uusinLinkki);

    $index = $x - 1;
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
for ($x = 1; $x < count($all_rows) + 1; $x++) {
    $index = $x - 1;
    $row = $all_rows[$index];
    echo '<div id="uusin' . $x . '">';
    echo '<p id="uusin' . $x . 'otsikko">' . $row["otsikko"] . '</p>';
    $newDate = date_create($row["aika"]);
    echo '<p id="uusin' . $x . 'aika">' . (string) $newDate->format('Y-m-d H:i') . '</p>';
    $sisaltoXML = simplexml_load_string($row["sisalto"]);
    echo '<p id="uusin' . $x . 'sisalto">' . substr($sisaltoXML->p[0], 0, 120) . "..." . '</p>';
    echo '<a id="uusin' . $x . 'linkki" href="http://localhost/blogitehtava/artikkeli.php?' . http_build_query(['id' => (string)$row['id'], 'sivu' => $sivu]) . '">Lue lisää</a>';  
    echo '</div>';
}

?>