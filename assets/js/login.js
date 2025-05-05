document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const errorOutputDiv = document.getElementById('js-error-output');

    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault(); 

            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const email = emailInput.value;
            const password = passwordInput.value;

            
            if (!email || !password) {
                showError("Veuillez entrer l'email et le mot de passe.");
                return;
            }

            
            hideError();

            console.log(`Tentative de connexion pour ${email}...`);

            if (!window.firebaseAuth) {
                console.error("Les fonctions Firebase Auth ne sont pas disponibles.");
                showError("Erreur d'initialisation de l'authentification. Réessayez.");
                return;
            }

            try {
                const userCredential = await window.firebaseAuth.signIn(email, password);
                console.log("Connexion réussie:", userCredential.user.uid);

            } catch (error) {
                console.error("Échec de la connexion:", error.code, error.message);
                
                let friendlyErrorMessage = "Échec de la connexion. Vérifiez vos identifiants.";
                switch (error.code) {
                    case 'auth/invalid-credential':
                    case 'auth/user-not-found': 
                    case 'auth/wrong-password': 
                        friendlyErrorMessage = "Email ou mot de passe incorrect.";
                        break;
                    case 'auth/invalid-email':
                        friendlyErrorMessage = "Le format de l'adresse email est invalide.";
                        break;
                    case 'auth/user-disabled':
                        friendlyErrorMessage = "Ce compte utilisateur a été désactivé.";
                        break;
                    case 'auth/too-many-requests':
                        friendlyErrorMessage = "Trop de tentatives de connexion. Réessayez plus tard.";
                        break;
                    
                    default:
                        friendlyErrorMessage = `Une erreur s'est produite: ${error.message}`;
                }
                showError(friendlyErrorMessage);
            }
        });
    }

    function showError(message) {
        if (errorOutputDiv) {
            errorOutputDiv.textContent = message;
            errorOutputDiv.style.display = 'block';
        }
    }

    function hideError() {
        if (errorOutputDiv) {
            errorOutputDiv.style.display = 'none';
            errorOutputDiv.textContent = '';
        }
    }
});
