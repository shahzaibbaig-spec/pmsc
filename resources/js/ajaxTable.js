export class AjaxTable {
    constructor(options = {}) {
        this.endpoint = options.endpoint || '';
        this.tbody = this.resolve(options.tbody);
        this.searchInput = this.resolve(options.searchInput);
        this.perPageInput = this.resolve(options.perPageInput);
        this.prevBtn = this.resolve(options.prevBtn);
        this.nextBtn = this.resolve(options.nextBtn);
        this.paginationInfo = this.resolve(options.paginationInfo);
        this.sortHeaders = options.sortHeaders || '[data-sort]';
        this.rowRenderer = options.rowRenderer || (() => '');
        this.emptyText = options.emptyText || 'No records found.';
        this.loadingText = options.loadingText || 'Loading...';
        this.errorText = options.errorText || 'Failed to load records.';
        this.extraParams = options.extraParams || (() => ({}));
        this.onLoaded = options.onLoaded || (() => null);

        this.state = {
            page: 1,
            per_page: Number(this.perPageInput?.value || options.perPage || 10),
            search: '',
            sort: options.sort || '',
            dir: options.dir || 'asc',
        };

        this.bindEvents();
        this.load();
    }

    resolve(target) {
        if (!target) {
            return null;
        }

        if (typeof target === 'string') {
            return document.querySelector(target);
        }

        return target;
    }

    bindEvents() {
        if (this.searchInput) {
            const debounced = window.NSMS.debounce(() => {
                this.state.search = this.searchInput.value.trim();
                this.state.page = 1;
                this.load();
            }, 300);
            this.searchInput.addEventListener('input', debounced);
        }

        if (this.perPageInput) {
            this.perPageInput.addEventListener('change', () => {
                this.state.per_page = Number(this.perPageInput.value || 10);
                this.state.page = 1;
                this.load();
            });
        }

        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => {
                if (this.state.page <= 1) {
                    return;
                }

                this.state.page -= 1;
                this.load();
            });
        }

        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => {
                this.state.page += 1;
                this.load();
            });
        }

        document.querySelectorAll(this.sortHeaders).forEach((header) => {
            header.addEventListener('click', () => {
                const sortBy = header.dataset.sort;
                if (!sortBy) {
                    return;
                }

                if (this.state.sort === sortBy) {
                    this.state.dir = this.state.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.state.sort = sortBy;
                    this.state.dir = 'asc';
                }

                this.state.page = 1;
                this.load();
            });
        });
    }

    async load() {
        if (!this.endpoint || !this.tbody) {
            return;
        }

        this.renderLoading();

        const params = new URLSearchParams({
            page: String(this.state.page),
            per_page: String(this.state.per_page),
            search: this.state.search,
        });

        if (this.state.sort) {
            params.set('sort', this.state.sort);
            params.set('dir', this.state.dir);
        }

        const extra = this.extraParams() || {};
        Object.entries(extra).forEach(([key, value]) => {
            if (value !== null && value !== undefined && String(value).trim() !== '') {
                params.set(key, String(value));
            }
        });

        const response = await fetch(`${this.endpoint}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        }).catch(() => null);

        if (!response || !response.ok) {
            this.renderError();
            return;
        }

        const payload = await response.json();
        const rows = payload.data || [];
        const meta = this.normalizeMeta(payload.meta || {});

        if (!rows.length) {
            this.tbody.innerHTML = `<tr><td colspan="12" class="px-4 py-8 text-center text-sm text-slate-500">${this.emptyText}</td></tr>`;
        } else {
            this.tbody.innerHTML = rows.map((row, index) => this.rowRenderer(row, index)).join('');
        }

        this.updatePagination(meta);
        this.onLoaded(payload, meta);
    }

    normalizeMeta(meta) {
        return {
            page: Number(meta.page || meta.current_page || 1),
            per_page: Number(meta.per_page || 10),
            total: Number(meta.total || 0),
            last_page: Number(meta.last_page || 1),
        };
    }

    updatePagination(meta) {
        this.state.page = meta.page;

        if (this.paginationInfo) {
            this.paginationInfo.textContent = `Page ${meta.page} of ${meta.last_page} | Total: ${meta.total}`;
        }

        if (this.prevBtn) {
            this.prevBtn.disabled = meta.page <= 1;
        }

        if (this.nextBtn) {
            this.nextBtn.disabled = meta.page >= meta.last_page;
        }
    }

    renderLoading() {
        this.tbody.innerHTML = `<tr><td colspan="12" class="px-4 py-8 text-center text-sm text-slate-500">${this.loadingText}</td></tr>`;
    }

    renderError() {
        this.tbody.innerHTML = `<tr><td colspan="12" class="px-4 py-8 text-center text-sm text-rose-600">${this.errorText}</td></tr>`;
    }

    reload() {
        this.load();
    }
}
