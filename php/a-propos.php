<?php
/**
 * ============================================================================
 * À PROPOS - Page de présentation de Trésor de Main
 * ============================================================================
 * 
 * Présente la mission, les valeurs et l'équipe de Trésor de Main
 * Renforce la confiance des utilisateurs
 */

session_start();
require_once 'config.php';
require_once 'tresorsdemain.php';

// Récupérer quelques statistiques
try {
    $pdo = getConnection();
    
    $statsArtisans = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE type_compte = 'artisan'")->fetchColumn();
    $statsCreations = $pdo->query("SELECT COUNT(*) FROM article WHERE disponibilite = 1")->fetchColumn();
    $statsCategories = $pdo->query("SELECT COUNT(DISTINCT categorie) FROM article")->fetchColumn();
    
} catch (PDOException $e) {
    $statsArtisans = "50+";
    $statsCreations = "200+";
    $statsCategories = "10";
}
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À Propos - Notre Histoire - Trésor de Main</title>
    <meta name="description" content="Découvrez l'histoire de Trésor de Main, la plateforme qui célèbre l'artisanat français. Notre mission : connecter artisans passionnés et amateurs d'art unique.">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/HeaderFooter.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/projet.css">
    <style>
        :root {
            --primary: #8D5524;
            --primary-light: #C58F5E;
            --secondary: #3E2723;
            --bg-light: #FFF8F0;
            --accent: #D4A574;
        }
        
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; }
        
        /* Hero Section */
        .about-hero {
            background: linear-gradient(rgba(62,39,35,0.85), rgba(141,85,36,0.9)),
                        url('<?= IMAGES_PATH ?>/Tour-de-potier.jpg') center/cover;
            color: white; text-align: center; padding: 100px 20px;
        }
        .about-hero h1 { color: white; font-size: 3rem; margin-bottom: 20px; }
        .about-hero h1:after { background: white; width: 100px; margin-top: 15px; }
        .about-hero p { font-size: 1.3rem; max-width: 700px; margin: 0 auto; opacity: 0.95; line-height: 1.8; }
        
        /* Stats Section */
        .stats-section { background: white; padding: 60px 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px; max-width: 900px; margin: 0 auto; }
        .stat-item { text-align: center; }
        .stat-number { font-size: 3.5rem; font-weight: bold; color: var(--primary); line-height: 1; margin-bottom: 10px; }
        .stat-label { color: #666; font-size: 1.1rem; }
        
        /* Mission Section */
        .mission-section { padding: 80px 20px; background: var(--bg-light); }
        .mission-content { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
        .mission-text h2 { color: var(--primary); margin-bottom: 25px; text-align: left; }
        .mission-text h2:after { left: 0; transform: none; }
        .mission-text p { color: var(--secondary); line-height: 1.9; font-size: 1.1rem; margin-bottom: 20px; }
        .mission-image { border-radius: 25px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.15); }
        .mission-image img { width: 100%; height: 400px; object-fit: cover; }
        
        /* Values Section */
        .values-section { padding: 80px 20px; background: white; }
        .values-section > h2 { text-align: center; color: var(--primary); margin-bottom: 50px; }
        .values-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; max-width: 1100px; margin: 0 auto; }
        .value-card {
            background: var(--bg-light); padding: 40px 30px; border-radius: 20px;
            text-align: center; transition: all 0.3s;
            border: 2px solid transparent;
        }
        .value-card:hover { transform: translateY(-10px); border-color: var(--primary-light); box-shadow: 0 20px 40px rgba(141,85,36,0.15); }
        .value-icon { font-size: 3.5rem; margin-bottom: 20px; }
        .value-card h3 { color: var(--primary); margin-bottom: 15px; font-size: 1.4rem; }
        .value-card p { color: #666; line-height: 1.7; }
        
        /* How it works */
        .how-section { padding: 80px 20px; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: white; }
        .how-section h2 { color: white; text-align: center; margin-bottom: 50px; }
        .how-section h2:after { background: white; }
        .how-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; max-width: 1100px; margin: 0 auto; }
        .how-card { text-align: center; position: relative; }
        .how-number {
            width: 60px; height: 60px; background: white; color: var(--primary);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; font-weight: bold; margin: 0 auto 20px;
        }
        .how-card h4 { color: white; font-size: 1.3rem; margin-bottom: 15px; }
        .how-card p { opacity: 0.9; line-height: 1.7; }
        
        /* Team Section */
        .team-section { padding: 80px 20px; background: var(--bg-light); }
        .team-section h2 { text-align: center; color: var(--primary); margin-bottom: 20px; }
        .team-intro { text-align: center; color: #666; max-width: 600px; margin: 0 auto 50px; font-size: 1.1rem; }
        .team-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 30px; max-width: 900px; margin: 0 auto; }
        .team-member { background: white; border-radius: 20px; overflow: hidden; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .team-member img { width: 100%; height: 200px; object-fit: cover; }
        .team-member-info { padding: 25px; }
        .team-member h4 { color: var(--secondary); margin: 0 0 5px; }
        .team-member p { color: var(--primary); font-weight: 500; margin: 0 0 10px; }
        .team-member small { color: #888; }
        
        /* CTA Section */
        .cta-section { padding: 80px 20px; text-align: center; background: white; }
        .cta-section h2 { color: var(--primary); margin-bottom: 20px; }
        .cta-section p { color: #666; max-width: 600px; margin: 0 auto 30px; font-size: 1.1rem; }
        .cta-buttons { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
        .btn-cta {
            padding: 18px 40px; border-radius: 50px; text-decoration: none;
            font-weight: 700; font-size: 1.1rem; transition: all 0.3s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-3px); box-shadow: 0 10px 30px rgba(141,85,36,0.3); }
        .btn-secondary { background: transparent; color: var(--primary); border: 2px solid var(--primary); }
        .btn-secondary:hover { background: var(--primary); color: white; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .about-hero { padding: 60px 20px; }
            .about-hero h1 { font-size: 2rem; }
            .about-hero p { font-size: 1.1rem; }
            .stat-number { font-size: 2.5rem; }
            .mission-content { grid-template-columns: 1fr; gap: 40px; }
            .mission-text h2 { text-align: center; }
            .mission-text h2:after { left: 50%; transform: translateX(-50%); }
            .values-section, .how-section, .team-section { padding: 50px 20px; }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php include 'header.php'; ?>

        <main>
            <!-- Hero -->
            <section class="about-hero">
                <h1>✨ Notre Histoire</h1>
                <p>Trésor de Main est né d'une passion commune pour l'artisanat et d'une conviction : chaque création mérite d'être découverte, chaque artisan mérite d'être reconnu.</p>
            </section>
            
            <!-- Stats -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?= $statsArtisans ?></div>
                        <div class="stat-label">Artisans inscrits</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $statsCreations ?></div>
                        <div class="stat-label">Créations uniques</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $statsCategories ?></div>
                        <div class="stat-label">Catégories d'art</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">100%</div>
                        <div class="stat-label">Fait main</div>
                    </div>
                </div>
            </section>
            
            <!-- Mission -->
            <section class="mission-section">
                <div class="mission-content">
                    <div class="mission-text">
                        <h2>🎯 Notre Mission</h2>
                        <p>Dans un monde où la production de masse domine, nous croyons fermement en la valeur du travail artisanal. Chaque pièce créée à la main porte en elle l'âme de son créateur, des heures de patience et un savoir-faire transmis de génération en génération.</p>
                        <p>Trésor de Main a pour mission de <strong>connecter les artisans passionnés avec des amateurs d'art authentique</strong>. Nous offrons une vitrine numérique aux créateurs pour qu'ils puissent partager leur travail avec le monde, tout en préservant la relation humaine qui fait la beauté de l'artisanat.</p>
                        <p>Notre plateforme célèbre la diversité des métiers d'art : céramistes, bijoutiers, ébénistes, peintres, sculpteurs... Chacun trouve ici un espace pour raconter son histoire et présenter ses créations.</p>
                    </div>
                    <div class="mission-image">
                        <img src="<?= IMAGES_PATH ?>/Tour-de-potier.jpg" alt="Artisan au travail">
                    </div>
                </div>
            </section>
            
            <!-- Values -->
            <section class="values-section">
                <h2>💎 Nos Valeurs</h2>
                <div class="values-grid">
                    <div class="value-card">
                        <div class="value-icon">🤲</div>
                        <h3>Authenticité</h3>
                        <p>Nous ne présentons que des créations originales, fabriquées à la main par des artisans vérifiés. Chaque pièce est unique et raconte une histoire.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon">🌿</div>
                        <h3>Durabilité</h3>
                        <p>L'artisanat est intrinsèquement durable : des objets de qualité, faits pour durer, qui s'opposent à la culture du jetable.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon">🤝</div>
                        <h3>Proximité</h3>
                        <p>Nous favorisons le lien direct entre artisans et acheteurs. Pas d'intermédiaire superflu, juste des échanges humains et sincères.</p>
                    </div>
                    <div class="value-card">
                        <div class="value-icon">🇫🇷</div>
                        <h3>Savoir-faire local</h3>
                        <p>Nous mettons en lumière les talents locaux et les techniques traditionnelles qui font la richesse de notre patrimoine artisanal.</p>
                    </div>
                </div>
            </section>
            
            <!-- How it works -->
            <section class="how-section">
                <h2>🚀 Comment ça marche ?</h2>
                <div class="how-grid">
                    <div class="how-card">
                        <div class="how-number">1</div>
                        <h4>Découvrez</h4>
                        <p>Parcourez notre catalogue de créations uniques, filtrez par catégorie et trouvez l'œuvre qui vous parle.</p>
                    </div>
                    <div class="how-card">
                        <div class="how-number">2</div>
                        <h4>Connectez</h4>
                        <p>Créez un compte gratuit, ajoutez vos coups de cœur en favoris et contactez directement les artisans.</p>
                    </div>
                    <div class="how-card">
                        <div class="how-number">3</div>
                        <h4>Échangez</h4>
                        <p>Discutez avec l'artisan de votre projet, posez vos questions et convenez des modalités de commande.</p>
                    </div>
                    <div class="how-card">
                        <div class="how-number">4</div>
                        <h4>Appréciez</h4>
                        <p>Recevez votre création unique et savourez la satisfaction de posséder une œuvre authentique, faite avec passion.</p>
                    </div>
                </div>
            </section>
            
            <!-- Team -->
            <section class="team-section">
                <h2>L'Équipe</h2>
                <p class="team-intro">Derrière Trésor de Main, une équipe passionnée qui croit en la force de l'artisanat pour transformer notre quotidien.</p>
                <div class="team-grid">
                    <div class="team-member">
                        <img src="<?= IMAGES_PATH ?>/Théobald_Cuivreforge.jpg" alt="Membre équipe">
                        <div class="team-member-info">
                            <h4>Théobald Cuivreforge</h4>
                            <p>Fondateur</p>
                            <small>Passionné d'artisanat depuis toujours</small>
                        </div>
                    </div>
                    <div class="team-member">
                        <img src="<?= IMAGES_PATH ?>/Adele_Finepinceau.jpg" alt="Membre équipe">
                        <div class="team-member-info">
                            <h4>Adèle Finepinceau</h4>
                            <p>Direction Artistique</p>
                            <small>Artiste et curatrice</small>
                        </div>
                    </div>
                    <div class="team-member">
                        <img src="<?= IMAGES_PATH ?>/default-avatar.svg" alt="Membre équipe">
                        <div class="team-member-info">
                            <h4>Lucas Martin</h4>
                            <p>Développeur</p>
                            <small>Créateur de la plateforme</small>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- CTA -->
            <section class="cta-section">
                <h2>Prêt à rejoindre l'aventure ?</h2>
                <p>Que vous soyez artisan souhaitant partager votre talent ou amateur d'art à la recherche de pièces uniques, Trésor de Main est fait pour vous.</p>
                <div class="cta-buttons">
                    <a href="inscription.php" class="btn-cta btn-primary">Créer mon compte</a>
                    <a href="All_Products.php" class="btn-cta btn-secondary">🔍 Explorer les créations</a>
                </div>
            </section>
        </main>

        <?php include '../HTML/footer.html'; ?>
    </div>

    <script src="../JavaScript/script.js"></script>
</body>
</html>
