<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        
        // 管理者のみ許可
        Gate::define('admin', function ($user) {
            return ($user->role == \App\Admin::ADMIN_ROLE || $user->role == \App\Admin::DRAFT_ROLE);
        });
        // サポートに許可
        Gate::define('support', function ($user) {
            return ($user->role <= \App\Admin::SUPPORT_ROLE || $user->role == \App\Admin::DRAFT_ROLE);
        });
        // 運営者に許可
        Gate::define('operator', function ($user) {
            return ($user->role <= \App\Admin::OPERATOR_ROLE || $user->role == \App\Admin::DRAFT_ROLE);
        });
        // 入稿に許可
        Gate::define('draft', function ($user) {
            return ($user->role <= \App\Admin::DRAFT_ROLE);
        });
    }
}
