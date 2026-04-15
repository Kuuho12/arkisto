<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if(empty($username) || empty($password)) {
        $error = 'Käyttäjätunnus ja salasana eivät saa olla tyhjiä';
    } else {
        $phpmyadmin_username = "tekoalytestaus";
        $phpmyadmin_password = getenv('PHPMYADMIN_PASSWORD');
        $servername = "localhost";
        $databasename = "tekoalytestaus";
        
        $conn = new mysqli($servername, $phpmyadmin_username, $phpmyadmin_password, $databasename);
        if ($conn->connect_error) {
            $error = 'Tietokantayhteys epäonnistui: ' . $conn->connect_error;
        } else {
            $sql = "SELECT * FROM Users WHERE Username = '$username'";
            $result = $conn->query($sql);
            if($result->num_rows === 0) {
                $error = 'Virheellinen käyttäjätunnus tai salasana';
            } else if($result->num_rows > 1) {
                $error = 'Tietokantavirhe: useampi käyttäjätunnus löytyy';
            } else if ($result->num_rows === 1 && password_verify($password, $result->fetch_assoc()['Password_hash'])) {
                $_SESSION['user'] = $username;
                header('Location: testisivu.php');
                exit;
            } else {
                $error = 'Virheellinen käyttäjätunnus tai salasana';
            }
        }
    }
}

require_once 'model.php';
$kirjautuminenlomake = tulostaKirjautuminenlomake($error ?? '');

$dom = new DOMDocument();
@$html_file = file_get_contents('template/kirjautuminentemplate.html');
$replace_strings = ['[KIRJAUTUMINENLOMAKE]'];
$html_file = str_replace($replace_strings, [$kirjautuminenlomake], $html_file);
@$dom->loadHTML($html_file);
echo $dom->saveHTML();