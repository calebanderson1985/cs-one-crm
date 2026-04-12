<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\AiLog;
use App\Models\Client;
use App\Models\Commission;
use App\Models\Deal;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\OnboardingItem;
use App\Models\OutboundMessage;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkflowRun;
use App\Services\WorkflowEngine;

class DashboardController {
    public function __construct(private \PDO $db) {}

    public function index(): void {
        Auth::requirePermission('dashboard', 'view');
        $clients = (new Client($this->db))->list();
        $leads = (new Lead($this->db))->list();
        $deals = (new Deal($this->db))->list();
        $tasks = (new Task($this->db))->list();
        $commissions = (new Commission($this->db))->list();
        $users = (new User($this->db))->list();
        $notificationsUnread = (new Notification($this->db))->unreadCount();
        $workflowRuns = (new WorkflowRun($this->db))->recent();
        $outboundQueue = array_slice((new OutboundMessage($this->db))->list(), 0, 8);
        $aiLogs = (new AiLog($this->db))->list(6);
        $workflowQueue = (new WorkflowEngine($this->db))->queueSnapshot(8);
        $subscription = (new Subscription($this->db))->getCurrent();
        $invoices = (new Invoice($this->db))->list();
        $onboardingPercent = (new OnboardingItem($this->db))->completionPercent();

        $openDealValue = 0.0;
        $pipeline = [];
        foreach ($deals as $deal) {
            if (!in_array($deal['stage'], ['Closed Won', 'Closed Lost'], true)) {
                $openDealValue += (float) $deal['amount'];
            }
            $stage = $deal['stage'] ?: 'Unknown';
            if (!isset($pipeline[$stage])) {
                $pipeline[$stage] = ['stage' => $stage, 'total' => 0, 'value_total' => 0];
            }
            $pipeline[$stage]['total']++;
            $pipeline[$stage]['value_total'] += (float) $deal['amount'];
        }
        usort($pipeline, fn ($a, $b) => $b['total'] <=> $a['total']);

        $stats = [
            'clients' => count($clients),
            'leads' => count($leads),
            'deals' => count($deals),
            'open_deal_value' => $openDealValue,
            'tasks_open' => count(array_filter($tasks, fn ($task) => ($task['status'] ?? '') !== 'Completed')),
            'commissions_due' => array_sum(array_map(fn ($row) => ($row['payout_status'] ?? '') !== 'Paid' ? (float) $row['amount'] : 0.0, $commissions)),
            'users' => count($users),
            'notifications_unread' => $notificationsUnread,
            'workflow_queue' => count($workflowQueue),
            'outbound_queue' => count(array_filter($outboundQueue, fn ($row) => in_array(($row['send_status'] ?? ''), ['Queued', 'Retry'], true))),
            'ai_activity' => count($aiLogs),
            'subscription_status' => $subscription['subscription_status'] ?? 'Trial',
            'subscription_plan' => $subscription['plan_name'] ?? 'Growth',
            'subscription_mrr' => (float) ($subscription['monthly_amount'] ?? 0),
            'onboarding_percent' => $onboardingPercent,
            'invoice_open_total' => array_sum(array_map(fn ($row) => ($row['invoice_status'] ?? '') !== 'Paid' ? (float) $row['amount'] : 0.0, $invoices)),
        ];

        $recentLeads = array_slice($leads, 0, 5);
        $recentInvoices = array_slice($invoices, 0, 5);
        View::render('dashboard/index', compact('stats', 'pipeline', 'recentLeads', 'workflowRuns', 'outboundQueue', 'workflowQueue', 'aiLogs', 'subscription', 'recentInvoices'));
    }
}
