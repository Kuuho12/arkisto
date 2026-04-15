<?php
session_start();
require_once 'class.ai.gemini.php';
require_once 'class.ai.huggingface.php';
require_once 'class.ai.openai.php';
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$user = $_SESSION['user'] ?? null;

if($user === null) {
    echo json_encode(['status' => 'error', 'message' => 'Käyttäjää ei tunnistettu.']);
    exit();
}

$prompt = $data['prompt'] ?? '';
$api = $data['api'] ?? null;
$model = $data['model'] ?? null;

if(!$api) {
    echo json_encode(['status' => 'error', 'message' => 'API-valinta puuttuu.']);
    exit();
}

$phpmyadmin_username = "tekoalytestaus";
$phpmyadmin_password = getenv('PHPMYADMIN_PASSWORD');
$servername = "localhost";
$databasename = "tekoalytestaus";

switch($api) {
    case "gemini":
        $AI = new Ai(getenv('GEMINI_API_KEY'), $api, $model);
        break;
    case "openai":
        $AI = new Ai(getenv('OPENAI_API_KEY'), $api, $model);
        break;
    case "huggingface":
        $AI = new Ai(getenv('HF_TOKEN'), $api, $model);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Tuntematon API-valinta.']);
        exit();
}
if(!$AI->modelExists($AI->model)[0]) {
    echo json_encode(['status' => 'error', 'message' => 'Valittua mallia ei tueta.']);
    exit();
}
if($model === null) {
    $model = $AI->getModel();
}

$conn = new mysqli($servername, $phpmyadmin_username, $phpmyadmin_password, $databasename);
if ($conn->connect_error) {
    $error = 'Tietokantayhteys epäonnistui: ' . $conn->connect_error;
} else {
    $stmt = $conn->prepare(
        "INSERT INTO Prompts_and_responses (User, Created_at, Prompt, Api, Model) VALUES (?, NOW(), ?, ?, ?)"
    );
    $stmt->bind_param('ssss', $user, $prompt, $api, $model);
    $sqlTulos = $stmt->execute();
    if (!$sqlTulos) {
        $error = 'Virhe tietojen tallentamisessa: ' . $conn->error;
    } else {
        $promptId = $conn->insert_id;
    }
}
$tulos = $AI->suoritaHaku([$prompt]);
$vastaus = $tulos[1];
if (!isset($error)) {
    $stmt = $conn->prepare(
        "UPDATE Prompts_and_responses SET Response = ? WHERE Id = ?"
    );
    $stmt->bind_param('si', $vastaus, $promptId);
    $sqlTulos = $stmt->execute();
    if (!$sqlTulos) {
        $error = 'Virhe tietojen tallentamisessa: ' . $conn->error;
    }
}
echo json_encode(["status" => $tulos[0] ? 'success' : 'error', "message" => $tulos[1], "error" => $error ?? null]);