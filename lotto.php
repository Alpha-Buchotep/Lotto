<?php
//-------------------------------------------------
// Egyszerű lottószám húzó by Jonastos (nCore)
// Ötös, hatos, hetes, Euro Jackpot lottó
// PHP 7+
//----------------------------------------

//----------------------------------------
// ini set - Max. 90 mp feldolgozási idő
//----------------------------------------

ini_set("max_execution_time", "90");

//-------------------------------------------------
// Fejléc / lokalizáció / időzóna + buffer + cache
//-------------------------------------------------
date_default_timezone_set("Europe/Budapest");
setlocale(LC_ALL,"hungarian");
header("Content-Type: text/html; charset=utf-8");
header("X-Accel-Buffering: no");
header("Pragma: no-cache");
header("Cache-Control: nocache");
header("Expires: Wed, 19 Mar 1986 21:00:00 GMT");

//-------------------------------------------------
// Ha nincs futó munkamenet, elindítjuk 
// nem szükséges a program működéséhez
//-------------------------------------------------
// if(!isset($_SESSION)): session_start();endif;
//-------------------------------------------------

?>
<!doctype html>
<html>
<head>
<title>Lottószám generátor</title>
</head>
<body>
<!-- CSS stílusok -->
<style>
body {
	background-color: #1e1e1e;
	color: #dfdfdf;
	font-family: Tahoma;
	font-size: 18px;
}

select {
    height: 36px;
    font-size: 18px;
    background-color: #1b1b1b;
    color: #f0f0f0;
	padding: 6px;
}

select:hover {
    height: 36px;
    font-size: 18px;
    background-color: #003d65;
    color: #ffffff;
}

input[type=button], input[type=submit] {
    height: 36px;
    font-size: 18px;
    background-color: #1b1b1b;
    color: #f0f0f0;
	border: 1px solid #f0f0f078;
	padding: 6px;
}

input[type=button]:hover, input[type=submit]:hover {
    height: 36px;
    font-size: 18px;
    background-color: #003d65;
    color: #f0f0f0;
	border: 1px solid #f0f0f088;
	padding: 6px;
}

.vezerlok {
	width: 35%;
	max-width: 35% !important;
	margin-top: 5px;
	margin-bottom: 5px;
}

@media (max-width:1200px) {
	.vezerlok {
		max-width: 40% !important;
		width: 40%;
	}
}

@media (max-width:1100px) {
	.vezerlok {
		max-width: 43% !important;
		width: 43%;
	}
}

@media (max-width:1000px) {
	.vezerlok {
		max-width: 45% !important;
		width: 45%;
	}
}

@media (max-width:900px) {
	.vezerlok {
		max-width: 48% !important;
		width: 48%;
	}
}

@media (max-width:800px) {
	.vezerlok {
		max-width: 100% !important;
		width: 100%;
	}
}

.szazasDiv {
	width: 100%;
	margin-top: 60px;
	margin-bottom: 15px;
	height: auto;
	text-align: center;
	display: block;
}

.szazasDiv img {
	max-width: 100%;
}

.lottoSzamok {
	margin-top: 10px;
	margin-bottom: 10px;
	border: 1px solid;
	border-color: #f0f0f0;
	border-radius: 6px;
	padding: 20px;
}

.kepArnyek {
	box-shadow: 0px 0px 12px 1px #717171;
	border: none;
}
</style>
<script>function autoScrolling() {window.scrollTo(0,document.body.scrollHeight)};</script>
<?php

//----------------------------------------------------------------
// A lottó típusok adatait tároló tömb
//  az id megegyezik a tömb indexének számával - a tömbök
// elemei(index) a legtöbb programozási nyelvben 0-val kezdődnek
//----------------------------------------------------------------

$lottoAdatok = array(

	// Összes lottó
	array(
		"id"=>0,
		"nev"=>"Ötös / hatos / hetes / Euro Jackpot"
	),
	// ötös lottó
	array(
		"id"=>1,
		"nev"=>"Ötös lottó",
		"kezdoSzam"=>1,
		"zaroSzam"=>90,
		"huzandoSzamok"=>5
	),
	// Hatos lottó
	array(
		"id"=>2,
		"nev"=>"Hatos lottó",
		"kezdoSzam"=>1,
		"zaroSzam"=>45,
		"huzandoSzamok"=>6
	),
	// Hetes (skandináv) lottó
	array(
		"id"=>3,
		"nev"=>"Hetes lottó",
		"kezdoSzam"=>1,
		"zaroSzam"=>35,
		"huzandoSzamok"=>7
	),
	// Euro Jackpot
	array(
		"id"=>4,
		"nev"=>"Euro Jackpot",
		"kezdoSzam"=>1,
		"zaroSzam"=>50,
		"huzandoSzamok"=>5,
		"kezdoSzamB"=>1,
		"zaroSzamB"=>12,
		"huzandoSzamokB"=>2
	)
);

//----------------------------------------------

	// a maximálisan megengedett játékmezők száma, amit paraméterrel / lenyíló menüből meg lehet adni
	$maximumJatekMezok = 30;
	
	// a számkiíró ciklusban használt változó, a várakozási időt tárolja
	// értéke mikroszekundumban van (µs) (1 másodperc = 1000000 µs)
	// értékadás később
	$varakozasiIdo;

	// lottó típusa - ha nincs kiválasztva semmelyik típus a felhasználó által, akkor 0-ra állítjuk (összes lottó)
	$lottoTipus = isset($_GET["tipus"]) ? $_GET["tipus"] : 0;
	
	// játékmezők száma paraméter - ha a felhasználó által nincs beállítva, akkor alapértelmezetten 2-re állítjuk be
	$jatekMezokSzama = isset($_GET["jatekMezok"]) ? $_GET["jatekMezok"] : 2;

	// késleltetés paraméter - 0-4 másodperc között, ha a felhasználó által nincs beállítva, akkor alapértelmezetten 0.8mp-re állítjuk
	$kesleltetes = isset($_GET["kesleltetes"]) ? $_GET["kesleltetes"] : 8;

	// Autoscroll paraméter - ha a felhasználó által nincs beállítva, akkor alapértelmezetten 1-re (van) állítjuk 
	$autoscroll = isset($_GET["autoscroll"]) ? $_GET["autoscroll"] : 1;
	
	// ellenőrzés > ha a lottó típusa paraméter nem szám, akkor beállítjuk a lotto típust 0-ra (összes lottó típus)
	if(is_numeric($lottoTipus) === false) {$lottoTipus = 0;}

	// ellenőrzés > ha a játékmezők száma paraméter nem szám, beállítjuk az alapértelmezett játékmezők számát kettőre (2)
	if(is_numeric($jatekMezokSzama) === false) {$jatekMezokSzama = 2;}

	// ellenőrzés > ha a késleltetés paraméter nem szám, akkor a késleletetést 0.8 mp-re állítjuk be
	if(is_numeric($kesleltetes) === false) {$kesleltetes = 8;}

	// ellenőrzés > ha az autoscroll paraméter nem szám, akkor a 1-re állítjuk az értéket (van)
	if(is_numeric($autoscroll) === false) {$autoscroll = 1;}

	// ellenőrzés - csak a 0 és 4 közötti lottó típusokat engedjük, mivel csak ezek vannak
	if($lottoTipus >=0 || $lottoTipus <= 4) {$lottoTipus = $lottoTipus;} else {$lottoTipus = 0;}

	// játékmezők számának korlátozása, ha több, mint a megengedett maximumot próbálnánk megadni
	if($jatekMezokSzama > $maximumJatekMezok) {$jatekMezokSzama = $maximumJatekMezok;}

	// játékmezők számának beállítása 2-re, ha a paraméter 0 vagy negatív szám
	if($jatekMezokSzama <= 0) {$jatekMezokSzama = 2;}

	// késleltetés ellenőrzése > min/max értékek, hogy a paraméterrel ne lehessen babrálni
	if($kesleltetes < 0) {$kesleltetes = 8;} elseif ($kesleltetes > 50) {$kesleltetes = 8;}

	// autoscroll érték ellenőrzése > min. 0 / max. 1
	if($autoscroll < 0) {$autoscroll = 1;} elseif ($autoscroll > 1) {$autoscroll = 1;}

	// döntés arról, melyik lottó típust választottuk (alapértelmezett játék (default): összes lottó -> id=0 típus)
	switch ($lottoTipus) {

		// Összes lottó
		case 0:
		$lottoJatekNeve = $lottoAdatok[0]["nev"];
		break;

		// Ötös lottó
		case 1:
		$lottoJatekNeve = $lottoAdatok[1]["nev"];
		break;

		// Hatos lottó
		case 2:
		$lottoJatekNeve = $lottoAdatok[2]["nev"];
		break;

		// Hetes lottó
		case 3:
		$lottoJatekNeve = $lottoAdatok[3]["nev"];
		break;

		// Euro Jackpot
		case 4:
		$lottoJatekNeve = $lottoAdatok[4]["nev"];
		break;

		// Alapértelmezett lottó típus 
		// csak akkor érvényesül, ha nincs értelmezhető paraméter / választás
		default:
		$lottoTipus = 0;
		$lottoJatekNeve = $lottoAdatok[0]["nev"];
	}

	// Várakozás kezelése
	// 0 - 4 másodperc között lehet kérni
	// a form lenyíló menüjét egy for ciklus tölti fel,
	// a Value értékei +=2 értékkel növekszenek azért,
	// hogy a lenyíló menü barátságos szövegeit a
	// ciklus $i/10 értékével lehessen kiiratni
	// pl. ha a $i = 18, akkor
	// a barátságos szöveg > 18/10 = 1.8 másodperc
	//----------------------------------------------------
	// a 0 értéket azért nem vizsgáljuk, mert azt a számokat
	// kiíró függvény ellenőrzi, ugyanis ha az érték 0, akkor
	// nincs késleltetés, tehát le sem fut a késleltetést
	// kezelő elágazás

	switch ($kesleltetes) {
		case 2:
			$varakozasiIdo = 200000;
			break;
		case 4:
			$varakozasiIdo = 400000;
			break;
		case 6:
			$varakozasiIdo = 600000;
			break;
		case 8:
			$varakozasiIdo = 800000;
			break;
		case 10:
			$varakozasiIdo = 1000000;
			break;
		//---------------------------
		case 12:
			$varakozasiIdo = 1200000;
			break;
		case 14:
			$varakozasiIdo = 1400000;
			break;
		case 16:
			$varakozasiIdo = 1600000;
			break;
		case 18:
			$varakozasiIdo = 1800000;
			break;
		case 20:
			$varakozasiIdo = 2000000;
			break;
		//---------------------------
		case 22:
			$varakozasiIdo = 220000;
			break;
		case 24:
			$varakozasiIdo = 2400000;
			break;
		case 26:
			$varakozasiIdo = 2600000;
			break;
		case 28:
			$varakozasiIdo = 2800000;
			break;
		case 30:
			$varakozasiIdo = 3000000;
			break;
		//---------------------------
		case 32:
			$varakozasiIdo = 3200000;
			break;
		case 34:
			$varakozasiIdo = 3400000;
			break;
		case 36:
			$varakozasiIdo = 3600000;
			break;
		case 38:
			$varakozasiIdo = 3800000;
			break;
		//---------------------------
		case 40:
			$varakozasiIdo = 4000000;
			break;
		case 42:
			$varakozasiIdo = 4200000;
			break;
		case 44:
			$varakozasiIdo = 4400000;
			break;
		case 46:
			$varakozasiIdo = 4600000;
			break;
		case 48:
			$varakozasiIdo = 4800000;
			break;
		case 50:
			$varakozasiIdo = 5000000;
			break;
		//---------------------------
		default:
		$varakozasiIdo = 8;
	}
	
	//------------------------------------------
	// Számhúzó függvény
	//------------------------------------------
	
	// $ltTip = a lottó típusa (0, 1, 2, 3, 4 - ez alapján tudjuk, hány számot kell húzni)
	// $kSz = húzható szám minimum (1)
	// $zSz = húzható szám maximum (35 / 45 / 90 stb.)
	// $ltNeve = a lottó barátságos neve (Ötös lottó, Hatos lottó stb.)
	
	function lottoSzamHuzas($ltTip=int, $kSz=int, $zSz=int, $ltNeve=string) {

		// Csak akkor fut le, ha a $ltTip változó nem üres
		if(!empty($ltTip)) {

			// $veletlenSzam = ez lesz az aktuálisan kihúzott szám
			// $lottoSzamok = a kihúzott lottószámokat tartalmazó tömb
			$veletlenSzam = -1;
			$lottoSzamok = [];

			// Globális változók inicializálása,
			// hogy tudjuk használni a függvényben is
			GLOBAL $kesleltetes;
			GLOBAL $varakozasiIdo;

			// ciklus a lottó típusának megfelelő számmennyiség kihúzásához
			for ($i = 0; $i < $ltTip; $i++) {

				// számhúzás véletlenszerűen a kezdő és záró számok között
				//$veletlenSzam = mt_rand($kSz, $zSz);
				$veletlenSzam = random_int($kSz, $zSz); // PHP 7, PHP 8

				// ellenőrizzük, nem lett-e már kihúzva a most kisorsolt szám
				// ha igen, továbblépünk és levonunk a $i számlálóból 1-t,
				// mivel ez a húzás érvénytelen
				if(in_array($veletlenSzam, $lottoSzamok)) {
					$i-=1;
					continue;
				}
				else {
					// a most kihúzott szám nem lett korábban kisorsolva, ezért beírjuk a tömbbe
					$lottoSzamok[$i] = $veletlenSzam;
				}

			} // ciklus vége

			// tömb rendezése emelkedő sorrendben
			sort($lottoSzamok);

			// ciklus a kihúzott számok kiiratására + formázás
			for ($i = 0; $i < $ltTip; $i++) {

				// ha az utolsó előtti kihúzandó számnál járunk, a sor végére nem teszünk nem törhető szóköz karaktert (&nbsp;)
				if($i == $ltTip-1) {
					// minden második számot külnböző színnel jelenítünk meg
					$stilus = ($i%2 == 0) ? "background-color: #165b88;" : "background-color: #b7003f;";
					echo "<span class='lottoSzamok' style='$stilus'><b style='font-size: 24px;'>" . $lottoSzamok[$i] . "</b></span>";
				}
				// nem az utolsó előtti kihúzandó számnál vagyunk
				else {
					// minden második számot különböző színnel jelenítünk meg
					$stilus = ($i%2 == 0) ? "background-color: #165b88;" : "background-color: #b7003f;";
					echo "<span class='lottoSzamok' style='$stilus'><b style='font-size: 24px;'>" . $lottoSzamok[$i] . "</b></span>&nbsp;";
				}

				// Van késleltetés beállítva, kezeljük
				if ($kesleltetes > 0) {

					//Puffer kiiratása
					flush();
					ob_flush();

					// Az első számot azonnal kíírjuk, nincs késleltetés
					if ($i > 0) {
						usleep($varakozasiIdo);
					}
				}

			} // ciklus vége
			
			// vízszintes vonal rajzolása / formázása
			echo "<hr style='margin-top: 35px; margin-bottom: 25px; border-top: 1px dashed red; width: 60%; opacity: 0.6;'>";

		}
	}
?>

<center>
<!-- Form > Lenyíló menük -->
<form action="lotto.php" method="GET" id="lotto" name="lotto" style="margin-top: 25px;">

<div class="vezerlok">

	<select id="tipus" name="tipus" style="width: 48%;">
		<option value=" " >Lottó típusa</option>

		<?php 

		// ciklussal töltjük fel a lottó típusa lenyíló menüt
		for ($i = 0; $i < 5; $i++) {
			// ha korábban már kijelöltünk egy lottó típust és a ciklus ehhez a típusú lottó listázásához ér, akkor azt kiválasztja
			if($lottoAdatok[$i]["id"] == $lottoTipus && isset($_GET["tipus"]) == true) {

		?>

		<option value="<?php echo $lottoAdatok[$i]["id"]; ?>" selected><?php echo $lottoAdatok[$i]["nev"]; ?></option>

		<?php }
		// nem a korábban kijelölt lottó típust listázza a ciklus vagy még nem jelöltünk meg semmit
		else { ?>

		<option value="<?php echo $lottoAdatok[$i]["id"]; ?>"><?php echo $lottoAdatok[$i]["nev"]; ?></option>

		<?php }} ?>

	</select>

	<select id="jatekMezok" name="jatekMezok" style="width: 48%;">
		<option value=" " >Játékmezők száma</option>

		<?php 

		// ciklussal töltjük fel a játékmezők számát
		for ($i = 1; $i <= $maximumJatekMezok; $i++) {
			// ha korábban már kiválasztottuk a játékmezők számát és a ciklus ehhez a számhoz ér, akkor azt kiválasztja
			if($jatekMezokSzama == $i && isset($_GET["tipus"]) == true) {

		?>

		<option value="<?php echo $i; ?>" selected><?php echo $i; ?></option>

		<?php }

		// nem a korábban kiválasztott játékmezők számánál tart a ciklus
		else { ?>

		<option value="<?php echo $i; ?>"><?php echo $i; ?></option>

		<?php }} ?>

	</select>

	<br />

	<select id="kesleltetes" name="kesleltetes" style="width: 48%;">
		<option value=" " >Késleltetés</option>

		<?php 

		// ciklussal töltjük fel a késleltetési választómezőt
		for ($i = 0; $i <= 50; $i+=2) {
			if($kesleltetes == $i && isset($_GET["kesleltetes"]) == true) {

		?>

		<option value="<?php echo $i; ?>" selected><?php if ($i == 0) {echo "Nincs késleltetés";} else {echo $i/10 . " másodperc";}?></option>

		<?php }

		// nem a korábban kiválasztott játékmezők számánál tart a ciklus
		else {

		?>

		<option value="<?php echo $i; ?>"><?php if ($i == 0) {echo "Nincs késleltetés";} else {echo $i/10 . " másodperc";}?></option>

		<?php }} ?>

	</select>

	<select id="autoscroll" name="autoscroll" style="width: 48%;">
		<option value=" " >Autoscroll</option>
		<?php if($autoscroll == 0) { ?>
		<option value="0" selected>Nincs autoscroll</option>
		<option value="1">Van autoscroll</option>
		<?php } elseif($autoscroll == 1) { ?>
		<option value="0">Nincs autoscroll</option>
		<option value="1" selected>Van autoscroll</option>
		<?php } else { ?>
		<option value="0">Nincs autoscroll</option>
		<option value="1" selected>Van autoscroll</option>
		<?php } ?>
	</select>

</div>

	<div class="vezerlok">
	<input style="width: 48%;" type="submit" value=" Sorsoljunk! " />&nbsp;<input style="width: 48%;" type="button" value=" Újrakezdem! " onclick="javascript: location.replace('./lotto.php');" />
	</div>
	</form>

	<div id="relax" class="szazasDiv">
		<center>
			<img src="loader_macska4.gif" class="kepArnyek" alt="" title="" />
		</center>
	</div>

</center>

<?php

// csak akkor fut le az alábbi kód, ha a HTTP metódus GET és a lottó típusa ki van választva

if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["tipus"])){

	// PHP-vel írunk a html oldalra Javascript kódot - ez a Javascript kód eltünteti a 'relax' nevű div-et
	// + Auto scroll start
	echo "<script>document.getElementById('relax').style.display = 'none';</script>";

	// Auto scroll start
	if($autoscroll == 1) {
		echo "<script>auScr = setInterval(autoScrolling, 1000);</script>";
	}

	//------------------------------------------
	// Döntés arról, hogy csak egy típusú lottót játszunk, vagy
	// az összes lottó játékhoz szeretnénk számokat húzni
	//------------------------------------------

	// egy típusú lottó (csak ötös, hatos, hetes stb. - ha 0 lenne, az az összes lottót jelentené)
	if($lottoTipus != 0) {

		echo "<center><h2><b>A(z) " . $lottoJatekNeve . " számok húzása</b></h2>";
		echo "<hr style='margin-top: 20px; margin-bottom: 10px; border-top: 1px dashed red; width: 60%; opacity: 0.6;'>";

		// Euro Jackpot számok húzása - 4-es típus (1x5 + 1x2 db szám > A és B mezők)
		if($lottoTipus == 4) {
			echo "<h3 style='color: #ffd77c; font-size: 28px;'><b>" . $lottoJatekNeve . "</b></h3><br />";
			// ciklussal annyiszor hívjuk meg a lottoSzamHuzas függvényt, ahány játékmező lett kiválasztva
			for ($i = 0; $i < $jatekMezokSzama; $i++) {

				// Számok húzása > Euro Jackpot A mező
				echo "<b style='font-size: 24px;'>A játékmező " . ($i+1) . "<br /><br />";
				// lottoSzamHuzas függvény hívása a tömbböl kikeresett paraméterekkel
				lottoSzamHuzas($lottoAdatok[$lottoTipus]["huzandoSzamok"], $lottoAdatok[$lottoTipus]["kezdoSzam"], $lottoAdatok[$lottoTipus]["zaroSzam"], $lottoAdatok[$lottoTipus]["nev"]);

				// Számok húzása > Euro Jackpot B mező
				echo "<b style='font-size: 24px;'>B játékmező " . ($i+1) . "<br /><br />";
				// lottoSzamHuzas függvény hívása a tömbböl kikeresett paraméterekkel
				lottoSzamHuzas($lottoAdatok[$lottoTipus]["huzandoSzamokB"], $lottoAdatok[$lottoTipus]["kezdoSzamB"], $lottoAdatok[$lottoTipus]["zaroSzamB"], $lottoAdatok[$lottoTipus]["nev"]);
			}
		}
		elseif ($lottoTipus == 3) {
		// Hetes lottó húzása - 3-as típus (1x7 kézi + 1x7 gépi számok)
			echo "<h3 style='color: #ffd77c; font-size: 28px;'><b>" . $lottoJatekNeve . "</b></h3><br />";
			// ciklussal annyiszor hívjuk meg a lottoSzamHuzas függvényt, ahány játékmező lett kiválasztva
			for ($i = 0; $i < $jatekMezokSzama; $i++) {

				echo "<b style='font-size: 24px;'>" . ($i+1) . ". játékmező (kézi)<br /><br />";
				// lottoSzamHuzas függvény hívása - 3 = hetes lottó kézi
				lottoSzamHuzas($lottoAdatok[3]["huzandoSzamok"], $lottoAdatok[3]["kezdoSzam"], $lottoAdatok[3]["zaroSzam"], $lottoAdatok[3]["nev"]);

				echo "<b style='font-size: 24px;'>" . ($i+1) . ". játékmező (gépi)<br /><br />";
				// lottoSzamHuzas függvény hívása - 3 = hetes lottó gépi
				lottoSzamHuzas($lottoAdatok[3]["huzandoSzamok"], $lottoAdatok[3]["kezdoSzam"], $lottoAdatok[3]["zaroSzam"], $lottoAdatok[3]["nev"]);
			}
		}			
		else {

		// Számok húzása normál lottó (5 / 6) - ahány játékmező ki lett választva
		echo "<h3 style='color: #ffd77c; font-size: 28px;'><b>" . $lottoJatekNeve . "</b></h3><br />";
		for ($i = 0; $i < $jatekMezokSzama; $i++) {
			// Számok húzása -> csak egy fajta lottó típus
			echo "<b style='font-size: 24px;'>" . ($i+1) . ". játékmező<br /><br />";
			// lottoSzamHuzas függvény hívása a tömbböl kikeresett paraméterekkel
			lottoSzamHuzas($lottoAdatok[$lottoTipus]["huzandoSzamok"], $lottoAdatok[$lottoTipus]["kezdoSzam"], $lottoAdatok[$lottoTipus]["zaroSzam"], $lottoAdatok[$lottoTipus]["nev"]);
		}
		
		}
		echo "</center>";

	}

	// összes lottó típusból húzunk számokat
	elseif ($lottoTipus == 0) {

		echo "<div style='text-align: center;'><h2><b>Az " . $lottoJatekNeve . " számok húzása</b></h2>";
		echo "<hr style='margin-top: 20px; margin-bottom: 10px; border-top: 1px dashed red; width: 60%; opacity: 0.6;'>";

		// Számok húzása -> Ötös lottó - ahány játékmező ki lett választva
		echo "<h3 style='color: #ffd77c; font-size: 28px;'><b>Ötös lottó</b></h3><br />";
		for ($i = 0; $i < $jatekMezokSzama; $i++) {
			echo "<b style='font-size: 24px;'>" . ($i+1) . ". játékmező<br /><br />";
			// lottoSzamHuzas függvény hívása - 1 = ötös lottó
			lottoSzamHuzas($lottoAdatok[1]["huzandoSzamok"], $lottoAdatok[1]["kezdoSzam"], $lottoAdatok[1]["zaroSzam"], $lottoAdatok[1]["nev"]);
		}

		// Számok húzása -> Hatos lottó - ahány játékmező ki lett választva
		echo "<h3 style='color: #ffd77c; font-size: 28px;'><b>Hatos lottó</b></h3><br />";
		for ($i = 0; $i < $jatekMezokSzama; $i++) {
			echo "<b style='font-size: 24px;'>" . ($i+1) . ". játékmező<br /><br />";
			// lottoSzamHuzas függvény hívása - 2 = hatos lottó
			lottoSzamHuzas($lottoAdatok[2]["huzandoSzamok"], $lottoAdatok[2]["kezdoSzam"], $lottoAdatok[2]["zaroSzam"], $lottoAdatok[2]["nev"]);
		}

		// Számok húzása -> Hetes lottó - ahány játékmező ki lett választva
		echo "<h3 style='color: #ffd77c; font-size: 28px;'><b>Hetes lottó</b></h3><br />";
		for ($i = 0; $i < $jatekMezokSzama; $i++) {

			echo "<b style='font-size: 24px;'>" . ($i+1) . ". játékmező (kézi)<br /><br />";
			// lottoSzamHuzas függvény hívása - 3 = hetes lottó kézi
			lottoSzamHuzas($lottoAdatok[3]["huzandoSzamok"], $lottoAdatok[3]["kezdoSzam"], $lottoAdatok[3]["zaroSzam"], $lottoAdatok[3]["nev"]);

			echo "<b style='font-size: 24px;'>" . ($i+1) . ". játékmező (gépi)<br /><br />";
			// lottoSzamHuzas függvény hívása - 3 = hetes lottó gépi
			lottoSzamHuzas($lottoAdatok[3]["huzandoSzamok"], $lottoAdatok[3]["kezdoSzam"], $lottoAdatok[3]["zaroSzam"], $lottoAdatok[3]["nev"]);
		}

		// Számok húzása -> Euro Jackpot - ahány játékmező ki lett választva (A és B mező)
		echo "<h3 style='color: #ffd77c; font-size: 28px;'><b>Euro Jackpot</b></h3><br />";
		for ($i = 0; $i < $jatekMezokSzama; $i++) {

			// Számok húzása > Euro Jackpot A mező
			echo "<b style='font-size: 24px;'>A játékmező " . ($i+1) . "<br /><br />";
			// lottoSzamHuzas függvény hívása A mező - 4 = Euro Jackpot
			lottoSzamHuzas($lottoAdatok[4]["huzandoSzamok"], $lottoAdatok[4]["kezdoSzam"], $lottoAdatok[4]["zaroSzam"], $lottoAdatok[4]["nev"]);
		
			// Számok húzása > Euro Jackpot B mező
			echo "<b style='font-size: 24px;'>B játékmező " . ($i+1) . "<br /><br />";
			// lottoSzamHuzas függvény hívása B mező - 4 = Euro Jackpot
			lottoSzamHuzas($lottoAdatok[4]["huzandoSzamokB"], $lottoAdatok[4]["kezdoSzamB"], $lottoAdatok[4]["zaroSzamB"], $lottoAdatok[4]["nev"]);
		}

		echo "</div>";

	}

	// Auto scroll stop
	if($autoscroll == 1) {
		echo "<script>clearInterval(auScr);window.scrollTo(0,document.body.scrollHeight);</script>";
	}
	
}
?>
</body>
</html>
