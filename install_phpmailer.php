<?php
// install_phpmailer.php
$base_url = "https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/";
$files = ["Exception.php", "PHPMailer.php", "SMTP.php"];

$target_dir = __DIR__ . "/api/vendor/PHPMailer/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

foreach ($files as $file) {
    if (!file_exists($target_dir . $file)) {
        echo "Downloading $file...\n";
        $content = file_get_contents($base_url . $file);
        if ($content) {
            file_put_contents($target_dir . $file, $content);
            echo "$file downloaded successfully.\n";
        } else {
            echo "Failed to download $file.\n";
        }
    } else {
        echo "$file already exists.\n";
    }
}
echo "PHPMailer basic installation complete.\n";
?>
