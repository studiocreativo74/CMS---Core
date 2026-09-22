<?php

declare(strict_types=1);

/**
 * DocumentRepository
 *
 * Zentraler Datenbank-Zugriff für das DMS-Modul (Paperless-Style):
 * - Dokumente, Versionen, Tags, Relationen und Audit-Logs
 * - Graceful Fallback bei noch nicht ausgeführter SQL-Migration
 * - Vorbereitung für spätere Eigentümerportal-Abfragen (Properties, Units, Cases)
 */
final class DocumentRepository
{
    private static ?bool $tableExists = null;

    /**
     * Prüft, ob die Tabelle `documents` in der Datenbank existiert.
     */
    public static function isTableCreated(): bool
    {
        if (self::$tableExists !== null) {
            return self::$tableExists;
        }

        try {
            $stmt = DB::query("SHOW TABLES LIKE 'documents'");
            self::$tableExists = ($stmt->fetch() !== false);
        } catch (\Throwable $e) {
            error_log('DocumentRepository::isTableCreated Fehler: ' . $e->getMessage());
            self::$tableExists = false;
        }

        return self::$tableExists;
    }

    /**
     * Lädt Dokumente anhand flexibler Filterkriterien mit Pagination.
     *
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public static function getDocuments(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        if (!self::isTableCreated()) {
            return [];
        }

        try {
            $params = [];
            $where = [];

            // 1. Status-Filter
            $status = (string) ($filters['status'] ?? 'active');
            if ($status !== 'all' && $status !== '') {
                $where[] = 'd.`status` = :status';
                $params['status'] = $status;
            }

            // 2. Dokumenten-Typ (z.B. INVOICE, MINUTES, PLAN)
            if (!empty($filters['doc_type'])) {
                $where[] = 'd.`doc_type` = :doc_type';
                $params['doc_type'] = (string) $filters['doc_type'];
            }

            // 3. Sichtbarkeit (internal, owner_portal, public etc.)
            if (!empty($filters['visibility'])) {
                $where[] = 'd.`visibility` = :visibility';
                $params['visibility'] = (string) $filters['visibility'];
            }

            // 4. Tag-Filter
            if (!empty($filters['tag_id'])) {
                $where[] = 'EXISTS (SELECT 1 FROM `document_tag_assignments` dta WHERE dta.`document_id` = d.`id` AND dta.`tag_id` = :tag_id)';
                $params['tag_id'] = (int) $filters['tag_id'];
            }

            // 5. Zielobjekt-Filter (Eigentümerportal: z.B. property, unit, case)
            if (!empty($filters['target_type']) && !empty($filters['target_id'])) {
                $where[] = 'EXISTS (SELECT 1 FROM `document_relations` dr WHERE dr.`document_id` = d.`id` AND dr.`target_type` = :target_type AND dr.`target_id` = :target_id)';
                $params['target_type'] = (string) $filters['target_type'];
                $params['target_id'] = (string) $filters['target_id'];
            }

            // 6. Suchbegriff (Volltext auf Titel, Beschreibung, Aktenzeichen, Dateinamen)
            if (!empty($filters['search'])) {
                $search = trim((string) $filters['search']);
                $where[] = '(d.`title` LIKE :search OR d.`description` LIKE :search OR d.`reference_number` LIKE :search OR dv.`original_filename` LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "
                SELECT 
                    d.*,
                    dv.`version_number` AS latest_version,
                    dv.`original_filename`,
                    dv.`storage_path`,
                    dv.`mime_type`,
                    dv.`file_size`,
                    dv.`file_hash`,
                    dv.`created_at` AS version_created_at,
                    (SELECT COUNT(*) FROM `document_versions` dv_all WHERE dv_all.`document_id` = d.`id`) AS version_count,
                    (SELECT COUNT(*) FROM `document_relations` dr_all WHERE dr_all.`document_id` = d.`id`) AS relations_count
                FROM `documents` d
                LEFT JOIN `document_versions` dv ON dv.`document_id` = d.`id` AND dv.`version_number` = (
                    SELECT MAX(sub_v.`version_number`) FROM `document_versions` sub_v WHERE sub_v.`document_id` = d.`id`
                )
                {$whereSql}
                ORDER BY d.`created_at` DESC
                LIMIT " . (int) $limit . " OFFSET " . (int) $offset . "
            ";

            $documents = DB::fetchAll($sql, $params);

            // Tags für gefundene Dokumente nachladen
            if (!empty($documents)) {
                $docIds = array_column($documents, 'id');
                $tagsMap = self::getTagsForMultipleDocuments($docIds);
                foreach ($documents as &$doc) {
                    $docId = (int) $doc['id'];
                    $doc['tags'] = $tagsMap[$docId] ?? [];
                }
                unset($doc);
            }

            return $documents;
        } catch (\Throwable $e) {
            error_log('DocumentRepository::getDocuments Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Zählt die Gesamtzahl an Dokumenten für gegebene Filter (für Pagination).
     *
     * @param array<string, mixed> $filters
     * @return int
     */
    public static function countDocuments(array $filters = []): int
    {
        if (!self::isTableCreated()) {
            return 0;
        }

        try {
            $params = [];
            $where = [];

            $status = (string) ($filters['status'] ?? 'active');
            if ($status !== 'all' && $status !== '') {
                $where[] = 'd.`status` = :status';
                $params['status'] = $status;
            }

            if (!empty($filters['doc_type'])) {
                $where[] = 'd.`doc_type` = :doc_type';
                $params['doc_type'] = (string) $filters['doc_type'];
            }

            if (!empty($filters['visibility'])) {
                $where[] = 'd.`visibility` = :visibility';
                $params['visibility'] = (string) $filters['visibility'];
            }

            if (!empty($filters['tag_id'])) {
                $where[] = 'EXISTS (SELECT 1 FROM `document_tag_assignments` dta WHERE dta.`document_id` = d.`id` AND dta.`tag_id` = :tag_id)';
                $params['tag_id'] = (int) $filters['tag_id'];
            }

            if (!empty($filters['target_type']) && !empty($filters['target_id'])) {
                $where[] = 'EXISTS (SELECT 1 FROM `document_relations` dr WHERE dr.`document_id` = d.`id` AND dr.`target_type` = :target_type AND dr.`target_id` = :target_id)';
                $params['target_type'] = (string) $filters['target_type'];
                $params['target_id'] = (string) $filters['target_id'];
            }

            if (!empty($filters['search'])) {
                $search = trim((string) $filters['search']);
                $where[] = '(d.`title` LIKE :search OR d.`description` LIKE :search OR d.`reference_number` LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            $sql = "SELECT COUNT(*) AS total FROM `documents` d {$whereSql}";

            $row = DB::fetchOne($sql, $params);
            return (int) ($row['total'] ?? 0);
        } catch (\Throwable $e) {
            error_log('DocumentRepository::countDocuments Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Lädt ein einzelnes Dokument anhand seiner ID inklusive neuester Version, Tags und Relationen.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public static function getDocument(int $id): ?array
    {
        if (!self::isTableCreated() || $id <= 0) {
            return null;
        }

        try {
            $sql = "
                SELECT 
                    d.*,
                    dv.`id` AS latest_version_id,
                    dv.`version_number` AS latest_version,
                    dv.`original_filename`,
                    dv.`storage_path`,
                    dv.`mime_type`,
                    dv.`file_size`,
                    dv.`file_hash`,
                    dv.`change_notes` AS latest_change_notes,
                    dv.`created_at` AS version_created_at,
                    u.`name` AS author_name,
                    u.`email` AS author_email
                FROM `documents` d
                LEFT JOIN `document_versions` dv ON dv.`document_id` = d.`id` AND dv.`version_number` = (
                    SELECT MAX(sub_v.`version_number`) FROM `document_versions` sub_v WHERE sub_v.`document_id` = d.`id`
                )
                LEFT JOIN `users` u ON u.`id` = d.`created_by`
                WHERE d.`id` = :id
                LIMIT 1
            ";

            $doc = DB::fetchOne($sql, ['id' => $id]);
            if (!$doc) {
                return null;
            }

            $doc['tags'] = self::getTagsForDocument($id);
            $doc['relations'] = self::getRelations($id);
            $doc['versions'] = self::getVersions($id);

            return $doc;
        } catch (\Throwable $e) {
            error_log('DocumentRepository::getDocument Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Lädt alle Versionen eines Dokuments geordnet von neu nach alt.
     *
     * @param int $documentId
     * @return array<int, array<string, mixed>>
     */
    public static function getVersions(int $documentId): array
    {
        if (!self::isTableCreated() || $documentId <= 0) {
            return [];
        }

        try {
            $sql = "
                SELECT 
                    dv.*,
                    u.`name` AS uploader_name,
                    u.`email` AS uploader_email
                FROM `document_versions` dv
                LEFT JOIN `users` u ON u.`id` = dv.`created_by`
                WHERE dv.`document_id` = :document_id
                ORDER BY dv.`version_number` DESC
            ";

            return DB::fetchAll($sql, ['document_id' => $documentId]);
        } catch (\Throwable $e) {
            error_log('DocumentRepository::getVersions Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lädt eine spezifische Version eines Dokuments.
     */
    public static function getVersion(int $documentId, int $versionNumber): ?array
    {
        if (!self::isTableCreated() || $documentId <= 0 || $versionNumber <= 0) {
            return null;
        }

        try {
            $sql = "SELECT * FROM `document_versions` WHERE `document_id` = :doc_id AND `version_number` = :v_num LIMIT 1";
            return DB::fetchOne($sql, ['doc_id' => $documentId, 'v_num' => $versionNumber]);
        } catch (\Throwable $e) {
            error_log('DocumentRepository::getVersion Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Erstellt ein neues Dokument inklusive der ersten Datei-Version (v1) und Tags.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $fileInfo
     * @param int|null $userId
     * @return int ID des neu angelegten Dokuments
     * @throws RuntimeException
     */
    public static function createDocument(array $data, array $fileInfo, ?int $userId = null): int
    {
        if (!self::isTableCreated()) {
            throw new RuntimeException('Die DMS-Datenbanktabellen wurden noch nicht angelegt. Bitte die Migration ausführen.');
        }

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            $title = (string) ($fileInfo['original_filename'] ?? 'Neues Dokument');
        }

        $docType = (string) ($data['doc_type'] ?? 'MISC');
        $status = (string) ($data['status'] ?? 'active');
        $description = isset($data['description']) ? trim((string) $data['description']) : null;
        $documentDate = !empty($data['document_date']) ? (string) $data['document_date'] : date('Y-m-d');
        $validFrom = !empty($data['valid_from']) ? (string) $data['valid_from'] : null;
        $validUntil = !empty($data['valid_until']) ? (string) $data['valid_until'] : null;
        $referenceNumber = !empty($data['reference_number']) ? trim((string) $data['reference_number']) : null;
        $visibility = (string) ($data['visibility'] ?? 'internal');

        try {
            // 1. Dokument anlegen
            $sqlDoc = "
                INSERT INTO `documents` 
                    (`title`, `description`, `doc_type`, `status`, `document_date`, `valid_from`, `valid_until`, `reference_number`, `visibility`, `created_by`, `updated_by`, `created_at`, `updated_at`)
                VALUES 
                    (:title, :description, :doc_type, :status, :doc_date, :valid_from, :valid_until, :ref_num, :visibility, :created_by, :created_by, NOW(), NOW())
            ";

            DB::execute($sqlDoc, [
                'title'       => $title,
                'description' => $description,
                'doc_type'    => $docType,
                'status'      => $status,
                'doc_date'    => $documentDate,
                'valid_from'  => $validFrom,
                'valid_until' => $validUntil,
                'ref_num'     => $referenceNumber,
                'visibility'  => $visibility,
                'created_by'  => $userId,
            ]);

            $documentId = (int) DB::lastInsertId();

            // 2. Version 1 anlegen
            $sqlVer = "
                INSERT INTO `document_versions`
                    (`document_id`, `version_number`, `original_filename`, `storage_path`, `mime_type`, `file_size`, `file_hash`, `change_notes`, `created_by`, `created_at`)
                VALUES
                    (:doc_id, 1, :orig_name, :storage_path, :mime_type, :file_size, :file_hash, :notes, :created_by, NOW())
            ";

            DB::execute($sqlVer, [
                'doc_id'       => $documentId,
                'orig_name'    => (string) ($fileInfo['original_filename'] ?? 'file'),
                'storage_path' => (string) ($fileInfo['storage_path'] ?? ''),
                'mime_type'    => (string) ($fileInfo['mime_type'] ?? 'application/octet-stream'),
                'file_size'    => (int) ($fileInfo['file_size'] ?? 0),
                'file_hash'    => (string) ($fileInfo['file_hash'] ?? ''),
                'notes'        => 'Erstversion (v1)',
                'created_by'   => $userId,
            ]);

            // 3. Tags verknüpfen
            if (!empty($data['tags']) && is_array($data['tags'])) {
                self::syncTags($documentId, $data['tags']);
            }

            // 4. Optionale initiale Objekt-Verknüpfung (Eigentümerportal)
            if (!empty($data['target_type']) && !empty($data['target_id'])) {
                self::addRelation(
                    $documentId,
                    (string) $data['target_type'],
                    (string) $data['target_id'],
                    isset($data['target_label']) ? (string) $data['target_label'] : null,
                    isset($data['relation_role']) ? (string) $data['relation_role'] : 'attachment'
                );
            }

            // 5. Audit Log erfassen
            self::logActivity($documentId, 'create', [
                'title'       => $title,
                'doc_type'    => $docType,
                'file_name'   => $fileInfo['original_filename'] ?? '',
                'file_size'   => $fileInfo['file_size'] ?? 0,
            ], $userId);

            return $documentId;
        } catch (\Throwable $e) {
            error_log('DocumentRepository::createDocument Fehler: ' . $e->getMessage());
            throw new RuntimeException('Dokument konnte nicht in der Datenbank gespeichert werden: ' . $e->getMessage());
        }
    }

    /**
     * Erstellt eine neue Version für ein bestehendes Dokument.
     *
     * @param int $documentId
     * @param array<string, mixed> $fileInfo
     * @param string|null $changeNotes
     * @param int|null $userId
     * @return int Neue Versionsnummer
     */
    public static function addVersion(int $documentId, array $fileInfo, ?string $changeNotes = null, ?int $userId = null): int
    {
        if (!self::isTableCreated() || $documentId <= 0) {
            throw new RuntimeException('Ungültiges Dokument oder Datenbanktabelle nicht vorhanden.');
        }

        try {
            // Nächste Versionsnummer ermitteln
            $row = DB::fetchOne(
                "SELECT COALESCE(MAX(`version_number`), 0) + 1 AS next_v FROM `document_versions` WHERE `document_id` = :doc_id",
                ['doc_id' => $documentId]
            );
            $nextVersion = (int) ($row['next_v'] ?? 2);

            $sqlVer = "
                INSERT INTO `document_versions`
                    (`document_id`, `version_number`, `original_filename`, `storage_path`, `mime_type`, `file_size`, `file_hash`, `change_notes`, `created_by`, `created_at`)
                VALUES
                    (:doc_id, :v_num, :orig_name, :storage_path, :mime_type, :file_size, :file_hash, :notes, :created_by, NOW())
            ";

            DB::execute($sqlVer, [
                'doc_id'       => $documentId,
                'v_num'        => $nextVersion,
                'orig_name'    => (string) ($fileInfo['original_filename'] ?? 'file'),
                'storage_path' => (string) ($fileInfo['storage_path'] ?? ''),
                'mime_type'    => (string) ($fileInfo['mime_type'] ?? 'application/octet-stream'),
                'file_size'    => (int) ($fileInfo['file_size'] ?? 0),
                'file_hash'    => (string) ($fileInfo['file_hash'] ?? ''),
                'notes'        => $changeNotes ?: 'Version v' . $nextVersion,
                'created_by'   => $userId,
            ]);

            // Dokument-Aktualisierungszeitpunkt auffrischen
            DB::execute(
                "UPDATE `documents` SET `updated_by` = :uid, `updated_at` = NOW() WHERE `id` = :id",
                ['uid' => $userId, 'id' => $documentId]
            );

            // Audit Log
            self::logActivity($documentId, 'version_add', [
                'version'      => $nextVersion,
                'file_name'    => $fileInfo['original_filename'] ?? '',
                'change_notes' => $changeNotes,
            ], $userId);

            return $nextVersion;
        } catch (\Throwable $e) {
            error_log('DocumentRepository::addVersion Fehler: ' . $e->getMessage());
            throw new RuntimeException('Version konnte nicht angelegt werden: ' . $e->getMessage());
        }
    }

    /**
     * Aktualisiert die Metadaten eines Dokuments.
     */
    public static function updateDocument(int $id, array $data, ?int $userId = null): bool
    {
        if (!self::isTableCreated() || $id <= 0) {
            return false;
        }

        try {
            $title = trim((string) ($data['title'] ?? ''));
            if ($title === '') {
                throw new RuntimeException('Der Dokumenttitel darf nicht leer sein.');
            }

            $sql = "
                UPDATE `documents` SET
                    `title`            = :title,
                    `description`      = :description,
                    `doc_type`         = :doc_type,
                    `status`           = :status,
                    `document_date`    = :doc_date,
                    `valid_from`       = :valid_from,
                    `valid_until`      = :valid_until,
                    `reference_number` = :ref_num,
                    `visibility`       = :visibility,
                    `updated_by`       = :updated_by,
                    `updated_at`       = NOW()
                WHERE `id` = :id
            ";

            DB::execute($sql, [
                'title'       => $title,
                'description' => isset($data['description']) ? trim((string) $data['description']) : null,
                'doc_type'    => (string) ($data['doc_type'] ?? 'MISC'),
                'status'      => (string) ($data['status'] ?? 'active'),
                'doc_date'    => !empty($data['document_date']) ? (string) $data['document_date'] : null,
                'valid_from'  => !empty($data['valid_from']) ? (string) $data['valid_from'] : null,
                'valid_until' => !empty($data['valid_until']) ? (string) $data['valid_until'] : null,
                'ref_num'     => !empty($data['reference_number']) ? trim((string) $data['reference_number']) : null,
                'visibility'  => (string) ($data['visibility'] ?? 'internal'),
                'updated_by'  => $userId,
                'id'          => $id,
            ]);

            if (isset($data['tags']) && is_array($data['tags'])) {
                self::syncTags($id, $data['tags']);
            }

            self::logActivity($id, 'update', ['title' => $title], $userId);

            return true;
        } catch (\Throwable $e) {
            error_log('DocumentRepository::updateDocument Fehler: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Löscht ein Dokument (soft delete = archiviert oder dauerhaft).
     */
    public static function deleteDocument(int $id, bool $permanent = false, ?int $userId = null): bool
    {
        if (!self::isTableCreated() || $id <= 0) {
            return false;
        }

        try {
            if (!$permanent) {
                // Soft-Delete: In Archiv / Papierkorb verschieben
                DB::execute(
                    "UPDATE `documents` SET `status` = 'archived', `updated_by` = :uid, `updated_at` = NOW() WHERE `id` = :id",
                    ['uid' => $userId, 'id' => $id]
                );
                self::logActivity($id, 'archive', ['reason' => 'Archiviert durch Benutzer'], $userId);
                return true;
            }

            // Permanentes Löschen: physische Dateien im Speicher löschen
            $versions = self::getVersions($id);
            foreach ($versions as $v) {
                $path = (string) ($v['storage_path'] ?? '');
                if ($path !== '' && file_exists($path)) {
                    @unlink($path);
                }
            }

            DB::execute("DELETE FROM `documents` WHERE `id` = :id", ['id' => $id]);
            return true;
        } catch (\Throwable $e) {
            error_log('DocumentRepository::deleteDocument Fehler: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // TAGS & SCHLAGWORTE
    // =========================================================================

    /**
     * Liefert alle Tags mit Anzahl zugewiesener Dokumente.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getTags(): array
    {
        if (!self::isTableCreated()) {
            return [];
        }

        try {
            $sql = "
                SELECT 
                    t.*,
                    COUNT(dta.`document_id`) AS document_count
                FROM `document_tags` t
                LEFT JOIN `document_tag_assignments` dta ON dta.`tag_id` = t.`id`
                GROUP BY t.`id`
                ORDER BY t.`name` ASC
            ";

            return DB::fetchAll($sql);
        } catch (\Throwable $e) {
            error_log('DocumentRepository::getTags Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Liefert alle Tags für ein einzelnes Dokument.
     *
     * @param int $documentId
     * @return array<int, array<string, mixed>>
     */
    public static function getTagsForDocument(int $documentId): array
    {
        if (!self::isTableCreated() || $documentId <= 0) {
            return [];
        }

        try {
            $sql = "
                SELECT t.* 
                FROM `document_tags` t
                JOIN `document_tag_assignments` dta ON dta.`tag_id` = t.`id`
                WHERE dta.`document_id` = :doc_id
                ORDER BY t.`name` ASC
            ";

            return DB::fetchAll($sql, ['doc_id' => $documentId]);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Synchronisiert die Tag-Zuweisung eines Dokuments.
     *
     * @param int $documentId
     * @param array<int|string> $tagIds
     */
    public static function syncTags(int $documentId, array $tagIds): void
    {
        if (!self::isTableCreated() || $documentId <= 0) {
            return;
        }

        try {
            DB::execute("DELETE FROM `document_tag_assignments` WHERE `document_id` = :doc_id", ['doc_id' => $documentId]);

            $cleanIds = array_filter(array_map('intval', $tagIds), static fn($id) => $id > 0);
            if (!empty($cleanIds)) {
                $insertSql = "INSERT IGNORE INTO `document_tag_assignments` (`document_id`, `tag_id`) VALUES ";
                $rows = [];
                $params = ['doc_id' => $documentId];
                $i = 0;
                foreach ($cleanIds as $tagId) {
                    $paramName = 'tag_' . $i;
                    $rows[] = "(:doc_id, :{$paramName})";
                    $params[$paramName] = $tagId;
                    $i++;
                }
                $insertSql .= implode(', ', $rows);
                DB::execute($insertSql, $params);
            }
        } catch (\Throwable $e) {
            error_log('DocumentRepository::syncTags Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Erstellt einen neuen Tag oder liefert einen bestehenden Tag nach Name.
     */
    public static function findOrCreateTag(string $name, string $color = '#0d6efd'): ?array
    {
        if (!self::isTableCreated()) {
            return null;
        }

        $cleanName = trim($name);
        if ($cleanName === '') {
            return null;
        }

        $slug = preg_replace('/[^a-zA-Z0-9_\-]+/', '-', mb_strtolower($cleanName)) ?? 'tag';
        $slug = trim($slug, '-');

        try {
            $existing = DB::fetchOne("SELECT * FROM `document_tags` WHERE `slug` = :slug OR `name` = :name LIMIT 1", [
                'slug' => $slug,
                'name' => $cleanName,
            ]);

            if ($existing) {
                return $existing;
            }

            DB::execute(
                "INSERT INTO `document_tags` (`name`, `slug`, `color`, `created_at`) VALUES (:name, :slug, :color, NOW())",
                [
                    'name'  => $cleanName,
                    'slug'  => $slug,
                    'color' => $color,
                ]
            );

            $id = (int) DB::lastInsertId();
            return DB::fetchOne("SELECT * FROM `document_tags` WHERE `id` = :id LIMIT 1", ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('DocumentRepository::findOrCreateTag Fehler: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Löscht einen Tag.
     */
    public static function deleteTag(int $tagId): bool
    {
        if (!self::isTableCreated() || $tagId <= 0) {
            return false;
        }

        try {
            DB::execute("DELETE FROM `document_tags` WHERE `id` = :id", ['id' => $tagId]);
            return true;
        } catch (\Throwable $e) {
            error_log('DocumentRepository::deleteTag Fehler: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // EIGENTÜMERPORTAL-RELATIONEN (Properties, Units, Cases)
    // =========================================================================

    /**
     * Lädt alle Verknüpfungen für ein Dokument.
     *
     * @param int $documentId
     * @return array<int, array<string, mixed>>
     */
    public static function getRelations(int $documentId): array
    {
        if (!self::isTableCreated() || $documentId <= 0) {
            return [];
        }

        try {
            $sql = "SELECT * FROM `document_relations` WHERE `document_id` = :doc_id ORDER BY `target_type` ASC, `created_at` ASC";
            return DB::fetchAll($sql, ['doc_id' => $documentId]);
        } catch (\Throwable $e) {
            error_log('DocumentRepository::getRelations Fehler: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verknüpft ein Dokument mit einem Zielobjekt (z.B. Liegenschaft, Einheit, Vorgang).
     */
    public static function addRelation(
        int $documentId,
        string $targetType,
        string $targetId,
        ?string $targetLabel = null,
        string $relationRole = 'attachment'
    ): int {
        if (!self::isTableCreated() || $documentId <= 0) {
            return 0;
        }

        try {
            $sql = "
                INSERT INTO `document_relations` 
                    (`document_id`, `target_type`, `target_id`, `target_label`, `relation_role`, `created_at`)
                VALUES 
                    (:doc_id, :target_type, :target_id, :target_label, :relation_role, NOW())
            ";

            DB::execute($sql, [
                'doc_id'        => $documentId,
                'target_type'   => $targetType,
                'target_id'     => $targetId,
                'target_label'  => $targetLabel,
                'relation_role' => $relationRole,
            ]);

            return (int) DB::lastInsertId();
        } catch (\Throwable $e) {
            error_log('DocumentRepository::addRelation Fehler: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Entfernt eine Verknüpfung.
     */
    public static function removeRelation(int $relationId): bool
    {
        if (!self::isTableCreated() || $relationId <= 0) {
            return false;
        }

        try {
            DB::execute("DELETE FROM `document_relations` WHERE `id` = :id", ['id' => $relationId]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Lädt alle Dokumente für ein bestimmtes Portal-Objekt (z.B. für Liegenschaft '10', Einheit '4b', Vorgang '2024-001').
     *
     * @param string $targetType z. B. 'property', 'unit', 'case'
     * @param string $targetId
     * @param string|null $visibilityFilter z. B. 'owner_portal'
     * @return array<int, array<string, mixed>>
     */
    public static function getDocumentsForTarget(string $targetType, string $targetId, ?string $visibilityFilter = null): array
    {
        if (!self::isTableCreated()) {
            return [];
        }

        try {
            $params = [
                'target_type' => $targetType,
                'target_id'   => $targetId,
            ];

            $visSql = '';
            if ($visibilityFilter !== null && $visibilityFilter !== '') {
                $visSql = "AND d.`visibility` IN (:visibility, 'public')";
                $params['visibility'] = $visibilityFilter;
            }

            $sql = "
                SELECT 
                    d.*,
                    dr.`relation_role`,
                    dr.`target_label`,
                    dv.`version_number` AS latest_version,
                    dv.`original_filename`,
                    dv.`mime_type`,
                    dv.`file_size`
                FROM `document_relations` dr
                JOIN `documents` d ON d.`id` = dr.`document_id`
                LEFT JOIN `document_versions` dv ON dv.`document_id` = d.`id` AND dv.`version_number` = (
                    SELECT MAX(sub_v.`version_number`) FROM `document_versions` sub_v WHERE sub_v.`document_id` = d.`id`
                )
                WHERE dr.`target_type` = :target_type 
                  AND dr.`target_id` = :target_id
                  AND d.`status` = 'active'
                  {$visSql}
                ORDER BY d.`document_date` DESC, d.`created_at` DESC
            ";

            return DB::fetchAll($sql, $params);
        } catch (\Throwable $e) {
            error_log('DocumentRepository::getDocumentsForTarget Fehler: ' . $e->getMessage());
            return [];
        }
    }

    // =========================================================================
    // AUDIT LOG & STATISTIKEN
    // =========================================================================

    /**
     * Protokolliert eine Aktion im Audit-Trail.
     */
    public static function logActivity(int $documentId, string $action, ?array $details = null, ?int $userId = null): void
    {
        if (!self::isTableCreated() || $documentId <= 0) {
            return;
        }

        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $detailsJson = $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;

            $sql = "
                INSERT INTO `document_activity_logs` 
                    (`document_id`, `user_id`, `action`, `details`, `ip_address`, `created_at`)
                VALUES 
                    (:doc_id, :uid, :action, :details, :ip, NOW())
            ";

            DB::execute($sql, [
                'doc_id'  => $documentId,
                'uid'     => $userId,
                'action'  => $action,
                'details' => $detailsJson,
                'ip'      => $ip,
            ]);
        } catch (\Throwable $e) {
            // Unkritisches Logging darf den Hauptablauf nicht blockieren
        }
    }

    /**
     * Lädt den Audit-Trail eines Dokuments.
     *
     * @param int $documentId
     * @return array<int, array<string, mixed>>
     */
    public static function getActivityLogs(int $documentId): array
    {
        if (!self::isTableCreated() || $documentId <= 0) {
            return [];
        }

        try {
            $sql = "
                SELECT 
                    al.*,
                    u.`name` AS user_name,
                    u.`email` AS user_email
                FROM `document_activity_logs` al
                LEFT JOIN `users` u ON u.`id` = al.`user_id`
                WHERE al.`document_id` = :doc_id
                ORDER BY al.`created_at` DESC
                LIMIT 50
            ";

            return DB::fetchAll($sql, ['doc_id' => $documentId]);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Liefert aggregierte Kennzahlen für das Paperless-Dashboard.
     *
     * @return array{total_documents: int, active_documents: int, total_size_bytes: int, total_tags: int, by_type: array<string, int>}
     */
    public static function getStats(): array
    {
        if (!self::isTableCreated()) {
            return [
                'total_documents'  => 0,
                'active_documents' => 0,
                'total_size_bytes' => 0,
                'total_tags'       => 0,
                'by_type'          => [],
            ];
        }

        try {
            $totalDocs = (int) (DB::fetchOne("SELECT COUNT(*) AS c FROM `documents`")['c'] ?? 0);
            $activeDocs = (int) (DB::fetchOne("SELECT COUNT(*) AS c FROM `documents` WHERE `status` = 'active'")['c'] ?? 0);
            $totalSize = (int) (DB::fetchOne("SELECT COALESCE(SUM(`file_size`), 0) AS s FROM `document_versions`")['s'] ?? 0);
            $totalTags = (int) (DB::fetchOne("SELECT COUNT(*) AS c FROM `document_tags`")['c'] ?? 0);

            $typesRows = DB::fetchAll("SELECT `doc_type`, COUNT(*) AS cnt FROM `documents` GROUP BY `doc_type` ORDER BY cnt DESC");
            $byType = [];
            foreach ($typesRows as $row) {
                $byType[(string) $row['doc_type']] = (int) $row['cnt'];
            }

            return [
                'total_documents'  => $totalDocs,
                'active_documents' => $activeDocs,
                'total_size_bytes' => $totalSize,
                'total_tags'       => $totalTags,
                'by_type'          => $byType,
            ];
        } catch (\Throwable $e) {
            return [
                'total_documents'  => 0,
                'active_documents' => 0,
                'total_size_bytes' => 0,
                'total_tags'       => 0,
                'by_type'          => [],
            ];
        }
    }

    /**
     * Hilfsmethode: Lädt Tags für eine Liste von Dokument-IDs in einem einzigen Query.
     *
     * @param array<int> $documentIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private static function getTagsForMultipleDocuments(array $documentIds): array
    {
        $cleanIds = array_filter(array_map('intval', $documentIds), static fn($id) => $id > 0);
        if (empty($cleanIds)) {
            return [];
        }

        try {
            $inList = implode(',', $cleanIds);
            $sql = "
                SELECT dta.`document_id`, t.*
                FROM `document_tag_assignments` dta
                JOIN `document_tags` t ON t.`id` = dta.`tag_id`
                WHERE dta.`document_id` IN ({$inList})
                ORDER BY t.`name` ASC
            ";

            $rows = DB::fetchAll($sql);
            $map = [];
            foreach ($rows as $row) {
                $docId = (int) $row['document_id'];
                $map[$docId][] = $row;
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
