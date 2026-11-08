<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\Loja\DashboardService;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->view('dashboard.index', [
            'title' => 'Dashboard',
            'breadcrumb' => [['label' => 'Dashboard', 'url' => null]],
            'user' => $this->currentUser(),
            'resumo' => (new DashboardService())->resumo(),
            'extraStyles' => [asset('css/loja.css')],
        ]);
    }
}
