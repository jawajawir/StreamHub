<?php
/**
 * Helper to render an admin page inside the admin layout.
 *  Usage in a page view:
 *  ob_start(); ?>
 *    <h1>Hello</h1>
 *  <?php $content = ob_get_clean();
 *  include __DIR__ . '/../layouts/wrap.php';
 */
$content = $content ?? '';
$title   = $title   ?? 'Admin';
include __DIR__ . '/app.php';
