<?php

namespace App\Http\Controllers;

use App\Services\Telegram\MadelineProtoApiClient;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class TelegramAuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        Log::info('ShowLoginForm called');

        // Check if already authenticated
        try {
            $sessionFile = storage_path('app/' . config('telegram.session_file'));
            Log::info('Checking session file: ' . $sessionFile);

            // If no session file exists, show login form
            if (!file_exists($sessionFile) && !is_dir($sessionFile)) {
                Log::info('No session file exists, showing login form');

                return view('telegram.login');
            }

            $client = new MadelineProtoApiClient;
            $api = $client->getApiInstance();
            if ($api) {
                try {
                    $self = $api->getSelf();
                    Log::info('User authenticated as: ' . json_encode($self));

                    // Show authenticated view without any user information
                    return view('telegram.authenticated');
                } catch (\Exception $e) {
                    // Session exists but is not authenticated
                    Log::info('Session exists but not authenticated: ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            // Not authenticated, show login form
            Log::info('No session or error getting API instance: ' . $e->getMessage());
        }

        Log::info('Showing login form view');

        return view('telegram.login');
    }

    /**
     * Start the authentication process
     */
    public function startAuth(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+?[0-9]{10,15}$/',
        ]);

        try {
            // Get API credentials from config (.env)
            $apiId = config('telegram.api_id');
            $apiHash = config('telegram.api_hash');

            if (!$apiId || !$apiHash) {
                throw new \Exception('API credentials not configured. Please set TELEGRAM_API_ID and TELEGRAM_API_HASH in .env file.');
            }

            // Store phone temporarily
            Session::put('temp_phone', $request->phone);

            // Initialize MadelineProto with settings from .env
            $settings = new Settings;
            $appInfo = new AppInfo;
            $appInfo->setApiId((int) $apiId);
            $appInfo->setApiHash($apiHash);
            $settings->setAppInfo($appInfo);

            $sessionFile = storage_path('app/' . config('telegram.session_file'));
            $api = new API($sessionFile, $settings);

            // Start phone login
            $api->phoneLogin($request->phone);

            Session::put('auth_step', 'verify_code');

            return redirect()->route('telegram.verify')->with('info', 'Verification code sent to ' . $request->phone);

        } catch (\Exception $e) {
            Log::error('Telegram auth error: ' . $e->getMessage());

            return back()->withErrors(['error' => 'Failed to start authentication: ' . $e->getMessage()]);
        }
    }

    /**
     * Show verification code form
     */
    public function showVerifyForm()
    {
        if (Session::get('auth_step') !== 'verify_code') {
            return redirect()->route('telegram.login');
        }

        return view('telegram.verify');
    }

    /**
     * Verify the code
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:5',
        ]);

        try {
            // Get API credentials from config (.env)
            $apiId = config('telegram.api_id');
            $apiHash = config('telegram.api_hash');

            if (!$apiId || !$apiHash) {
                throw new \Exception('API credentials not configured.');
            }

            // Reinitialize with .env credentials
            $settings = new Settings;
            $appInfo = new AppInfo;
            $appInfo->setApiId((int) $apiId);
            $appInfo->setApiHash($apiHash);
            $settings->setAppInfo($appInfo);

            $sessionFile = storage_path('app/' . config('telegram.session_file'));
            $api = new API($sessionFile, $settings);

            // Complete login
            $api->completePhoneLogin($request->code);

            // Clear temporary session data
            Session::forget(['temp_phone', 'auth_step']);

            // Show authenticated view without any user information
            return view('telegram.authenticated');

        } catch (\Exception $e) {
            Log::error('Telegram verification error: ' . $e->getMessage());

            if (str_contains($e->getMessage(), 'PHONE_CODE_INVALID')) {
                return back()->withErrors(['code' => 'Invalid verification code']);
            }

            return back()->withErrors(['error' => 'Verification failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Logout and clear the session
     */
    public function logout()
    {
        try {
            $client = new MadelineProtoApiClient;
            $client->logout();

            return redirect()->route('home')->with('success', 'Successfully logged out from Telegram!');
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());

            return redirect()->route('home')->with('error', 'Error logging out: ' . $e->getMessage());
        }
    }
}
