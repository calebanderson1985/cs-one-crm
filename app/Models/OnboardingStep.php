<?php
namespace App\Models;

class OnboardingStep extends BaseModel {
    public function seedDefaults(): void {
        $existing = $this->db->prepare('SELECT COUNT(*) FROM onboarding_steps WHERE company_id = ?');
        $existing->execute([current_company_id()]);
        if ((int)$existing->fetchColumn() > 0) { return; }
        $steps = [
            ['brand_setup', 'Brand setup', 'Configure app name, support email, and company-facing branding.', 10],
            ['communications', 'Communications', 'Enter outbound email and SMS provider settings.', 20],
            ['permissions', 'Permissions', 'Review role matrix and confirm operational access.', 30],
            ['team_setup', 'Team setup', 'Create managers, agents, and any client portal users.', 40],
            ['api_tokens', 'API tokens', 'Generate scoped API credentials for integrations.', 50],
            ['launch_review', 'Launch review', 'Confirm billing, webhook, and worker settings before go-live.', 60],
        ];
        $stmt = $this->db->prepare('INSERT INTO onboarding_steps (company_id, step_key, title, description_text, is_complete, sort_order, completed_by, completed_at, created_at, updated_at) VALUES (?,?,?,?,0,?,?,NULL,NOW(),NOW())');
        foreach ($steps as [$key, $title, $desc, $sort]) {
            $stmt->execute([current_company_id(), $key, $title, $desc, $sort, current_user_id() ?: null]);
        }
    }

    public function list(): array {
        $this->seedDefaults();
        $stmt = $this->db->prepare('SELECT * FROM onboarding_steps WHERE company_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([current_company_id()]);
        return $stmt->fetchAll();
    }

    public function complete(int $id, bool $complete): void {
        $stmt = $this->db->prepare('UPDATE onboarding_steps SET is_complete = ?, completed_by = ?, completed_at = ?, updated_at = NOW() WHERE id = ? AND company_id = ?');
        $stmt->execute([$complete ? 1 : 0, current_user_id() ?: null, $complete ? now() : null, $id, current_company_id()]);
    }

    public function progress(): array {
        $steps = $this->list();
        $total = count($steps);
        $done = count(array_filter($steps, fn($s) => !empty($s['is_complete'])));
        return ['total' => $total, 'complete' => $done, 'percent' => $total ? (int)round(($done / $total) * 100) : 0];
    }
}
