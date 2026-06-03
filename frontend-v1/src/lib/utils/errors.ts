type LaravelErrorValue = string | number | boolean | null | undefined | LaravelErrorValue[];
type LaravelErrorPayload = {
	message?: unknown;
	error?: unknown;
	errors?: unknown;
};

const FALLBACK_ERROR_MESSAGE = 'Terjadi kesalahan. Silakan coba lagi.';

/** Narrows unknown values to object records. */
function isRecord(value: unknown): value is Record<string, unknown> {
	return typeof value === 'object' && value !== null;
}

/** Recursively stringifies Laravel validation error values. */
function stringifyErrorValue(value: LaravelErrorValue): string[] {
	if (Array.isArray(value)) {
		return value.flatMap((item) => stringifyErrorValue(item));
	}

	if (value === null || value === undefined || value === '') {
		return [];
	}

	return [String(value)];
}

/** Reads the Laravel response payload from axios-like errors. */
function extractResponseData(err: unknown): LaravelErrorPayload | undefined {
	if (!isRecord(err) || !isRecord(err.response)) {
		return undefined;
	}

	const { data } = err.response;
	return isRecord(data) ? data : undefined;
}

/** Flattens Laravel validation error bags into a newline-separated string. */
function extractValidationErrors(errors: unknown): string | undefined {
	if (!isRecord(errors)) {
		return undefined;
	}

	const messages = Object.values(errors).flatMap((value) =>
		stringifyErrorValue(value as LaravelErrorValue)
	);

	return messages.length > 0 ? messages.join('\n') : undefined;
}

/** Returns non-empty string messages only. */
function extractStringMessage(value: unknown): string | undefined {
	return typeof value === 'string' && value.trim() ? value : undefined;
}

/** Extracts a user-readable message from Laravel or generic JavaScript errors. */
export function extractApiErrors(err: unknown): string {
	const responseData = extractResponseData(err);
	const validationMessage = extractValidationErrors(responseData?.errors);

	return (
		validationMessage ??
		extractStringMessage(responseData?.message) ??
		extractStringMessage(responseData?.error) ??
		(err instanceof Error ? err.message : undefined) ??
		FALLBACK_ERROR_MESSAGE
	);
}
