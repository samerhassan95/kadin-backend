<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShopsTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get or create a user for the shop
        $user = User::first();
        
        if (!$user) {
            $user = User::create([
                'firstname' => 'Test',
                'lastname' => 'User',
                'email' => 'test@example.com',
                'phone' => '+1234567890',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Create test shops
        $shops = [
            [
                'uuid' => 'test-shop-1',
                'slug' => 'electronics-store',
                'user_id' => $user->id,
                'tax' => 5.0,
                'delivery_range' => 10,
                'percentage' => 10,
                'phone' => '+1234567890',
                'open' => true,
                'visibility' => true,
                'open_time' => '08:00',
                'close_time' => '22:00',
                'background_img' => 'https://example.com/bg1.jpg',
                'logo_img' => 'https://example.com/logo1.jpg',
                'min_amount' => 50,
                'status' => 'approved',
                'status_note' => 'ok',
                'rating_avg' => 4.5,
            ],
            [
                'uuid' => 'test-shop-2',
                'slug' => 'fashion-boutique',
                'user_id' => $user->id,
                'tax' => 8.0,
                'delivery_range' => 15,
                'percentage' => 12,
                'phone' => '+1234567891',
                'open' => true,
                'visibility' => true,
                'open_time' => '09:00',
                'close_time' => '21:00',
                'background_img' => 'https://example.com/bg2.jpg',
                'logo_img' => 'https://example.com/logo2.jpg',
                'min_amount' => 30,
                'status' => 'approved',
                'status_note' => 'ok',
                'rating_avg' => 4.2,
            ],
            [
                'uuid' => 'test-shop-3',
                'slug' => 'food-corner',
                'user_id' => $user->id,
                'tax' => 6.0,
                'delivery_range' => 8,
                'percentage' => 15,
                'phone' => '+1234567892',
                'open' => true,
                'visibility' => true,
                'open_time' => '10:00',
                'close_time' => '23:00',
                'background_img' => 'https://example.com/bg3.jpg',
                'logo_img' => 'https://example.com/logo3.jpg',
                'min_amount' => 25,
                'status' => 'approved',
                'status_note' => 'ok',
                'rating_avg' => 4.8,
            ],
        ];

        foreach ($shops as $shopData) {
            $shop = Shop::create($shopData);
            
            // Create shop translation
            $shop->translations()->create([
                'locale' => 'en',
                'title' => $this->getShopTitle($shopData['slug']),
                'description' => $this->getShopDescription($shopData['slug']),
                'address' => $this->getShopAddress($shopData['slug']),
            ]);
        }
    }

    private function getShopTitle($slug)
    {
        $titles = [
            'electronics-store' => 'Electronics Store',
            'fashion-boutique' => 'Fashion Boutique',
            'food-corner' => 'Food Corner',
        ];

        return $titles[$slug] ?? 'Test Shop';
    }

    private function getShopDescription($slug)
    {
        $descriptions = [
            'electronics-store' => 'Your one-stop shop for all electronic devices and gadgets.',
            'fashion-boutique' => 'Trendy fashion and accessories for modern lifestyle.',
            'food-corner' => 'Delicious food and beverages delivered fresh to your door.',
        ];

        return $descriptions[$slug] ?? 'A great shop for all your needs.';
    }

    private function getShopAddress($slug)
    {
        $addresses = [
            'electronics-store' => '123 Tech Street, Digital City',
            'fashion-boutique' => '456 Fashion Ave, Style District',
            'food-corner' => '789 Food Plaza, Taste Town',
        ];

        return $addresses[$slug] ?? '123 Main Street, Test City';
    }
}