<?php

namespace App\Modules\Identity\Http\Controllers\Admin;

use App\Modules\Identity\Actions\ManageUsers;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Exceptions\UserManagementException;
use App\Modules\Identity\Http\Requests\UserRequest;
use App\Modules\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController
{
    public function __construct(private readonly ManageUsers $users) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(Role::class)],
        ]);

        $users = User::query()
            ->with('roles:id,name')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($q) => $q->where('name', 'ilike', $like)->orWhere('email', 'ilike', $like));
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->role($role))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => $this->present($user, $request->user()));

        return Inertia::render('Identity/Admin/Users/Index', [
            'users' => $users,
            'filters' => ['search' => $filters['search'] ?? '', 'role' => $filters['role'] ?? ''],
            'roles' => $this->roleOptions(Role::cases()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Identity/Admin/Users/Form', [
            'user' => null,
            'roles' => $this->roleOptions(Role::staff()),
            'invitationHours' => ManageUsers::INVITATION_HOURS,
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = $this->users->invite(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('phone'),
            $request->role(),
        );

        return to_route('admin.users.index')->with('success', "Undangan dikirim ke {$user->email}.");
    }

    public function edit(Request $request, User $user): Response
    {
        return Inertia::render('Identity/Admin/Users/Form', [
            'user' => $this->present($user, $request->user()),
            'roles' => $this->roleOptions(Role::cases()),
            'invitationHours' => ManageUsers::INVITATION_HOURS,
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        try {
            $this->users->update($request->user(), $user, $request->validated('name'), $request->validated('phone'), $request->role());
        } catch (UserManagementException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.users.index')->with('success', "Data {$user->name} diperbarui.");
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        try {
            $this->users->setActive($request->user(), $user, ! $user->is_active);
        } catch (UserManagementException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $user->is_active ? "{$user->name} diaktifkan." : "{$user->name} dinonaktifkan.");
    }

    public function resendInvitation(User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return back()->with('error', "{$user->name} sudah mengaktifkan akunnya.");
        }

        $this->users->sendInvitation($user);

        return back()->with('success', "Undangan dikirim ulang ke {$user->email}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $user, User $actor): array
    {
        $role = $user->primaryRole();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $role?->value,
            'role_label' => $role?->label() ?? '-',
            'is_active' => $user->is_active,
            'is_self' => $actor->is($user),
            'invitation_pending' => ! $user->hasVerifiedEmail() && ($role?->isStaff() ?? false),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  list<Role>  $roles
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(array $roles): array
    {
        return array_map(fn (Role $role) => ['value' => $role->value, 'label' => $role->label()], $roles);
    }
}
