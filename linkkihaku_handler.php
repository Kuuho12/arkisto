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
$tulos = $AI->linkkiHaku($linkki);

if($tulos[0]) {
    if(!isset($tulos[1]['Alkuperäinen otsikko'])) {
        echo json_encode(['status' => 'success', 'otsikko' => $tulos[1][0]['Alkuperäinen otsikko'], 'lehti' => $tulos[1][0]['Lehden nimi'], 'julkaisuvuosi' => $tulos[1][0]['Julkaisuvuosi'], 'maksullinen' => $tulos[1][0]['Maksullinen'], 'kieli' => $tulos[1][0]['Kieli'], 'tekijat' => $tulos[1][0]['Tekijät'], 'organisaatiot' => $tulos[1][0]['Tekijöiden organisaatiot'], 'esittely' => $tulos[1][0]['Esittely']]);
    } else {
        echo json_encode(['status' => 'success', 'otsikko' => $tulos[1]['Alkuperäinen otsikko'], 'lehti' => $tulos[1]['Lehden nimi'], 'julkaisuvuosi' => $tulos[1]['Julkaisuvuosi'], 'maksullinen' => $tulos[1]['Maksullinen'], 'kieli' => $tulos[1]['Kieli'], 'tekijat' => $tulos[1]['Tekijät'], 'organisaatiot' => $tulos[1]['Tekijöiden organisaatiot'], 'esittely' => $tulos[1]['Esittely']]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => $tulos[1], 'error_details' => $tulos[2] ?? null]);
}