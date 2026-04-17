<?php
$files = ['index.php', 'api/mailer.php', '.env.example'];

foreach ($files as $f) {
    if (file_exists($f)) {
        $content = file_get_contents($f);
        $content = str_replace('TRACO', 'EcoTracker', $content);
        $content = str_replace('traco', 'ecotracker', $content);
        file_put_contents($f, $content);
        echo "Updated $f\n";
    }
}
?>
