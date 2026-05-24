<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;

class PagesManagerController extends Controller
{
    public function index(Request $request): void
    {
        $rows = Database::getInstance()->fetchAll('SELECT * FROM pages ORDER BY page_type, title');
        $this->view('admin.pages.pages.index', ['title' => 'Pages', 'rows' => $rows]);
    }

    public function edit(Request $request): void
    {
        $id = (int) $request->param('id');
        $row = Database::getInstance()->fetch('SELECT * FROM pages WHERE id = :id LIMIT 1', [':id' => $id]);
        if (!$row) { Response::notFound(); return; }
        $this->view('admin.pages.pages.edit', ['title' => 'Edit · ' . $row['title'], 'row' => $row]);
    }

    public function update(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $db = Database::getInstance();
        $row = $db->fetch('SELECT * FROM pages WHERE id = :id LIMIT 1', [':id' => $id]);
        if (!$row) { Response::notFound(); return; }
        $title = trim((string) $request->post('title', ''));
        $slug  = slugify($request->post('slug') ?: $title);
        $body  = (string) $request->post('body', '');
        if ($title === '') { Session::instance()->flash('error', 'Title required.'); $this->redirect(admin_url('pages/' . $id . '/edit')); return; }
        // Sanitize body (basic - admin entered content). Strip <script>, on* attrs, javascript: URLs.
        $sanitized = self::sanitizeHtml($body);
        $admin = Auth::admin();
        $db->transaction(function () use ($db, $row, $id, $title, $slug, $body, $sanitized, $admin) {
            $db->insert('page_revisions', [
                'page_id' => $id, 'title' => $row['title'], 'body' => $row['body'], 'sanitized_body' => $row['sanitized_body'],
                'created_by_admin_id' => $admin['id'],
            ]);
            // Slug uniqueness
            $clash = $db->fetch('SELECT id FROM pages WHERE slug = :s AND id <> :id LIMIT 1', [':s' => $slug, ':id' => $id]);
            if ($clash) $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
            $db->update('pages', [
                'title' => $title, 'slug' => $slug, 'body' => $body, 'sanitized_body' => $sanitized,
                'updated_by_admin_id' => $admin['id'],
            ], 'id = :id', [':id' => $id]);
        });
        AuditService::log('page.update', 'page', $id);
        Session::instance()->flash('success', 'Page saved.');
        $this->redirect(admin_url('pages/' . $id . '/edit'));
    }

    public function publish(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $db = Database::getInstance();
        $row = $db->fetch('SELECT * FROM pages WHERE id = :id LIMIT 1', [':id' => $id]);
        if (!$row) { Response::notFound(); return; }
        $newStatus = $row['status'] === 'published' ? 'draft' : 'published';
        $db->update('pages', [
            'status' => $newStatus,
            'published_at' => $newStatus === 'published' ? date('Y-m-d H:i:s') : null,
        ], 'id = :id', [':id' => $id]);
        AuditService::log('page.' . ($newStatus === 'published' ? 'publish' : 'unpublish'), 'page', $id);
        Session::instance()->flash('success', 'Page ' . $newStatus . '.');
        $this->redirect(admin_url('pages/' . $id . '/edit'));
    }

    /**
     * Conservative HTML sanitizer for admin-entered legal/static page content.
     * Allows a curated tag set, strips event handlers and javascript: URLs.
     */
    public static function sanitizeHtml(string $html): string
    {
        // Remove <script>, <style>, <iframe>, <object>, <embed>
        $html = preg_replace('#<(script|style|iframe|object|embed)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        // Remove on* event attributes
        $html = preg_replace('#\son[a-z]+\s*=\s*"[^"]*"#i', '', $html) ?? $html;
        $html = preg_replace("#\son[a-z]+\s*=\s*'[^']*'#i", '', $html) ?? $html;
        // Remove javascript: in href/src
        $html = preg_replace('#(href|src)\s*=\s*"javascript:[^"]*"#i', '$1="#"', $html) ?? $html;
        $html = preg_replace("#(href|src)\s*=\s*'javascript:[^']*'#i", '$1="#"', $html) ?? $html;
        // Allowlist tags
        $allowed = '<p><br><strong><b><em><i><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre><hr><span><div><table><thead><tbody><tr><th><td><img><figure><figcaption>';
        return strip_tags($html, $allowed);
    }
}
