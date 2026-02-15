<?php
declare(strict_types=1);
namespace CompanyWebsite\Models;
use PDO;

class Category {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    
    public function getAll(int $websiteId): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM kdd_website_categories
                WHERE website_id = ?
                ORDER BY sort_order ASC, name ASC
            ");
            $stmt->execute([$websiteId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error in Category::getAll: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function create(array $data, int $websiteId, int $authorityId): int {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO kdd_website_categories (website_id, name, slug, description, sort_order, authority_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $websiteId, $data['name'], $data['slug'] ?? '',
                $data['description'] ?? '', $data['sort_order'] ?? 0, $authorityId
            ]);
            return (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error in Category::create: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function update(int $categoryId, array $data, int $authorityId): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE kdd_website_categories SET name = ?, slug = ?, description = ?, sort_order = ?
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([
                $data['name'], $data['slug'] ?? '', $data['description'] ?? '',
                $data['sort_order'] ?? 0, $categoryId, $authorityId
            ]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error in Category::update: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function delete(int $categoryId, int $authorityId): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM kdd_website_categories WHERE id = ? AND authority_id = ?");
            $stmt->execute([$categoryId, $authorityId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error in Category::delete: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateOrder(array $categoryIds, int $authorityId): bool {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE kdd_website_categories SET sort_order = ? WHERE id = ? AND authority_id = ?");
            foreach ($categoryIds as $index => $categoryId) {
                $stmt->execute([$index, $categoryId, $authorityId]);
            }
            $this->pdo->commit();
            return true;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error in Category::updateOrder: " . $e->getMessage());
            throw $e;
        }
    }
}
