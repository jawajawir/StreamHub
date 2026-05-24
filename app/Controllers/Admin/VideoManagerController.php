<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AuditService;
use App\Services\SecurityService;

class VideoManagerController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::getInstance();
        $q       = trim((string) $request->query('q', ''));
        $source  = (string) $request->query('source', '');
        $status  = (string) $request->query('status', '');
        $access  = (string) $request->query('access', '');
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = 25;

        $where = ['deleted_at IS NULL']; $bind = [];
        if ($q !== '')     { $where[] = '(title LIKE :q OR slug LIKE :q OR internal_code LIKE :q)'; $bind[':q'] = '%' . $q . '%'; }
        if (in_array($source, ['doodstream','direct_mysql'], true))                          { $where[] = 'source_type = :s'; $bind[':s'] = $source; }
        if (in_array($status, ['draft','pending_review','published','scheduled','rejected','disabled','broken','archived'], true)) { $where[] = 'lifecycle_status = :st'; $bind[':st'] = $status; }
        if (in_array($access, ['public','registered','premium','vip'], true))                { $where[] = 'access_level = :a'; $bind[':a'] = $access; }

        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $total = (int) $db->fetchValue('SELECT COUNT(*) FROM content_items ' . $whereSql, $bind);
        $rows  = $db->fetchAll(
            'SELECT id, title, slug, source_type, lifecycle_status, access_level, view_count, like_count, thumbnail_url, created_at, published_at
             FROM content_items ' . $whereSql . ' ORDER BY created_at DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage),
            $bind
        );
        $this->view('admin.pages.videos.index', [
            'title' => 'Video Manager',
            'rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
            'pages' => (int) ceil($total / $perPage),
            'q' => $q, 'source' => $source, 'status' => $status, 'access' => $access,
        ]);
    }

    public function create(Request $request): void
    {
        $this->renderForm(null);
    }

    public function edit(Request $request): void
    {
        $id = (int) $request->param('id');
        $row = Database::getInstance()->fetch('SELECT * FROM content_items WHERE id = :id LIMIT 1', [':id' => $id]);
        if (!$row) { Response::notFound(); return; }
        $this->renderForm($row);
    }

    private function renderForm(?array $row): void
    {
        $db = Database::getInstance();
        $source = $row ? $db->fetch('SELECT * FROM content_sources WHERE content_id = :c LIMIT 1', [':c' => $row['id']]) : null;
        $cats = $db->fetchAll('SELECT id, name FROM categories WHERE status = "active" ORDER BY name');
        $tags = $db->fetchAll('SELECT id, name FROM tags WHERE status = "active" ORDER BY name LIMIT 500');
        $performers = $db->fetchAll('SELECT id, name FROM performers WHERE status = "active" ORDER BY name LIMIT 1000');
        $studios = $db->fetchAll('SELECT id, name FROM studios WHERE status = "active" ORDER BY name');
        $series  = $db->fetchAll('SELECT id, name FROM series_collections WHERE status = "active" ORDER BY name');
        $selectedCats = $row ? array_column($db->fetchAll('SELECT category_id FROM content_categories WHERE content_id = :c', [':c' => $row['id']]), 'category_id') : [];
        $selectedTags = $row ? array_column($db->fetchAll('SELECT tag_id FROM content_tags WHERE content_id = :c', [':c' => $row['id']]), 'tag_id') : [];
        $selectedPerformers = $row ? array_column($db->fetchAll('SELECT performer_id FROM content_performers WHERE content_id = :c', [':c' => $row['id']]), 'performer_id') : [];

        $this->view('admin.pages.videos.form', [
            'title' => $row ? 'Edit content · ' . $row['title'] : 'Add content',
            'row' => $row, 'source' => $source,
            'cats' => $cats, 'tags' => $tags, 'performers' => $performers, 'studios' => $studios, 'series' => $series,
            'selectedCats' => $selectedCats, 'selectedTags' => $selectedTags, 'selectedPerformers' => $selectedPerformers,
            'iframeAllowlist' => SecurityService::iframeAllowlist(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->validateCsrf($request);
        $this->save($request, null);
    }

    public function update(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $row = Database::getInstance()->fetch('SELECT * FROM content_items WHERE id = :id LIMIT 1', [':id' => $id]);
        if (!$row) { Response::notFound(); return; }
        $this->save($request, $row);
    }

    private function save(Request $request, ?array $existing): void
    {
        $db = Database::getInstance();
        $admin = Auth::admin();

        $sourceType = $request->post('source_type') === 'doodstream' ? 'doodstream' : 'direct_mysql';
        $title = trim((string) $request->post('title', ''));
        $slug  = trim((string) $request->post('slug', ''));
        $description = trim((string) $request->post('description', ''));
        $releaseDate = trim((string) $request->post('release_date', ''));
        $runtime = (int) $request->post('runtime_seconds', 0);
        $thumbnail = trim((string) $request->post('thumbnail_url', ''));
        $lifecycle = (string) $request->post('lifecycle_status', 'draft');
        $access    = (string) $request->post('access_level', 'public');
        $visibility= (string) $request->post('visibility_level', 'visible');
        $featured  = $request->post('is_featured') ? 1 : 0;
        $allowComments = $request->post('allow_comments') ? 1 : 0;
        $studioId  = (int) $request->post('studio_id', 0) ?: null;
        $seriesId  = (int) $request->post('series_id', 0) ?: null;
        $scheduledAt = trim((string) $request->post('scheduled_at', ''));
        $iframeUrl = trim((string) $request->post('iframe_url', ''));
        $fileCode  = trim((string) $request->post('doodstream_file_code', ''));
        $directUrl = trim((string) $request->post('direct_url', ''));
        $internalCode = trim((string) $request->post('internal_code', '')) ?: null;

        $errors = [];
        if ($title === '') $errors['title'] = 'Required';
        if (mb_strlen($title) > 255) $errors['title'] = 'Too long';
        if (!in_array($lifecycle, ['draft','pending_review','published','scheduled','rejected','disabled','broken','archived'], true)) $errors['lifecycle_status'] = 'Invalid';
        if (!in_array($access, ['public','registered','premium','vip'], true)) $errors['access_level'] = 'Invalid';
        if (!in_array($visibility, ['visible','hidden','unlisted'], true)) $errors['visibility_level'] = 'Invalid';

        // Validate iframe / Doodstream URL
        $validatedIframe = null;
        if ($sourceType === 'doodstream') {
            $iframeCandidate = $iframeUrl !== '' ? $iframeUrl : ($fileCode !== '' ? rtrim((string) (config('doodstream.embed_base') ?? 'https://dood.li/e/'), '/') . '/' . $fileCode : '');
            if ($iframeCandidate === '') {
                $errors['iframe_url'] = 'Provide an iframe URL or a Doodstream file_code.';
            } else {
                $validatedIframe = SecurityService::validateIframeUrl($iframeCandidate);
                if (!$validatedIframe) {
                    $errors['iframe_url'] = 'iframe URL is not in the allowlist.';
                    SecurityService::logEvent('invalid_embed_domain', 'warning', $request, ['url' => $iframeCandidate]);
                }
            }
        } else {
            // Direct MySQL: require either a direct URL or assume admin will attach later (warn only)
            if ($directUrl !== '' && !filter_var($directUrl, FILTER_VALIDATE_URL)) {
                $errors['direct_url'] = 'Invalid URL.';
            }
        }

        // Slug
        if ($slug === '') $slug = slugify($title);
        $slug = slugify($slug);
        $slug = $this->resolveUniqueSlug($db, $slug, $existing['id'] ?? null);

        if ($errors) {
            $this->flashErrors($errors, $request->post());
            $this->redirect($existing ? admin_url('videos/' . $existing['id'] . '/edit') : admin_url('videos/create'));
            return;
        }

        $payload = [
            'title' => $title, 'slug' => $slug, 'description' => $description ?: null,
            'release_date' => $releaseDate ?: null, 'runtime_seconds' => $runtime ?: null,
            'thumbnail_url' => $thumbnail ?: null,
            'lifecycle_status' => $lifecycle, 'access_level' => $access, 'visibility_level' => $visibility,
            'is_featured' => $featured, 'allow_comments' => $allowComments,
            'studio_id' => $studioId, 'series_id' => $seriesId,
            'scheduled_at' => $scheduledAt ? date('Y-m-d H:i:s', strtotime($scheduledAt)) : null,
            'internal_code' => $internalCode,
            'source_type' => $sourceType,
            'updated_by_admin_id' => $admin['id'],
        ];
        if ($lifecycle === 'published' && empty($existing['published_at'])) {
            $payload['published_at'] = date('Y-m-d H:i:s');
        }

        $db->transaction(function () use ($db, $existing, $payload, $request, $sourceType, $validatedIframe, $fileCode, $directUrl, $admin, $slug) {
            if ($existing) {
                if ($existing['slug'] !== $slug) {
                    $db->insert('slug_history', ['entity_type' => 'content', 'entity_id' => $existing['id'], 'old_slug' => $existing['slug'], 'new_slug' => $slug]);
                }
                $db->update('content_items', $payload, 'id = :id', [':id' => $existing['id']]);
                $contentId = (int) $existing['id'];
            } else {
                $payload['created_by_admin_id'] = $admin['id'];
                $contentId = $db->insert('content_items', $payload);
            }

            // Upsert content_sources
            $existingSrc = $db->fetch('SELECT id FROM content_sources WHERE content_id = :c', [':c' => $contentId]);
            $srcRow = [
                'content_id' => $contentId,
                'source_type' => $sourceType,
                'doodstream_file_code' => $sourceType === 'doodstream' ? ($fileCode ?: null) : null,
                'doodstream_embed_url' => $sourceType === 'doodstream' ? $validatedIframe : null,
                'iframe_url'           => $sourceType === 'doodstream' ? $validatedIframe : null,
                'last_synced_at'       => $sourceType === 'doodstream' ? date('Y-m-d H:i:s') : null,
            ];
            if ($sourceType === 'direct_mysql') {
                if ($directUrl !== '') {
                    // Persist as a media_assets row + reference it from content_sources.
                    $existingAsset = $db->fetch('SELECT id FROM media_assets WHERE content_id = :c AND public_url = :u', [':c' => $contentId, ':u' => $directUrl]);
                    if ($existingAsset) {
                        $assetId = (int) $existingAsset['id'];
                    } else {
                        $assetId = $db->insert('media_assets', [
                            'content_id' => $contentId,
                            'asset_type' => str_ends_with(strtolower($directUrl), '.m3u8') ? 'video_hls' : 'video_mp4',
                            'public_url' => $directUrl,
                            'mime_type'  => str_ends_with(strtolower($directUrl), '.m3u8') ? 'application/x-mpegURL' : 'video/mp4',
                            'status'     => 'active',
                        ]);
                    }
                    $srcRow['direct_media_asset_id'] = $assetId;
                    $srcRow['iframe_url'] = $directUrl;
                }
            }
            if ($existingSrc) {
                $db->update('content_sources', $srcRow, 'id = :id', [':id' => $existingSrc['id']]);
            } else {
                $db->insert('content_sources', $srcRow);
            }

            // Pivot tables
            $cats = (array) ($request->post('categories') ?? []);
            $tags = (array) ($request->post('tags') ?? []);
            $perfs= (array) ($request->post('performers') ?? []);
            $db->delete('content_categories', 'content_id = :c', [':c' => $contentId]);
            $db->delete('content_tags',       'content_id = :c', [':c' => $contentId]);
            $db->delete('content_performers', 'content_id = :c', [':c' => $contentId]);
            foreach ($cats as $cid) { $cid = (int) $cid; if ($cid) $db->query('INSERT IGNORE INTO content_categories (content_id, category_id) VALUES (:c, :x)', [':c' => $contentId, ':x' => $cid]); }
            foreach ($tags as $tid) { $tid = (int) $tid; if ($tid) $db->query('INSERT IGNORE INTO content_tags (content_id, tag_id) VALUES (:c, :x)', [':c' => $contentId, ':x' => $tid]); }
            foreach ($perfs as $pid){ $pid = (int) $pid; if ($pid) $db->query('INSERT IGNORE INTO content_performers (content_id, performer_id) VALUES (:c, :x)', [':c' => $contentId, ':x' => $pid]); }

            // Update count caches on tags/categories/performers/studios
            $this->refreshCounts($db, array_merge($cats, $tags, $perfs), $payload['studio_id'] ?? null);

            return $contentId;
        });

        AuditService::log($existing ? 'content.update' : 'content.create', 'content', (int) ($existing['id'] ?? $db->pdo()->lastInsertId()));
        Session::instance()->flash('success', 'Content saved.');
        $this->redirect(admin_url('videos'));
    }

    private function refreshCounts(\App\Core\Database $db, array $entityIds, $studioId): void
    {
        // Recompute counts for categories/tags/performers/studio touched.
        $db->query('UPDATE categories c SET content_count = (SELECT COUNT(*) FROM content_categories cc JOIN content_items ci ON ci.id = cc.content_id WHERE cc.category_id = c.id AND ci.lifecycle_status = "published")');
        $db->query('UPDATE tags t SET content_count = (SELECT COUNT(*) FROM content_tags ct JOIN content_items ci ON ci.id = ct.content_id WHERE ct.tag_id = t.id AND ci.lifecycle_status = "published")');
        $db->query('UPDATE performers p SET content_count = (SELECT COUNT(*) FROM content_performers cp JOIN content_items ci ON ci.id = cp.content_id WHERE cp.performer_id = p.id AND ci.lifecycle_status = "published")');
        $db->query('UPDATE studios s SET content_count = (SELECT COUNT(*) FROM content_items ci WHERE ci.studio_id = s.id AND ci.lifecycle_status = "published")');
    }

    private function resolveUniqueSlug(\App\Core\Database $db, string $slug, ?int $excludeId): string
    {
        $base = $slug; $i = 2;
        while (true) {
            $row = $db->fetch('SELECT id FROM content_items WHERE slug = :s AND (:eid IS NULL OR id <> :eid) LIMIT 1', [':s' => $slug, ':eid' => $excludeId]);
            if (!$row) return $slug;
            $slug = $base . '-' . $i++;
        }
    }

    public function updateStatus(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $status = (string) $request->post('status', '');
        if (!in_array($status, ['draft','pending_review','published','scheduled','rejected','disabled','broken','archived'], true)) {
            Session::instance()->flash('error', 'Invalid status.');
            $this->redirect(admin_url('videos')); return;
        }
        $db = Database::getInstance();
        $row = $db->fetch('SELECT * FROM content_items WHERE id = :id LIMIT 1', [':id' => $id]);
        if (!$row) { Response::notFound(); return; }
        $update = ['lifecycle_status' => $status];
        if ($status === 'published' && empty($row['published_at'])) $update['published_at'] = date('Y-m-d H:i:s');
        $db->update('content_items', $update, 'id = :id', [':id' => $id]);
        AuditService::log('content.status_update', 'content', $id, ['from' => $row['lifecycle_status']], ['to' => $status]);
        Session::instance()->flash('success', 'Status updated.');
        $this->back(admin_url('videos'));
    }

    public function deleteOrArchive(Request $request): void
    {
        $this->validateCsrf($request);
        $id = (int) $request->param('id');
        $action = (string) $request->post('action', 'archive');
        $db = Database::getInstance();
        $row = $db->fetch('SELECT * FROM content_items WHERE id = :id LIMIT 1', [':id' => $id]);
        if (!$row) { Response::notFound(); return; }
        if ($action === 'delete') {
            $db->update('content_items', ['deleted_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $id]);
            AuditService::log('content.soft_delete', 'content', $id);
            Session::instance()->flash('success', 'Content soft-deleted.');
        } else {
            $db->update('content_items', ['lifecycle_status' => 'archived'], 'id = :id', [':id' => $id]);
            AuditService::log('content.archive', 'content', $id);
            Session::instance()->flash('success', 'Content archived.');
        }
        $this->redirect(admin_url('videos'));
    }
}
