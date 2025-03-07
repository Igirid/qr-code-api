<?php

namespace App\Traits;

use App\Models\NotificationMessage;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;


/**
 * This is for returning Http responses
 */
trait HttpResponses
{
    protected function success($data = [], $message = null, $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => empty($data) ? new \stdClass : $data,
            'errors' => [],
        ], $code);
    }


    protected function error($data = [], $message = null, $errors = [], $code = 500)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => empty($data) ? new \stdClass :  $data,
            'errors' => empty($errors) ? new \stdClass : $errors,
        ], $code);
    }


    protected function UploadToCloud($uploadedFile, $file)
    {
        $response = Http::attach(
            'attach',
            $uploadedFile,
            $file
        )->post(config('setting.firebase_cloud_storage_url'));
        return $response;
    }

    public function CreateNotification(array $data)
    {
        return NotificationMessage::create($data);
    }

    //FireBase Notification V2
    /**
     * FirebaseAuthToken
     * This function send a http request to firebase auth api and get the auth token.
     * @return void
     */
    public function FirebaseAuthToken()
    {
        $url = config('setting.firebase_auth_url');
        return Http::get($url);
    }

    /**
     * FirebaseSendNotification
     * Data
     * data sample to be pass to this function
     * $data = [
     * "service_name" => $service_name,
     * "notification" => [
     *      "title" => $title,
     *      "body" => $body
     *  ]
     * ]
     *
     * @param  mixed $user_id
     * @param  mixed $data
     * @return void
     */
    public function FirebaseSendNotification(int $user_id, array $data)
    {

        try {
            // $userNotificationSetting = $this->PushNotificationChecker($user_id);
            // if ($userNotificationSetting == true) {
            $token = $this->FirebaseAuthToken();
            $user = User::where('user_id', $user_id)->first();
            $deviceToken = $user->deviceToken ?? null;
            if ($deviceToken == null || $token == "") {
                return false;
            }

            $data['token'] = $deviceToken;
            $data['data']['name'] = $data['service_name'];
            unset($data['service_name']);
            $message =
                [
                    "message" => $data,
                ];
            // Log::error(["token" => $token]);
            // return $message;
            $res =  Http::withToken($token)
                ->post('https://fcm.googleapis.com/v1/projects/' . config('setting.firebase_project_id') . '/messages:send', $message);
            if ($res->ok()) {
                return true;
            }
            return false;
            // }
            return false;
        } catch (\Throwable $th) {
            Log::error(["firebase_api_call" => $th]);
            return false;
        }
    }


    //FireBase Notification V1


    /**
     * FirebaseGetUserDeviceID
     *
     * @param  mixed $user_id
     * @return object
     */
    public function FirebaseGetUserDeviceID(int $user_id)
    {
        // Code to get user's device ID from Firebase
        $user = User::where('id', $user_id)->first();
        $data = [
            'firebaseID' => $user->profile->firebaseUID ?? null,
            'deviceToken' => $user->profile->deviceToken ?? null,
        ];
        return $data;
    }

    public function FireBaseSend($data)
    {
        $userProfile = $this->FirebaseGetUserDeviceID($data['user_id']);
        $data['deviceToken'] = $userProfile['deviceToken'];
        if ($data['deviceToken'] == null) {
            return false;
        }
        // Code to send push notification to user's device via Firebase
        $headers = [
            'Authorization' => 'key=' . config('setting.firebase_server_id'),
            'Content-Type' => 'application/json',
        ];
        $da = [
            'to' => $data['deviceToken'],
            'notification' => [
                'title' => $data['title'],
                'body' => $data['body'],
            ],
        ];
        $response = Http::withHeaders($headers)
            ->post('https://fcm.googleapis.com/fcm/send', $da);

        return $response;
    }

    public function getImageHtml($img_path = "public/logo/otask.png", $altText = "Otask Logo")
    {
        return new HtmlString(
            "<img src='" . asset($img_path) . "' alt='" . $altText . "' style='max-width:30%;'>"
        );
    }

    private function getWelcomeEmailContent($userName)
    {
        return new HtmlString('<div style="background-color:#D32F2F; padding: 20px; border-radius: 15px; font-family: Arial, sans-serif;">
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="' . asset('public/logo/squareboxlogo.png') . '" alt="SquareBox TV" style="width: 150px;">
            </div>
            <div style="background-color: #fff; padding: 30px; border-radius: 10px;">
                <h1 style="color: #D32F2F; text-align: center;">Welcome to SquareBox TV! 😊</h1>
                <p>Hello ' . $userName . ',</p>
                <p>Thank you for signing up with SquareBox TV! We are glad to see you join our FREE platform.</p>
                <p>By joining SquareBox TV, you’ve agreed to our Terms of Use and Privacy Statement.</p>
                <p>Thank you for choosing SquareBox TV. We look forward to serving you!</p>
                <p>Best regards,<br>The SquareBox TV Team</p>
            </div>
            <div style="text-align: center; margin-top: 20px; color: white;">
                <p>Questions or FAQs? Contact us at <a href="mailto:' . config('setting.support_email') . '" style="color: white;">' . config('setting.support_email') . '</a></p>
                <p>&copy; 2024 SquareBox TV. All Rights Reserved.</p>
            </div>
        </div>');
    }

    private function OnBoardEmailContent($full_name, $otp)
    {
        return new HtmlString('<div style="background-color:#D32F2F; padding: 20px; border-radius: 15px; font-family: Arial, sans-serif;">
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="' . asset('public/logo/squareboxlogo.png') . '" alt="SquareBox TV" style="width: 150px;">
            </div>
            <div style="background-color: #fff; padding: 30px; border-radius: 10px;">
                <h1 style="color: #D32F2F; text-align: center;">Welcome to SquareBox TV! 😊</h1>
                <p>Hello ' . $full_name . ',</p>
                <p>Thank you for signing up with SquareBox TV! We are glad to see you join our FREE platform.</p>
                <p>To complete your registration and verify your account, Copy the OTP below and :</p>
                <p style="text-align: center;">
                    <h4 style="background-color: #D32F2F; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">OTP: ' . $otp . '</h4>
                </p>
                <p>By joining SquareBox TV, you’ve agreed to our Terms of Use and Privacy Statement.</p>
                <p>Thank you for choosing SquareBox TV. We look forward to serving you!</p>
                <p>Best regards,<br>The SquareBox TV Team</p>
            </div>
            <div style="text-align: center; margin-top: 20px; color: white;">
                <p>Questions or FAQs? Contact us at <a href="mailto:' . config('setting.support_email') . '" style="color: white;">' . config('setting.support_email') . '</a></p>
                <p>&copy; 2024 SquareBox TV. All Rights Reserved.</p>
            </div>
        </div>');
    }

    private function LoginEmailContent($full_name)
    {
        return new HtmlString('<div style="background-color:#D32F2F; padding: 20px; border-radius: 15px; font-family: Arial, sans-serif;">
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="' . asset('public/logo/squareboxlogo.png') . '" alt="SquareBox TV" style="width: 150px;">
            </div>
            <div style="background-color: #fff; padding: 30px; border-radius: 10px;">
                <h1 style="color: #D32F2F; text-align: center;">Login Notification on SquareBox TV! 😊</h1>
                <p>Hello ' . $full_name . ',</p>
                <p>We hope this email finds you well. We wanted to inform you that your oTask account was recently accessed.</p>
                <p>Date:  .' . now()->format('d:m:y') . '</p>
                <p>Time:  .' . now()->format('h:m:s') . '</p>
                <p>If you were the one who accessed your account, you can disregard this email. However, if you suspect any unauthorized access or activity on your account, please contact us immediately at ' . config('setting.support_email') . ' for further assistance.</p>
                <p>We hope this email finds you well. We wanted to inform you that your oTask account was recently accessed.</p>
                <p>Best regards,<br>The SquareBox TV Team</p>
            </div>
            <div style="text-align: center; margin-top: 20px; color: white;">
                <p>Questions or FAQs? Contact us at <a href="mailto:' . config('setting.support_email') . '" style="color: white;">' . config('setting.support_email') . '</a></p>
                <p>&copy; 2024 SquareBox TV. All Rights Reserved.</p>
            </div>
        </div>');
    }
}
