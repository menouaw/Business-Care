<?php
require_once __DIR__ . '/../../init.php';

/**
 * recupere les details d'un don
 * 
 * @param int $id identifiant du don
 * @return array|false donnees du don ou false si non trouve
 */
function donationsGetDetails($id) {
    $sql = "SELECT d.*, 
                   p_donor.nom as donor_nom, p_donor.prenom as donor_prenom, p_donor.email as donor_email
            FROM " . TABLE_DONATIONS . " d
            LEFT JOIN " . TABLE_USERS . " p_donor ON d.personne_id = p_donor.id
            WHERE d.id = ? 
            LIMIT 1";
            
    $donation = executeQuery($sql, [$id])->fetch();

    if ($donation && $donation['type'] == 'financier' && isset($donation['montant'])) {
        $donation['montant_formate'] = formatMoney($donation['montant']);
    }

    return $donation;
}

?>
