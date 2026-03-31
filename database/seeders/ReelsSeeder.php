<?php

namespace Database\Seeders;

use App\Models\Reel;
use App\Models\Shop;
use Illuminate\Database\Seeder;

class ReelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get first shop or create one if none exists
        $shop = Shop::first();
        
        if (!$shop) {
            // Create a basic shop for testing
            $shop = Shop::create([
                'uuid' => 'test-shop-uuid',
                'slug' => 'test-shop',
                'user_id' => 1, // Assuming user with ID 1 exists
                'tax' => 5.0,
                'delivery_range' => 10,
                'percentage' => 10,
                'phone' => '+1234567890',
                'open' => true,
                'visibility' => true,
                'open_time' => '08:00',
                'close_time' => '22:00',
                'background_img' => 'https://example.com/bg.jpg',
                'logo_img' => 'https://example.com/logo.jpg',
                'min_amount' => 50,
                'status' => 'approved',
                'status_note' => 'ok',
                'rating_avg' => 4.5,
            ]);
        }

        // Create sample reels
        $reels = [
            [
                'shop_id' => $shop->id,
                'video_url' => 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4',
                'description' => 'Amazing product showcase - Check out our latest collection!',
                'active' => true,
                'likes_count' => 234,
            ],
            [
                'shop_id' => $shop->id,
                'video_url' => 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_2mb.mp4',
                'description' => 'Behind the scenes of our store - See how we prepare your orders',
                'active' => true,
                'likes_count' => 156,
            ],
            [
                'shop_id' => $shop->id,
                'video_url' => 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_5mb.mp4',
                'description' => 'Customer testimonial - Happy customers sharing their experience',
                'active' => true,
                'likes_count' => 89,
            ],
        ];

        foreach ($reels as $reelData) {
            Reel::create($reelData);
        }
    }
}