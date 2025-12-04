<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes">
  <title>@yield('title', 'Chantix')</title>
  <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/logos/favicon.png') }}" />
  <link rel="stylesheet" href="{{ asset('assets/css/styles.min.css') }}" />
</head>

<body>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed" style="overflow-x: hidden;">
    <!-- Sidebar Start -->
    <aside class="left-sidebar">
      <!-- Sidebar scroll-->
      <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
          <a href="{{ route('dashboard') }}" class="text-nowrap logo-img">
            <img src="{{ asset('assets/images/logos/dark-logo.svg') }}" width="180" alt="" />
          </a>
          <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
            <i class="ti ti-x fs-8"></i>
          </div>
        </div>
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
          <ul id="sidebarnav">
            <li class="nav-small-cap">
              <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
              <span class="hide-menu">Home</span>
            </li>
            @php
              $user = auth()->user();
              $currentRole = $user->currentRole();
              $roleName = $currentRole ? $currentRole->name : null;
            @endphp
            
            @if($user->isSuperAdmin())
              <li class="sidebar-item">
                <a class="sidebar-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.users-validation') }}" aria-expanded="false">
                  <span>
                    <i class="ti ti-shield-check"></i>
                  </span>
                  <span class="hide-menu">Validation Utilisateurs</span>
                </a>
              </li>
            @endif
            
            <li class="sidebar-item">
              <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" aria-expanded="false">
                <span>
                  <i class="ti ti-layout-dashboard"></i>
                </span>
                <span class="hide-menu">Dashboard</span>
              </a>
            </li>
            <li class="nav-small-cap">
              <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
              <span class="hide-menu">GESTION</span>
            </li>
            @php
              $user = auth()->user();
              $currentRole = $user->currentRole();
              $roleName = $currentRole ? $currentRole->name : null;
            @endphp
            
            {{-- Projets - Visible pour tous les rôles --}}
            @if($user->hasPermission('projects.view'))
              <li class="sidebar-item">
                <a class="sidebar-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}" aria-expanded="false">
                  <span>
                    <i class="ti ti-building"></i>
                  </span>
                  <span class="hide-menu">Projets</span>
                </a>
              </li>
            @endif

            {{-- Matériaux - Visible pour Admin et Chef de Chantier --}}
            @if($user->hasPermission('materials.manage') || $user->hasRoleInCompany('admin'))
              <li class="sidebar-item">
                <a class="sidebar-link {{ request()->routeIs('materials.*') ? 'active' : '' }}" href="{{ route('materials.index') }}" aria-expanded="false">
                  <span>
                    <i class="ti ti-package"></i>
                  </span>
                  <span class="hide-menu">Matériaux</span>
                </a>
              </li>
            @endif

            {{-- Employés - Visible pour Admin et Chef de Chantier (pour gérer l'équipe) --}}
            @if($user->hasPermission('projects.manage_team') || $user->hasRoleInCompany('admin'))
              <li class="sidebar-item">
                <a class="sidebar-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}" aria-expanded="false">
                  <span>
                    <i class="ti ti-users"></i>
                  </span>
                  <span class="hide-menu">Employés</span>
                </a>
              </li>
            @endif

            {{-- Entreprises - Visible uniquement pour Super Admin --}}
            @if($user->isSuperAdmin())
              <li class="sidebar-item">
                <a class="sidebar-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}" aria-expanded="false">
                  <span>
                    <i class="ti ti-building-warehouse"></i>
                  </span>
                  <span class="hide-menu">Entreprises</span>
                </a>
              </li>
            @endif

            {{-- Invitations - Visible pour les Admins (pas super admin) --}}
            @if($user->hasRoleInCompany('admin') && !$user->isSuperAdmin() && $user->current_company_id)
              @php
                $currentCompany = \App\Models\Company::find($user->current_company_id);
              @endphp
              @if($currentCompany)
                <li class="sidebar-item">
                  <a class="sidebar-link {{ request()->routeIs('invitations.*') ? 'active' : '' }}" href="{{ route('invitations.index', $currentCompany) }}" aria-expanded="false">
                    <span>
                      <i class="ti ti-user-plus"></i>
                    </span>
                    <span class="hide-menu">Invitations</span>
                  </a>
                </li>
              @endif
            @endif
          </ul>
        </nav>
        <!-- End Sidebar navigation -->
      </div>
      <!-- End Sidebar scroll-->
    </aside>
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
      <!--  Header Start -->
      <header class="app-header">
        <nav class="navbar navbar-expand-lg navbar-light">
          <ul class="navbar-nav">
            <li class="nav-item d-block d-xl-none">
              <a class="nav-link sidebartoggler nav-icon-hover" id="headerCollapse" href="javascript:void(0)">
                <i class="ti ti-menu-2"></i>
              </a>
            </li>
          </ul>
          <!-- Barre de recherche globale -->
          <div class="d-none d-md-flex align-items-center me-3" style="max-width: 300px;">
            <form action="{{ route('dashboard') }}" method="GET" class="w-100">
              <div class="input-group">
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Rechercher projets, matériaux, employés..." value="{{ request('search') }}" id="globalSearch">
                <button class="btn btn-outline-primary btn-sm" type="submit" title="Rechercher">
                  <i class="ti ti-search"></i>
                </button>
                @if(request('search'))
                  <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm" title="Effacer">
                    <i class="ti ti-x"></i>
                  </a>
                @endif
              </div>
            </form>
          </div>
          <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
            <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
              <li class="nav-item dropdown me-3">
                <a class="nav-link nav-icon-hover position-relative" href="javascript:void(0)" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="ti ti-bell-ringing"></i>
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
                    <span id="notificationCount">0</span>
                  </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-lg" aria-labelledby="notificationDropdown" style="max-width: 400px; max-height: 500px; overflow-y: auto; right: 0; left: auto;">
                  <li class="dropdown-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Notifications</h6>
                    <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-link p-0">Voir tout</a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <div id="notificationsList">
                    <li class="px-3 py-2 text-center text-muted">
                      <small>Chargement...</small>
                    </li>
                  </div>
                  <li><hr class="dropdown-divider"></li>
                  <li class="px-3 py-2">
                    <a href="{{ route('notifications.read-all') }}" class="btn btn-sm btn-outline-primary w-100" onclick="event.preventDefault(); document.getElementById('markAllReadForm').submit();">
                      <i class="ti ti-check me-1"></i> Tout marquer comme lu
                    </a>
                    <form id="markAllReadForm" action="{{ route('notifications.read-all') }}" method="POST" style="display: none;">
                      @csrf
                    </form>
                  </li>
                </ul>
              </li>
              @if(auth()->user()->currentCompany)
                <li class="nav-item me-3">
                  <div class="d-flex flex-column align-items-end">
                    <span class="text-dark fw-semibold">{{ auth()->user()->currentCompany->name }}</span>
                    @if(auth()->user()->currentRole())
                      <small class="text-muted">
                        <i class="ti ti-user me-1"></i>
                        {{ auth()->user()->currentRole()->display_name }}
                      </small>
                    @endif
                  </div>
                </li>
              @endif
              <li class="nav-item dropdown">
                <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown"
                  aria-expanded="false">
                  <img src="{{ asset('assets/images/profile/user-1.jpg') }}" alt="" width="35" height="35" class="rounded-circle">
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
                  <div class="message-body">
                    <div class="px-3 py-2 border-bottom">
                      <p class="mb-0 fw-semibold">{{ auth()->user()->name }}</p>
                      <small class="text-muted">{{ auth()->user()->email }}</small>
                      @if(auth()->user()->currentRole())
                        <div class="mt-2">
                          <span class="badge bg-primary">{{ auth()->user()->currentRole()->display_name }}</span>
                        </div>
                      @endif
                    </div>
                    {{-- Mes Entreprises - Visible uniquement pour Super Admin --}}
                    @if(auth()->user()->isSuperAdmin())
                      <a href="{{ route('companies.index') }}" class="d-flex align-items-center gap-2 dropdown-item">
                        <i class="ti ti-building-warehouse fs-6"></i>
                        <p class="mb-0 fs-3">Mes Entreprises</p>
                      </a>
                    @endif
                    <a href="{{ route('profile.index') }}" class="d-flex align-items-center gap-2 dropdown-item">
                      <i class="ti ti-user fs-6"></i>
                      <p class="mb-0 fs-3">Mon Profil</p>
                    </a>
                    <a href="javascript:void(0)" class="d-flex align-items-center gap-2 dropdown-item">
                      <i class="ti ti-settings fs-6"></i>
                      <p class="mb-0 fs-3">Paramètres</p>
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="mx-3 mt-2">
                      @csrf
                      <button type="submit" class="btn btn-outline-primary w-100">Déconnexion</button>
                    </form>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </nav>
      </header>
      <!--  Header End -->
      <div class="container-fluid">
        @yield('content')
        <div class="py-6 px-6 text-center">
          <p class="mb-0 fs-4">Design and Developed by <a href="https://adminmart.com/" target="_blank" class="pe-1 text-primary text-decoration-underline">AdminMart.com</a> Distributed by <a href="https://themewagon.com">ThemeWagon</a></p>
        </div>
      </div>
    </div>
  </div>
  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    @if(session('success'))
      <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
        <div class="toast-header bg-success text-white">
          <i class="ti ti-check me-2"></i>
          <strong class="me-auto">Succès</strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
          {{ session('success') }}
        </div>
      </div>
    @endif
    
    @if(session('error'))
      <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
        <div class="toast-header bg-danger text-white">
          <i class="ti ti-alert-circle me-2"></i>
          <strong class="me-auto">Erreur</strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
          {{ session('error') }}
        </div>
      </div>
    @endif
    
    @if(session('info'))
      <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
        <div class="toast-header bg-info text-white">
          <i class="ti ti-info-circle me-2"></i>
          <strong class="me-auto">Information</strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
          {{ session('info') }}
        </div>
      </div>
    @endif
    
    @if(session('warning'))
      <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
        <div class="toast-header bg-warning text-dark">
          <i class="ti ti-alert-triangle me-2"></i>
          <strong class="me-auto">Attention</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
          {{ session('warning') }}
        </div>
      </div>
    @endif
  </div>
  
  <script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/sidebarmenu.js') }}"></script>
  <script src="{{ asset('assets/js/app.min.js') }}"></script>
  <script src="{{ asset('assets/libs/simplebar/dist/simplebar.js') }}"></script>
  
  <!-- Notifications en temps réel -->
  <script>
    $(document).ready(function() {
      // Charger les notifications au chargement de la page
      loadNotifications();
      
      // Actualiser les notifications toutes les 30 secondes
      setInterval(loadNotifications, 30000);
      
      function loadNotifications() {
        // Charger le nombre de notifications non lues
        $.get('{{ route("notifications.unread-count") }}', function(data) {
          const count = data.count || 0;
          const badge = $('#notificationBadge');
          const countSpan = $('#notificationCount');
          
          if (count > 0) {
            badge.show();
            countSpan.text(count > 99 ? '99+' : count);
          } else {
            badge.hide();
          }
        });
        
        // Charger les dernières notifications
        $.get('{{ route("notifications.latest") }}', function(notifications) {
          const list = $('#notificationsList');
          list.empty();
          
          if (notifications.length === 0) {
            list.html('<li class="px-3 py-4 text-center text-muted"><small>Aucune notification</small></li>');
            return;
          }
          
          notifications.forEach(function(notification) {
            const icon = getNotificationIcon(notification.type);
            const timeAgo = getTimeAgo(notification.created_at);
            const item = `
              <li class="dropdown-item-text px-3 py-2 ${!notification.is_read ? 'bg-light' : ''}" style="cursor: pointer;" onclick="window.location.href='${notification.link || '#'}'">
                <div class="d-flex align-items-start">
                  <div class="flex-shrink-0 me-2">
                    <i class="ti ${icon} fs-5 text-primary"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="mb-1 fw-semibold" style="font-size: 0.875rem;">${notification.title}</h6>
                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">${notification.message}</p>
                    <small class="text-muted">${timeAgo}</small>
                  </div>
                  ${!notification.is_read ? '<span class="badge bg-primary rounded-circle" style="width: 8px; height: 8px;"></span>' : ''}
                </div>
              </li>
            `;
            list.append(item);
          });
        });
      }
      
      function getNotificationIcon(type) {
        const icons = {
          'comment': 'ti-message-circle',
          'mention': 'ti-at',
          'task_assigned': 'ti-checklist',
          'progress_update': 'ti-progress',
          'expense_added': 'ti-currency-euro',
        };
        return icons[type] || 'ti-bell';
      }
      
      function getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return 'À l\'instant';
        if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
        if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;
        if (diff < 604800) return `Il y a ${Math.floor(diff / 86400)} j`;
        return date.toLocaleDateString('fr-FR');
      }
    });
  </script>
  
  <script>
    // Initialiser les toasts
    document.addEventListener('DOMContentLoaded', function() {
      const toastElements = document.querySelectorAll('.toast');
      toastElements.forEach(function(toastEl) {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
      });
    });
  </script>
  
  @stack('scripts')
</body>

</html>

