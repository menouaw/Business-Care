import { initializeApp } from "firebase/app";
import { getVertexAI, getGenerativeModel } from "firebase/vertexai";

// TODO(developer) Replace the following with your app's Firebase configuration
// See: https://firebase.google.com/docs/web/learn-more#config-object
const firebaseConfig = {
    apiKey: "AIzaSyDlXeERsJavObt1FbhBBlRD7MlEwFqUoaA",
    authDomain: "business-care-9a9d8.firebaseapp.com",
    databaseURL: "https://business-care-9a9d8-default-rtdb.europe-west1.firebasedatabase.app",
    projectId: "business-care-9a9d8",
    storageBucket: "business-care-9a9d8.firebasestorage.app",
      messagingSenderId: "881729160757",
     appId: "1:881729160757:web:7de488be69bf904aa0537c",
     measurementId: "G-6BTRM15PX6"
};

// Initialize FirebaseApp
const firebaseApp = initializeApp(firebaseConfig);

const vertexAI = getVertexAI(firebaseApp, { location: "europe-west9"});

const model = getGenerativeModel(vertexAI, { model: "gemini-2.0-flash-lite-001" });

async function run() {
    const prompt = "write a story about a magic backpack.";

    const result = await model.generateContent(prompt);

    const response = result.response;

    const text = response.text();
    console.log(text);
}

run();