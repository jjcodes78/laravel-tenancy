<?php
/**
 * Created by PhpStorm.
 * User: jjsquady
 * Date: 5/7/18
 * Time: 3:57 PM
 */

namespace App;

use Hyn\Tenancy\Contracts\Repositories\CustomerRepository;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Customer;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Support\Facades\Hash;

/**
 * @property Customer customer
 * @property Website website
 * @property Hostname hostname
 * @property User admin
 */
class Tenant
{

    public function __construct(
        Customer $customer,
        Website $website = null,
        Hostname $hostname = null,
        User $admin = null
    ) {
        $this->customer = $customer;
        $this->website  = $website ?? $customer->websites->first();
        $this->hostname = $hostname ?? $customer->hostnames->first();
        $this->admin    = $admin;
    }

    public function delete()
    {
        app(HostnameRepository::class)->delete($this->hostname, true);
        app(WebsiteRepository::class)->delete($this->website, true);
        app(CustomerRepository::class)->delete($this->customer, true);
    }

    public static function createFrom($name, $email, $domain, $password = null): Tenant
    {
        // create a customer
        $customer        = new Customer;
        $customer->name  = $name;
        $customer->email = $email;
        app(CustomerRepository::class)->create($customer);
        // associate the customer with a website
        $website = new Website;
        $website->customer()->associate($customer);
        app(WebsiteRepository::class)->create($website);
        // associate the website with a hostname
        $hostname       = new Hostname;
        $baseUrl        = config('app.base_url');
        $hostname->fqdn = "{$domain}.{$baseUrl}";
        $hostname->customer()->associate($customer);
        app(HostnameRepository::class)->attach($hostname, $website);
        // make hostname current
        app(Environment::class)->hostname($hostname);
        $admin = static::makeAdmin($name, $email, $password ?? str_random());
        return new Tenant($customer, $website, $hostname, $admin);
    }

    private static function makeAdmin($name, $email, $password): User
    {
        $admin             = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
        $admin->guard_name = 'web';
        $admin->assignRole('admin');
        return $admin;
    }

    public static function retrieveBy($name): ?Tenant
    {
        if ($customer = Customer::where('name', $name)->with(['websites', 'hostnames'])->first()) {
            return new Tenant($customer);
        }
        return null;
    }

}