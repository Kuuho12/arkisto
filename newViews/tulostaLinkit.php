<?php
$y = 0;
for ($x = $taulunPituus; $x > 0; $x -= $listaMaara) {
    $y++;
    if ($y == $sivu) {
        echo '<a id="linkki' . $y . '" class="bold">' . $y . '</a> ';
        continue;
    }
    echo '<a id="linkki' . $y . '" href="http://localhost' . $url . '?' . http_build_query(['id' => $id, 'sivu' => $y]) . '">' . $y . '</a> ';
}
?>