ALTER TABLE categories
ADD COLUMN seo_title VARCHAR(255) AFTER name,
ADD COLUMN seo_description TEXT AFTER seo_title,
ADD COLUMN seo_keywords TEXT AFTER seo_description,
ADD COLUMN slug VARCHAR(255) UNIQUE AFTER seo_keywords;
