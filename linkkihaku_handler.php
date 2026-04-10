<?php
require_once 'class.ai.gemini.php';
require_once 'class.ai.huggingface.php';
require_once 'class.ai.openai.php';
session_start();
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$api = $data['api'] ?? null;
$model = $data['model'] ?? null;
$linkki = $data['linkki'] ?? null;
$tagityyppi = $data['tagityyppi'] ?? null;

switch($api) {
    case "gemini":
        $AI = new Ai(getenv('GEMINI_API_KEY'), "gemini", $model);
        break;
    case "huggingface":
        if($model === null) {
            $model = "Qwen/Qwen3-32B:groq";
        }
        $AI = new Ai(getenv('HF_TOKEN'), "huggingface", $model); // Qwen/Qwen3-32B:groq Qwen/Qwen3.5-9B:together
        break;
    case "openai":
        $AI = new Ai(getenv('OPENAI_API_KEY'), "openai", $model);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Tuntematon API-valinta.']);
        exit();
}
$modelExists = $AI->modelExists($model)[0];
if(!$modelExists) {
    echo json_encode(['status' => 'error', 'message' => 'Valittua mallia ei tueta.']);
    exit();
}
switch($tagityyppi) {
    case "null":
    case null:
        $tagityyppi = null;
        break;
    case "true":
        $tagityyppi = true;
        break;
    case "false":
        $tagityyppi = false;
        break;
    default:
        if(!is_array($tagityyppi)) {
            echo json_encode(['status' => 'error', 'message' => 'Tuntematon tagityyppi-valinta.']);
            exit();
        }
}
$tulos = $AI->linkkiHaku($linkki, null, null, $tagityyppi);

if($tulos[0]) {
    if(!isset($tulos[1]['Alkuperäinen otsikko'])) {
        echo json_encode(['status' => 'success', 'otsikko' => $tulos[1][0]['Alkuperäinen otsikko'], 'lehti' => $tulos[1][0]['Lehden nimi'], 'julkaisuvuosi' => $tulos[1][0]['Julkaisuvuosi'], 'maksullinen' => $tulos[1][0]['Maksullinen'], 'kieli' => $tulos[1][0]['Kieli'], 'tekijat' => $tulos[1][0]['Tekijät'], 'organisaatiot' => $tulos[1][0]['Tekijöiden organisaatiot'], 'esittely' => $tulos[1][0]['Esittely'], 'tagit' => @$tulos[1][0]['Tägit']]);
    } else {
        echo json_encode(['status' => 'success', 'otsikko' => $tulos[1]['Alkuperäinen otsikko'], 'lehti' => $tulos[1]['Lehden nimi'], 'julkaisuvuosi' => $tulos[1]['Julkaisuvuosi'], 'maksullinen' => $tulos[1]['Maksullinen'], 'kieli' => $tulos[1]['Kieli'], 'tekijat' => $tulos[1]['Tekijät'], 'organisaatiot' => $tulos[1]['Tekijöiden organisaatiot'], 'esittely' => $tulos[1]['Esittely'], 'tagit' => @$tulos[1]['Tägit']]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => $tulos[1], 'error_details' => $tulos[2] ?? null]);
}