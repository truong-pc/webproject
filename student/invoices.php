<?php 
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/invoice_functions.php';

$title = 'Student Invoices';

safeSessionStart();
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}
$studentId = $_SESSION['user_id'];

$invoices = getInvoicesByStudentId($studentId);
$payments = getPaymentsByStudentId($studentId);

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
    <link href="../assets/css/theme_green.css" rel="stylesheet">
    <link rel="icon" href="data:,\">
  </head>
  <body>
    <?php include __DIR__.'/../partials/header.php'; ?>

    <div class="pagebar py-3 mb-3">
        <div class="container">
            <h1 class="title h4">My Invoices</h1>
            <p class="subtitle mb-0">View your invoice history and payment status.</p>
        </div>
    </div>

    <main class="container my-3">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">My Invoices</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Course</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)) : ?>
                                <tr>
                                    <td colspan="7" class="text-center">You have no invoices.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($invoices as $invoice) : ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($invoice['id']) ?></td>
                                        <td><?= htmlspecialchars($invoice['course_title']) ?></td>
                                        <td><?= htmlspecialchars($invoice['issue_date']) ?></td>
                                        <td><?= htmlspecialchars($invoice['due_date']) ?></td>
                                        <td>$<?= number_format($invoice['total'], 2) ?></td>
                                        <td>$<?= number_format($invoice['balance'], 2) ?></td>
                                        <td><span class="badge <?= getStatusBadgeClass($invoice['status']) ?>"><?= str_replace('_', ' ', htmlspecialchars($invoice['status'])) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">My Payment History</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Payment ID</th>
                                <th>Invoice ID</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)) : ?>
                                <tr>
                                    <td colspan="5" class="text-center">You have no payment history.</td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($payments as $payment) : ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($payment['id']) ?></td>
                                        <td>#<?= htmlspecialchars($payment['invoice_id']) ?></td>
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

    <?php include __DIR__.'/../partials/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>