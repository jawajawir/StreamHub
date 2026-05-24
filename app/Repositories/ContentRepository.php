<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class ContentRepository
{
    public function findBySlug(string $slug): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT * FROM content_items WHERE slug = :s AND deleted_at IS NULL LIMIT 1',
            [':s' => $slug]
        );
    }

    public function findById(int $id): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT * FROM content_items WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            [':id' => $id]
        );
    }

    public function source(int $contentId): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT * FROM content_sources WHERE content_id = :c LIMIT 1',
            [':c' => $contentId]
        );
    }

    /** Public listing, only published + visible. */
    public function listPublic(array $opts = []): array
    {
        $page    = max(1, (int) ($opts['page'] ?? 1));
        $perPage = min(60, max(1, (int) ($opts['per_page'] ?? 24)));
        $offset  = ($page - 1) * $perPage;
        $sortMap = [
            'latest'      => 'COALESCE(published_at, created_at) DESC',
            'most_viewed' => 'view_count DESC, COALESCE(published_at, created_at) DESC',
            'most_liked'  => 'like_count DESC, COALESCE(published_at, created_at) DESC',
            'trending'    => 'trending_score DESC, view_count DESC',
            'oldest'      => 'COALESCE(published_at, created_at) ASC',
        ];
        $orderBy = $sortMap[$opts['sort'] ?? 'latest'] ?? $sortMap['latest'];
        $where = ['lifecycle_status = "published"', 'visibility_level = "visible"', 'deleted_at IS NULL'];
        $bind  = [];
        if (!empty($opts['category_id'])) {
            $where[] = 'id IN (SELECT content_id FROM content_categories WHERE category_id = :cat)';
            $bind[':cat'] = (int) $opts['category_id'];
        }
        if (!empty($opts['tag_id'])) {
            $where[] = 'id IN (SELECT content_id FROM content_tags WHERE tag_id = :tg)';
            $bind[':tg'] = (int) $opts['tag_id'];
        }
        if (!empty($opts['performer_id'])) {
            $where[] = 'id IN (SELECT content_id FROM content_performers WHERE performer_id = :pf)';
            $bind[':pf'] = (int) $opts['performer_id'];
        }
        if (!empty($opts['studio_id'])) {
            $where[] = 'studio_id = :st';
            $bind[':st'] = (int) $opts['studio_id'];
        }
        if (!empty($opts['series_id'])) {
            $where[] = 'series_id = :se';
            $bind[':se'] = (int) $opts['series_id'];
        }
        if (!empty($opts['search'])) {
            $where[] = 'MATCH(title, description) AGAINST(:q IN NATURAL LANGUAGE MODE)';
            $bind[':q'] = $opts['search'];
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $db = Database::getInstance();
        $total = (int) $db->fetchValue('SELECT COUNT(*) FROM content_items ' . $whereSql, $bind);
        $rows  = $db->fetchAll(
            'SELECT id, slug, title, thumbnail_url, runtime_seconds, view_count, like_count, dislike_count, access_level, published_at
             FROM content_items ' . $whereSql . ' ORDER BY ' . $orderBy .
            ' LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $bind
        );
        return [
            'items'     => $rows,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'pages'     => (int) ceil($total / $perPage),
        ];
    }

    public function related(array $content, int $limit = 12): array
    {
        $bind = [':id' => $content['id']];
        $studio = $content['studio_id'] ?? null;
        $series = $content['series_id'] ?? null;
        $sql = 'SELECT id, slug, title, thumbnail_url, runtime_seconds, view_count
                FROM content_items
                WHERE id <> :id AND lifecycle_status = "published" AND visibility_level = "visible" AND deleted_at IS NULL';
        if ($studio) {
            $sql .= ' AND (studio_id = :st OR id IN (SELECT content_id FROM content_tags WHERE tag_id IN (SELECT tag_id FROM content_tags WHERE content_id = :id)))';
            $bind[':st'] = $studio;
        } else {
            $sql .= ' AND id IN (SELECT content_id FROM content_tags WHERE tag_id IN (SELECT tag_id FROM content_tags WHERE content_id = :id))';
        }
        $sql .= ' ORDER BY view_count DESC LIMIT ' . max(1, min(48, $limit));
        return Database::getInstance()->fetchAll($sql, $bind);
    }

    public function tagsOf(int $contentId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT t.id, t.name, t.slug FROM content_tags ct JOIN tags t ON t.id = ct.tag_id WHERE ct.content_id = :c ORDER BY t.name',
            [':c' => $contentId]
        );
    }

    public function categoriesOf(int $contentId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT c.id, c.name, c.slug FROM content_categories cc JOIN categories c ON c.id = cc.category_id WHERE cc.content_id = :c ORDER BY c.name',
            [':c' => $contentId]
        );
    }

    public function performersOf(int $contentId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT p.id, p.name, p.slug, p.avatar_url FROM content_performers cp JOIN performers p ON p.id = cp.performer_id WHERE cp.content_id = :c ORDER BY p.name',
            [':c' => $contentId]
        );
    }

    public function incrementView(int $contentId): void
    {
        try {
            Database::getInstance()->query('UPDATE content_items SET view_count = view_count + 1 WHERE id = :id', [':id' => $contentId]);
        } catch (\Throwable $e) {}
    }
}
