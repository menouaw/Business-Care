
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-app.js";
import { getAnalytics } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-analytics.js";
import { getAuth, onAuthStateChanged, createUserWithEmailAndPassword, signInWithEmailAndPassword, signOut } from "https://www.gstatic.com/firebasejs/11.6.1/firebase-auth.js";




const firebaseConfig = {
  apiKey: "AIzaSyDlXeERsJavObt1FbhBBlRD7MlEwFqUoaA",
  authDomain: "business-care-9a9d8.firebaseapp.com",
  projectId: "business-care-9a9d8",
  storageBucket: "business-care-9a9d8.firebasestorage.app",
  messagingSenderId: "881729160757",
  appId: "1:881729160757:web:7de488be69bf904aa0537c",
  measurementId: "G-6BTRM15PX6"
};


const app = initializeApp(firebaseConfig);

const auth = getAuth(app);

const analytics = getAnalytics(app);




onAuthStateChanged(auth, async (user) => {
  
  if (user) {
    
    console.log("Firebase Auth: utilisateur connecté avec l'identifiant :", user.uid);
    
    const currentPath = window.location.pathname;
    console.log("Etat de l'authentification changé, chemin actuel :", currentPath);

    try {
      
      
      const idToken = await user.getIdToken();
      
      
      sessionStorage.setItem('firebaseIdToken', idToken);
      

      
      
      const isAdminLoginPath = currentPath.includes('/admin/login.php'); 
      const isClientLoginPath = currentPath.includes('/client/login.php');

      
      if (isAdminLoginPath) {
          console.log("Redirection: Page de connexion admin -> Tableau de bord admin");
          
          window.location.href = '/admin/index.php'; 
      
      } else if (isClientLoginPath) {
           console.log("Redirection: Page de connexion client -> Tableau de bord client");
           
           window.location.href = '/client/index.php';
      } else {
           
           
           console.log("Utilisateur connecté, mais pas sur une page de connexion connue. Reste sur la page actuelle.");
      }
      

    } catch (error) {
       
       console.error("Firebase Auth: erreur lors de la récupération du jeton ou de la redirection:", error);
       
       await signOut(auth);
       
       
    }
  } else {
    
    console.log("onAuthStateChanged: utilisateur NULL (déconnecté).");
    console.log("Firebase Auth: utilisateur déconnecté");
    
    sessionStorage.removeItem('firebaseIdToken');
    

    
    
    const adminAreaPrefix = '/admin/'; 
    const clientAreaPrefix = '/client/';
    const adminLoginPath = adminAreaPrefix + 'login.php'; 
    const clientLoginPath = clientAreaPrefix + 'login.php';
    const currentPath = window.location.pathname;

    console.log("Etat de l'authentification changé (déconnecté) - Chemin actuel:", currentPath);

    
    
    if (currentPath !== adminLoginPath && currentPath !== clientLoginPath) {
        
        if (currentPath.startsWith(adminAreaPrefix)) {
             console.log("Redirection: déconnexion de la zone admin -> Page de connexion admin");
             
             window.location.href = adminLoginPath;
        
        } else if (currentPath.startsWith(clientAreaPrefix)) {
             console.log("Redirection: déconnexion de la zone client -> Page de connexion client");
             
             window.location.href = clientLoginPath;
        } else {
            
            
            
            
            console.log("Utilisateur déconnecté de la zone publique/inconnue. Aucune redirection automatique.");
            
        }
    } else {
         
         console.log("Gestionnaire de déconnexion - Déjà sur une page de connexion. Aucune redirection nécessaire.");
    }
    
  }
});





window.firebaseAuth = {
    
    signUp: (email, password) => createUserWithEmailAndPassword(auth, email, password),
    
    signIn: (email, password) => signInWithEmailAndPassword(auth, email, password),
    
    logOut: () => signOut(auth),
    
    getCurrentUser: () => auth.currentUser,
    
    getIdToken: async () => {
        const user = auth.currentUser;
        
        if (!user) return null;
        try {
            
            
            
            return await user.getIdToken();
        } catch (error) {
            
            console.error("Erreur lors de la récupération/rafraîchissement du jeton ID:", error);
            
            
            return null;
        }
    }
};


console.log("Firebase initialized and auth state listener attached.");
