<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QrCodeService;
use App\Models\QrCode;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{

    public function __construct(protected QrCodeService $qrCodeService) {}

    public function generate(Request $request)
    {
        $request->validate([
            'text' => 'required|array',
            'expires_at' => 'nullable|date',
        ]);

        return  $this->qrCodeService->generate($request);
    }

    public function scan(Request $request)
    {
        $request->validate(['code' => 'required']);
        return  $this->qrCodeService->scan($request);
    }
}
