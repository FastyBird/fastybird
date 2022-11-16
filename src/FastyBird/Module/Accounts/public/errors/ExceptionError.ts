class ExceptionError extends Error {
	public type: string;

	public exception: Error | null;

	// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
	constructor(type: string, exception: Error | null, ...params: any) {
		// Pass remaining arguments (including vendor specific ones) to parent constructor
		super(...params);

		// Maintains proper stack trace for where our error was thrown (only available on V8)
		if (Error.captureStackTrace) {
			Error.captureStackTrace(this, ExceptionError);
		}

		// Custom debugging information
		this.type = type;
		this.exception = exception;
	}
}

export default ExceptionError;
