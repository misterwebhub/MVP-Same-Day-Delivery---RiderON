<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Repositories\Contracts\RefundRepositoryInterface;
use App\Services\Refunds\RefundProcessor;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Refunds are created only via Order actions (self-service cancellation or
 * the admin "approve refund" flow on OrderController) — there is no
 * create/edit form here. Admin/ops act through approve/reject, mirroring
 * the removed Filament RefundResource.
 */
class RefundController extends Controller
{
    public function __construct(
        private readonly RefundRepositoryInterface $refunds,
        private readonly RefundProcessor $refundProcessor,
    ) {}

    public function index(Request $request): View
    {
        $refunds = $this->refunds->paginate(15, $request->only(['status']));

        return view('admin.refunds.index', [
            'refunds' => $refunds,
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(Refund $refund): View
    {
        $refund->load(['order', 'payment', 'approvedBy']);

        return view('admin.refunds.show', ['refund' => $refund]);
    }

    public function approve(Refund $refund): RedirectResponse
    {
        abort_unless(AdminAccess::canApproveRefunds(), 403);

        if ($refund->status !== Refund::STATUS_PENDING) {
            return redirect()->route('admin.refunds.show', $refund)->with('error', 'Refund is not pending approval.');
        }

        try {
            $this->refundProcessor->approve($refund, auth()->id());

            return redirect()->route('admin.refunds.show', $refund)->with('status', 'Refund processed.');
        } catch (RuntimeException $e) {
            return redirect()->route('admin.refunds.show', $refund)->with('error', 'Refund could not be processed: '.$e->getMessage());
        }
    }

    public function reject(Refund $refund): RedirectResponse
    {
        abort_unless(AdminAccess::canApproveRefunds(), 403);

        if ($refund->status !== Refund::STATUS_PENDING) {
            return redirect()->route('admin.refunds.show', $refund)->with('error', 'Refund is not pending approval.');
        }

        $refund->forceFill([
            'status' => Refund::STATUS_REJECTED,
            'approved_by' => auth()->id(),
        ])->save();

        return redirect()->route('admin.refunds.show', $refund)->with('status', 'Refund rejected.');
    }
}
