-- Second DB for tenant content (registry DB is created from MYSQL_DATABASE=cms_registry)
CREATE DATABASE IF NOT EXISTS cms_tenant_demo
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
