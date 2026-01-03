<?php
/**
 * Income Statement - Content
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
                    <i class="ti ti-file-analytics me-2"></i>
                    Income Statement
                </h2>
                <div class="text-muted mt-1">Revenue breakdown by category</div>
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
                        <select name="period" class="form-select form-select-sm">
                            <option value="term">Current Term</option>
                            <option value="year">Current Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="ti ti-search me-1"></i>Generate
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-sm btn-secondary">
                            <i class="ti ti-printer me-1"></i>Print
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="ti ti-info-circle me-2"></i>
                    Income statement shows revenue recognized by fee category (tuition, meals, transport, etc.)
                </div>
            </div>
        </div>
    </div>
</div>
