
<?php
/*
<li>
    <article>
        <header>
            <h3><a href="single.html">Lorem ipsum fermentum ut nisl vitae</a></h3>
            <time class="published" datetime="2015-10-20">October 20, 2015</time>
        </header>
        <a href="single.html" class="image"><img src="images/pic08.jpg" alt="" /></a>
    </article>
</li>*/ 
for ($x = 1; $x < count($all_rows) + 1; $x++) {
    $index = $x - 1;
    $row = $all_rows[$index];
    echo '<li>';
    echo '<article>';
    echo '<header>';
    echo '<h3><a href="artikkeli.php?' . http_build_query(['id' => (string)$row['id'], 'sivu' => $sivu]) . '">' . $row["otsikko"] . '</a></h3>';
    $newDate = date_create($row["aika"]);
    echo '<time class="published" datetime="' . $row["aika"] . '">' . (string) $newDate->format('Y-m-d H:i') . '</time>';
    $sisaltoXML = simplexml_load_string($row["sisalto"]);
    echo '<p id="uusin' . $x . 'sisalto">' . substr($sisaltoXML->p[0], 0, 120) . "..." . '</p>';
    echo '<a id="uusin' . $x . 'linkki" href="http://localhost/blogitehtava/artikkeli.php?' . http_build_query(['id' => (string)$row['id'], 'sivu' => $sivu]) . '">Lue lisää</a>';
    echo '</header>';
    echo '</article>';
    echo '</li>';
}
?>

<?php
/*
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
}*/
?>