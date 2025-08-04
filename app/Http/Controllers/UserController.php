<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Notifications\RoleAssigned;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
  public function index(Request $request)
  {
      $user = Auth::user(); // جلب المستخدم الحالي
      $roles = Role::all(); // جلب جميع الأدوار

      $users = User::with('roles')
          ->when(!$user->hasRole('Admin'), function ($query) use ($user) {
              // إذا لم يكن المستخدم Admin، اعرض بياناته فقط
              $query->where('id', $user->id);
          })
          ->when($user->hasRole('Admin') && $request->get('role'), function ($query) use ($request) {
              // تصفية حسب الدور إذا كان المستخدم Admin
              $query->whereHas('roles', function ($query) use ($request) {
                  $query->where('name', $request->get('role'));
              });
          })
          ->when($request->get('search'), function ($query) use ($request) {
              // تصفية حسب البحث في الاسم أو البريد الإلكتروني
              $query->where(function ($query) use ($request) {
                  $query->where('name', 'like', '%' . $request->get('search') . '%')
                      ->orWhere('email', 'like', '%' . $request->get('search') . '%');
              });
          })
          ->paginate(10);

      return view('content.dashboard.users.index', compact('users', 'roles'));
  }


    public function create()
    {
        $roles = Role::all();
        return view('content.dashboard.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return redirect()->route('dashboard.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('content.dashboard.users.edit', compact('user'));
    }

    public function permissions_roles(User $user)
    {
        // جلب الأدوار والصلاحيات التي تستخدم نفس guard_name
        $roles = Role::where('guard_name', 'sanctum')->get();
        $permissions = Permission::where('guard_name', 'sanctum')->get();

        return view('content.dashboard.users.permissions-roles', compact('user', 'roles', 'permissions'));
    }

    public function update_permissions_roles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'nullable|array',
            'permissions' => 'nullable|array'
        ]);

        // تحديث الأدوار مع التأكد من guard_name
        if ($request->has('roles')) {
            $roles = Role::where('guard_name', 'sanctum')
                        ->whereIn('name', $request->roles)
                        ->get();
            $user->syncRoles($roles);
        } else {
            $user->syncRoles([]);
        }

        // تحديث الصلاحيات المباشرة مع التأكد من guard_name
        if ($request->has('permissions')) {
            $permissions = Permission::where('guard_name', 'sanctum')
                                  ->whereIn('name', $request->permissions)
                                  ->get();
            $user->syncPermissions($permissions);
        } else {
            $user->syncPermissions([]);
        }

        return redirect()->route('dashboard.users.show', $user)
            ->with('success', __('User roles and permissions updated successfully.'));
    }

    public function update(Request $request, User $user)
    {
        // التحقق من الحقول الأساسية
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:15',
            'job_title' => 'nullable|string|max:100',
            'gender' => 'nullable|in:male,female',
            'country' => 'nullable|string|max:100',
            'social_links' => 'nullable|array',
            'social_links.*' => 'nullable|url',
            'bio' => 'nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_online' => 'boolean',
        ]);

        // تحديث المعلومات الأساسية
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->job_title = $validated['job_title'] ?? null;
        $user->gender = $validated['gender'] ?? null;
        $user->country = $validated['country'] ?? null;
        
        // معالجة روابط التواصل الاجتماعي
        if (isset($validated['social_links'])) {
            // تصفية الروابط الفارغة
            $socialLinks = array_filter($validated['social_links'], function($url) {
                return !empty(trim($url));
            });
            
            // تحويل الروابط إلى صيغة JSON مع الحفاظ على التنسيق
            $user->social_links = !empty($socialLinks) ? $socialLinks : null;
        } else {
            $user->social_links = null;
        }
        
        $user->bio = $validated['bio'] ?? null;

        // معالجة وتحديث الصورة الرمزية
        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::delete($user->profile_photo_path);
            }
            $path = $request->file('profile_photo')->store('profile_photos');
            $user->profile_photo_path = $path;
        }
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
            $user->save();
        }

        // حفظ التحديثات
        $user->save();

        return redirect()->route('dashboard.users.index')->with('success', 'User information updated successfully.');
    }

    public function show(User $user)
{
    $authUser = Auth::user(); // جلب المستخدم المسجل حاليًا

    // إذا لم يكن المستخدم Admin وحاول رؤية ملف شخص آخر، امنعه
    if (!$authUser->hasRole('Admin') && $authUser->id !== $user->id) {
        return redirect()->route('dashboard.users.show', $authUser->id)
            ->with('error', 'لا يمكنك الوصول إلى ملفات المستخدمين الآخرين.');
    }

    return view('content.dashboard.users.show', compact('user'));
}

    public function updatePermissionsRoles(Request $request, User $user)
    {
        // التحقق من الحقول التي يمكن أن تكون موجودة أو لا
        $request->validate([
            'roles' => 'sometimes|array',
            'permissions' => 'sometimes|array',
        ]);

        // الحصول على الأدوار والصلاحيات الحالية للمستخدم
        $currentRoles = $user->roles->pluck('name')->toArray();
        $currentPermissions = $user->permissions->pluck('name')->toArray();

        // مزامنة الأدوار
        $newRoles = $request->roles ?? [];
        $user->syncRoles($newRoles);

        // مزامنة الصلاحيات
        $newPermissions = $request->permissions ?? [];
        $user->syncPermissions($newPermissions);

        // تسجيل الأنشطة وإرسال الإشعارات للأدوار الجديدة والمزالة
        $this->logAndNotifyRoleChanges($user, $currentRoles, $newRoles);

        // تسجيل الأنشطة وإرسال الإشعارات للصلاحيات الجديدة والمزالة
        $this->logAndNotifyPermissionChanges($user, $currentPermissions, $newPermissions);

        return redirect()->route('dashboard.users.index')->with('success', 'User roles and permissions updated successfully.');
    }

    private function logAndNotifyRoleChanges(User $user, array $currentRoles, array $newRoles)
    {
        $removedRoles = array_diff($currentRoles, $newRoles);
        $addedRoles = array_diff($newRoles, $currentRoles);

        foreach ($removedRoles as $role) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->log("Removed role '{$role}' from user '{$user->name}'");

            $user->notify(new RoleAssigned($role, null, 'removed'));
            Log::info("Notification sent for role {$role} removed from user {$user->name}");
        }

        foreach ($addedRoles as $role) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->log("Assigned role '{$role}' to user '{$user->name}'");

            $user->notify(new RoleAssigned($role, null, 'assigned'));
            Log::info("Notification sent for role {$role} assigned to user {$user->name}");
        }
    }

    private function logAndNotifyPermissionChanges(User $user, array $currentPermissions, array $newPermissions)
    {
        $removedPermissions = array_diff($currentPermissions, $newPermissions);
        $addedPermissions = array_diff($newPermissions, $currentPermissions);

        foreach ($removedPermissions as $permission) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->log("Removed permission '{$permission}' from user '{$user->name}'");
        }

        foreach ($addedPermissions as $permission) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->log("Assigned permission '{$permission}' to user '{$user->name}'");
        }
    }

    public function destroy(User $user)
    {
        try {
            // Delete profile photo if exists
            if ($user->profile_photo_path) {
                Storage::delete($user->profile_photo_path);
            }

            // Remove roles and permissions
            $user->roles()->detach();
            $user->permissions()->detach();

            // Delete the user
            $user->delete();

            // Log the deletion
            activity()
                ->causedBy(auth()->user())
                ->log("Deleted user '{$user->name}'");

            return redirect()->route('dashboard.users.index')->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return redirect()->route('dashboard.users.index')->with('error', 'Error deleting user. Please try again.');
        }
    }

    /**
     * حذف مجموعة من المستخدمين دفعة واحدة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        // التحقق من وجود صلاحيات الحذف
        if (!auth()->user()->can('admin users')) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف المستخدمين'
            ], 403);
        }

        // التحقق من البيانات المرسلة
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|integer|exists:users,id'
        ]);

        $userIds = $request->user_ids;
        $deletedCount = 0;
        $errors = [];

        // استخدام المعاملات لضمان تكامل البيانات
        DB::beginTransaction();

        try {
            // جلب المستخدمين المراد حذفهم
            $users = User::whereIn('id', $userIds)->get();

            foreach ($users as $user) {
                // تخطي المستخدم الحالي لمنع حذف نفسه
                if ($user->id === auth()->id()) {
                    $errors[] = "لا يمكنك حذف حسابك الحالي: {$user->name}";
                    continue;
                }

                // حذف الصورة الشخصية إذا وجدت
                if ($user->profile_photo_path) {
                    Storage::delete($user->profile_photo_path);
                }

                // إزالة الأدوار والصلاحيات
                $user->roles()->detach();
                $user->permissions()->detach();

                // حذف المستخدم
                $user->delete();
                $deletedCount++;

                // تسجيل عملية الحذف
                activity()
                    ->causedBy(auth()->user())
                    ->log("Deleted user '{$user->name}' in bulk operation");
            }

            // تأكيد المعاملة إذا تم كل شيء بنجاح
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} مستخدم بنجاح" . (count($errors) > 0 ? " مع {count($errors)} أخطاء" : ""),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            DB::rollBack();
            Log::error('Error in bulk delete users: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء محاولة حذف المستخدمين',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
