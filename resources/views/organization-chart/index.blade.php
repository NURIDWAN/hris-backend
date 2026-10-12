@extends('layouts.admin')

@section('title', 'Struktur Organisasi')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Struktur Organisasi</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Struktur Organisasi</h1>
            <p class="text-secondary-500 mt-1">Visualisasi struktur organisasi perusahaan.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('organization-chart.index', ['view' => 'department']) }}"
               class="btn {{ $viewMode === 'department' ? 'btn-primary' : 'btn-ghost' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Departemen
            </a>
            <a href="{{ route('organization-chart.index', ['view' => 'employee']) }}"
               class="btn {{ $viewMode === 'employee' ? 'btn-primary' : 'btn-ghost' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Hierarki Karyawan
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="card">
            <div class="card-body-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-secondary-900">{{ $stats['total_departments'] }}</p>
                    <p class="text-xs text-secondary-500">Departemen</p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-success-100 text-success-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-secondary-900">{{ $stats['total_employees'] }}</p>
                    <p class="text-xs text-secondary-500">Total Karyawan</p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-info-100 text-info-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-secondary-900">{{ $stats['with_manager'] }}</p>
                    <p class="text-xs text-secondary-500">Punya Atasan</p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body-sm flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-warning-100 text-warning-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="text-xl font-bold text-secondary-900">{{ $stats['without_manager'] }}</p>
                    <p class="text-xs text-secondary-500">Tanpa Atasan</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Organization Chart --}}
    <div class="card" x-data="organizationChart('{{ $viewMode }}')" x-init="loadData()">
        <div class="card-header flex items-center justify-between">
            <h3 class="card-title">
                {{ $viewMode === 'department' ? 'Struktur Departemen' : 'Hierarki Karyawan' }}
            </h3>
            <div class="flex items-center gap-2">
                <button @click="expandAll()" class="btn btn-ghost btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    Expand All
                </button>
                <button @click="collapseAll()" class="btn btn-ghost btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    Collapse All
                </button>
            </div>
        </div>
        <div class="card-body">
            {{-- Loading State --}}
            <div x-show="loading" class="flex items-center justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            {{-- Empty State --}}
            <div x-show="!loading && treeData.length === 0" class="text-center py-12">
                <svg class="w-16 h-16 text-secondary-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <p class="text-secondary-500">Belum ada data untuk ditampilkan.</p>
                <p class="text-sm text-secondary-400 mt-1">Tambahkan departemen dan karyawan terlebih dahulu.</p>
            </div>

            {{-- Tree View --}}
            <div x-show="!loading && treeData.length > 0" class="org-chart-container overflow-x-auto">
                <div class="min-w-max py-4">
                    <template x-for="node in treeData" :key="node.id">
                        <div class="org-tree-root">
                            <template x-if="true">
                                <div x-html="renderNode(node, 0)"></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <style>
        .org-node {
            display: inline-block;
            margin: 0 8px 16px;
            vertical-align: top;
        }

        .org-node-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            background: white;
            min-width: 180px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .org-node-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }

        .org-node-card.department {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-color: #93c5fd;
        }

        .org-node-card.employee {
            background: white;
        }

        .org-children {
            padding-left: 24px;
            border-left: 2px solid #e2e8f0;
            margin-left: 24px;
            margin-top: 8px;
        }

        .org-children:before {
            content: '';
            display: block;
            position: relative;
            top: -8px;
            left: -26px;
            width: 24px;
            height: 2px;
            background: #e2e8f0;
        }

        .org-tree-level {
            margin-bottom: 8px;
        }

        .org-toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            background: #f1f5f9;
            color: #64748b;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .org-toggle-btn:hover {
            background: #e2e8f0;
            color: #334155;
        }
    </style>

    <script>
        function organizationChart(viewMode) {
            return {
                viewMode: viewMode,
                loading: true,
                treeData: [],
                expandedNodes: new Set(),

                async loadData() {
                    this.loading = true;
                    try {
                        const url = this.viewMode === 'department'
                            ? '{{ route('organization-chart.department-tree') }}'
                            : '{{ route('organization-chart.employee-tree') }}';
                        const response = await fetch(url);
                        this.treeData = await response.json();

                        // Expand first level by default
                        this.treeData.forEach(node => {
                            this.expandedNodes.add(node.id);
                        });
                    } catch (error) {
                        console.error('Failed to load organization data:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                toggle(nodeId) {
                    if (this.expandedNodes.has(nodeId)) {
                        this.expandedNodes.delete(nodeId);
                    } else {
                        this.expandedNodes.add(nodeId);
                    }
                    // Force re-render
                    this.treeData = [...this.treeData];
                },

                isExpanded(nodeId) {
                    return this.expandedNodes.has(nodeId);
                },

                expandAll() {
                    const addAllIds = (nodes) => {
                        nodes.forEach(node => {
                            this.expandedNodes.add(node.id);
                            if (node.children && node.children.length > 0) {
                                addAllIds(node.children);
                            }
                        });
                    };
                    addAllIds(this.treeData);
                    this.treeData = [...this.treeData];
                },

                collapseAll() {
                    this.expandedNodes.clear();
                    this.treeData = [...this.treeData];
                },

                renderNode(node, level) {
                    const isExpanded = this.expandedNodes.has(node.id);
                    const hasChildren = node.children && node.children.length > 0;

                    let html = `<div class="org-tree-level" style="margin-left: ${level * 32}px;">`;

                    if (node.type === 'department') {
                        html += `
                            <div class="org-node-card department" onclick="document.querySelector('[x-data]').__x.$data.toggle('${node.id}')">
                                <div class="flex items-center gap-3">
                                    ${hasChildren ? `
                                        <span class="org-toggle-btn">
                                            ${isExpanded ? '−' : '+'}
                                        </span>
                                    ` : '<span class="w-5"></span>'}
                                    <div class="w-10 h-10 rounded-lg bg-primary-500 text-white flex items-center justify-center font-bold text-sm">
                                        ${node.name.substring(0, 2).toUpperCase()}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-secondary-900">${node.name}</p>
                                        <p class="text-xs text-secondary-500">${node.code || ''} &bull; ${node.employee_count} karyawan</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        const initials = node.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                        html += `
                            <div class="org-node-card employee" onclick="document.querySelector('[x-data]').__x.$data.toggle('${node.id}')">
                                <div class="flex items-center gap-3">
                                    ${hasChildren ? `
                                        <span class="org-toggle-btn">
                                            ${isExpanded ? '−' : '+'}
                                        </span>
                                    ` : '<span class="w-5"></span>'}
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 text-white flex items-center justify-center font-medium text-sm">
                                        ${initials}
                                    </div>
                                    <div>
                                        <p class="font-medium text-secondary-900">${node.name}</p>
                                        <p class="text-xs text-secondary-500">${node.position}</p>
                                        ${node.department ? `<p class="text-xs text-secondary-400">${node.department}</p>` : ''}
                                        ${node.subordinate_count !== undefined && node.subordinate_count > 0 ? `<p class="text-xs text-primary-600">${node.subordinate_count} bawahan</p>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    html += '</div>';

                    if (hasChildren && isExpanded) {
                        node.children.forEach(child => {
                            html += this.renderNode(child, level + 1);
                        });
                    }

                    return html;
                }
            }
        }
    </script>
@endsection
