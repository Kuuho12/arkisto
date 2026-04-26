<?php // Nimi oli ennen testisivu.php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: kirjautuminen.php');
    exit;
}

$promptTeksti = "";
$Gemini = false;
$HuggingFace = false;
$OpenAI = false;
$Gemini_model = "gemini-2.5-flash";
$HuggingFace_model = "Qwen/Qwen3-32B:groq";
$OpenAI_model = "gpt-5-nano";
$responses = null;

$promptId = $_GET['id'] ?? null;

if($promptId !== null) {

    $phpmyadmin_username = "tekoalytestaus";
    $phpmyadmin_password = getenv('PHPMYADMIN_PASSWORD');
    $servername = "localhost";
    $databasename = "tekoalytestaus";

    $conn = new mysqli($servername, $phpmyadmin_username, $phpmyadmin_password, $databasename);
    if ($conn->connect_error) {
        $error = 'Tietokantayhteys epäonnistui: ' . $conn->connect_error;
    } else {
        $stmt = $conn->prepare(
            "SELECT Prompt, Gemini, Hugging_Face, OpenAI, Gemini_model, Hugging_Face_model, OpenAI_model FROM Prompts WHERE Id = ?"
        );
        $stmt->bind_param('i', $promptId);
        $sqlTulos = $stmt->execute();
        $result = $stmt->get_result();
        if(!$sqlTulos) {
            $error = 'Virhe tietokantahauissa promptia hakiessa: ' . $conn->error;
        } else if ($result->num_rows == 0) {
            $error = 'Promptia ei löytynyt.';
        } else {
            $promptData = $result->fetch_assoc();
            $promptTeksti = $promptData['Prompt'];
            $Gemini = (bool)$promptData['Gemini'];
            $HuggingFace = (bool)$promptData['Hugging_Face'];
            $OpenAI = (bool)$promptData['OpenAI'];
            $Gemini_model = ($promptData['Gemini_model'] == '' ? $Gemini_model : $promptData['Gemini_model']);
            $HuggingFace_model = ($promptData['Hugging_Face_model'] == '' ? $HuggingFace_model : $promptData['Hugging_Face_model']);
            $OpenAI_model = ($promptData['OpenAI_model'] == '' ? $OpenAI_model : $promptData['OpenAI_model']);
        }
        $result->free();
        if($sqlTulos) {
            $stmt = $conn->prepare(
                "SELECT Created_at, Group_code, Response, Api, Model FROM Ai_responses WHERE Prompt_id = ?"
            );
            $stmt->bind_param('i', $promptId);
            $sqlTulos = $stmt->execute();
            if (!$sqlTulos) { // Se ettei löydy vastauksia ei aiheuta virhettä
                $error2 = 'Virhe tietokantahauissa vastauksia hakiessa: ' . $conn->error;
            }
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $responses = [];
                while($row = $result->fetch_assoc()) {
                    $responses[] = [
                        'Created_at' => $row['Created_at'],
                        'GroupCode' => $row['Group_code'],
                        'Response' => $row['Response'],
                        'Api' => $row['Api'],
                        'Model' => $row['Model']
                    ];
                }
            }
            $result->free();
        }
    }
}

require_once 'model.php';
$tekoalytestaus = tulostaTekoalytestaus($promptTeksti, $Gemini, $HuggingFace, $OpenAI, $Gemini_model, $HuggingFace_model, $OpenAI_model, $promptId, $responses, [$error ?? null, $error2 ?? null]);

$dom = new DOMDocument();
@$html_file = file_get_contents('template\testisivutemplate.html');
$replace_strings = ['[TEKOALYTESTAUS]'];
$html_file = str_replace($replace_strings, [$tekoalytestaus], $html_file);
@$dom->loadHTML($html_file);
echo $dom->saveHTML();