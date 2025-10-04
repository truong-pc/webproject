<?php
/**
 * Vehicle-related database functions.
 */

/**
 * Fetch a paginated list of all vehicles with branch names.
 * @param int $limit Number of records to return.
 * @param int $offset Number of records to skip.
 * @return array List of vehicles.
 */
function getVehicles(int $limit = 100, int $offset = 0): array {
    $pdo = db();
    $sql = "SELECT 
                v.id, 
                v.plate_no, 
                v.model, 
                v.status, 
                v.branch_id,
                b.name AS branch_name
            FROM vehicles v
            LEFT JOIN branches b ON v.branch_id = b.id
            ORDER BY v.created_at DESC
            LIMIT :limit OFFSET :offset";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Log error in a real application
        return [];
    }
}

/**
 * Fetch details for a single vehicle.
 * @param int $vehicleId The ID of the vehicle.
 * @return array|null Vehicle data or null if not found.
 */
function getVehicleDetails(int $vehicleId): ?array {
    $pdo = db();
    $sql = "SELECT 
                v.id, 
                v.plate_no, 
                v.model, 
                v.status, 
                v.branch_id
            FROM vehicles v
            WHERE v.id = :id";
            
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $vehicleId, PDO::PARAM_INT);
        $stmt->execute();
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        return $vehicle ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Add a new vehicle to the database.
 * @param array $data Vehicle data (plate_no, model, status, branch_id).
 * @return array Result status with success/error message.
 */
function addVehicle(array $data): array {
    $pdo = db();
    $sql = "INSERT INTO vehicles (plate_no, model, status, branch_id) VALUES (:plate_no, :model, :status, :branch_id)";

    // Basic validation
    if (empty($data['plate_no']) || empty($data['model'])) {
        return ['success' => false, 'error' => 'Plate number and model are required.'];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':plate_no' => trim($data['plate_no']),
            ':model' => trim($data['model']),
            ':status' => $data['status'] ?? 'active',
            ':branch_id' => !empty($data['branch_id']) ? (int)$data['branch_id'] : null,
        ]);
        return ['success' => true, 'id' => $pdo->lastInsertId()];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., duplicate plate_no)
            return ['success' => false, 'error' => 'A vehicle with this plate number already exists.'];
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update an existing vehicle's information.
 * @param int $vehicleId The ID of the vehicle to update.
 * @param array $data New data for the vehicle.
 * @return array Result status.
 */
function updateVehicle(int $vehicleId, array $data): array {
    $pdo = db();
    $sql = "UPDATE vehicles SET 
                plate_no = :plate_no, 
                model = :model, 
                status = :status, 
                branch_id = :branch_id 
            WHERE id = :id";

    if (empty($data['plate_no']) || empty($data['model'])) {
        return ['success' => false, 'error' => 'Plate number and model are required.'];
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $vehicleId,
            ':plate_no' => trim($data['plate_no']),
            ':model' => trim($data['model']),
            ':status' => $data['status'] ?? 'active',
            ':branch_id' => !empty($data['branch_id']) ? (int)$data['branch_id'] : null,
        ]);
        return ['success' => true];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['success' => false, 'error' => 'A vehicle with this plate number already exists.'];
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete a vehicle from the database.
 * @param int $vehicleId The ID of the vehicle to delete.
 * @return array Result status.
 */
function deleteVehicle(int $vehicleId): array {
    $pdo = db();
    // Check for dependencies (e.g., scheduled lessons) before deleting.
    // For this example, we'll perform a direct delete.
    $sql = "DELETE FROM vehicles WHERE id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $vehicleId]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Vehicle not found or could not be deleted.'];
    } catch (PDOException $e) {
        // Foreign key constraint violation
        if ($e->getCode() === '23000') {
            return ['success' => false, 'error' => 'Cannot delete vehicle. It is currently assigned to one or more lessons.'];
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
