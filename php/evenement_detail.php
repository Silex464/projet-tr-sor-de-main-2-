<?php session_start();
require_once 'config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: evenements.php");
    exit();
}

$id_evenement = $_GET['id'];

$servername = "localhost";
$username = "root";
$password = "";// "root" pour les ordi MAC
$dbname = "tresordemain";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Erreur : " . $conn->connect_error); }

$sql = "SELECT * FROM Evenement WHERE id_evenement = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_evenement); 
$stmt->execute();
$result = $stmt->get_result();


if ($result->num_rows > 0) {
    $event = $result->fetch_assoc(); 
} else {
    die("Événement introuvable.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($event['lieu']); ?> - Trésor de Main</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/projet.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/HeaderFooter.css">
    <style>
        .page-detail {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .detail-img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .detail-info {
            background: #f9f9f9;
            padding: 15px;
            border-left: 5px solid #d35400;
            margin-bottom: 20px;
        }
        .btn-retour {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #333;
            border: 1px solid #333;
            padding: 10px 20px;
            border-radius: 4px;
        }
        .btn-retour:hover { background: #333; color: white; }
    </style>
</head>
<body>

	<!-- Header -->
	<?php include '../php/header.php'; ?>

    <main class="page-detail">
        
        <?php if (!empty($event['image'])): ?>
            <img src="<?= IMAGES_PATH ?>/<?php echo htmlspecialchars($event['image']); ?>" class="detail-img" alt="Event">
        <?php endif; ?>

        <h1><?php echo htmlspecialchars($event['titre']); ?></h1>

        <div class="detail-info">
            <p><strong>Dates :</strong> 
                Du <?php echo date("d/m/Y", strtotime($event['date_debut'])); ?> 
                au <?php echo date("d/m/Y", strtotime($event['date_fin'])); ?>
            </p>
            <p><strong>Lieu :</strong>
            <?php
                $URL  = htmlspecialchars($event['url_lieu'], ENT_QUOTES, 'UTF-8');
                $lieu = htmlspecialchars($event['lieu'], ENT_QUOTES, 'UTF-8');
                echo '<a href="' . $URL . '" target="_blank" rel="noopener noreferrer">' . $lieu . '</a>';
            ?>↗️
            </p>
        </div>

        <div class="description-complete">
            <h3>À propos de cet événement</h3>
            <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
        </div>

        <a href="evenements.php" class="btn-retour">← Retour à la liste</a>

    </main>

    <?php include '../HTML/footer.html'; ?>

</body>
</html>
