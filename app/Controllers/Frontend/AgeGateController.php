<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\AgeGateService;

class AgeGateController extends Controller
{
    public function show(Request $request): void
    {
        if (AgeGateService::isPassed($request)) {
            $this->redirect(Session::instance()->pull('_age_gate_intended', '/'));
            return;
        }
        $this->view('frontend.pages.age_gate', ['title' => 'Age verification']);
    }

    public function accept(Request $request): void
    {
        $this->validateCsrf($request);
        if ($request->post('confirm') !== '1') {
            $this->redirect('/age-gate'); return;
        }
        AgeGateService::pass($request);
        $intended = Session::instance()->pull('_age_gate_intended', '/');
        $this->redirect($intended);
    }
}
