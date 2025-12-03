<?php
$alkumaara = $listaMaara * $sivu - 9;
$listausPituus = count($all_rows);
echo '<h3 class="listausotsikko">Artikkelit ' . $alkumaara . '-' . $alkumaara + $listausPituus - 1 .':</h3>';
for ($x = 1; $x < $listausPituus + 1; $x++) {
    $index = $x - 1;
    $row = $all_rows[$index];
    echo '<li>';
    echo '<article>';
    echo '<header>';
    //echo '<h3>' . $row["otsikko"] . '</h3>';
    echo '<h3><a href="artikkeli.php?' . http_build_query(['id' => (string)$row['id'], 'sivu' => $sivu]) . '">' . $row["otsikko"] . '</a></h3>';
    $newDate = date_create($row["aika"]);
    echo '<time class="published" datetime="' . $row["aika"] . '">' . (string) $newDate->format('Y-m-d H:i') . '</time>';
    @$sisaltoXML = simplexml_load_string($row["sisalto"]);
    $onkoPitka = false;
    if ($sisaltoXML) {
        $full = (string) $sisaltoXML->p[0];
        if($sisaltoXML->p[1] != NULL) {
            $onkoPitka = true;
        }
    } else {
        $full = $row["sisalto"];
    }
    $full = html_entity_decode($full, ENT_QUOTES, 'UTF-8');
    $plain = strip_tags($full);
    $sisallonAlkuosa = mb_substr($plain, 0, 120, 'UTF-8');
    if(!$onkoPitka) {
        if(!mb_strlen($sisallonAlkuosa) === 120) {
            $onkoPitka = true;
        }
    }
    echo '<p id="uusin' . $x . 'sisalto">' . $sisallonAlkuosa . ($onkoPitka ? "..." : "") . '</p>';
    echo '<div class="article-rivi">';
    echo '<a id="uusin' . $x . 'linkki" href="http://localhost/blogitehtava/artikkeli.php?' . http_build_query(['id' => (string)$row['id'], 'sivu' => $sivu]) . '">Lue lisää</a>';
    echo '<form method="POST" action="muokkaus.php? ' . http_build_query(['id' => (string)$row['id']]) . '" >';
    echo '<button type="submit" name="" value="">Muokkaa</button>';
    echo '</form>';
    echo '<form method="POST" action="hallinta.php" >';
    echo '<button type="submit" name="id" value="'. $row['id'] . '">Palauta varmuuskopio</button>';
    echo '</form>';
    echo '</div>';
    echo '</header>';
    echo '</article>';
    echo '</li>';
}
?>