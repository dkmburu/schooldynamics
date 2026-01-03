<?php
/**
 * Outstanding Balances Report - Content
 */
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/finance" class="btn btn-ghost-secondary btn-sm mb-2">
                    <i class="ti ti-arrow-left me-1"></i>Back to Finance
                </a>
                <h2 class="page-title">
                    <i class="ti ti-alert-circle me-2"></i>
                    Outstanding Balances
                </h2>
                <div class="text-muted mt-1">Debtors list sorted by amount owed</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <select name="grade" class="form-select form-select-sm">
                            <option value="">All Grades</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select name="sort" class="form-select form-select-sm">
                            <option value="balance_desc">Highest Balance First</option>
                            <option value="balance_asc">Lowest Balance First</option>
                            <option value="name_asc">Student Name A-Z</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="ti ti-filter me-1"></i>Filter
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-sm btn-secondary">
                            <i class="ti ti-download me-1"></i>Export
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="ti ti-info-circle me-2"></i>
                    Outstanding balances report shows students with unpaid balances.
                </div>
            </div>
        </div>
    </div>
</div>
