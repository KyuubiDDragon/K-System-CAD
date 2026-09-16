CREATE TABLE kdd_law_settings (
 id TINYINT PRIMARY KEY, enabled TINYINT NOT NULL DEFAULT 1,
 guest TINYINT NOT NULL DEFAULT 0, revision INT NOT NULL DEFAULT 1
) ENGINE=InnoDB;
INSERT INTO kdd_law_settings(id) VALUES(1);
CREATE TABLE kdd_law_books (
 id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(160) NOT NULL,
 description TEXT NOT NULL, revision INT NOT NULL DEFAULT 1,
 created_by INT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE kdd_law_grants (
 book_id INT NOT NULL, authority_id INT NOT NULL,
 draft_read TINYINT NOT NULL DEFAULT 0, edit TINYINT NOT NULL DEFAULT 0,
 publish TINYINT NOT NULL DEFAULT 0, repeal TINYINT NOT NULL DEFAULT 0,
 PRIMARY KEY(book_id,authority_id),
 FOREIGN KEY(book_id) REFERENCES kdd_law_books(id),
 FOREIGN KEY(authority_id) REFERENCES kdd_authorities(id)
) ENGINE=InnoDB;
CREATE TABLE kdd_law_articles (
 id INT AUTO_INCREMENT PRIMARY KEY, book_id INT NOT NULL,
 number VARCHAR(40) NOT NULL, revision INT NOT NULL DEFAULT 1,
 UNIQUE KEY(book_id,number), FOREIGN KEY(book_id) REFERENCES kdd_law_books(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE kdd_law_versions (
 id INT AUTO_INCREMENT PRIMARY KEY, article_id INT NOT NULL,
 title VARCHAR(200) NOT NULL, chapter VARCHAR(160) NOT NULL DEFAULT '', body MEDIUMTEXT NOT NULL,
 state ENUM('draft','published') NOT NULL DEFAULT 'draft',
 reason TEXT NOT NULL, author_id INT NOT NULL, authority_id INT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 published_at DATETIME DEFAULT NULL, published_by INT DEFAULT NULL,
 effective_at DATETIME DEFAULT NULL, repealed_at DATETIME DEFAULT NULL,
 repeal_reason TEXT DEFAULT NULL, repealed_by INT DEFAULT NULL,
 INDEX(article_id,state,effective_at), FOREIGN KEY(article_id) REFERENCES kdd_law_articles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE kdd_law_audit (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, actor_id INT NOT NULL, authority_id INT NOT NULL,
 action VARCHAR(50) NOT NULL, details TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO kdd_authority_features(name,code,description) VALUES('Gesetze bearbeiten','laws','Redaktioneller Zugang; zusätzliche Zuständigkeit je Gesetzbuch erforderlich');
INSERT INTO kdd_permissions(name,module,sub_module,action,bitmask_value,action_display,module_display,display_group,description) VALUES
('READ_LAWS','laws',NULL,'read',1,'Lesen','Gesetze','Gesetze','Veröffentlichte Gesetze lesen'),
('READ_LAWS_DRAFTS','laws','drafts','read',1,'Entwürfe lesen','Gesetze – Entwürfe','Gesetze','Nur innerhalb zugewiesener Gesetzbücher'),
('WRITE_LAWS_DRAFTS','laws','drafts','write',2,'Bearbeiten','Gesetze – Entwürfe','Gesetze','Entwürfe erstellen und bearbeiten'),
('WRITE_LAWS_PUBLICATION','laws','publication','write',2,'Veröffentlichen','Gesetze – Veröffentlichung','Gesetze','Neue Fassungen freigeben'),
('WRITE_LAWS_REPEAL','laws','repeal','write',2,'Außer Kraft setzen','Gesetze – Aufhebung','Gesetze','Veröffentlichte Fassungen außer Kraft setzen');
