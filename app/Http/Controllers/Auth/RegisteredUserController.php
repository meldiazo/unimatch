<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'student_code' => ['required', 'string', 'max:50'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => User::ROLE_ESTUDIANTE,
            'password' => Hash::make($validated['password']),
        ]);

        $this->syncStudentRecord($validated, $user);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function syncStudentRecord(array $data, User $user): void
    {
        $studentCode = trim((string) ($data['student_code'] ?? ''));

        if ($studentCode === '') {
            return;
        }

        $student = Student::where('code', $studentCode)->first();

        if ($student) {
            $student->update([
                'full_name' => $student->full_name ?: $data['name'],
                'email' => $student->email ?: $data['email'],
            ]);

            return;
        }

        Student::create([
            'code' => $studentCode,
            'full_name' => $data['name'],
            'email' => $data['email'],
        ]);
    }
}
