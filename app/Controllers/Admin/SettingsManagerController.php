<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\FeatureToggleService;
use App\Services\SettingService;

class SettingsManagerController extends Controller
{
    public function index(Request $request): void
    {
        $features = FeatureToggleService::all();
        $this->view('admin.pages.settings.index', [
            'title' => 'Settings', 'features' => $features,
            'site' => [
                'name'        => SettingService::get('site', 'name', ''),
                'tagline'     => SettingService::get('site', 'tagline', ''),
                'description' => SettingService::get('site', 'description', ''),
            ],
            'age_gate' => [
                'enabled'     => (bool) SettingService::get('age_gate', 'enabled', true),
                'expiry_days' => (int) SettingService::get('age_gate', 'expiry_days', 30),
            ],
            'player' => [
                'guest_countdown' => (int) SettingService::get('player', 'guest_countdown', 5),
            ],
            'iframe' => [
                'allowlist_extra' => (string) SettingService::get('iframe', 'allowlist_extra', ''),
            ],
            'seo' => [
                'default_title_suffix' => (string) SettingService::get('seo', 'default_title_suffix', ''),
            ],
        ]);
    }

    public function update(Request $request): void
    {
        $this->validateCsrf($request);
        $admin = Auth::admin();
        $adminId = (int) $admin['id'];
        SettingService::set('site',     'name',                trim((string) $request->post('site_name', '')),    'string', $adminId);
        SettingService::set('site',     'tagline',             trim((string) $request->post('site_tagline', '')), 'string', $adminId);
        SettingService::set('site',     'description',         trim((string) $request->post('site_description', '')), 'text', $adminId);
        SettingService::set('age_gate', 'enabled',             $request->post('age_gate_enabled') ? '1' : '0',    'bool',   $adminId);
        SettingService::set('age_gate', 'expiry_days',         max(1, (int) $request->post('age_gate_expiry_days', 30)), 'int', $adminId);
        SettingService::set('player',   'guest_countdown',     max(0, (int) $request->post('player_guest_countdown', 5)), 'int', $adminId);
        SettingService::set('iframe',   'allowlist_extra',     trim((string) $request->post('iframe_allowlist_extra', '')), 'text', $adminId);
        SettingService::set('seo',      'default_title_suffix',trim((string) $request->post('seo_default_title_suffix', '')), 'string', $adminId);
        AuditService::log('settings.update');
        Session::instance()->flash('success', 'Settings saved.');
        $this->redirect(admin_url('settings'));
    }

    public function updateFeatures(Request $request): void
    {
        $this->validateCsrf($request);
        $admin = Auth::admin();
        $features = FeatureToggleService::all();
        $checked  = (array) ($request->post('features') ?? []);
        foreach ($features as $f) {
            $isOn = in_array((string) $f['feature_key'], $checked, true);
            FeatureToggleService::set((string) $f['feature_key'], $isOn, (int) $admin['id']);
        }
        AuditService::log('settings.features_update');
        Session::instance()->flash('success', 'Feature toggles updated.');
        $this->redirect(admin_url('settings'));
    }
}
