<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Mail\ActivationEmail;
use GuzzleHttp\Client;

/**
 * Class RegisterController
 *
 * Controlador responsable de gestionar el flujo completo de registro
 * y activación de cuentas de usuario.
 *
 * Flujo de registro:
 *   1. El usuario completa el formulario de registro → register()
 *   2. Se crea la cuenta con is_active = false
 *   3. Se envía un correo con enlace firmado de activación
 *   4. El usuario hace clic en el enlace → activate()
 *   5. La cuenta se activa y el usuario puede iniciar sesión
 *
 * En caso de no recibir el correo:
 *   - El usuario puede solicitar un reenvío → resendActivationEmail()
 *   - Se aplica rate limiting de 1 reenvío por minuto por correo
 *
 * Medidas de seguridad implementadas:
 *   - Validación de reCAPTCHA Enterprise en registro y reenvío
 *   - Enlace de activación con firma digital (hasValidSignature)
 *   - Rate limiting en reenvío de correos de activación
 *   - Respuesta genérica en reenvío para no revelar emails registrados
 *   - Logs de seguridad en todos los eventos relevantes
 *
 * @package App\Http\Controllers\Auth
 */
class RegisterController extends Controller
{
    /**
     * Muestra el formulario de registro de nuevo usuario.
     *
     * @return \Illuminate\View\View
     */
    public function registerform()
    {
        return view('auth.register');
    }

    /**
     * Muestra el formulario para reenviar el correo de activación.
     *
     * @return \Illuminate\View\View
     */
    public function resendActivationForm()
    {
        return view('auth.resend-activation');
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
            Log::warning('[RECAPTCHA] Validación fallida', [
                'ip'       => $request->ip(),
                'accion'   => $request->route()->getName(),
                'response' => $data,
            ]);
            return false;
        }

        if (($data['score'] ?? 0) < 0.5) {
            Log::warning('[RECAPTCHA] Puntuación baja', [
                'ip'     => $request->ip(),
                'accion' => $request->route()->getName(),
                'score'  => $data['score'],
            ]);
            return false;
        }

        Log::info('[RECAPTCHA] Validación exitosa', [
            'ip'     => $request->ip(),
            'accion' => $request->route()->getName(),
            'score'  => $data['score'],
        ]);

        return true;
    }

    /**
     * Procesa el formulario de registro de nuevo usuario.
     *
     * Realiza las siguientes operaciones en orden:
     *   1. Validación de todos los campos del formulario
     *   2. Validación de reCAPTCHA Enterprise
     *   3. Creación del usuario con cuenta inactiva (is_active = false)
     *   4. Envío del correo de activación con enlace firmado
     *
     * Reglas de validación aplicadas:
     *   - name: requerido, string, máximo 100 caracteres
     *   - email: requerido, formato válido, único en tabla users
     *   - password: mínimo 8 caracteres, mayúsculas, minúsculas,
     *               números, caracteres especiales (@$!%*#?&.) y confirmación
     *
     * @param  \Illuminate\Http\Request  $request  Petición con 'name', 'email', 'password',
     *                                             'password_confirmation' y 'g-recaptcha-response'
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException  Si algún campo no pasa la validación
     */
    public function register(Request $request)
    {
        Log::info('[REGISTRO] Intento de registro', [
            'ip'         => $request->ip(),
            'email'      => $request->input('email'),
            'user_agent' => $request->userAgent(),
        ]);

        $request->validate([
            'name'                 => 'required|string|max:100',
            'email'                => 'required|email|unique:users,email',
            'password'             => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/[a-z]/', 'regex:/[A-Z]/',
                'regex:/[0-9]/', 'regex:/[@$!%*#?&.]/',
            ],
            'g-recaptcha-response' => 'required',
        ], [
            'name.required'      => 'El nombre es obligatorio.',
            'name.max'           => 'El nombre no puede superar 100 caracteres.',
            'email.required'     => 'El correo es obligatorio.',
            'email.email'        => 'El correo no tiene un formato válido.',
            'email.unique'       => 'Este correo ya está registrado.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.regex'     => 'La contraseña debe contener mayúsculas, minúsculas, números y caracteres especiales.',
        ]);

        if (!$this->validateRecaptcha($request)) {
            Log::warning('[REGISTRO] Bloqueado por reCAPTCHA', [
                'ip'    => $request->ip(),
                'email' => $request->input('email'),
            ]);
            return redirect()->route('register.form')
                ->withErrors(['message' => 'reCAPTCHA no validado. Intente de nuevo.'])
                ->withInput($request->only('name', 'email'));
        }

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'is_active' => false,
        ]);

        Mail::to($user->email)->send(new ActivationEmail($user));

        Log::info('[REGISTRO] Usuario registrado exitosamente', [
            'ip'      => $request->ip(),
            'user_id' => $user->id,
            'email'   => $user->email,
            'name'    => $user->name,
        ]);

        return redirect()->route('register.form')->with([
            'success' => 'Registro exitoso. Revisa tu correo para activar tu cuenta.',
            'email'   => $user->email,
        ]);
    }

    /**
     * Activa la cuenta de un usuario mediante el enlace firmado enviado por correo.
     *
     * Verifica en orden:
     *   1. Que la firma del enlace sea válida y no haya expirado (hasValidSignature)
     *   2. Que la cuenta no esté ya activada
     *   3. Que el token del enlace coincida con el almacenado en la base de datos
     *
     * Si todas las verificaciones pasan, establece is_active = true y
     * redirige al login con mensaje de éxito.
     *
     * @param  \Illuminate\Http\Request  $request  Petición con el token en la query string
     * @param  \App\Models\User          $user     Usuario a activar (inyectado por Route Model Binding)
     * @return \Illuminate\Http\RedirectResponse
     */
    public function activate(Request $request, User $user)
    {
        Log::info('[ACTIVACION] Intento de activación de cuenta', [
            'ip'      => $request->ip(),
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        if (!$request->hasValidSignature()) {
            Log::warning('[ACTIVACION] Firma inválida o enlace expirado', [
                'ip'      => $request->ip(),
                'user_id' => $user->id,
                'email'   => $user->email,
                'url'     => $request->fullUrl(),
            ]);
            return redirect()->route('login')
                ->with('message', 'El enlace de activación no es válido o ha expirado.');
        }

        if ($user->is_active) {
            Log::info('[ACTIVACION] Intento de activar cuenta ya activa', [
                'ip'      => $request->ip(),
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);
            return redirect()->route('login')
                ->with('message', 'La cuenta ya está activada.');
        }

        if ($user->activation_token !== $request->token) {
            Log::warning('[SEGURIDAD] Token de activación inválido', [
                'ip'      => $request->ip(),
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);
            return redirect()->route('login')
                ->with('message', 'Token de activación inválido.');
        }

        $user->is_active = true;
        $user->save();

        Log::info('[ACTIVACION] Cuenta activada exitosamente', [
            'ip'      => $request->ip(),
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return redirect()->route('login')
            ->with('message', '¡Cuenta activada exitosamente! Ya puedes iniciar sesión.');
    }

    /**
     * Reenvía el correo de activación a un usuario no activo.
     *
     * Implementa las siguientes medidas de seguridad:
     *   - Validación de reCAPTCHA Enterprise para evitar abuso automatizado
     *   - Rate limiting: máximo 1 reenvío por minuto por dirección de correo
     *   - Respuesta genérica: siempre devuelve el mismo mensaje independientemente
     *     de si el correo existe o no, para evitar enumeración de usuarios
     *
     * El rate limiting se implementa con Laravel Cache usando la clave
     * 'resend_email_{email}' con TTL de 1 minuto.
     *
     * @param  \Illuminate\Http\Request  $request  Petición con 'email' y 'g-recaptcha-response'
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException  Si el email no tiene formato válido
     */
    public function resendActivationEmail(Request $request)
    {
        Log::info('[REENVIO] Solicitud de reenvío de correo de activación', [
            'ip'    => $request->ip(),
            'email' => $request->input('email'),
        ]);

        $request->validate([
            'email'                => 'required|email',
            'g-recaptcha-response' => 'required',
        ], [
            'email.required' => 'El correo es obligatorio.',
            'email.email'    => 'El correo no tiene un formato válido.',
        ]);

        if (!$this->validateRecaptcha($request)) {
            Log::warning('[REENVIO] Bloqueado por reCAPTCHA', [
                'ip'    => $request->ip(),
                'email' => $request->input('email'),
            ]);
            return response()->json(['message' => 'reCAPTCHA no validado. Intente de nuevo.'], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->is_active) {
            Log::info('[REENVIO] Correo no encontrado o cuenta ya activa (respuesta genérica)', [
                'ip'         => $request->ip(),
                'email'      => $request->input('email'),
                'encontrado' => (bool) $user,
                'ya_activo'  => $user?->is_active,
            ]);
            return response()->json([
                'message' => 'Si el correo existe y la cuenta no está activa, recibirás un nuevo enlace.',
            ], 200);
        }

        $cacheKey = 'resend_email_' . $user->email;
        if (Cache::has($cacheKey)) {
            Log::warning('[REENVIO] Rate limit alcanzado para reenvío de activación', [
                'ip'      => $request->ip(),
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);
            return response()->json([
                'message' => 'Ya se envió un correo recientemente. Por favor espera un momento.',
            ], 429);
        }

        Mail::to($user->email)->send(new ActivationEmail($user));
        Cache::put($cacheKey, true, now()->addMinutes(1));

        Log::info('[REENVIO] Correo de activación reenviado exitosamente', [
            'ip'      => $request->ip(),
            'user_id' => $user->id,
            'email'   => $user->email,
        ]);

        return response()->json([
            'message' => 'Si el correo existe y la cuenta no está activa, recibirás un nuevo enlace.',
        ], 200);
    }
}
