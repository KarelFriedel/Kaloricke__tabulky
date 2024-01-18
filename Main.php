<?php
session_start();
if (!isset($_SESSION['id_Uzivatele'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kaloricke_tabulky";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Chyba připojení k databázi: " . $conn->connect_error);
}

function nactiPotraviny()
{
    global $conn;

    $result = $conn->query("SELECT * FROM potraviny");

    $potraviny = array();
    while ($row = $result->fetch_assoc()) {
        $potraviny[] = $row;
    }

    return $potraviny;
}

function pridejPotravinu($datum, $cast, $nazevPotraviny, $gramaz)
{
    global $conn;

    $idUzivatele = $_SESSION['id_Uzivatele'];

    $stmtCast = $conn->prepare("SELECT ID_Casti FROM cast_dne WHERE Nazev = ?");
    $stmtCast->bind_param("s", $cast);
    $stmtCast->execute();
    $resultCast = $stmtCast->get_result();

    if ($rowCast = $resultCast->fetch_assoc()) {
        $idCasti = $rowCast['ID_Casti'];
    } else {
        echo "Error: část dne not found.";
        return;
    }

    $stmtCast->close();

    $sql = "INSERT INTO jidlo (datum, potravina, gramaz, cast_dne, Id_Uzivatele)
            VALUES (?, (SELECT Id_Potraviny FROM potraviny WHERE Nazev_Potraviny = ? LIMIT 1), ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Chyba při přípravě dotazu: " . $conn->error);
    }

    $stmt->bind_param("ssiii", $datum, $nazevPotraviny, $gramaz, $idCasti, $idUzivatele);

    if ($stmt->execute()) {
    } else {
        echo "Chyba: " . $stmt->error;
    }

    $stmt->close();
}

$potraviny = nactiPotraviny();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_pridat_jidlo"])) {
    $datum = isset($_POST["datum"]) ? $_POST["datum"] : date('Y-m-d');
    $cast = isset($_POST["cast"]) ? $_POST["cast"] : "";
    $nazevPotraviny = isset($_POST["nazevPotraviny"]) ? $_POST["nazevPotraviny"] : "";
    $gramaz = isset($_POST["gramaz"]) ? $_POST["gramaz"] : "";

    pridejPotravinu($datum, $cast, $nazevPotraviny, $gramaz);
}

function nactiNapoje()
{
    global $conn;

    $result = $conn->query("SELECT * FROM piti");

    $napoje = array();
    while ($row = $result->fetch_assoc()) {
        $napoje[] = $row;
    }

    return $napoje;
}

function pridejNapoj($datum, $cast, $nazevPiti, $gramaz)
{
    global $conn;



    $idUzivatele = $_SESSION['id_Uzivatele'];

    $sql = "INSERT INTO napoje (datum, piti, gramaz, cast_dne, Id_Uzivatele)
            VALUES (?, (SELECT id_Piti FROM piti WHERE Nazev_Piti = ? LIMIT 1), ?, (SELECT ID_Casti FROM cast_dne WHERE Nazev = ? LIMIT 1), ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Chyba při přípravě dotazu: " . $conn->error);
    }

    $stmt->bind_param("ssisi", $datum, $nazevPiti, $gramaz, $cast, $idUzivatele);

    if ($stmt->execute()) {
    } else {
        echo "Chyba: " . $stmt->error;
    }

    $stmt->close();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit_pridat_napoj"])) {

    $datum = isset($_POST["datum"]) ? $_POST["datum"] : date("Y-m-d");
    $cast = $_POST["cast"];
    $nazevPiti = $_POST["nazevPiti"];
    $gramaz = $_POST["gramaz"];

    pridejNapoj($datum, $cast, $nazevPiti, $gramaz);
}

$napoje = nactiNapoje();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaše jídlo</title>
    <link rel="stylesheet" href="Main.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
    <link rel="stylesheet" href="Zapis.css">
  

    <div class="pie animate Celkem" style="--p:10;--c:lightgreen">Celkem</div>
    <div class="pie animate Bilkoviny" style="--p:30;--c:lightgreen">Bílkoviny </div>
    <div class="pie animate Sacharidy" style="--p:40;--c:lightgreen">Sacharidy </div>
    <div class="pie animate Vlaknina" style="--p:100;--c:lightgreen">Vláknina</div>
    <div class="pie animate Tuky" style="--p:70;--c:lightgreen">Tuky</div>
    <div class="pie animate " style="--p:50;--c:lightgreen">Pitný R</div>

    <a href="Test.php"><button class="my-button" onclick="ZmenaCile()">Změnit cíl</button></a>
    <button class="my-button" onclick="PridatJidlo()">Přidat jídlo</button>
    <button class="my-button" onclick="PridatNapoj()">Přidat Nápoj</button>
    <button class="my-button" onclick="OdhlasitUzivatele()">Odhlásit se</button>

    <div id="popup-zmena-cile" class="popup popup-zmena-cile">
         
        <button onclick="ZavriZmenaCile()">Zavřít</button>
    </div>

    <div id="popup-pridat-jidlo" class="popup popup-pridat-jidlo">
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="zapis-form">
            <h2>Přidat jídlo</h2>

            <label for="datum" class="zapis-label">Datum:</label>
            <input type="date" name="datum" value="<?php echo date('Y-m-d'); ?>" class="zapis-input" required>

            <label for="cast" class="zapis-label">Část dne:</label>
            <select name="cast" class="zapis-select" required>
                <?php
                $result = $conn->query("SELECT * FROM cast_dne");

                while ($row = $result->fetch_assoc()) {
                    $selected = ($row["Nazev"] === $cast) ? "selected" : "";
                    echo "<option value='{$row["Nazev"]}' $selected>{$row["Nazev"]}</option>";
                }
                ?>
            </select>

            <label for="nazevPotraviny" class="zapis-label">Název potraviny:</label>
            <select name="nazevPotraviny" class="zapis-select" required>
                <?php
                foreach ($potraviny as $potravina) {
                    echo "<option value='{$potravina["Nazev_Potraviny"]}'>{$potravina["Nazev_Potraviny"]}</option>";
                }
                ?>
            </select>

            <label for="gramaz" class="zapis-label">Gramáž:</label>
            <input type="text" name="gramaz" class="zapis-input" required>

            <button type="submit" name="submit_pridat_jidlo" class="zapis-button">Přidat jídlo</button>
        </form>
        <button onclick="ZavriPridatJidlo()">Zavřít</button>
    </div>

    <div id="popup-pridat-napoj" class="popup popup-pridat-jidlo">

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="zapis-form">
    <h2 class="zapis-heading">Přidat pití</h2>

    <label for="datum" class="zapis-label">Datum:</label>
    <input type="date" name="datum" value="<?php echo date('Y-m-d'); ?>"class="zapis-input" required>

    <label for="cast" class="zapis-label">Část dne:</label>
    <select name="cast" class="zapis-select" required>
        <?php
        $result = $conn->query("SELECT * FROM cast_dne");

        while ($row = $result->fetch_assoc()) {
            echo "<option value='{$row["Nazev"]}' class='zapis-option'>{$row["Nazev"]}</option>";
        }
        ?>
    </select>

    <label for="nazevPiti" class="zapis-label">Název pití:</label>
    <select name="nazevPiti" class="zapis-select" required>
        <?php
        foreach ($napoje as $napoj) {
            echo "<option value='{$napoj["Nazev_Piti"]}' class='zapis-option'>{$napoj["Nazev_Piti"]}</option>";
        }
        ?>
    </select>

    <label for="gramaz" class="zapis-label">Množství (ml):</label>
    <input type="text" name="gramaz" class="zapis-input" required>

    <button type="submit" name="submit_pridat_napoj" class="zapis-button">Přidat pití</button>
</form>
        <button onclick="ZavriPridatNapoj()">Zavřít</button>
    </div>

    <script>
    function ZmenaCile() {
        var popup = document.getElementById('popup-zmena-cile');
        popup.style.display = 'block';
    }

    function ZavriZmenaCile() {
        var popup = document.getElementById('popup-zmena-cile');
        popup.style.display = 'none';
    }

    function PridatJidlo() {
        var popup = document.getElementById('popup-pridat-jidlo');
        popup.style.display = 'block';
    }

    function ZavriPridatJidlo() {
        var popup = document.getElementById('popup-pridat-jidlo');
        popup.style.display = 'none';
    }

    function PridatNapoj() {
        var popup = document.getElementById('popup-pridat-napoj');
        popup.style.display = 'block';
    }

    function ZavriPridatNapoj() {
        var popup = document.getElementById('popup-pridat-napoj');
        popup.style.display = 'none';
    }

    function OdhlasitUzivatele() {
        window.location.href = 'Logout.php';
    }
</script>
</body>

</html>