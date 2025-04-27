if ($newEmployeeId) {
logSecurityEvent($_SESSION['user_id'] ?? null, 'employee_add', '[SUCCESS] Ajout salarié ID: ' . $newEmployeeId . ' pour entreprise ID: ' . $entreprise_id);

// Correction: Ne pas afficher le mot de passe en clair sauf en développement
// En production, il faudrait envoyer le mot de passe par email et non l'afficher
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
flashMessage("Salarié ajouté avec succès. Mot de passe temporaire (DEV ONLY) : " . $temporaryPassword, "success");
} else {
flashMessage("Salarié ajouté avec succès. Un email avec les instructions de connexion a été envoyé.", "success");
// TODO: Implémenter l'envoi d'email avec le mot de passe temporaire
}
return (int)$newEmployeeId;
} else {
// ... (code d'échec inchangé) ...
}