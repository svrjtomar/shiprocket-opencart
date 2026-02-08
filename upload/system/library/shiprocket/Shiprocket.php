<?php
class Shiprocket {

    private $token;

    public function __construct($token) {
        $this->token = $token;
    }

    private function request($url, $data) {

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->token
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function createOrder($payload) {
        return $this->request(
            'https://apiv2.shiprocket.in/v1/external/orders/create/adhoc',
            $payload
        );
    }
}

