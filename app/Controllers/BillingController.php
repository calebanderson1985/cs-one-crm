<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Invoice;
use App\Models\Subscription;

class BillingController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('billing', 'view');
        $subscriptionModel = new Subscription($this->db);
        $invoiceModel = new Invoice($this->db);
        $subscriptionModel->ensureDefault();
        $invoiceModel->ensureStarterInvoice();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? '';
            if ($action === 'create_subscription') {
                Auth::requirePermission('billing', 'create');
                $id = $subscriptionModel->create($_POST);
                audit_log($this->db, 'billing', 'create_subscription', $id, 'Subscription created');
                flash('success', 'Subscription created.');
            }
            if ($action === 'update_subscription') {
                Auth::requirePermission('billing', 'edit');
                $id = (int) $_POST['id'];
                $subscriptionModel->update($id, $_POST);
                audit_log($this->db, 'billing', 'update_subscription', $id, 'Subscription updated');
                flash('success', 'Subscription updated.');
            }
            if ($action === 'delete_subscription') {
                Auth::requirePermission('billing', 'delete');
                $id = (int) $_POST['id'];
                $subscriptionModel->delete($id);
                audit_log($this->db, 'billing', 'delete_subscription', $id, 'Subscription deleted');
                flash('success', 'Subscription deleted.');
            }
            if ($action === 'create_invoice') {
                Auth::requirePermission('billing', 'create');
                $id = $invoiceModel->create($_POST);
                audit_log($this->db, 'billing', 'create_invoice', $id, 'Invoice created');
                flash('success', 'Invoice created.');
            }
            if ($action === 'update_invoice') {
                Auth::requirePermission('billing', 'edit');
                $id = (int) $_POST['id'];
                $invoiceModel->update($id, $_POST);
                audit_log($this->db, 'billing', 'update_invoice', $id, 'Invoice updated');
                flash('success', 'Invoice updated.');
            }
            if ($action === 'delete_invoice') {
                Auth::requirePermission('billing', 'delete');
                $id = (int) $_POST['id'];
                $invoiceModel->delete($id);
                audit_log($this->db, 'billing', 'delete_invoice', $id, 'Invoice deleted');
                flash('success', 'Invoice deleted.');
            }
            redirect('index.php?page=billing');
        }

        $subscriptions = $subscriptionModel->list();
        $invoices = $invoiceModel->list();
        $editSubscription = isset($_GET['subscription_id']) ? $subscriptionModel->get((int) $_GET['subscription_id']) : null;
        $editInvoice = isset($_GET['invoice_id']) ? $invoiceModel->get((int) $_GET['invoice_id']) : null;
        $currentSubscription = $subscriptionModel->getCurrent();
        View::render('admin/billing', compact('subscriptions', 'invoices', 'editSubscription', 'editInvoice', 'currentSubscription'));
    }
}
