<?php
namespace App\Models;

class Subscription extends BaseModel {
    public function list(): array {
        return $this->all('subscriptions', 'updated_at DESC');
    }

    public function getCurrent(): ?array {
        $stmt = $this->db->prepare('SELECT * FROM subscriptions WHERE company_id = ? ORDER BY updated_at DESC, id DESC LIMIT 1');
        $stmt->execute([current_company_id()]);
        return $stmt->fetch() ?: null;
    }

    public function get(int $id): ?array {
        return $this->find('subscriptions', $id);
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO subscriptions (company_id, plan_name, billing_cycle, subscription_status, seat_count, monthly_amount, renewal_date, trial_ends_at, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            current_company_id(),
            trim($data['plan_name'] ?? 'Growth'),
            trim($data['billing_cycle'] ?? 'Monthly'),
            trim($data['subscription_status'] ?? 'Trial'),
            (int) ($data['seat_count'] ?? 5),
            (float) ($data['monthly_amount'] ?? 0),
            ($data['renewal_date'] ?? '') ?: null,
            ($data['trial_ends_at'] ?? '') ?: null,
            trim($data['notes'] ?? ''),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare('UPDATE subscriptions SET plan_name=?, billing_cycle=?, subscription_status=?, seat_count=?, monthly_amount=?, renewal_date=?, trial_ends_at=?, notes=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([
            trim($data['plan_name'] ?? 'Growth'),
            trim($data['billing_cycle'] ?? 'Monthly'),
            trim($data['subscription_status'] ?? 'Trial'),
            (int) ($data['seat_count'] ?? 5),
            (float) ($data['monthly_amount'] ?? 0),
            ($data['renewal_date'] ?? '') ?: null,
            ($data['trial_ends_at'] ?? '') ?: null,
            trim($data['notes'] ?? ''),
            $id,
            current_company_id(),
        ]);
    }

    public function delete(int $id): void {
        $this->deleteRecord('subscriptions', $id);
    }

    public function ensureDefault(): void {
        if ($this->getCurrent()) {
            return;
        }
        $this->create([
            'plan_name' => 'Growth',
            'billing_cycle' => 'Monthly',
            'subscription_status' => 'Trial',
            'seat_count' => 5,
            'monthly_amount' => 299,
            'renewal_date' => date('Y-m-d', strtotime('+30 days')),
            'trial_ends_at' => date('Y-m-d', strtotime('+14 days')),
            'notes' => 'Starter commercial plan seeded during install.'
        ]);
    }
}
