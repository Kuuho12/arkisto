<article class="post">
    <header>
        <div class="title">
            <h2><?php echo $row["otsikko"] ?></h2>
        </div>
        <div class="meta">
            <time class="published" datetime="<?php echo $row["aika"] ?>"><?php echo (string) date_create($row["aika"])->format('Y-m-d H:i') ?></time>
            <span class="name"><?php echo $row["kirjoittaja"] ?></span>
        </div>
    </header>
    <?php echo $row["sisalto"]; ?>
</article>

<?php /*
echo '<h1 id="ostikko">' . $row["otsikko"] . '</h1>';
echo '<p id="aika">' . (string) date_create($row["aika"])->format('Y-m-d H:i') . '</p>';
echo '<div id="sisalto">';
echo $row["sisalto"];
echo '</div>';
echo '<p id="kirjoittaja">' . $row["kirjoittaja"] . '</p>'; */
?>