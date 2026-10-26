<?php

declare(strict_types=1);

/** Adds the optional React/JSX source fields to existing template catalogues. */
return static function (PDO $pdo): void {
    $database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($database === '') {
        throw new RuntimeException('No database selected for the portfolio template migration.');
    }

    $columns = $pdo->prepare(
        'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = :database_name AND TABLE_NAME = :table_name'
    );
    $columns->execute(['database_name' => $database, 'table_name' => 'portfolio_template_definitions']);
    $existing = array_fill_keys(array_map(static fn (array $row): string => (string) $row['COLUMN_NAME'], $columns->fetchAll()), true);

    if (!isset($existing['render_engine'])) {
        $pdo->exec("ALTER TABLE portfolio_template_definitions ADD COLUMN render_engine ENUM('html', 'react') NOT NULL DEFAULT 'html' AFTER description");
    }
    if (!isset($existing['react_code'])) {
        $pdo->exec('ALTER TABLE portfolio_template_definitions ADD COLUMN react_code MEDIUMTEXT NULL AFTER css_code');
    }
};
