<?php
declare(strict_types=1);
namespace CompanyWebsite\Models;
use PDO;

class Media {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    
    public function getAll(int $websiteId): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM kdd_website_media
                WHERE website_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$websiteId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error in Media::getAll: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function create(array $data, int $websiteId, int $authorityId): int {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO kdd_website_media (website_id, filename, file_path, file_type, 
                    file_size, title, alt_text, authority_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $websiteId, $data['filename'], $data['file_path'], $data['file_type'],
                $data['file_size'], $data['title'] ?? '', $data['alt_text'] ?? '', $authorityId
            ]);
            return (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error in Media::create: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function update(int $mediaId, array $data, int $authorityId): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE kdd_website_media SET title = ?, alt_text = ?
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([$data['title'] ?? '', $data['alt_text'] ?? '', $mediaId, $authorityId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error in Media::update: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function delete(int $mediaId, int $authorityId): ?array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM kdd_website_media WHERE id = ? AND authority_id = ?");
            $stmt->execute([$mediaId, $authorityId]);
            $media = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$media) return null;
            
            $stmt = $this->pdo->prepare("DELETE FROM kdd_website_media WHERE id = ? AND authority_id = ?");
            $stmt->execute([$mediaId, $authorityId]);
            return $media;
        } catch (\PDOException $e) {
            error_log("Error in Media::delete: " . $e->getMessage());
            throw $e;
        }
    }
}
