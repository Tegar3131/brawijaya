export class SimpbApiError extends Error {
    constructor(message, {
        status = 0,
        code = 'UNKNOWN_ERROR',
        details = null,
        meta = null,
        raw = null,
    } = {}) {
        super(message);

        this.name = 'SimpbApiError';
        this.status = status;
        this.code = code;
        this.details = details;
        this.meta = meta;
        this.raw = raw;
    }

    isValidationError() {
        return this.code === 'VALIDATION_ERROR';
    }

    isUnauthenticated() {
        return this.code === 'UNAUTHENTICATED' || this.status === 401;
    }

    isForbidden() {
        return this.code === 'FORBIDDEN' || this.status === 403;
    }

    isNotFound() {
        return this.code === 'RESOURCE_NOT_FOUND' || this.status === 404;
    }

    isRateLimited() {
        return this.code === 'RATE_LIMIT_EXCEEDED' || this.status === 429;
    }
}

export function normalizeApiError(error) {
    if (error instanceof SimpbApiError) {
        return error;
    }

    return new SimpbApiError(
        error?.message || 'Terjadi kesalahan tidak dikenal.',
        {
            code: 'UNKNOWN_ERROR',
            raw: error,
        }
    );
}

export function validationErrorsToObject(details = {}) {
    const output = {};

    Object.entries(details || {}).forEach(([field, messages]) => {
        output[field] = Array.isArray(messages)
            ? messages.join(' ')
            : String(messages);
    });

    return output;
}