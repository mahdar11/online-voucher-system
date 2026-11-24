const rupiahFormatter = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

const numberFormatter = new Intl.NumberFormat('id-ID');

const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
});

export function formatRupiah(value) {
    return rupiahFormatter.format(Number(value ?? 0));
}

export function formatNumber(value) {
    return numberFormatter.format(Number(value ?? 0));
}

export function formatDateTime(value) {
    return value ? dateTimeFormatter.format(new Date(value)) : '-';
}

export const DURATION_UNITS = [
    { value: 1, label: 'Menit' },
    { value: 60, label: 'Jam' },
    { value: 1440, label: 'Hari' },
];

/** 90 -> "1 jam 30 menit", 1440 -> "1 hari" */
export function formatDuration(minutes) {
    let rest = Number(minutes ?? 0);
    const parts = [];

    const days = Math.floor(rest / 1440);
    rest %= 1440;
    const hours = Math.floor(rest / 60);
    rest %= 60;

    if (days) parts.push(`${days} hari`);
    if (hours) parts.push(`${hours} jam`);
    if (rest) parts.push(`${rest} menit`);

    return parts.join(' ') || '0 menit';
}

/** Memilih satuan terbesar yang membagi habis durasi, untuk form edit. */
export function splitDuration(minutes) {
    const value = Number(minutes ?? 0);
    const unit = [...DURATION_UNITS].reverse().find((u) => value > 0 && value % u.value === 0) ?? DURATION_UNITS[0];

    return { amount: value / unit.value || '', unit: unit.value };
}

/** Masa aktif paket: 7 -> "7 hari", null -> "tanpa batas". */
export function formatValidity(days) {
    return days ? `${formatNumber(days)} hari` : 'tanpa batas';
}

export function formatQuota(mb) {
    if (mb === null || mb === undefined || mb === '') return 'Unlimited';

    const value = Number(mb);

    return value >= 1024 && value % 1024 === 0 ? `${value / 1024} GB` : `${formatNumber(value)} MB`;
}
