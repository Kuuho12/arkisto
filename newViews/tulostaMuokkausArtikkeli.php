<article class="post">
    <form action="http://localhost/blogitehtava/muokkaus.php?id= <?php echo $id ?>" method="POST" class="post" id="form">
        <header>
            <div class="title">
                <h2>Muokkaus</h2>
                <button type="submit" name="" <?php echo "value=" ?>>Tallenna</button>
                <?php echo ($result === NULL ? "" : ($result ? "<p>Tallennus onnistui</p>" : "<p>Tallennus epäonnistui</p>"))  ?>
            </div>
        </header>
        <header>
            <div class="title">
                <label class="isolabel" for="otsikko">Otsikko</label>
                <textarea name="otsikko" id="otsikko"><?php echo $row["otsikko"] ?></textarea>
            </div>
            <div class="meta">
                <label for="aika">Julkaisuaika</label>
                <textarea name="aika" id="aika"><?php echo (string) date_create($row["aika"])->format('Y-m-d H:i:s') ?></textarea>
                <label for="kirjoittaja">Kirjoittaja</label>
                <textarea name="kirjoittaja" id="kirjoittaja"><?php echo $row["kirjoittaja"] ?></textarea>
            </div>
        </header>
        <label class="isolabel" for="sisalto">Sisältö</label>
        <textarea name="sisalto" id="sisalto"><?php echo htmlspecialchars($row["sisalto"]) ?></textarea>
    </form>
</article>