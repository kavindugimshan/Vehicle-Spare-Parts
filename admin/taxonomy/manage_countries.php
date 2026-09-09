<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';

requireAdmin();

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Your session expired. Please try again.');
        redirect('admin/taxonomy/manage_countries.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $countryId = (int) ($_POST['country_id'] ?? 0);
        $name = trim($_POST['country_name'] ?? '');
        $code = strtoupper(trim($_POST['country_code'] ?? ''));
        $dutyRate = $_POST['import_duty_rate'] ?? '';

        if ($name === '') {
            $errors['country_name'] = 'Country name is required.';
        }
        if (!preg_match('/^[A-Z]{2,3}$/', $code)) {
            $errors['country_code'] = 'Enter a 2-3 letter country code.';
        }
        if (!is_numeric($dutyRate) || (float) $dutyRate < 0) {
            $errors['import_duty_rate'] = 'Enter a valid duty rate.';
        }

        if (empty($errors)) {
            if ($action === 'create') {
                $stmt = $db->prepare('INSERT INTO country (countryName, countryCode, importDutyRate) VALUES (?, ?, ?)');
                $stmt->execute([$name, $code, (float) $dutyRate]);
                setFlash('success', 'Country added.');
            } else {
                $stmt = $db->prepare('UPDATE country SET countryName = ?, countryCode = ?, importDutyRate = ? WHERE countryID = ?');
                $stmt->execute([$name, $code, (float) $dutyRate, $countryId]);
                setFlash('success', 'Country updated.');
            }
            redirect('admin/taxonomy/manage_countries.php');
        }
    } elseif ($action === 'delete') {
        $countryId = (int) ($_POST['country_id'] ?? 0);
        $countStmt = $db->prepare('SELECT COUNT(*) AS c FROM spare_part WHERE countryID = ?');
        $countStmt->execute([$countryId]);

        if ((int) $countStmt->fetch()['c'] > 0) {
            setFlash('error', 'This country still has parts assigned to it.');
        } else {
            $db->prepare('DELETE FROM country WHERE countryID = ?')->execute([$countryId]);
            setFlash('success', 'Country deleted.');
        }
        redirect('admin/taxonomy/manage_countries.php');
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editCountry = null;
if ($editId) {
    $stmt = $db->prepare('SELECT * FROM country WHERE countryID = ?');
    $stmt->execute([$editId]);
    $editCountry = $stmt->fetch();
}

$countries = $db->query('SELECT * FROM country ORDER BY countryName')->fetchAll();

$pageTitle = 'Manage Countries';
$pageCss = ['admin.css'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="container">
    <div class="adm-layout">
        <?php require __DIR__ . '/../../includes/admin_sidebar.php'; ?>

        <div class="adm-content">
            <h1>Manage Countries</h1>

            <div class="card mb-2">
                <h2 class="card-title"><?php echo $editCountry ? 'Edit Country' : 'Add Country'; ?></h2>
                <form method="post" action="<?php echo BASE_URL; ?>/admin/taxonomy/manage_countries.php" class="adm-inline-form" data-validate novalidate>
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="<?php echo $editCountry ? 'update' : 'create'; ?>">
                    <?php if ($editCountry): ?><input type="hidden" name="country_id" value="<?php echo (int) $editCountry['countryID']; ?>"><?php endif; ?>

                    <input type="text" name="country_name" class="form-control" placeholder="Country name" value="<?php echo e($editCountry['countryName'] ?? ''); ?>" required>
                    <input type="text" name="country_code" class="form-control" style="width:100px;" placeholder="Code" maxlength="3" value="<?php echo e($editCountry['countryCode'] ?? ''); ?>" required>
                    <input type="number" name="import_duty_rate" class="form-control" style="width:130px;" placeholder="Duty %" step="0.01" min="0" value="<?php echo e((string) ($editCountry['importDutyRate'] ?? '')); ?>" required>
                    <button type="submit" class="btn btn-primary"><?php echo $editCountry ? 'Save' : 'Add'; ?></button>
                    <?php if ($editCountry): ?><a class="btn btn-outline" href="<?php echo BASE_URL; ?>/admin/taxonomy/manage_countries.php">Cancel</a><?php endif; ?>
                </form>
                <?php foreach ($errors as $error): ?><p class="form-error"><?php echo e($error); ?></p><?php endforeach; ?>
            </div>

            <table class="table-plain">
                <thead><tr><th>Country</th><th>Code</th><th>Import Duty</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($countries as $country): ?>
                    <tr>
                        <td><?php echo e($country['countryName']); ?></td>
                        <td><?php echo e($country['countryCode']); ?></td>
                        <td><?php echo number_format((float) $country['importDutyRate'], 2); ?>%</td>
                        <td>
                            <a class="btn btn-sm btn-outline" href="<?php echo BASE_URL; ?>/admin/taxonomy/manage_countries.php?edit=<?php echo (int) $country['countryID']; ?>">Edit</a>
                            <form method="post" action="<?php echo BASE_URL; ?>/admin/taxonomy/manage_countries.php" style="display:inline;" data-confirm="Delete this country?">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="country_id" value="<?php echo (int) $country['countryID']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
