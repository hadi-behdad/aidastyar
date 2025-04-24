<?php
// Check if user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url()); // Redirect to login page
    exit;
}

// Load the form HTML
include 'form.html';
?>