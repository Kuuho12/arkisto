
<div id="promptit-listaus">
    <div id="promptit-header">
        <h2>Promptiesi listaus:</h2>
        <div id="promptit-header2">
            <h2 class="prompt-text">Promptit</h2>
            <div id="promptit-header3">
                <h2>Api</h2>
                <h3>Malli</h3>
            </div>
        </div>
    </div>
<?php 
foreach ($prompts as $prompt) {
    $promptTeksti = htmlspecialchars($prompt['Prompt']);
    $tekstinPituus = 500; 
    $promptTekstinAlkuosa = mb_substr($promptTeksti, 0, $tekstinPituus, 'UTF-8'); // Hieman turha nykyään
    if(mb_strlen($promptTeksti) > $tekstinPituus) {
        $promptTekstinAlkuosa .= "...";
    }
    echo "<a class='prompt-item' href='prompt.php?id=" . $prompt['Id'] . "'>";
    echo "<p class='prompt-text'>" . $promptTekstinAlkuosa . "</p>";
    echo "<div class='prompt-osa0'>";
    echo "<div class='prompt-osa'><div class='prompt-osa2'><input type='checkbox' id='api-gemini" . $prompt['Id'] . "' class='disabled' tabindex='-1' name='api-gemini' value='gemini' " . ($prompt['Gemini'] ? 'checked' : '') . "><label for='api-gemini" . $prompt['Id'] . "'>Gemini</label></div>";
    echo "<input type='text' name='gemini-model' id='gemini-model" . $prompt['Id'] . "' class='disabled' tabindex='-1' value='" . htmlspecialchars($prompt['Gemini_model']) . "'></div>";
    echo "<div class='prompt-osa'><div class='prompt-osa2'><input type='checkbox' id='api-openai" . $prompt['Id'] . "' class='disabled' tabindex='-1' name='api-openai' value='openai' " . ($prompt['OpenAI'] ? 'checked' : '') . "><label for='api-openai" . $prompt['Id'] . "'>OpenAI</label></div>";
    echo "<input type='text' name='openai-model' id='openai-model" . $prompt['Id'] . "' class='disabled' tabindex='-1' value='" . htmlspecialchars($prompt['OpenAI_model']) . "'></div>";
    echo "<div class='prompt-osa'><div class='prompt-osa2'><input type='checkbox' id='api-huggingface" . $prompt['Id'] . "' class='disabled' name='api-huggingface' value='huggingface' " . ($prompt['Hugging_Face'] ? 'checked' : '') . "><label for='api-huggingface" . $prompt['Id'] . "'>Hugging Face</label></div>";
    echo "<input type='text' name='huggingface-model' id='huggingface-model" . $prompt['Id'] . "' class='disabled' tabindex='-1' value='" . htmlspecialchars($prompt['Hugging_Face_model']) . "'></div>";
    echo "</div></a>";
}
?>
</div>