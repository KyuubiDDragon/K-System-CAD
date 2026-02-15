<?php
declare(strict_types=1);
namespace CompanyWebsite\Models;
use PDO;

class Navigation {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    
    public function getAll(int $websiteId): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM kdd_website_navigation
                WHERE website_id = ?
                ORDER BY sort_order ASC
            ");
            $stmt->execute([$websiteId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error in Navigation::getAll: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function create(array $data, int $websiteId, int $authorityId): int {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO kdd_website_navigation (website_id, label, link_type, link_target, 
                    parent_id, sort_order, authority_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $websiteId, $data['label'], $data['link_type'], $data['link_target'],
                $data['parent_id'] ?? null, $data['sort_order'] ?? 0, $authorityId
            ]);
            return (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error in Navigation::create: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function update(int $navId, array $data, int $authorityId): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE kdd_website_navigation SET label = ?, link_type = ?, link_target = ?, 
                    parent_id = ?, sort_order = ?
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([
                $data['label'], $data['link_type'], $data['link_target'],
                $data['parent_id'] ?? null, $data['sort_order'] ?? 0, $navId, $authorityId
            ]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error in Navigation::update: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function delete(int $navId, int $authorityId): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM kdd_website_navigation WHERE id = ? AND authority_id = ?");
            $stmt->execute([$navId, $authorityId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error in Navigation::delete: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateOrder(array $navIds, int $authorityId): bool {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE kdd_website_navigation SET sort_order = ? WHERE id = ? AND authority_id = ?");
            foreach ($navIds as $index => $navId) {
                $stmt->execute([$index, $navId, $authorityId]);
            }
            $this->pdo->commit();
            return true;
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error in Navigation::updateOrder: " . $e->getMessage());
            throw $e;
        }
    }
}
