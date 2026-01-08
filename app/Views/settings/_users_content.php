<?php
/**
 * Users & Roles Management Content
 * Tabs: Users | Roles
 */

$activeTab = $activeTab ?? Request::get('tab', 'users');
$users = $users ?? [];
$roles = $roles ?? [];
$filters = $filters ?? [];
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">Users & Roles Management</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="#">Settings</a></li>
                    <li class="breadcrumb-item active">Users & Roles</li>
                </ol>
            </nav>
        </div>
        <div>
            <?php if ($activeTab === 'users'): ?>
                <a href="/settings/users/create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add User
                </a>
            <?php else: ?>
                <a href="/settings/roles/create" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Role
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="usersRolesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'users' ? 'active fw-bold' : '' ?>" href="/settings/users" role="tab">
                <i class="ti ti-users me-1"></i>
                Users
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link <?= $activeTab === 'roles' ? 'active fw-bold' : '' ?>" href="/settings/users?tab=roles" role="tab">
                <i class="ti ti-shield-lock me-1"></i>
                Roles
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <?php if ($activeTab === 'users'): ?>
        <!-- Users Tab -->
        <?php require __DIR__ . '/_users_tab.php'; ?>
    <?php else: ?>
        <!-- Roles Tab -->
        <?php require __DIR__ . '/_roles_tab.php'; ?>
    <?php endif; ?>
</div>

<script>
// Delete user confirmation
function deleteUser(userId, userName) {
    if (!confirm('Are you sure you want to delete user "' + userName + '"? This action cannot be undone.')) {
        return;
    }

    fetch('/settings/users/' + userId + '/delete', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('user-row-' + userId).remove();
            showToast('success', data.message);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'An error occurred. Please try again.');
    });
}

// Toggle user status
function toggleUserStatus(userId) {
    fetch('/settings/users/' + userId + '/toggle-status', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'An error occurred. Please try again.');
    });
}

// Delete role confirmation
function deleteRole(roleId, roleName) {
    if (!confirm('Are you sure you want to delete role "' + roleName + '"? This action cannot be undone.')) {
        return;
    }

    fetch('/settings/roles/' + roleId + '/delete', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('role-row-' + roleId).remove();
            showToast('success', data.message);
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'An error occurred. Please try again.');
    });
}

// Clone role
function cloneRole(roleId) {
    fetch('/settings/roles/' + roleId + '/clone', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/settings/roles/' + data.newRoleId + '/edit';
        } else {
            showToast('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'An error occurred. Please try again.');
    });
}

// Simple toast notification
function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') + ' position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
    toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + ' me-2"></i>' + message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
