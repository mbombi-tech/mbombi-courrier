<?php
// helpers/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function requireLogin() {
    if (!isset($_SESSION["user"])) {
        header("Location: /login.php"); // racine du site
        exit;
    }
}

/**
 * Vérifie si l'utilisateur a le bon rôle
 * Exemple : requireRole(['admin', 'directeur']);
 */
function requireRole(array $roles) {
    if (!isset($_SESSION["user"]["role"]) || !in_array($_SESSION["user"]["role"], $roles)) {
        http_response_code(403);
        echo "<h3 style='color:red;text-align:center;margin-top:50px'>⛔ Accès refusé</h3>";
        exit;
    }
}

/**
 * Récupère l'utilisateur connecté
 */
function currentUser() {
    return $_SESSION["user"] ?? null;
}

/**
 * Vérifications rapides de rôles
 */
function isAdmin() {
    return isset($_SESSION["user"]["role"]) && $_SESSION["user"]["role"] === "admin";
}

function isAgent() {
    return isset($_SESSION["user"]["role"]) && $_SESSION["user"]["role"] === "agent";
}

function isDirecteur() {
    return isset($_SESSION["user"]["role"]) && $_SESSION["user"]["role"] === "directeur";
}
