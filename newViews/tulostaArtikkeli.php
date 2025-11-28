<?php
function TiivistelmanLuonti($row, $id)
{
    $GeminiApiKey = getenv('GEMINI_API_KEY');

    $client = Gemini::client($GeminiApiKey);
    $prompt = $row["otsikko"] . "\n" . $row["sisalto"] . "\n\n" . "Tee hyvin lyhyt tiivistelmä yllä olevasta artikkelista suomeksi.";
    $model = 'gemini-2.5-flash';

    try {
        $result = $client
            ->generativeModel(model: $model)
            ->generateContent($prompt);
        $tiivistelma = $result->text();
        $file = fopen('temp_ai/summary_' . $id . '.txt', 'w');
        fwrite($file, $tiivistelma);
        fclose($file);
        return $tiivistelma;
    } catch (\Exception $e) {
       return "Tekoälytiivistelmää ei voida luoda tällä hetkellä. Error: " . $e->getMessage();
    }
}

if (!file_exists('temp_ai')) {
    mkdir('temp_ai');
}
$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$params = [];
parse_str($queryString, $params);
$id = $params['id'];
if (isset($_POST['reload_tiivistelma'])) {
    $tiivistelma = TiivistelmanLuonti($row, $id);
} else {
    if (file_exists('temp_ai/summary_' . $id . '.txt')) {
        $file = fopen('temp_ai/summary_' . $id . '.txt', 'r');
        $tiivistelma = fread($file, filesize('temp_ai/summary_' . $id . '.txt'));
        fclose($file);
    } else {
        $tiivistelma = TiivistelmanLuonti($row, $id);
    }
}
?>

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
    <p id="ai_tiivistelma">
        <?php echo "<strong>Tekoälytiivistelmä:</strong> " . $tiivistelma; ?>
    </p>
    <form method="POST" action=" <?php echo $_SERVER['REQUEST_URI']; ?>">
        <button type="submit" name="reload_tiivistelma" value="">Lataa tekoälytiivistelmä uudestaan</button>
    </form>
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