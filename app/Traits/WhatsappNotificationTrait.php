<?php

namespace App\Traits;

trait WhatsappNotificationTrait
{
    public function sendTextWatsapp($phone, $message)
    {
        // Membersihkan dan memformat nomor telepon
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = (str_starts_with($phone, '0')) ? '62' . substr($phone, 1) : $phone;

        // Inisialisasi cURL
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://app.wapanels.com/api/create-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'appkey' => env('APPKEY_SERVER_WAPANELS'),
                'authkey' => env('API_KEY_WAPANELS'),
                'to' => $phone,
                'message' => $message,
                'sandbox' => 'false'
            ),
        ));

        // Eksekusi cURL dan ambil respons
        $response = curl_exec($curl);

        // Tangani kesalahan cURL
        if (curl_errno($curl)) {
            curl_close($curl);
            return self::sendMessages_Starsender($phone, $message);
        }

        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode != 200) {
            \Log::info("Gagal Mengirim Menggunakan Watsapid " . date('Y-m-d H:i:s'));
            return self::sendMessages_Starsender($phone, $message);
        }

        $responseArray = json_decode($response, true);
        \Log::info("Berhasil Mengirim Menggunakan Watsapid " . date('Y-m-d H:i:s'));
        return;
    }
}
