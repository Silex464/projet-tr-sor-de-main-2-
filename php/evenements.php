<?php
session_start();
require_once 'tresorsdemain.php';

try {
    $pdo = getConnection();
    
    // Récupérer les événements à venir
    $stmt = $pdo->prepare("SELECT * FROM evenement WHERE date_fin >= CURDATE() ORDER BY date_debut ASC");
    $stmt->execute();
    $evenements = $stmt->fetchAll();
    
    $event_count = count($evenements);
    
} 

catch (PDOException $e) {
    error_log("Evenements error: " . $e->getMessage());
    $evenements = [];
    $event_count = 0;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - Trésor de Main</title>
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/projet.css">
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/evenements.css">
    <style>
        .page-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
            padding: 40px 20px;
        }

        .events-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .events-header h2 {
            color: #8D5524;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .events-counter {
            background: rgba(141, 85, 36, 0.1);
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            margin-top: 20px;
            font-weight: 500;
            color: #8D5524;
        }

        .container-events {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .carte-event {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .carte-event:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .carte-img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .carte-contenu {
            padding: 25px;
        }

        .carte-contenu h3 {
            color: #3E2723;
            margin: 0 0 10px 0;
            font-size: 1.3rem;
        }

        .carte-date {
            color: #8D5524;
            font-weight: bold;
            font-size: 0.9rem;
            margin: 10px 0;
        }

        .carte-lieu {
            color: #666;
            font-size: 0.95rem;
            margin: 5px 0 15px;
        }

        .bouton-detail {
            background: #8D5524;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-block;
            margin-top: 15px;
        }

        .no-events {
            text-align: center;
            padding: 60px 20px;
        }
        
        @media (max-width: 768px) { .container-events { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; padding: 10px; } }
    </style>
</head>
<body>
    <div class="page-container">
        
        <!-- Header -->
		<?php include '../php/header.php'; ?>

        <main>
            <div class="events-header">
                <h2>Nos Événements</h2>
                <p>Découvrez les prochains marchés d'artisans, expositions et ateliers.</p>
                <div class="events-counter"><?php echo $event_count; ?> événement<?php echo $event_count > 1 ? 's' : ''; ?> à venir</div>
            </div>
            
            <?php if ($event_count > 0): ?>
            <div class="container-events">
                <?php foreach ($evenements as $event): 
                    $dateDebut = date("d/m/Y", strtotime($event['date_debut']));
                    $dateFin = date("d/m/Y", strtotime($event['date_fin']));
                    $image = !empty($event['image']) ? $event['image'] : 'https://via.placeholder.com/400x300/8D5524/FFFFFF?text=Evénement';
                ?>
                <div class="carte-event">
                    <img src="/Projet-Tr-sor-de-Main/assets/images/<?php echo htmlspecialchars($image); ?>" class="carte-img"  alt="<?php echo htmlspecialchars($event['titre']); ?>">
                    <div class="carte-contenu">
                        <h3><?php echo htmlspecialchars($event['titre']); ?></h3>
                        <div class="carte-lieu">                        
						<?php
                            $URL  = htmlspecialchars($event['url_lieu'], ENT_QUOTES, 'UTF-8');
                            $lieu = htmlspecialchars($event['lieu'], ENT_QUOTES, 'UTF-8');
                            echo '<a href="' . $URL . '" target="_blank" rel="noopener noreferrer">' . $lieu . '</a>';
                        ?>
						</div>
                        <div class="carte-date">Du <?php echo $dateDebut; ?> au <?php echo $dateFin; ?></div>
                        <p><?php echo htmlspecialchars(substr($event['description'], 0, 150) . '...'); ?></p>
                        <?php 
                        echo '<a href="evenement_detail.php?id=' . (int)$event['id_evenement'] . '" class="bouton-detail">Voir les détails →</a>';
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-events">
                <h3>Aucun événement prévu pour le moment</h3>
                <p>Revenez bientôt pour découvrir nos prochains événements.</p>
            </div>
            <?php endif; ?>
        </main>

        <?php include '../HTML/footer.html'; ?>
    </div>
    <script src="../JavaScript/script.js"></script>
</body>
</html>
