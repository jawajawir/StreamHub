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

class TaxonomyController extends Controller
{
    // ---------- Categories ----------
    public function categories(Request $request): void
    {
        $rows = Database::getInstance()->fetchAll('SELECT * FROM categories ORDER BY name ASC');
        $this->view('admin.pages.taxonomy.categories', ['title' => 'Categories', 'rows' => $rows]);
    }
    public function storeCategory(Request $request): void { $this->validateCsrf($request); $this->upsertSimple('categories', null, $request); }
    public function updateCategory(Request $request): void { $this->validateCsrf($request); $this->upsertSimple('categories', (int) $request->param('id'), $request); }
    public function deleteCategory(Request $request): void { $this->validateCsrf($request); $this->deleteSimple('categories', (int) $request->param('id'), 'categories'); }

    // ---------- Tags ----------
    public function tags(Request $request): void
    {
        $rows = Database::getInstance()->fetchAll('SELECT * FROM tags ORDER BY name ASC LIMIT 1000');
        $this->view('admin.pages.taxonomy.tags', ['title' => 'Tags', 'rows' => $rows]);
    }
    public function storeTag(Request $request): void { $this->validateCsrf($request); $this->upsertTag(null, $request); }
    public function updateTag(Request $request): void { $this->validateCsrf($request); $this->upsertTag((int) $request->param('id'), $request); }
    public function deleteTag(Request $request): void { $this->validateCsrf($request); $this->deleteSimple('tags', (int) $request->param('id'), 'tags'); }

    // ---------- Performers ----------
    public function performers(Request $request): void
    {
        $rows = Database::getInstance()->fetchAll('SELECT * FROM performers ORDER BY name ASC LIMIT 500');
        $this->view('admin.pages.taxonomy.performers', ['title' => 'Performers', 'rows' => $rows]);
    }
    public function storePerformer(Request $request): void { $this->validateCsrf($request); $this->upsertEntity('performers', null, $request, ['name','bio','avatar_url','cover_url']); }
    public function updatePerformer(Request $request): void { $this->validateCsrf($request); $this->upsertEntity('performers', (int) $request->param('id'), $request, ['name','bio','avatar_url','cover_url']); }
    public function deletePerformer(Request $request): void { $this->validateCsrf($request); $this->deleteSimple('performers', (int) $request->param('id'), 'performers'); }

    // ---------- Studios ----------
    public function studios(Request $request): void
    {
        $rows = Database::getInstance()->fetchAll('SELECT * FROM studios ORDER BY name ASC');
        $this->view('admin.pages.taxonomy.studios', ['title' => 'Studios', 'rows' => $rows]);
    }
    public function storeStudio(Request $request): void { $this->validateCsrf($request); $this->upsertEntity('studios', null, $request, ['name','bio','logo_url','website_url']); }
    public function updateStudio(Request $request): void { $this->validateCsrf($request); $this->upsertEntity('studios', (int) $request->param('id'), $request, ['name','bio','logo_url','website_url']); }
    public function deleteStudio(Request $request): void { $this->validateCsrf($request); $this->deleteSimple('studios', (int) $request->param('id'), 'studios'); }

    // ---------- Series ----------
    public function series(Request $request): void
    {
        $rows = Database::getInstance()->fetchAll(
            'SELECT s.*, st.name AS studio_name FROM series_collections s LEFT JOIN studios st ON st.id = s.studio_id ORDER BY s.name ASC'
        );
        $this->view('admin.pages.taxonomy.series', ['title' => 'Series', 'rows' => $rows,
            'studios' => Database::getInstance()->fetchAll('SELECT id, name FROM studios WHERE status = "active" ORDER BY name')]);
    }
    public function storeSeries(Request $request): void { $this->validateCsrf($request); $this->upsertEntity('series_collections', null, $request, ['name','description','cover_url','studio_id']); }
    public function updateSeries(Request $request): void { $this->validateCsrf($request); $this->upsertEntity('series_collections', (int) $request->param('id'), $request, ['name','description','cover_url','studio_id']); }
    public function deleteSeries(Request $request): void { $this->validateCsrf($request); $this->deleteSimple('series_collections', (int) $request->param('id'), 'series'); }

    // ---------- helpers ----------
    private function upsertSimple(string $table, ?int $id, Request $request): void
    {
        $name = trim((string) $request->post('name', ''));
        if ($name === '') { Session::instance()->flash('error', 'Name required.'); $this->back(admin_url($table)); return; }
        $slug = slugify($request->post('slug') ?: $name);
        $description = trim((string) $request->post('description', ''));
        $status = in_array($request->post('status'), ['active','hidden','disabled'], true) ? $request->post('status') : 'active';
        $db = Database::getInstance();
        $existing = $db->fetch("SELECT id FROM $table WHERE slug = :s AND (:eid IS NULL OR id <> :eid)", [':s' => $slug, ':eid' => $id]);
        if ($existing) $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        if ($id) {
            $db->update($table, ['name' => $name, 'slug' => $slug, 'description' => $description ?: null, 'status' => $status], 'id = :id', [':id' => $id]);
            AuditService::log("$table.update", $table, $id);
        } else {
            $newId = $db->insert($table, ['name' => $name, 'slug' => $slug, 'description' => $description ?: null, 'status' => $status]);
            AuditService::log("$table.create", $table, $newId);
        }
        Session::instance()->flash('success', 'Saved.');
        $this->redirect(admin_url($table));
    }

    private function upsertTag(?int $id, Request $request): void
    {
        $name = trim((string) $request->post('name', ''));
        if ($name === '') { Session::instance()->flash('error', 'Name required.'); $this->back(admin_url('tags')); return; }
        $normalized = mb_strtolower(trim($name));
        $slug = slugify($request->post('slug') ?: $name);
        $status = in_array($request->post('status'), ['active','hidden','disabled','merged'], true) ? $request->post('status') : 'active';
        $db = Database::getInstance();
        $existing = $db->fetch('SELECT id FROM tags WHERE (slug = :s OR normalized_name = :n) AND (:eid IS NULL OR id <> :eid) LIMIT 1', [':s' => $slug, ':n' => $normalized, ':eid' => $id]);
        if ($existing) $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        $payload = ['name' => $name, 'slug' => $slug, 'normalized_name' => $normalized, 'status' => $status];
        if ($id) { $db->update('tags', $payload, 'id = :id', [':id' => $id]); AuditService::log('tag.update', 'tag', $id); }
        else     { $newId = $db->insert('tags', $payload); AuditService::log('tag.create', 'tag', $newId); }
        Session::instance()->flash('success', 'Saved.');
        $this->redirect(admin_url('tags'));
    }

    private function upsertEntity(string $table, ?int $id, Request $request, array $fields): void
    {
        $name = trim((string) $request->post('name', ''));
        if ($name === '') { Session::instance()->flash('error', 'Name required.'); $this->back(); return; }
        $slug = slugify($request->post('slug') ?: $name);
        $status = in_array($request->post('status'), ['active','hidden','disabled'], true) ? $request->post('status') : 'active';
        $payload = ['name' => $name, 'slug' => $slug, 'status' => $status];
        foreach ($fields as $f) {
            if ($f === 'name') continue;
            $val = $request->post($f);
            if ($val !== null && $val !== '') $payload[$f] = is_string($val) ? trim($val) : $val;
            elseif ($id) $payload[$f] = null;
        }
        $db = Database::getInstance();
        $existing = $db->fetch("SELECT id FROM $table WHERE slug = :s AND (:eid IS NULL OR id <> :eid)", [':s' => $slug, ':eid' => $id]);
        if ($existing) { $payload['slug'] .= '-' . substr(bin2hex(random_bytes(2)), 0, 4); }
        if ($id) { $db->update($table, $payload, 'id = :id', [':id' => $id]); AuditService::log("$table.update", $table, $id); }
        else     { $newId = $db->insert($table, $payload); AuditService::log("$table.create", $table, $newId); }
        Session::instance()->flash('success', 'Saved.');
        $route = match ($table) { 'series_collections' => 'series', default => $table };
        $this->redirect(admin_url($route));
    }

    private function deleteSimple(string $table, int $id, string $route): void
    {
        $db = Database::getInstance();
        $row = $db->fetch("SELECT * FROM $table WHERE id = :id", [':id' => $id]);
        if (!$row) { Response::notFound(); return; }
        $db->delete($table, 'id = :id', [':id' => $id]);
        AuditService::log("$table.delete", $table, $id, $row);
        Session::instance()->flash('success', 'Deleted.');
        $this->redirect(admin_url($route));
    }
}
