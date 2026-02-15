<?php
/**
 * Page Model
 * Handles database operations for website pages
 */

declare(strict_types=1);

namespace CompanyWebsite\Models;

use PDO;
use PDOException;

class Page
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(int $websiteId, bool $publishedOnly = false): array
    {
        try {
            if ($publishedOnly) {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM kdd_website_pages
                    WHERE website_id = ? AND is_published = 1
                    ORDER BY created_at DESC
                ");
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT * FROM kdd_website_pages
                    WHERE website_id = ?
                    ORDER BY created_at DESC
                ");
            }
            $stmt->execute([$websiteId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Page::getAll: " . $e->getMessage());
            throw $e;
        }
    }

    public function getById(int $pageId, int $authorityId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM kdd_website_pages
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([$pageId, $authorityId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error in Page::getById: " . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $data, int $websiteId, int $authorityId): int
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO kdd_website_pages (
                    website_id, title, slug, content, 
                    is_published, use_blocks, authority_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $websiteId,
                $data['title'],
                $data['slug'] ?? '',
                $data['content'] ?? '',
                isset($data['is_published']) ? (int)$data['is_published'] : 1,
                isset($data['use_blocks']) ? (int)$data['use_blocks'] : 0,
                $authorityId
            ]);
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error in Page::create: " . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $pageId, array $data, int $authorityId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE kdd_website_pages SET
                title = ?,
                slug = ?,
                content = ?,
                is_published = ?,
                use_blocks = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([
                $data['title'],
                $data['slug'] ?? '',
                $data['content'] ?? '',
                isset($data['is_published']) ? (int)$data['is_published'] : 1,
                isset($data['use_blocks']) ? (int)$data['use_blocks'] : 0,
                $pageId,
                $authorityId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error in Page::update: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $pageId, int $authorityId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM kdd_website_pages
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([$pageId, $authorityId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error in Page::delete: " . $e->getMessage());
            throw $e;
        }
    }

    public function getWebsiteIdByPageId(int $pageId, int $authorityId): ?int
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT website_id FROM kdd_website_pages 
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([$pageId, $authorityId]);
            $result = $stmt->fetchColumn();
            return $result ? (int)$result : null;
        } catch (PDOException $e) {
            error_log("Error in Page::getWebsiteIdByPageId: " . $e->getMessage());
            throw $e;
        }
    }
}
