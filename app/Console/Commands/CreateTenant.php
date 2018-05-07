<?php

namespace App\Console\Commands;

use App\Tenant;
use App\User;
use Hyn\Tenancy\Contracts\Repositories\CustomerRepository;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Customer;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create {domain} {name} {email}';

    protected $description = 'Creates a tenant with the provided name and email address e.g. php artisan tenant:create boise boise@example.com';

    public function handle()
    {
        $domain = $this->argument('domain');
        $name = $this->argument('name');
        $email = $this->argument('email');

        if ($this->tenantExists($name, $email)) {
            $this->error("A tenant with name '{$name}' and/or '{$email}' already exists.");

            return;
        }

        if($this->tenantHostExists($domain)) {
            $this->error("This customer domain already exists.");

            return;
        }

        $password = str_random();
        $tenant = Tenant::createFrom($name, $email, $domain, $password);
        $this->info("Tenant '{$name}' is created and is now accessible at {$tenant->hostname->fqdn}");
        $this->info("Admin {$email} can log in using password {$password}");

        // invite admin
//        $tenant->admin->notify(new TenantCreated($tenant->hostname));
//        $this->info("Admin {$email} has been invited!");
    }

    private function tenantExists($name, $email)
    {
        return Customer::where('name', $name)->orWhere('email', $email)->exists();
    }

    private function tenantHostExists($domain)
    {
        $baseUrl = config('app.base_url');
        return Hostname::where('fqdn', "{$domain}.{$baseUrl}")->exists();
    }
}