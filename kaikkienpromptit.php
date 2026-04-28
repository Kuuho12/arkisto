<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: kirjautuminen.php');
    exit;
}
$otsikko = "Kaikkien promptit";

$user = $_SESSION['user'];
$tietokantaError = null;
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

$stmt = $conn->prepare(
    "SELECT Id, Created_at, Prompt, Gemini, Hugging_Face, OpenAI, Gemini_model, Hugging_Face_model, OpenAI_model FROM Prompts"
);
$sqlTulos = $stmt->execute();
if (!$sqlTulos) {
    $tietokantaError = 'Virhe tietokantahauissa: ' . $conn->error;
}
$result = $stmt->get_result();
$prompts = [];
while($row = $result->fetch_assoc()) {
    $prompts[] = [
        'Id' => $row['Id'],
        'Created_at' => $row['Created_at'],
        'Prompt' => $row['Prompt'],
        'Gemini' => (bool)$row['Gemini'],
        'Hugging_Face' => (bool)$row['Hugging_Face'],
        'OpenAI' => (bool)$row['OpenAI'],
        'Gemini_model' => $row['Gemini_model'],
        'Hugging_Face_model' => $row['Hugging_Face_model'],
        'OpenAI_model' => $row['OpenAI_model']
    ];
}
usort($prompts, function($a, $b) {
    return $a['Id'] - $b['Id'];
});

require_once 'model.php';
$header = tulostaPromptHeader($user, $otsikko);
$kaikkienpromptitkeskiosa = tulostaKaikkienPromptitKeskiosa();
$promptitListaus = tulostaPromptitListaus($prompts, "Kaikkien promptien listaus:");

$dom = new DOMDocument();
@$html_file = file_get_contents('template\kaikkienpromptittemplate.html');
$replace_strings = ['[KAIKKIENPROMPTITHEADER]', '[KAIKKIENPROMPTITKESKIOSA]', '[PROMPTITLISTAUS]'];
$html_file = str_replace($replace_strings, [$header, $kaikkienpromptitkeskiosa, $promptitListaus], $html_file);
echo $html_file;

?>