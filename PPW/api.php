<?php
// API Sederhana untuk CRUD Data Mahasiswa
// Menggunakan file JSON (data.json) sebagai simulasi database

header('Content-Type: application/json');
$data_file = 'data.json';

// Fungsi untuk membaca data dari file JSON
function readData($file) {
    if (!file_exists($file) || filesize($file) == 0) {
        return [];
    }
    $json = file_get_contents($file);
    return json_decode($json, true) ?: [];
}

// Fungsi untuk menyimpan data ke file JSON
function saveData($file, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    if (file_put_contents($file, $json) === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menulis ke file data.']);
        exit;
    }
}

// --- Logika Utama ---

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // READ (Mengambil semua data)
    $data = readData($data_file);
    echo json_encode($data);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CREATE, UPDATE, DELETE
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format JSON tidak valid.']);
        exit;
    }

    $data = readData($data_file);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'create':
            // CREATE (Menambah data baru)
            $new_data = [
                'id' => uniqid(), // ID unik untuk setiap entri
                'nama' => $input['nama'] ?? '',
                'nim' => $input['nim'] ?? '',
                'tanggalLahir' => $input['tanggalLahir'] ?? '',
                'alamat' => $input['alamat'] ?? ''
            ];
            
            // Validasi sederhana
            if (empty($new_data['nama']) || empty($new_data['nim'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Nama dan NIM wajib diisi.']);
                exit;
            }

            $data[] = $new_data;
            saveData($data_file, $data);
            echo json_encode(['success' => true, 'id' => $new_data['id']]);
            break;

        case 'update':
            // UPDATE (Mengubah data yang sudah ada)
            $found = false;
            foreach ($data as $index => $item) {
                if ($item['id'] === $input['id']) {
                    $data[$index]['nama'] = $input['nama'] ?? $item['nama'];
                    $data[$index]['nim'] = $input['nim'] ?? $item['nim'];
                    $data[$index]['tanggalLahir'] = $input['tanggalLahir'] ?? $item['tanggalLahir'];
                    $data[$index]['alamat'] = $input['alamat'] ?? $item['alamat'];
                    $found = true;
                    break;
                }
            }

            if ($found) {
                saveData($data_file, $data);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
            }
            break;

        case 'delete':
            // DELETE (Menghapus data)
            $id_to_delete = $input['id'] ?? null;
            if (!$id_to_delete) {
                 http_response_code(400);
                 echo json_encode(['success' => false, 'message' => 'ID data tidak diberikan.']);
                 exit;
            }

            $initial_count = count($data);
            $data = array_filter($data, function($item) use ($id_to_delete) {
                return $item['id'] !== $id_to_delete;
            });
            $final_count = count($data);

            if ($initial_count > $final_count) {
                // Data berhasil dihapus
                $data = array_values($data); // Mengatur ulang indeks array
                saveData($data_file, $data);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan untuk dihapus.']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
            break;
    }

} else {
    // Metode HTTP tidak diizinkan
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode HTTP tidak diizinkan.']);
}
?>
