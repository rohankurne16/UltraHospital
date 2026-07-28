<?php
// ============================================================
// ENCRYPTION FUNCTIONS
// ============================================================

if (!function_exists('encryptId')) {
    function encryptId($id)
    {
        $key = 'UltraHospital@2026#SecureKey';
        $cipher = 'aes-256-cbc';
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($id, $cipher, $key, 0, $iv);
        return urlencode(base64_encode($iv . $encrypted));
    }
}

if (!function_exists('decryptId')) {
    function decryptId($encrypted)
    {
        $key = 'UltraHospital@2026#SecureKey';
        $cipher = 'aes-256-cbc';
        $data = base64_decode(urldecode($encrypted));
        $ivLength = openssl_cipher_iv_length($cipher);
        $iv = substr($data, 0, $ivLength);
        $encryptedText = substr($data, $ivLength);
        return openssl_decrypt($encryptedText, $cipher, $key, 0, $iv);
    }
}
?>