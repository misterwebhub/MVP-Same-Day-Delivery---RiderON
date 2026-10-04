<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\OtpVerificationLogRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only audit list of OTP verification attempts — no create/edit/delete,
 * mirrors a Filament-style log viewer.
 */
class OtpVerificationLogController extends Controller
{
    public function __construct(private readonly OtpVerificationLogRepositoryInterface $logs) {}

    public function index(Request $request): View
    {
        $logs = $this->logs->paginate(20, $request->only(['result', 'attempted_by_type']));

        return view('admin.otp-logs.index', [
            'logs' => $logs,
            'filters' => $request->only(['result', 'attempted_by_type']),
        ]);
    }
}
