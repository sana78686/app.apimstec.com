<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users module
            ['name' => 'Manage users', 'slug' => 'users.manage', 'group' => 'users'],
            ['name' => 'View users', 'slug' => 'users.view', 'group' => 'users'],
            ['name' => 'Create users', 'slug' => 'users.create', 'group' => 'users'],
            ['name' => 'Edit users', 'slug' => 'users.edit', 'group' => 'users'],
            ['name' => 'Delete users', 'slug' => 'users.delete', 'group' => 'users'],
            // Roles module
            ['name' => 'Manage roles', 'slug' => 'roles.manage', 'group' => 'roles'],
            ['name' => 'View roles', 'slug' => 'roles.view', 'group' => 'roles'],
            ['name' => 'Create roles', 'slug' => 'roles.create', 'group' => 'roles'],
            ['name' => 'Edit roles', 'slug' => 'roles.edit', 'group' => 'roles'],
            ['name' => 'Delete roles', 'slug' => 'roles.delete', 'group' => 'roles'],
            // Content module
            ['name' => 'Manage content', 'slug' => 'content.manage', 'group' => 'content'],
            ['name' => 'View content', 'slug' => 'content.view', 'group' => 'content'],
            ['name' => 'Create content', 'slug' => 'content.create', 'group' => 'content'],
            ['name' => 'Edit content', 'slug' => 'content.edit', 'group' => 'content'],
            ['name' => 'Delete content', 'slug' => 'content.delete', 'group' => 'content'],
            // SEO module
            ['name' => 'Manage SEO', 'slug' => 'seo.manage', 'group' => 'seo'],
            ['name' => 'View SEO', 'slug' => 'seo.view', 'group' => 'seo'],
            ['name' => 'Edit meta tags', 'slug' => 'seo.meta.edit', 'group' => 'seo'],
            ['name' => 'Manage sitemap', 'slug' => 'seo.sitemap.manage', 'group' => 'seo'],
            ['name' => 'Manage redirects', 'slug' => 'seo.redirects.manage', 'group' => 'seo'],
            ['name' => 'View SEO analytics', 'slug' => 'seo.analytics.view', 'group' => 'seo'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['slug' => $p['slug']],
                $p
            );
        }
    }
}
