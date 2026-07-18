<?php
session_start();

// Always set theme to light (no dark mode)
if (isset($_POST['theme'])) {
    // Only allow 'light' theme
    if ($_POST['theme'] == 'light') {
        $_SESSION['theme'] = 'light';
        echo json_encode(['success' => true, 'theme' => 'light']);
    } else {
        // Force light theme
        $_SESSION['theme'] = 'light';
        echo json_encode(['success' => true, 'theme' => 'light', 'message' => 'Only light theme is supported']);
    }
} else {
    // Default to light theme
    $_SESSION['theme'] = 'light';
    echo json_encode(['success' => true, 'theme' => 'light']);
}
?>