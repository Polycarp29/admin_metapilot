<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdTrackEvent;
use App\Models\AnalyticsProperty;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\OrganizationInvitation;
use App\Models\Sitemap;
use App\Models\Schema as JsonSchema;
use App\Models\KeywordResearch;
use App\Models\TrendingKeyword;
use App\Models\SiteConfig;
use App\Mail\InvitationMailable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/MasterDashboard', [
            'tab' => $request->query('tab', 'overview'),
            'stats' => [
                'total_users' => User::count(),
                'total_orgs'  => Organization::count(),
                'total_props' => AnalyticsProperty::count(),
                'hits_24h'    => AdTrackEvent::where('created_at', '>=', now()->subDay())->count(),
                'hits_7d'     => AdTrackEvent::where('created_at', '>=', now()->subDays(7))->count(),
                'pending_invites' => OrganizationInvitation::whereNull('accepted_at')->where('expires_at', '>', now())->count(),
            ],
            'recent_activities' => UserActivity::with(['user', 'organization'])
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
        ]);
    }

    public function users(Request $request)
    {
        $users = User::with('organizations')
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->status, function ($q, $status) {
                if ($status === 'active') $q->where('is_active', true);
                if ($status === 'disabled') $q->where('is_active', false);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($users);
    }

    public function addUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'is_admin' => 'boolean'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $validated['is_admin'] ?? false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        auth()->user()->logActivity('admin_action', "Created new user: {$user->email}");

        return response()->json(['message' => 'User created successfully', 'user' => $user]);
    }

    public function inviteUser(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
        ]);

        // Logic for platform-wide invite - for now we can reuse OrganizationInvitation 
        // with a null organization_id, or just send a manual link to /register
        // Since we want the "Metapilot" invite experience, let's use a dummy public org if needed 
        // or just a custom mailer. For this task, we'll assume they just need the register link.
        
        return response()->json(['message' => 'Platform invitation feature coming soon. Use Org Invite for now.']);
    }

    public function toggleUserStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'You cannot disable your own account.'], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        auth()->user()->logActivity(
            'admin_action',
            ($user->is_active ? 'Enabled' : 'Disabled') . " user account: {$user->email}",
            ['target_user_id' => $user->id]
        );

        return response()->json(['message' => 'User status updated successfully.', 'user' => $user]);
    }

    public function resetUserPassword(User $user)
    {
        $newPassword = Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);

        auth()->user()->logActivity('admin_action', "Reset password for user: {$user->email}");

        return response()->json(['message' => 'Password reset successfully', 'new_password' => $newPassword]);
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['error' => 'You cannot delete yourself.'], 403);
        }

        $email = $user->email;
        $user->delete();

        auth()->user()->logActivity('admin_action', "Deleted user account: {$email}");

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function invitations(Request $request)
    {
        $invites = OrganizationInvitation::with(['organization', 'inviter'])
            ->when($request->search, function ($q, $search) {
                $q->where('email', 'like', "%{$search}%");
            })
            ->when($request->status, function ($q, $status) {
                if ($status === 'pending') $q->whereNull('accepted_at')->where('expires_at', '>', now());
                if ($status === 'accepted') $q->whereNotNull('accepted_at');
                if ($status === 'expired') $q->whereNull('accepted_at')->where('expires_at', '<=', now());
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($invites);
    }

    public function revokeInvitation(OrganizationInvitation $invitation)
    {
        $email = $invitation->email;
        $invitation->delete();

        auth()->user()->logActivity('admin_action', "Revoked invitation for: {$email}");

        return response()->json(['message' => 'Invitation revoked successfully']);
    }

    public function inviteToOrganization(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'organization_id' => 'required|exists:organizations,id',
            'role' => 'required|in:admin,member',
        ]);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $validated['organization_id'],
            'invited_by' => auth()->id(),
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($validated['email'])->queue(new InvitationMailable($invitation));

        auth()->user()->logActivity('admin_action', "Invited {$validated['email']} to organization ID: {$validated['organization_id']}");

        return response()->json(['message' => 'Invitation sent successfully']);
    }

    public function organizations(Request $request)
    {
        $orgs = Organization::withCount(['users', 'analyticsProperties', 'adTrackEvents'])
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($orgs);
    }

    public function properties(Request $request)
    {
        $props = AnalyticsProperty::with(['organization', 'user'])
            ->withCount(['snapshots', 'leadEvents', 'insights', 'searchConsoleMetrics'])
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('website_url', 'like', "%{$search}%")
                  ->orWhere('property_id', 'like', "%{$search}%");
            })
            ->when($request->status, function ($q, $status) {
                if ($status === 'active') $q->where('is_active', true);
                if ($status === 'inactive') $q->where('is_active', false);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($props);
    }

    public function propertyDetail(AnalyticsProperty $property)
    {
        $property->load([
            'organization', 
            'user', 
            'adCampaigns', 
            'seoCampaigns', 
            'latestSnapshot', 
            'latestSearchConsoleMetric'
        ]);
        
        return response()->json($property);
    }

    public function sitemaps(Request $request)
    {
        $sitemaps = Sitemap::with('organization')
            ->withCount('links')
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('site_url', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($sitemaps);
    }

    public function schemas(Request $request)
    {
        $schemas = JsonSchema::with(['organization', 'schemaType'])
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($schemas);
    }

    public function keywordResearch(Request $request)
    {
        $research = KeywordResearch::with('organization')
            ->when($request->search, function ($q, $search) {
                $q->where('query', 'like', "%{$search}%");
            })
            ->orderBy('last_searched_at', 'desc')
            ->paginate(50);

        return response()->json($research);
    }

    public function keywordTrends(Request $request)
    {
        $trends = TrendingKeyword::with('organization')
            ->when($request->search, function ($q, $search) {
                $q->where('keyword', 'like', "%{$search}%");
            })
            ->orderBy('trending_date', 'desc')
            ->paginate(50);

        return response()->json($trends);
    }

    public function getConfig()
    {
        $configs = SiteConfig::all()->pluck('value', 'key');
        return response()->json($configs);
    }

    public function updateConfig(Request $request)
    {
        $validated = $request->validate([
            'configs' => 'required|array',
        ]);

        foreach ($validated['configs'] as $key => $value) {
            SiteConfig::set($key, $value);
        }

        auth()->user()->logActivity('admin_action', "Updated site configurations");

        return response()->json(['message' => 'Configuration updated successfully']);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,svg|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', 'public');
            SiteConfig::set('site_logo', asset('storage/' . $path), 'image');
            
            return response()->json(['message' => 'Logo uploaded successfully', 'url' => asset('storage/' . $path)]);
        }

        return response()->json(['error' => 'Upload failed'], 400);
    }

    public function services()
    {
        $services = [
            [
                'name' => 'Laravel App',
                'status' => 'Online',
                'ping' => 'Active',
                'icon' => '🛡️'
            ],
            [
                'name' => 'Python Engine',
                'status' => $this->checkService('http://localhost:8000/health'),
                'ping' => 'FastAPI',
                'icon' => '⚙️'
            ],
            [
                'name' => 'Worker Process',
                'status' => 'Online', // Placeholder
                'ping' => 'Redis Queue',
                'icon' => '🏗️'
            ],
            [
                'name' => 'Crawler Service',
                'status' => 'Online', // Placeholder
                'ping' => 'Spider Node',
                'icon' => '🕷️'
            ],
            [
                'name' => 'Main Site',
                'status' => 'Online',
                'ping' => 'Metapilot Service',
                'icon' => '🤖',
            ]
        ];

        return response()->json($services);
    }

    private function checkService($url)
    {
        try {
            $response = Http::timeout(2)->get($url);
            return $response->successful() ? 'Online' : 'Degraded';
        } catch (\Exception $e) {
            return 'Offline';
        }
    }

    public function logs(Request $request)
    {
        $resource = $request->input('resource', 'laravel');
        $paths = [
            'laravel' => storage_path('logs/laravel.log'),
            'engine'  => base_path('../analytics-engine/engine.log'),
            'worker'  => base_path('../analytics-engine/worker.log'),
            'crawler' => $this->getLatestCrawlerLog(),
            'metapilot' => base_path('../metapilot/storage/logs/larave.log'),
        ];

        $path = $paths[$resource] ?? $paths['laravel'];

        if (!File::exists($path)) {
            return response()->json(['content' => "Log file not found at: {$path}", 'resource' => $resource]);
        }

        // Tail the last 300 lines
        $command = "tail -n 300 " . escapeshellarg($path);
        $content = shell_exec($command);

        return response()->json([
            'content'  => $content ?: "No log content yet.",
            'resource' => $resource,
            'path'     => $path
        ]);
    }

    private function getLatestCrawlerLog()
    {
        $dir = base_path('../crawler-service/logs');
        if (!File::isDirectory($dir)) return null;

        $files = File::glob($dir . '/*.stderr.log');
        if (empty($files)) return null;

        // Sort by last modified
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $files[0];
    }
}
