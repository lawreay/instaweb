<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class FeedbackSubmission
{
    private PDO $database;

    public function __construct(?PDO $database = null)
    {
        $this->database = $database ?? Database::connection();
    }

    public function create(array $data): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO feedback_submissions
                (user_id, submitter_name, submitter_email, type, subject, message, page_url, status, email_status)
             VALUES
                (:user_id, :submitter_name, :submitter_email, :type, :subject, :message, :page_url, \'new\', :email_status)'
        );
        $statement->execute([
            'user_id' => $data['user_id'] ?: null,
            'submitter_name' => $data['submitter_name'],
            'submitter_email' => $data['submitter_email'] ?: null,
            'type' => $data['type'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'page_url' => $data['page_url'] ?: null,
            'email_status' => $data['email_status'] ?? 'pending',
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function all(string $status = '', string $type = ''): array
    {
        $sql = 'SELECT f.*, u.username
                FROM feedback_submissions f
                LEFT JOIN users u ON u.id = f.user_id
                WHERE 1 = 1';
        $params = [];
        if (in_array($status, ['new', 'in_progress', 'resolved', 'closed'], true)) {
            $sql .= ' AND f.status = :status';
            $params['status'] = $status;
        }
        if (in_array($type, ['problem', 'feature'], true)) {
            $sql .= ' AND f.type = :type';
            $params['type'] = $type;
        }
        $sql .= ' ORDER BY CASE f.status WHEN \'new\' THEN 0 WHEN \'in_progress\' THEN 1 ELSE 2 END, f.created_at DESC LIMIT 300';
        $statement = $this->database->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function updateStatus(int $id, string $status, string $adminNote = ''): bool
    {
        $statement = $this->database->prepare(
            'UPDATE feedback_submissions SET status = :status, admin_note = :admin_note WHERE id = :id'
        );
        $statement->execute(['status' => $status, 'admin_note' => $adminNote, 'id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function setEmailStatus(int $id, string $status): void
    {
        if (!in_array($status, ['pending', 'sent', 'failed', 'not_configured'], true)) {
            return;
        }
        $statement = $this->database->prepare('UPDATE feedback_submissions SET email_status = :email_status WHERE id = :id');
        $statement->execute(['email_status' => $status, 'id' => $id]);
    }

    public function countNew(): int
    {
        return (int) $this->database->query("SELECT COUNT(*) FROM feedback_submissions WHERE status = 'new'")->fetchColumn();
    }
}
