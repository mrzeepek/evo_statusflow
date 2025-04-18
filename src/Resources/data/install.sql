CREATE TABLE IF NOT EXISTS `PREFIX_evo_statusflow_rule` (
  `id_rule` INT UNSIGNED AUTO_INCREMENT NOT NULL,
  `id_order_state_from` INT UNSIGNED NOT NULL,
  `id_order_state_to` INT UNSIGNED NOT NULL,
  `delay_hours` INT UNSIGNED NOT NULL DEFAULT 0,
  `condition_sql` TEXT,
  `auto_execute` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `active` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `date_add` DATETIME NOT NULL,
  `date_upd` DATETIME NOT NULL,
  PRIMARY KEY (`id_rule`),
  KEY `evo_statusflow_rule_from_idx` (`id_order_state_from`),
  KEY `evo_statusflow_rule_to_idx` (`id_order_state_to`),
  KEY `evo_statusflow_rule_active_idx` (`active`),
  KEY `evo_statusflow_rule_auto_execute_idx` (`auto_execute`),
  KEY `evo_statusflow_rule_date_add_idx` (`date_add`),
  KEY `evo_statusflow_rule_active_auto_idx` (`active`, `auto_execute`),
  CONSTRAINT `evo_statusflow_rule_order_state_from_fk`
  FOREIGN KEY (`id_order_state_from`) REFERENCES `PREFIX_order_state` (`id_order_state`) ON DELETE CASCADE,
  CONSTRAINT `evo_statusflow_rule_order_state_to_fk`
  FOREIGN KEY (`id_order_state_to`) REFERENCES `PREFIX_order_state` (`id_order_state`) ON DELETE CASCADE
  ) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_evo_statusflow_object` (
  `id_object` INT UNSIGNED NOT NULL,
  `object_type` VARCHAR(50) NOT NULL,
  `id_order_state` INT UNSIGNED NOT NULL,
  `date_add` DATETIME NOT NULL,
  `date_upd` DATETIME NOT NULL,
  PRIMARY KEY (`id_object`, `object_type`),
  KEY `evo_statusflow_object_order_state_idx` (`id_order_state`),
  KEY `evo_statusflow_object_date_add_idx` (`date_add`),
  KEY `evo_statusflow_object_date_upd_idx` (`date_upd`),
  KEY `evo_statusflow_object_type_idx` (`object_type`),
  CONSTRAINT `evo_statusflow_object_order_state_fk`
  FOREIGN KEY (`id_order_state`) REFERENCES `PREFIX_order_state` (`id_order_state`) ON DELETE CASCADE
  ) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `PREFIX_evo_statusflow_log` (
   `id_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,
   `log_type` VARCHAR(50) NOT NULL,
  `log_message` TEXT NOT NULL,
  `object_type` VARCHAR(50) NOT NULL,
  `object_id` INT UNSIGNED NOT NULL,
  `id_rule` INT UNSIGNED DEFAULT NULL,
  `additional_data` TEXT DEFAULT NULL,
  `date_add` DATETIME NOT NULL,
  PRIMARY KEY (`id_log`),
  KEY `idx_object_type_id` (`object_type`, `object_id`),
  KEY `idx_rule` (`id_rule`),
  KEY `idx_date_add` (`date_add`),
  KEY `idx_log_type` (`log_type`),
  KEY `idx_object_id` (`object_id`)
  ) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
