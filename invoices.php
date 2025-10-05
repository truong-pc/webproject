<?php
$title = 'Invoices';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/invoice_functions.php';

safeSessionStart();
$currentUser = getCurrentUser();
if (!isLoggedIn() || ($currentUser['role'] ?? null) !== 'admin') {
    http_response_code(403);
    exit('Access Denied: Only Admin can manage invoices.');
}

$invoices = getAllInvoices();
$paymentHistory = getPaymentHistory();

// Helper to get badge class based on status
function getStatusBadgeClass(string $status): string
{
    switch ($status) {
        case 'paid':
            return 'bg-success';
        case 'partial':
            return 'bg-warning text-dark';
        case 'pending':
            return 'bg-info text-dark';
        case 'overdue':
            return 'bg-danger';
        case 'cancelled':
            return 'bg-secondary';
        default:
            return 'bg-light text-dark';
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'Origin Driving School' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,">
</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <div class="pagebar py-3 mb-3">
        <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
            <div>
                <h1 class="title h4">Invoice Management</h1>
                <p class="subtitle mb-0">Manage all student invoices and payments.</p>
            </div>
        </div>
    </div>

    <main class="container my-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Invoice List</h5>
                <p class="text-muted mb-3">View all invoices, payment status and balances.</p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)) : ?>
                                <tr>
                                    <td colspan="9" class="text-center">No invoices found.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($invoices as $invoice) : ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($invoice['id']) ?></td>
                                        <td><?= htmlspecialchars($invoice['student_name']) ?></td>
                                        <td><?= htmlspecialchars($invoice['course_title']) ?></td>
                                        <td><?= htmlspecialchars($invoice['issue_date']) ?></td>
                                        <td><?= htmlspecialchars($invoice['due_date']) ?></td>
                                        <td>$<?= number_format($invoice['total'], 2) ?></td>
                                        <td>$<?= number_format($invoice['balance'], 2) ?></td>
                                        <td><span class="badge <?= getStatusBadgeClass($invoice['status']) ?>"><?= str_replace('_', ' ', htmlspecialchars($invoice['status'])) ?></span></td>
                                        <td class="text-end">
                                            <?php if ($invoice['status'] !== 'paid' && $invoice['status'] !== 'cancelled') : ?>
                                                <button class="btn btn-sm btn-primary add-payment-btn" 
                                                        data-invoice-id="<?= $invoice['id'] ?>" 
                                                        data-total="<?= $invoice['total'] ?>" 
                                                        data-balance="<?= $invoice['balance'] ?>"
                                                        data-notes="<?= htmlspecialchars($invoice['notes']) ?>"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#paymentModal">
                                                    <i class="bi bi-credit-card"></i> Add Payment
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Payment History</h5>
                <p class="text-muted mb-3">A log of all payments received.</p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Invoice ID</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($paymentHistory)) : ?>
                                <tr>
                                    <td colspan="6" class="text-center">No payment history found.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($paymentHistory as $payment) : ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($payment['id']) ?></td>
                                        <td>#<?= htmlspecialchars($payment['invoice_id']) ?></td>
                                        <td><?= htmlspecialchars($payment['student_name']) ?></td>
                                        <td>$<?= number_format($payment['amount'], 2) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($payment['method'])) ?></td>
                                        <td><?= htmlspecialchars($payment['paid_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Add Payment for Invoice #<span id="modalInvoiceId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="paymentForm">
                    <div class="modal-body">
                        <div id="payment-error" class="alert alert-danger d-none"></div>
                        <input type="hidden" id="invoice_id" name="invoice_id">
                        
                        <div class="row mb-3">
                            <div class="col"><strong>Total:</strong> $<span id="modalTotal"></span></div>
                            <div class="col"><strong>Paid:</strong> $<span id="modalPaid"></span></div>
                            <div class="col"><strong>Due:</strong> $<span id="modalDue"></span></div>
                        </div>

                        <div class="mb-3">
                            <label for="amount" class="form-label">Payment Amount</label>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="method" class="form-label">Payment Method</label>
                            <select class="form-select" id="method" name="method" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="transfer">Bank Transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="ref_no" class="form-label">Reference No. (Optional)</label>
                            <input type="text" class="form-control" id="ref_no" name="ref_no">
                        </div>
                         <div class="mb-3">
                            <label for="notes" class="form-label">Invoice Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const paymentModal = document.getElementById('paymentModal');
        const paymentForm = document.getElementById('paymentForm');
        const errorDiv = document.getElementById('payment-error');

        paymentModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const invoiceId = button.getAttribute('data-invoice-id');
            const total = parseFloat(button.getAttribute('data-total'));
            const balance = parseFloat(button.getAttribute('data-balance'));
            const notes = button.getAttribute('data-notes');
            const due = total - balance;

            // Populate modal
            document.getElementById('modalInvoiceId').textContent = invoiceId;
            document.getElementById('invoice_id').value = invoiceId;
            document.getElementById('modalTotal').textContent = total.toFixed(2);
            document.getElementById('modalPaid').textContent = balance.toFixed(2);
            document.getElementById('modalDue').textContent = due.toFixed(2);
            
            const amountInput = document.getElementById('amount');
            amountInput.value = due.toFixed(2);
            amountInput.max = due.toFixed(2);
            amountInput.min = 0.01;

            document.getElementById('notes').value = notes;
            
            // Clear previous errors
            errorDiv.classList.add('d-none');
            errorDiv.textContent = '';
        });

        paymentForm.addEventListener('submit', function(event) {
            event.preventDefault();
            errorDiv.classList.add('d-none');

            const formData = {
                invoice_id: document.getElementById('invoice_id').value,
                amount: parseFloat(document.getElementById('amount').value),
                method: document.getElementById('method').value,
                ref_no: document.getElementById('ref_no').value,
                notes: document.getElementById('notes').value
            };

            fetch('handlers/payment_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide modal and reload page to show updated data
                    const modal = bootstrap.Modal.getInstance(paymentModal);
                    modal.hide();
                    location.reload();
                } else {
                    // Show error message
                    errorDiv.textContent = data.message || 'An unknown error occurred.';
                    errorDiv.classList.remove('d-none');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorDiv.textContent = 'A network error occurred. Please try again.';
                errorDiv.classList.remove('d-none');
            });
        });
    });
    </script>
</body>
</html>

