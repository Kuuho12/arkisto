<?php 
/*
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
        */
        $y = 0;
        for ($x = $taulunPituus; $x > 0; $x -= $listaMaara) { 
            $y++;
            if( $y == $sivu ) {
                echo '<a id="linkki' . $y . '" class="bold">' . $y . '</a> ';
                continue;
            }
            echo '<a id="linkki' . $y . '" href="http://localhost' . $url . '?' . http_build_query(['id' => $id, 'sivu' => $y]) . '">' . $y . '</a> ';
        }
        //ob_get_contents();
?>