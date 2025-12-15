<?php
//require_once 'vendor/autoload.php';
require 'model.php';
$listaMaara = 10;

$sqlModel = new SQLHaku();

$queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$params = [];
parse_str($queryString, $params);
$id = $params['id'];
$sivu = 1;
if(preg_match('/\bsivu\b/', $queryString)) {
$sivu = (int) $params['sivu'];
}

$row = $sqlModel->haeArtikkeliIdlla($id);
$artikkeli = tulostaArtikkeli($row);

$all_rows = $sqlModel->haeListatus($listaMaara, $sivu, $id);
$taulunPituus = $sqlModel->haeCount();
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$tulostukset = tulostaListausJaLinkit($all_rows, $taulunPituus, $listaMaara, $url, $sivu, $id);
$listaus = $tulostukset['listaus'];
$linkit = $tulostukset['linkit'];

/*$html_string = '<!DOCTYPE html>
<html lang="fi">

<head>
    <meta charset="ISO-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="index.css">
    <title>Artikkeli</title>
</head>

<body>
    <div id="artikkeli"> ' . $artikkeli . '
    </div>
    <div id="uusimmat"> ' . $listaus . '
        </div>
        <div id="linkit"> '. $linkit . '
    </div>
</body>

</html>';*/
/*$html_string = '
<!DOCTYPE HTML>
<!--
	Future Imperfect by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
-->
<html>
	<head>
		<title>Artikkeli</title>
		<meta charset="ISO-8859-1" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="assets/css/main.css" />
        <link rel="stylesheet" href="index.css" />
	</head>
	<body class="is-preload">

		<!-- Wrapper -->
			<div id="wrapper">

				<!-- Header -->
					<header id="header">
						<h1><a href="index.php">Arkisto</a></h1>
					</header>

				<!-- Main -->
					<div id="main">

                            ' . $artikkeli . '

					</div>

				<!-- Sidebar -->
					<section id="sidebar">

						<!-- Intro -->
							<!--<section id="intro">
								<header>
									<h2>Arkisto</h2>
                                    <p>Lisää luettavaa:</p>
								</header>
							</section>-->

						<!-- Posts List -->
							<section>
								<ul class="posts">[LISTAUS]
                                    ' . $listaus . '
								</ul>
							</section>
                            
                            <section class="blurb" id="linkit">
                            ' . $linkit . '
							</section>
					</section>

			</div>

		<!-- Scripts -->
			<script src="assets/js/jquery.min.js"></script>
			<script src="assets/js/browser.min.js"></script>
			<script src="assets/js/breakpoints.min.js"></script>
			<script src="assets/js/util.js"></script>
			<script src="assets/js/main.js"></script>

	</body>
</html>'*/;
$dom = new DOMDocument();
@$html_file = file_get_contents('template/template.html');
$replace_strings = ['[ARTIKKELI]', '[LISTAUS]', '[LINKIT]'];
$html_file = str_replace($replace_strings, [$artikkeli, $listaus, $linkit], $html_file);
@$html_for_dom = mb_convert_encoding($html_file, 'HTML-ENTITIES', 'UTF-8');
@$dom->loadHTML($html_for_dom);
echo $dom->saveHTML();
?>