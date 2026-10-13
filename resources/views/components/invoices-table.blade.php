@props([
    'invoices' => [],
    'currency' => config('billing.currency', 'Tk.'),
])

@php
    $currencySymbol = (string) $currency;
    $rawInvoices = collect($invoices)->map(function ($inv) use ($currencySymbol) {
        $serverUrl = rtrim((string) config('billing.server_url', 'https://billing.techghor.com.bd'), '/');
        $hash = $inv['payment_hash'] ?? null;

        $publicView = $inv['links']['public_view'] ?? null;
        if (! $publicView && $hash) {
            $publicView = "{$serverUrl}/invoices/{$hash}";
        }

        $pdfDownload = $inv['links']['pdf_download'] ?? null;
        if (! $pdfDownload && $hash) {
            $pdfDownload = "{$serverUrl}/invoices/{$hash}/pdf";
        }

        $total = (float) ($inv['total_amount'] ?? 0);
        $paid = (float) ($inv['paid_amount'] ?? 0);
        $due = (float) ($inv['due_amount'] ?? max(0, $total - $paid));
        $status = strtolower((string) ($inv['status'] ?? 'unpaid'));

        return [
            'id'                   => $inv['id'] ?? null,
            'invoice_number'       => (string) ($inv['invoice_number'] ?? ('INV-' . ($inv['id'] ?? '—'))),
            'status'               => $status,
            'total_amount'         => $total,
            'paid_amount'          => $paid,
            'due_amount'           => $due,
            'issue_date'           => $inv['issue_date'] ?? '—',
            'due_date'             => $inv['due_date'] ?? '—',
            'recurring_profile_id' => $inv['recurring_profile_id'] ?? null,
            'payment_hash'         => $hash,
            'ref'                  => $hash ? (substr($hash, 0, 10) . '...' . substr($hash, -6)) : null,
            'public_view'          => $publicView,
            'pdf_download'         => $pdfDownload,
        ];
    })->values();

    $totalInvoicesCount = $rawInvoices->count();
    $totalBilledAmount = $rawInvoices->sum('total_amount');
    $totalPaidAmount = $rawInvoices->sum('paid_amount');
    $totalDueAmount = $rawInvoices->sum('due_amount');
@endphp

<div x-data="billingInvoicesDataTable({
        rawItems: {{ Js::from($rawInvoices) }},
        currency: {{ Js::from($currencySymbol) }}
    })"
    class="space-y-4">

    <!-- Header & Summary Strip -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold tracking-tight text-slate-800">Invoice History</h3>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 border border-slate-200">
                    <span x-text="filteredItems.length"></span> of {{ $totalInvoicesCount }}
                </span>
            </div>
            <p class="text-[11px] text-slate-500 mt-0.5">Comprehensive history of past and current subscription invoices.</p>
        </div>

        <!-- Quick Summary Stats Chips -->
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <div class="rounded-lg bg-slate-50 border border-slate-200/80 px-2.5 py-1">
                <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">Total Billed:</span>
                <span class="font-bold text-slate-800 tabular-nums ml-1">{{ $currencySymbol }} {{ number_format($totalBilledAmount, 2) }}</span>
            </div>
            <div class="rounded-lg bg-emerald-50/70 border border-emerald-200/80 px-2.5 py-1">
                <span class="text-[10px] font-medium text-emerald-600 uppercase tracking-wider">Total Paid:</span>
                <span class="font-bold text-emerald-800 tabular-nums ml-1">{{ $currencySymbol }} {{ number_format($totalPaidAmount, 2) }}</span>
            </div>
            @if ($totalDueAmount > 0)
                <div class="rounded-lg bg-rose-50/70 border border-rose-200/80 px-2.5 py-1">
                    <span class="text-[10px] font-medium text-rose-600 uppercase tracking-wider">Total Due:</span>
                    <span class="font-bold text-rose-800 tabular-nums ml-1">{{ $currencySymbol }} {{ number_format($totalDueAmount, 2) }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- DataTable Controls: Search, Filter, Page Size -->
    <div class="flex flex-wrap items-center justify-between gap-2.5 rounded-xl bg-white p-2.5 border border-slate-200 shadow-2xs">
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[280px]">
            <!-- Live Search -->
            <div class="relative flex-1 min-w-[180px] max-w-sm">
                <input type="text"
                       x-model="search"
                       placeholder="Search invoice #, date, status, amount..."
                       class="w-full h-8 rounded-lg border border-slate-200 bg-slate-50 pl-8 pr-7 text-xs text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <button type="button"
                        x-show="search.length > 0"
                        @click="search = ''"
                        class="absolute right-2 top-2 text-slate-400 hover:text-slate-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Status Filter -->
            <select x-model="statusFilter"
                    class="h-8 rounded-lg border border-slate-200 bg-slate-50 py-1 px-2.5 text-xs text-slate-700 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                <option value="">All Statuses</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
                <option value="partially_paid">Partially Paid</option>
                <option value="overdue">Overdue</option>
                <option value="cancelled">Cancelled</option>
            </select>

            <button type="button"
                    x-show="search || statusFilter"
                    @click="resetFilters()"
                    class="h-8 px-2 flex items-center text-xs font-medium text-slate-500 hover:text-slate-800 transition">
                Reset
            </button>
        </div>

        <!-- Page Size Selector -->
        <div class="flex items-center gap-1.5 text-xs text-slate-500">
            <span>Show</span>
            <select x-model.number="perPage"
                    @change="currentPage = 1"
                    class="h-8 rounded-lg border border-slate-200 bg-slate-50 py-1 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 transition">
                <option :value="5">5</option>
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
                <option :value="100">100</option>
            </select>
            <span>entries</span>
        </div>
    </div>

    <!-- Data Table Container -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xs">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 font-semibold uppercase tracking-wider text-[10px] text-slate-500 select-none">
                    <tr>
                        <!-- Invoice Number Column -->
                        <th @click="sortBy('invoice_number')" class="px-3.5 py-2.5 cursor-pointer hover:bg-slate-100 transition whitespace-nowrap">
                            <div class="flex items-center gap-1">
                                <span>Invoice #</span>
                                <span class="text-slate-400 text-[10px]" x-html="sortIndicator('invoice_number')"></span>
                            </div>
                        </th>

                        <!-- Issue Date Column -->
                        <th @click="sortBy('issue_date')" class="px-3 py-2.5 cursor-pointer hover:bg-slate-100 transition whitespace-nowrap">
                            <div class="flex items-center gap-1">
                                <span>Issue Date</span>
                                <span class="text-slate-400 text-[10px]" x-html="sortIndicator('issue_date')"></span>
                            </div>
                        </th>

                        <!-- Due Date Column -->
                        <th @click="sortBy('due_date')" class="px-3 py-2.5 cursor-pointer hover:bg-slate-100 transition whitespace-nowrap">
                            <div class="flex items-center gap-1">
                                <span>Due Date</span>
                                <span class="text-slate-400 text-[10px]" x-html="sortIndicator('due_date')"></span>
                            </div>
                        </th>

                        <!-- Total Amount Column -->
                        <th @click="sortBy('total_amount')" class="px-3 py-2.5 cursor-pointer hover:bg-slate-100 transition whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <span>Total</span>
                                <span class="text-slate-400 text-[10px]" x-html="sortIndicator('total_amount')"></span>
                            </div>
                        </th>

                        <!-- Paid Amount Column -->
                        <th @click="sortBy('paid_amount')" class="px-3 py-2.5 cursor-pointer hover:bg-slate-100 transition whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <span>Paid</span>
                                <span class="text-slate-400 text-[10px]" x-html="sortIndicator('paid_amount')"></span>
                            </div>
                        </th>

                        <!-- Due Amount Column -->
                        <th @click="sortBy('due_amount')" class="px-3 py-2.5 cursor-pointer hover:bg-slate-100 transition whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <span>Due</span>
                                <span class="text-slate-400 text-[10px]" x-html="sortIndicator('due_amount')"></span>
                            </div>
                        </th>

                        <!-- Status Column -->
                        <th @click="sortBy('status')" class="px-3 py-2.5 cursor-pointer hover:bg-slate-100 transition whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-1">
                                <span>Status</span>
                                <span class="text-slate-400 text-[10px]" x-html="sortIndicator('status')"></span>
                            </div>
                        </th>

                        <!-- Actions Column -->
                        <th class="px-3.5 py-2.5 text-right whitespace-nowrap">
                            <span>Actions</span>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <template x-for="(invoice, index) in paginatedItems" :key="invoice.id || index">
                        <tr class="hover:bg-slate-50/80 transition group">
                            <!-- Invoice # & Ref -->
                            <td class="px-3.5 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 font-semibold text-slate-900">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <span x-text="invoice.invoice_number"></span>
                                    <template x-if="invoice.recurring_profile_id">
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100"
                                              :title="'Recurring Profile ID #' + invoice.recurring_profile_id"
                                              x-text="'#Profile ' + invoice.recurring_profile_id">
                                        </span>
                                    </template>
                                </div>
                                <template x-if="invoice.payment_hash">
                                    <div class="mt-0.5 flex items-center gap-1 text-[10px] text-slate-400 font-normal">
                                        <span class="text-slate-400 font-medium">Ref:</span>
                                        <span class="font-mono text-slate-500 hover:text-indigo-600 transition"
                                              x-text="invoice.ref"
                                              :title="invoice.payment_hash"></span>
                                        <button type="button"
                                                @click="navigator.clipboard.writeText(invoice.payment_hash)"
                                                class="text-slate-400 hover:text-indigo-600 transition"
                                                title="Copy full payment hash">
                                            <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </td>

                            <!-- Issue Date -->
                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap" x-text="invoice.issue_date"></td>

                            <!-- Due Date -->
                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap" x-text="invoice.due_date"></td>

                            <!-- Total Amount -->
                            <td class="px-3 py-2 text-right font-medium text-slate-800 whitespace-nowrap tabular-nums">
                                <span x-text="currency"></span> <span x-text="formatNumber(invoice.total_amount)"></span>
                            </td>

                            <!-- Paid Amount -->
                            <td class="px-3 py-2 text-right font-medium text-emerald-700 whitespace-nowrap tabular-nums">
                                <span x-text="currency"></span> <span x-text="formatNumber(invoice.paid_amount)"></span>
                            </td>

                            <!-- Due Amount -->
                            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap tabular-nums"
                                :class="invoice.due_amount > 0 ? 'text-rose-600' : 'text-slate-400'">
                                <span x-text="currency"></span> <span x-text="formatNumber(invoice.due_amount)"></span>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border"
                                      :class="statusBadgeClass(invoice.status)">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 shrink-0" :class="statusDotClass(invoice.status)"></span>
                                    <span x-text="formatStatus(invoice.status)"></span>
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-3.5 py-2 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <template x-if="invoice.public_view">
                                        <a :href="invoice.public_view"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-medium bg-slate-100 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 border border-slate-200 transition"
                                           :title="'View invoice ' + invoice.invoice_number">
                                            <span>View</span>
                                            <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    </template>

                                    <template x-if="invoice.pdf_download">
                                        <a :href="invoice.pdf_download"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="inline-flex items-center p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                                           title="Download PDF">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </a>
                                    </template>

                                    <template x-if="invoice.due_amount > 0 && invoice.public_view">
                                        <a :href="invoice.public_view"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-semibold bg-rose-600 hover:bg-rose-700 text-white shadow-2xs transition">
                                            Pay
                                        </a>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Empty State -->
                    <tr x-show="filteredItems.length === 0">
                        <td colspan="8" class="px-4 py-10 text-center">
                            <div class="max-w-sm mx-auto text-slate-400">
                                <svg class="w-8 h-8 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="text-xs font-semibold text-slate-600">No invoices match your criteria</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Try clearing the search query or status filter.</p>
                                <button type="button"
                                        @click="resetFilters()"
                                        x-show="search || statusFilter"
                                        class="mt-3 inline-flex items-center px-2.5 py-1 rounded text-xs font-medium bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                    Clear Filters
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- DataTable Pagination Bar -->
        <div class="px-3.5 py-3 border-t border-slate-100 bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-500">
            <div>
                Showing
                <span class="font-semibold text-slate-800" x-text="filteredItems.length === 0 ? 0 : (currentPage - 1) * perPage + 1"></span>
                to
                <span class="font-semibold text-slate-800" x-text="Math.min(currentPage * perPage, filteredItems.length)"></span>
                of
                <span class="font-semibold text-slate-800" x-text="filteredItems.length"></span>
                entries
                <span x-show="filteredItems.length !== allItems.length" class="text-slate-400">
                    (filtered from <span x-text="allItems.length"></span> total)
                </span>
            </div>

            <!-- Page Buttons -->
            <div class="flex items-center space-x-1" x-show="totalPages > 1">
                <button type="button"
                        @click="changePage(currentPage - 1)"
                        :disabled="currentPage <= 1"
                        :class="currentPage <= 1 ? 'opacity-40 cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-100'"
                        class="px-2.5 py-1 rounded border border-slate-200 text-xs font-medium transition">
                    &larr; Prev
                </button>

                <template x-for="p in visiblePages" :key="p">
                    <button type="button"
                            @click="changePage(p)"
                            :class="p === currentPage ? 'bg-indigo-600 text-white border-indigo-600 font-semibold' : 'text-slate-700 hover:bg-slate-100 border-slate-200'"
                            class="min-w-[30px] h-7 px-2 rounded border text-xs transition"
                            x-text="p">
                    </button>
                </template>

                <button type="button"
                        @click="changePage(currentPage + 1)"
                        :disabled="currentPage >= totalPages"
                        :class="currentPage >= totalPages ? 'opacity-40 cursor-not-allowed text-slate-400' : 'text-slate-700 hover:bg-slate-100'"
                        class="px-2.5 py-1 rounded border border-slate-200 text-xs font-medium transition">
                    Next &rarr;
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function billingInvoicesDataTable(config) {
        return {
            allItems: config.rawItems || [],
            currency: config.currency || 'Tk.',
            search: '',
            statusFilter: '',
            sortColumn: 'due_date',
            sortDirection: 'desc',
            currentPage: 1,
            perPage: 10,

            get filteredItems() {
                let items = [...this.allItems];

                if (this.statusFilter) {
                    items = items.filter(i => (i.status || '').toLowerCase() === this.statusFilter.toLowerCase());
                }

                if (this.search && this.search.trim() !== '') {
                    const q = this.search.trim().toLowerCase();
                    items = items.filter(i => {
                        return (i.invoice_number && i.invoice_number.toLowerCase().includes(q))
                            || (i.payment_hash && i.payment_hash.toLowerCase().includes(q))
                            || (i.issue_date && i.issue_date.toLowerCase().includes(q))
                            || (i.due_date && i.due_date.toLowerCase().includes(q))
                            || (i.status && i.status.toLowerCase().includes(q))
                            || (String(i.total_amount).includes(q))
                            || (String(i.due_amount).includes(q));
                    });
                }

                items.sort((a, b) => {
                    let valA = a[this.sortColumn];
                    let valB = b[this.sortColumn];

                    if (typeof valA === 'number' && typeof valB === 'number') {
                        return this.sortDirection === 'asc' ? valA - valB : valB - valA;
                    }

                    valA = valA ? String(valA).toLowerCase() : '';
                    valB = valB ? String(valB).toLowerCase() : '';

                    if (valA < valB) return this.sortDirection === 'asc' ? -1 : 1;
                    if (valA > valB) return this.sortDirection === 'asc' ? 1 : -1;
                    return 0;
                });

                return items;
            },

            get totalPages() {
                return Math.ceil(this.filteredItems.length / this.perPage) || 1;
            },

            get paginatedItems() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.filteredItems.slice(start, start + this.perPage);
            },

            get visiblePages() {
                const total = this.totalPages;
                const cur = this.currentPage;
                const pages = [];
                const maxButtons = 5;

                let start = Math.max(1, cur - Math.floor(maxButtons / 2));
                let end = Math.min(total, start + maxButtons - 1);

                if (end - start + 1 < maxButtons) {
                    start = Math.max(1, end - maxButtons + 1);
                }

                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }
                return pages;
            },

            sortBy(column) {
                if (this.sortColumn === column) {
                    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortColumn = column;
                    this.sortDirection = ['total_amount', 'paid_amount', 'due_amount', 'due_date', 'issue_date'].includes(column) ? 'desc' : 'asc';
                }
                this.currentPage = 1;
            },

            sortIndicator(column) {
                if (this.sortColumn !== column) {
                    return '&#8597;';
                }
                return this.sortDirection === 'asc' ? '&#9650;' : '&#9660;';
            },

            changePage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.currentPage = page;
                }
            },

            resetFilters() {
                this.search = '';
                this.statusFilter = '';
                this.currentPage = 1;
            },

            formatNumber(num) {
                return Number(num || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            formatStatus(status) {
                if (!status) return 'Unknown';
                return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            },

            statusBadgeClass(status) {
                switch ((status || '').toLowerCase()) {
                    case 'paid':
                        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
                    case 'partially_paid':
                        return 'bg-amber-50 text-amber-700 border-amber-200';
                    case 'overdue':
                        return 'bg-rose-100 text-rose-800 border-rose-300';
                    case 'unpaid':
                        return 'bg-rose-50 text-rose-700 border-rose-200';
                    case 'cancelled':
                        return 'bg-slate-100 text-slate-600 border-slate-200';
                    default:
                        return 'bg-slate-50 text-slate-700 border-slate-200';
                }
            },

            statusDotClass(status) {
                switch ((status || '').toLowerCase()) {
                    case 'paid':
                        return 'bg-emerald-500';
                    case 'partially_paid':
                        return 'bg-amber-500';
                    case 'overdue':
                    case 'unpaid':
                        return 'bg-rose-500';
                    case 'cancelled':
                        return 'bg-slate-400';
                    default:
                        return 'bg-slate-400';
                }
            }
        };
    }
</script>

