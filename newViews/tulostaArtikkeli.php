<?php

//use Gemini;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Gemini\Enums\ModelVariation;

$GeminiApiKey = getenv('GEMINI_API_KEY');

$client = Gemini::client($GeminiApiKey);
$prompt = $row["otsikko"] . "\n" . $row["sisalto"] . "\n\n" . "Tee hyvin lyhyt tiivistelmä yllä olevasta artikkelista suomeksi.";
$model = 'gemini-2.5-flash';

try {
    $result = $client
        ->generativeModel(model: $model)
        ->generateContent($prompt);
        $tiivistelma = $result->text();
} catch (\Exception $e) {
    $tiivistelma = "Tekoälytiivistelmää ei voida luoda tällä hetkellä. " . $e->getMessage();
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
    <p>
        <?php echo "<strong>Tekoälytiivistelmä:</strong> " . $tiivistelma; ?>
    </p>
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