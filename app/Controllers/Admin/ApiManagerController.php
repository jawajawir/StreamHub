<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\DoodstreamService;

class ApiManagerController extends Controller
{
    public function doodstream(Request $request): void
    {
        $cred = DoodstreamService::record();
        $logs = Database::getInstance()->fetchAll('SELECT * FROM doodstream_sync_logs ORDER BY created_at DESC LIMIT 20');
        $this->view('admin.pages.api.doodstream', ['title' => 'Doodstream API', 'cred' => $cred, 'logs' => $logs]);
    }

    public function saveDoodstreamKey(Request $request): void
    {
        $this->validateCsrf($request);
        $key = trim((string) $request->post('api_key', ''));
        if ($key === '') {
            Session::instance()->flash('error', 'API key cannot be empty.');
            $this->redirect(admin_url('api/doodstream')); return;
        }
        DoodstreamService::setApiKey($key, (int) Auth::admin()['id']);
        AuditService::log('doodstream.key_update');
        Session::instance()->flash('success', 'API key saved (encrypted).');
        $this->redirect(admin_url('api/doodstream'));
    }

    public function testDoodstream(Request $request): void
    {
        $this->validateCsrf($request);
        $result = DoodstreamService::testConnection();
        AuditService::log('doodstream.test', null, null, null, ['ok' => $result['ok'], 'message' => $result['message']]);
        Session::instance()->flash($result['ok'] ? 'success' : 'error', $result['message']);
        $this->redirect(admin_url('api/doodstream'));
    }
}
