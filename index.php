<?php
/**
 * Point d'entrée principal - Trésor de Main
 * Redirige vers la page d'accueil
 */

// Inclure directement la page d'accueil au lieu de rediriger
// Cela fonctionne mieux avec nginx
include __DIR__ . '/php/Page_Acceuil.php';
