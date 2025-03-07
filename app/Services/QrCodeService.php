<?php

namespace App\Services;

use App\Models\QrCode as QR;
use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRImage;
use chillerlan\QRCode\Common\EccLevel;
use Illuminate\Support\Facades\Storage;
use chillerlan\QRCode\Output\GDImage;
use chillerlan\QRCode\Data\QRMatrix;

class QrCodeService
{
    use HttpResponses;

    public function generate(Request $request)
    {
        if (!is_array($request->text)) {
            return response()->json(['message' => 'Invalid Request: text must be an array'], 400);
        }

        $qrCodes = [];

        foreach ($request->text as $text) {
            $code = uniqid('qr_');

            $options = new QROptions([
                'eccLevel' => EccLevel::H,
                'scale' => 10,
                'imageBase64' => false,
            ]);

            $qrCode = new QRCode($options);
            // $qrCode = new QRCode();
            $qrcode = $qrCode->render($text);

            $path = "qr-codes/{$code}.svg";

            // 🔥 This is the magic line
            Storage::put($path, $qrcode);

            $qr = QR::create([
                'code' => $code,
                'expires_at' => $request->expires_at,
            ]);

            $qrCodes[] = $qr;
        }
        // echo '<img src="'.(new QRCode)->render($qrCodes[0]).'" alt="QR Code" />';
        return $this->success($qrCodes, 'QR Code generated successfully', 200);
    }

    public function scan(Request $request)
    {
        $qr = QR::where('code', $request->code)->first();

        if (!$qr) {
            return response()->json(['message' => 'Invalid QR Code'], 404);
        }

        if ($qr->status !== 'active') {
            return response()->json(['message' => 'QR Code already used or expired'], 400);
        }

        if ($qr->expires_at && now()->greaterThan($qr->expires_at)) {
            $qr->update(['status' => 'expired']);
            return response()->json(['message' => 'QR Code expired'], 400);
        }

        $qr->update(['status' => 'used']);

        return $this->success($qr, 'QR Code scanned successfully', 200);


        // return response()->json(['message' => 'QR Code scanned successfully', 'data' => $qr]);
    }
}
