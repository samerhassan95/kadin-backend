<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComprehensiveSeeder extends Seeder
{
    public function run()
    {
        // Add Brands
        $this->addBrands();
        
        // Add Banners
        $this->addBanners();
        
        // Add Products
        $this->addProducts();
        
        // Add Extra Groups
        $this->addExtraGroups();
        
        // Add Property Groups
        $this->addPropertyGroups();
        
        // Add Blogs
        $this->addBlogs();
        
        // Add FAQs
        $this->addFaqs();
        
        // Add Coupons
        $this->addCoupons();
        
        // Add Areas
        $this->addAreas();
        
        echo "Comprehensive seeding completed!\n";
    }
    
    private function addBrands()
    {
        $brands = ['Nike', 'Adidas', 'Samsung', 'Apple', 'Sony'];
        
        foreach ($brands as $brand) {
            DB::table('brands')->insert([
                'uuid' => Str::uuid(),
                'title' => $brand,
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        echo "Brands added: " . count($brands) . "\n";
    }
    
    private function addBanners()
    {
        for ($i = 1; $i <= 3; $i++) {
            $bannerId = DB::table('banners')->insertGetId([
                'active' => 1,
                'clickable' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('banner_translations')->insert([
                'banner_id' => $bannerId,
                'locale' => 'en',
                'title' => 'Banner ' . $i,
                'description' => 'Description for banner ' . $i
            ]);
        }
        
        echo "Banners added: 3\n";
    }
    
    private function addProducts()
    {
        $shopId = DB::table('shops')->first()->id ?? 1;
        $categoryId = DB::table('categories')->first()->id ?? 1;
        $brandId = DB::table('brands')->first()->id ?? null;
        $unitId = DB::table('units')->first()->id ?? 1;
        
        $products = [
            ['name' => 'Laptop', 'price' => 1500],
            ['name' => 'Smartphone', 'price' => 800],
            ['name' => 'Headphones', 'price' => 150],
            ['name' => 'Smart Watch', 'price' => 300],
            ['name' => 'Tablet', 'price' => 600],
        ];
        
        foreach ($products as $product) {
            $productId = DB::table('products')->insertGetId([
                'uuid' => Str::uuid(),
                'shop_id' => $shopId,
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'unit_id' => $unitId,
                'active' => 1,
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('product_translations')->insert([
                'product_id' => $productId,
                'locale' => 'en',
                'title' => $product['name'],
                'description' => 'High quality ' . $product['name']
            ]);
            
            // Add stock
            DB::table('stocks')->insert([
                'product_id' => $productId,
                'price' => $product['price'],
                'quantity' => rand(10, 100),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        echo "Products added: " . count($products) . "\n";
    }
    
    private function addExtraGroups()
    {
        $groups = ['Size', 'Color', 'Material'];
        
        foreach ($groups as $group) {
            $groupId = DB::table('extra_groups')->insertGetId([
                'active' => 1
            ]);
            
            DB::table('extra_group_translations')->insert([
                'extra_group_id' => $groupId,
                'locale' => 'en',
                'title' => $group
            ]);
            
            // Add values for each group
            if ($group === 'Size') {
                $values = ['Small', 'Medium', 'Large', 'XL'];
            } elseif ($group === 'Color') {
                $values = ['Red', 'Blue', 'Green', 'Black', 'White'];
            } else {
                $values = ['Cotton', 'Polyester', 'Leather'];
            }
            
            foreach ($values as $value) {
                DB::table('extra_values')->insert([
                    'extra_group_id' => $groupId,
                    'value' => $value,
                    'active' => 1
                ]);
            }
        }
        
        echo "Extra Groups added: " . count($groups) . "\n";
    }
    
    private function addPropertyGroups()
    {
        $properties = ['Screen Size', 'RAM', 'Storage', 'Battery'];
        
        foreach ($properties as $property) {
            $propertyId = DB::table('property_groups')->insertGetId([
                'active' => 1
            ]);
            
            DB::table('property_group_translations')->insert([
                'property_group_id' => $propertyId,
                'locale' => 'en',
                'title' => $property
            ]);
        }
        
        echo "Property Groups added: " . count($properties) . "\n";
    }
    
    private function addBlogs()
    {
        $userId = DB::table('users')->first()->id ?? 1;
        
        for ($i = 1; $i <= 5; $i++) {
            $blogId = DB::table('blogs')->insertGetId([
                'uuid' => Str::uuid(),
                'user_id' => $userId,
                'active' => 1,
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('blog_translations')->insert([
                'blog_id' => $blogId,
                'locale' => 'en',
                'title' => 'Blog Post ' . $i,
                'description' => 'This is the description for blog post ' . $i,
                'short_desc' => 'Short description for blog ' . $i
            ]);
        }
        
        echo "Blogs added: 5\n";
    }
    
    private function addFaqs()
    {
        $faqs = [
            ['q' => 'How to place an order?', 'a' => 'You can place an order by selecting products and proceeding to checkout.'],
            ['q' => 'What payment methods do you accept?', 'a' => 'We accept credit cards, PayPal, and cash on delivery.'],
            ['q' => 'How long does shipping take?', 'a' => 'Shipping usually takes 3-5 business days.'],
            ['q' => 'Can I return a product?', 'a' => 'Yes, you can return products within 14 days of purchase.'],
        ];
        
        foreach ($faqs as $faq) {
            $faqId = DB::table('faqs')->insertGetId([
                'uuid' => Str::uuid(),
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('faq_translations')->insert([
                'faq_id' => $faqId,
                'locale' => 'en',
                'question' => $faq['q'],
                'answer' => $faq['a']
            ]);
        }
        
        echo "FAQs added: " . count($faqs) . "\n";
    }
    
    private function addCoupons()
    {
        $shopId = DB::table('shops')->first()->id ?? 1;
        
        for ($i = 1; $i <= 3; $i++) {
            $couponId = DB::table('coupons')->insertGetId([
                'shop_id' => $shopId,
                'name' => 'SAVE' . ($i * 10),
                'type' => 'percent',
                'price' => $i * 10,
                'qty' => 100,
                'expired_at' => now()->addMonths(3),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::table('coupon_translations')->insert([
                'coupon_id' => $couponId,
                'locale' => 'en',
                'title' => 'Save ' . ($i * 10) . '% Discount',
                'description' => 'Get ' . ($i * 10) . '% off on your purchase'
            ]);
        }
        
        echo "Coupons added: 3\n";
    }
    
    private function addAreas()
    {
        $cityId = DB::table('cities')->first()->id ?? 1;
        $regionId = DB::table('regions')->first()->id ?? 1;
        
        $areas = ['Downtown', 'Suburb', 'Industrial Zone'];
        
        foreach ($areas as $area) {
            $areaId = DB::table('areas')->insertGetId([
                'region_id' => $regionId,
                'city_id' => $cityId,
                'active' => 1
            ]);
            
            DB::table('area_translations')->insert([
                'area_id' => $areaId,
                'locale' => 'en',
                'title' => $area
            ]);
        }
        
        echo "Areas added: " . count($areas) . "\n";
    }
}
