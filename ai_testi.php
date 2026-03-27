<?php
require_once 'class.ai.php';
require_once 'class.ai.gemini.php';
require_once 'class.ai.huggingface.php';
require_once 'class.ai.openai.php';
/*$apiKey = getenv('GEMINI_API_KEY');
$AIclass = new AI ($apiKey, "gemini", "gemini-2.5-flash", false);
$AIGemini = new AIGemini($AIclass, false);*/
/*$apiKey = getenv('HF_TOKEN');
$AIclass = new AI ($apiKey, "huggingface", 'deepseek-ai/DeepSeek-V3.2:novita'); // deepseek-ai/DeepSeek-V3.2:novita LumiOpen/Llama-Poro-2-8B-Instruct:featherless-ai google/gemma-3-27b-it:featherless-ai
$AIhuggingface = new AIHuggingface($AIclass);*/
$apiKey = getenv('OPENAI_API_KEY');
$AIclass = new AI ($apiKey, "openai", "gpt-5-nano"); //gpt-4o-mini gpt-5-nano
$AIOpenAI = new AIOpenAI($AIclass);

/*$tulos = $AIclass->tekstiHaku(["Minkä värisiä rubiinit ovat?", "systemInstruction" => "Valehtele aina."], 1.0, 1000);
echo "Vastaus: " . $tulos[1] . "<br>";
var_dump($tulos);*/
/*$tulos = $AIclass->tiedostoHaku(["Kerro mitä näet kuvassa"], "testikamaa\Testikuva2.jpg", 1.0, 3000);
//$tulos = $AIclass->suoritaHaku(["Kuinka monta varvasta norsulla on?"]);
//$tulos = $AIOpenAI->modelExists("gpt-4o-mini");
echo $tulos[1];
echo "<br>Tokens: " . $tulos["total_tokens"];*/
/*$AIclass->valittuEsivalmisteltuKysely = $AIclass->esivalmistellutKyselyt["json_structured"];
$tulos = $AIclass->strukturoituHaku(
    ["Kirjoita lista kolmesta suomalaisesta perinneruoasta, niiden pääraaka-aineista ja valmistusajoista.", "systemInstruction" => "Vastaa englanniksi."],
    "reseptit"
);
var_dump($tulos[1]);
echo "<br>Tokens: " . $tulos["total_tokens"];*/

/*$tulos3 = $AIclass->linkkiHaku("https://www.is.fi/kotimaa/art-2000011882780.html"); // https://www.is.fi/uutiset/art-2000008502780.html https://www.iltalehti.fi/ulkomaat/a/64f0981b-dbb7-497d-8845-45b8b1391e44 https://www.iltalehti.fi/keho/a/bebfa2b8-77ff-4f13-9395-b866a22ffab9 https://yle.fi/a/74-20213734
var_dump($tulos3[1]);
echo "Tokens: " . $tulos3["total_tokens"] . "<br>";*/

/*$tulos1 = $AIGemini->linkkiHaku("https://www.is.fi/kotimaa/art-2000011882780.html"); // https://www.is.fi/uutiset/art-2000008502780.html https://www.iltalehti.fi/ulkomaat/a/64f0981b-dbb7-497d-8845-45b8b1391e44 https://www.iltalehti.fi/keho/a/bebfa2b8-77ff-4f13-9395-b866a22ffab9 https://yle.fi/a/74-20213734
var_dump($tulos1[1]);
echo "Tokens: " . $tulos1["total_tokens"] . "<br>";

$tulos = $AIOpenAI->linkkiHaku("https://www.is.fi/kotimaa/art-2000011882780.html"); // https://www.is.fi/uutiset/art-2000008502780.html https://www.iltalehti.fi/ulkomaat/a/64f0981b-dbb7-497d-8845-45b8b1391e44 https://www.iltalehti.fi/keho/a/bebfa2b8-77ff-4f13-9395-b866a22ffab9 https://yle.fi/a/74-20213734
var_dump($tulos[1]);
echo "Tokens: " . $tulos["total_tokens"] . "<br>";

$tulos2 = $AIhuggingface->linkkiHaku("https://www.is.fi/kotimaa/art-2000011882780.html"); // https://www.is.fi/uutiset/art-2000008502780.html https://www.iltalehti.fi/ulkomaat/a/64f0981b-dbb7-497d-8845-45b8b1391e44 https://www.iltalehti.fi/keho/a/bebfa2b8-77ff-4f13-9395-b866a22ffab9 https://yle.fi/a/74-20213734
var_dump($tulos2[1]);
echo "Tokens: " . $tulos2["total_tokens"] . "<br>";*/

/*$tulos = $AIhuggingface->suoritaHaku(["Kuinka monta linnaa on Japanissa?"], null, 0.8, 1000, false);
var_dump($tulos);*/
/*$tulos = $AIGemini->suoritaHaku(["Minkälaista markdownia käytät itse?"]);
var_dump($tulos[1]);
echo "<br>";
echo $tulos[1];

$vastausFile = fopen("temp_ai/gemini_markdown_vastaus.txt", 'w');
fwrite($vastausFile, $tulos[1]);
fclose($vastausFile);*/

/*$tulos1 = $AIhuggingface->suoritaHaku([$contents], "testikamaa\acrobat-AI-assistant.txt", 0.8, null, false);
echo "Tiedoston tokenit Hugging Face -mallilla: " . $tulos1["total_tokens"] . "\n";
echo "Hugging Face -vastaus: " . $tulos1[1] . "\n";
$vastausFile = fopen("temp_ai/testi_vastaus_hf_en.txt", 'w');
fwrite($vastausFile, "Tokens: " . $tulos1["total_tokens"] . "\nPrompt: \n" . $tulos1[1]);
fclose($vastausFile);*/

/*$AIGemini->lisaaTiedosto("testikamaa\acrobat-AI-assistant-2.txt");

$tulos2 = $AIGemini->suoritaHaku([$contents]);
echo "Tiedoston tokenit Gemini-mallilla: " . $tulos2["total_tokens"] . "\n";
echo "Gemini-vastaus: " . $tulos2[1] . "\n";

$vastausFile = fopen("temp_ai/testi_vastaus_gemini_fi.txt", 'w');
fwrite($vastausFile, "Tokens: " . $tulos2["total_tokens"] . "\nPrompt: \n" . $tulos2[1]);
fclose($vastausFile);*/
//$Aihuggingface = new AIHuggingface(getenv('HF_TOKEN'), "deepseek-ai/DeepSeek-V3.2:novita");


/*$tulos = $AIGemini->filesApiLisaaTiedosto("testikamaa\acrobat-AI-assistant.txt");
if($tulos[0]) {
    echo "Tiedosto lisätty Gemini Files API:in onnistuneesti. <br>";
    var_dump($tulos[1]);
    $result = $AIGemini->filesApiHaku([$contents], $tulos[1]);
    if($result[0]) {
        echo "Tiedoston tokenit Gemini Files API:in kautta: " . $result["total_tokens"] . "\n";
        echo "Gemini Files API -vastaus: " . $result[1] . "\n";
        $vastausFile = fopen("temp_ai/testi_vastaus_gemini_en.txt", 'w');
        fwrite($vastausFile, "Tokens: " . $result["total_tokens"] . "\nPrompt: \n" . $result[1]);
        fclose($vastausFile);
    } else {
        echo "Tiedostohaku Gemini Files API:in kautta epäonnistui. Virhe: " . $result[1] . "\n";
    }
} else {
    echo "Tiedoston lisäys Gemini Files API:in epäonnistui. Virhe: " . $tulos[1] . "\n";
}*/

//$tulos = $Aihuggingface->suoritaHaku(["Mitkä ovat Suomen suurimmat kaupungit väkiluvultaan?"]);
//$tulos = $AIclass->tekstiHaku(["Kirjoita runo kesästä."], $Aihuggingface);
/*$tulos = $AIclass->strukturoituHaku(
    ["Kirjoita lista kolmesta suomalaisesta perinneruoasta, niiden pääraaka-aineista ja valmistusajoista."],
    "reseptit"
);*/
//var_dump($tulos[1]);
/*$AIGemini->lisaaStructure("testistruktuuri", [
    ["Ominaisuus1", "string", true],
    ["Ominaisuus2", "number", false],
    ["Ominaisuus3", ["string"], true],
    ["Ominaisuus4", [["string"]], false]
]);
var_dump($AIGemini->haeStructure("testistruktuuri")[1]["properties"]["Ominaisuus2"]);
var_dump($AIGemini->haeStructure("testistruktuuri")[1]["properties"]["Ominaisuus3"]);
var_dump($AIGemini->haeStructure("testistruktuuri")[1]["properties"]["Ominaisuus4"]);*/
/*$AIclass->valittuEsivalmisteltuKysely = $AIclass->esivalmistellutKyselyt["json_structured"];
$AIclass->childClass->lisaaStructure("Henkilohahmot", [['Nimi', 'string', true], ['Kotikunta', 'string', true], ['Ika', 'int', true]]);
var_dump($AIclass->haeStructuret());
$tulos = $AIclass->strukturoituHaku(["Keksi suomalaisia henkilöitä metsästystarinaa varten"], "Henkilohahmot", false);
var_dump($tulos[1]);
echo "<br>Tokens: " . $tulos["total_tokens"];*/
//$AIclass->lisaaTiedosto("testikamaa\Testikuva.jpg");
//$AIclass->lisaaTiedosto("testikamaa\Testikuva2.jpg");
//$AIclass->lisaaTiedosto("testikamaa\Starry_Night.jpg");
//$AIclass->lisaaTiedosto("testikamaa\JouluisaHuone.avif");
//$AIclass->poistaTiedosto("testikamaa\Mona_Lisa.jpg");
//$AIclass->lisaaTiedosto('testikamaa\acrobat-AI-assistant.json');
//$AIclass->lisaaTiedosto('testikamaa\example.json');
//$AIclass->lisaaTiedosto('testikamaa\text.txt');
/*$tulos = $AIclass->tiedostoHaku(["Kerro mitä näissä tiedostoissa on."], null, 0.8, 1000);
echo "Vastauksen tokenit: " . $tulos["total_tokens"] . "<br>";
echo "Vastaus: " . $tulos[1] . "<br>";*/

/*$tulos1 = $AIGemini->laskeTokenit(["Kerro vain näiden maalausten nimet ja taiteilijat."]);
echo "Promptin toikenit: " . $tulos1[1] . "<br>";
$tulos2 = $AIGemini->suoritaHaku(["Kerro vain näiden maalausten nimet ja taiteilijat."], null, 0.8, null, false);
echo "Tuloksen koko tokenit: " . $tulos2["total_tokens"] . "<br>";
echo "Gemini-vastaus: " . $tulos2[1] . "<br>";*/

//$tulos = $AIclass->tiedostoHaku(["Tell me about these files."], $AIGemini);
/*$Aihuggingface = new AIHuggingface(getenv('HF_TOKEN'), "zai-org/GLM-4.6V-Flash:novita");
$tulos = $AIclass->tiedostoHaku(["Kerro mitä tässä tiedostossa on:"], $Aihuggingface, 'testikamaa\Apple.jpg');
print_r($tulos[1]);*/
//echo $tulos[1];
?>
