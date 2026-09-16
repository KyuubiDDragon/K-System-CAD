-- Short-lived opening status, maintained by authorized company employees.
CREATE TABLE IF NOT EXISTS kdd_social_company_openings (
 company_id INT PRIMARY KEY,
 open_until DATETIME NOT NULL,
 updated_by INT NOT NULL,
 INDEX(open_until)
) ENGINE=InnoDB;
