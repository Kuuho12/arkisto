<article class="post">
    <form action="http://localhost/blogitehtava/lisays.php" method="POST" class="post" id="form">
        <header>
            <div class="title">
                <h2>Artikkelin lisäys</h2>
                <button type="submit" name="" <?php echo "value=" ?>>Tallenna</button>
                <?php echo ($result === NULL ? "" : ($result ? "<p>Artikkelin lisäys onnistui</p>" : "<p>Artikkelin lisäys epäonnistui</p>"))  ?>
            </div>
        </header>
        <header>
            <div class="title">
                <label class="isolabel" for="otsikko">Otsikko</label>
                <textarea name="otsikko" id="otsikko"></textarea>
            </div>
            <div class="meta">
                <label for="aika">Julkaisuaika</label>
                <textarea name="aika" id="aika"><?php echo /*date_create(time())->format('Y-m-d H:i:s')*/ date('Y-m-d H:i:s', time()) ?></textarea>
                <label for="kirjoittaja">Kirjoittaja</label>
                <textarea name="kirjoittaja" id="kirjoittaja"></textarea>
            </div>
        </header>
        <label class="isolabel" for="sisalto">Sisältö</label>
        <textarea name="sisalto" id="sisalto"></textarea>
    </form>
</article>