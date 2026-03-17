<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\User;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Find the user by email
    $user = User::where('email', 'kadin@gmail.com')->first();
    
    if (!$user) {
        echo "❌ User with email 'kadin@gmail.com' not found!\n";
        exit(1);
    }
    
    echo "✅ Found user: {$user->firstname} {$user->lastname} ({$user->email})\n";
    echo "📋 Current roles: " . implode(', ', $
user->getRoleNames()->toArray()) . "\n";
    
    // Sync roles to match owner (admin + seller)
    $user->syncRoles(['admin', 'seller']);
    
    echo "🔄 Updated roles to: admin, seller\n";
    echo "✅ User role updated successfully!\n";
    
    // Verify the change
    $user->refresh();
    echo "📋 New roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}