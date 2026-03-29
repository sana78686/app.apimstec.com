<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([PermissionSeeder::class]);

        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Full access. This role cannot be deleted or have its permissions changed.',
                'is_system' => true,
            ]
        );
        $admin->permissions()->sync(Permission::pluck('id'));

        // SEO Manager – full SEO + all content permissions
        $seoManager = Role::firstOrCreate(
            ['slug' => 'seo-manager'],
            [
                'name' => 'SEO Manager',
                'description' => 'Full SEO access: meta tags, sitemap, redirects, analytics. Can also manage content.',
                'is_system' => false,
            ]
        );
        $seoManager->permissions()->sync(
            Permission::whereIn('group', ['seo', 'content'])->pluck('id')
        );

        // SEO Editor – all SEO permissions + view content only
        $seoEditor = Role::firstOrCreate(
            ['slug' => 'seo-editor'],
            [
                'name' => 'SEO Editor',
                'description' => 'Edit meta tags, manage redirects and sitemap, view SEO analytics and content.',
                'is_system' => false,
            ]
        );
        $seoEditor->permissions()->sync(
            Permission::where('group', 'seo')->pluck('id')
                ->merge(Permission::where('slug', 'content.view')->pluck('id'))
                ->unique()->values()
        );

        // Content Editor – all content permissions + edit meta tags (for SEO on content)
        $contentEditor = Role::firstOrCreate(
            ['slug' => 'content-editor'],
            [
                'name' => 'Content Editor',
                'description' => 'Create and edit content with meta tags. No user/role or full SEO settings access.',
                'is_system' => false,
            ]
        );
        $contentEditor->permissions()->sync(
            Permission::where('group', 'content')->pluck('id')
                ->merge(Permission::where('slug', 'seo.meta.edit')->pluck('id'))
                ->unique()->values()
        );
    }
}
