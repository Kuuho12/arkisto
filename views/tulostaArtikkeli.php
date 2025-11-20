<?php
/*function tulostaArtikkeli($row)
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
        <h1 id="otsikko"></h1>
        <p id="aika"></p>
        <div id="sisalto"></div>
        <p id="kirjoittaja"></p>*/
        echo '<h1 id="ostikko">' . $row["otsikko"] . '</h1>';
        echo '<p id="aika">' . (string) date_create($row["aika"])->format('Y-m-d H:i') . '</p>';
        echo '<div id="sisalto">';
        echo $row["sisalto"];
        echo '</div>';
        echo '<p id="kirjoittaja">' . $row["kirjoittaja"] . '</p>';
?>