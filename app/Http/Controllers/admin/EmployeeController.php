<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Employee;
use App\Models\admin\EmployeeType;
use App\Rules\ValidarLicenciaA3c;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $employees = Employee::query()
                ->join('employee_types', 'employees.employee_type_id', '=', 'employee_types.id')
                ->select([
                    'employees.id',
                    'employees.employee_type_id',
                    'employees.dni',
                    'employees.first_name',
                    'employees.last_name',
                    'employees.email',
                    'employees.status',
                    'employees.created_at',
                    'employees.profile_photo_path',
                    'employee_types.name as type',
                ]);

            return DataTables::of($employees)
                ->addColumn('photo', function (Employee $employee) {
                    // Ignore legacy external avatar URLs instead of generating /storage/https:// requests.
                    $photoPath = trim((string) $employee->profile_photo_path);
                    $photo = $photoPath !== '' && ! preg_match('/^https?:\\/\\//i', $photoPath)
                        ? asset('storage/'.ltrim($photoPath, '/'))
                        : self::employeePhotoPlaceholder();

                    return '<img src="'.e($photo).'" loading="lazy" decoding="async" width="48" height="48" onerror="this.onerror=null;this.src=\''.e(self::employeePhotoPlaceholder()).'\'" alt="Foto de '.e($employee->first_name).'" style="width:48px;height:48px;max-width:none;object-fit:cover;border-radius:9999px">';
                })
                ->editColumn('created_at', fn (Employee $employee) => $employee->created_at?->format('d/m/Y H:i'))
                ->editColumn('status', function (Employee $employee) {
                    $class = $employee->status === 'Activo' ? 'bg-success' : 'bg-secondary';

                    return '<span class="badge '.$class.'">'.e($employee->status).'</span>';
                })
                ->addColumn('actions', function (Employee $employee) {
                    return '<div class="employee-actions">'
                        .'<button type="button" class="btn btn-outline-primary btn-sm employee-action-btn btnVerEmpleado" data-url="'.e(route('personal.employees.show', $employee)).'" title="Ver" aria-label="Ver empleado"><i class="bi bi-eye" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-outline-warning btn-sm employee-action-btn btnEditarEmpleado" data-url="'.e(route('personal.employees.edit', $employee)).'" title="Editar" aria-label="Editar empleado"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-outline-danger btn-sm employee-action-btn btnEliminarEmpleado" data-url="'.e(route('personal.employees.destroy', $employee)).'" title="Eliminar" aria-label="Eliminar empleado"><i class="bi bi-trash" aria-hidden="true"></i></button>'
                        .'</div>';
                })
                ->rawColumns(['photo', 'status', 'actions'])
                ->toJson();
        }

        $tipos = EmployeeType::orderBy('name')->get(['id', 'name']);

        return view('personal.employees.index', compact('tipos'));
    }

    private static function employeePhotoPlaceholder(): string
    {
        return 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><rect width="48" height="48" rx="24" fill="#e9ecef"/><circle cx="24" cy="18" r="8" fill="#adb5bd"/><path d="M8 43c1-9 7-14 16-14s15 5 16 14" fill="#adb5bd"/></svg>');
    }

    public function store(Request $request)
    {
        $tipoPersonal = EmployeeType::find($request->input('tipo_personal_id'));
        $requiereLicencia = $tipoPersonal && (
            str_contains(mb_strtolower($tipoPersonal->name), 'conductor')
            || str_contains(mb_strtolower($tipoPersonal->name), 'chofer')
        );

        $data = $request->validate([
            'dni' => ['required', 'string', 'regex:/^\\d{8}$/', 'unique:employees,dni'],
            'tipo_personal_id' => ['required', 'integer', 'exists:employee_types,id'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'email' => ['required', 'email', 'max:150', 'unique:employees,email'],
            'password' => ['required', 'string', 'min:8'],
            'direccion' => ['required', 'string', 'max:255'],
            'foto' => ['required', 'image', 'max:5120'],
            'telefono' => ['nullable', 'regex:/^[0-9]{9}$/'],
            'licencia' => $requiereLicencia ? ['required', new ValidarLicenciaA3c($request->input('dni'))] : ['nullable'],
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.string' => 'El DNI debe ser texto válido.',
            'dni.regex' => 'El DNI debe tener exactamente 8 dígitos numéricos.',
            'dni.unique' => 'Ya existe un empleado con ese DNI.',
            'tipo_personal_id.required' => 'Seleccione el tipo de personal.',
            'tipo_personal_id.integer' => 'El tipo de personal seleccionado no es válido.',
            'tipo_personal_id.exists' => 'El tipo de personal seleccionado no es válido.',
            'nombres.required' => 'Ingrese los nombres.',
            'nombres.string' => 'Los nombres deben ser texto válido.',
            'nombres.max' => 'Los nombres no deben superar los 100 caracteres.',
            'apellidos.required' => 'Ingrese los apellidos.',
            'apellidos.string' => 'Los apellidos deben ser texto válido.',
            'apellidos.max' => 'Los apellidos no deben superar los 100 caracteres.',
            'fecha_nacimiento.required' => 'Ingrese la fecha de nacimiento.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before_or_equal' => 'El empleado debe tener al menos 18 años.',
            'email.required' => 'Ingrese el correo electrónico.',
            'email.string' => 'El correo electrónico debe ser texto válido.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.max' => 'El correo electrónico no debe superar los 150 caracteres.',
            'email.unique' => 'Ya existe un empleado con ese correo electrónico.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser texto válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'direccion.required' => 'Ingrese la dirección.',
            'direccion.string' => 'La dirección debe ser texto válido.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
            'foto.required' => 'Seleccione una foto de perfil.',
            'foto.image' => 'El archivo seleccionado debe ser una imagen.',
            'foto.max' => 'La foto no debe superar los 5 MB.',
            'telefono.regex' => 'El teléfono debe tener exactamente 9 dígitos numéricos.',
            'licencia.required' => 'Ingrese el número de licencia para este tipo de personal.',
        ]);

        $foto = $request->file('foto')->store('employees', 'public');

        try {
            Employee::create([
                'employee_type_id' => $data['tipo_personal_id'],
                'dni' => $data['dni'],
                'first_name' => $data['nombres'],
                'last_name' => $data['apellidos'],
                'birthdate' => $data['fecha_nacimiento'],
                'email' => $data['email'],
                'status' => 'Activo',
                'password' => Hash::make($data['password']),
                'address' => $data['direccion'],
                'profile_photo_path' => $foto,
                'phone' => $data['telefono'] ?? null,
                'license' => $data['licencia'] ?? null,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($foto);

            throw $exception;
        }

        return response()->json(['mensaje' => 'Personal registrado correctamente.']);
    }

    public function show(Employee $employee)
    {
        $employee->load('employeeType');

        return view('personal.employees.partials.show-modal', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return $this->formModal($employee);
    }

    private function formModal(?Employee $employee = null)
    {
        $tipos = EmployeeType::orderBy('name')->get(['id', 'name']);

        return view('personal.employees.partials.form-modal', compact('employee', 'tipos'));
    }

    public function update(Request $request, Employee $employee)
    {
        $tipoPersonal = EmployeeType::find($request->input('tipo_personal_id'));
        $requiereLicencia = $tipoPersonal && (
            str_contains(mb_strtolower($tipoPersonal->name), 'conductor')
            || str_contains(mb_strtolower($tipoPersonal->name), 'chofer')
        );

        $data = $request->validate([
            'dni' => ['required', 'string', 'regex:/^\\d{8}$/', Rule::unique('employees', 'dni')->ignore($employee->id)],
            'tipo_personal_id' => ['required', 'integer', 'exists:employee_types,id'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'email' => ['required', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($employee->id)],
            'estado' => ['required', Rule::in(['Activo', 'Inactivo'])],
            'direccion' => ['required', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'max:5120'],
            'password' => ['nullable', 'string', 'min:8'],
            'telefono' => ['nullable', 'regex:/^[0-9]{9}$/'],
            'licencia' => $requiereLicencia ? ['required', new ValidarLicenciaA3c($request->input('dni'))] : ['nullable'],
        ], [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.string' => 'El DNI debe ser texto válido.',
            'dni.regex' => 'El DNI debe tener exactamente 8 dígitos numéricos.',
            'dni.unique' => 'Ya existe un empleado con ese DNI.',
            'tipo_personal_id.required' => 'Seleccione el tipo de personal.',
            'tipo_personal_id.integer' => 'El tipo de personal seleccionado no es válido.',
            'tipo_personal_id.exists' => 'El tipo de personal seleccionado no es válido.',
            'nombres.required' => 'Ingrese los nombres.',
            'nombres.string' => 'Los nombres deben ser texto válido.',
            'nombres.max' => 'Los nombres no deben superar los 100 caracteres.',
            'apellidos.required' => 'Ingrese los apellidos.',
            'apellidos.string' => 'Los apellidos deben ser texto válido.',
            'apellidos.max' => 'Los apellidos no deben superar los 100 caracteres.',
            'fecha_nacimiento.required' => 'Ingrese la fecha de nacimiento.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before_or_equal' => 'El empleado debe tener al menos 18 años.',
            'email.required' => 'Ingrese el correo electrónico.',
            'email.string' => 'El correo electrónico debe ser texto válido.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.max' => 'El correo electrónico no debe superar los 150 caracteres.',
            'email.unique' => 'Ya existe un empleado con ese correo electrónico.',
            'estado.required' => 'Seleccione el estado del empleado.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'password.string' => 'La contraseña debe ser texto válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'direccion.required' => 'Ingrese la dirección.',
            'direccion.string' => 'La dirección debe ser texto válido.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
            'foto.image' => 'El archivo seleccionado debe ser una imagen.',
            'foto.max' => 'La foto no debe superar los 5 MB.',
            'telefono.regex' => 'El teléfono debe tener exactamente 9 dígitos numéricos.',
            'licencia.required' => 'Ingrese el número de licencia para este tipo de personal.',
        ]);

        if ($data['estado'] === 'Inactivo' && $employee->status !== 'Inactivo' && $employee->hasVigenteContract()) {
            throw ValidationException::withMessages([
                'estado' => 'No se puede dar de baja al empleado porque tiene un contrato vigente.',
            ]);
        }

        $employee->fill([
            'employee_type_id' => $data['tipo_personal_id'],
            'dni' => $data['dni'],
            'first_name' => $data['nombres'],
            'last_name' => $data['apellidos'],
            'birthdate' => $data['fecha_nacimiento'],
            'email' => $data['email'],
            'status' => $data['estado'],
            'address' => $data['direccion'],
            'phone' => $data['telefono'] ?? null,
            'license' => $data['licencia'] ?? null,
        ]);

        if ($request->filled('password')) {
            $employee->password = Hash::make($data['password']);
        }

        $fotoAnterior = null;
        $fotoNueva = null;
        if ($request->hasFile('foto')) {
            $fotoAnterior = $employee->profile_photo_path;
            $fotoNueva = $request->file('foto')->store('employees', 'public');
            $employee->profile_photo_path = $fotoNueva;
        }

        try {
            $employee->save();
        } catch (Throwable $exception) {
            if ($fotoNueva) {
                Storage::disk('public')->delete($fotoNueva);
            }

            throw $exception;
        }

        if ($fotoAnterior && $fotoAnterior !== $fotoNueva) {
            Storage::disk('public')->delete($fotoAnterior);
        }

        return response()->json(['mensaje' => 'Personal actualizado correctamente.']);
    }

    public function destroy(Employee $employee)
    {
        if ($employee->hasVigenteContract()) {
            return response()->json([
                'mensaje' => 'No se puede dar de baja al empleado porque tiene un contrato vigente.',
            ], 409);
        }

        if ($employee->contracts()->exists()) {
            $employee->update(['status' => 'Inactivo']);

            return response()->json([
                'mensaje' => 'El empleado fue marcado como inactivo porque tiene contratos históricos.',
            ]);
        }

        $foto = $employee->profile_photo_path;
        $employee->delete();

        if ($foto) {
            Storage::disk('public')->delete($foto);
        }

        return response()->json(['mensaje' => 'Empleado eliminado correctamente.']);
    }
}
