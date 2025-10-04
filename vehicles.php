<?php
$title = 'Vehicles';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/vehicels_functions.php';

safeSessionStart();
$currentUser = getCurrentUser();
if (!isLoggedIn() || ($currentUser['role'] ?? null) !== 'admin') {
  http_response_code(403);
  exit('Access Denied: Only Admin can manage vehicles.');
}

$messages = ['success' => [], 'error' => []];
$selectedVehicle = null;
$hasPostErrors = false;

// Handle form submissions for add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_vehicle') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $data = [
            'plate_no' => $_POST['plate_no'] ?? '',
            'model' => $_POST['model'] ?? '',
            'status' => $_POST['status'] ?? 'active',
            'branch_id' => $_POST['branch_id'] ?? null,
        ];

        if ($vehicleId > 0) { // Update existing vehicle
            $result = updateVehicle($vehicleId, $data);
            if ($result['success']) {
                header('Location: vehicles.php?msg=updated');
                exit;
            }
            $messages['error'][] = $result['error'] ?? 'Failed to update vehicle.';
        } else { // Add new vehicle
            $result = addVehicle($data);
            if ($result['success']) {
                header('Location: vehicles.php?msg=added');
                exit;
            }
            $messages['error'][] = $result['error'] ?? 'Failed to add vehicle.';
        }
        
        // If there was an error, retain submitted data to re-populate the form
        $hasPostErrors = true;
        $selectedVehicle = array_merge($data, ['id' => $vehicleId]);

    } elseif ($action === 'delete_vehicle') {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
        $result = deleteVehicle($vehicleId);
        if ($result['success']) {
            header('Location: vehicles.php?msg=deleted');
            exit;
        }
        $messages['error'][] = $result['error'] ?? 'Failed to delete vehicle.';
        $hasPostErrors = true;
    }
}

// Display success/error messages from GET params
if (!$hasPostErrors && isset($_GET['msg'])) {
    $msgMap = [
        'added' => 'Vehicle added successfully.',
        'updated' => 'Vehicle updated successfully.',
        'deleted' => 'Vehicle deleted successfully.',
    ];
    if (isset($msgMap[$_GET['msg']])) {
        $messages['success'][] = $msgMap[$_GET['msg']];
    }
}

// Fetch data for display
$vehicles = getVehicles();
$branches = getBranches();
$statusOptions = ['active' => 'Active', 'maintenance' => 'Maintenance', 'using' => 'Using'];

// Helper to get badge class for status
$badgeClass = fn(string $status): string => match ($status) {
    'active' => 'bg-success',
    'maintenance' => 'bg-warning text-dark',
    'using' => 'bg-info text-dark',
    default => 'bg-secondary',
};

$page_title = 'Vehicle Management';
$page_subtitle = 'Manage the fleet of training vehicles.';
$primary_action = [
  'href' => '#',
  'text' => '+ Add Vehicle',
  'class' => 'btn btn-success',
  'attrs' => 'data-bs-toggle="modal" data-bs-target="#vehicleModal"'
];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Origin Driving School' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,\">
  </head>
  <body>
    <?php include __DIR__.'/partials/header.php'; ?>

    <div class="pagebar py-3 mb-3">
      <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
        <div>
          <h1 class="title h4"><?= htmlspecialchars($page_title) ?></h1>
          <p class="subtitle mb-0"><?= htmlspecialchars($page_subtitle) ?></p>
        </div>
        <div class="actions d-flex gap-2">
          <button type="button" class="<?= htmlspecialchars($primary_action['class']) ?>" <?= $primary_action['attrs'] ?>>
            <?= htmlspecialchars($primary_action['text']) ?>
          </button>
        </div>
      </div>
    </div>

    <main class="container my-3">
        <div class="card"><div class="card-body">
            <h5 class="card-title">Vehicle List</h5>
            <p class="text-muted mb-3">Showing <?= count($vehicles) ?> vehicles.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Plate No.</th><th>Model</th><th>Branch</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($vehicles)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No vehicles found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($vehicles as $vehicle): ?>
                            <tr>
                                <td><?= htmlspecialchars($vehicle['plate_no']) ?></td>
                                <td><?= htmlspecialchars($vehicle['model']) ?></td>
                                <td><?= htmlspecialchars($vehicle['branch_name'] ?? 'N/A') ?></td>
                                <td><span class="badge <?= $badgeClass($vehicle['status']) ?>"><?= htmlspecialchars($statusOptions[$vehicle['status']] ?? 'Unknown') ?></span></td>
                                <td class="text-end">
                                    <button 
                                        type="button" 
                                        class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#vehicleModal"
                                        data-vehicle-id="<?= $vehicle['id'] ?>"
                                        data-plate-no="<?= htmlspecialchars($vehicle['plate_no']) ?>"
                                        data-model="<?= htmlspecialchars($vehicle['model']) ?>"
                                        data-status="<?= htmlspecialchars($vehicle['status']) ?>"
                                        data-branch-id="<?= htmlspecialchars($vehicle['branch_id'] ?? '') ?>"
                                    >Edit</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $vehicle['id'] ?>, '<?= htmlspecialchars($vehicle['plate_no']) ?>')">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div></div>
    </main>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="vehicleModal" tabindex="-1" aria-labelledby="vehicleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="vehicleModalLabel">Add/Edit Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_vehicle">
                    <input type="hidden" name="vehicle_id" id="modalVehicleId" value="<?= htmlspecialchars($selectedVehicle['id'] ?? '') ?>">
                    
                    <div class="mb-3">
                        <label for="modalPlateNo" class="form-label">Plate Number</label>
                        <input type="text" class="form-control" id="modalPlateNo" name="plate_no" value="<?= htmlspecialchars($selectedVehicle['plate_no'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="modalModel" class="form-label">Make/Model</label>
                        <input type="text" class="form-control" id="modalModel" name="model" value="<?= htmlspecialchars($selectedVehicle['model'] ?? '') ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="modalStatus" class="form-label">Status</label>
                            <select class="form-select" id="modalStatus" name="status">
                                <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= $value ?>" <?= (($selectedVehicle['status'] ?? 'active') === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="modalBranch" class="form-label">Branch</label>
                            <select class="form-select" id="modalBranch" name="branch_id">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?= $branch['id'] ?>" <?= (($selectedVehicle['branch_id'] ?? '') == $branch['id']) ? 'selected' : '' ?>><?= htmlspecialchars($branch['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="post" class="d-none">
        <input type="hidden" name="action" value="delete_vehicle">
        <input type="hidden" name="vehicle_id" id="deleteVehicleId">
    </form>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
      <div id="notificationToast" class="toast align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <!-- Message will be injected here -->
          </div>
          <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    </div>

    <?php include __DIR__.'/partials/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const vehicleModal = document.getElementById('vehicleModal');
        const modalTitle = vehicleModal.querySelector('.modal-title');
        const modalVehicleId = vehicleModal.querySelector('#modalVehicleId');
        const modalPlateNo = vehicleModal.querySelector('#modalPlateNo');
        const modalModel = vehicleModal.querySelector('#modalModel');
        const modalStatus = vehicleModal.querySelector('#modalStatus');
        const modalBranch = vehicleModal.querySelector('#modalBranch');

        vehicleModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const vehicleId = button.getAttribute('data-vehicle-id');

            if (vehicleId) { // Editing existing vehicle
                modalTitle.textContent = 'Edit Vehicle';
                modalVehicleId.value = vehicleId;
                modalPlateNo.value = button.getAttribute('data-plate-no');
                modalModel.value = button.getAttribute('data-model');
                modalStatus.value = button.getAttribute('data-status');
                modalBranch.value = button.getAttribute('data-branch-id');
            } else { // Adding new vehicle
                modalTitle.textContent = 'Add Vehicle';
                modalVehicleId.value = '';
                vehicleModal.querySelector('form').reset();
            }
        });

        <?php
        $all_messages = array_merge(
            array_map(fn($m) => ['type' => 'success', 'text' => $m], $messages['success']),
            array_map(fn($m) => ['type' => 'error', 'text' => $m], $messages['error'])
        );
        if (!empty($all_messages)):
            $notification = $all_messages[0]; // Show first message
        ?>
        const toastEl = document.getElementById('notificationToast');
        const toastBodyEl = toastEl.querySelector('.toast-body');
        const toast = new bootstrap.Toast(toastEl);

        const notificationType = '<?= $notification['type'] ?>';
        const notificationMessage = '<?= addslashes(htmlspecialchars($notification['text'])) ?>';

        if (notificationType === 'success') {
            toastEl.classList.remove('text-bg-danger');
            toastEl.classList.add('text-bg-success');
        } else {
            toastEl.classList.remove('text-bg-success');
            toastEl.classList.add('text-bg-danger');
        }
        
        toastBodyEl.textContent = notificationMessage;
        toast.show();
        <?php endif; ?>

        <?php if ($hasPostErrors): ?>
        // If form submission failed, re-open the modal
        const errorModal = new bootstrap.Modal(vehicleModal);
        errorModal.show();
        <?php endif; ?>
    });

    function confirmDelete(id, plate) {
        if (confirm(`Are you sure you want to delete vehicle with plate number "${plate}"?`)) {
            document.getElementById('deleteVehicleId').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
    </script>
  </body>
</html>