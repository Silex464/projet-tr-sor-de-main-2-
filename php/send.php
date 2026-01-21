<?php
if (isset($_POST['btn-submit'])) {
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    $to = "vdeLabareyre@gmail.com"; // Remplace par ton adresse
    $subject = "Nouveau message de contact";
    $body = "Nom: $nom\nEmail: $email\nMessage:\n$message";
    $headers = "From: $email";

    if (mail($to, $subject, $body, $headers)) {
        echo "<script>alert('Votre message a été envoyé avec succès'); window.location.href='form.html';</script>";
    } else {
        echo "<script>alert('Une erreur est survenue lors de l\\'envoi'); window.location.href='form.html';</script>";
    }
}
?>
