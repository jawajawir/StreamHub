<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\Controller;
use App\Core\Request;

class SystemPageController extends Controller
{
    public function maintenance(Request $request): void
    {
        http_response_code(503);
        $this->view('frontend.pages.maintenance', [
            'title' => 'Maintenance',
            'window' => null,
        ]);
    }
    public function forbidden(Request $request): void  { http_response_code(403); $this->view('frontend.pages.error_403', ['title' => 'Forbidden']); }
    public function notFound(Request $request): void   { http_response_code(404); $this->view('frontend.pages.error_404', ['title' => 'Not found']); }
}
