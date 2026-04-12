<?php
namespace App\Models;

class OnboardingItem extends BaseModel {
    public function list(): array {
        return $this->all('onboarding_items', 'sort_order ASC, id ASC');
    }

    public function get(int $id): ?array {
        return $this->find('onboarding_items', $id);
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare('INSERT INTO onboarding_items (company_id, category_name, item_key, item_label, owner_role, is_complete, completed_at, sort_order, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,NOW(),NOW())');
        $isComplete = !empty($data['is_complete']) ? 1 : 0;
        $stmt->execute([
            current_company_id(),
            trim($data['category_name'] ?? 'Go Live'),
            trim($data['item_key'] ?? strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) ($data['item_label'] ?? 'checklist_item')))),
            trim($data['item_label'] ?? 'Checklist item'),
            trim($data['owner_role'] ?? 'admin'),
            $isComplete,
            $isComplete ? now() : null,
            (int) ($data['sort_order'] ?? 100),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $isComplete = !empty($data['is_complete']) ? 1 : 0;
        $stmt = $this->db->prepare('UPDATE onboarding_items SET category_name=?, item_key=?, item_label=?, owner_role=?, is_complete=?, completed_at=?, sort_order=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([
            trim($data['category_name'] ?? 'Go Live'),
            trim($data['item_key'] ?? strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) ($data['item_label'] ?? 'checklist_item')))),
            trim($data['item_label'] ?? 'Checklist item'),
            trim($data['owner_role'] ?? 'admin'),
            $isComplete,
            $isComplete ? (($data['completed_at'] ?? '') ?: now()) : null,
            (int) ($data['sort_order'] ?? 100),
            $id,
            current_company_id(),
        ]);
    }

    public function toggleComplete(int $id, bool $complete): void {
        $stmt = $this->db->prepare('UPDATE onboarding_items SET is_complete=?, completed_at=?, updated_at=NOW() WHERE id=? AND company_id=?');
        $stmt->execute([$complete ? 1 : 0, $complete ? now() : null, $id, current_company_id()]);
    }

    public function delete(int $id): void {
        $this->deleteRecord('onboarding_items', $id);
    }

    public function ensureDefaults(): void {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM onboarding_items WHERE company_id = ?');
        $stmt->execute([current_company_id()]);
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }
        $seed = [
            ['Foundation', 'install_complete', 'Complete system install', 'admin', 1],
            ['Branding', 'review_branding', 'Review branding and support settings', 'admin', 2],
            ['Communications', 'configure_email', 'Configure email provider', 'admin', 3],
            ['Communications', 'configure_sms', 'Configure SMS provider', 'admin', 4],
            ['Security', 'review_permissions', 'Review role permissions and team access', 'admin', 5],
            ['Operations', 'seed_first_team', 'Create managers and agents', 'admin', 6],
            ['Go Live', 'run_worker', 'Run background worker / cron', 'admin', 7],
            ['Go Live', 'import_clients', 'Import or create first clients and leads', 'manager', 8],
        ];
        foreach ($seed as [$category, $key, $label, $role, $sort]) {
            $this->create([
                'category_name' => $category,
                'item_key' => $key,
                'item_label' => $label,
                'owner_role' => $role,
                'sort_order' => $sort,
                'is_complete' => $key === 'install_complete',
            ]);
        }
    }

    public function completionPercent(): int {
        $items = $this->list();
        if (!$items) {
            return 0;
        }
        $complete = count(array_filter($items, fn ($row) => !empty($row['is_complete'])));
        return (int) round(($complete / max(1, count($items))) * 100);
    }
}
