<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="ISO-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arkisto</title>
</head>
<body>
    
</body>
</html>

<?php
$servername = "localhost";
$username = "esimerkki";
$password = "ikkremise";
$dbname = "localhost";

$url = 'https://harto.wordpress.com/page/4/';
$html_content = @file_get_contents($url);
$dom = new DOMDocument();
@$dom->loadHTML($html_content);

$xpath = new DOMXPath($dom);
$nodeArticles = $xpath->query("//article");
$nodeEntryContent = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " entry-content ")]');
$extracted_content_string = [];
//var_dump($nodeEntryContent[0]);
for($x = 0; $x < $nodeEntryContent->length; $x++) {
    $target_div = $nodeEntryContent->item($x);
    $extracted_content_string[] = $dom->saveXML($target_div);
}
//echo $extracted_content_string[0];

if($nodeArticles->length > 0) {
    //var_dump($nodeArticles[0]);
    foreach ($nodeArticles AS $article) {
        $nodeAuthor = $xpath->query("//a[@rel='author']", $article);
        $nodeOtsikko = $xpath->query("//*[@class='entry-title']/a[@rel='bookmark']", $article);
        $nodeDate = $xpath->query("//time/@datetime", $article);
        $nodeBookmarks = $xpath->query("//a[@rel='bookmark']", $article);
        $nodeSisalto = $xpath->query("//div[@class='entry-content']", $article);
    }
    /*var_dump($nodeArticles[4]->nodeValue);
    var_dump($nodeAuthor);
    print $nodeOtsikko[0]->nodeValue;
    var_dump($nodeDate[0]);*/
    //var_dump($nodeSisalto[0]);
    $newDate = date_create($nodeDate[0]->value);
    var_dump($newDate->format('Y-m-d H:i:s'));
    for($x = 0; $x < $nodeArticles->length; $x++) {
        print $nodeDate[$x]->nodeValue . " Nodeotsikko:" . $nodeOtsikko[$x]->nodeValue . " " . $nodeArticles[$x]->nodeName . " " . $nodeAuthor[$x]->nodeValue;
        echo "<br>";
    }
    echo "<br>";
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "Connected successfully";
    echo "<br>";
    /*for($x = 0; $x < 3; $x++) {
        $date = date_create($nodeDate[$x]->value);
        $dateString = $date->format('Y-m-d H:i:s');
        echo $dateString;
        $otsikko = $nodeOtsikko[$x]->nodeValue;
        $artikkeli = $extracted_content_string[$x];
        $kirjoittaja = $nodeAuthor[$x]->nodeValue;
        $sql = "INSERT INTO artikkelit3 (aika, otsikko, sisalto, kirjoittaja)
        VALUES ('$dateString', '$otsikko', '$artikkeli', '$kirjoittaja')"; 
        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }*/
}
    ?>