<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

function encryptId($id)
{
    $key = 'UltraHospital@2026#SecureKey';

    $iv = openssl_random_pseudo_bytes(
        openssl_cipher_iv_length('aes-256-cbc')
    );

    $encrypted = openssl_encrypt(
        $id,
        'aes-256-cbc',
        $key,
        0,
        $iv
    );

    return base64_encode($iv . $encrypted);
}
function sendRegistrationEmail(
    $conn,
    $hospital_id,
    $name,
    $email,
    $password,
    $message = "Congratulations! Your hospital has been successfully registered with <strong>UltraHospital Management System.</strong>"
)
{
    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ultrahospital8@gmail.com';
        $mail->Password = 'rjuk cjay cbeq wrub';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('ultrahospital8@gmail.com', 'UltraHospital');
        $mail->isHTML(true);

        // Get Email Template
        $template = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT subject, body
             FROM email_templates
             WHERE template_name='successful_registration'"
        ));

        // Get Hospital Details
        $hospital = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT hospital_name,
                    hospital_code,
                    hospital_logo
            FROM hospital_master
            WHERE hospital_id='$hospital_id'"
        ));

        if (!empty($hospital['hospital_logo'])) {

    $logoPath = __DIR__ . '/../' . $hospital['hospital_logo'];

    if (file_exists($logoPath)) {

        $mail->addEmbeddedImage(
            $logoPath,
            'hospital_logo'
        );

        $bodyLogo = '<img src="cid:hospital_logo" alt="' . htmlspecialchars($hospital['hospital_name']) . '" style="max-width:120px;height:auto;">';

    } else {

        $bodyLogo = '';

    }

} else {

    $bodyLogo = '';

}

        // Encrypt Hospital ID
        $encryptedHospitalId = urlencode(encryptId($hospital_id));

        $loginLink = "http://localhost/Ultra_Hospital/UltraHospital-main/index.php?hid=".$encryptedHospitalId;

        $body = $template['body'];

        $body = str_replace("{message}", $message, $body);
        $body = str_replace("{admin_name}", $name, $body);
        $body = str_replace("{hospital_name}", $hospital['hospital_name'], $body);
        $body = str_replace("{hospital_code}", $hospital['hospital_code'], $body);
        $body = str_replace("{email}", $email, $body);
        $body = str_replace("{password}", $password, $body);
        $body = str_replace("{login_link}", $loginLink, $body);
        $body = str_replace("{year}", date('Y'), $body);
        $body = str_replace("{hospital_logo}", $bodyLogo, $body);

        $mail->addAddress($email, $name);
        $mail->Subject = $template['subject'];
        $mail->Body = $body;

        return $mail->send();

    } catch (Exception $e) {
        error_log($mail->ErrorInfo);
        return false;
    }
}