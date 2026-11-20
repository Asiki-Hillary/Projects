<?php
require 'config.php';

roles(['admin']); // Administrator only
verify_csrf();

$msg = '';

$validRoles = ['admin', 'manager', 'sales', 'storekeeper', 'procurement'];

$isAdmin = $_SESSION['role'] === 'admin';
$isManager = $_SESSION['role'] === 'manager';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // CREATE USER: ADMIN ONLY
    if ($action === 'create' && $isAdmin) {

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        if (
            $username === '' ||
            strlen($password) < 8 ||
            !in_array($role, $validRoles, true)
        ) {
            $msg = 'Use a username, password of at least 8 characters, and a valid role.';

        } else {
            try {
                $s = $pdo->prepare(
                    'INSERT INTO users(username,password_hash,role)
                     VALUES(?,?,?)'
                );

                $s->execute([
                    $username,
                    password_hash($password, PASSWORD_DEFAULT),
                    $role
                ]);

                $msg = 'User account created successfully.';

            } catch (PDOException $e) {
                $msg = 'Could not create user. The username may already exist.';
            }
        }
    }

    // RESET PASSWORD: ADMIN ONLY
    elseif ($action === 'password' && $isAdmin) {

        $id = positive_int($_POST['user_id'] ?? 0);
        $password = $_POST['new_password'] ?? '';

        if (!$id || strlen($password) < 8) {
            $msg = 'Select a user and enter a password of at least 8 characters.';

        } elseif ($id === (int)$_SESSION['user_id']) {
            $msg = 'Use a separate password-change process for your own account.';

        } else {
            $s = $pdo->prepare(
                'UPDATE users SET password_hash=? WHERE user_id=?'
            );

            $s->execute([
                password_hash($password, PASSWORD_DEFAULT),
                $id
            ]);

            $msg = $s->rowCount()
                ? 'User password updated.'
                : 'Password was not changed. Check the selected account.';
        }
    }

    // DELETE USER: ADMIN AND MANAGER
    elseif ($action === 'delete' && ($isAdmin || $isManager)) {

        $id = positive_int($_POST['user_id'] ?? 0);

        if (!$id) {
            $msg = 'Invalid user selected.';

        } elseif ($id === (int)$_SESSION['user_id']) {
            $msg = 'You cannot delete your own account.';

        } else {

            $check = $pdo->prepare(
                'SELECT user_id, username, role
                 FROM users WHERE user_id=?'
            );

            $check->execute([$id]);
            $target = $check->fetch();

            if (!$target) {
                $msg = 'User account not found.';

            } elseif ($isManager && $target['role'] === 'admin') {
                $msg = 'Managers cannot delete administrator accounts.';

            } else {
                try {

                    $delete = $pdo->prepare(
                        'DELETE FROM users WHERE user_id=?'
                    );

                    $delete->execute([$id]);

                    $msg = 'User account deleted successfully.';

                } catch (PDOException $e) {
                    $msg = 'Cannot delete this account because it has related records in the system.';
                }
            }
        }
    }

    else {
        $msg = 'You are not authorized to perform this action.';
    }
}


// GET REGISTERED USERS
$users = $pdo->query(
    'SELECT user_id, username, role, created_at
     FROM users ORDER BY user_id'
)->fetchAll();


page_header('User Administration');

if ($msg) {
    flash($msg, 'notice');
}


// CREATE ACCOUNT FORM: ADMIN ONLY
if ($isAdmin) {

    echo '
    <div class="panel">
        <h3>Create Employee Account</h3>

        <form method="post">

            <input type="hidden" name="csrf_token"
            value="' . h(csrf_token()) . '">

            <input type="hidden" name="action" value="create">

            <div class="formgrid">

                <label>Username
                    <input name="username" maxlength="50" required>
                </label>

                <label>Temporary Password
                    <input type="password" name="password"
                    minlength="8" required>
                </label>

                <label>Role
                    <select name="role" required>
                        <option value="">Choose role</option>
                        <option value="sales">Sales Attendant</option>
                        <option value="storekeeper">Storekeeper</option>
                        <option value="procurement">Procurement Officer</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Administrator</option>
                    </select>
                </label>

            </div>

            <button type="submit">Create Account</button>

        </form>
    </div>';
}


echo '
<h3>Registered Users</h3>

<div class="table-wrap">
<table>

<tr>
    <th>ID</th>
    <th>Username</th>
    <th>Role</th>
    <th>Created</th>';

if ($isAdmin) {
    echo '<th>Reset Password</th>';
}

echo '<th>Delete User</th></tr>';

foreach ($users as $u) {

    echo '
    <tr>
        <td>' . h($u['user_id']) . '</td>
        <td>' . h($u['username']) . '</td>
        <td>' . h(ucfirst($u['role'])) . '</td>
        <td>' . h($u['created_at']) . '</td>';

    // RESET PASSWORD: ADMIN ONLY
    if ($isAdmin) {

        echo '<td>';

        if ((int)$u['user_id'] !== (int)$_SESSION['user_id']) {

            echo '
            <form class="inlineform" method="post">

                <input type="hidden" name="csrf_token"
                value="' . h(csrf_token()) . '">

                <input type="hidden" name="action" value="password">

                <input type="hidden" name="user_id"
                value="' . h($u['user_id']) . '">

                <input type="password" name="new_password"
                minlength="8" placeholder="New password" required>

                <button type="submit">Reset</button>

            </form>';

        } else {
            echo 'Current account';
        }

        echo '</td>';
    }

    // DELETE USER: ADMIN AND MANAGER
    echo '<td>';

    $canDelete = (
        (int)$u['user_id'] !== (int)$_SESSION['user_id']
        &&
        (!$isManager || $u['role'] !== 'admin')
    );

    if ($canDelete) {

        echo '
        <form method="post"
        onsubmit="return confirm(\'Are you sure you want to delete this user account?\');">

            <input type="hidden" name="csrf_token"
            value="' . h(csrf_token()) . '">

            <input type="hidden" name="action" value="delete">

            <input type="hidden" name="user_id"
            value="' . h($u['user_id']) . '">

            <button type="submit">Delete</button>

        </form>';

    } else {
        echo 'Not permitted';
    }

    echo '</td></tr>';
}

echo '
</table>
</div>

<p class="muted">
    Administrators can create accounts, reset passwords and delete users.
    Managers can delete non-administrator accounts only.
</p>';

page_footer();
