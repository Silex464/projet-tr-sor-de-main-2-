<?php
/**
 * ============================================================================
 * FAQ - Foire Aux Questions
 * ============================================================================
 * 
 * Page complète de FAQ pour artisans et acheteurs
 * Améliore la confiance et réduit les demandes au support
 */

session_start();
require_once 'config.php';
require_once 'tresorsdemain.php';

$faqCategories = [
    'general' => [
        'title' => '🏠 Questions Générales',
        'icon' => '🏠',
        'questions' => [
            [
                'q' => 'Qu\'est-ce que Trésor de Main ?',
                'a' => 'Trésor de Main est une plateforme en ligne dédiée à la mise en relation entre artisans créateurs et amateurs d\'art artisanal. Notre mission est de valoriser le savoir-faire artisanal français et de permettre aux créateurs de vendre leurs œuvres uniques directement aux passionnés.'
            ],
            [
                'q' => 'Comment fonctionne la plateforme ?',
                'a' => 'Les artisans peuvent créer un compte gratuit, présenter leurs créations avec photos et descriptions détaillées. Les acheteurs peuvent parcourir les œuvres, les ajouter à leurs favoris et contacter directement les artisans pour passer commande. Nous facilitons la connexion, mais chaque transaction se fait directement entre l\'artisan et l\'acheteur.'
            ],
            [
                'q' => 'L\'inscription est-elle gratuite ?',
                'a' => 'Oui ! L\'inscription sur Trésor de Main est entièrement gratuite, que vous soyez artisan ou acheteur. Nous croyons que l\'art et l\'artisanat doivent être accessibles à tous.'
            ],
            [
                'q' => 'Comment contacter l\'équipe Trésor de Main ?',
                'a' => 'Vous pouvez nous contacter via notre formulaire de contact, par email à contact@tresordemain.fr ou par téléphone au 01 23 45 67 89 du lundi au vendredi de 9h à 18h.'
            ]
        ]
    ],
    'artisan' => [
        'title' => 'Pour les Artisans',
        'icon' => 'A',
        'questions' => [
            [
                'q' => 'Comment créer mon compte artisan ?',
                'a' => 'Cliquez sur "Inscription" et sélectionnez "Artisan" comme type de compte. Renseignez vos informations personnelles et validez. Vous pourrez ensuite compléter votre profil avec votre spécialité, votre description et vos liens vers les réseaux sociaux.'
            ],
            [
                'q' => 'Comment ajouter mes créations ?',
                'a' => 'Depuis votre espace "Mon Compte", cliquez sur "Ajouter une création". Remplissez le formulaire avec le nom de l\'œuvre, la catégorie, le prix, une description détaillée et téléchargez une photo de qualité. Plus vos descriptions sont complètes, plus vous attirerez d\'acheteurs potentiels.'
            ],
            [
                'q' => 'Quels formats de photos sont acceptés ?',
                'a' => 'Nous acceptons les formats JPG, PNG, GIF et WEBP. La taille maximale est de 5 Mo par image. Pour un meilleur rendu, nous recommandons des images de minimum 800x600 pixels avec un bon éclairage naturel.'
            ],
            [
                'q' => 'Comment mettre en avant mes créations ?',
                'a' => 'Rédigez des descriptions détaillées incluant les matériaux, techniques et inspirations. Ajoutez des photos de qualité professionnelle. Complétez votre profil artisan avec votre histoire et vos réseaux sociaux. Les créations avec des profils complets sont plus visibles.'
            ],
            [
                'q' => 'Comment suis-je payé ?',
                'a' => 'Trésor de Main est une plateforme vitrine. Quand un client s\'intéresse à vos créations, il vous contacte directement. Vous convenez ensemble des modalités de paiement et de remise en main propre ou d\'expédition, en dehors de la plateforme.'
            ],
            [
                'q' => 'Puis-je participer aux événements ?',
                'a' => 'Oui ! Notre page Événements répertorie les marchés artisanaux, expositions et ateliers à venir. Vous pouvez y découvrir des opportunités de présenter vos créations en personne et rencontrer des acheteurs passionnés.'
            ]
        ]
    ],
    'buyer' => [
        'title' => '�️ Pour les Visiteurs',
        'icon' => 'V',
        'questions' => [
            [
                'q' => 'Comment trouver des créations ?',
                'a' => 'Utilisez notre page "Tous les produits" pour parcourir toutes les créations. Vous pouvez filtrer par catégorie (bijoux, céramique, peintures...), par prix ou trier par date d\'ajout. Chaque création affiche le nom de l\'artisan que vous pouvez découvrir.'
            ],
            [
                'q' => 'Comment fonctionnent les favoris ?',
                'a' => 'Créez un compte gratuit, puis cliquez sur le coeur sur n\'importe quelle création pour l\'ajouter à vos favoris. Retrouvez toutes vos créations sauvegardées dans votre espace "Mes Favoris" pour les consulter plus tard.'
            ],
            [
                'q' => 'Comment contacter un artisan ?',
                'a' => 'Sur la page de détail d\'une création, cliquez sur "Contacter l\'artisan". Vous pouvez aussi le joindre par email, téléphone ou via ses réseaux sociaux s\'il les a renseignés. Présentez-vous et expliquez votre intérêt pour son travail.'
            ],
            [
                'q' => 'Les créations sont-elles des pièces uniques ?',
                'a' => 'Oui, la grande majorité des créations sur Trésor de Main sont des pièces uniques ou en série très limitée. C\'est ce qui fait la valeur de l\'artisanat : chaque œuvre porte la signature de son créateur et raconte une histoire unique.'
            ],
            [
                'q' => 'Comment acquérir une création ?',
                'a' => 'Trésor de Main est une plateforme de mise en relation. Pour acquérir une création, contactez directement l\'artisan via la page du produit. Vous conviendrez ensemble des modalités de paiement et de remise (en main propre ou expédition).'
            ],
            [
                'q' => 'Puis-je demander une création personnalisée ?',
                'a' => 'Beaucoup d\'artisans acceptent les demandes sur mesure ! Contactez l\'artisan pour discuter de votre projet. Précisez vos souhaits (dimensions, couleurs, matériaux) et il vous indiquera s\'il peut réaliser votre demande et à quel prix.'
            ]
        ]
    ],
    'trust' => [
        'title' => 'Confiance & Sécurité',
        'icon' => 'S',
        'questions' => [
            [
                'q' => 'Comment sont vérifiés les artisans ?',
                'a' => 'Chaque artisan s\'inscrit avec des informations vérifiables. Nous révisons les profils et les créations pour garantir l\'authenticité. Les artisans "vérifiés" ont fourni des preuves supplémentaires de leur activité artisanale.'
            ],
            [
                'q' => 'Mes données personnelles sont-elles protégées ?',
                'a' => 'Absolument. Nous respectons le RGPD et ne partageons jamais vos données avec des tiers. Vos informations de contact ne sont visibles que des utilisateurs connectés et uniquement dans le contexte des échanges sur la plateforme.'
            ],
            [
                'q' => 'Que faire en cas de problème avec un artisan ?',
                'a' => 'Si vous rencontrez un problème lors de vos échanges avec un artisan, contactez notre équipe à contact@tresordemain.fr. Nous pourrons intervenir comme médiateur si nécessaire et prendre les mesures appropriées.'
            ],
            [
                'q' => 'Les créations sont-elles authentiques ?',
                'a' => 'Toutes les créations présentes sur la plateforme sont des œuvres originales réalisées par les artisans inscrits. Nous vérifions régulièrement les annonces et supprimons tout contenu qui ne respecterait pas notre charte d\'authenticité.'
            ]
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - Questions Fréquentes - Trésor de Main</title>
    <meta name="description" content="Toutes les réponses à vos questions sur Trésor de Main : inscription, vente de créations, achat d'art artisanal, paiement, livraison.">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/HeaderFooter.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/projet.css">
    <style>
        :root {
            --primary: #8D5524;
            --primary-light: #C58F5E;
            --secondary: #3E2723;
            --bg-light: #FFF8F0;
            --success: #28a745;
        }
        
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; padding: 40px 20px; background: var(--bg-light); }
        .container { max-width: 900px; margin: 0 auto; }
        
        /* Header */
        .faq-header { text-align: center; margin-bottom: 50px; }
        .faq-header h1 { color: var(--secondary); font-size: 2.5rem; margin-bottom: 15px; }
        .faq-header p { color: #666; font-size: 1.1rem; max-width: 600px; margin: 0 auto; }
        
        /* Search */
        .faq-search { max-width: 500px; margin: 30px auto; position: relative; }
        .faq-search input {
            width: 100%; padding: 18px 25px 18px 55px; border: 2px solid #E6E2DD;
            border-radius: 50px; font-size: 1.1rem; transition: all 0.3s;
            background: white; box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .faq-search input:focus { border-color: var(--primary); outline: none; box-shadow: 0 5px 25px rgba(141,85,36,0.15); }
        .faq-search::before {
            content: '🔍'; position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
            font-size: 1.2rem;
        }
        
        /* Category Navigation */
        .faq-nav { display: flex; justify-content: center; gap: 15px; margin-bottom: 40px; flex-wrap: wrap; }
        .faq-nav-btn {
            padding: 12px 25px; background: white; border: 2px solid #E6E2DD;
            border-radius: 50px; cursor: pointer; font-weight: 600; color: var(--secondary);
            transition: all 0.3s; font-size: 0.95rem; text-decoration: none;
        }
        .faq-nav-btn:hover, .faq-nav-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        
        /* Category Section */
        .faq-category { margin-bottom: 50px; }
        .faq-category-title {
            font-size: 1.6rem; color: var(--primary); margin-bottom: 25px;
            padding-bottom: 15px; border-bottom: 3px solid var(--primary-light);
            display: flex; align-items: center; gap: 10px;
        }
        
        /* Accordion */
        .faq-item {
            background: white; border-radius: 15px; margin-bottom: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .faq-question {
            padding: 22px 25px; cursor: pointer; display: flex;
            justify-content: space-between; align-items: center;
            font-weight: 600; color: var(--secondary); transition: all 0.3s;
        }
        .faq-question:hover { background: rgba(141,85,36,0.05); }
        .faq-question.active { background: var(--primary); color: white; }
        
        .faq-toggle { font-size: 1.5rem; transition: transform 0.3s; }
        .faq-question.active .faq-toggle { transform: rotate(45deg); }
        
        .faq-answer {
            max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out, padding 0.3s;
            background: #fafafa; padding: 0 25px;
        }
        .faq-answer.show { max-height: 500px; padding: 25px; }
        .faq-answer p { color: #555; line-height: 1.8; margin: 0; }
        
        /* Contact CTA */
        .faq-contact {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white; text-align: center; padding: 50px 30px; border-radius: 25px;
            margin-top: 50px;
        }
        .faq-contact h2 { color: white; margin-bottom: 15px; }
        .faq-contact h2:after { display: none; }
        .faq-contact p { margin-bottom: 25px; opacity: 0.9; }
        .faq-contact .btn {
            display: inline-block; background: white; color: var(--primary);
            padding: 15px 40px; border-radius: 50px; text-decoration: none;
            font-weight: 700; transition: all 0.3s;
        }
        .faq-contact .btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        
        /* No results */
        .no-results { text-align: center; padding: 50px; display: none; }
        .no-results.show { display: block; }
        .no-results h3 { color: var(--secondary); margin-bottom: 10px; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .faq-header h1 { font-size: 1.8rem; }
            .faq-nav { gap: 10px; }
            .faq-nav-btn { padding: 10px 18px; font-size: 0.85rem; }
            .faq-question { padding: 18px 20px; font-size: 0.95rem; }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php include 'header.php'; ?>

        <main>
            <div class="container">
                <div class="faq-header">
                    <h1>❓ Foire Aux Questions</h1>
                    <p>Trouvez rapidement les réponses à vos questions sur Trésor de Main. Si vous ne trouvez pas ce que vous cherchez, n'hésitez pas à nous contacter.</p>
                </div>
                
                <!-- Search -->
                <div class="faq-search">
                    <input type="text" id="faqSearch" placeholder="Rechercher une question...">
                </div>
                
                <!-- Category Navigation -->
                <div class="faq-nav">
                    <button class="faq-nav-btn active" data-category="all">📚 Toutes</button>
                    <?php foreach ($faqCategories as $key => $category): ?>
                    <button class="faq-nav-btn" data-category="<?= $key ?>"><?= $category['title'] ?></button>
                    <?php endforeach; ?>
                </div>
                
                <!-- No Results Message -->
                <div class="no-results" id="noResults">
                    <h3>😕 Aucun résultat trouvé</h3>
                    <p>Essayez avec d'autres mots-clés ou <a href="Contact.php">contactez-nous</a> directement.</p>
                </div>
                
                <!-- FAQ Categories -->
                <?php foreach ($faqCategories as $key => $category): ?>
                <div class="faq-category" data-category="<?= $key ?>">
                    <h2 class="faq-category-title">
                        <span><?= $category['icon'] ?></span>
                        <?= $category['title'] ?>
                    </h2>
                    
                    <?php foreach ($category['questions'] as $index => $qa): ?>
                    <div class="faq-item" data-search="<?= strtolower(htmlspecialchars($qa['q'] . ' ' . $qa['a'])) ?>">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span><?= htmlspecialchars($qa['q']) ?></span>
                            <span class="faq-toggle">+</span>
                        </div>
                        <div class="faq-answer">
                            <p><?= htmlspecialchars($qa['a']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Contact CTA -->
                <div class="faq-contact">
                    <h2>Vous n'avez pas trouvé votre réponse ?</h2>
                    <p>Notre équipe est là pour vous aider. N'hésitez pas à nous contacter directement.</p>
                    <a href="Contact.php" class="btn">Nous contacter</a>
                </div>
            </div>
        </main>

        <?php include '../HTML/footer.html'; ?>
    </div>

    <script>
        // Toggle FAQ accordion
        function toggleFaq(element) {
            const answer = element.nextElementSibling;
            const isActive = element.classList.contains('active');
            
            // Close all other FAQs
            document.querySelectorAll('.faq-question.active').forEach(q => {
                if (q !== element) {
                    q.classList.remove('active');
                    q.nextElementSibling.classList.remove('show');
                }
            });
            
            // Toggle current FAQ
            element.classList.toggle('active');
            answer.classList.toggle('show');
        }
        
        // Category filter
        document.querySelectorAll('.faq-nav-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const category = this.dataset.category;
                
                // Update active button
                document.querySelectorAll('.faq-nav-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Show/hide categories
                document.querySelectorAll('.faq-category').forEach(cat => {
                    if (category === 'all' || cat.dataset.category === category) {
                        cat.style.display = 'block';
                    } else {
                        cat.style.display = 'none';
                    }
                });
                
                document.getElementById('noResults').classList.remove('show');
            });
        });
        
        // Search functionality
        document.getElementById('faqSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let resultsFound = false;
            
            // Reset category filter
            document.querySelectorAll('.faq-nav-btn').forEach(b => b.classList.remove('active'));
            document.querySelector('.faq-nav-btn[data-category="all"]').classList.add('active');
            document.querySelectorAll('.faq-category').forEach(cat => cat.style.display = 'block');
            
            document.querySelectorAll('.faq-item').forEach(item => {
                const searchContent = item.dataset.search;
                if (searchTerm === '' || searchContent.includes(searchTerm)) {
                    item.style.display = 'block';
                    resultsFound = true;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            document.getElementById('noResults').classList.toggle('show', !resultsFound && searchTerm !== '');
            
            // Hide empty categories
            document.querySelectorAll('.faq-category').forEach(cat => {
                const visibleItems = cat.querySelectorAll('.faq-item[style="display: block"]').length;
                cat.style.display = visibleItems > 0 || searchTerm === '' ? 'block' : 'none';
            });
        });
    </script>
    
    <script src="../JavaScript/script.js"></script>
</body>
</html>
