const GA_MEASUREMENT_ID = /^G-[A-Z0-9]+$/;

/** Empty or invalid ids render nothing. The live id is not stored in source. */
export function gaMeasurementId(): string | undefined {
  const value = import.meta.env.PUBLIC_GA_MEASUREMENT_ID?.trim() ?? "";
  if (!GA_MEASUREMENT_ID.test(value)) {
    return undefined;
  }
  return value;
}
