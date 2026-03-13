<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ActivationEmail;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;

class RegisterController extends Controller
{

    public function registerform()
    {
        return view('auth.register');
    }
    /**
     * Register a new user.
     *
     * This method validates the incoming registration data, creates a new user in the database,
     * and sends an activation email to the user for account verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        \Log::info('Datos del formulario:', $request->all());

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&.]/',
            'g-recaptcha-response' => 'required',
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
                return redirect()->route('register')->withErrors(['message' => 'Puntuación de reCAPTCHA baja, por favor intente de nuevo.']);
            }
        } else {
            \Log::info('reCAPTCHA no validado', ['data' => $data]);
            return redirect()->route('register')->withErrors(['message' => 'reCAPTCHA no validado']);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => false,
        ]);

        Mail::to($user->email)->send(new ActivationEmail($user));

        return redirect()->route('register')->with([
            'success' => 'User registered successfully. Please check your email for activation link.',
            'email' => $user->email,
        ]);
    }

    /**
     * Activate the user's account.
     *
     * This method validates the activation token and activates the user's account if the token is valid.
     * If the user is already active or the token is invalid, appropriate error messages are returned.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate(Request $request, User $user)
    {

        if (!$request->hasValidSignature()) {
            return redirect()->route('login.form')->with([
                "message" => "Invalid activation link"
            ]);
        }

        if (!$user) {
            return redirect()->route('login.form')->with([
                'message' => 'User not found',
            ]);
        }

        if ($user->is_active) {
            return redirect()->route('login.form')->with([
                'message' => 'User already activated',
            ]);
        }

        if ($user->activation_token !== $request->token) {
            return redirect()->route('login.form')->with([
                'message' => 'Invalid activation token',
            ]);
        }

        $user->is_active = true;
        $user->save();

        return redirect()->route('register.form')->with('success', 'User activated successfully.');
    }

    /**
     * Resend the activation email.
     *
     * This method allows a user to request a new activation email if they did not receive the first one.
     * It checks that the user exists, that the account is not already active, and that an email
     * hasn't been sent recently, to prevent spamming.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function resendActivationEmail(Request $request)
    {
        \Log::info('Datos del formulario:', $request->all());

        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->is_active) {
            return response()->json(['message' => 'The account is already active.'], 400);
        }

        $cacheKey = 'resend_email_' . $user->email;
        if (Cache::has($cacheKey)) {
            return response()->json(['message' => 'An email has already been sent recently. Please try again later.'], 429);
        }

        Mail::to($user->email)->send(new ActivationEmail($user));

        Cache::put($cacheKey, true, now()->addMinutes(1));

        \Log::info('Activation email resent', ['user_id' => $user->id]);

        return response()->json(['message' => 'Activation email successfully resent.'], 200);
    }

    public function resendActivationForm()
    {
        return view('auth.resend-activation');
    }
}
