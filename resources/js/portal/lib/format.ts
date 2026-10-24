const rupiah = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const longDate = new Intl.DateTimeFormat('id-ID', { dateStyle: 'long', timeZone: 'Asia/Jakarta' });
const dateTime = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' });

export const formatRupiah = (value: number): string => rupiah.format(value);

/** YYYY-MM-DD (tanggal kalender) atau ISO 8601 → "24 Oktober 2026". */
export const formatDate = (value: string): string =>
  longDate.format(new Date(/^\d{4}-\d{2}-\d{2}$/.test(value) ? `${value}T00:00:00+07:00` : value));

export const formatDateTime = (value: string): string => dateTime.format(new Date(value));

export const issuerLabel = (issuer: string): string => (issuer === 'bnsp' ? 'Sertifikasi BNSP' : 'Sertifikat Lembaga');

export const modeLabel = (mode: string): string =>
  ({ online: 'Online', offline: 'Tatap muka', hybrid: 'Hybrid' })[mode] ?? mode;
