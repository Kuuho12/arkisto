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

$action = $data['action'] ?? null;
if($action === null) {
    echo json_encode(['status' => 'error', 'message' => 'Toimintoa ei määritetty.']);
    exit();
}
$prompt = $data['prompt'] ?? '';
$Gemini = $data['Gemini'] ?? false;
$HuggingFace = $data['HuggingFace'] ?? false;
$OpenAI = $data['OpenAI'] ?? false;
$Gemini_model = $data['Gemini_model'] ?? "";
$HuggingFace_model = $data['HuggingFace_model'] ?? "";
$OpenAI_model = $data['OpenAI_model'] ?? "";

$promptId = $data['promptId'] ?? null;

if(!$Gemini && !$HuggingFace && !$OpenAI) {
    echo json_encode(['status' => 'error', 'message' => 'Vähintään yksi API-valinta on pakollinen.']);
    exit();
}

$Gemini = $Gemini ? 1 : 0;
$HuggingFace = $HuggingFace ? 1 : 0;
$OpenAI = $OpenAI ? 1 : 0;

$phpmyadmin_username = "tekoalytestaus";
$phpmyadmin_password = getenv('PHPMYADMIN_PASSWORD');
$servername = "localhost";
$databasename = "tekoalytestaus";

$conn = new mysqli($servername, $phpmyadmin_username, $phpmyadmin_password, $databasename);
if ($conn->connect_error) {
    $error = 'Tietokantayhteys epäonnistui: ' . $conn->connect_error;
    echo json_encode(["status" => 'error', "message" => $error]);
    exit();
}

if($action === 0) { // Tallennetaan prompt tietokantaan
    /*$stmt = $conn->prepare(  // Estää saman promptin tallentamisen uudestaan. Päätettiin, ettei tätä tarvitse estää
        "SELECT Id FROM Prompts WHERE User = ? AND Prompt = ? AND Gemini = ? AND Hugging_Face = ? AND OpenAI = ? AND Gemini_model = ? AND Hugging_Face_model = ? AND OpenAI_model = ?"
    );
    $stmt->bind_param('ssiiisss', $user, $prompt, $Gemini, $HuggingFace, $OpenAI, $Gemini_model, $HuggingFace_model, $OpenAI_model);
    $sqlTulos = $stmt->execute();
    if (!$sqlTulos) {
        $error = 'Tietokantavirhe: ' . $conn->error;
        echo json_encode(["status" => 'error', "message" => $error]);
        exit();
    }
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $promptId = $result->fetch_assoc()['Id'];
        $result->free();
        echo json_encode(["status" => 'success', "message" => 'Sama prompt on jo tallennettu.', "promptId" => $promptId]);
        exit();
    }*/ 
    $stmt = $conn->prepare(
        "INSERT INTO Prompts (User, Prompt, Gemini, Hugging_Face, OpenAI, Gemini_model, Hugging_Face_model, OpenAI_model) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ssiiisss', $user, $prompt, $Gemini, $HuggingFace, $OpenAI, $Gemini_model, $HuggingFace_model, $OpenAI_model);
    $sqlTulos = $stmt->execute();
    $promptId = null;
    if (!$sqlTulos) {
        $error = 'Virhe tietojen tallentamisessa: ' . $conn->error;
    } 
    $promptId = $stmt->insert_id;
    echo json_encode(["status" => $sqlTulos ? 'success' : 'error', "message" => $sqlTulos ? 'Prompt tallennettu onnistuneesti.' : $error, "promptId" => $promptId]);

} else if($action === 1) { // Suoritetaan haku tekoälyllä ja tallennetaan vastaus tietokantaan
    $groupCode = $data['groupCode'] ?? time();
    $api = $data['api'] ?? "";
    $model = $data['model'] ?? "";

    if(!$api) {
        echo json_encode(['status' => 'error', 'message' => 'API-valinta puuttuu.']);
        exit();
    }
    $stmt = $conn->prepare(
        "SELECT * FROM Prompts WHERE Id = ? AND Prompt = ? AND Gemini = ? AND Hugging_Face = ? AND OpenAI = ? AND Gemini_model = ? AND Hugging_Face_model = ? AND OpenAI_model = ?"
    );
    $stmt->bind_param('isiiisss', $promptId, $prompt, $Gemini, $HuggingFace, $OpenAI, $Gemini_model, $HuggingFace_model, $OpenAI_model);
    $sqlTulos = $stmt->execute();
    $result = $stmt->get_result();
    if (!$sqlTulos) {
        $error = 'Virhe promptin löytämisessä. ' . $conn->error;
        echo json_encode(["status" => 'error', "message" => $error]);
        $result->free();
        exit();
    } else if ($result->num_rows === 0) {
        $error = 'Virhe promptin löytämisessä. Promptia on saatettu muuttaa. ';
        echo json_encode(["status" => 'error', "message" => $error]);
        $result->free();
        exit();
    }
    $result->free();
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
    $tulos = $AI->suoritaHaku([$prompt]);
    $vastaus = $tulos[1];
    $stmt = $conn->prepare(
        "INSERT INTO Ai_responses (User, Prompt_id, Group_code, Prompt, Response, Api, Model) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sidssss', $user, $promptId, $groupCode, $prompt, $vastaus, $api, $model);
    $sqlTulos = $stmt->execute();
    if (!$sqlTulos) {
        $error = 'Virhe tietojen tallentamisessa: ' . $conn->error;
    }
    echo json_encode(["status" => $tulos[0] ? 'success' : 'error', "message" => $tulos[1], "error" => $error ?? null]);
} else if ($action === 2) { // Muokataan (käyttäjän omaa) promptia
    $promptId = $data['promptId'] ?? null;
    if($promptId === null) {
        echo json_encode(['status' => 'error', 'message' => 'Prompt ID puuttuu.']);
        exit();
    }
    $stmt = $conn->prepare(
        "UPDATE Prompts SET Prompt = ?, Gemini = ?, Hugging_Face = ?, OpenAI = ?, Gemini_model = ?, Hugging_Face_model = ?, OpenAI_model = ? WHERE Id = ? AND User = ?"
    );
    $stmt->bind_param('siiisssis', $prompt, $Gemini, $HuggingFace, $OpenAI, $Gemini_model, $HuggingFace_model, $OpenAI_model, $promptId, $user);
    $sqlTulos = $stmt->execute();
    if (!$sqlTulos) {
        $error = 'Virhe tietojen päivittämisessä: ' . $conn->error;
        echo json_encode(["status" => 'error', "message" => $error]);
    } else if ($stmt->affected_rows === 0) {
        echo json_encode(["status" => 'error', "message" => 'Promptia ei onnistuttu päivittämään. Promptia ei löytynyt, prompti ei ollut sinun tai tiedot olivat samat kuin ennen.']);
    } else {
        echo json_encode(["status" => 'success', "message" => 'Prompt päivitetty onnistuneesti.']);
    }
} else if ($action === 3) { // Haetaan kaikki vastaukset tietylle promptille (Ei käytössä)
    $responses = getResponses($promptId);
    echo json_encode(["status" => $responses['status'], "message" => $responses['message'], "responses" => $responses['responses'] ?? null]);
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Tuntematon toiminto.']);
}

function getResponses($promptId) {
    global $conn;
    if($promptId === null) {
        return ['status' => 'error', 'message' => 'Prompt ID puuttuu.'];
    }
    $stmt = $conn->prepare(
        "SELECT Response, Api, Model FROM Ai_responses WHERE Prompt_id = ?"
    );
    $stmt->bind_param('i', $promptId);
    $sqlTulos = $stmt->execute();
    if (!$sqlTulos) { // Se ettei löydy vastauksia ei aiheuta virhettä
        $error = 'Virhe tietokantahauissa: ' . $conn->error;
        return ['status' => 'error', 'message' => $error];
    }
    $result = $stmt->get_result();
    $responses = [];
    while($row = $result->fetch_assoc()) {
        $responses[] = [
            'Response' => $row['Response'],
            'Api' => $row['Api'],
            'Model' => $row['Model']
        ];
    }
    $result->free();
    return ['status' => 'success', 'message' => 'Haku tehty onnistuneesti', 'responses' => $responses];
}