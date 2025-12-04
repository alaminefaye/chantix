<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    /**
     * Afficher le formulaire de connexion
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Traiter la connexion
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Vérifier d'abord si l'utilisateur existe
        $user = User::where('email', $request->email)->first();

        // Si l'utilisateur existe mais n'est pas vérifié
        if ($user && !$user->isVerified() && !$user->isSuperAdmin()) {
            return back()->withErrors([
                'email' => 'Votre compte n\'a pas encore été validé par l\'administrateur. Veuillez patienter jusqu\'à ce que votre compte soit activé.',
            ])->onlyInput('email');
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Définir l'entreprise actuelle si l'utilisateur en a une
            $user = Auth::user();
            if (!$user->current_company_id && $user->companies()->exists()) {
                $user->current_company_id = $user->companies()->first()->id;
                $user->save();
            }

            return redirect()->intended(route('dashboard'));
        }

        // Identifiants incorrects
        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
        ])->onlyInput('email');
    }

    /**
     * Afficher le formulaire d'inscription
     */
    public function showRegisterForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $invitation = null;
        if ($request->has('token')) {
            $invitation = \App\Models\Invitation::where('token', $request->token)->first();
            if ($invitation && !$invitation->isValid()) {
                $invitation = null; // Invitation invalide
            }
        }

        return view('auth.register', compact('invitation'));
    }

    /**
     * Traiter l'inscription
     */
    public function register(Request $request)
    {
        $invitation = null;
        if ($request->has('token')) {
            $invitation = \App\Models\Invitation::where('token', $request->token)->first();
            if ($invitation && $invitation->isValid()) {
                // Validation avec email de l'invitation
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users|in:' . $invitation->email,
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                ]);
            } else {
                return redirect()->route('register')
                    ->with('error', 'Lien d\'invitation invalide ou expiré.');
            }
        } else {
            // Inscription normale (création d'entreprise)
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'company_name' => 'required|string|max:255',
            ]);
        }

        // Créer l'utilisateur (non vérifié par défaut, sauf si invitation)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_verified' => false, // Doit être validé par le super admin
        ]);

        if ($invitation) {
            // Accepter l'invitation - les invitations sont automatiquement vérifiées
            $user->is_verified = true;
            $user->save();
            
            $invitation->company->users()->attach($user->id, [
                'role_id' => $invitation->role_id,
                'is_active' => true,
                'joined_at' => now(),
            ]);

            $user->current_company_id = $invitation->company_id;
            $user->save();

            $invitation->markAsAccepted();

            $message = 'Votre compte a été créé et vous avez rejoint l\'entreprise ' . $invitation->company->name . ' !';
            
            // Connecter l'utilisateur
            Auth::login($user);
            
            return redirect()->route('dashboard')
                ->with('success', $message);
        } else {
            // Créer l'entreprise
            $company = Company::create([
                'name' => $request->company_name,
                'email' => $request->email,
                'is_active' => true,
            ]);

            // Attacher l'utilisateur à l'entreprise avec le rôle admin
            $adminRole = \App\Models\Role::where('name', 'admin')->first();
            if ($adminRole) {
                $user->companies()->attach($company->id, [
                    'role_id' => $adminRole->id,
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            }

            $user->current_company_id = $company->id;
            $user->save();

            // Ne pas connecter l'utilisateur, il doit attendre la validation du super admin
            return redirect()->route('login')
                ->with('info', 'Votre compte a été créé avec succès. Il sera activé après validation par l\'administrateur. Vous recevrez un email une fois votre compte validé.');
        }
    }

    /**
     * Déconnexion
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Vérifier l'email de l'utilisateur
     */
    public function verifyEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($request->user()));
        }

        return redirect()->route('dashboard')->with('success', 'Email vérifié avec succès !');
    }

    /**
     * Renvoyer l'email de vérification
     */
    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Lien de vérification envoyé !');
    }
}
