<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Services\FeatureToggleService;
use App\Services\MembershipService;
use App\Services\RateLimitService;

class MembershipController extends Controller
{
    public function index(Request $request): void
    {
        if (!FeatureToggleService::enabled('membership_page')) { \App\Core\Response::notFound(); return; }
        $plans = MembershipService::plans();
        $myRequest = null;
        if (Auth::check()) {
            $myRequest = Database::getInstance()->fetch(
                'SELECT mr.*, mp.code AS plan_code, mp.name AS plan_name FROM membership_requests mr
                 JOIN membership_plans mp ON mp.id = mr.requested_plan_id
                 WHERE mr.user_id = :u ORDER BY mr.created_at DESC LIMIT 1',
                [':u' => Auth::user()['id']]
            );
        }
        $this->view('frontend.pages.membership', [
            'title'      => 'Membership',
            'plans'      => $plans,
            'myRequest'  => $myRequest,
        ]);
    }

    public function requestUpgrade(Request $request): void
    {
        $this->validateCsrf($request);
        if (!FeatureToggleService::enabled('membership_page')) { \App\Core\Response::notFound(); return; }
        $code = (string) $request->post('plan', '');
        $plan = MembershipService::planByCode($code);
        if (!$plan || $plan['code'] === 'free') {
            Session::instance()->flash('error', 'Invalid plan.');
            $this->redirect('/membership'); return;
        }
        if (!RateLimitService::hit('membership_request', (string) hash_ip($request->ip()), 3600, 5)) {
            Session::instance()->flash('error', 'Too many requests. Try again later.');
            $this->redirect('/membership'); return;
        }
        $v = Validator::make($request->post())
            ->field('contact_name',  ['required', 'max:120'])
            ->field('contact_email', ['required', 'email', 'max:190'])
            ->field('message',       ['max:2000']);
        if ($v->fails()) {
            $this->flashErrors($v->errors(), $request->post());
            $this->redirect('/membership'); return;
        }
        Database::getInstance()->insert('membership_requests', [
            'user_id'           => Auth::user()['id'] ?? null,
            'requested_plan_id' => $plan['id'],
            'contact_name'      => trim((string) $request->post('contact_name')),
            'contact_email'     => trim((string) $request->post('contact_email')),
            'contact_phone'     => mb_substr(trim((string) $request->post('contact_phone', '')), 0, 80),
            'message'           => mb_substr(trim((string) $request->post('message', '')), 0, 2000),
            'status'            => 'new',
        ]);
        \App\Services\AuditService::log('membership.request_created', 'membership_request');
        Session::instance()->flash('success', 'Your request has been submitted. We will contact you shortly.');
        $this->redirect('/membership');
    }
}
