<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundEvidence;
use App\Models\RefundRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RefundRequestController extends Controller
{
    private const STATUSES = ['pending', 'approved', 'rejected', 'refunded'];

    public function index(Request $request): View
    {
        $request->validate([
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ]);

        $requests = RefundRequest::query()
            ->with(['order', 'customer'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->trim()->toString();
                $orderNumberId = preg_match('/^OS-0*([0-9]+)$/i', $search, $matches) === 1
                    ? $matches[1]
                    : null;

                $query->where(function ($query) use ($search, $orderNumberId): void {
                    $query->whereHas('order', function ($query) use ($search, $orderNumberId): void {
                        $query->where('id', 'like', "%{$search}%");
                        if ($orderNumberId !== null) {
                            $query->orWhere('id', $orderNumberId);
                        }
                    })
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = RefundRequest::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.refunds.index', [
            'requests' => $requests,
            'statuses' => self::STATUSES,
            'counts' => $counts,
        ]);
    }

    public function show(RefundRequest $refundRequest): View
    {
        return view('admin.refunds.show', [
            'refundRequest' => $refundRequest->load(['order.items', 'customer', 'reviewer', 'evidences']),
        ]);
    }

    public function update(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'refunded'])],
            'approved_amount' => [
                'required_if:status,approved',
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0.01',
                'max:'.$refundRequest->order->total,
            ],
            'admin_note' => ['required_if:status,rejected', 'nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $refundRequest, $data): void {
            $lockedRequest = RefundRequest::query()
                ->lockForUpdate()
                ->findOrFail($refundRequest->id);

            if ($lockedRequest->status === 'pending' && in_array($data['status'], ['approved', 'rejected'], true)) {
                $lockedRequest->update([
                    'status' => $data['status'],
                    'approved_amount' => $data['status'] === 'approved' ? $data['approved_amount'] : null,
                    'admin_note' => $data['admin_note'] ?? null,
                    'reviewed_by' => $request->user('admin')->id,
                    'reviewed_at' => now(),
                ]);

                return;
            }

            if ($lockedRequest->status === 'approved' && $data['status'] === 'refunded') {
                $lockedRequest->update([
                    'status' => 'refunded',
                    'completed_by' => $request->user('admin')->id,
                    'completed_at' => now(),
                ]);

                return;
            }

            throw ValidationException::withMessages([
                'status' => ['This refund request has already been reviewed or cannot be moved to that status.'],
            ]);
        });

        $statusMessage = match ($data['status']) {
            'approved' => 'Refund request approved. The customer can view the decision in their order history.',
            'rejected' => 'Refund request declined.',
            'refunded' => 'Refund marked as manually completed.',
        };

        return redirect()
            ->route('admin.refunds.show', $refundRequest)
            ->with('status', $statusMessage);
    }

    public function downloadEvidence(RefundEvidence $evidence)
    {
        $extension = pathinfo($evidence->path, PATHINFO_EXTENSION);

        return Storage::disk($evidence->disk)->download(
            $evidence->path,
            'refund-evidence-'.$evidence->id.($extension !== '' ? '.'.$extension : ''),
            [
                'Content-Type' => $evidence->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function previewEvidence(RefundEvidence $evidence)
    {
        abort_unless(in_array($evidence->mime_type, [
            'image/jpeg',
            'image/png',
            'image/webp',
            'video/mp4',
            'video/quicktime',
            'video/webm',
        ], true), 404);
        abort_unless($evidence->disk === 'local', 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($evidence->path), 404);

        $response = response()->file($disk->path($evidence->path), [
            'Content-Type' => $evidence->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-store');

        return $response;
    }
}
