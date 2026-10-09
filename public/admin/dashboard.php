<?php

require_once __DIR__.'/../../includes/bootstrap.php';

$u = require_role('ADMIN');

$stmt = db()->query(
    'SELECT
        a.id,
        a.activity,
        a.description,
        a.ip_address,
        a.created_at,
        a.actor_snapshot,
        u.username,
        u.full_name
     FROM activity_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.id DESC
     LIMIT 20'
);

$activities = $stmt->fetchAll();

render_start('Admin dashboard');
?>

<p class="lede">
    Welcome, <?= e($u['name']) ?>
    <span class="badge admin">ADMIN</span>
</p>

<p>
    Server authorization confirms your administrator role.
</p>

<form method="post" action="/logout.php">
    <?= csrf_field() ?>
    <button class="button secondary">
        Sign out
    </button>
</form>

<h2>Recent Activity</h2>

<?php if (!$activities): ?>

    <p>No activity has been recorded yet.</p>

<?php else: ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Activity</th>
                    <th>Description</th>
                    <th>IP address</th>
                    <th>Date / time</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach ($activities as $activity): ?>

                <tr>
                    <td>
                        <?= e(
                            $activity['actor_snapshot']
                            ?: (
                                $activity['username']
                                ?: $activity['full_name']
                                ?: 'Unknown'
                            )
                        ) ?>
                    </td>

                    <td>
                        <?= e($activity['activity']) ?>
                    </td>

                    <td>
                        <?= e($activity['description'] ?? '') ?>
                    </td>

                    <td>
                        <?= e($activity['ip_address'] ?? '') ?>
                    </td>

                    <td>
                        <?= e($activity['created_at']) ?>
                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>
    </div>

<?php endif; ?>

<?php render_end();
