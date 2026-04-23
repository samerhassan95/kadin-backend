<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Spatie\Permission\Models\Role;

class LocalTestSeeder extends Seeder
{
    public function run()
    {
        // Create roles if they don't exist
        $this->createRoles();
        
        // Create admin user
        $this->createAdminUser();
        
        // Create test user
        $this->createTestUser();
        
        // Add basic settings
        $this->addBasicSettings();
        
        // Add currencies
        $this->addCurrencies();
        
        // Add languages
        $this->addLanguages();
        
        echo "Local test seeding completed!\n";
    }
    
    private function createRoles()
    {
        $roles = ['admin', 'user', 'seller', 'deliveryman', 'moderator'];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
    
    private function createAdminUser()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@kadin.app'],
            [
                'uuid' => Str::uuid(),
                'firstname' => 'Kadin',
                'lastname' => 'Seller',
                'email' => 'admin@kadin.app',
                'phone' => '01000000000',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'active' => true,
                'ip_address' => '127.0.0.1',
            ]
        );
        
        // Change to seller role instead of admin
        $admin->syncRoles(['seller']);
        
        // Create wallet for seller
        DB::table('wallets')->updateOrInsert(
            ['user_id' => $admin->id],
            [
                'uuid' => Str::uuid(),
                'user_id' => $admin->id,
                'currency_id' => 1,
                'price' => 1000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        // Create shop for seller
        $this->createShopForSeller($admin);
    }
    
    private function createShopForSeller($user)
    {
        $shop = DB::table('shops')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'tax' => 0,
                'percentage' => 0,
                'phone' => $user->phone,
                'open' => 1,
                'visibility' => 1,
                'status' => 'approved',
                'status_note' => 'Auto approved',
                'delivery_time_type' => 'minute',
                'delivery_time_to' => '60',
                'delivery_time_from' => '30',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        
        // Get shop ID
        $shopId = DB::table('shops')->where('user_id', $user->id)->value('id');
        
        if ($shopId) {
            // Add shop translation
            DB::table('shop_translations')->updateOrInsert(
                ['shop_id' => $shopId, 'locale' => 'en'],
                [
                    'shop_id' => $shopId,
                    'locale' => 'en',
                    'title' => 'Kadin Shop',
                    'description' => 'Official Kadin Marketplace Shop',
                    'address' => 'Cairo, Egypt',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            // Add Arabic translation
            DB::table('shop_translations')->updateOrInsert(
                ['shop_id' => $shopId, 'locale' => 'ar'],
                [
                    'shop_id' => $shopId,
                    'locale' => 'ar',
                    'title' => 'متجر كادين',
                    'description' => 'المتجر الرسمي لسوق كادين',
                    'address' => 'القاهرة، مصر',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
    
    private function createTestUser()
    {
        $user = User::firstOrCreate(
            ['email' => 'test@kadin.app'],
            [
                'uuid' => Str::uuid(),
                'firstname' => 'Test',
                'lastname' => 'User',
                'email' => 'test@kadin.app',
                'phone' => '01111111111',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'active' => true,
                'ip_address' => '127.0.0.1',
            ]
        );
        
        $user->syncRoles(['user']);
        
        // Create wallet for user
        DB::table('wallets')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'currency_id' => 1,
                'price' => 100.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
    
    private function addBasicSettings()
    {
        $settings = [
            ['key' => 'title', 'value' => 'Kadin Marketplace'],
            ['key' => 'description', 'value' => 'Your trusted marketplace'],
            ['key' => 'keywords', 'value' => 'marketplace, shopping, ecommerce'],
            ['key' => 'logo', 'value' => ''],
            ['key' => 'favicon', 'value' => ''],
            ['key' => 'currency_id', 'value' => '1'],
            ['key' => 'tax', 'value' => '0'],
            ['key' => 'delivery_fee', 'value' => '10'],
            ['key' => 'min_order_amount', 'value' => '50'],
        ];
        
        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
    
    private function addCurrencies()
    {
        $currencies = [
            ['title' => 'Egyptian Pound', 'symbol' => 'EGP', 'rate' => 1, 'default' => 1, 'active' => 1],
            ['title' => 'US Dollar', 'symbol' => 'USD', 'rate' => 0.032, 'default' => 0, 'active' => 1],
            ['title' => 'Euro', 'symbol' => 'EUR', 'rate' => 0.027, 'default' => 0, 'active' => 1],
        ];
        
        foreach ($currencies as $currency) {
            DB::table('currencies')->updateOrInsert(
                ['symbol' => $currency['symbol']],
                [
                    'title' => $currency['title'],
                    'symbol' => $currency['symbol'],
                    'rate' => $currency['rate'],
                    'default' => $currency['default'],
                    'active' => $currency['active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
    
    private function addLanguages()
    {
        $languages = [
            ['title' => 'العربية', 'locale' => 'ar', 'backward' => 1, 'default' => 1, 'active' => 1],
            ['title' => 'English', 'locale' => 'en', 'backward' => 0, 'default' => 0, 'active' => 1],
        ];
        
        foreach ($languages as $language) {
            DB::table('languages')->updateOrInsert(
                ['locale' => $language['locale']],
                [
                    'title' => $language['title'],
                    'locale' => $language['locale'],
                    'backward' => $language['backward'],
                    'default' => $language['default'],
                    'active' => $language['active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}