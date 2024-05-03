export const DATE_PICK_TYPES = ['year', 'years', 'month', 'date', 'dates', 'week', 'datetime', 'datetimerange', 'daterange', 'monthrange'];

export const WEEK_DAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

export type DatePickType = (typeof DATE_PICK_TYPES)[number];

export type WeekDay = (typeof WEEK_DAYS)[number];
