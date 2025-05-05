<?php

use Firebase\JWT\JWT;
use Firebase\JWT\CachedKeySet;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;


use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;


const FIREBASE_JWKS_URI = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';


$httpClient = new Client(); 
$httpFactory = new HttpFactory(); 


$cacheItemPool = new FilesystemAdapter('firebase_jwks', 0, sys_get_temp_dir() . '/php-cache');

$firebaseKeySet = new CachedKeySet(
    FIREBASE_JWKS_URI,
    $httpClient,
    $httpFactory,
    $cacheItemPool,
    null, 
    true  
);

/**
 * Vérifie la signature et les revendications standard d'un jeton Firebase ID en utilisant CachedKeySet.
 *
 * @param string|null $idToken Le JWT à vérifier.
 * @return object|null Le payload du jeton décodé (objet) si valide, sinon null.
 */
function verifyFirebaseToken(?string $idToken): ?object {
    global $firebaseKeySet; 

    if (!$idToken) {
        return null;
    }

    try {
    
        $decodedToken = JWT::decode($idToken, $firebaseKeySet);

        $firebaseProjectId = getenv('FIREBASE_PROJECT_ID');
        if (empty($firebaseProjectId)) {
             error_log('ERREUR: la variable d\'environnement FIREBASE_PROJECT_ID n\'est pas définie.');
             throw new RuntimeException('Erreur de configuration du serveur: le projet Firebase n\'est pas défini.');
        }
        if (!isset($decodedToken->aud) || $decodedToken->aud !== $firebaseProjectId) {
            throw new UnexpectedValueException('Audience invalide. Attendu: ' . $firebaseProjectId . ' Obtenu: ' . ($decodedToken->aud ?? 'null'));
        }

        
        $expectedIssuer = 'https://securetoken.google.com/' . $firebaseProjectId;
        if (!isset($decodedToken->iss) || $decodedToken->iss !== $expectedIssuer) {
            throw new UnexpectedValueException('Issuer invalide. Attendu: ' . $expectedIssuer . ' Obtenu: ' . ($decodedToken->iss ?? 'null'));
        }

        
        if (!isset($decodedToken->sub) || empty($decodedToken->sub)) {
            throw new UnexpectedValueException('Revendication (ID utilisateur) manquante ou vide.');
        }

        
        if (!isset($decodedToken->auth_time) || $decodedToken->auth_time > time()) {
             throw new UnexpectedValueException('Revendication (temps d\'authentification) manquante ou dans le futur.');
        }

        
        return $decodedToken; 

    
    } catch (InvalidArgumentException $e) {
        
        error_log('Vérification du jeton Firebase échouée: Argument invalide/Problème de clé. ' . $e->getMessage());
        return null;
    } catch (DomainException $e) {
        
        error_log('Vérification du jeton Firebase échouée: Exception de domaine (Algorithme/Clé/OpenSSL/Libsodium). ' . $e->getMessage());
        return null;
    } catch (SignatureInvalidException $e) {
        
        error_log('Vérification du jeton Firebase échouée: Signature invalide. ' . $e->getMessage());
        return null;
    } catch (BeforeValidException $e) {
        
        error_log('Vérification du jeton Firebase échouée: Jeton utilisé avant la date valide (nbf/iat). ' . $e->getMessage());
        return null;
    } catch (ExpiredException $e) {
        
        error_log('Vérification du jeton Firebase échouée: Jeton expiré (exp). ' . $e->getMessage());
        return null;
    } catch (UnexpectedValueException $e) {
        
        error_log('Vérification du jeton Firebase échouée: Valeur inattendue (Jeton malformé/Algorithme/Revendications). ' . $e->getMessage());
        return null;
    } catch (RuntimeException $e) { 
        error_log('Vérification du jeton Firebase échouée: Exception de runtime. ' . $e->getMessage());
        
        return null;
    } catch (\Exception $e) { 
        error_log('Vérification du jeton Firebase échouée: Exception générique (' . get_class($e) . '). ' . $e->getMessage());
        return null;
    }
}



/**
 * Récupère le jeton Firebase ID de l'en-tête Authorization et le vérifie.
 *
 * @return object|null Le payload du jeton décodé si l'authentification est réussie, null sinon.
 */
function getAuthenticatedFirebaseUser(): ?object {
     $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
     
     if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
         return null;
     }
     $idToken = $matches[1];
     return verifyFirebaseToken($idToken);
}

/**
 * Exige l'authentification Firebase pour la requête actuelle.
 * Si l'authentification échoue, il envoie une réponse 401 Non autorisée et quitte.
 *
 * @param bool $requireEmailVerified (Optionnel) Si true, vérifie également si la revendication email_verified est true. Envoie 403 si non.
 * @return object Le payload du jeton décodé si l'authentification est réussie.
 */
function requireFirebaseAuthentication(bool $requireEmailVerified = false): object {
    $decodedToken = getAuthenticatedFirebaseUser();
    
    if (!$decodedToken) {
        http_response_code(401); 
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'message' => 'Authentification requise. Jeton invalide ou manquant.']);
        exit;
    }

    
    if ($requireEmailVerified && !($decodedToken->email_verified ?? false)) {
        http_response_code(403); 
        header('Content-Type: application/json');
        echo json_encode(['error' => true, 'message' => 'Vérification de l\'email requise.']);
        exit;
    }
    
    return $decodedToken; 
}

?>
