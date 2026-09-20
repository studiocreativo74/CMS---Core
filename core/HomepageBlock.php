<?php

declare(strict_types=1);

/**
 * Model zur Verwaltung dynamischer Inhaltsblöcke der Startseite.
 *
 * Repräsentiert Datensätze aus der Tabelle `homepage_blocks`.
 * Fängt fehlende Tabellen oder DB-Verbindungsprobleme defensiv ab.
 */
final class HomepageBlock
{
    /**
     * Gültige Block-Typen
     */
    public const VALID_TYPES = [
        'hero'        => 'Hero-Sektion (Großer Aufhänger)',
        'text'        => 'Textblock (Fließtext mit Überschrift)',
        'two_column'  => '2-Spalten-Block (Text & Bild/Info)',
        'features'    => 'Feature-Liste (3-4 Vorteils-Kacheln)',
        'cta'         => 'Call-To-Action (Handlungsaufforderung)',
        'custom'      => 'Benutzerdefiniert / HTML',
    ];

    private function __construct()
    {
    }

    /**
     * Liefert alle Blöcke sortiert nach sort_order ASC, id ASC.
     *
     * @param bool $onlyVisible Nur sichtbare Blöcke abrufen (für die öffentliche Startseite)
     * @return array<int, array<string, mixed>>
     */
    public static function all(bool $onlyVisible = false): array
    {
        try {
            $sql = 'SELECT * FROM `homepage_blocks`';
            $params = [];

            if ($onlyVisible) {
                $sql .= ' WHERE `is_visible` = 1';
            }

            $sql .= ' ORDER BY `sort_order` ASC, `id` ASC';

            $rows = DB::fetchAll($sql, $params);
            return array_map([self::class, 'formatRow'], $rows);
        } catch (\Throwable $e) {
            // Tabelle existiert ggf. noch nicht in phpMyAdmin -> sanfter Fallback
            error_log('HomepageBlock::all Hinweis/Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Findet einen einzelnen Block anhand der ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        try {
            $row = DB::fetchOne('SELECT * FROM `homepage_blocks` WHERE `id` = :id LIMIT 1', ['id' => $id]);
            return $row ? self::formatRow($row) : null;
        } catch (\Throwable $e) {
            error_log('HomepageBlock::find Fehler bei ID ' . $id . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Erstellt einen neuen Block.
     *
     * @param array<string, mixed> $data
     * @return int Letzte Insert-ID oder 0 bei Fehler
     */
    public static function create(array $data): int
    {
        $type = (string) ($data['type'] ?? 'text');
        if (!array_key_exists($type, self::VALID_TYPES)) {
            $type = 'text';
        }

        $title = trim((string) ($data['title'] ?? ''));
        $subtitle = trim((string) ($data['subtitle'] ?? ''));
        $content = (string) ($data['content'] ?? '');
        $sortOrder = (int) ($data['sort_order'] ?? self::getNextSortOrder());
        $isVisible = !empty($data['is_visible']) ? 1 : 0;

        $extraJson = null;
        if (isset($data['extra'])) {
            $extraJson = is_string($data['extra']) ? $data['extra'] : json_encode($data['extra'], JSON_UNESCAPED_UNICODE);
        }

        try {
            DB::execute(
                'INSERT INTO `homepage_blocks` 
                 (`type`, `title`, `subtitle`, `content`, `extra`, `sort_order`, `is_visible`, `created_at`, `updated_at`)
                 VALUES (:type, :title, :subtitle, :content, :extra, :sort_order, :is_visible, NOW(), NOW())',
                [
                    'type'       => $type,
                    'title'      => $title !== '' ? $title : null,
                    'subtitle'   => $subtitle !== '' ? $subtitle : null,
                    'content'    => $content !== '' ? $content : null,
                    'extra'      => $extraJson,
                    'sort_order' => $sortOrder,
                    'is_visible' => $isVisible,
                ]
            );

            return (int) DB::lastInsertId();
        } catch (\Throwable $e) {
            error_log('HomepageBlock::create Fehler: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Aktualisiert einen bestehenden Block.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public static function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $fields = [];
        $params = ['id' => $id];

        if (array_key_exists('type', $data)) {
            $type = (string) $data['type'];
            if (array_key_exists($type, self::VALID_TYPES)) {
                $fields[] = '`type` = :type';
                $params['type'] = $type;
            }
        }

        if (array_key_exists('title', $data)) {
            $t = trim((string) $data['title']);
            $fields[] = '`title` = :title';
            $params['title'] = $t !== '' ? $t : null;
        }

        if (array_key_exists('subtitle', $data)) {
            $s = trim((string) $data['subtitle']);
            $fields[] = '`subtitle` = :subtitle';
            $params['subtitle'] = $s !== '' ? $s : null;
        }

        if (array_key_exists('content', $data)) {
            $c = (string) $data['content'];
            $fields[] = '`content` = :content';
            $params['content'] = $c !== '' ? $c : null;
        }

        if (array_key_exists('extra', $data)) {
            $extraJson = is_string($data['extra']) ? $data['extra'] : json_encode($data['extra'], JSON_UNESCAPED_UNICODE);
            $fields[] = '`extra` = :extra';
            $params['extra'] = $extraJson;
        }

        if (array_key_exists('sort_order', $data)) {
            $fields[] = '`sort_order` = :sort_order';
            $params['sort_order'] = (int) $data['sort_order'];
        }

        if (array_key_exists('is_visible', $data)) {
            $fields[] = '`is_visible` = :is_visible';
            $params['is_visible'] = !empty($data['is_visible']) ? 1 : 0;
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = '`updated_at` = NOW()';

        try {
            DB::execute('UPDATE `homepage_blocks` SET ' . implode(', ', $fields) . ' WHERE `id` = :id', $params);
            return true;
        } catch (\Throwable $e) {
            error_log('HomepageBlock::update Fehler bei ID ' . $id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Löscht einen Block dauerhaft.
     *
     * @param int $id
     * @return bool
     */
    public static function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        try {
            DB::execute('DELETE FROM `homepage_blocks` WHERE `id` = :id', ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('HomepageBlock::delete Fehler bei ID ' . $id . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Schaltet die Sichtbarkeit eines Blocks um.
     *
     * @param int $id
     * @param int $isVisible 1 oder 0
     * @return bool
     */
    public static function toggleVisibility(int $id, int $isVisible): bool
    {
        return self::update($id, ['is_visible' => $isVisible ? 1 : 0]);
    }

    /**
     * Aktualisiert die Reihenfolge mehrerer Blöcke anhand eines sortierten Arrays von IDs.
     *
     * @param array<int, int> $orderedIds
     */
    public static function reorder(array $orderedIds): void
    {
        $order = 10;
        foreach ($orderedIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                try {
                    DB::execute(
                        'UPDATE `homepage_blocks` SET `sort_order` = :order, `updated_at` = NOW() WHERE `id` = :id',
                        ['order' => $order, 'id' => $id]
                    );
                    $order += 10;
                } catch (\Throwable $e) {
                    error_log('HomepageBlock::reorder Fehler bei ID ' . $id . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Ermittelt den nächsten verfügbaren sort_order Wert.
     *
     * @return int
     */
    public static function getNextSortOrder(): int
    {
        try {
            $max = DB::fetchOne('SELECT MAX(`sort_order`) AS max_order FROM `homepage_blocks`');
            return ((int) ($max['max_order'] ?? 0)) + 10;
        } catch (\Throwable $e) {
            return 10;
        }
    }

    /**
     * Hilfsmethode zur Typkonvertierung einer DB-Zeile.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function formatRow(array $row): array
    {
        $extra = null;
        if (!empty($row['extra'])) {
            $decoded = json_decode((string) $row['extra'], true);
            $extra = is_array($decoded) ? $decoded : null;
        }

        return [
            'id'         => (int) ($row['id'] ?? 0),
            'type'       => (string) ($row['type'] ?? 'text'),
            'title'      => (string) ($row['title'] ?? ''),
            'subtitle'   => (string) ($row['subtitle'] ?? ''),
            'content'    => (string) ($row['content'] ?? ''),
            'extra'      => $extra,
            'extra_raw'  => (string) ($row['extra'] ?? ''),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_visible' => (int) ($row['is_visible'] ?? 1),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }
}
