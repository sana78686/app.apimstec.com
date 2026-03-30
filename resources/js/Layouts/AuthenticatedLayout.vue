<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

import 'bootstrap/dist/css/bootstrap.min.css';
import '../../css/admin.css';

const sidebarOpen = ref(false);
const userDropdownOpen = ref(false);
const userDropdownEl = ref(null);

const page = usePage();
const user          = computed(() => page.props.auth?.user ?? {});
const domains       = computed(() => page.props.domains ?? []);
const activeDomain  = computed(() => page.props.activeDomain ?? null);
const domainDropOpen = ref(false);
const domainDropEl   = ref(null);

function switchDomain(id) {
  domainDropOpen.value = false;
  router.post(route('domains.switch'), { domain_id: id }, { preserveScroll: true });
}

/** Clear active site and open the picker (CMS is blocked until a domain is chosen). */
function openDomainPicker() {
  domainDropOpen.value = false;
  router.post(route('domains.switch'), { domain_id: null });
}

/** Which nav panel to show: driven by current route. Home = dashboard only, Lock = credentials, Pencil = content only, SEO = seo.*, Gear = account (profile, users, roles). */
const activeNavSection = computed(() => {
  const name = route().current() || '';
  if (name === 'dashboard') return 'dashboard';
  if (name.startsWith('credentials')) return 'credentials';
  if (name.startsWith('content-manager') || name.startsWith('pages.') || name.startsWith('blogs.')) return 'content';
  if (name.startsWith('seo.')) return 'seo';
  if (name.startsWith('profile') || name.startsWith('users.') || name.startsWith('roles.') || name.startsWith('domains')) return 'account';
  if (name.startsWith('media')) return 'media';
  return 'dashboard';
});

/**
 * SEO modules — ordered as an SEO person's step-by-step workflow.
 *
 * Step 1  Robots.txt Manager        → Allow/block crawler access
 * Step 2  Indexing Controls         → Choose what pages to index
 * Step 3  Sitemap Manager           → Submit pages to search engines
 * Step 4  Meta Manager              → Write title & description per page
 * Step 5  URL & Redirect Manager    → Fix URLs, set up redirects
 * Step 6  Content Optimization      → Optimise copy & keywords
 * Step 7  Image SEO Manager         → Add alt text, compress images
 * Step 8  Schema Markup Manager     → Add structured data / rich results
 * Step 9  Social Sharing (OG)       → Set Open Graph for social previews
 * Step 10 Performance & Speed       → Improve Core Web Vitals / page speed
 * Step 11 Broken Link & Error Mon.  → Find and fix crawl errors
 * Step 12 SEO Analytics & Reports   → Measure rankings and traffic
 */
const seoModules = [
  { slug: 'home-page',            name: '0. Home Page SEO' },
  { slug: 'robots',               name: '1. Robots.txt Manager' },
  { slug: 'indexing',             name: '2. Indexing Controls' },
  { slug: 'sitemap',              name: '3. Sitemap Manager' },
  { slug: 'meta-manager',         name: '4. Meta Manager' },
  { slug: 'url-redirects',        name: '5. URL & Redirect Manager' },
  { slug: 'content-optimization', name: '6. Content Optimization Tools' },
  { slug: 'image-seo',            name: '7. Image SEO Manager' },
  { slug: 'schema-markup',        name: '8. Schema Markup Manager' },
  { slug: 'social-sharing',       name: '9. Social Sharing (Open Graph)' },
  { slug: 'performance',          name: '10. Performance & Speed' },
  { slug: 'broken-links',         name: '11. Broken Link & Error Monitor' },
  { slug: 'analytics',            name: '12. SEO Analytics & Reports' },
];

/** Which SEO sub-module is active: Meta Manager list and create/edit both count as "meta-manager". */
const activeSeoModule = computed(() => {
  const name = route().current() || '';
  if (name === 'seo.meta-manager' || name === 'seo.meta-manager.create') return 'meta-manager';
  const match = name.match(/^seo\.([a-z0-9-]+)$/);
  return match ? match[1] : '';
});

/** Section title for the main header (Dashboard | Credentials | Content manager | Media library | SEO | Account). */
const sectionTitle = computed(() => {
  const s = activeNavSection.value;
  if (s === 'dashboard') return 'Dashboard';
  if (s === 'credentials') return 'Credentials';
  if (s === 'content') return 'Content manager';
  if (s === 'media') return 'Media library';
  if (s === 'seo') return 'SEO';
  if (s === 'account') return 'Account';
  return 'Dashboard';
});

function can(permission) {
  const perms = page.props.auth?.user?.permissions;
  if (!perms) return false;
  if (perms.includes('*')) return true;
  return perms.includes(permission);
}

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value;
}

function closeSidebar() {
  sidebarOpen.value = false;
}

function toggleUserDropdown() {
  userDropdownOpen.value = !userDropdownOpen.value;
}

function closeUserDropdown() {
  userDropdownOpen.value = false;
}

/** POST to a named maintenance route; optional confirm for destructive actions. */
function maintenancePost(routeName, confirmMessage = null) {
  if (confirmMessage && !window.confirm(confirmMessage)) {
    return;
  }
  closeUserDropdown();
  router.post(route(routeName), {}, { preserveScroll: true });
}

function onDocumentClick(e) {
  if (userDropdownOpen.value && userDropdownEl.value && !userDropdownEl.value.contains(e.target)) {
    closeUserDropdown();
  }
  if (domainDropOpen.value && domainDropEl.value && !domainDropEl.value.contains(e.target)) {
    domainDropOpen.value = false;
  }
}

onMounted(() => document.addEventListener('click', onDocumentClick));
onUnmounted(() => document.removeEventListener('click', onDocumentClick));
</script>

<template>
  <div class="admin-wrap">
    <div
      class="admin-sidebar-overlay"
      :class="{ 'is-open': sidebarOpen }"
      @click="closeSidebar"
      aria-hidden="true"
    />

    <!-- Narrow icon sidebar (Strapi-style left bar) -->
    <aside class="admin-icon-sidebar" :class="{ 'is-open': sidebarOpen }">
      <nav class="admin-icon-sidebar-nav" aria-label="Main">
        <Link
          :href="route('credentials.index')"
          class="admin-icon-sidebar-item"
          :class="{ 'is-active': activeNavSection === 'credentials' }"
          title="Credential management"
          @click="closeSidebar"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0110 0v4" />
          </svg>
        </Link>
        <Link
          :href="route('dashboard')"
          class="admin-icon-sidebar-item"
          :class="{ 'is-active': activeNavSection === 'dashboard' }"
          title="Dashboard"
          @click="closeSidebar"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
          </svg>
        </Link>
        <Link
          :href="route('content-manager.index')"
          class="admin-icon-sidebar-item"
          :class="{ 'is-active': activeNavSection === 'content' }"
          title="Content manager"
          @click="closeSidebar"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
          </svg>
        </Link>
        <Link
          :href="route('media.index')"
          class="admin-icon-sidebar-item"
          :class="{ 'is-active': activeNavSection === 'media' }"
          title="Media library"
          @click="closeSidebar"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
            <circle cx="8.5" cy="8.5" r="1.5" />
            <polyline points="21 15 16 10 5 21" />
          </svg>
        </Link>
        <Link
          :href="route('seo.meta-manager')"
          class="admin-icon-sidebar-item"
          :class="{ 'is-active': activeNavSection === 'seo' }"
          title="SEO"
          @click="closeSidebar"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="20" x2="18" y2="10" />
            <line x1="12" y1="20" x2="12" y2="4" />
            <line x1="6" y1="20" x2="6" y2="14" />
          </svg>
        </Link>
        <Link
          :href="route('domains.index')"
          class="admin-icon-sidebar-item"
          :class="{ 'is-active': activeNavSection === 'account' && (route().current() || '').startsWith('domains') }"
          title="Domains — switch website"
          @click="closeSidebar"
        >
          <!-- Globe icon -->
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
          </svg>
        </Link>
        <Link
          :href="route('profile.edit')"
          class="admin-icon-sidebar-item"
          :class="{ 'is-active': activeNavSection === 'account' }"
          title="Settings (Profile, Users, Roles)"
          @click="closeSidebar"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009.19 18a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" />
          </svg>
        </Link>
      </nav>
      <div class="admin-icon-sidebar-footer">
        <button
          type="button"
          class="admin-icon-sidebar-item admin-icon-sidebar-user"
          :title="user.name"
          @click="toggleUserDropdown"
        >
          <span class="admin-icon-sidebar-avatar">{{ (user.name || 'A').charAt(0).toUpperCase() }}</span>
        </button>
      </div>
    </aside>

    <!-- Wider secondary sidebar: logo at top (all modules) + nav -->
    <aside class="admin-nav-sidebar" :class="{ 'is-open': sidebarOpen }">
      <div class="admin-nav-sidebar-head">
        <Link :href="route('dashboard')" class="admin-nav-sidebar-logo" aria-label="Go to dashboard">
          <!-- Show active domain name instead of logo when a domain is selected -->
          <template v-if="activeDomain">
            <span class="admin-nav-domain-pill">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
              <span class="admin-nav-domain-pill-name">{{ activeDomain.name }}</span>
            </span>
          </template>
          <template v-else>
            <img src="/logos/compresspdf.png" alt="compressedPDF" class="admin-nav-sidebar-logo-img" />
          </template>
        </Link>
      </div>
      <nav class="admin-nav-sidebar-nav">
        <!-- Home icon: Dashboard only -->
        <template v-if="activeNavSection === 'dashboard'">
          <Link
            :href="route('dashboard')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': route().current('dashboard') }"
            @click="closeSidebar"
          >
            Dashboard
          </Link>
        </template>
        <!-- Lock icon: Credential management -->
        <template v-else-if="activeNavSection === 'credentials'">
          <Link
            :href="route('credentials.index')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': route().current('credentials.index') }"
            @click="closeSidebar"
          >
            Credential management
          </Link>
        </template>
        <!-- Pencil icon: Content manager (Home page + children: FAQ, Use cards; then Contact, Terms, Pages, Blogs) -->
        <template v-else-if="activeNavSection === 'content'">
          <div class="admin-nav-sidebar-group">
            <Link
              :href="route('content-manager.index')"
              class="admin-nav-sidebar-link"
              :class="{ 'is-active': route().current('content-manager.index') }"
              @click="closeSidebar"
            >
              Home page
            </Link>
            <div class="admin-nav-sidebar-sublinks">
              <Link
                :href="route('content-manager.home', { tab: 'faq' })"
                class="admin-nav-sidebar-sublink"
                :class="{ 'is-active': route().current('content-manager.home') && page.url.endsWith('/faq') }"
                @click="closeSidebar"
              >
                FAQ
              </Link>
              <Link
                :href="route('content-manager.home', { tab: 'use-cards' })"
                class="admin-nav-sidebar-sublink"
                :class="{ 'is-active': route().current('content-manager.home') && page.url.includes('/use-cards') }"
                @click="closeSidebar"
              >
                Use cards
              </Link>
            </div>
          </div>
          <Link
            :href="route('content-manager.contact')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': route().current('content-manager.contact') }"
            @click="closeSidebar"
          >
            Contact us page
          </Link>
          <Link
            :href="route('content-manager.terms')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': route().current('content-manager.terms') }"
            @click="closeSidebar"
          >
            Terms and conditions
          </Link>
          <Link
            :href="route('content-manager.privacy-policy')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': route().current('content-manager.privacy-policy') }"
            @click="closeSidebar"
          >
            Privacy policy
          </Link>
          <Link
            :href="route('content-manager.disclaimer')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': route().current('content-manager.disclaimer') }"
            @click="closeSidebar"
          >
            Disclaimer
          </Link>
          <Link
            :href="route('content-manager.about-us')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': route().current('content-manager.about-us') }"
            @click="closeSidebar"
          >
            About us
          </Link>
          <Link
            :href="route('content-manager.cookie-policy')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': route().current('content-manager.cookie-policy') }"
            @click="closeSidebar"
          >
            Cookie policy
          </Link>
          <Link
            :href="route('pages.index')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': (route().current() || '').startsWith('pages.') }"
            @click="closeSidebar"
          >
            Pages
          </Link>
          <Link
            :href="route('blogs.index')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': (route().current() || '').startsWith('blogs.') }"
            @click="closeSidebar"
          >
            Blogs
          </Link>
        </template>
        <!-- Gear icon: Account (Profile, Users, Roles, Domains) -->
        <template v-else-if="activeNavSection === 'account'">
          <Link
            :href="route('profile.edit')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': route().current('profile.edit') }"
            @click="closeSidebar"
          >
            Profile
          </Link>
          <Link
            v-if="can('users.view')"
            :href="route('users.index')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': (route().current() || '').startsWith('users.') }"
            @click="closeSidebar"
          >
            Users
          </Link>
          <Link
            v-if="can('roles.view')"
            :href="route('roles.index')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': (route().current() || '').startsWith('roles.') }"
            @click="closeSidebar"
          >
            Roles
          </Link>
          <Link
            :href="route('domains.index')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': (route().current() || '').startsWith('domains') }"
            @click="closeSidebar"
          >
            🌐 Domains
          </Link>
        </template>
        <!-- Media icon: Media library only -->
        <template v-else-if="activeNavSection === 'media'">
          <Link
            :href="route('media.index')"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': route().current('media.index') }"
            @click="closeSidebar"
          >
            Media library
          </Link>
        </template>
        <!-- SEO icon: all 12 SEO modules -->
        <template v-else-if="activeNavSection === 'seo'">
          <Link
            v-for="m in seoModules"
            :key="m.slug"
            :href="route('seo.' + m.slug)"
            class="admin-nav-sidebar-link"
            :class="{ 'is-active': m.slug === activeSeoModule }"
            @click="closeSidebar"
          >
            {{ m.name }}
          </Link>
        </template>
        <template v-else>
          <Link
            :href="route('dashboard')"
            class="admin-nav-sidebar-link"
            @click="closeSidebar"
          >
            Dashboard
          </Link>
        </template>
      </nav>
    </aside>

    <div class="admin-main-wrap">
      <header class="admin-header">
        <div class="admin-header-left">
          <button
            type="button"
            class="admin-header-menu-btn"
            aria-label="Toggle menu"
            @click="toggleSidebar"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="3" y1="12" x2="21" y2="12" />
              <line x1="3" y1="6" x2="21" y2="6" />
              <line x1="3" y1="18" x2="21" y2="18" />
            </svg>
          </button>
          <!-- Mobile: show domain name when active, otherwise logo -->
          <Link :href="route('dashboard')" class="admin-header-logo" aria-label="Go to dashboard">
            <template v-if="activeDomain">
              <span class="admin-nav-domain-pill admin-nav-domain-pill--sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <span class="admin-nav-domain-pill-name">{{ activeDomain.name }}</span>
              </span>
            </template>
            <template v-else>
              <img src="/logos/compresspdf.png" alt="compressedPDF" class="admin-header-logo-img" />
            </template>
          </Link>
          <span class="admin-header-section-title">{{ sectionTitle }}</span>
        </div>

        <div class="admin-header-right">

          <!-- Domain switcher pill -->
          <div v-if="domains.length" class="admin-domain-menu" ref="domainDropEl" style="position:relative;margin-right:.75rem;">
            <button
              type="button"
              class="admin-domain-btn"
              @click="domainDropOpen = !domainDropOpen"
              style="display:flex;align-items:center;gap:.4rem;padding:.3rem .75rem;border-radius:20px;border:1px solid var(--admin-card-border,#eaeaef);background:var(--admin-card-bg,#fff);font-size:.8125rem;cursor:pointer;white-space:nowrap;"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
              <span>{{ activeDomain ? activeDomain.name : 'Choose website' }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div
              v-show="domainDropOpen"
              style="position:absolute;top:calc(100% + 6px);right:0;min-width:200px;background:#fff;border:1px solid var(--admin-card-border,#eaeaef);border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);z-index:1000;overflow:hidden;"
            >
              <button
                type="button"
                style="display:block;width:100%;text-align:left;padding:.5rem .9rem;font-size:.8125rem;border:none;background:none;cursor:pointer;"
                @click="openDomainPicker"
              >
                Choose / change website…
              </button>
              <hr style="margin:0;" />
              <button
                v-for="d in domains"
                :key="d.id"
                type="button"
                style="display:block;width:100%;text-align:left;padding:.5rem .9rem;font-size:.8125rem;border:none;background:none;cursor:pointer;"
                :style="activeDomain?.id === d.id ? 'font-weight:600;color:var(--admin-primary,#4945ff)' : ''"
                @click="switchDomain(d.id)"
              >
                {{ d.name }}
                <span style="font-size:.7rem;color:#888;margin-left:.3rem;">{{ d.domain }}</span>
              </button>
              <hr style="margin:0;" />
              <Link
                :href="route('domains.index')"
                style="display:block;padding:.5rem .9rem;font-size:.8125rem;color:var(--admin-primary,#4945ff);text-decoration:none;"
                @click="domainDropOpen = false"
              >
                Manage domains →
              </Link>
            </div>
          </div>

          <div class="admin-user-menu" ref="userDropdownEl">
            <button
              type="button"
              class="admin-user-btn"
              @click="toggleUserDropdown"
              aria-haspopup="true"
              :aria-expanded="userDropdownOpen"
            >
              <span>{{ user.name }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9" />
              </svg>
            </button>
            <div
              v-show="userDropdownOpen"
              class="admin-user-dropdown"
            >
              <Link :href="route('profile.edit')" @click="closeUserDropdown">Profile</Link>
              <button type="button" class="admin-user-dropdown-action" @click="closeUserDropdown(); openDomainPicker();">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                Choose website
              </button>
              <hr class="admin-user-dropdown-divider" />
              <p class="admin-user-dropdown-label">Active site database</p>
              <button
                type="button"
                class="admin-user-dropdown-action"
                @click="maintenancePost('maintenance.migrate')"
              >
                Run migrations
              </button>
              <button
                type="button"
                class="admin-user-dropdown-action"
                @click="maintenancePost('maintenance.seed', 'Run tenant seed on the active site database?')"
              >
                Seed tenant (TenantDatabaseSeeder)
              </button>
              <button
                type="button"
                class="admin-user-dropdown-action admin-user-dropdown-danger"
                @click="maintenancePost('maintenance.rollback', 'Roll back the last migration batch on the active site database?')"
              >
                Roll back last batch
              </button>
              <button
                type="button"
                class="admin-user-dropdown-action admin-user-dropdown-danger"
                @click="maintenancePost('maintenance.migrate-fresh', 'This will DELETE ALL TABLES in the active site database and re-run migrations. Continue?')"
              >
                Fresh migrate (wipe site DB)
              </button>
              <hr class="admin-user-dropdown-divider" />
              <p class="admin-user-dropdown-label">Application caches</p>
              <button
                type="button"
                class="admin-user-dropdown-action"
                @click="maintenancePost('maintenance.optimize-clear')"
              >
                Optimize clear
              </button>
              <button
                type="button"
                class="admin-user-dropdown-action"
                @click="maintenancePost('maintenance.config-clear')"
              >
                Config clear
              </button>
              <button
                type="button"
                class="admin-user-dropdown-action"
                @click="maintenancePost('maintenance.cache-clear')"
              >
                Cache clear
              </button>
              <hr class="admin-user-dropdown-divider" />
              <Link :href="route('logout')" method="post" as="button" @click="closeUserDropdown">
                Log out
              </Link>
            </div>
          </div>
        </div>
      </header>

      <main class="admin-content">
        <p v-if="page.props.flash?.success" class="admin-flash admin-flash-success" role="status">{{ page.props.flash.success }}</p>
        <p v-if="page.props.flash?.error" class="admin-flash admin-flash-error" role="alert">{{ page.props.flash.error }}</p>
        <slot />
      </main>
    </div>
  </div>
</template>
