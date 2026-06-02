export type CategoryType = 'item' | 'project' | 'activity' | 'certificate';
export type StockMovementType = 'inbound' | 'outbound' | 'transfer' | 'project_allocation';

export type PaginationMeta = {
	current_page: number;
	last_page: number;
	per_page: number;
	total: number;
};

export type PaginatedResponse<T> = {
	data: T[];
	meta?: Partial<PaginationMeta>;
	message?: string;
};

export type Category = {
	id: number;
	name: string;
	type: CategoryType;
	created_at?: string | null;
	updated_at?: string | null;
};

export type Warehouse = {
	id: number;
	name: string;
	location?: string | null;
	inventories_count?: number;
	inventories?: Inventory[];
	created_at?: string | null;
	updated_at?: string | null;
};

export type Item = {
	id: number;
	sku: string;
	category_id: number;
	category?: Category | null;
	name: string;
	unit: string;
	minimum_stock: number;
	inventories?: Inventory[];
	created_at?: string | null;
	updated_at?: string | null;
};

export type Inventory = {
	id: number;
	item_id: number;
	warehouse_id: number;
	quantity: number;
	item?: Item | null;
	warehouse?: Pick<Warehouse, 'id' | 'name'> | null;
};

export type ProjectOption = {
	id: number;
	name: string;
};

export type StockMovement = {
	id: number;
	type: StockMovementType;
	item_id: number;
	item?: Item | null;
	source_warehouse_id?: number | null;
	source_warehouse?: Warehouse | null;
	destination_warehouse_id?: number | null;
	destination_warehouse?: Warehouse | null;
	project_id?: number | null;
	project?: ProjectOption | null;
	quantity: number;
	notes?: string | null;
	occurred_at?: string | null;
	created_at?: string | null;
	updated_at?: string | null;
};

export const CATEGORY_TYPE_OPTIONS: Array<{ value: CategoryType; label: string }> = [
	{ value: 'item', label: 'Item' },
	{ value: 'project', label: 'Project' },
	{ value: 'activity', label: 'Activity' },
	{ value: 'certificate', label: 'Certificate' }
];

export const STOCK_MOVEMENT_TYPE_OPTIONS: Array<{ value: StockMovementType; label: string }> = [
	{ value: 'inbound', label: 'Inbound' },
	{ value: 'outbound', label: 'Outbound' },
	{ value: 'transfer', label: 'Transfer' },
	{ value: 'project_allocation', label: 'Alokasi Project' }
];

type ApiError = {
	response?: {
		data?: {
			message?: string;
			errors?: Record<string, string[]>;
		};
	};
};

export function normalizeMeta<T>(
	payload: PaginatedResponse<T>,
	fallbackLength: number
): PaginationMeta {
	return {
		current_page: payload.meta?.current_page ?? 1,
		last_page: payload.meta?.last_page ?? 1,
		per_page: payload.meta?.per_page ?? fallbackLength,
		total: payload.meta?.total ?? fallbackLength
	};
}

export function getApiErrorMessage(error: unknown, fallback: string): string {
	const apiError = error as ApiError;
	const errors = apiError.response?.data?.errors;

	if (errors) {
		return Object.values(errors).flat().join('\n');
	}

	return apiError.response?.data?.message ?? fallback;
}

export function formatDateTime(value?: string | null): string {
	if (!value) return '-';

	const date = new Date(value);
	if (Number.isNaN(date.getTime())) return value;

	return new Intl.DateTimeFormat('id-ID', {
		day: '2-digit',
		month: 'short',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit'
	}).format(date);
}

export function formatNumber(value: number | string | null | undefined): string {
	const numberValue = Number(value ?? 0);

	return new Intl.NumberFormat('id-ID').format(Number.isFinite(numberValue) ? numberValue : 0);
}
