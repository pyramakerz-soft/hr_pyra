@extends('clocks::layouts.master')

@section('content')
    <style>
        :root {
            color-scheme: light;
        }

        body {
            background-color: #f6f8fb;
        }

        .rules-wrapper {
            font-family: 'Figtree', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 32px;
            max-width: 1440px;
            margin: 0 auto;
            color: #1f2937;
        }

        .rules-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .rules-header p {
            margin: 0;
            color: #4b5563;
        }

        .rules-grid {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            margin-top: 32px;
        }

        .panel {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            padding: 24px;
            display: flex;
            flex-direction: column;
            min-height: 520px;
        }

        .panel h2 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .panel > p.subtitle {
            margin-top: 0;
            margin-bottom: 16px;
            color: #6b7280;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.18s ease;
            font-family: inherit;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #1f2937;
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .btn-danger {
            background-color: #f87171;
            color: #ffffff;
        }

        .btn-danger:hover {
            background-color: #ef4444;
        }

        .btn-icon {
            padding: 6px;
            border-radius: 6px;
            width: 30px;
            height: 30px;
        }

        input[type="text"],
        input[type="search"],
        input[type="number"],
        textarea,
        select {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 9px 12px;
            font-size: 14px;
            font-family: inherit;
            background-color: #ffffff;
            transition: border-color 0.16s ease, box-shadow 0.16s ease;
        }

        input[type="text"]:focus,
        input[type="search"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            outline: none;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-grid {
            display: grid;
            gap: 12px;
        }

        .form-grid.two {
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        }

        label {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        th {
            font-weight: 600;
            color: #374151;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .template-row {
            cursor: pointer;
        }

        .template-row:hover {
            background-color: rgba(37, 99, 235, 0.05);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            font-size: 11px;
            border-radius: 999px;
            font-weight: 600;
        }

        .badge-success {
            background-color: rgba(34, 197, 94, 0.15);
            color: #15803d;
        }

        .badge-muted {
            background-color: rgba(148, 163, 184, 0.2);
            color: #475569;
        }

        .empty-state {
            padding: 24px;
            border: 1px dashed #cbd5f5;
            border-radius: 12px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }

        .stepper {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }

        .stepper .step {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background-color: #f9fafc;
        }

        .step.active {
            border-color: #2563eb;
            background-color: rgba(37, 99, 235, 0.08);
        }

        .step-number {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: #1d4ed8;
            background-color: #dbeafe;
        }

        .plan-summary {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            background-color: #f8fafc;
            margin-bottom: 20px;
        }

        .plan-summary h3 {
            margin: 0 0 12px;
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
        }

        .rule-card {
            border: 1px solid #dbe2f3;
            border-radius: 12px;
            padding: 16px;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
        }

        .rule-card[data-mode="template"] {
            border-color: rgba(37, 99, 235, 0.35);
            background-color: rgba(37, 99, 235, 0.03);
        }

        .rule-card[data-mode="custom"] {
            border-color: rgba(20, 184, 166, 0.3);
            background-color: rgba(20, 184, 166, 0.04);
        }

        .rule-card__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .rule-card__title {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .rule-card__title strong {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
        }

        .rule-card__title span {
            font-size: 12px;
            color: #6b7280;
        }

        .rule-card__actions {
            display: flex;
            gap: 6px;
        }

        .rule-description {
            font-size: 13px;
            color: #4b5563;
            margin: 0;
        }

        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.15);
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transform: translateY(12px);
            transition: opacity 0.18s ease, transform 0.18s ease;
        }

        .toast.toast-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .toast-success {
            background-color: #16a34a;
        }

        .toast-error {
            background-color: #dc2626;
        }

        .plan-loading {
            padding: 16px;
            border-radius: 12px;
            background-color: #f1f5f9;
            color: #475569;
            font-size: 14px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 3px solid rgba(37, 99, 235, 0.25);
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 900px) {
            .rules-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="rules-wrapper" id="rule-management">
        <header class="rules-header">
            <h1>Deduction Rules Management</h1>
            <p>
                Maintain reusable deduction rule templates and assign them to departments, sub-departments, or individual
                employees with full control over inheritance and overrides.
            </p>
        </header>

        <div class="rules-grid">
            <section class="panel" id="templates-panel">
                <h2>Rule Templates Library</h2>
                <p class="subtitle">
                    Templates define reusable deduction logic. Create once, adjust when needed, and reuse everywhere.
                </p>

                <div class="template-actions">
                    <input type="search" id="template-search" placeholder="Search by name, key, or description...">
                    <button type="button" class="btn btn-primary" id="btn-new-template">
                        + New Template
                    </button>
                </div>

                <div id="template-list" class="template-list"></div>

                <div id="template-form-container" class="template-form" hidden>
                    <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;">
                    <h3 id="template-form-title" style="margin-top: 0; font-size: 18px; font-weight: 600;">
                        Create Template
                    </h3>
                    <form id="template-form" class="form-grid" autocomplete="off">
                        <input type="hidden" id="template-id">
                        <div class="form-grid two">
                            <label>
                                Display Name
                                <input type="text" id="template-name" placeholder="Academic Late Policy" required>
                            </label>
                            <label>
                                Unique Key
                                <input type="text" id="template-key" placeholder="academic_late" required>
                            </label>
                            <label>
                                Category
                                <input type="text" id="template-category" placeholder="lateness" required>
                            </label>
                            <label>
                                Scope
                                <input type="text" id="template-scope" placeholder="daily" required>
                            </label>
                            <label>
                                Active?
                                <select id="template-active">
                                    <option value="1">Yes</option>
                                    <option value="0">No (keep for later)</option>
                                </select>
                            </label>
                        </div>
                        <label>
                            Description
                            <textarea id="template-description" placeholder="Short explanation for HR colleagues"></textarea>
                        </label>
                        <label>
                            Rule Definition (JSON)
                            <textarea id="template-rule" placeholder='Example: { "label": "Late", "when": {"minutes_late_gte": 1}, "penalty": {"type": "fixed_minutes", "value": 30} }' required></textarea>
                        </label>
                        <p class="muted" style="font-size: 12px; margin-top: -6px;">
                            Accepts either a single rule object or an array of rule objects. Each rule supports the same fields used by the engine (label, when, penalty, notes, meta, etc).
                        </p>
                        <div style="display:flex; gap: 12px; justify-content: flex-end; margin-top: 8px;">
                            <button type="button" class="btn btn-secondary" id="btn-cancel-template">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="btn-save-template">Save Template</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="panel" id="assignment-panel">
                <h2>Assign Rules &amp; Manage Overrides</h2>
                <p class="subtitle">
                    Choose a scope, pick the target, inspect inherited rules, and curate the plan that should apply.
                </p>

                <div class="stepper">
                    <div class="step active" id="step-scope">
                        <span class="step-number">1</span>
                        <div>
                            <div style="font-weight: 600;">Select Scope</div>
                            <div class="muted" style="font-size: 12px;">Department, Sub-Department, or Employee</div>
                        </div>
                    </div>
                    <div class="step" id="step-target">
                        <span class="step-number">2</span>
                        <div>
                            <div style="font-weight: 600;">Choose Target</div>
                            <div class="muted" style="font-size: 12px;">Who should receive these rules?</div>
                        </div>
                    </div>
                    <div class="step" id="step-plan">
                        <span class="step-number">3</span>
                        <div>
                            <div style="font-weight: 600;">Craft Plan</div>
                            <div class="muted" style="font-size: 12px;">Review inheritance &amp; publish overrides</div>
                        </div>
                    </div>
                </div>

                <div class="form-grid two" style="margin-bottom: 12px;">
                    <label style="flex-direction: row; align-items: center; gap: 8px;">
                        <input type="radio" name="scope" value="department" checked>
                        Department
                    </label>
                    <label style="flex-direction: row; align-items: center; gap: 8px;">
                        <input type="radio" name="scope" value="sub-department">
                        Sub-Department
                    </label>
                    <label style="flex-direction: row; align-items: center; gap: 8px;">
                        <input type="radio" name="scope" value="user">
                        Employee
                    </label>
                </div>

                <div class="form-grid" id="target-selectors">
                    <div class="target target-department" data-scope="department">
                        <label>
                            Department
                            <select id="department-select">
                                <option value="">Select a department...</option>
                            </select>
                        </label>
                    </div>

                    <div class="target target-sub" data-scope="sub-department" hidden>
                        <label>
                            Department (filter)
                            <select id="sub-department-parent">
                                <option value="">All departments...</option>
                            </select>
                        </label>
                        <label>
                            Sub-Department
                            <select id="sub-department-select">
                                <option value="">Select a sub-department...</option>
                            </select>
                        </label>
                    </div>

                    <div class="target target-user" data-scope="user" hidden>
                        <label>
                            Department (filter)
                            <select id="user-department-filter">
                                <option value="">All departments...</option>
                            </select>
                        </label>
                        <label>
                            Sub-Department (filter)
                            <select id="user-sub-department-filter">
                                <option value="">All sub-departments...</option>
                            </select>
                        </label>
                        <label>
                            Employee
                            <select id="user-select">
                                <option value="">Select an employee...</option>
                            </select>
                        </label>
                    </div>
                </div>

                <div id="plan-container">
                    <div id="plan-loading" class="plan-loading" hidden>
                        <span class="spinner" aria-hidden="true"></span>
                        Loading plan details...
                    </div>

                    <div id="plan-summary" class="plan-summary" hidden></div>

                    <div id="builder-section" hidden>
                        <div class="rule-builder">
                            <div class="rule-builder-controls" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                                <label style="flex:1;">
                                    Add template rule
                                    <select id="builder-template-select">
                                        <option value="">Select a template...</option>
                                    </select>
                                </label>
                                <button type="button" class="btn btn-secondary" id="btn-add-template-rule">
                                    Add Template
                                </button>
                                <button type="button" class="btn btn-secondary" id="btn-add-custom-rule">
                                    Add Custom Rule
                                </button>
                            </div>

                            <div class="form-grid two" id="plan-settings">
                                <label>
                                    Grace Minutes
                                    <input type="number" id="plan-grace-minutes" min="0" max="1440" placeholder="defaults to 15">
                                </label>
                                <label id="overwrite-all-container" hidden style="align-items: flex-start;">
                                    <span>
                                        <input type="checkbox" id="plan-overwrite" style="margin-right: 8px;">
                                        <strong>Overwrite inherited rules?</strong>
                                    </span>
                                </label>
                                <label id="overwrite-dep-container" hidden style="align-items: flex-start;">
                                    <span>
                                        <input type="checkbox" id="plan-overwrite-dep" style="margin-right: 8px;">
                                        <strong>Ignore department plan</strong>
                                    </span>
                                </label>
                                <label id="overwrite-subdep-container" hidden style="align-items: flex-start;">
                                    <span>
                                        <input type="checkbox" id="plan-overwrite-subdep" style="margin-right: 8px;">
                                        <strong>Ignore sub-department plan</strong>
                                    </span>
                                </label>
                            </div>

                            <div id="builder-rules"></div>

                            <div class="rules-empty-plan" id="builder-empty" hidden>
                                No rules yet. Add template or custom rules to craft this plan.
                            </div>

                            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:12px;">
                                <button type="button" class="btn btn-primary" id="btn-save-plan">
                                    Save Plan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="toast" id="rule-toast"></div>
    </div>

    <script>
        window.ruleManagementBoot = {
            csrfToken: '{{ csrf_token() }}',
            templates: @json($templates),
            departments: @json($departments),
            users: @json($users),
            routes: {
                planShow: @json(route('clocks.rules.plan.show', ['scope' => ':scope', 'id' => ':id'])),
                planUpdate: @json(route('clocks.rules.plan.update', ['scope' => ':scope', 'id' => ':id'])),
                templateStore: @json(route('clocks.rules.templates.store')),
                templateUpdate: @json(route('clocks.rules.templates.update', ['template' => ':id'])),
                templateDelete: @json(route('clocks.rules.templates.destroy', ['template' => ':id']))
            }
        };
    </script>

    <script>
        (function () {
            'use strict';

            const config = window.ruleManagementBoot || {};
            const root = document.getElementById('rule-management');
            if (!root) {
                return;
            }

            const templateList = root.querySelector('#template-list');
            const templateSearchInput = root.querySelector('#template-search');
            const templateFormContainer = root.querySelector('#template-form-container');
            const templateForm = root.querySelector('#template-form');
            const templateFormTitle = root.querySelector('#template-form-title');
            const templateIdInput = root.querySelector('#template-id');
            const templateNameInput = root.querySelector('#template-name');
            const templateKeyInput = root.querySelector('#template-key');
            const templateCategoryInput = root.querySelector('#template-category');
            const templateScopeInput = root.querySelector('#template-scope');
            const templateActiveInput = root.querySelector('#template-active');
            const templateDescriptionInput = root.querySelector('#template-description');
            const templateRuleInput = root.querySelector('#template-rule');
            const templateNewBtn = root.querySelector('#btn-new-template');
            const templateCancelBtn = root.querySelector('#btn-cancel-template');
            const templateSaveBtn = root.querySelector('#btn-save-template');

            const scopeRadios = root.querySelectorAll('input[name="scope"]');
            const departmentSelect = root.querySelector('#department-select');
            const subDeptParentSelect = root.querySelector('#sub-department-parent');
            const subDeptSelect = root.querySelector('#sub-department-select');
            const userDepartmentFilter = root.querySelector('#user-department-filter');
            const userSubDepartmentFilter = root.querySelector('#user-sub-department-filter');
            const userSelect = root.querySelector('#user-select');

            const planSummary = root.querySelector('#plan-summary');
            const planLoading = root.querySelector('#plan-loading');
            const builderSection = root.querySelector('#builder-section');
            const builderTemplateSelect = root.querySelector('#builder-template-select');
            const builderRulesContainer = root.querySelector('#builder-rules');
            const builderEmpty = root.querySelector('#builder-empty');
            const addTemplateRuleBtn = root.querySelector('#btn-add-template-rule');
            const addCustomRuleBtn = root.querySelector('#btn-add-custom-rule');
            const savePlanBtn = root.querySelector('#btn-save-plan');

            const planGraceInput = root.querySelector('#plan-grace-minutes');
            const overwriteAllContainer = root.querySelector('#overwrite-all-container');
            const overwriteDepContainer = root.querySelector('#overwrite-dep-container');
            const overwriteSubDepContainer = root.querySelector('#overwrite-subdep-container');
            const overwriteAllCheckbox = root.querySelector('#plan-overwrite');
            const overwriteDepCheckbox = root.querySelector('#plan-overwrite-dep');
            const overwriteSubDepCheckbox = root.querySelector('#plan-overwrite-subdep');
            const toast = root.querySelector('#rule-toast');

            const penaltyTypes = [
                'fixed_minutes',
                'fixed_hours',
                'fraction_day',
                'day',
                'days',
                'percentage_shortfall',
                'lateness_actual',
                'lateness_beyond_grace',
                'amount'
            ];

            const templateKeyMap = new Map();
            const templateIdMap = new Map();
            const departmentMap = new Map();
            const subDepartmentMap = new Map();
            const userMap = new Map();

            const state = {
                templates: Array.isArray(config.templates) ? [...config.templates] : [],
                scope: 'department',
                selectedDepartment: '',
                selectedSubDepartment: '',
                selectedUser: '',
                plan: null,
                planable: null,
                context: null,
                effectivePlan: null,
                builderRules: [],
                planSettings: {
                    grace: '',
                    overwrite: false,
                    overwrite_dep: false,
                    overwrite_subdep: false
                },
                loadingPlan: false,
                savingPlan: false,
                templateFormMode: 'create'
            };

            state.templates.forEach((template) => {
                templateKeyMap.set(template.key, template);
                templateIdMap.set(template.id, template);
            });

            (config.departments || []).forEach((department) => {
                const subs = Array.isArray(department.sub_departments)
                    ? department.sub_departments
                    : (department.subDepartments || []);
                departmentMap.set(department.id, { ...department, subDepartments: subs });
                subs.forEach((sub) => {
                    subDepartmentMap.set(sub.id, sub);
                });
            });

            (config.users || []).forEach((user) => {
                userMap.set(user.id, user);
            });

            let uidCounter = 0;
            function uniqueId(prefix) {
                uidCounter += 1;
                return `${prefix}-${Date.now()}-${uidCounter}`;
            }

            function escapeHtml(value) {
                if (value === undefined || value === null) {
                    return '';
                }
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function normalizeTemplateRuleDefinitions(ruleDefinition) {
                if (!ruleDefinition) {
                    return [];
                }
                if (Array.isArray(ruleDefinition)) {
                    return ruleDefinition.filter(Boolean);
                }
                if (typeof ruleDefinition === 'object') {
                    return [ruleDefinition];
                }
                return [];
            }

            function normalizeColor(value) {
                if (!value) {
                    return '';
                }
                const trimmed = String(value).trim();
                if (trimmed === '') {
                    return '';
                }
                if (trimmed.startsWith('#')) {
                    return trimmed.toUpperCase();
                }
                if (/^[0-9a-fA-F]{6}$/u.test(trimmed)) {
                    return `#${trimmed.toUpperCase()}`;
                }
                return trimmed;
            }

            function normalizeColorForPayload(value) {
                const normalized = normalizeColor(value);
                if (!normalized) {
                    return null;
                }
                return normalized.startsWith('#') ? normalized.slice(1).toUpperCase() : normalized.toUpperCase();
            }

            function parseJsonField(raw, label) {
                if (!raw || raw.trim() === '') {
                    return null;
                }
                try {
                    return JSON.parse(raw);
                } catch (error) {
                    throw new Error(`${label} JSON is invalid (${error.message}).`);
                }
            }

            function stringifyJson(value) {
                if (value === undefined || value === null) {
                    return '';
                }
                try {
                    return JSON.stringify(value, null, 2);
                } catch (_) {
                    return '';
                }
            }

            function penaltyLabel(penalty) {
                if (!penalty || typeof penalty !== 'object') {
                    return '—';
                }
                const type = penalty.type || 'fixed_minutes';
                const value = penalty.value !== undefined && penalty.value !== null ? penalty.value : '';
                const unit = penalty.unit ? ` ${penalty.unit}` : '';
                return `${type}${value !== '' ? ` (${value}${unit})` : ''}`;
            }

            function formatWhenValue(when) {
                if (!when || (Array.isArray(when) && when.length === 0) || (typeof when === 'object' && Object.keys(when).length === 0)) {
                    return '—';
                }
                try {
                    return JSON.stringify(when);
                } catch (_) {
                    return String(when);
                }
            }

            function getTemplateDefinition(template, rule) {
                const definitions = normalizeTemplateRuleDefinitions(template.rule);
                if (definitions.length <= 1) {
                    return definitions[0] || {};
                }

                const ruleMeta = rule && typeof rule.meta === 'object' ? rule.meta : null;
                if (ruleMeta) {
                    const matchingKeys = ['sequence_step', 'sequence', 'template_step', 'step', 'slug', 'identifier'];
                    for (const key of matchingKeys) {
                        if (ruleMeta[key] !== undefined && ruleMeta[key] !== null) {
                            const candidate = definitions.find((definition) => {
                                const defMeta = definition && typeof definition.meta === 'object' ? definition.meta : null;
                                return defMeta && String(defMeta[key]) === String(ruleMeta[key]);
                            });
                            if (candidate) {
                                return candidate;
                            }
                        }
                    }
                }

                const label = rule && rule.label ? rule.label : null;
                if (label) {
                    const candidateByLabel = definitions.find((definition) => definition && definition.label === label);
                    if (candidateByLabel) {
                        return candidateByLabel;
                    }
                }

                return definitions[0] || {};
            }

            function computeStopProcessingMode(definition, planRule) {
                const defaultValue = !!(definition && definition.stop_processing);
                const currentValue = planRule && planRule.stop_processing !== undefined
                    ? !!planRule.stop_processing
                    : defaultValue;

                if (currentValue === defaultValue) {
                    return 'inherit';
                }

                return currentValue ? 'force_true' : 'force_false';
            }

            function diffJson(templateValue, ruleValue) {
                if (!templateValue && !ruleValue) {
                    return '';
                }
                const templateJson = stringifyJson(templateValue);
                const ruleJson = stringifyJson(ruleValue);
                if (templateJson === ruleJson) {
                    return '';
                }
                return ruleJson || '';
            }

            function populateDepartmentSelect(selectElement, includeAllOption = true) {
                if (!selectElement) {
                    return;
                }

                const options = includeAllOption
                    ? ['<option value="">All departments...</option>']
                    : ['<option value="">Select a department...</option>'];

                [...departmentMap.values()]
                    .sort((a, b) => (a.name || '').localeCompare(b.name || ''))
                    .forEach((department) => {
                        options.push(`<option value="${escapeHtml(department.id)}">${escapeHtml(department.name || '')}</option>`);
                    });

                selectElement.innerHTML = options.join('');
            }

            function populateSubDepartmentSelect(selectElement, departmentId, includeAll = true) {
                if (!selectElement) {
                    return;
                }

                const options = includeAll
                    ? ['<option value="">All sub-departments...</option>']
                    : ['<option value="">Select a sub-department...</option>'];

                let subs = [];
                if (departmentId) {
                    const department = departmentMap.get(Number(departmentId));
                    subs = department ? (department.subDepartments || []) : [];
                } else {
                    subs = [...subDepartmentMap.values()];
                }

                subs
                    .sort((a, b) => (a.name || '').localeCompare(b.name || ''))
                    .forEach((sub) => {
                        options.push(`<option value="${escapeHtml(sub.id)}">${escapeHtml(sub.name || '')}</option>`);
                    });

                selectElement.innerHTML = options.join('');
            }

            function populateUserSelect() {
                if (!userSelect) {
                    return;
                }

                const deptFilter = userDepartmentFilter.value ? Number(userDepartmentFilter.value) : null;
                const subFilter = userSubDepartmentFilter.value ? Number(userSubDepartmentFilter.value) : null;

                const options = ['<option value="">Select an employee...</option>'];
                [...userMap.values()]
                    .filter((user) => {
                        if (deptFilter && Number(user.department_id) !== deptFilter) {
                            return false;
                        }
                        if (subFilter && Number(user.sub_department_id) !== subFilter) {
                            return false;
                        }
                        return true;
                    })
                    .sort((a, b) => (a.name || '').localeCompare(b.name || ''))
                    .forEach((user) => {
                        const codeSuffix = user.code ? ` · ${user.code}` : '';
                        options.push(`<option value="${escapeHtml(user.id)}">${escapeHtml(user.name || '')}${escapeHtml(codeSuffix)}</option>`);
                    });

                userSelect.innerHTML = options.join('');
            }

            function showToast(message, type = 'success') {
                if (!toast) {
                    return;
                }

                toast.textContent = message;
                toast.classList.remove('toast-success', 'toast-error', 'toast-visible');
                toast.classList.add(type === 'error' ? 'toast-error' : 'toast-success');
                requestAnimationFrame(() => toast.classList.add('toast-visible'));
                setTimeout(() => toast.classList.remove('toast-visible'), 3200);
            }

            function renderTemplateList() {
                const filter = templateSearchInput.value ? templateSearchInput.value.trim().toLowerCase() : '';
                const templates = [...state.templates].sort((a, b) => (a.name || '').localeCompare(b.name || ''));
                const filtered = filter
                    ? templates.filter((template) => {
                        const haystack = [
                            template.name,
                            template.key,
                            template.description,
                            template.category
                        ].join(' ').toLowerCase();
                        return haystack.includes(filter);
                    })
                    : templates;

                if (filtered.length === 0) {
                    templateList.innerHTML = `
                        <div class="empty-state">
                            No templates yet. Create your first shared rule template to get started.
                        </div>
                    `;
                    return;
                }

                const rows = filtered.map((template) => {
                    const definitions = normalizeTemplateRuleDefinitions(template.rule);
                    const activeBadge = template.is_active
                        ? '<span class="badge badge-success">Active</span>'
                        : '<span class="badge badge-muted">Inactive</span>';

                    return `
                        <tr class="template-row" data-id="${escapeHtml(template.id)}" data-key="${escapeHtml(template.key)}">
                            <td style="width: 34%;">
                                <div style="font-weight: 600;">${escapeHtml(template.name)}</div>
                                <div class="muted" style="font-size: 12px;">${escapeHtml(template.description || '—')}</div>
                            </td>
                            <td>${escapeHtml(template.key)}</td>
                            <td>${escapeHtml(template.category || '—')}</td>
                            <td>${escapeHtml(template.scope || '—')}</td>
                            <td>${activeBadge}</td>
                            <td>${definitions.length}</td>
                            <td style="width: 120px;">
                                <div style="display:flex; gap: 6px; justify-content:flex-end;">
                                    <button type="button" class="btn btn-secondary btn-icon" data-action="edit-template" title="Edit">Edit</button>
                                    <button type="button" class="btn btn-danger btn-icon" data-action="delete-template" title="Delete">Del</button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                templateList.innerHTML = `
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Key</th>
                                <th>Category</th>
                                <th>Scope</th>
                                <th>Status</th>
                                <th># Rules</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                `;
            }

            function updateTemplateSelectOptions() {
                if (!builderTemplateSelect) {
                    return;
                }
                const options = ['<option value="">Select a template...</option>'];
                [...state.templates]
                    .filter((template) => template.is_active)
                    .sort((a, b) => (a.name || '').localeCompare(b.name || ''))
                    .forEach((template) => {
                        const definitions = normalizeTemplateRuleDefinitions(template.rule);
                        options.push(`<option value="${escapeHtml(template.key)}">${escapeHtml(template.name)} (${definitions.length} rule${definitions.length === 1 ? '' : 's'})</option>`);
                    });
                builderTemplateSelect.innerHTML = options.join('');
            }

            function openTemplateForm(mode, template = null) {
                state.templateFormMode = mode;
                templateFormContainer.hidden = false;

                if (mode === 'edit' && template) {
                    templateFormTitle.textContent = `Edit Template: ${template.name}`;
                    templateIdInput.value = template.id;
                    templateNameInput.value = template.name || '';
                    templateKeyInput.value = template.key || '';
                    templateCategoryInput.value = template.category || '';
                    templateScopeInput.value = template.scope || '';
                    templateActiveInput.value = template.is_active ? '1' : '0';
                    templateDescriptionInput.value = template.description || '';
                    templateRuleInput.value = stringifyJson(template.rule);
                } else {
                    templateFormTitle.textContent = 'Create Template';
                    templateIdInput.value = '';
                    templateNameInput.value = '';
                    templateKeyInput.value = '';
                    templateCategoryInput.value = 'lateness';
                    templateScopeInput.value = 'daily';
                    templateActiveInput.value = '1';
                    templateDescriptionInput.value = '';
                    templateRuleInput.value = '';
                }
            }

            function closeTemplateForm() {
                templateFormContainer.hidden = true;
                templateIdInput.value = '';
                templateForm.reset();
                templateCategoryInput.value = 'lateness';
                templateScopeInput.value = 'daily';
                templateActiveInput.value = '1';
            }

            async function submitTemplateForm(event) {
                event.preventDefault();
                const mode = state.templateFormMode;
                const id = templateIdInput.value;

                const payload = {
                    name: templateNameInput.value.trim(),
                    key: templateKeyInput.value.trim(),
                    category: templateCategoryInput.value.trim() || 'other',
                    scope: templateScopeInput.value.trim() || 'daily',
                    description: templateDescriptionInput.value.trim(),
                    is_active: templateActiveInput.value === '1'
                };

                try {
                    payload.rule = JSON.parse(templateRuleInput.value);
                } catch (error) {
                    showToast(`Rule JSON is invalid (${error.message})`, 'error');
                    return;
                }

                const url = mode === 'edit'
                    ? config.routes.templateUpdate.replace(':id', encodeURIComponent(id))
                    : config.routes.templateStore;

                templateSaveBtn.disabled = true;
                templateSaveBtn.textContent = 'Saving...';

                try {
                    const response = await fetch(url, {
                        method: mode === 'edit' ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken
                        },
                        body: JSON.stringify(payload)
                    });

                    const body = await response.json();
                    if (!response.ok || body.result === 'false') {
                        const message = body.message || 'Unable to save template.';
                        showToast(message, 'error');
                        return;
                    }

                    const template = body.data && body.data.template ? body.data.template : null;
                    if (template) {
                        const index = state.templates.findIndex((item) => item.id === template.id);
                        if (index >= 0) {
                            state.templates.splice(index, 1, template);
                        } else {
                            state.templates.push(template);
                        }
                        templateKeyMap.set(template.key, template);
                        templateIdMap.set(template.id, template);
                        renderTemplateList();
                        updateTemplateSelectOptions();
                    }

                    showToast(body.message || 'Template saved.');
                    closeTemplateForm();
                } catch (error) {
                    showToast(error.message || 'Unexpected error while saving template.', 'error');
                } finally {
                    templateSaveBtn.disabled = false;
                    templateSaveBtn.textContent = 'Save Template';
                }
            }

            async function deleteTemplate(id) {
                if (!id) {
                    return;
                }

                const template = templateIdMap.get(Number(id));
                const label = template ? template.name : 'this template';

                if (!confirm(`Delete ${label}? This cannot be undone.`)) {
                    return;
                }

                try {
                    const response = await fetch(config.routes.templateDelete.replace(':id', encodeURIComponent(id)), {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken
                        }
                    });

                    if (!response.ok) {
                        const body = await response.json().catch(() => ({}));
                        const message = body.message || 'Unable to delete template.';
                        showToast(message, 'error');
                        return;
                    }

                    state.templates = state.templates.filter((item) => item.id !== Number(id));
                    templateKeyMap.delete(template && template.key ? template.key : '');
                    templateIdMap.delete(Number(id));
                    renderTemplateList();
                    updateTemplateSelectOptions();
                    showToast('Template deleted.');
                } catch (error) {
                    showToast(error.message || 'Unexpected error while deleting template.', 'error');
                }
            }

            function createBuilderRuleFromPlan(rule) {
                if (rule && rule.template_key && templateKeyMap.has(rule.template_key)) {
                    const template = templateKeyMap.get(rule.template_key);
                    const definition = getTemplateDefinition(template, rule);
                    const basePenalty = definition && definition.penalty ? definition.penalty : {};
                    const planPenalty = rule && rule.penalty ? rule.penalty : {};

                    return {
                        id: uniqueId('rule'),
                        mode: 'template',
                        templateKey: rule.template_key,
                        templateId: rule.template_id || template.id,
                        templateRecord: template,
                        templateDefinition: definition,
                        label: rule.label || definition.label || template.name,
                        meta: (rule && typeof rule.meta === 'object') ? { ...rule.meta } : (definition && typeof definition.meta === 'object' ? { ...definition.meta } : null),
                        overrides: {
                            notes: rule && rule.notes ? rule.notes : '',
                            color: normalizeColor(rule && rule.color ? rule.color : (definition && definition.color ? definition.color : '')),
                            penaltyType: planPenalty.type || basePenalty.type || 'fixed_minutes',
                            penaltyValue: planPenalty.value !== undefined && planPenalty.value !== null ? planPenalty.value : '',
                            penaltyUnit: planPenalty.unit || basePenalty.unit || '',
                            whenJson: diffJson(definition && definition.when, rule && rule.when),
                            metaJson: diffJson(definition && definition.meta, rule && rule.meta),
                            stopProcessing: computeStopProcessingMode(definition, rule)
                        },
                        base: {
                            penaltyType: basePenalty.type || 'fixed_minutes',
                            penaltyValue: basePenalty.value !== undefined && basePenalty.value !== null ? basePenalty.value : '',
                            penaltyUnit: basePenalty.unit || '',
                            notes: definition && definition.notes ? definition.notes : '',
                            color: normalizeColor(definition && definition.color ? definition.color : ''),
                            when: definition && definition.when ? definition.when : null,
                            meta: definition && definition.meta ? definition.meta : null,
                            stopProcessing: !!(definition && definition.stop_processing)
                        }
                    };
                }

                return {
                    id: uniqueId('rule'),
                    mode: 'custom',
                    data: {
                        label: rule && rule.label ? rule.label : '',
                        category: rule && rule.category ? rule.category : 'other',
                        scope: rule && rule.scope ? rule.scope : 'daily',
                        penaltyType: rule && rule.penalty && rule.penalty.type ? rule.penalty.type : 'fixed_minutes',
                        penaltyValue: rule && rule.penalty && rule.penalty.value !== undefined && rule.penalty.value !== null
                            ? rule.penalty.value
                            : '',
                        penaltyUnit: rule && rule.penalty && rule.penalty.unit ? rule.penalty.unit : '',
                        whenJson: stringifyJson(rule && rule.when ? rule.when : []),
                        notes: rule && rule.notes ? rule.notes : '',
                        color: normalizeColor(rule && rule.color ? rule.color : ''),
                        stop_processing: !!(rule && rule.stop_processing),
                        metaJson: stringifyJson(rule && rule.meta ? rule.meta : null)
                    }
                };
            }

            function createBuilderRuleFromTemplate(template, definition, index) {
                const basePenalty = definition && definition.penalty ? definition.penalty : {};
                const meta = definition && typeof definition.meta === 'object'
                    ? { ...definition.meta }
                    : { sequence_step: index + 1 };

                return {
                    id: uniqueId('rule'),
                    mode: 'template',
                    templateKey: template.key,
                    templateId: template.id,
                    templateRecord: template,
                    templateDefinition: definition,
                    label: definition && definition.label ? definition.label : template.name,
                    meta,
                    overrides: {
                        notes: '',
                        color: normalizeColor(definition && definition.color ? definition.color : ''),
                        penaltyType: basePenalty.type || 'fixed_minutes',
                        penaltyValue: basePenalty.value !== undefined && basePenalty.value !== null ? basePenalty.value : '',
                        penaltyUnit: basePenalty.unit || '',
                        whenJson: '',
                        metaJson: '',
                        stopProcessing: 'inherit'
                    },
                    base: {
                        penaltyType: basePenalty.type || 'fixed_minutes',
                        penaltyValue: basePenalty.value !== undefined && basePenalty.value !== null ? basePenalty.value : '',
                        penaltyUnit: basePenalty.unit || '',
                        notes: definition && definition.notes ? definition.notes : '',
                        color: normalizeColor(definition && definition.color ? definition.color : ''),
                        when: definition && definition.when ? definition.when : null,
                        meta: definition && definition.meta ? definition.meta : null,
                        stopProcessing: !!(definition && definition.stop_processing)
                    }
                };
            }

            function createEmptyCustomRule() {
                return {
                    id: uniqueId('rule'),
                    mode: 'custom',
                    data: {
                        label: '',
                        category: 'other',
                        scope: 'daily',
                        penaltyType: 'fixed_minutes',
                        penaltyValue: '',
                        penaltyUnit: '',
                        whenJson: '{\n  \n}',
                        notes: '',
                        color: '',
                        stop_processing: false,
                        metaJson: ''
                    }
                };
            }

            function setPlanLoading(isLoading) {
                state.loadingPlan = isLoading;
                if (planLoading) {
                    planLoading.hidden = !isLoading;
                }
                if (builderSection && !isLoading) {
                    builderSection.hidden = !state.plan;
                }
            }

            function clearPlanDisplay() {
                state.plan = null;
                state.planable = null;
                state.context = null;
                state.effectivePlan = null;
                state.builderRules = [];
                state.planSettings = {
                    grace: '',
                    overwrite: false,
                    overwrite_dep: false,
                    overwrite_subdep: false
                };

                if (planSummary) {
                    planSummary.hidden = true;
                    planSummary.innerHTML = '';
                }
                if (builderSection) {
                    builderSection.hidden = true;
                }
                if (builderRulesContainer) {
                    builderRulesContainer.innerHTML = '';
                }
                if (builderEmpty) {
                    builderEmpty.hidden = false;
                }
            }

            function updatePlanContextUI(context) {
                if (!context) {
                    overwriteAllContainer.hidden = true;
                    overwriteDepContainer.hidden = true;
                    overwriteSubDepContainer.hidden = true;
                    return;
                }

                overwriteAllContainer.hidden = !context.supports_overwrite;
                overwriteDepContainer.hidden = !context.supports_overwrite_dep;
                overwriteSubDepContainer.hidden = !context.supports_overwrite_subdep;
            }

            function syncPlanSettingsInputs() {
                planGraceInput.value = state.planSettings.grace;
                overwriteAllCheckbox.checked = !!state.planSettings.overwrite;
                overwriteDepCheckbox.checked = !!state.planSettings.overwrite_dep;
                overwriteSubDepCheckbox.checked = !!state.planSettings.overwrite_subdep;
            }

            function renderPlanSummary() {
                if (!planSummary) {
                    return;
                }

                if (!state.plan) {
                    planSummary.hidden = true;
                    planSummary.innerHTML = '';
                    return;
                }

                const planRules = Array.isArray(state.plan.rules) ? state.plan.rules : [];
                const metadataBadges = [];

                if (state.plan.grace_minutes !== undefined && state.plan.grace_minutes !== null) {
                    metadataBadges.push(`<span>Grace: ${escapeHtml(state.plan.grace_minutes)}</span>`);
                }
                if (state.plan.overwrite) {
                    metadataBadges.push('<span>Overwrites parent rules</span>');
                }
                if (state.plan.overwrite_dep) {
                    metadataBadges.push('<span>Ignores department plan</span>');
                }
                if (state.plan.overwrite_subdep) {
                    metadataBadges.push('<span>Ignores sub-department plan</span>');
                }

                const rows = planRules.map((rule, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(rule.label || '—')}</td>
                        <td>${escapeHtml(rule.category || '—')}</td>
                        <td>${escapeHtml(rule.template_key || 'Custom')}</td>
                        <td>${penaltyLabel(rule.penalty)}</td>
                        <td>${formatWhenValue(rule.when)}</td>
                        <td>${escapeHtml(rule.notes || '—')}</td>
                    </tr>
                `).join('');

                const effectivePlan = state.effectivePlan;
                let effectiveSection = '';
                if (effectivePlan && Array.isArray(effectivePlan.rules)) {
                    const effectiveRows = effectivePlan.rules.map((rule, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${escapeHtml(rule.label || '—')}</td>
                            <td>${escapeHtml(rule.category || '—')}</td>
                            <td>${escapeHtml(rule.source && rule.source.type ? rule.source.type : 'Plan')}</td>
                            <td>${penaltyLabel(rule.penalty)}</td>
                            <td>${formatWhenValue(rule.when)}</td>
                            <td>${escapeHtml(rule.notes || '—')}</td>
                        </tr>
                    `).join('');

                    effectiveSection = `
                        <div style="margin-top:18px;">
                            <h3>Effective Rules (after inheritance)</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Label</th>
                                        <th>Category</th>
                                        <th>Source</th>
                                        <th>Penalty</th>
                                        <th>Conditions</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>${effectiveRows}</tbody>
                            </table>
                        </div>
                    `;
                }

                planSummary.hidden = false;
                planSummary.innerHTML = `
                    <div class="plan-meta" ${metadataBadges.length === 0 ? 'hidden' : ''}>
                        ${metadataBadges.join('')}
                    </div>
                    <h3>Plan Rules</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Label</th>
                                <th>Category</th>
                                <th>Template</th>
                                <th>Penalty</th>
                                <th>Conditions</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                    ${effectiveSection}
                `;
            }

            function renderBuilder() {
                if (!builderRulesContainer) {
                    return;
                }

                const rules = state.builderRules;
                if (!rules || rules.length === 0) {
                    builderRulesContainer.innerHTML = '';
                    builderEmpty.hidden = false;
                    return;
                }

                builderEmpty.hidden = true;

                const cards = rules.map((rule, index) => {
                    if (rule.mode === 'template') {
                        const definitionPreview = stringifyJson(rule.templateDefinition || {});
                        const whenPlaceholder = rule.overrides.whenJson || '';
                        const metaPlaceholder = rule.overrides.metaJson || '';
                        const sequenceLabel = rule.meta && (rule.meta.sequence_step || rule.meta.step)
                            ? `Step ${rule.meta.sequence_step || rule.meta.step}`
                            : `Rule ${index + 1}`;

                        const stopOptions = `
                            <option value="inherit"${rule.overrides.stopProcessing === 'inherit' ? ' selected' : ''}>Use template default</option>
                            <option value="force_true"${rule.overrides.stopProcessing === 'force_true' ? ' selected' : ''}>Force stop processing</option>
                            <option value="force_false"${rule.overrides.stopProcessing === 'force_false' ? ' selected' : ''}>Allow subsequent rules</option>
                        `;

                        const penaltyOptions = penaltyTypes.map((type) => `
                            <option value="${type}"${rule.overrides.penaltyType === type ? ' selected' : ''}>${type}</option>
                        `).join('');

                        return `
                            <div class="rule-card" data-id="${escapeHtml(rule.id)}" data-mode="template">
                                <div class="rule-card__header">
                                    <div class="rule-card__title">
                                        <span class="badge badge-primary">Template</span>
                                        <strong>${escapeHtml(rule.templateRecord.name || 'Template')}</strong>
                                        <span>${escapeHtml(sequenceLabel)}</span>
                                    </div>
                                    <div class="rule-card__actions">
                                        <button type="button" class="btn btn-secondary btn-icon" data-action="move-up" title="Move up">Up</button>
                                        <button type="button" class="btn btn-secondary btn-icon" data-action="move-down" title="Move down">Down</button>
                                        <button type="button" class="btn btn-danger btn-icon" data-action="remove" title="Remove">Remove</button>
                                    </div>
                                </div>
                                <p class="rule-description">${escapeHtml(rule.templateRecord.description || 'Reusable rule')}</p>
                                <div class="form-grid two">
                                    <label>
                                        Notes Override
                                        <textarea data-field="overrides.notes" placeholder="Optional notes shown on export">${escapeHtml(rule.overrides.notes)}</textarea>
                                    </label>
                                    <label>
                                        Color (hex without #)
                                        <input type="text" data-field="overrides.color" placeholder="e.g. FFC7CE" value="${escapeHtml(rule.overrides.color ? rule.overrides.color.replace('#', '') : '')}">
                                    </label>
                                    <label>
                                        Penalty Type
                                        <select data-field="overrides.penaltyType">${penaltyOptions}</select>
                                    </label>
                                    <label>
                                        Penalty Value
                                        <input type="number" step="0.01" data-field="overrides.penaltyValue" value="${escapeHtml(rule.overrides.penaltyValue)}" placeholder="Leave blank to reuse template value">
                                    </label>
                                    <label>
                                        Penalty Unit
                                        <input type="text" data-field="overrides.penaltyUnit" value="${escapeHtml(rule.overrides.penaltyUnit)}" placeholder="Optional unit label">
                                    </label>
                                    <label>
                                        Stop Processing
                                        <select data-field="overrides.stopProcessing">${stopOptions}</select>
                                    </label>
                                </div>
                                <details class="advanced-toggle">
                                    <summary>Advanced Overrides</summary>
                                    <div class="form-grid">
                                        <label>
                                            Condition Override (JSON)
                                            <textarea data-field="overrides.whenJson" placeholder="Leave empty to inherit template conditions">${escapeHtml(whenPlaceholder)}</textarea>
                                        </label>
                                        <label>
                                            Meta Override (JSON)
                                            <textarea data-field="overrides.metaJson" placeholder="Leave empty to inherit template metadata">${escapeHtml(metaPlaceholder)}</textarea>
                                        </label>
                                        <div>
                                            <details>
                                                <summary>Original Template Rule</summary>
                                                <pre>${escapeHtml(definitionPreview)}</pre>
                                            </details>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        `;
                    }

                    const penaltyOptions = penaltyTypes.map((type) => `
                        <option value="${type}"${rule.data.penaltyType === type ? ' selected' : ''}>${type}</option>
                    `).join('');
                    const stopOptions = `
                        <option value="0"${!rule.data.stop_processing ? ' selected' : ''}>Continue to next rules</option>
                        <option value="1"${rule.data.stop_processing ? ' selected' : ''}>Stop after this rule</option>
                    `;

                    return `
                        <div class="rule-card" data-id="${escapeHtml(rule.id)}" data-mode="custom">
                            <div class="rule-card__header">
                                <div class="rule-card__title">
                                    <span class="badge badge-success">Custom</span>
                                    <strong>${escapeHtml(rule.data.label || 'Untitled Custom Rule')}</strong>
                                </div>
                                <div class="rule-card__actions">
                                    <button type="button" class="btn btn-secondary btn-icon" data-action="move-up" title="Move up">Up</button>
                                    <button type="button" class="btn btn-secondary btn-icon" data-action="move-down" title="Move down">Down</button>
                                    <button type="button" class="btn btn-secondary btn-icon" data-action="duplicate" title="Duplicate">Duplicate</button>
                                    <button type="button" class="btn btn-danger btn-icon" data-action="remove" title="Remove">Remove</button>
                                </div>
                            </div>
                            <div class="form-grid two">
                                <label>
                                    Label
                                    <input type="text" data-field="data.label" value="${escapeHtml(rule.data.label)}" placeholder="Display label">
                                </label>
                                <label>
                                    Category
                                    <input type="text" data-field="data.category" value="${escapeHtml(rule.data.category)}" placeholder="e.g. lateness">
                                </label>
                                <label>
                                    Scope
                                    <input type="text" data-field="data.scope" value="${escapeHtml(rule.data.scope)}" placeholder="daily">
                                </label>
                                <label>
                                    Penalty Type
                                    <select data-field="data.penaltyType">${penaltyOptions}</select>
                                </label>
                                <label>
                                    Penalty Value
                                    <input type="number" step="0.01" data-field="data.penaltyValue" value="${escapeHtml(rule.data.penaltyValue)}">
                                </label>
                                <label>
                                    Penalty Unit
                                    <input type="text" data-field="data.penaltyUnit" value="${escapeHtml(rule.data.penaltyUnit)}">
                                </label>
                                <label>
                                    Stop Processing
                                    <select data-field="data.stop_processing">${stopOptions}</select>
                                </label>
                                <label>
                                    Notes
                                    <textarea data-field="data.notes" placeholder="Optional notes">${escapeHtml(rule.data.notes)}</textarea>
                                </label>
                                <label>
                                    Color (hex without #)
                                    <input type="text" data-field="data.color" value="${escapeHtml(rule.data.color ? rule.data.color.replace('#', '') : '')}">
                                </label>
                            </div>
                            <details class="advanced-toggle">
                                <summary>Advanced Settings</summary>
                                <div class="form-grid">
                                    <label>
                                        Conditions (JSON)
                                        <textarea data-field="data.whenJson">${escapeHtml(rule.data.whenJson)}</textarea>
                                    </label>
                                    <label>
                                        Meta (JSON)
                                        <textarea data-field="data.metaJson">${escapeHtml(rule.data.metaJson)}</textarea>
                                    </label>
                                </div>
                            </details>
                        </div>
                    `;
                }).join('');

                builderRulesContainer.innerHTML = cards;
            }

            function initializeBuilderFromPlan() {
                const rules = state.plan && Array.isArray(state.plan.rules) ? state.plan.rules : [];
                state.builderRules = rules.map((item) => createBuilderRuleFromPlan(item));
                renderBuilder();
            }

            function applyPlanResponse(payload) {
                state.plan = payload.plan || null;
                state.planable = payload.planable || null;
                state.context = payload.context || null;
                state.effectivePlan = payload.effective_plan || null;

                state.planSettings = {
                    grace: state.plan && state.plan.grace_minutes !== undefined && state.plan.grace_minutes !== null
                        ? state.plan.grace_minutes
                        : '',
                    overwrite: !!(state.plan && state.plan.overwrite),
                    overwrite_dep: !!(state.plan && state.plan.overwrite_dep),
                    overwrite_subdep: !!(state.plan && state.plan.overwrite_subdep)
                };

                updatePlanContextUI(state.context);
                syncPlanSettingsInputs();
                renderPlanSummary();
                initializeBuilderFromPlan();

                if (builderSection) {
                    builderSection.hidden = false;
                }
            }

            function getSelectedTarget() {
                if (state.scope === 'department' && state.selectedDepartment) {
                    return { scope: 'department', id: state.selectedDepartment };
                }
                if (state.scope === 'sub-department' && state.selectedSubDepartment) {
                    return { scope: 'sub-department', id: state.selectedSubDepartment };
                }
                if (state.scope === 'user' && state.selectedUser) {
                    return { scope: 'user', id: state.selectedUser };
                }
                return null;
            }

            async function fetchPlan(scope, id) {
                const url = config.routes.planShow
                    .replace(':scope', encodeURIComponent(scope))
                    .replace(':id', encodeURIComponent(id));

                setPlanLoading(true);

                try {
                    const response = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const body = await response.json();

                    if (!response.ok || body.result === 'false') {
                        showToast(body.message || 'Unable to load plan data.', 'error');
                        clearPlanDisplay();
                        return;
                    }

                    applyPlanResponse(body.data || {});
                } catch (error) {
                    showToast(error.message || 'Unexpected error while loading plan.', 'error');
                    clearPlanDisplay();
                } finally {
                    setPlanLoading(false);
                }
            }

            function maybeFetchPlan() {
                const target = getSelectedTarget();
                if (!target) {
                    clearPlanDisplay();
                    return;
                }
                fetchPlan(target.scope, target.id);
            }

            function buildPlanPayload() {
                const payload = {
                    grace_minutes: state.planSettings.grace === '' ? null : Number(state.planSettings.grace),
                    rules: []
                };

                if (state.context && state.context.supports_overwrite) {
                    payload.overwrite = !!state.planSettings.overwrite;
                    payload.overwrite_dep = !!state.planSettings.overwrite_dep;
                    payload.overwrite_subdep = !!state.planSettings.overwrite_subdep;
                } else {
                    payload.overwrite = false;
                    payload.overwrite_dep = false;
                    payload.overwrite_subdep = false;
                }

                state.builderRules.forEach((rule, index) => {
                    if (rule.mode === 'template') {
                        const entry = {
                            template_key: rule.templateKey,
                            template_id: rule.templateId,
                            order: index
                        };

                        if (rule.meta && Object.keys(rule.meta).length > 0) {
                            entry.meta = rule.meta;
                        }

                        const overrides = {};

                        if (rule.overrides.notes && rule.overrides.notes.trim() !== '') {
                            overrides.notes = rule.overrides.notes.trim();
                        }

                        const overrideColor = normalizeColorForPayload(rule.overrides.color);
                        if (overrideColor) {
                            overrides.color = overrideColor;
                        }

                        const overridePenaltyType = rule.overrides.penaltyType || rule.base.penaltyType || 'fixed_minutes';
                        const overridePenaltyValue = rule.overrides.penaltyValue;
                        const overridePenaltyUnit = rule.overrides.penaltyUnit || '';

                        if (
                            overridePenaltyType !== (rule.base.penaltyType || 'fixed_minutes') ||
                            (overridePenaltyValue !== '' && Number(overridePenaltyValue) !== Number(rule.base.penaltyValue || 0)) ||
                            (overridePenaltyUnit && overridePenaltyUnit !== (rule.base.penaltyUnit || ''))
                        ) {
                            const penalty = { type: overridePenaltyType };
                            if (overridePenaltyValue !== '') {
                                const numericPenalty = Number(overridePenaltyValue);
                                if (!Number.isFinite(numericPenalty)) {
                                    throw new Error('Penalty value must be numeric.');
                                }
                                penalty.value = numericPenalty;
                            }
                            if (overridePenaltyUnit.trim() !== '') {
                                penalty.unit = overridePenaltyUnit.trim();
                            }
                            overrides.penalty = penalty;
                        }

                        if (rule.overrides.whenJson && rule.overrides.whenJson.trim() !== '') {
                            overrides.when = parseJsonField(rule.overrides.whenJson, 'Template condition override');
                        }

                        if (rule.overrides.metaJson && rule.overrides.metaJson.trim() !== '') {
                            overrides.meta = parseJsonField(rule.overrides.metaJson, 'Template meta override');
                        }

                        if (rule.overrides.stopProcessing === 'force_true') {
                            overrides.stop_processing = true;
                        } else if (rule.overrides.stopProcessing === 'force_false') {
                            overrides.stop_processing = false;
                        }

                        if (Object.keys(overrides).length > 0) {
                            entry.overrides = overrides;
                        }

                        payload.rules.push(entry);
                    } else {
                        const custom = {
                            label: rule.data.label.trim(),
                            category: rule.data.category.trim() || 'other',
                            scope: rule.data.scope.trim() || 'daily',
                            penalty: { type: rule.data.penaltyType || 'fixed_minutes' },
                            when: parseJsonField(rule.data.whenJson, 'Custom rule conditions') || [],
                            notes: rule.data.notes ? rule.data.notes.trim() : null,
                            color: normalizeColorForPayload(rule.data.color),
                            stop_processing: !!rule.data.stop_processing,
                            order: index
                        };

                        if (rule.data.penaltyValue !== '') {
                            const numericValue = Number(rule.data.penaltyValue);
                            if (!Number.isFinite(numericValue)) {
                                throw new Error(`Penalty value for "${rule.data.label || 'Custom rule'}" must be numeric.`);
                            }
                            custom.penalty.value = numericValue;
                        }

                        if (rule.data.penaltyUnit && rule.data.penaltyUnit.trim() !== '') {
                            custom.penalty.unit = rule.data.penaltyUnit.trim();
                        }

                        if (rule.data.metaJson && rule.data.metaJson.trim() !== '') {
                            custom.meta = parseJsonField(rule.data.metaJson, 'Custom rule meta');
                        }

                        payload.rules.push(custom);
                    }
                });

                return payload;
            }

            async function savePlan() {
                const target = getSelectedTarget();
                if (!target) {
                    showToast('Select a department, sub-department, or employee first.', 'error');
                    return;
                }

                let payload;
                try {
                    payload = buildPlanPayload();
                } catch (error) {
                    showToast(error.message || 'Unable to build plan payload.', 'error');
                    return;
                }

                const url = config.routes.planUpdate
                    .replace(':scope', encodeURIComponent(target.scope))
                    .replace(':id', encodeURIComponent(target.id));

                savePlanBtn.disabled = true;
                savePlanBtn.textContent = 'Saving...';

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken
                        },
                        body: JSON.stringify(payload)
                    });
                    const body = await response.json();

                    if (!response.ok || body.result === 'false') {
                        const message = (body.errors && Object.values(body.errors)[0]) || body.message || 'Unable to save plan.';
                        showToast(message, 'error');
                        return;
                    }

                    applyPlanResponse(body.data || {});
                    showToast(body.message || 'Plan saved successfully.');
                } catch (error) {
                    showToast(error.message || 'Unexpected error while saving plan.', 'error');
                } finally {
                    savePlanBtn.disabled = false;
                    savePlanBtn.textContent = 'Save Plan';
                }
            }

            function removeBuilderRule(id) {
                const index = state.builderRules.findIndex((rule) => rule.id === id);
                if (index >= 0) {
                    state.builderRules.splice(index, 1);
                    renderBuilder();
                }
            }

            function moveBuilderRule(id, direction) {
                const index = state.builderRules.findIndex((rule) => rule.id === id);
                if (index < 0) {
                    return;
                }
                const target = index + direction;
                if (target < 0 || target >= state.builderRules.length) {
                    return;
                }
                const [item] = state.builderRules.splice(index, 1);
                state.builderRules.splice(target, 0, item);
                renderBuilder();
            }

            function duplicateBuilderRule(id) {
                const index = state.builderRules.findIndex((rule) => rule.id === id);
                if (index < 0) {
                    return;
                }

                const clone = JSON.parse(JSON.stringify(state.builderRules[index]));
                clone.id = uniqueId('rule');
                state.builderRules.splice(index + 1, 0, clone);
                renderBuilder();
            }

            function handleBuilderClick(event) {
                const button = event.target.closest('button[data-action]');
                if (!button) {
                    return;
                }
                const card = button.closest('.rule-card');
                if (!card) {
                    return;
                }
                const id = card.dataset.id;
                switch (button.dataset.action) {
                    case 'remove':
                        removeBuilderRule(id);
                        break;
                    case 'move-up':
                        moveBuilderRule(id, -1);
                        break;
                    case 'move-down':
                        moveBuilderRule(id, 1);
                        break;
                    case 'duplicate':
                        duplicateBuilderRule(id);
                        break;
                    default:
                        break;
                }
            }

            function handleBuilderInput(event) {
                const field = event.target.getAttribute('data-field');
                if (!field) {
                    return;
                }
                const card = event.target.closest('.rule-card');
                if (!card) {
                    return;
                }
                const id = card.dataset.id;
                const rule = state.builderRules.find((item) => item.id === id);
                if (!rule) {
                    return;
                }

                const segments = field.split('.');
                let target = rule;
                for (let i = 0; i < segments.length - 1; i += 1) {
                    const key = segments[i];
                    if (!target[key] || typeof target[key] !== 'object') {
                        target[key] = {};
                    }
                    target = target[key];
                }

                const finalKey = segments[segments.length - 1];
                if (event.target.type === 'checkbox') {
                    target[finalKey] = event.target.checked;
                } else if (event.target.tagName === 'SELECT' && field === 'data.stop_processing') {
                    target[finalKey] = event.target.value === '1';
                } else {
                    target[finalKey] = event.target.value;
                }
            }

            function handleScopeChange(event) {
                state.scope = event.target.value;
                document.querySelectorAll('.target').forEach((element) => {
                    element.hidden = element.dataset.scope !== state.scope;
                });

                state.selectedDepartment = '';
                state.selectedSubDepartment = '';
                state.selectedUser = '';
                departmentSelect.value = '';
                subDeptParentSelect.value = '';
                subDeptSelect.value = '';
                userDepartmentFilter.value = '';
                userSubDepartmentFilter.value = '';
                userSelect.value = '';

                populateSubDepartmentSelect(subDeptSelect, null, false);
                populateUserSelect();
                clearPlanDisplay();
            }

            function initialize() {
                renderTemplateList();
                updateTemplateSelectOptions();
                populateDepartmentSelect(departmentSelect, false);
                populateDepartmentSelect(subDeptParentSelect, true);
                populateDepartmentSelect(userDepartmentFilter, true);
                populateSubDepartmentSelect(subDeptSelect, null, false);
                populateSubDepartmentSelect(userSubDepartmentFilter, null, true);
                populateUserSelect();

                templateSearchInput.addEventListener('input', renderTemplateList);
                templateNewBtn.addEventListener('click', () => openTemplateForm('create'));
                templateCancelBtn.addEventListener('click', closeTemplateForm);
                templateForm.addEventListener('submit', submitTemplateForm);

                templateList.addEventListener('click', (event) => {
                    const button = event.target.closest('button[data-action]');
                    if (!button) {
                        return;
                    }
                    const row = button.closest('tr[data-id]');
                    if (!row) {
                        return;
                    }
                    const id = row.dataset.id;
                    const key = row.dataset.key;

                    if (button.dataset.action === 'edit-template') {
                        const template = templateIdMap.get(Number(id)) || templateKeyMap.get(key);
                        if (template) {
                            openTemplateForm('edit', template);
                        }
                    } else if (button.dataset.action === 'delete-template') {
                        deleteTemplate(id);
                    }
                });

                scopeRadios.forEach((radio) => radio.addEventListener('change', handleScopeChange));

                departmentSelect.addEventListener('change', (event) => {
                    state.selectedDepartment = event.target.value;
                    maybeFetchPlan();
                });

                subDeptParentSelect.addEventListener('change', (event) => {
                    const departmentId = event.target.value || null;
                    populateSubDepartmentSelect(subDeptSelect, departmentId, false);
                    subDeptSelect.value = '';
                    state.selectedSubDepartment = '';
                    maybeFetchPlan();
                });

                subDeptSelect.addEventListener('change', (event) => {
                    state.selectedSubDepartment = event.target.value;
                    maybeFetchPlan();
                });

                userDepartmentFilter.addEventListener('change', (event) => {
                    const departmentId = event.target.value || null;
                    populateSubDepartmentSelect(userSubDepartmentFilter, departmentId, true);
                    userSubDepartmentFilter.value = '';
                    populateUserSelect();
                    userSelect.value = '';
                    state.selectedUser = '';
                    maybeFetchPlan();
                });

                userSubDepartmentFilter.addEventListener('change', () => {
                    populateUserSelect();
                    userSelect.value = '';
                    state.selectedUser = '';
                    maybeFetchPlan();
                });

                userSelect.addEventListener('change', (event) => {
                    state.selectedUser = event.target.value;
                    maybeFetchPlan();
                });

                addTemplateRuleBtn.addEventListener('click', () => {
                    const key = builderTemplateSelect.value;
                    if (!key) {
                        showToast('Select a template first.', 'error');
                        return;
                    }
                    if (!templateKeyMap.has(key)) {
                        showToast('Template not found.', 'error');
                        return;
                    }
                    const template = templateKeyMap.get(key);
                    const definitions = normalizeTemplateRuleDefinitions(template.rule);
                    definitions.forEach((definition, index) => {
                        state.builderRules.push(createBuilderRuleFromTemplate(template, definition, index));
                    });
                    renderBuilder();
                    showToast(`${template.name} added (${definitions.length} rule${definitions.length === 1 ? '' : 's'}).`);
                });

                addCustomRuleBtn.addEventListener('click', () => {
                    state.builderRules.push(createEmptyCustomRule());
                    renderBuilder();
                });

                builderRulesContainer.addEventListener('click', handleBuilderClick);
                builderRulesContainer.addEventListener('input', handleBuilderInput);
                builderRulesContainer.addEventListener('change', handleBuilderInput);

                planGraceInput.addEventListener('input', (event) => {
                    state.planSettings.grace = event.target.value;
                });
                overwriteAllCheckbox.addEventListener('change', (event) => {
                    state.planSettings.overwrite = event.target.checked;
                });
                overwriteDepCheckbox.addEventListener('change', (event) => {
                    state.planSettings.overwrite_dep = event.target.checked;
                });
                overwriteSubDepCheckbox.addEventListener('change', (event) => {
                    state.planSettings.overwrite_subdep = event.target.checked;
                });

                savePlanBtn.addEventListener('click', savePlan);

                const firstDepartmentOption = departmentSelect.options[1];
                if (firstDepartmentOption) {
                    departmentSelect.value = firstDepartmentOption.value;
                    state.selectedDepartment = firstDepartmentOption.value;
                    maybeFetchPlan();
                }
            }

            initialize();
        })();
    </script>
@endsection
