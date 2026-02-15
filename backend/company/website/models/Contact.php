<?php
declare(strict_types=1);
namespace CompanyWebsite\Models;
use PDO;

class Contact {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    
    public function getAll(int $websiteId, int $authorityId): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM kdd_website_contact_submissions
                WHERE website_id = ? AND authority_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$websiteId, $authorityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error in Contact::getAll: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function create(array $data, int $websiteId): int {
        try {
            // Get authority_id from website
            $stmt = $this->pdo->prepare("SELECT authority_id FROM kdd_website_config WHERE id = ?");
            $stmt->execute([$websiteId]);
            $authorityId = $stmt->fetchColumn();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO kdd_website_contact_submissions (website_id, name, email, subject, 
                    message, form_data, authority_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $websiteId, $data['name'] ?? '', $data['email'] ?? '', $data['subject'] ?? '',
                $data['message'] ?? '', isset($data['form_data']) ? json_encode($data['form_data']) : null,
                $authorityId
            ]);
            return (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error in Contact::create: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function markAsRead(int $submissionId, int $authorityId): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE kdd_website_contact_submissions SET is_read = 1
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([$submissionId, $authorityId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error in Contact::markAsRead: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function delete(int $submissionId, int $authorityId): bool {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM kdd_website_contact_submissions WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([$submissionId, $authorityId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Error in Contact::delete: " . $e->getMessage());
            throw $e;
        }
    }
}
