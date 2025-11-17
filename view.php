<?php
class View
{
    public $dom;
    public function __construct($dom)
    {
        $this->dom = $dom;
    }
    function tulostaArtikkeli($row)
    {
        $otsikkoElement = $this->dom->getElementById("otsikko");
        $aikaElement = $this->dom->getElementById("aika");
        $sisaltoElement = $this->dom->getElementById("sisalto");
        $kirjoittajaElement = $this->dom->getElementById("kirjoittaja");

        $newDate = date_create($row["aika"]);
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
            $imported_node = $this->dom->importNode($node, true);
            $sisaltoElement->appendChild($imported_node);
        }
    }
    function tulostaListaus($sivu, $all_rows)
    {
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
        }
    }
    function tulostaLinkit($url, $taulunPituus, $listaMaara, $sivu, $id)
    {
        $linkitElement = $this->dom->getElementById("linkit");
        $y = 0;
        for ($x = $taulunPituus; $x > 0; $x -= $listaMaara) {
            $y++;
            $uusiLinkkiElement = $this->dom->createElement('a');
            $uusiLinkkiElement->setAttribute("id", "linkki$y");
            $uusiLinkkiElement->setAttribute(
                'href',
                'http://localhost' . $url . '?' . http_build_query(['id' => $id, 'sivu' => $y])
            );
            $uusiLinkkiElement->textContent = "$y";
            $linkitElement->appendChild($uusiLinkkiElement);
        }
        $nykyinenLinkki = $this->dom->getElementById("linkki" . $sivu);
        $nykyinenLinkki->removeAttribute("href");
        $nykyinenLinkki->className = "bold";
    }

    function tulostaDom() {
        echo $this->dom->saveHTML();
    }
}
