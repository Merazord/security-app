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
use GuzzleHttp\Client;

/**
 * Class LoginController
 *
 * Controlador responsable de gestionar el flujo completo de autenticación
 * de usuarios, incluyendo inicio de sesión, verificación de código por
 * correo electrónico (2FA) y cierre de sesión.
 *
 * Flujo de autenticación:
 *   1. El usuario envía email y contraseña → login()
 *   2. Si las credenciales son válidas, se envía un código de 6 dígitos al correo
 *   3. El usuario ingresa el código → verification()
 *   4. Si el código es correcto, accede al dashboard
 *
 * Medidas de seguridad implementadas:
 *   - Validación de reCAPTCHA Enterprise en el login
 *   - Bloqueo de cuenta tras 10 intentos fallidos de login
 *   - Bloqueo de cuenta tras 5 intentos fallidos de verificación
 *   - Redirección a reenvío de activación si la cuenta no está activa
 *   - Logs de seguridad en todos los eventos relevantes
 *
 * @package App\Http\Controllers\Auth
 */
class LoginController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Muestra el formulario de verificación de código 2FA.
     *
     * Redirige al login si el usuario no tiene una sesión autenticada activa,
     * lo que previene el acceso directo a esta ruta sin haber pasado por el login.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showVerificationForm()
    {
        if (!Auth::check()) {
            Log::warning('[AUTH] Intento de acceso a verificación sin sesión activa', [
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            return redirect()->route('login');
        }
        return view('auth.verification');
    }

    /**
     * Valida el token de reCAPTCHA Enterprise contra la API de Google.
     *
     * Realiza una petición POST a la API de verificación de reCAPTCHA
     * y evalúa tanto el resultado de validación como el score de riesgo.
     * Un score menor a 0.5 se considera sospechoso y se rechaza.
     *
     * @param  \Illuminate\Http\Request  $request  Petición que contiene el token en 'g-recaptcha-response'
     * @return bool  true si el reCAPTCHA es válido y el score es aceptable, false en caso contrario
     */
    private function validateRecaptcha(Request $request): bool
    {
        $client = new Client();
        $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret'   => config('services.recaptcha.secret_key'),
                'response' => $request->input('g-recaptcha-response'),
                'remoteip' => $request->ip(),
            ],
        ]);
        $data = json_decode($response->getBody(), true);

        if (!isset($data['success']) || !$data['success']) {
            Log::warning('[RECAPTCHA] Validación fallida en login', [
                'ip'       => $request->ip(),
                'email'    => $request->input('email'),
                'response' => $data,
            ]);
            return false;
        }

        if (($data['score'] ?? 0) < 0.5) {
            Log::warning('[RECAPTCHA] Puntuación baja en login', [
                'ip'    => $request->ip(),
                'email' => $request->input('email'),
                'score' => $data['score'],
            ]);
            return false;
        }

        Log::info('[RECAPTCHA] Validación exitosa en login', [
            'ip'    => $request->ip(),
            'email' => $request->input('email'),
            'score' => $data['score'],
        ]);

        return true;
    }

    /**
     * Procesa el formulario de inicio de sesión.
     *
     * Realiza las siguientes verificaciones en orden:
     *   1. Validación de campos requeridos (email, password, recaptcha)
     *   2. Validación de reCAPTCHA Enterprise
     *   3. Existencia del usuario en la base de datos
     *   4. Estado activo de la cuenta (is_active)
     *   5. Límite de intentos fallidos (máximo 10)
     *   6. Validación de credenciales con Auth::attempt()
     *
     * En caso de login exitoso:
     *   - Resetea el contador de intentos fallidos
     *   - Genera y almacena un código de verificación hasheado
     *   - Envía el código al correo del usuario
     *   - Guarda el user_id en sesión para el paso de verificación
     *
     * @param  \Illuminate\Http\Request  $request  Petición con 'email', 'password' y 'g-recaptcha-response'
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException  Si los campos requeridos no pasan la validación
     */
    public function login(Request $request)
    {
        Log::info('[LOGIN] Intento de inicio de sesión', [
            'ip'         => $request->ip(),
            'email'      => $request->input('email'),
            'user_agent' => $request->userAgent(),
        ]);

        $request->validate([
            'email'                => 'required|email',
            'password'             => 'required|string',
            'g-recaptcha-response' => 'required',
        ]);

        if (!$this->validateRecaptcha($request)) {
            return redirect()->route('login')
                ->withErrors(['message' => 'reCAPTCHA no validado. Intente de nuevo.'])
                ->withInput($request->only('email'));
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            Log::warning('[LOGIN] Intento con email no registrado', [
                'ip'    => $request->ip(),
                'email' => $request->input('email'),
            ]);
            return redirect()->route('login')
                ->withErrors(['message' => 'Credenciales inválidas.'])
                ->withInput($request->only('email'));
        }

        if (!$user->is_active) {
            Log::warning('[LOGIN] Intento de login en cuenta no activada', [
                'ip'      => $request->ip(),
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);
            return redirect()->route('resend.activation.form')
                ->with('warning', 'Tu cuenta no está activada. Revisa tu correo o solicita un nuevo enlace.');
        }

        if ($user->failed_login_attempts >= 10) {
            Log::critical('[SEGURIDAD] Cuenta bloqueada por exceso de intentos fallidos de login', [
                'ip'       => $request->ip(),
                'user_id'  => $user->id,
                'email'    => $user->email,
                'intentos' => $user->failed_login_attempts,
            ]);
            $user->is_active = false;
            $user->save();
            return redirect()->route('login')
                ->withErrors(['message' => 'Demasiados intentos fallidos. Tu cuenta ha sido bloqueada.']);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            $user->failed_login_attempts += 1;
            $user->save();

            Log::warning('[LOGIN] Contraseña incorrecta', [
                'ip'                 => $request->ip(),
                'user_id'            => $user->id,
                'email'              => $user->email,
                'intentos_fallidos'  => $user->failed_login_attempts,
                'intentos_restantes' => max(0, 10 - $user->failed_login_attempts),
            ]);

            if ($user->failed_login_attempts >= 7) {
                Log::warning('[SEGURIDAD] Usuario cerca del límite de intentos fallidos', [
                    'ip'                => $request->ip(),
                    'user_id'           => $user->id,
                    'email'             => $user->email,
                    'intentos_fallidos' => $user->failed_login_attempts,
                ]);
            }

            return redirect()->route('login')
                ->withErrors(['message' => 'Credenciales inválidas.'])
                ->withInput($request->only('email'));
        }

        $user->failed_login_attempts = 0;
        $code = rand(100000, 999999);
        $user->verification_token = Hash::make($code);
        $user->is_verified = false;
        $user->save();

        Mail::to($user->email)->send(new VerificationEmail($code, $user));

        $request->session()->put('user_id', $user->id);
        $request->session()->put('verification_code', $code);
        $request->session()->save();

        Log::info('[LOGIN] Autenticación exitosa, código de verificación enviado', [
            'ip'         => $request->ip(),
            'user_id'    => $user->id,
            'email'      => $user->email,
            'session_id' => session()->getId(),
        ]);

        return redirect()->route('verification')
            ->with('message', 'Código de verificación enviado a tu correo.');
    }

    /**
     * Procesa la verificación del código 2FA enviado por correo.
     *
     * Verifica que el código ingresado coincida con el token hasheado
     * almacenado en la base de datos. Implementa un límite de 5 intentos
     * fallidos antes de bloquear la cuenta.
     *
     * En caso de verificación exitosa:
     *   - Marca al usuario como verificado (is_verified = true)
     *   - Limpia el token de verificación de la base de datos
     *   - Elimina los datos temporales de la sesión
     *   - Redirige al dashboard
     *
     * En caso de fallo:
     *   - Incrementa el contador de intentos fallidos
     *   - Si alcanza 5 intentos, desactiva la cuenta y cierra la sesión
     *
     * @param  \Illuminate\Http\Request  $request  Petición con el campo 'code' (6 dígitos)
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException  Si el código no tiene el formato requerido
     */
    public function verification(Request $request)
    {
        if (!Auth::check()) {
            Log::warning('[VERIFICACION] Intento sin sesión autenticada', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
            ]);
            return redirect()->route('login')
                ->withErrors(['error' => 'Debes iniciar sesión primero.']);
        }

        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $user = Auth::user();

        Log::info('[VERIFICACION] Intento de verificación de código', [
            'ip'         => $request->ip(),
            'user_id'    => $user->id,
            'email'      => $user->email,
            'session_id' => session()->getId(),
        ]);

        if ($user->failed_verification_attempts >= 5) {
            Log::critical('[SEGURIDAD] Cuenta bloqueada por exceso de intentos fallidos de verificación', [
                'ip'      => $request->ip(),
                'user_id' => $user->id,
                'email'   => $user->email,
                'intentos' => $user->failed_verification_attempts,
            ]);
            $user->is_active = false;
            $user->save();
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')
                ->withErrors(['error' => 'Demasiados intentos fallidos. Tu cuenta ha sido bloqueada.']);
        }

        if (Hash::check($request->code, $user->verification_token)) {
            $user->is_verified = true;
            $user->failed_verification_attempts = 0;
            $user->verification_token = null;
            $user->save();

            $request->session()->forget(['user_id', 'verification_code']);

            Log::info('[VERIFICACION] Código verificado correctamente, acceso al dashboard', [
                'ip'      => $request->ip(),
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return redirect()->route('dashboard')
                ->with('message', 'Cuenta verificada exitosamente.');
        }

        $user->failed_verification_attempts += 1;
        $user->save();

        Log::warning('[VERIFICACION] Código incorrecto ingresado', [
            'ip'                 => $request->ip(),
            'user_id'            => $user->id,
            'email'              => $user->email,
            'intentos_fallidos'  => $user->failed_verification_attempts,
            'intentos_restantes' => max(0, 5 - $user->failed_verification_attempts),
        ]);

        return redirect()->route('verification')
            ->withErrors(['code' => 'Código de verificación inválido.']);
    }

    /**
     * Cierra la sesión del usuario autenticado.
     *
     * Realiza las siguientes acciones:
     *   - Registra el evento de logout con datos del usuario
     *   - Invalida la sesión actual completamente
     *   - Regenera el token CSRF para prevenir ataques de fijación de sesión
     *   - Redirige al formulario de login con mensaje de confirmación
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        Log::info('[LOGOUT] Cierre de sesión', [
            'ip'         => $request->ip(),
            'user_id'    => $user?->id,
            'email'      => $user?->email,
            'session_id' => session()->getId(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('message', 'Has cerrado sesión exitosamente.');
    }
}
