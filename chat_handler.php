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
    $chat_history_id = uniqid();
    $_SESSION['chat_history'][$chat_history_id] = [];
    if($api === "Gemini") {
        $AI = new Ai(getenv('GEMINI_API_KEY'), "gemini", $malli);
        $AIGemini = new AIGemini($AI);
        $tulos = $AIGemini->modelExists();
        if($tulos[0]) {
            echo json_encode(['status' => 'success', 'message' => 'Malli löytyy ja toimii.', 'id' => $chat_history_id ]);
        }
        else {
            if($tulos[2]) {
                $error = 1; //Malli löytyi
            } else {
                $error = 0; //Malli ei löytynyt
            }
            echo json_encode(['status' => 'error', 'error' => $error, 'message' => 'Error: ' . $tulos[1]]);
        }
    } else if ($api === "Hugging Face") {
        $AI = new Ai(getenv('HF_TOKEN'), "huggingface", $malli);
        $Aihuggingface = new AIHuggingface($AI);
        $tulos = $Aihuggingface->modelExists();
        if($tulos[0]) {
            echo json_encode(['status' => 'success', 'message' => 'Malli löytyy ja toimii.', 'id' => $chat_history_id ]);
        }
        else {
            echo json_encode(['status' => 'error', 'message' => 'Error: ' . $tulos[1] . " Asia " . $tulos[2]]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tuntematon API-valinta.']);
    }
} else if ($pyynto === 2) { //Haetaan chat-historia
    $chatti_id = $data['chatti_id'];
    if(isset($_SESSION['chat_history'][$chatti_id])) {
        echo json_encode(['status' => 'success', 'chat_history' => $_SESSION['chat_history'][$chatti_id]]);
        exit();
    }
    echo json_encode(['status' => 'error', 'message' => 'Chatti-id ei löydy istunnosta.']);
} else { //Käsitellään käyttäjän viesti
    try {
        $api = $data['api'];
        $malli = $data['malli'];
        $viesti = $data['viesti']; //Jatkokehitysideaksi mallin, apin ja muiden asetusten välimuistutus
        $onkoChattays = $data['onkoChattays'] ?? false;
        $chatti_id = $data['chatti_id'] ?? null;
        $parser = new \cebe\markdown\GithubMarkdown();
        //$parser = new \cebe\markdown\Markdown();
        $parser->html5 = true;
        if($api === "Gemini") {
            $AI = new Ai(getenv('GEMINI_API_KEY'), "gemini", $malli);
            $AIGemini = new AIGemini($AI);
            if($onkoChattays) {
                $vastaus = $AIGemini->chattays([$viesti], $_SESSION['chat_history'][$chatti_id]);
                if(count($vastaus) > 2) {
                    $_SESSION['chat_history'][$chatti_id] = array_merge($_SESSION['chat_history'][$chatti_id], $vastaus[2]);
                    unset($vastaus[2]);
                }
            } else {
                $vastaus = $AIGemini->suoritaHaku([$viesti]);
            }
            if($vastaus[0] === false) {
                echo json_encode(['status' => 'error', 'message' => $vastaus[1]]);
                exit;
            }
            $markdownToHtml = gemtextToHtml($vastaus[1]);
            /*$vastausFile = fopen("temp_ai/gemini_". $chatti_id . "_" . count($_SESSION['chat_history'][$chatti_id])  . ".txt", 'w');
            fwrite($vastausFile, $vastaus[1]);
            fclose($vastausFile);*/
            echo json_encode(['status' => 'success', 'vastaus' => $markdownToHtml]);
        } else if ($api === "Hugging Face") {
            $AI = new Ai(getenv('HF_TOKEN'), "huggingface", $malli);
            $Aihuggingface = new AIHuggingface($AI);
            if ($onkoChattays) {
                $vastaus = $Aihuggingface->chattays([$viesti], $_SESSION['chat_history'][$chatti_id]);
                if(count($vastaus) > 2) {
                    $_SESSION['chat_history'][$chatti_id] = $vastaus[2];
                    unset($vastaus[2]);
                }
            } else {
                $vastaus = $Aihuggingface->suoritaHaku([$viesti]);
            }
            if($vastaus[0] === false) {
                echo json_encode(['status' => 'error', 'message' => $vastaus[1]]);
                exit;
            }
            $markdownToHtml = $parser->parse($vastaus[1]);
            echo json_encode(['status' => 'success', 'vastaus' => $markdownToHtml]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Tuntematon API-valinta.']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
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