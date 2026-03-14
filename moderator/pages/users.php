<?php
if (!defined('in_nia_app')) exit;
global $db;
$pre = $db->prefix();

$sub = admin_subsection();
$id = (int) admin_segment(2);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $uid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($action === 'create' && isset($_POST['username'], $_POST['email'], $_POST['password'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $name = trim($_POST['name'] ?? $username);
        $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : USER_GROUP_DEFAULT;
        if ($username !== '' && $email !== '' && strlen($password) >= 4) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->query("INSERT INTO {$pre}users (username, name, email, password, group_id) VALUES (?, ?, ?, ?, ?)", [$username, $name, $email, $hash, $group_id]);
            redirect(admin_url('users'));
        }
    }
    if ($uid > 0) {
        if ($action === 'delete') {
            $db->query("DELETE FROM {$pre}users WHERE id = ?", [$uid]);
            redirect(admin_url('users'));
        }
        if ($action === 'save') {
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $group_id = isset($_POST['group_id']) ? (int) $_POST['group_id'] : USER_GROUP_DEFAULT;
            if ($name !== '' && $email !== '') {
                $db->query("UPDATE {$pre}users SET name = ?, email = ?, group_id = ? WHERE id = ?", [$name, $email, $group_id, $uid]);
                if (isset($_POST['password']) && strlen($_POST['password']) >= 4) {
                    $db->query("UPDATE {$pre}users SET password = ? WHERE id = ?", [password_hash($_POST['password'], PASSWORD_DEFAULT), $uid]);
                }
                redirect(admin_url('users/edit/' . $uid));
            }
        }
    }
}

if ($sub === 'edit' && $id > 0) {
    $item = $db->fetch("SELECT * FROM {$pre}users WHERE id = ?", [$id]);
    if (!$item) { redirect(admin_url('users')); }
    $item = admin_normalize_row($item, ['id', 'username', 'name', 'email', 'group_id', 'avatar', 'created_at', 'last_login']);
    $admin_title = 'Edit user';
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
    $groups_raw = $db->fetchAll("SELECT id, name, slug FROM {$pre}users_groups ORDER BY id");
    $groups = admin_normalize_rows($groups_raw ?: [], ['id', 'name', 'slug']);
    ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?php echo _e($item->username); ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?php echo _e($item->name); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo _e($item->email); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Group</label>
                    <select class="form-select" name="group_id">
                        <?php foreach ($groups as $g) { ?><option value="<?php echo (int) $g->id; ?>" <?php echo (int) $item->group_id === (int) $g->id ? 'selected' : ''; ?>><?php echo _e($g->name); ?></option><?php } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">New password (leave blank to keep)</label>
                    <input type="password" class="form-control" name="password" placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="<?php echo _e(admin_url('users')); ?>" class="btn btn-outline-secondary">Back to list</a>
                <a href="<?php echo _e(profile_url($item->username ?? '', $item->id ?? 0)); ?>" class="btn btn-outline-secondary" target="_blank">View profile</a>
            </form>
        </div>
    </div>
    <form method="post" class="d-inline" onsubmit="return confirm('Delete this user and all their content?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
        <button type="submit" class="btn btn-danger">Delete user</button>
    </form>
    <?php
    include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
    return;
}

$admin_title = 'Users';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, (int) ($_GET['p'] ?? 1));
$per = 20;
$offset = ($page - 1) * $per;
$where = "1=1";
$params = [];
if ($q !== '') {
    $where .= " AND (u.username LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$total = admin_fetch_count($db->fetch("SELECT COUNT(*) AS c FROM {$pre}users u WHERE " . $where, $params));
$params[] = $per;
$params[] = $offset;
$items = $db->fetchAll("SELECT u.id AS id, u.username AS username, u.name AS name, u.email AS email, u.last_login AS last_login, u.created_at AS created_at, g.name AS group_name FROM {$pre}users u LEFT JOIN {$pre}users_groups g ON g.id = u.group_id WHERE " . $where . " ORDER BY u.created_at DESC LIMIT ? OFFSET ?", $params);
$items = admin_normalize_rows($items, ['id', 'username', 'name', 'email', 'last_login', 'created_at', 'group_name']);

include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'header.php';
?>

<div class="card mb-4">
    <div class="card-header">Create user</div>
    <div class="card-body">
        <form method="post" class="row g-2">
            <input type="hidden" name="action" value="create">
            <div class="col-md-2"><input type="text" class="form-control" name="username" placeholder="Username" required></div>
            <div class="col-md-2"><input type="text" class="form-control" name="name" placeholder="Display name"></div>
            <div class="col-md-2"><input type="email" class="form-control" name="email" placeholder="Email" required></div>
            <div class="col-md-2"><input type="password" class="form-control" name="password" placeholder="Password" required minlength="4"></div>
            <div class="col-md-2">
                <select class="form-select" name="group_id">
                    <?php $create_groups = admin_normalize_rows($db->fetchAll("SELECT id, name FROM {$pre}users_groups ORDER BY id"), ['id', 'name']); foreach ($create_groups as $g) { ?><option value="<?php echo (int) $g->id; ?>"><?php echo _e($g->name); ?></option><?php } ?>
                </select>
            </div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary">Create</button></div>
        </form>
    </div>
</div>

<form method="get" class="mb-3 d-flex gap-2">
    <input type="search" class="form-control" name="q" value="<?php echo _e($q); ?>" placeholder="Search users">
    <button type="submit" class="btn btn-primary">Search</button>
</form>
<table class="table table-sm table-striped">
    <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Email</th><th>Group</th><th>Last login</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $u) { ?>
    <tr>
        <td><?php echo (int) ($u->id ?? 0); ?></td>
        <td><a href="<?php echo _e(admin_url('users/edit/' . ($u->id ?? 0))); ?>"><?php echo _e($u->username ?? '-'); ?></a></td>
        <td><?php echo _e($u->name ?? '-'); ?></td>
        <td><?php echo _e($u->email ?? '-'); ?></td>
        <td><?php
            $gclass = 'bg-secondary';
            if(strtolower($u->group_name) == 'admin') $gclass = 'bg-danger';
            if(strtolower($u->group_name) == 'moderator') $gclass = 'bg-primary';
        ?><span class="badge <?php echo $gclass; ?>"><?php echo _e($u->group_name ?? '-'); ?></span></td>
        <td><?php echo _e($u->last_login ?? '—'); ?></td>
        <td>
            <a href="<?php echo _e(admin_url('users/edit/' . ($u->id ?? 0))); ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <a href="<?php echo _e(profile_url($u->username ?? '', $u->id ?? 0)); ?>" class="btn btn-sm btn-outline-secondary" target="_blank">View</a>
        </td>
    </tr>
    <?php } ?>
    <?php if (empty($items)) { ?><tr><td colspan="7" class="text-muted text-center py-4">No users found.</td></tr><?php } ?>
    </tbody>
</table>
<?php
$total_pages = $total ? (int) ceil($total / $per) : 1;
if ($total_pages > 1) {
    echo '<nav><ul class="pagination">';
    for ($i = 1; $i <= $total_pages; $i++) {
        $url = admin_url('users') . '?p=' . $i . ($q !== '' ? '&q=' . rawurlencode($q) : '');
        echo '<li class="page-item' . ($i === $page ? ' active' : '') . '"><a class="page-link" href="' . _e($url) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}
include dirname(__DIR__) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'footer.php';
