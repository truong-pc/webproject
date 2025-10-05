<?php
require_once __DIR__ . '/../config/connect_db.php';
require_once __DIR__ . '/notification_functions.php';

/**
 * Fetches all invoices with associated student and course information.
 *
 * @return array An array of all invoices.
 */
function getAllInvoices(): array
{
    $pdo = db();
    $sql = "SELECT 
                i.id,
                i.student_id,
                u.name AS student_name,
                c.title AS course_title,
                i.issue_date,
                i.due_date,
                i.total,
                i.balance,
                i.status,
                i.notes
            FROM invoices i
            JOIN users u ON i.student_id = u.id
            JOIN courses c ON i.course_id = c.id
            ORDER BY i.issue_date DESC, i.id DESC";
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching all invoices: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetches a single invoice by its ID.
 *
 * @param integer $invoiceId
 * @return array|false
 */
function getInvoiceById(int $invoiceId)
{
    $pdo = db();
    $sql = "SELECT * FROM invoices WHERE id = :id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $invoiceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching invoice by ID: " . $e->getMessage());
        return false;
    }
}

/**
 * Processes a new payment, updates the invoice, and creates notifications.
 *
 * @param array $data Payment data including: invoice_id, amount, method, ref_no, notes
 * @return array ['success' => bool, 'message' => string]
 */
function addPaymentAndNotify(array $data): array
{
    $pdo = db();

    // Sanitize and validate input
    $invoiceId = filter_var($data['invoice_id'], FILTER_VALIDATE_INT);
    $amount = filter_var($data['amount'], FILTER_VALIDATE_FLOAT);
    $method = htmlspecialchars($data['method']);
    $refNo = !empty($data['ref_no']) ? htmlspecialchars($data['ref_no']) : null;
    $notes = !empty($data['notes']) ? htmlspecialchars($data['notes']) : null;

    if (!$invoiceId || $amount === false || $amount <= 0) {
        return ['success' => false, 'message' => 'Invalid input data provided.'];
    }

    try {
        $pdo->beginTransaction();

        // 1. Get current invoice details
        $invoice = getInvoiceById($invoiceId);
        if (!$invoice) {
            throw new Exception("Invoice not found.");
        }

        // 2. Check if payment amount is valid
        $maxPayment = $invoice['total'] - $invoice['balance'];
        if ($amount > $maxPayment) {
            throw new Exception("Payment amount cannot exceed the remaining balance of " . number_format($maxPayment, 2));
        }

        // 3. Insert into payments table
        $paymentSql = "INSERT INTO payments (invoice_id, amount, method, ref_no) VALUES (:invoice_id, :amount, :method, :ref_no)";
        $paymentStmt = $pdo->prepare($paymentSql);
        $paymentStmt->execute([
            ':invoice_id' => $invoiceId,
            ':amount' => $amount,
            ':method' => $method,
            ':ref_no' => $refNo
        ]);

        // 4. Update invoice
        $newBalance = $invoice['balance'] + $amount;
        $newStatus = $invoice['status'];
        
        if ($newBalance >= $invoice['total']) {
            $newStatus = 'paid';
            $newBalance = $invoice['total']; // Ensure balance doesn't exceed total
        } else {
            $newStatus = 'partial';
        }

        $updateSql = "UPDATE invoices SET balance = :balance, status = :status, notes = :notes WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':balance' => $newBalance,
            ':status' => $newStatus,
            ':notes' => $notes,
            ':id' => $invoiceId
        ]);

        // 5. Create notification for the student
        $remainingBalance = $invoice['total'] - $newBalance;
        if ($newStatus === 'paid') {
            $title = "Payment Successful for Invoice #{$invoiceId}";
            $content = "Your payment of " . number_format($amount, 2) . " for invoice #{$invoiceId} has been successfully processed. The invoice is now fully paid. Thank you!";
        } else {
            $title = "Partial Payment Received for Invoice #{$invoiceId}";
            $content = "We have received your payment of " . number_format($amount, 2) . " for invoice #{$invoiceId}. The remaining balance is " . number_format($remainingBalance, 2) . ".";
        }
        createNotification($invoice['student_id'], $title, $content);

        $pdo->commit();
        return ['success' => true, 'message' => 'Payment successfully recorded. Invoice updated.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Payment processing error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
    }
}

/**
 * Fetches the complete payment history with student names.
 *
 * @return array An array of all payment records.
 */
function getPaymentHistory(): array
{
    $pdo = db();
    $sql = "SELECT 
                p.id,
                p.invoice_id,
                p.amount,
                p.method,
                p.paid_at,
                u.name AS student_name
            FROM payments p
            JOIN invoices i ON p.invoice_id = i.id
            JOIN users u ON i.student_id = u.id
            ORDER BY p.paid_at DESC";
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching payment history: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetches all invoices for a specific student.
 *
 * @param integer $studentId The ID of the student.
 * @return array An array of invoices for the student.
 */
function getInvoicesByStudentId(int $studentId): array
{
    $pdo = db();
    $sql = "SELECT 
                i.id,
                c.title AS course_title,
                i.issue_date,
                i.due_date,
                i.total,
                i.balance,
                i.status
            FROM invoices i
            JOIN courses c ON i.course_id = c.id
            WHERE i.student_id = :student_id
            ORDER BY i.issue_date DESC, i.id DESC";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching invoices for student ID {$studentId}: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetches the payment history for a specific student.
 *
 * @param integer $studentId The ID of the student.
 * @return array An array of payment records for the student.
 */
function getPaymentsByStudentId(int $studentId): array
{
    $pdo = db();
    $sql = "SELECT 
                p.id,
                p.invoice_id,
                p.amount,
                p.method,
                p.paid_at
            FROM payments p
            JOIN invoices i ON p.invoice_id = i.id
            WHERE i.student_id = :student_id
            ORDER BY p.paid_at DESC";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':student_id' => $studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching payment history for student ID {$studentId}: " . $e->getMessage());
        return [];
    }
}
?>
