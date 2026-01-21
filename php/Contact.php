<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <title>Contact - Trésor de Main</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contactez Trésor de Main">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/HeaderFooter.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/projet.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Special+Elite&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon.ico">
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
        
        .contact-container {
            background: rgba(255, 255, 255, 0.95);
            max-width: 600px;
            margin: 30px auto;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(62, 39, 35, 0.1);
            border: 1px solid rgba(141, 85, 36, 0.2);
        }
        
        .contact-container h2 {
            color: #8D5524;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
        }
        
        .contact-container input,
        .contact-container textarea {
            width: 100%;
            margin: 10px 0 20px;
            padding: 15px;
            border: 2px solid #E6E2DD;
            border-radius: 10px;
            font-size: 1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: all 0.3s ease;
        }
        
        .contact-container input:focus,
        .contact-container textarea:focus {
            border-color: #8D5524;
            outline: none;
            box-shadow: 0 0 0 3px rgba(141, 85, 36, 0.1);
        }
        
        .contact-container textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-submit {
            background: #8D5524;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            background: #C58F5E;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(141, 85, 36, 0.2);
        }
        
        .info-contact {
            background: rgba(141, 85, 36, 0.1);
            padding: 25px;
            border-radius: 15px;
            margin-top: 40px;
            border-left: 4px solid #8D5524;
        }
        
        .info-contact h3 {
            color: #8D5524;
            margin-top: 0;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .contact-container {
                margin: 20px;
                padding: 25px;
            }
            
            main {
                padding: 20px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php include '../php/header.php'; ?>

        <main>
            <div class="contact-container">
                <h2>Contactez-Nous</h2>
                <form method="post" action="send.php">
                    <input type="text" name="nom" placeholder="Votre nom" required>
                    <input type="email" name="email" placeholder="Votre email" required>
                    <textarea name="message" placeholder="Votre message" required></textarea>
                    <button type="submit" name="btn-submit" class="btn-submit">Envoyer le message</button>
                </form>
                
                <div class="info-contact">
                    <h3>Autres moyens de contact</h3>
                    <p><strong>Email :</strong> contact@tresordemain.fr</p>
                    <p><strong>📞 Téléphone :</strong> 01 23 45 67 89</p>
                    <p><strong>Adresse :</strong> 123 Rue des Artisans, 75000 Paris</p>
                    <p><strong>⏰ Horaires :</strong> Lundi-Vendredi 9h-18h</p>
                </div>
            </div>
        </main>

        <?php include '../HTML/footer.html'; ?>
    </div>

    <script src="../JavaScript/script.js"></script>
</body>
</html>