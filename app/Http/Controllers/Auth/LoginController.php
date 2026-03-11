<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Mail\VerificationEmail;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class LoginController extends Controller
{

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function showVerificationForm()
    {
        return view('auth.verification');
    }

    /**
     * Handle user login.
     *
     * This method validates the incoming login request, checks if the user exists,
     * verifies if the account is active, and handles failed login attempts. If successful,
     * a JWT token is returned along with a verification code sent to the user's email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'g-recaptcha-response' => 'required'
        ]);

        $client = new Client();
        $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret'   => config('services.recaptcha.secret_key'),
                'response' => $request->input('g-recaptcha-response'),
                'remoteip' => $request->ip(),
            ],
        ]);
        $data = json_decode($response->getBody(), true);

        if (isset($data['success']) && $data['success']) {

            \Log::info('reCAPTCHA validado con éxito', ['data' => $data]);
            $score = $data['score'];

            if ($score < 0.5) {
                \Log::warning('Puntuación baja de reCAPTCHA', ['score' => $score]);
                return redirect()->route('login.form')->withErrors(['message' => 'Puntuación de reCAPTCHA baja, por favor intente de nuevo.']);
            }
        } else {
            \Log::info('reCAPTCHA no validado', ['data' => $data]);
            return redirect()->route('login.form')->withErrors(['message' => 'reCAPTCHA no validado']);
        }




        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return redirect()->route('login.form')->withErrors(['message' => 'Invalid credentials']);
        }

        if (!$user->is_active) {
            return redirect()->route('login.form')->withErrors(['message' => 'Your account is deactivated.']);
        }

        if ($user->failed_login_attempts >= 10) {
            $user->is_active = false;
            $user->save();
            return redirect()->route('login.form')->withErrors(['message' => 'Too many failed attempts. Your account has been locked.']);
        }

        if (Auth::attempt($request->only('email', 'password'))) {
            $user->failed_login_attempts = 0;

            $code = rand(100000, 999999);
            $user->verification_token = Hash::make($code);
            $user->is_verified = false;
            $user->save();

            Mail::to($user->email)->send(new VerificationEmail($code, $user));
            $request->session()->put('user_id', $user->id);
            $request->session()->put('verification_code', $code);
            $request->session()->put('user', $user);
            $request->session()->save();

            \Log::info('User authenticated', ['user_id' => Auth::id(), 'session_id' => session()->getId(), 'session_data' => session()->all()]);

            return redirect()->route('verification')->with('message', 'Verification code sent to your email.');
        } else {
            $user->failed_login_attempts += 1;
            $user->save();

            return redirect()->route('login.form')->withErrors(['message' => 'Invalid credentials']);
        }
    }

    /**
     * Handle user account verification using the verification code.
     *
     * This method checks if the user is authenticated, validates the verification code,
     * and updates the user's account to be verified. If the code is valid, a new JWT token
     * is generated and returned with a cookie. In case of incorrect code, it increments failed attempts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function verification(Request $request)
    {
        \Log::info('Verification attempt', [
            'user_id' => Auth::id(),
            'session_id' => session()->getId(),
            'session_data' => session()->all(),
            'stored_user_id' => $request->session()->get('user_id'),
            'stored_verification_code' => $request->session()->get('verification_code')
        ]);

        if (!Auth::check()) {
            \Log::warning('User not authenticated', ['session_id' => session()->getId()]);
            return redirect()->route('login.form')->withErrors(['error' => 'You must log in first.']);
        }

        $userId = $request->session()->get('user_id');
        $verificationCode = $request->session()->get('verification_code');

        \Log::info('Verification attempt', ['user_id' => Auth::id()]);

        $request->validate([
            'code' => 'required|numeric',

        ]);
        $user = Auth::user();

        if ($user->failed_verification_attempts >= 5) {
            $user->is_active = false;
            $user->save();
            return redirect()->route('login.form')->withErrors(['error' => 'Too many failed attempts. Your account has been locked.']);
        }

        if (Hash::check($request->code, $user->verification_token)) {

            $user->is_verified = true;
            $user->failed_verification_attempts = 0;
            $user->verification_token = null;
            $user->save();
            $request->session()->forget(['user_id', 'verification_code']);

            return redirect()->route('dashboard')->with('message', 'Account verified successfully.');
        } else {
            $user->failed_verification_attempts += 1;
            $user->save();
            return redirect()->route('verification')->withErrors(['code' => 'Invalid verification code.']);
        }

        return response()->json(['message' => 'Invalid verification code.'], 400);
    }

    /**
     * Logout the user and invalidate the session.
     *
     * This method invalidates the user's JWT token, deletes the JWT token from the cookie,
     * and redirects the user to the login page with a success message indicating successful logout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form')->with('message', 'You have logged out successfully.');
    }
}
