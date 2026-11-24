<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RefundEvidence;
use App\Models\User;
use App\Notifications\RefundRequestSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RefundRequestController extends Controller
{
    public function store(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', 'max:12288'],
            'payment_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $order, $data, &$storedPaths): void {
                $customerOrder = Order::query()
                    ->where('user_id', $request->user()->id)
                    ->lockForUpdate()
                    ->findOrFail($order);

                if ($customerOrder->status !== 'completed') {
                    throw ValidationException::withMessages([
                        'refund_request' => ['A refund request can only be made after delivery is completed.'],
                    ]);
                }

                if ($customerOrder->refundRequest()->exists()) {
                    throw ValidationException::withMessages([
                        'refund_request' => ['A refund request has already been submitted for this order.'],
                    ]);
                }

                $refundRequest = $customerOrder->refundRequest()->create([
                    'user_id' => $request->user()->id,
                    'description' => $data['description'],
                    'status' => 'pending',
                ]);

                foreach ($data['attachments'] ?? [] as $file) {
                    $path = $file->store('refund-evidence', 'local');
                    if (! is_string($path) || $path === '') {
                        throw new RuntimeException('Unable to store refund evidence.');
                    }
                    $storedPaths[] = $path;

                    $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                    $refundRequest->evidences()->create([
                        'purpose' => 'evidence',
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => mb_substr($originalName, 0, 255),
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'size' => $file->getSize(),
                    ]);
                }

                if (isset($data['payment_qr'])) {
                    $file = $data['payment_qr'];
                    $path = $file->store('refund-evidence', 'local');
                    if (! is_string($path) || $path === '') {
                        throw new RuntimeException('Unable to store the e-wallet QR image.');
                    }
                    $storedPaths[] = $path;

                    $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                    $refundRequest->evidences()->create([
                        'purpose' => 'payment_qr',
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => mb_substr($originalName, 0, 255),
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'size' => $file->getSize(),
                    ]);
                }

                User::query()
                    ->where(fn ($query) => $query->where('is_admin', true)->orWhere('is_staff', true))
                    ->get()
                    ->each(fn (User $recipient) => $recipient->notify(new RefundRequestSubmitted($refundRequest)));
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== [] && ! Storage::disk('local')->delete($storedPaths)) {
                Log::error('Failed to clean up refund evidence after a failed refund request.', [
                    'file_count' => count($storedPaths),
                ]);
            }

            throw $exception;
        }

        return redirect()->route('orders')->with('status', 'Your refund request was submitted for review.');
    }

    public function downloadEvidence(Request $request, RefundEvidence $evidence)
    {
        abort_unless(
            $evidence->refundRequest()
                ->where('user_id', $request->user()->id)
                ->exists(),
            404
        );

        return $this->download($evidence);
    }

    private function download(RefundEvidence $evidence)
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
}
