<?php
declare(strict_types=1);
namespace CompanyWebsite\Models;
use PDO;

class Post {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    
    public function getAll(int $websiteId, ?int $categoryId = null, bool $publishedOnly = false): array {
        try {
            $sql = "SELECT * FROM kdd_website_posts WHERE website_id = ?";
            $params = [$websiteId];
            if ($categoryId) { $sql .= " AND category_id = ?"; $params[] = $categoryId; }
            if ($publishedOnly) { $sql .= " AND is_published = 1"; }
            $sql .= " ORDER BY sort_order ASC, created_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error in Post::getAll: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function create(array $data, int $websiteId, int $authorityId): int {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO kdd_website_posts (website_id, category_id, title, slug, content, 
                    excerpt, featured_image, is_published, sort_order, authority_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $websiteId, $data['category_id'] ?? null, $data['title'], $data['slug'] ?? '',
                $data['content'] ?? '', $data['excerpt'] ?? '', $data['featured_image'] ?? '',
                isset($data['is_published']) ? (int)$data['is_published'] : 1,
                $data['sort_order'] ?? 0, $authorityId
            ]);
            return (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error in Post::create: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function update(int $postId, array $data, int $authorityId): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE kdd_website_posts SET category_id = ?, title = ?, slug = ?, content = ?,
                    excerpt = ?, featured_image = ?, is_published = ?, sort_order = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([
                $data['category_id'] ?? null, $data['title'], $data['slug'] ?? '',
                $data['content'] ?? '', $data['excerpt'] ?? '', $data['featured_image'] ?? '',
                isset($data['is_published']) ? (int)$data['is_published'] : 1,
                $data['sort_order'] ?? 0, $postId, $authorityId
            ]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error in Post::update: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function delete(int $postId, int $authorityId): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM kdd_website_posts WHERE id = ? AND authority_id = ?");
            $stmt->execute([$postId, $authorityId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error in Post::delete: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateOrder(array $postIds, int $authorityId): bool {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE kdd_website_posts SET sort_order = ? WHERE id = ? AND authority_id = ?");
            foreach ($postIds as $index => $postId) {
                $stmt->execute([$index, $postId, $authorityId]);
            }
            $this->pdo->commit();
            return true;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error in Post::updateOrder: " . $e->getMessage());
            throw $e;
        }
    }
}
