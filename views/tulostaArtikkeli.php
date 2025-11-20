<?php
echo '<h1 id="ostikko">' . $row["otsikko"] . '</h1>';
echo '<p id="aika">' . (string) date_create($row["aika"])->format('Y-m-d H:i') . '</p>';
echo '<div id="sisalto">';
echo $row["sisalto"];
echo '</div>';
echo '<p id="kirjoittaja">' . $row["kirjoittaja"] . '</p>';
?>