<?php
namespace App\Models;

class Invoice extends BaseModel {
    public function list(): array {
        return $this->all('billing_invoices', 'due_date ASC, id DESC');
    }

    public function get(int $id): ?array {
        return $this->find('billing_invoices', $id);
    }

    public function create(array $data): int {
        $invoiceNumber = trim($data['invoice_number'] ?? 'INV-' . date('YmdHis'));
        $stmt = $this->db->prepare('INSERT INTO billing_invoices (company_id, invoice_number, plan_name, amount, invoice_status, due_date, paid_at, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            $invoiceNumber,
            trim($data['plan_name'] ?? 'Growth'),
            (float) ($data['amount'] ?? 0),
            trim($data['invoice_status'] ?? 'Draft'),
            ($data['due_date'] ?? '') ?: null,
            ($data['paid_at'] ?? '') ?: null,
            trim($data['notes'] ?? ''),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare('UPDATE billing_invoices SET invoice_number=?, plan_name=?, amount=?, invoice_status=?, due_date=?, paid_at=?, notes=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([
            trim($data['invoice_number'] ?? 'INV-' . date('YmdHis')),
            trim($data['plan_name'] ?? 'Growth'),
            (float) ($data['amount'] ?? 0),
            trim($data['invoice_status'] ?? 'Draft'),
            ($data['due_date'] ?? '') ?: null,
            ($data['paid_at'] ?? '') ?: null,
            trim($data['notes'] ?? ''),
            $id,
            current_company_id(),
        ]);
    }

    public function delete(int $id): void {
        $this->deleteRecord('billing_invoices', $id);
    }

    public function ensureStarterInvoice(): void {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM billing_invoices WHERE company_id = ?');
        $stmt->execute([current_company_id()]);
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }
        $this->create([
            'invoice_number' => 'INV-' . date('Ymd') . '-001',
            'plan_name' => 'Growth',
            'amount' => 299,
            'invoice_status' => 'Pending',
            'due_date' => date('Y-m-d', strtotime('+14 days')),
            'notes' => 'Starter invoice seeded during install.'
        ]);
    }
}
