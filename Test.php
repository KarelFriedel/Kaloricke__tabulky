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

$idUzivatele = $_SESSION['id_Uzivatele'];

$sql = "SELECT u.Id_Uzivatele, n.Id_Nastaveni, n.Cile, n.Vaha AS Aktualni_Vaha
        FROM uzivatel u
        JOIN nastaveni n ON u.Nastaveni_Uzivatele = n.Id_Nastaveni
        WHERE u.Id_Uzivatele = $idUzivatele";

$result = $conn->query($sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nastaveni_id = $_POST["nastaveni_id"];
    $new_cile = $_POST["new_cile_" . $nastaveni_id];
    $nova_vaha = $_POST["nova_vaha_" . $nastaveni_id];

    $update_query = "UPDATE nastaveni SET Cile = '$new_cile', Vaha = '$nova_vaha' WHERE Id_Nastaveni = $nastaveni_id";
    $conn->query($update_query);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Změna cíle</title>
    <link rel="stylesheet" type="text/css" href="Test.css">
    <style>
    </style>
</head>

<body>
<div class="container">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
        ?>
                <p>ID nastavení: <?php echo $row["Id_Nastaveni"]; ?></p>
                <p>Aktuální cíl: <?php echo $row["Cile"]; ?></p>
                <p>Aktuální váha: <?php echo $row["Aktualni_Vaha"]; ?></p>

                <form method="post" action="" class="zapis-form">
                    <input type="hidden" name="nastaveni_id" value="<?php echo $row["Id_Nastaveni"]; ?>">
                    <label for="new_cile">Nový cíl:</label>
                    <select id="new_cile" name="new_cile_<?php echo $row["Id_Nastaveni"]; ?>" required>
                        <option value="Zhubout">Zhubout</option>
                        <option value="Nabrat svalovou hmotu">Nabrat svalovou hmotu</option>
                        <option value="Zůstat fit">Zůstat fit</option>
                    </select>
                    <label for="nova_vaha_<?php echo $row["Id_Nastaveni"]; ?>">Nová váha:</label>
                    <input type="text" id="nova_vaha_<?php echo $row["Id_Nastaveni"]; ?>" name="nova_vaha_<?php echo $row["Id_Nastaveni"]; ?>" value="<?php echo $row["Aktualni_Vaha"]; ?>" required>

                    <button type="submit" class="zapis-button">Uložit změny v cíli</button>
                </form>
        <?php
            }
        } else {
            echo "Žádná data k zobrazení.";
        }
        ?>
    </div>
</body>

</html>