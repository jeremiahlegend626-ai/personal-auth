<?php
declare(strict_types=1);

function log_activity(
    string $activity,
    ?int $userId = null,
    ?string $description = null
): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $actorSnapshot = null;

        if ($userId !== null) {
            $userStmt = db()->prepare(
                'SELECT username, email
                 FROM users
                 WHERE id = ?
                 LIMIT 1'
            );

            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();

            if ($user) {
                $actorSnapshot = $user['username'] . ' <' . $user['email'] . '>';
            }
        }

        $stmt = db()->prepare(
            'INSERT INTO activity_logs
            (user_id, actor_snapshot, activity, description, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $userId,
            $actorSnapshot,
            $activity,
            $description,
            $ip,
            $userAgent
        ]);
    } catch (Throwable $e) {
        // Activity logging must never break authentication.
    }
}
