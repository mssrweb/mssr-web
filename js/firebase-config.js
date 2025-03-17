// Firebase yapılandırması
const firebaseConfig = {
    // Firebase Console'dan alınan yapılandırma bilgileri buraya eklenecek
    apiKey: "YOUR_API_KEY",
    authDomain: "your-project.firebaseapp.com",
    projectId: "your-project",
    storageBucket: "your-project.appspot.com",
    messagingSenderId: "your-messaging-sender-id",
    appId: "your-app-id"
};

// Firebase'i başlat
firebase.initializeApp(firebaseConfig);

// Firestore referansı
const db = firebase.firestore(); 