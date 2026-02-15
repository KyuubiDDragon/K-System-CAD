-- Migration: Add view type support for documents (spreadsheet/document toggle)
-- Date: 2025-01-23
-- Description: Adds support for documents to be displayed as rich-text documents or spreadsheets (Univer)

-- Add new columns to kdd_doc_documents table
ALTER TABLE kdd_doc_documents
  ADD COLUMN view_type ENUM('document', 'spreadsheet', 'both') DEFAULT 'document' COMMENT 'Type of view: document only, spreadsheet only, or both switchable',
  ADD COLUMN spreadsheet_data LONGTEXT NULL COMMENT 'JSON data for spreadsheet content (Univer format)',
  ADD COLUMN default_view ENUM('document', 'spreadsheet') DEFAULT 'document' COMMENT 'Default view to show when opening (only relevant when view_type is both)';

-- Add index for better query performance when filtering by view_type
ALTER TABLE kdd_doc_documents
  ADD INDEX idx_view_type (view_type);

-- Migration completed successfully
