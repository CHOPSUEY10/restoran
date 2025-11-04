<?php

namespace App\Controllers;
use CodeIgniter\Controller;

class FirebaseController extends Controller
{
    // Fungsi untuk mengirim konfigurasi Firebase ke frontend
    public function getConfig()
    {
        $config = [
            'apiKey'            => getenv('FIREBASE_API_KEY'),
            'authDomain'        => getenv('FIREBASE_AUTH_DOMAIN'),
            'projectId'         => getenv('FIREBASE_PROJECT_ID'),
            'storageBucket'     => getenv('FIREBASE_STORAGE_BUCKET'),
            'databaseURL'       => getenv('FIREBASE_DATABASE_URL'),
            'messagingSenderId' => getenv('FIREBASE_MESSAGING_SENDER_ID'),
            'appId'             => getenv('FIREBASE_APP_ID'),
            'measurementId'     => getenv('FIREBASE_MEASUREMENT_ID'),
        ];

        // Kirim ke frontend dalam format JSON
        return $this->response->setJSON($config);
    }

    // (Opsional) Fungsi untuk cek apakah koneksi ke Firebase aktif
    public function testConnection()
    {
        $apiKey = getenv('FIREBASE_API_KEY');
        $projectId = getenv('FIREBASE_PROJECT_ID');

        if ($apiKey && $projectId) {
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Firebase configuration loaded successfully!',
                'projectId' => $projectId
            ]);
        } else {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Firebase configuration not found. Check your .env file!'
            ]);
        }
    }
}
