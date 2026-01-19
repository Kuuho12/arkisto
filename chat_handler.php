<?php
require_once 'class.ai.gemini.php';
require_once 'class.ai.huggingface.php';
session_start();
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$pyynto = $data['pyynto'] ?? null;

if($pyynto == 1) { //Testataan onko mallia olemassa
    $api = $data['api'];
    $malli = $data['malli'];
    $_SESSION['chat_history'] = [];
    if($api === "Gemini") {
        $AIGemini = new AIGemini(getenv('GEMINI_API_KEY'), $malli);
        $tulos = $AIGemini->modelExists();
        if($tulos[0]) {
            echo json_encode(['status' => 'success', 'message' => 'Malli löytyy.']);
        }
        else {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $tulos[1]]);
        }
    } else if ($api === "Hugging Face") {
        $Aihuggingface = new AIHuggingface(getenv('HF_TOKEN'), $malli);
        $tulos = $Aihuggingface->modelExists();
        if($tulos[0]) {
            echo json_encode(['status' => 'success', 'message' => 'Malli löytyy.']);
        }
        else {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $tulos[1]]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tuntematon API-valinta.']);
    }
} else { //Käsitellään käyttäjän viesti
    $api = $data['api'];
    $malli = $data['malli'];
    $viesti = $data['viesti']; //Jatkokehitysideaksi mallin, apin ja muiden asetusten välimuistutus
    if($api === "Gemini") {
        $AIGemini = new AIGemini(getenv('GEMINI_API_KEY'), $malli);
        $vastaus = $AIGemini->chattays([$viesti], $_SESSION['chat_history'] ?? []);
        if(count($vastaus) > 2) {
        $_SESSION['chat_history'] = array_merge($_SESSION['chat_history'] ?? [], $vastaus[2]);
        }
        echo json_encode(['status' => 'success', 'vastaus' => $vastaus]);
    } else if ($api === "Hugging Face") {
        $Aihuggingface = new AIHuggingface(getenv('HF_TOKEN'), $malli);
        $vastaus = $Aihuggingface->chattays([$viesti], $_SESSION['chat_history'] ?? []);
        if(count($vastaus) > 2) {
        $_SESSION['chat_history'] = $vastaus[2];
        unset($vastaus[2]);
        }
        echo json_encode(['status' => 'success', 'vastaus' => $vastaus]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tuntematon API-valinta.']);
    }
}
/*$GeminiMallit = [
    'gemini-2.5-flash' => 'gemini-2.5-flash',
    'gemini-2.0-flash' => 'gemini-2.0-flash',
    'gemini-2.0-flash-lite' => 'gemini-2.0-flash-lite',
    "gemini-1.5-pro" => "gemini-1.5-pro",
    "gemini-1.5-large" => "gemini-1.5-large",
    "gemini-1.5-medium" => "gemini-1.5-medium",
    "gemini-1.5-small" => "gemini-1.5-small"
];
$HuggingfaceMallit = [
    'deepseek-ai/DeepSeek-V3.2:novita' => 'deepseek-ai/DeepSeek-V3.2:novita',
    'google/gemma-3-27b-it:nebius' => 'google/gemma-3-27b-it:nebius',
    'Qwen/Qwen3-32B:groq' => 'Qwen/Qwen3-32B:groq',
    'zai-org/GLM-4.6V-Flash:novita' => 'zai-org/GLM-4.6V-Flash:novita',
    'mistralai/Mistral-7B-Instruct-v0.1:novita' => 'mistralai/Mistral-7B-Instruct-v0.1:novita'
];*/