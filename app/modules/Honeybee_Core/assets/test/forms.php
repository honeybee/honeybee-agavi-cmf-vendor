<?php
require "../../../../../vendor/autoload.php";

$roles = array('Administrator', 'Redakteur', 'Contentworker', 'Chef vom Dienst', 'Redaktionsleiter', 'Informant');
$workflowsteps = array('Publiziert', 'In Bearbeitung', 'In Abnahme', 'Abgenommen', 'Archiviert');
$faker = Faker\Factory::create();
$users = array();
for ($i=0; $i<50; $i++) {
    $users[$i]['uuid'] = $faker->ipv6;
    $users[$i]['username'] = $faker->username;
    $users[$i]['firstname'] = $faker->firstname;
    $users[$i]['lastname'] = $faker->lastname;
    $users[$i]['role'] = $roles[mt_rand(0, count($roles) - 1)];
    $users[$i]['workflowstep'] = $workflowsteps[mt_rand(0, count($workflowsteps) - 1)];
}
?>
<!DOCTYPE html>
<html lang="en" class="no-svg js-ready">

<head>
    <meta charset='utf-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <title>Module X :: Tableview</title>
<!-- <link rel="stylesheet" href="themes/honeybee-minimal/theme_honeybee-minimal.css"> -->
<link rel="stylesheet" href="step4.css">
<style type="text/css">

.pagination1 li {
    display: inline;
    list-style-type: none;
}

.pagination {
    margin-bottom: 2em;
}

/*
*,*:after,*:before {
  box-sizing:border-box;
  -moz-box-sizing:border-box;
  -webkit-box-sizing:border-box;
}
*/
/*
.cf:before,
.cf:after {
    content:"";
    display:table;
}
.cf:after {
    clear:both;
}
*/
/*
body {
  background: #464646;
  color: #ccc;
  font: 700 12px/18px "museo-slab", "Arial", serif;
  text-align: center;
}
*/
/*
p {
  bottom:10px;
  left:10px;
  position:absolute;
}
p a {
	color: #ccc
}
*/
nav.profile ul {
	background-color: #e8eaee;
	background: rgb(255,255,255);
	background: -moz-linear-gradient(top, rgba(255,255,255,1) 0%, rgba(233,235,239,1) 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(255,255,255,1)), color-stop(100%,rgba(233,235,239,1)));
	background: -webkit-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(233,235,239,1) 100%);
	background: -o-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(233,235,239,1) 100%);
	background: -ms-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(233,235,239,1) 100%);
	background: linear-gradient(to bottom, rgba(255,255,255,1) 0%,rgba(233,235,239,1) 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#e9ebef',GradientType=0 );
	border: 1px solid #e6e6e6;
	border-bottom-color: #8e8e8e;
	display: inline-block;
	margin: 70px auto 0;
	position: relative;
	border-radius: 20px;
	box-shadow: 0 0 0 5px #393939, 0 -1px 0 5px rgba(0,0,0,.85), 0 1px 0 5px rgba(255,255,255,.3)
}
nav.profile ul li {
	float: left;
}
nav.profile ul a {
	color: #434343;
	display: block;
	font-weight: 700;
	padding: 10px 17px;
	position: relative;
	text-align: center;
	text-decoration: none;
}
nav.profile ul li:first-child a {
    border-radius: 20px 0 0 20px
}
nav.profile ul li:last-child a {
    border-radius: 0 20px 20px 0
}
nav.profile ul a:hover {
    background: #eee;
    background: rgb(255,255,255);
    background: -moz-linear-gradient(top, rgba(255,255,255,1) 0%, rgba(244,245,247,1) 100%);
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(255,255,255,1)), color-stop(100%,rgba(244,245,247,1)));
    background: -webkit-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(244,245,247,1) 100%);
    background: -o-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(244,245,247,1) 100%);
    background: -ms-linear-gradient(top, rgba(255,255,255,1) 0%,rgba(244,245,247,1) 100%);
    background: linear-gradient(to bottom, rgba(255,255,255,1) 0%,rgba(244,245,247,1) 100%);
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#f4f5f7',GradientType=0 );
}
nav.profile ul a:active {
    background:rgba(0,0,0,.01);
}
nav.profile ul a:before {
    border-left: 1px dotted #989ca8;
    content: "";
    height: 17px;
    left: 0;
    position: absolute;
    top: 11px;
    width: 1px;
}
nav.profile ul li:first-child a:before {
    display: none
}
.new:after {
	background: rgb(210,103,103);
	background: -moz-linear-gradient(top, rgba(210,103,103,1) 0%, rgba(246,151,151,1) 78%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(210,103,103,1)), color-stop(78%,rgba(246,151,151,1)));
	background: -webkit-linear-gradient(top, rgba(210,103,103,1) 0%,rgba(246,151,151,1) 78%);
	background: -o-linear-gradient(top, rgba(210,103,103,1) 0%,rgba(246,151,151,1) 78%);
	background: -ms-linear-gradient(top, rgba(210,103,103,1) 0%,rgba(246,151,151,1) 78%);
	background: linear-gradient(to bottom, rgba(210,103,103,1) 0%,rgba(246,151,151,1) 78%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#d26767', endColorstr='#f69797',GradientType=0 );
	border: 1px solid #8e4f4f;
	content: attr(attr-new);
	color: #fff;
	font-size: 12px;
	font-weight: 500;
	line-height: 12px;
	padding: 6px 9px;
	position: absolute;
	right: 0;
	top: -15px;
	z-index: 99;
	border-radius: 25px;
	box-shadow: 0 0 3px #000
}



table button {
    /*border: 1px solid black;
    border-radius: 0;*/
    background: none;
    /*background: blue;*/
    /*color: black;*/
    /*text-shadow: none;*/
    /*background: -moz-linear-gradient(top, rgba(210,103,103,1) 0%, rgba(246,151,151,1) 78%);*/
    /*background: linear-gradient(to bottom, rgba(210,103,103,1) 0%,rgba(246,151,151,1) 78%);
    text-shadow: 2px 2px 4px #00ff00;
    border-radius: 5px;
    box-shadow: 0 0 10px #00f;*/
}











#nav {
    background: #333333;
}
.block {
  position: relative;
  margin: 0 auto;
  padding: 1.5em 1.25em;
  max-width: 60em;
}

.close-btn {
  display: block;
  width: 2.625em;
  height: 2.25em;
  padding: 0;
  border: 0;
  outline: none;
  background: #333333 url("close-btn.svg") left center no-repeat;
  background-size: 1.875em 1.875em;
  overflow: hidden;
  white-space: nowrap;
  text-indent: 100%;
  filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=100);
  opacity: 1;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}
.no-svg .close-btn {
  background-image: url("close-btn.png");
}
.close-btn:focus, .close-btn:hover {
  filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=100);
  opacity: 1;
}

.nav-btn {
  display: block;
  width: 2.625em;
  height: 2.25em;
  padding: 0;
  border: 0;
  outline: none;
  background: #333333 url("nav-icon.svg") left center no-repeat;
  background-size: 1.875em 1.5em;
  overflow: hidden;
  white-space: nowrap;
  text-indent: 100%;
  filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=70);
  opacity: 0.7;
  -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
}
.no-svg .nav-btn {
  background-image: url("nav-icon.png");
}
.nav-btn:hover, .nav-btn:focus {
  filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=100);
  opacity: 1;
}

#outer-wrap {
  position: relative;
  overflow: hidden;
  width: 100%;
}

#inner-wrap {
  position: relative;
  width: 100%;
}

#nav {
  z-index: 200;
  position: relative;
  overflow: hidden;
  width: 100%;
  color: #fff;
}
#nav .close-btn {
  display: none;
}
#nav .block-title {
  border: 0;
  clip: rect(0 0 0 0);
  height: 1px;
  margin: -1px;
  overflow: hidden;
  padding: 0;
  position: absolute;
  width: 1px;
}
#nav .block {
  z-index: 2;
  position: relative;
  padding: 0.75em 1.25em;
  background: #333333;
}
#nav ul {
  *zoom: 1;
  display: block;
}
#nav ul:before, #nav ul:after {
  content: "";
  display: table;
}
#nav ul:after {
  clear: both;
}
#nav li {
  display: block;
}
#nav li a {
  display: block;
  color: #ccc;
  font-size: 0.875em;
  line-height: 1.28571em;
  font-weight: bold;
  outline: none;
}
#nav li a:focus, #nav li a:hover {
  color: #fff;
  background: rgba(255, 255, 255, 0.1);
}
#nav li.is-active a {
  color: #fff;
}

#top {
  z-index: 100;
  position: relative;
  color: #fff;
  background: #333333;
}
#top .block-title {
  margin: 0;
  font-size: 1.875em;
  line-height: 1.2em;
  text-align: center;
  white-space: nowrap;
}
#top .nav-btn {
  position: absolute;
  top: 1.5em;
  left: 1.875em;
}

@media screen and (max-width: 450em) {
  #nav {
    position: absolute;
    top: 0;
    padding-top: 5.25em;
  }
  #nav:not(:target) {
    z-index: 1;
    height: 0;
  }
  #nav:target .close-btn {
    display: block;
  }
  #nav .close-btn {
    position: absolute;
    top: -3.75em;
    left: 1.875em;
  }
  #nav .block {
    position: relative;
    padding: 0;
  }
  #nav li {
    position: relative;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }
  #nav li:last-child {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }
  #nav li.is-active:after {
    z-index: 50;
    display: block;
    content: "";
    position: absolute;
    top: 50%;
    right: -0.03125em;
    margin-top: -0.625em;
    border-top: 0.625em transparent solid;
    border-bottom: 0.625em transparent solid;
    border-right: 0.625em white solid;
  }
  #nav li a {
    padding: 0.85714em 2.14286em;
  }
}


</style>

</head>

<body>

<div id="outer-wrap">
<div id="inner-wrap">

    <header id="top" role="banner">
        <div class="block">
            <h1 class="block-title">Module X :: Table View</h1>
            <a class="nav-btn" id="nav-open-btn" href="#nav">Module Navigation</a>
        </div>
    </header>

    <section>

        <h1>Hauptnavigation</h1>
        <nav class="main" id="nav" role="navigation">
            <div class="block">
                <ul>
                    <li class="is-active"><h2>Modul 1</h2>
                        <ul>
                            <li><a href="">Tabelle</a></li>
                            <li><a href="">Liste</a></li>
                            <li><a href="">Hierarchie</a></li>
                            <li><a href="">Nur eigene Datensätze</a></li>
                            <li><a href="">Mir zugewiesene Datensätze</a></li>
                            <li><a href="">Offene Datensätze</a></li>
                        </ul>
                    </li>

                    <li><h2>Modul 2</h2>
                        <ul>
                            <li><a href="">Tabelle</a></li>
                        </ul>
                    </li>
                    <li><h2>Modul 3</h2>
                        <ul>
                            <li><a href="">Liste</a></li>
                        </ul>
                    </li>
                    <li><h2>Modul 4</h2>
                        <ul>
                            <li><a href="">Hierarchie</a></li>
                        </ul>
                    </li>
                    <li><h2>Modul 5</h2>
                        <ul>
                            <li><a href="">Tabelle</a></li>
                            <li><a href="">Liste</a></li>
                            <li><a href="">Hierarchie</a></li>
                        </ul>
                    </li>
                    <li><h2>Modul 6</h2>
                        <ul>
                            <li><a href="">Tabelle</a></li>
                            <li><a href="">Liste</a></li>
                            <li><a href="">Hierarchie</a></li>
                        </ul>
                    </li>
                    <li><h2>Modul 7</h2>
                        <ul>
                            <li><a href="">Tabelle</a></li>
                            <li><a href="">Liste</a></li>
                            <li><a href="">Hierarchie</a></li>
                        </ul>
                    </li>
                    <li><h2>Modul 8</h2>
                        <ul>
                            <li><a href="">Tabelle</a></li>
                            <li><a href="">Liste</a></li>
                            <li><a href="">Hierarchie</a></li>
                        </ul>
                    </li>
                    <li><h2>Modul 9</h2>
                        <ul>
                            <li><a href="">Tabelle</a></li>
                            <li><a href="">Liste</a></li>
                            <li><a href="">Hierarchie</a></li>
                        </ul>
                    </li>
                    <li><h2>Modul 10</h2>
                        <ul>
                            <li><a href="">Tabelle</a></li>
                            <li><a href="">Liste</a></li>
                            <li><a href="">Hierarchie</a></li>
                        </ul>
                    </li>
                    <li><h2>Modul 11</h2>
                        <ul>
                            <li><a href="">Tabelle</a></li>
                            <li><a href="">Liste</a></li>
                            <li><a href="">Hierarchie</a></li>
                        </ul>
                    </li>
                </ul>
                <a class="close-btn" id="nav-close-btn" href="#top">Return to Content</a>
                <input type="search" placeholder="Suchen..."/>
            </div>
        </nav>

        <nav class="profile">
            <ul class="cf">
                <li><a href="#">Profile</a></li>
                <li><a href="#">Settings</a></li>
                <li><a href="#" class="new" attr-new="3">Notification</a></li>
                <li><a href="#">Login</a></li>
            </ul>
        </nav>

    </section>

    <main role="main">
        <form oninput="rangeoutput.value = parseInt(page_range.value)">
        <section>
            <h1>Globale Interaktionen für das Modul</h1>

            <section>
                <h1>Modulspezifische Aktionen</h1>
                <ul>
                    <li><a href="">Neuer Datensatz</a></li>
                    <li><a href="">Import</a></li>
                    <li><a href="">Export</a></li>
                    <li><a href="">Multiupload</a></li>
                </ul>
            </section>

            <section>
                <h1>Suche / Erweiterte Suche (innerhalb des Moduls)</h1>
                <input type="text" name="search" placeholder="Suchen..." />
                <button>Suchen</button>
                <div>
                    <p>
                        Deine Suche trifft auf N Einträge zu.
                    </p>
                </div>
            </section>

            <section>
                <h1>Filter</h1>
                <div>
                    <input type="checkbox" />
                    <input type="text" />
                    <input type="checkbox" />
                    <input type="text" />
                    <input type="radio" name="radiogroup" /><input type="radio" name="radiogroup" /><input type="radio" name="radiogroup" />
                    <input type="checkbox" />
                    <input type="text" />
                    <p>
                        Dein Filter trifft auf N Einträge zu.
                        <button>Filter aufheben</button>
                    </p>
                </div>
            </section>

            <section>
                <h1>Sortierung</h1>
                <div>Sortierung nach
                <select name="sort_by">
                    <option selected>Benutzername</option>
                    <option>Voreinstellung 1</option>
                    <option>Voreinstellung Batch</option>
                    <option>Nachname aufsteigend, dann Vorname absteigend</option>
                    <option>Rolle</option>
                    <option>Status</option>
                </select>
                <select name="sort_by">
                    <option selected>Benutzername</option>
                    <option>Vorname</option>
                    <option>Nachname</option>
                    <option>Rolle</option>
                    <option>Status</option>
                </select>
                <select name="sort_direction">
                    <option selected>absteigend</option>
                    <option>aufsteigend</option>
                </select>
                <select name="sort_by">
                    <option selected>Benutzername</option>
                    <option>Vorname</option>
                    <option>Nachname</option>
                    <option>Rolle</option>
                    <option>Status</option>
                </select>
                <select name="sort_direction">
                    <option selected>absteigend</option>
                    <option>aufsteigend</option>
                </select>
                <button>Go</button>
                </div>
            </section>

            <section>
                <h1>Paginierung</h1>
                <div>Datensätze 21 bis 40 von 11256</div>
                <div class="pagination pagination1">
                    <nav>
                        <ul>
                            <li><a href="">&#8592; Vorherige Seite</a></li>
                            <li><a href="">1</a></li>
                            <li>2</li>
                            <li><a href="">3</a></li>
                            <li><a href="">4</a></li>
                            <li><a href="">10</a></li>
                            <li><a href="">561</a></li>
                            <li><a href="">562</a></li>
                            <li><a href="">Nächste Seite &#8594;</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="pagination pagination2">
                    <button name="prev_page">&#8592; Vorherige Seite</button>
                    Seite:
                    <select name="page">
                        <optgroup label="Seite">
                            <option>1</option>
                            <option selected>2</option>
                            <option>3</option>
                            <option>4</option>
                            <option>5</option>
                            <option>10</option>
                            <option>20</option>
                            <option>50</option>
                            <option>100</option>
                            <option>200</option>
                            <option>500</option>
                            <option>1000</option>
                            <option>letzte Seite</option>
                        </optgroup>
                    </select>
                    <select name="page">
                        <optgroup label="Seite">
                            <option>1</option>
                            <option>5</option>
                            <option>10</option>
                            <option>15</option>
                            <option>16</option>
                            <option>17</option>
                            <option>18</option>
                            <option>19</option>
                            <option selected>20</option>
                            <option>21</option>
                            <option>22</option>
                            <option>23</option>
                            <option>24</option>
                            <option>25</option>
                            <option>30</option>
                            <option>50</option>
                            <option>100</option>
                            <option>200</option>
                            <option>500</option>
                            <option>1000</option>
                            <option>letzte Seite</option>
                        </optgroup>
                    </select>
                    <select name="entries_per_page">
                        <optgroup label="Pro Seite">
                            <option selected>20 pro Seite</option>
                            <option>50 pro Seite</option>
                            <option>100 pro Seite</option>
                        </optgroup>
                    </select>
                    <button>Go</button>
                    <button name="next_page">Nächste Seite &#8594;</button>
                </div>


                <div class="pagination pagination3">
                    <button name="prev_page">&#8592; Vorherige Seite</button>
                    Datensatz:
                    <select name="page">
                        <optgroup label="Springe zum">
                            <option>Anfang</option>
                            <option selected>Datensatz 21</option>
                            <option>Datensatz 41</option>
                            <option>Datensatz 61</option>
                            <option>Datensatz 81</option>
                            <option>Datensatz 200</option>
                            <option>Datensatz 400</option>
                            <option>Datensatz 800</option>
                            <option>Datensatz 1000</option>
                            <option>Datensatz 2000</option>
                            <option>Datensatz 5000</option>
                            <option>Datensatz 10000</option>
                            <option>Ende</option>
                        </optgroup>
                    </select>
                    <select name="entries_per_page">
                        <optgroup label="Pro Seite">
                            <option selected>20 pro Seite</option>
                            <option>50 pro Seite</option>
                            <option>100 pro Seite</option>
                        </optgroup>
                    </select>
                    <button>Go</button>
                    <button name="next_page">Nächste Seite &#8594;</button>
                </div>

                <div class="pagination pagination4">
                    <button name="prev_page">&#8592; Vorherige Seite</button>
                    Seite: <input type="range" name="page_range" min="1" max="562" step="1" value="2" />
                    <output name="rangeoutput" for="page_range" >2</output>
                    <select name="entries_per_page">
                        <optgroup label="Pro Seite">
                            <option selected>20 pro Seite</option>
                            <option>50 pro Seite</option>
                            <option>100 pro Seite</option>
                        </optgroup>
                    </select>
                    <button>Go</button>
                    <button name="next_page">Nächste Seite &#8594;</button>
                </div>

                <div class="pagination pagination5">
                    <button name="prev_page">&#8592; Vorherige Seite</button>
                    Seite: <input type="number" min="1" max="562" step="1" value="2" />
                    <select name="entries_per_page">
                        <optgroup label="Pro Seite">
                            <option selected>20 pro Seite</option>
                            <option>50 pro Seite</option>
                            <option>100 pro Seite</option>
                        </optgroup>
                    </select>
                    <button>Go</button>
                    <button name="next_page">Nächste Seite &#8594;</button>
                </div>
            </section>

            <section>
                <h1>Stapelverarbeitung</h1>
                <ul>
                    <li><button class="btn">Zuordnen</button></li>
                    <li><button class="btn">Löschen</button></li>
                    <li><button class="btn">Publizieren</button></li>
                    <li><button class="btn">Depublizieren</button></li>
                    <li><button class="btn">Kategorien verknüpfen</button></li>
                    <li><button class="btn">Informationen verknüpfen</button></li>
                    <li><button class="btn">Downloads verknüpfen</button></li>
                    <li><button class="btn">Besitzer zuweisen</button></li>
                </ul>
                <select name="batchactions">
                    <option>Zuordnen</option>
                    <option>Löschen</option>
                    <option selected>Publizieren</option>
                    <option>Depublizieren</option>
                    <option>Kategorien verknüpfen</option>
                    <option>Informationen verknüpfen</option>
                    <option>Downloads verknüpfen</option>
                    <option>Besitzer zuweisen</option>
                </select>
                <button>Go</button>
                <h2>Einträge:</h2>
                <ul>
                    <li><button title="Alle Einträge dieser Seite ab-/auswählen">Alle auswählen</button></li>
                    <li><button>Auswahl umkehren</button></li>
                </ul>
            </section>

        </section>

        <hr>

        <section>
            <h1>Tabellenansicht der Datensätze des Moduls</h1>
            <table>
                <thead>
                    <tr>
                        <th>
                            <label title="Alle auswählen"><input type="checkbox" /></label>
                        </th>
                        <th>
                            <a href="#">Nutzername</a>
                        </th>
                        <th>
                            <a href="">Vorname</a>
                        </th>
                        <th>
                            <a href="">Nachname</a>
                        </th>
                        <th>
                            <a href="">Rolle</a>
                        </th>
                        <th>
                            <a href="">Workflowschritt</a>
                        </th>
                        <th>
                            Aktionen
                        </th>
                    </tr>
                </thead>
                <tbody>
<?php
foreach ($users as $key => $user) {
?>
                    <tr>
                        <td>
                            <label title="Datensatz wählen"><input type="checkbox" /><?php echo $key; ?></label>
                        </td>
                        <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($user['firstname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($user['lastname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($user['workflowstep'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td>
                            <h2>Lokale Interaktionen</h2>
                            <ul>
                                <li><button class="btn btn--soft btn--inactive btn--natural">Archivieren</button></li>
                                <li><button class="btn btn--soft btn--positive btn--natural">Bearbeiten</button></li>
                                <li><button class="btn btn--soft btn--negative btn--natural" form="item<?php echo $key; ?>-workflow-delete">Löschen</button></li>
                                <li><button class="btn btn--soft btn--positive btn--natural" form="item<?php echo $key; ?>-workflow-step1">Publizieren</button></li>
                                <li><button class="btn btn--soft btn--negative btn--natural">Depublizieren</button></li>
                                <li><button class="btn btn--soft btn--inactive btn--natural" disabled="disabled">Deaktivieren</button></li>
                                <li><button class="btn btn--soft btn--natural">Kategorien verknüpfen</button></li>
                                <li><button class="btn btn--soft btn--natural">Informationen verknüpfen</button></li>
                                <li><button class="btn btn--soft btn--natural">Downloads verknüpfen</button></li>
                                <li><button class="btn btn--soft btn--natural">Besitzer zuweisen</button></li>
                            </ul>
                        </td>
                    </tr>
<?php
}
?>
                </tbody>
            </table>
        </section>

        <a href="#nav">Modulmenü anzeigen</a>

        <section style="display:none">
            <h6>Versteckte Formulare für Lokale Interaktionen der Datensätze</h6>
<?php
foreach ($users as $key => $user) {
?>

            <form id="item<?php echo $key; ?>-workflow-delete" action="items/<?php echo $key; ?>" method="post">
                <input type="hidden" name="identifier" value="<?php echo $user['uuid']; ?>" />
                <input type="hidden" name="_method" value="delete" />
            </form>

            <form id="item1-workflow-step1" action="items/<?php echo $key; ?>/workflow" method="post">
                <input type="hidden" name="identifier" value="<?php echo $user['uuid']; ?>" />
                <input type="hidden" name="step" value="publicise" />
            </form>

            <form id="item1-workflow-step2" action="items/<?php echo $key; ?>/workflow" method="post">
                <input type="hidden" name="identifier" value="<?php echo $user['uuid']; ?>" />
                <input type="hidden" name="step" value="trololo" />
            </form>

            <form id="item1-workflow-step3" action="items/<?php echo $key; ?>/workflow" method="post">
                <input type="hidden" name="identifier" value="<?php echo $user['uuid']; ?>" />
                <input type="hidden" name="step" value="omgomgomg" />
            </form>

            <form id="item1-assign-category" action="items/<?php echo $key; ?>/category" method="post">
                <input type="hidden" name="identifier" value="<?php echo $user['uuid']; ?>" />
                <input type="hidden" name="step" value="omgomgomg" />
            </form>

            <form id="item1-assign-download" action="items/<?php echo $key; ?>/download" method="post">
                <input type="hidden" name="identifier" value="<?php echo $user['uuid']; ?>" />
                <input type="hidden" name="step" value="omgomgomg" />
            </form>

            <form id="item1-assign-user" action="items/<?php echo $key; ?>/user" method="post">
                <input type="hidden" name="identifier" value="<?php echo $user['uuid']; ?>" />
                <input type="hidden" name="step" value="omgomgomg" />
            </form>
<?php
}
?>
        </section>
    </form>
    </main>

</div>
</div>
</body>
</html>
