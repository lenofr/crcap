-- =====================================================
-- CRCAP - DATABASE SCHEMA COMPLETO E ATUALIZADO
-- Sistema de Gerenciamento de Conteúdo
-- Versão: 2026.02 - Gerado com o projeto
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- TABELA: users
-- Gerenciamento de usuários e administradores
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NULL,
  `role` ENUM('admin', 'editor', 'author', 'viewer') DEFAULT 'viewer',
  `avatar` VARCHAR(255) NULL,
  `phone` VARCHAR(20) NULL,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: categories
-- Categorias para posts
-- =====================================================
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `icon` VARCHAR(100) NULL,
  `color` VARCHAR(7) DEFAULT '#001644',
  `parent_id` INT(11) UNSIGNED NULL,
  `order_position` INT(11) DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: posts
-- Últimas postagens e notícias
-- =====================================================
CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NOT NULL,
  `slug` VARCHAR(500) NOT NULL,
  `excerpt` TEXT NULL,
  `content` LONGTEXT NOT NULL,
  `featured_image` VARCHAR(500) NULL,
  `category_id` INT(11) UNSIGNED NULL,
  `author_id` INT(11) UNSIGNED NULL,
  `status` ENUM('draft', 'published', 'scheduled', 'archived') DEFAULT 'draft',
  `is_featured` TINYINT(1) DEFAULT 0,
  `views` INT(11) DEFAULT 0,
  `published_at` DATETIME NULL,
  `scheduled_at` DATETIME NULL,
  `seo_title` VARCHAR(255) NULL,
  `seo_description` TEXT NULL,
  `seo_keywords` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_published` (`published_at`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_category` (`category_id`),
  KEY `idx_author` (`author_id`),
  FULLTEXT KEY `fulltext_search` (`title`, `content`),
  CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posts_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: tags
-- Tags para posts
-- =====================================================
CREATE TABLE IF NOT EXISTS `tags` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: post_tags
-- Relacionamento posts e tags
-- =====================================================
CREATE TABLE IF NOT EXISTS `post_tags` (
  `post_id` INT(11) UNSIGNED NOT NULL,
  `tag_id` INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`post_id`, `tag_id`),
  KEY `idx_tag` (`tag_id`),
  CONSTRAINT `fk_post_tags_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_post_tags_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: president_schedule
-- Agenda do Presidente
-- =====================================================
CREATE TABLE IF NOT EXISTS `president_schedule` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT NULL,
  `event_type` ENUM('meeting', 'visit', 'ceremony', 'conference', 'trip', 'other') DEFAULT 'meeting',
  `location` VARCHAR(500) NULL,
  `address` TEXT NULL,
  `event_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NULL,
  `all_day` TINYINT(1) DEFAULT 0,
  `participants` TEXT NULL,
  `image` VARCHAR(500) NULL,
  `status` ENUM('scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
  `is_public` TINYINT(1) DEFAULT 1,
  `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
  `notes` TEXT NULL,
  `created_by` INT(11) UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`event_date`),
  KEY `idx_status` (`status`),
  KEY `idx_public` (`is_public`),
  KEY `idx_type` (`event_type`),
  CONSTRAINT `fk_schedule_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: events
-- Próximos eventos com links externos
-- =====================================================
CREATE TABLE IF NOT EXISTS `events` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NOT NULL,
  `slug` VARCHAR(500) NOT NULL,
  `description` TEXT NULL,
  `content` LONGTEXT NULL,
  `event_type` VARCHAR(100) NULL,
  `location` VARCHAR(500) NULL,
  `venue_name` VARCHAR(255) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(255) NULL,
  `state` VARCHAR(100) NULL,
  `postal_code` VARCHAR(20) NULL,
  `event_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NULL,
  `end_date` DATE NULL,
  `all_day` TINYINT(1) DEFAULT 0,
  `timezone` VARCHAR(50) DEFAULT 'America/Sao_Paulo',
  `featured_image` VARCHAR(500) NULL,
  `gallery` TEXT NULL COMMENT 'JSON array de imagens',
  `external_link` VARCHAR(1000) NULL,
  `registration_link` VARCHAR(1000) NULL,
  `registration_required` TINYINT(1) DEFAULT 0,
  `registration_deadline` DATETIME NULL,
  `max_participants` INT(11) NULL,
  `current_participants` INT(11) DEFAULT 0,
  `price` DECIMAL(10,2) DEFAULT 0.00,
  `is_free` TINYINT(1) DEFAULT 1,
  `organizer` VARCHAR(255) NULL,
  `contact_email` VARCHAR(255) NULL,
  `contact_phone` VARCHAR(20) NULL,
  `tags` TEXT NULL COMMENT 'JSON array de tags',
  `status` ENUM('draft', 'published', 'cancelled', 'postponed', 'completed') DEFAULT 'draft',
  `is_featured` TINYINT(1) DEFAULT 0,
  `visibility` ENUM('public', 'private', 'members_only') DEFAULT 'public',
  `views` INT(11) DEFAULT 0,
  `seo_title` VARCHAR(255) NULL,
  `seo_description` TEXT NULL,
  `created_by` INT(11) UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_date` (`event_date`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_visibility` (`visibility`),
  FULLTEXT KEY `fulltext_search` (`title`, `description`),
  CONSTRAINT `fk_events_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: event_registrations
-- Registros de participantes em eventos
-- =====================================================
CREATE TABLE IF NOT EXISTS `event_registrations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT(11) UNSIGNED NOT NULL,
  `user_id` INT(11) UNSIGNED NULL COMMENT 'Se o participante for usuário cadastrado',
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `cpf` VARCHAR(14) NULL,
  `company` VARCHAR(255) NULL,
  `position` VARCHAR(255) NULL,
  `additional_info` TEXT NULL,
  `status` ENUM('pending', 'confirmed', 'cancelled', 'attended') DEFAULT 'pending',
  `payment_status` ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
  `confirmation_code` VARCHAR(50) NULL,
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `confirmed_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_event` (`event_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  UNIQUE KEY `unique_registration` (`event_id`, `email`),
  CONSTRAINT `fk_registrations_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_registrations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: pages
-- Páginas estáticas (Histórico, Organograma, etc)
-- =====================================================
CREATE TABLE IF NOT EXISTS `pages` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NOT NULL,
  `slug` VARCHAR(500) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `template` VARCHAR(100) DEFAULT 'default',
  `parent_id` INT(11) UNSIGNED NULL,
  `menu_section` VARCHAR(100) NULL COMMENT 'Ex: crcap, governanca, etc',
  `order_position` INT(11) DEFAULT 0,
  `featured_image` VARCHAR(500) NULL,
  `status` ENUM('draft', 'published', 'private') DEFAULT 'draft',
  `visibility` ENUM('public', 'members_only', 'private') DEFAULT 'public',
  `show_in_menu` TINYINT(1) DEFAULT 1,
  `icon` VARCHAR(100) NULL,
  `seo_title` VARCHAR(255) NULL,
  `seo_description` TEXT NULL,
  `seo_keywords` TEXT NULL,
  `author_id` INT(11) UNSIGNED NULL,
  `views` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_status` (`status`),
  KEY `idx_menu_section` (`menu_section`),
  KEY `idx_show_in_menu` (`show_in_menu`),
  CONSTRAINT `fk_pages_parent` FOREIGN KEY (`parent_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pages_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: menu_items
-- Itens de menu customizáveis
-- =====================================================
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `menu_location` VARCHAR(50) NOT NULL COMMENT 'main, footer, sidebar, etc',
  `title` VARCHAR(255) NOT NULL,
  `url` VARCHAR(1000) NULL,
  `page_id` INT(11) UNSIGNED NULL,
  `parent_id` INT(11) UNSIGNED NULL,
  `icon` VARCHAR(100) NULL,
  `target` ENUM('_self', '_blank') DEFAULT '_self',
  `order_position` INT(11) DEFAULT 0,
  `css_class` VARCHAR(255) NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_menu_location` (`menu_location`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_order` (`order_position`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_menu_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_menu_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: sliders
-- Slider principal da página inicial
-- =====================================================
CREATE TABLE IF NOT EXISTS `sliders` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NOT NULL,
  `subtitle` TEXT NULL,
  `description` TEXT NULL,
  `image` VARCHAR(500) NOT NULL,
  `image_mobile` VARCHAR(500) NULL,
  `link_url` VARCHAR(1000) NULL,
  `link_text` VARCHAR(255) NULL,
  `link_target` ENUM('_self', '_blank') DEFAULT '_self',
  `order_position` INT(11) DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `overlay_opacity` DECIMAL(3,2) DEFAULT 0.50,
  `text_alignment` ENUM('left', 'center', 'right') DEFAULT 'left',
  `show_from` DATE NULL,
  `show_until` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_order` (`order_position`),
  KEY `idx_dates` (`show_from`, `show_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: documents
-- Documentos e arquivos (PDFs, editais, atas, etc)
-- =====================================================
CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_size` INT(11) NULL COMMENT 'Tamanho em bytes',
  `file_type` VARCHAR(50) NULL,
  `category` VARCHAR(100) NULL COMMENT 'editais, atas, relatorios, etc',
  `document_type` VARCHAR(100) NULL COMMENT 'Para atas: administrativa, fiscalizacao, registro, etc',
  `reference_number` VARCHAR(100) NULL,
  `publication_date` DATE NULL,
  `expiry_date` DATE NULL,
  `tags` TEXT NULL COMMENT 'JSON array',
  `downloads` INT(11) DEFAULT 0,
  `is_public` TINYINT(1) DEFAULT 1,
  `status` ENUM('active', 'archived', 'expired') DEFAULT 'active',
  `uploaded_by` INT(11) UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_status` (`status`),
  KEY `idx_public` (`is_public`),
  KEY `idx_publication` (`publication_date`),
  FULLTEXT KEY `fulltext_search` (`title`, `description`),
  CONSTRAINT `fk_documents_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: galleries
-- Galerias de fotos
-- =====================================================
CREATE TABLE IF NOT EXISTS `galleries` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NOT NULL,
  `slug` VARCHAR(500) NOT NULL,
  `description` TEXT NULL,
  `cover_image` VARCHAR(500) NULL,
  `category` VARCHAR(100) NULL,
  `event_date` DATE NULL,
  `photographer` VARCHAR(255) NULL,
  `status` ENUM('draft', 'published', 'private') DEFAULT 'draft',
  `views` INT(11) DEFAULT 0,
  `created_by` INT(11) UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  CONSTRAINT `fk_galleries_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: gallery_images
-- Imagens das galerias
-- =====================================================
CREATE TABLE IF NOT EXISTS `gallery_images` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `gallery_id` INT(11) UNSIGNED NOT NULL,
  `image_path` VARCHAR(500) NOT NULL,
  `title` VARCHAR(500) NULL,
  `description` TEXT NULL,
  `order_position` INT(11) DEFAULT 0,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gallery` (`gallery_id`),
  KEY `idx_order` (`order_position`),
  CONSTRAINT `fk_gallery_images` FOREIGN KEY (`gallery_id`) REFERENCES `galleries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: newsletters
-- Lista de emails para newsletter
-- =====================================================
CREATE TABLE IF NOT EXISTS `newsletters` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NULL,
  `status` ENUM('subscribed', 'unsubscribed', 'bounced') DEFAULT 'subscribed',
  `subscription_ip` VARCHAR(45) NULL,
  `subscription_source` VARCHAR(100) NULL,
  `confirmed` TINYINT(1) DEFAULT 0,
  `confirmation_token` VARCHAR(100) NULL,
  `confirmed_at` DATETIME NULL,
  `unsubscribed_at` DATETIME NULL,
  `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_confirmed` (`confirmed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: email_templates
-- Templates de email
-- =====================================================
CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `subject` VARCHAR(500) NULL,
  `content_html` LONGTEXT NOT NULL,
  `content_text` LONGTEXT NULL,
  `thumbnail` VARCHAR(500) NULL,
  `category` VARCHAR(100) NULL,
  `is_system` TINYINT(1) DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_by` INT(11) UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  CONSTRAINT `fk_templates_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: email_campaigns
-- Campanhas de email marketing
-- =====================================================
CREATE TABLE IF NOT EXISTS `email_campaigns` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(500) NOT NULL,
  `subject` VARCHAR(500) NOT NULL,
  `from_name` VARCHAR(255) NOT NULL,
  `from_email` VARCHAR(255) NOT NULL,
  `reply_to` VARCHAR(255) NULL,
  `template_id` INT(11) UNSIGNED NULL,
  `content_html` LONGTEXT NOT NULL,
  `content_text` LONGTEXT NULL,
  `status` ENUM('draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled') DEFAULT 'draft',
  `scheduled_at` DATETIME NULL,
  `sent_at` DATETIME NULL,
  `total_recipients` INT(11) DEFAULT 0,
  `sent_count` INT(11) DEFAULT 0,
  `opened_count` INT(11) DEFAULT 0,
  `clicked_count` INT(11) DEFAULT 0,
  `bounced_count` INT(11) DEFAULT 0,
  `unsubscribed_count` INT(11) DEFAULT 0,
  `segment_filter` TEXT NULL COMMENT 'JSON filtros de segmentação',
  `created_by` INT(11) UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_scheduled` (`scheduled_at`),
  KEY `idx_creator` (`created_by`),
  CONSTRAINT `fk_campaigns_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_campaigns_template` FOREIGN KEY (`template_id`) REFERENCES `email_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: email_logs
-- Logs de envio de emails
-- =====================================================
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id` INT(11) UNSIGNED NULL,
  `recipient_email` VARCHAR(255) NOT NULL,
  `recipient_name` VARCHAR(255) NULL,
  `subject` VARCHAR(500) NOT NULL,
  `status` ENUM('queued', 'sent', 'failed', 'bounced', 'opened', 'clicked') DEFAULT 'queued',
  `error_message` TEXT NULL,
  `opened_at` DATETIME NULL,
  `clicked_at` DATETIME NULL,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_campaign` (`campaign_id`),
  KEY `idx_recipient` (`recipient_email`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`),
  CONSTRAINT `fk_logs_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `email_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: smtp_settings
-- Configurações SMTP
-- =====================================================
CREATE TABLE IF NOT EXISTS `smtp_settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `host` VARCHAR(255) NOT NULL,
  `port` INT(11) DEFAULT 587,
  `username` VARCHAR(255) NOT NULL,
  `password` VARCHAR(500) NOT NULL COMMENT 'Encrypted',
  `encryption` ENUM('none', 'ssl', 'tls') DEFAULT 'tls',
  `from_email` VARCHAR(255) NOT NULL,
  `from_name` VARCHAR(255) NOT NULL,
  `is_default` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `daily_limit` INT(11) DEFAULT 0 COMMENT '0 = sem limite',
  `emails_sent_today` INT(11) DEFAULT 0,
  `last_reset_date` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_default` (`is_default`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: contacts
-- Mensagens de contato e ouvidoria
-- =====================================================
CREATE TABLE IF NOT EXISTS `contacts` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NULL,
  `subject` VARCHAR(500) NULL,
  `message` TEXT NOT NULL,
  `department` VARCHAR(100) NULL COMMENT 'Ex: ouvidoria, financeiro, fiscalizacao',
  `type` ENUM('contact', 'ouvidoria', 'denuncia', 'sugestao', 'elogio') DEFAULT 'contact',
  `status` ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `replied_at` DATETIME NULL,
  `replied_by` INT(11) UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_email` (`email`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_contacts_replier` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: settings
-- Configurações gerais do site
-- =====================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` LONGTEXT NULL,
  `setting_type` VARCHAR(50) DEFAULT 'text' COMMENT 'text, number, boolean, json, etc',
  `setting_group` VARCHAR(100) DEFAULT 'general',
  `description` TEXT NULL,
  `is_public` TINYINT(1) DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: media
-- Biblioteca de mídia
-- =====================================================
CREATE TABLE IF NOT EXISTS `media` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_size` INT(11) NULL,
  `file_type` VARCHAR(50) NULL,
  `mime_type` VARCHAR(100) NULL,
  `width` INT(11) NULL,
  `height` INT(11) NULL,
  `alt_text` VARCHAR(500) NULL,
  `caption` TEXT NULL,
  `description` TEXT NULL,
  `uploaded_by` INT(11) UNSIGNED NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`file_type`),
  KEY `idx_uploader` (`uploaded_by`),
  CONSTRAINT `fk_media_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: activity_logs
-- Logs de atividades do sistema
-- =====================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(100) NULL,
  `entity_id` INT(11) UNSIGNED NULL,
  `description` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: password_resets
-- Tokens para recuperação de senha
-- =====================================================
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Usuário admin padrão
-- IMPORTANTE: Alterar a senha após o primeiro login!
-- Senha padrão: Admin@2026
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `status`) VALUES
('admin', 'admin@crcap.org.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador CRCAP', 'admin', 'active');

-- =====================================================
-- CONFIGURAÇÕES INICIAIS DO SITE
-- =====================================================
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `setting_group`, `description`, `is_public`) VALUES

-- Grupo: general
('site_name',           'CRCAP - Conselho Regional de Administração do Amapá', 'text',    'general', 'Nome do site',                    1),
('site_description',    'Órgão fiscalizador responsável pelo controle e auditoria das atividades profissionais no Amapá.', 'text', 'general', 'Descrição do site', 1),
('site_cnpj',           '00.000.000/0001-00',  'text',    'general', 'CNPJ do conselho',                0),
('site_region',         'Amapá',               'text',    'general', 'Estado/Região de atuação',        1),
('site_hours',          'Seg-Sex: 9h às 18h',  'text',    'general', 'Horário de funcionamento',        1),
('posts_per_page',      '10',                  'number',  'general', 'Posts por página',                0),
('enable_comments',     '0',                   'boolean', 'general', 'Habilitar comentários',           0),
('maintenance_mode',    '0',                   'boolean', 'general', 'Modo manutenção',                 0),
('maintenance_message', 'Site em manutenção. Voltaremos em breve.', 'text', 'general', 'Mensagem do modo manutenção', 0),
('allow_registration',  '1',                   'boolean', 'general', 'Permitir auto-registro de usuários', 0),

-- Grupo: contact
('site_email',          'contato@crcap.org.br',   'text', 'contact', 'Email principal',              1),
('ouvidoria_email',     'ouvidoria@crcap.org.br', 'text', 'contact', 'Email da ouvidoria',           1),
('site_phone',          '(96) 3223-2600',          'text', 'contact', 'Telefone principal',           1),
('whatsapp',            '',                        'text', 'contact', 'WhatsApp (somente números)',   1),

-- Grupo: address
('endereco_logradouro', 'Av. Padre Júlio Maria Lombaerd, 1010', 'text', 'address', 'Logradouro', 1),
('endereco_bairro',     'Centro',      'text', 'address', 'Bairro',           1),
('endereco_cidade',     'Macapá',      'text', 'address', 'Cidade',           1),
('endereco_estado',     'AP',          'text', 'address', 'UF',               1),
('endereco_cep',        '68900-000',   'text', 'address', 'CEP',              1),
('google_maps_url',     '',            'text', 'address', 'URL embed Google Maps', 0),

-- Grupo: identity (logo e favicon gerenciados via upload no admin)
('site_logo',           '',  'text', 'identity', 'Caminho do logotipo',      1),
('site_favicon',        '',  'text', 'identity', 'Caminho do favicon',       1),
('color_primary',       '#001644', 'text', 'identity', 'Cor primária (hex)', 1),
('color_accent',        '#BF8D1A', 'text', 'identity', 'Cor de destaque (hex)', 1),
('color_secondary',     '#006633', 'text', 'identity', 'Cor secundária (hex)',  1),

-- Grupo: social
('facebook_url',        '', 'text', 'social', 'URL do Facebook',       1),
('instagram_url',       '', 'text', 'social', 'URL do Instagram',      1),
('twitter_url',         '', 'text', 'social', 'URL do Twitter/X',      1),
('linkedin_url',        '', 'text', 'social', 'URL do LinkedIn',       1),
('youtube_url',         '', 'text', 'social', 'URL do YouTube',        1),
('tiktok_url',          '', 'text', 'social', 'URL do TikTok',         1),

-- Grupo: seo
('google_analytics',         '', 'text', 'seo', 'Google Analytics ID (GA4)',             0),
('google_tag_manager',       '', 'text', 'seo', 'Google Tag Manager ID',                 0),
('google_search_console',    '', 'text', 'seo', 'Google Search Console meta verificação', 0),
('default_keywords',         'CRCAP, Conselho Regional, Administração, Amapá, fiscalização, profissionais', 'text', 'seo', 'Palavras-chave padrão', 0),
('head_scripts',             '', 'text', 'seo', 'Scripts no <head>',                     0),
('body_scripts',             '', 'text', 'seo', 'Scripts antes do </body>',              0);

-- =====================================================
-- CATEGORIAS PADRÃO
-- =====================================================
INSERT INTO `categories` (`name`, `slug`, `description`, `icon`, `color`, `order_position`) VALUES
('Notícias',     'noticias',     'Notícias e atualizações institucionais',    'fa-newspaper',    '#001644', 1),
('Eventos',      'eventos',      'Eventos e atividades do Conselho',           'fa-calendar',     '#BF8D1A', 2),
('Governança',   'governanca',   'Governança e transparência',                 'fa-shield-alt',   '#006633', 3),
('Editais',      'editais',      'Editais, concursos e licitações',            'fa-file-alt',     '#022E6B', 4),
('Fiscalização', 'fiscalizacao', 'Ações de fiscalização e auditoria',          'fa-search',       '#001644', 5),
('Educação',     'educacao',     'Cursos, capacitações e desenvolvimento',     'fa-graduation-cap','#006633',6);

-- =====================================================
-- TAGS PADRÃO
-- =====================================================
INSERT INTO `tags` (`name`, `slug`) VALUES
('Resolução',          'resolucao'),
('Portaria',           'portaria'),
('Edital',             'edital'),
('Concurso',           'concurso'),
('Capacitação',        'capacitacao'),
('Fiscalização',       'fiscalizacao'),
('Transparência',      'transparencia'),
('LGPD',               'lgpd'),
('Governança',         'governanca'),
('Registro',           'registro');

-- =====================================================
-- SLIDERS INICIAIS DA HOME
-- =====================================================
INSERT INTO `sliders` (`title`, `subtitle`, `description`, `image`, `link_url`, `link_text`, `order_position`, `status`) VALUES
('Bem-vindo ao CRCAP',
 'Conselho Regional de Administração do Amapá',
 'Fiscalizando e valorizando a profissão de Administrador no Amapá desde 1966.',
 '/uploads/sliders/slide-default-1.jpg',
 '/pages/historico.php',
 'Conheça nossa história',
 1, 'active'),
('Portal do Profissional',
 'Acesse seus serviços online',
 'Emita certidões, pague anuidades e acesse seus dados profissionais de forma rápida e segura.',
 '/uploads/sliders/slide-default-2.jpg',
 '/pages/servicos-online.php',
 'Acessar serviços',
 2, 'active'),
('Desenvolvimento Profissional',
 'Cursos e capacitações',
 'Participe de cursos, workshops e eventos para manter-se atualizado na área de Administração.',
 '/uploads/sliders/slide-default-3.jpg',
 '/pages/desenvolvimento/agenda-lives.php',
 'Ver agenda',
 3, 'active');

-- =====================================================
-- PÁGINAS ESTÁTICAS INICIAIS
-- =====================================================
INSERT INTO `pages` (`title`, `slug`, `content`, `menu_section`, `status`, `visibility`, `order_position`, `icon`) VALUES
('Histórico do CRCAP',
 'historico',
 '<p>O Conselho Regional de Administração do Amapá (CRCAP) foi criado com a missão de fiscalizar o exercício da profissão de Administrador no estado do Amapá, garantindo padrões éticos e de qualidade na prestação de serviços profissionais.</p><p>Desde sua fundação, o CRCAP tem trabalhado incansavelmente em prol dos profissionais de Administração amapaenses, promovendo capacitação, fiscalização e representação da classe.</p>',
 'crcap', 'published', 'public', 1, 'fa-landmark'),

('Organograma CRCAP',
 'organograma',
 '<p>Estrutura organizacional do Conselho Regional de Administração do Amapá, composta pela Presidência, Vice-Presidência, Câmaras Especializadas e Departamentos Técnicos.</p>',
 'crcap', 'published', 'public', 2, 'fa-sitemap'),

('Composição do Conselho',
 'composicao',
 '<p>O CRCAP é composto por conselheiros efetivos e suplentes, eleitos pelos profissionais de Administração registrados no Amapá, além de representantes do CFA.</p>',
 'crcap', 'published', 'public', 3, 'fa-users'),

('Delegacias e Representações',
 'delegacias',
 '<p>O CRCAP conta com delegacias e representações regionais para atendimento dos profissionais em todo o estado do Amapá.</p>',
 'crcap', 'published', 'public', 4, 'fa-map-marker-alt'),

('Sobre a Governança',
 'sobre-governanca',
 '<p>O CRCAP adota um modelo de governança orientado à transparência, integridade e eficiência na gestão pública, seguindo as melhores práticas nacionais e internacionais.</p>',
 'governanca', 'published', 'public', 1, 'fa-shield-alt'),

('Política de Privacidade',
 'privacidade',
 '<p>Esta Política de Privacidade descreve como o CRCAP coleta, usa e protege as informações pessoais dos usuários deste portal, em conformidade com a Lei Geral de Proteção de Dados (LGPD – Lei nº 13.709/2018).</p>',
 NULL, 'published', 'public', 1, 'fa-lock'),

('Termos de Uso',
 'termos',
 '<p>Ao utilizar este portal, você concorda com os presentes Termos de Uso. O CRCAP reserva-se o direito de modificar estes termos a qualquer momento.</p>',
 NULL, 'published', 'public', 2, 'fa-file-contract'),

('Acessibilidade',
 'acessibilidade',
 '<p>O CRCAP está comprometido em garantir acessibilidade digital para todas as pessoas, incluindo aquelas com deficiências. Este portal segue as diretrizes WCAG 2.1 nível AA.</p>',
 NULL, 'published', 'public', 3, 'fa-universal-access'),

('Perguntas Frequentes',
 'faq',
 '<p>Respostas para as principais dúvidas dos profissionais sobre os serviços e processos do CRCAP.</p>',
 NULL, 'published', 'public', 4, 'fa-question-circle'),

('Serviços Online',
 'servicos-online',
 '<p>Acesse os principais serviços do CRCAP de forma online, 24 horas por dia, 7 dias por semana.</p>',
 NULL, 'published', 'public', 5, 'fa-laptop');

-- =====================================================
-- AGENDA DO PRESIDENTE - EXEMPLOS
-- =====================================================
INSERT INTO `president_schedule` (`title`, `description`, `event_type`, `location`, `event_date`, `start_time`, `end_time`, `status`, `is_public`, `priority`) VALUES
('Reunião com Secretaria de Trabalho',
 'Reunião para tratar de convênios e parcerias institucionais.',
 'meeting',
 'Secretaria de Estado do Trabalho e Empreendedorismo - Macapá/AP',
 DATE_ADD(CURDATE(), INTERVAL 1 DAY),
 '14:00:00', '16:00:00',
 'scheduled', 1, 'high'),

('Sessão Plenária do Conselho',
 'Sessão ordinária de deliberações do Pleno do CRCAP.',
 'meeting',
 'Auditório Principal CRCAP',
 DATE_ADD(CURDATE(), INTERVAL 2 DAY),
 '09:00:00', '12:00:00',
 'scheduled', 1, 'high'),

('Abertura do Seminário de Administração',
 'Cerimônia de abertura do Seminário Regional de Administração 2026.',
 'ceremony',
 'Centro de Convenções do Amapá',
 DATE_ADD(CURDATE(), INTERVAL 7 DAY),
 '08:00:00', '09:00:00',
 'confirmed', 1, 'medium'),

('Reunião com CFA – Conselho Federal',
 'Videoconferência com a presidência do CFA para alinhamento de diretrizes nacionais.',
 'conference',
 'Online – Microsoft Teams',
 DATE_ADD(CURDATE(), INTERVAL 10 DAY),
 '10:00:00', '11:30:00',
 'scheduled', 1, 'medium');

-- =====================================================
-- EVENTOS INICIAIS - EXEMPLOS
-- =====================================================
INSERT INTO `events` (`title`, `slug`, `description`, `event_type`, `location`, `event_date`, `start_time`, `end_time`, `is_free`, `status`, `is_featured`) VALUES
('Seminário Regional de Administração 2026',
 'seminario-regional-administracao-2026',
 'O maior evento de Administração do Amapá, reunindo profissionais, acadêmicos e gestores para debater as tendências e desafios da área.',
 'Congresso',
 'Centro de Convenções do Amapá – Macapá/AP',
 DATE_ADD(CURDATE(), INTERVAL 15 DAY),
 '08:00:00', '18:00:00',
 1, 'published', 1),

('Workshop: Gestão Pública e Controle Interno',
 'workshop-gestao-publica-controle-interno',
 'Capacitação prática voltada a profissionais de Administração que atuam no setor público.',
 'Workshop',
 'Auditório CRCAP – Macapá/AP',
 DATE_ADD(CURDATE(), INTERVAL 22 DAY),
 '09:00:00', '17:00:00',
 1, 'published', 0),

('Live: Reforma Administrativa e o Profissional de ADM',
 'live-reforma-administrativa-profissional-adm',
 'Debate online sobre os impactos da reforma administrativa para os profissionais de Administração.',
 'Live',
 'Online – YouTube CRCAP',
 DATE_ADD(CURDATE(), INTERVAL 5 DAY),
 '19:00:00', '21:00:00',
 1, 'published', 0);

-- =====================================================
-- DOCUMENTOS INICIAIS - EXEMPLOS
-- =====================================================
INSERT INTO `documents` (`title`, `description`, `file_path`, `file_name`, `file_type`, `category`, `document_type`, `publication_date`, `is_public`, `status`) VALUES
('Estatuto do CRCAP',
 'Estatuto social do Conselho Regional de Administração do Amapá.',
 '/uploads/documents/estatuto-crcap.pdf',
 'estatuto-crcap.pdf',
 'pdf', 'estatutos', NULL,
 CURDATE(), 1, 'active'),

('Regimento Interno',
 'Regimento interno do CRCAP aprovado pelo Plenário.',
 '/uploads/documents/regimento-interno.pdf',
 'regimento-interno.pdf',
 'pdf', 'regimentos', NULL,
 CURDATE(), 1, 'active'),

('Edital nº 001/2026 – Processo Seletivo',
 'Edital de processo seletivo para contratação de colaboradores.',
 '/uploads/documents/edital-001-2026.pdf',
 'edital-001-2026.pdf',
 'pdf', 'editais', NULL,
 CURDATE(), 1, 'active'),

('Ata da Reunião Plenária – Janeiro/2026',
 'Ata da reunião plenária ordinária realizada em janeiro de 2026.',
 '/uploads/documents/ata-plenaria-jan-2026.pdf',
 'ata-plenaria-jan-2026.pdf',
 'pdf', 'atas', 'administrativa',
 DATE_FORMAT(CURDATE(), '%Y-01-01'), 1, 'active'),

('Ata da Câmara de Fiscalização – Fevereiro/2026',
 'Ata da reunião da Câmara de Fiscalização de fevereiro de 2026.',
 '/uploads/documents/ata-fiscalizacao-fev-2026.pdf',
 'ata-fiscalizacao-fev-2026.pdf',
 'pdf', 'atas', 'fiscalizacao',
 DATE_FORMAT(CURDATE(), '%Y-02-01'), 1, 'active');

-- =====================================================
-- FIM DO SCHEMA COMPLETO
-- =====================================================
-- Tabelas: 17 tabelas principais + 1 auxiliar (password_resets) = 18 total
-- Dados iniciais: 1 admin, 10 settings, 6 categorias, 10 tags,
--                 3 sliders, 10 páginas, 4 agenda, 3 eventos, 5 documentos
-- =====================================================
