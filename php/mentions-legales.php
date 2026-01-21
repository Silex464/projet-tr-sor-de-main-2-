<?php
/**
 * ============================================================================
 * MENTIONS LÉGALES - Page obligatoire légalement
 * ============================================================================
 */

session_start();
require_once 'config.php';
require_once 'tresorsdemain.php';
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentions Légales - Trésor de Main</title>
    <meta name="description" content="Mentions légales du site Trésor de Main - Informations sur l'éditeur, l'hébergeur et les données personnelles.">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/HeaderFooter.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/projet.css">
    <style>
        :root {
            --primary: #8D5524;
            --primary-light: #C58F5E;
            --secondary: #3E2723;
            --bg-light: #FFF8F0;
        }
        
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; padding: 50px 20px; background: var(--bg-light); }
        .container { max-width: 800px; margin: 0 auto; }
        
        .legal-header { text-align: center; margin-bottom: 50px; }
        .legal-header h1 { color: var(--secondary); font-size: 2.5rem; }
        
        .legal-section {
            background: white; padding: 35px; border-radius: 15px;
            margin-bottom: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .legal-section h2 { color: var(--primary); margin-top: 0; font-size: 1.4rem; border-bottom: 2px solid var(--primary-light); padding-bottom: 10px; }
        .legal-section h2:after { display: none; }
        .legal-section p, .legal-section li { color: #555; line-height: 1.8; }
        .legal-section strong { color: var(--secondary); }
        .legal-section ul { padding-left: 20px; }
        .legal-section li { margin-bottom: 10px; }
        
        .last-update { text-align: center; color: #888; font-size: 0.9rem; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="page-container">
        <?php include 'header.php'; ?>

        <main>
            <div class="container">
                <div class="legal-header">
                    <h1>📜 Mentions Légales</h1>
                </div>
                
                <div class="legal-section">
                    <h2>1. Éditeur du site</h2>
                    <p><strong>Trésor de Main</strong></p>
                    <p>
                        Adresse : 123 Rue des Artisans, 75000 Paris<br>
                        Email : contact@tresordemain.fr<br>
                        Téléphone : 01 23 45 67 89
                    </p>
                    <p>Directeur de la publication : Équipe Trésor de Main</p>
                </div>
                
                <div class="legal-section">
                    <h2>2. Hébergement</h2>
                    <p>
                        Le site Trésor de Main est hébergé par :<br>
                        <strong>[Nom de l'hébergeur]</strong><br>
                        Adresse : [Adresse de l'hébergeur]<br>
                        Téléphone : [Numéro de téléphone]
                    </p>
                </div>
                
                <div class="legal-section">
                    <h2>3. Propriété intellectuelle</h2>
                    <p>L'ensemble du contenu présent sur le site Trésor de Main (textes, images, vidéos, logos, icônes, etc.) est protégé par les lois relatives à la propriété intellectuelle.</p>
                    <p>Les images des créations artisanales restent la propriété de leurs auteurs respectifs (les artisans). Toute reproduction, représentation, modification ou exploitation non autorisée de ces contenus est interdite.</p>
                    <p>Les marques, logos et noms commerciaux présents sur le site sont la propriété de Trésor de Main ou de leurs détenteurs respectifs.</p>
                </div>
                
                <div class="legal-section">
                    <h2>4. Protection des données personnelles (RGPD)</h2>
                    <p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, vous disposez des droits suivants sur vos données personnelles :</p>
                    <ul>
                        <li><strong>Droit d'accès :</strong> Vous pouvez demander à accéder aux données vous concernant.</li>
                        <li><strong>Droit de rectification :</strong> Vous pouvez demander la correction de données inexactes.</li>
                        <li><strong>Droit à l'effacement :</strong> Vous pouvez demander la suppression de vos données dans certains cas.</li>
                        <li><strong>Droit à la portabilité :</strong> Vous pouvez récupérer vos données dans un format structuré.</li>
                        <li><strong>Droit d'opposition :</strong> Vous pouvez vous opposer au traitement de vos données.</li>
                    </ul>
                    <p>Pour exercer ces droits, contactez-nous à : <strong>contact@tresordemain.fr</strong></p>
                    <p>Les données collectées (nom, email, etc.) sont utilisées uniquement pour le fonctionnement de la plateforme et ne sont jamais vendues à des tiers.</p>
                </div>
                
                <div class="legal-section">
                    <h2>5. Cookies</h2>
                    <p>Le site Trésor de Main utilise des cookies techniques nécessaires au bon fonctionnement de la plateforme, notamment pour :</p>
                    <ul>
                        <li>Maintenir votre session de connexion</li>
                        <li>Mémoriser vos préférences de navigation</li>
                        <li>Assurer la sécurité de votre compte</li>
                    </ul>
                    <p>Aucun cookie publicitaire ou de tracking n'est utilisé sur ce site.</p>
                </div>
                
                <div class="legal-section">
                    <h2>6. Responsabilité</h2>
                    <p>Trésor de Main s'efforce d'assurer au mieux l'exactitude et la mise à jour des informations diffusées sur ce site. Toutefois, nous ne pouvons garantir l'exactitude, la précision ou l'exhaustivité des informations mises à disposition.</p>
                    <p>Les transactions entre artisans et acheteurs se font directement entre eux. Trésor de Main agit en tant qu'intermédiaire de mise en relation et ne peut être tenu responsable des litiges éventuels liés aux achats.</p>
                </div>
                
                <div class="legal-section">
                    <h2>7. Liens hypertextes</h2>
                    <p>Le site peut contenir des liens vers d'autres sites internet. Trésor de Main ne peut être tenu responsable du contenu de ces sites externes.</p>
                </div>
                
                <div class="legal-section">
                    <h2>8. Droit applicable</h2>
                    <p>Les présentes mentions légales sont régies par le droit français. En cas de litige, les tribunaux français seront seuls compétents.</p>
                </div>
                
                <p class="last-update">Dernière mise à jour : Janvier 2026</p>
            </div>
        </main>

        <?php include '../HTML/footer.html'; ?>
    </div>

    <script src="../JavaScript/script.js"></script>
</body>
</html>
