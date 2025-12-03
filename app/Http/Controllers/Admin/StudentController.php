<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        $students = Student::orderBy('full_name')->paginate(25);

        return view('admin.students.index', compact('students'));
    }

    public function create(): View
    {
        return view('admin.students.form', [
            'student' => new Student(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateStudent($request);

        Student::create($data);

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Estudiante registrado.');
    }

    public function edit(Student $student): View
    {
        return view('admin.students.form', compact('student'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $data = $this->validateStudent($request, $student);

        $student->update($data);

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Estudiante actualizado.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Estudiante eliminado.');
    }

    private function validateStudent(Request $request, ?Student $student = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('students', 'code')->ignore($student?->id)],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('students', 'email')->ignore($student?->id)],
            'document' => ['nullable', 'string', 'max:80'],
        ]);

        $meta = $student?->meta ?? [];
        if (! empty($validated['document'])) {
            $meta['document'] = $validated['document'];
        } else {
            unset($meta['document']);
        }

        return [
            'code' => $validated['code'],
            'full_name' => $validated['full_name'],
            'email' => $validated['email'] ?? null,
            'meta' => empty($meta) ? null : $meta,
        ];
    }
}
