<?php
/**
 * Page d'Accueil - Trésor de Main
 * Affiche les best-sellers et les artisans dynamiquement depuis la BDD
 */
require_once 'tresorsdemain.php';

// Récupérer les produits best-sellers et les artisans depuis la BDD
try {
    $pdo = getConnection();
    
    // Récupérer les 8 produits best-sellers (les plus vus ou mis en avant)
    $stmtProducts = $pdo->query("
        SELECT id_article, nom_article, prix, image, description 
        FROM article 
        WHERE disponibilite = 1 
        ORDER BY mis_en_avant DESC, vue DESC, date_ajout DESC 
        LIMIT 8
    ");
    $bestSellers = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les artisans actifs
    $stmtArtisans = $pdo->query("
        SELECT id, prenom, nom, description, specialite, photo_profil 
        FROM utilisateurs 
        WHERE type_compte = 'artisan' AND statut = 'actif' 
        ORDER BY badge_verifie DESC, note_moyenne DESC 
        LIMIT 5
    ");
    $artisans = $stmtArtisans->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $bestSellers = [];
    $artisans = [];
}
?>
<!DOCTYPE html>
<html lang="fr-FR">
	<head>
		<title>TrésordeMain</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="Le site web de tous les artisans">
		<link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/HeaderFooter.css">
		<link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/projet.css">
		<link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/gallerieRotative.css">
		<link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/horizontalScroll.css">
		<link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/image.css">
		<a name="haut"></a>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Special+Elite&display=swap" rel="stylesheet">
		<style>
			/* Style pour les liens dans les galeries */
			.Gal_card-link, .artisan-card-link {
				text-decoration: none;
				color: inherit;
				display: block;
				width: 100%;
				height: 100%;
			}
			.Gal_card-link:hover .Gal_card,
			.artisan-card-link:hover .scroll-objet {
				transform: scale(1.02);
				box-shadow: 0 8px 25px rgba(141, 85, 36, 0.4);
			}
			.Gal_card, .scroll-objet.card {
				transition: transform 0.3s ease, box-shadow 0.3s ease;
				cursor: pointer;
			}
		</style>
	</head>
	<body>
		<section>
			<!-- Header -->
			<?php include '../php/header.php'; ?>

			<h2>Bienvenue sur Trésor de Main !</h2>
			<div class="conteneur-presentation">
	    		<div class="block-left">
					<p>Bienvenue sur Trésor de main, la plateforme en ligne dédiée aux artisans pour vendre leurs créations uniques. Parcourez notre collection variée comprenant des sculptures, des peintures, des bijoux, et des portraits. Trouvez l'œuvre parfaite qui vous inspire ou vendez vos créations.</p>
					<a href="All_Products.php" class="bouton">Explorer</a>
					<a href="inscription.php" class="bouton">Créer un compte</a>
				</div>
            	<img src="/Projet-Tr-sor-de-Main/assets/images/Tour-de-potier.jpg" alt="Poterie" width="30%">
			</div>
		</section>
		<article>

        <section class="best-seller-section">
			<div class="best-seller-header">
				<h3>Best-Seller</h3>
				<p>Découvrez nos best-sellers, les produits les plus appréciés par nos clients et incontournables de notre boutique.</p>
			</div>
	<div class="gallerieRotative">
    <div class="image-container">
        <?php if (!empty($bestSellers)): ?>
            <?php foreach ($bestSellers as $index => $product): ?>
                <?php 
                    $i = $index + 1;
                    $imageSrc = !empty($product['image']) ? '/Projet-Tr-sor-de-Main/' . $product['image'] : '/Projet-Tr-sor-de-Main/assets/images/Tour-de-potier.jpg';
                    $productName = htmlspecialchars($product['nom_article']);
                    $productPrice = number_format($product['prix'], 0, ',', ' ') . ' €';
                    $productDesc = htmlspecialchars(substr($product['description'] ?? '', 0, 100));
                    $productId = $product['id_article'];
                ?>
                <span style="--i:<?= $i ?>">
                    <a href="produit_detail.php?id=<?= $productId ?>" class="Gal_card-link">
                        <div class="Gal_card">
                            <img src="<?= $imageSrc ?>" alt="<?= $productName ?>">
                            <div class="info">
                                <p><?= $productName ?> - <?= $productPrice ?></p>
                                <p><?= $productDesc ?></p>
                            </div>
                        </div>
                    </a>
                </span>
            <?php endforeach; ?>
            <?php 
            // Compléter avec des doublons si moins de 8 produits
            $count = count($bestSellers);
            if ($count < 8 && $count > 0):
                for ($j = $count; $j < 8; $j++):
                    $product = $bestSellers[$j % $count];
                    $i = $j + 1;
                    $imageSrc = !empty($product['image']) ? '/Projet-Tr-sor-de-Main/' . $product['image'] : '/Projet-Tr-sor-de-Main/assets/images/Tour-de-potier.jpg';
                    $productName = htmlspecialchars($product['nom_article']);
                    $productPrice = number_format($product['prix'], 0, ',', ' ') . ' €';
                    $productDesc = htmlspecialchars(substr($product['description'] ?? '', 0, 100));
                    $productId = $product['id_article'];
            ?>
                <span style="--i:<?= $i ?>">
                    <a href="produit_detail.php?id=<?= $productId ?>" class="Gal_card-link">
                        <div class="Gal_card">
                            <img src="<?= $imageSrc ?>" alt="<?= $productName ?>">
                            <div class="info">
                                <p><?= $productName ?> - <?= $productPrice ?></p>
                                <p><?= $productDesc ?></p>
                            </div>
                        </div>
                    </a>
                </span>
            <?php endfor; endif; ?>
        <?php else: ?>
            <!-- Fallback si pas de produits en BDD -->
            <span style="--i:1">
				<a href="All_Products.php" class="Gal_card-link">
				<div class="Gal_card">
					<img src="/Projet-Tr-sor-de-Main/assets/images/Assiette-Chats.jpg" alt="Assiette-Chats">
					<div class="info">
						<p>Assiette-Chats - 40 €</p>
						<p>Assiette en céramique illustrée de chats bleus inspiré de la porcelaine japonaise traditionnelle</p>
					</div>
				</div>
				</a>
			</span>
            <span style="--i:2">
				<a href="All_Products.php" class="Gal_card-link">
				<div class="Gal_card">
					<img src="/Projet-Tr-sor-de-Main/assets/images/Tasse-visage.jpg" alt="Tasse-visage">
					<div class="info">
						<p>Tasse-visage - 10 €</p>
						<p>Une tasse sculpturale, mêlant art céramique et design expressif pour éveiller la curiosité.</p>
					</div>
				</div>
				</a>
			</span>
            <span style="--i:3">
				<a href="All_Products.php" class="Gal_card-link">
				<div class="Gal_card">
					<img src="/Projet-Tr-sor-de-Main/assets/images/bol breton zakia.png" alt="Bol breton personnalisé">
					<div class="info">
						<p>Bol breton personnalisé - 15 000 €</p>
						<p>Un bol traditionnel breton personnalisé à votre guise</p>
					</div>
				</div>
				</a>
			</span>
            <span style="--i:4">
				<a href="All_Products.php" class="Gal_card-link">
				<div class="Gal_card">
					<img src="/Projet-Tr-sor-de-Main/assets/images/Tasse-Noireaudes.jpg" alt="Tasse-Noireaudes">
					<div class="info">
						<p>Tasse-Noireaudes - 1 000 €</p>
						<p>Une tasse féerique aux accents animés, où des créatures étoilées éveillent l'imaginaire avec douceur et fantaisie.</p>
					</div>
				</div>
				</a>
			</span>
            <span style="--i:5">
				<a href="All_Products.php" class="Gal_card-link">
				<div class="Gal_card">
					<img src="/Projet-Tr-sor-de-Main/assets/images/Assiette-Chats.jpg" alt="Assiette-Chats">
					<div class="info">
						<p>Assiette-Chats - 40 €</p>
						<p>Assiette en céramique illustrée de chats bleus inspiré de la porcelaine japonaise traditionnelle</p>
					</div>
				</div>
				</a>
			</span>
            <span style="--i:6">
				<a href="All_Products.php" class="Gal_card-link">
				<div class="Gal_card">
					<img src="/Projet-Tr-sor-de-Main/assets/images/Tasse-visage.jpg" alt="Tasse-visage">
					<div class="info">
						<p>Tasse-visage - 10 €</p>
						<p>Une tasse sculpturale, mêlant art céramique et design expressif pour éveiller la curiosité.</p>
					</div>
				</div>
				</a>
			</span>
            <span style="--i:7">
				<a href="All_Products.php" class="Gal_card-link">
				<div class="Gal_card">
					<img src="/Projet-Tr-sor-de-Main/assets/images/bol breton zakia.png" alt="Bol breton personnalisé">
					<div class="info">
						<p>Bol breton personnalisé - 15 000 €</p>
						<p>Un bol traditionnel breton personnalisé à votre guise</p>
					</div>
				</div>
				</a>
			</span>
            <span style="--i:8">
				<a href="All_Products.php" class="Gal_card-link">
				<div class="Gal_card">
					<img src="/Projet-Tr-sor-de-Main/assets/images/Tasse-Noireaudes.jpg" alt="Tasse-Noireaudes">
					<div class="info">
						<p>Tasse-Noireaudes - 1 000 €</p>
						<p>Une tasse féerique aux accents animés, où des créatures étoilées éveillent l'imaginaire avec douceur et fantaisie.</p>
					</div>
				</div>
				</a>
			</span>
        <?php endif; ?>
    </div>
    
    <div class="btn-container">
        <button class="btn" id="prev">&#8592;</button>
        <button class="btn" id="next">&#8594;</button>
    </div>
    <script src="/Projet-Tr-sor-de-Main/JavaScript/gallerieRotative.js"></script>
	</div>
	<div class="best-seller-cta">
		<a href="All_Products.php" class="bouton">Voir tout</a>
	</div>
	</section>

        	<section>
                <div class="boite-contenu">
                    <h3>Promotions en Cours</h3>
                    <p>Ne manquez pas nos offres spéciales et promotions sur une sélection d'œuvres uniques.</p>
                    <a href="All_Products.php" class="bouton">Découvrir</a>
                </div>
            </section>
			<a name="#A_Propos"></a>
			<section>
                <div class="boite-contenu">
                    <h3>A Propos de nous</h3>
                    <p><strong>Trésor de Main</strong> est né d'une conviction simple : derrière chaque objet façonné à la main se cache une histoire, une patience et une âme. Nous ne sommes pas simplement une place de marché, mais une galerie vivante dédiée à la promotion des créateurs, des artistes et des artisans d'exception.</p>
                    <br>
                    <p>Notre plateforme a pour vocation de redonner ses lettres de noblesse au "Fait-Main". En connectant directement les amateurs d'art aux créateurs, nous valorisons un savoir-faire authentique, loin de la production de masse. Chaque pièce disponible ici est unique, fruit d'heures de travail et de passion.</p>
                    <br>
                    <p>Que vous soyez à la recherche d'une sculpture émouvante, d'une peinture vibrante ou d'un bijou singulier, vous participez à une économie plus humaine et créative. Soutenez le talent, offrez de l'unique.</p>
                    <a href="a-propos.php" class="bouton">En savoir plus</a>
                </div>
            </section>

            <!-- Section Confiance -->
            <section>
                <h3 style="text-align: center;">Pourquoi nous faire confiance ?</h3>
                <div class="trust-indicators">
                    <div class="trust-item">
                        <span class="trust-title">100% Fait Main</span>
                        <span class="trust-desc">Chaque création est unique et réalisée par des artisans passionnés</span>
                    </div>
                    <div class="trust-item">
                        <span class="trust-title">Artisans Vérifiés</span>
                        <span class="trust-desc">Tous nos artisans sont vérifiés et validés par notre équipe</span>
                    </div>
                    <div class="trust-item">
                        <span class="trust-title">Contact Direct</span>
                        <span class="trust-desc">Échangez directement avec les artisans en toute confiance</span>
                    </div>
                    <div class="trust-item">
                        <span class="trust-title">Support Réactif</span>
                        <span class="trust-desc">Notre équipe est à votre écoute pour vous accompagner</span>
                    </div>
                </div>
            </section>

        	<section class="Follow">
				<h3>Nos Artisans</h3>
				<p>Les meilleurs artisans sont à votre disposition pour répondre à vos besoins.</p>
		<div class="scroll-container">
			<?php if (!empty($artisans)): ?>
				<?php 
				// Dupliquer les artisans pour avoir un défilement continu
				$allArtisans = array_merge($artisans, $artisans);
				foreach ($allArtisans as $artisan): 
					$artisanPhoto = !empty($artisan['photo_profil']) ? '/Projet-Tr-sor-de-Main/' . $artisan['photo_profil'] : '/Projet-Tr-sor-de-Main/assets/images/default-avatar.svg';
					$artisanName = htmlspecialchars($artisan['prenom'] . ' ' . $artisan['nom']);
					$artisanDesc = !empty($artisan['specialite']) ? htmlspecialchars($artisan['specialite']) : (!empty($artisan['description']) ? htmlspecialchars(substr($artisan['description'], 0, 80)) : 'Artisan passionné');
					$artisanId = $artisan['id'];
				?>
				<a href="artisan.php?id=<?= $artisanId ?>" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="<?= $artisanPhoto ?>" alt="<?= $artisanName ?>"/>
						<div class="info">
							<p><?= $artisanName ?></p>
							<p><?= $artisanDesc ?></p>
						</div>
					</div>
				</a>
				<?php endforeach; ?>
			<?php else: ?>
				<!-- Fallback si pas d'artisans en BDD -->
				<a href="All_Products.php" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="/Projet-Tr-sor-de-Main/assets/images/Théobald_Cuivreforge.jpg" alt="Théobald Cuivreforge"/>
						<div class="info">
							<p>Théobald Cuivreforge</p>
							<p>Artisan menuisier passionné et experimenté</p>
						</div>
					</div>
				</a>
				<a href="All_Products.php" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="/Projet-Tr-sor-de-Main/assets/images/Adele_Finepinceau.jpg" alt="Adele Finepinceau"/>
						<div class="info">
							<p>Adele Finepinceau</p>
							<p>Peintre virtuose qui manie ses pinceaux avec une précision inégalée</p>
						</div>
					</div>
				</a>
				<a href="All_Products.php" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="/Projet-Tr-sor-de-Main/assets/images/Théobald_Cuivreforge.jpg" alt="Théobald Cuivreforge"/>
						<div class="info">
							<p>Théobald Cuivreforge</p>
							<p>Artisan menuisier passionné et experimenté</p>
						</div>
					</div>
				</a>
				<a href="All_Products.php" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="/Projet-Tr-sor-de-Main/assets/images/Adele_Finepinceau.jpg" alt="Adele Finepinceau"/>
						<div class="info">
							<p>Adele Finepinceau</p>
							<p>Peintre virtuose qui manie ses pinceaux avec une précision inégalée</p>
						</div>
					</div>
				</a>
				<a href="All_Products.php" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="/Projet-Tr-sor-de-Main/assets/images/Théobald_Cuivreforge.jpg" alt="Théobald Cuivreforge"/>
						<div class="info">
							<p>Théobald Cuivreforge</p>
							<p>Artisan menuisier passionné et experimenté</p>
						</div>
					</div>
				</a>
				<!-- Dupliquer pour le défilement continu -->
				<a href="All_Products.php" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="/Projet-Tr-sor-de-Main/assets/images/Adele_Finepinceau.jpg" alt="Adele Finepinceau"/>
						<div class="info">
							<p>Adele Finepinceau</p>
							<p>Peintre virtuose qui manie ses pinceaux avec une précision inégalée</p>
						</div>
					</div>
				</a>
				<a href="All_Products.php" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="/Projet-Tr-sor-de-Main/assets/images/Théobald_Cuivreforge.jpg" alt="Théobald Cuivreforge"/>
						<div class="info">
							<p>Théobald Cuivreforge</p>
							<p>Artisan menuisier passionné et experimenté</p>
						</div>
					</div>
				</a>
				<a href="All_Products.php" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="/Projet-Tr-sor-de-Main/assets/images/Adele_Finepinceau.jpg" alt="Adele Finepinceau"/>
						<div class="info">
							<p>Adele Finepinceau</p>
							<p>Peintre virtuose qui manie ses pinceaux avec une précision inégalée</p>
						</div>
					</div>
				</a>
				<a href="All_Products.php" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="/Projet-Tr-sor-de-Main/assets/images/Théobald_Cuivreforge.jpg" alt="Théobald Cuivreforge"/>
						<div class="info">
							<p>Théobald Cuivreforge</p>
							<p>Artisan menuisier passionné et experimenté</p>
						</div>
					</div>
				</a>
				<a href="All_Products.php" class="artisan-card-link">
					<div class="scroll-objet card">
						<img src="/Projet-Tr-sor-de-Main/assets/images/Adele_Finepinceau.jpg" alt="Adele Finepinceau"/>
						<div class="info">
							<p>Adele Finepinceau</p>
							<p>Peintre virtuose qui manie ses pinceaux avec une précision inégalée</p>
						</div>
					</div>
				</a>
			<?php endif; ?>
		</div>
			</section>
		</article>
		
 <!-- Pied de page -->
    <?php include '../HTML/footer.html'; ?>
	
<script src="../JavaScript/script.js"></script>
</body>
</html>
