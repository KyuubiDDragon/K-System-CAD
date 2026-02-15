<?php
/**
 * Website Model
 * Handles database operations for website configuration
 */

declare(strict_types=1);

namespace CompanyWebsite\Models;

use PDO;
use PDOException;

class Website
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all websites for an authority
     */
    public function getAll(int $authorityId): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, site_name, site_description, is_active,
                       maintenance_mode, logo, created_at, updated_at
                FROM kdd_website_config
                WHERE authority_id = ?
                ORDER BY site_name ASC
            ");
            $stmt->execute([$authorityId]);
            $websites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($websites as &$website) {
                $website['is_active'] = (bool)$website['is_active'];
                $website['maintenance_mode'] = (bool)$website['maintenance_mode'];
            }

            return $websites;
        } catch (PDOException $e) {
            error_log("Error in Website::getAll: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get website by ID
     */
    public function getById(int $websiteId, int $authorityId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT *
                FROM kdd_website_config
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([$websiteId, $authorityId]);
            $website = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$website) {
                return null;
            }

            // Convert boolean fields
            $website['is_active'] = (bool)$website['is_active'];
            $website['maintenance_mode'] = (bool)$website['maintenance_mode'];
            $website['show_contact_form'] = (bool)$website['show_contact_form'];
            $website['use_custom_colors'] = (bool)$website['use_custom_colors'];

            // Decode JSON fields
            if (!empty($website['social_links'])) {
                $website['social_links'] = json_decode($website['social_links'], true) ?? [];
            } else {
                $website['social_links'] = [];
            }

            if (!empty($website['contact_form_fields'])) {
                $website['contact_form_fields'] = json_decode($website['contact_form_fields'], true) ?? [];
            } else {
                $website['contact_form_fields'] = [];
            }

            if (!empty($website['custom_colors'])) {
                $website['custom_colors'] = json_decode($website['custom_colors'], true) ?? null;
            }

            return $website;
        } catch (PDOException $e) {
            error_log("Error in Website::getById: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new website
     */
    public function create(array $data, int $authorityId): int
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO kdd_website_config (
                    authority_id, site_name, site_description, is_active,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->execute([
                $authorityId,
                $data['site_name'] ?? 'New Website',
                $data['site_description'] ?? '',
                $data['is_active'] ?? 1
            ]);

            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error in Website::create: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update website configuration
     */
    public function update(int $websiteId, array $data, int $authorityId): bool
    {
        try {
            // Prepare JSON fields
            if (isset($data['social_links']) && is_array($data['social_links'])) {
                $data['social_links'] = json_encode($data['social_links']);
            }

            if (isset($data['contact_form_fields']) && is_array($data['contact_form_fields'])) {
                $data['contact_form_fields'] = json_encode($data['contact_form_fields']);
            }

            if (isset($data['customColors']) && is_array($data['customColors'])) {
                $data['customColors'] = json_encode($data['customColors']);
            }

            $stmt = $this->pdo->prepare("
                UPDATE kdd_website_config SET
                    site_name = ?,
                    site_slogan = ?,
                    site_description = ?,
                    primary_color = ?,
                    secondary_color = ?,
                    background_color = ?,
                    contact_email = ?,
                    contact_phone = ?,
                    footer_text = ?,
                    is_active = ?,
                    maintenance_mode = ?,
                    maintenance_message = ?,
                    layout_template = ?,
                    navbar_name = ?,
                    show_contact_form = ?,
                    contact_form_fields = ?,
                    social_links = ?,
                    logo = ?,
                    banner = ?,
                    background_image = ?,
                    hero_image = ?,
                    cta_text = ?,
                    cta_target_type = ?,
                    cta_target_id = ?,
                    selected_scheme = ?,
                    use_custom_colors = ?,
                    custom_colors = ?,
                    updated_at = NOW()
                WHERE id = ? AND authority_id = ?
            ");

            $stmt->execute([
                $data['site_name'] ?? '',
                $data['site_slogan'] ?? '',
                $data['site_description'] ?? '',
                $data['primary_color'] ?? '',
                $data['secondary_color'] ?? '',
                $data['background_color'] ?? '',
                $data['contact_email'] ?? '',
                $data['contact_phone'] ?? '',
                $data['footer_text'] ?? '',
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                isset($data['maintenance_mode']) ? (int)$data['maintenance_mode'] : 0,
                $data['maintenance_message'] ?? '',
                $data['layout_template'] ?? 'default',
                $data['navbar_name'] ?? '',
                isset($data['show_contact_form']) ? (int)$data['show_contact_form'] : 0,
                $data['contact_form_fields'] ?? '[]',
                $data['social_links'] ?? '[]',
                $data['logo'] ?? '',
                $data['banner'] ?? '',
                $data['background_image'] ?? '',
                $data['hero_image'] ?? '',
                $data['cta_text'] ?? '',
                $data['cta_target_type'] ?? '',
                $data['cta_target_id'] ?? '',
                $data['selected_scheme'] ?? 'default',
                isset($data['use_custom_colors']) ? (int)$data['use_custom_colors'] : 0,
                $data['customColors'] ?? null,
                $websiteId,
                $authorityId
            ]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error in Website::update: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a website
     */
    public function delete(int $websiteId, int $authorityId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM kdd_website_config
                WHERE id = ? AND authority_id = ?
            ");
            $stmt->execute([$websiteId, $authorityId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error in Website::delete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all public websites (no authority filter)
     */
    public function getAllPublic(): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    wc.id,
                    wc.site_name,
                    wc.site_description,
                    wc.logo,
                    wc.site_name as company_name
                FROM
                    kdd_website_config wc
                WHERE
                    wc.is_active = 1
                    AND wc.maintenance_mode = 0
                ORDER BY
                    wc.site_name ASC
            ");
            $stmt->execute();
            $websites = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($websites as &$website) {
                $website['id'] = (int)$website['id'];
                if (empty($website['site_description'])) {
                    $website['site_description'] = 'Website of ' . ($website['company_name'] ?: 'Unknown Company');
                }
            }

            return $websites;
        } catch (PDOException $e) {
            error_log("Error in Website::getAllPublic: " . $e->getMessage());
            throw $e;
        }
    }
}
