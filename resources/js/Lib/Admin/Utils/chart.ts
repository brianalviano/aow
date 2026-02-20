export type ThemeColors = { themeColor: string; gridColor: string };

export function getThemeColors(dark?: boolean): ThemeColors {
    const isDark =
        typeof dark === "boolean"
            ? dark
            : typeof window !== "undefined" &&
              !!window.matchMedia &&
              window.matchMedia("(prefers-color-scheme: dark)").matches;
    return {
        themeColor: isDark ? "#94a3b8" : "#475569",
        gridColor: isDark ? "rgba(148,163,184,0.1)" : "rgba(71,85,105,0.08)",
    };
}

export function createLineChart(
    ChartCtor: any,
    canvas: HTMLCanvasElement,
    labels: string[],
    inbound: number[],
    outbound: number[]
) {
    const ctx = canvas.getContext("2d");
    if (!ctx) return null;
    const { themeColor, gridColor } = getThemeColors();
    return new ChartCtor(ctx, {
        type: "line",
        data: {
            labels,
            datasets: [
                {
                    label: "Inbound",
                    data: inbound,
                    borderColor: "#14b8a6",
                    backgroundColor: "rgba(20,184,166,0.12)",
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2,
                },
                {
                    label: "Outbound",
                    data: outbound,
                    borderColor: "#f97316",
                    backgroundColor: "rgba(249,115,22,0.12)",
                    tension: 0.4,
                    fill: true,
                    borderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: themeColor,
                        font: { size: 13, weight: "500" },
                        padding: 16,
                    },
                },
            },
            scales: {
                x: {
                    ticks: { color: themeColor, font: { size: 12 } },
                    grid: { color: gridColor, drawBorder: false },
                    border: { display: false },
                },
                y: {
                    ticks: { color: themeColor, font: { size: 12 } },
                    grid: { color: gridColor, drawBorder: false },
                    border: { display: false },
                },
            },
        },
    });
}

export function createBarChart(
    ChartCtor: any,
    canvas: HTMLCanvasElement,
    labels: string[],
    data: number[],
    datasetLabel: string,
    colorHex: string
) {
    const ctx = canvas.getContext("2d");
    if (!ctx) return null;
    const { themeColor, gridColor } = getThemeColors();
    return new ChartCtor(ctx, {
        type: "bar",
        data: {
            labels,
            datasets: [
                {
                    label: datasetLabel,
                    data,
                    backgroundColor: colorHex,
                    borderRadius: 6,
                    borderSkipped: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: themeColor,
                        font: { size: 13, weight: "500" },
                        padding: 16,
                    },
                },
            },
            scales: {
                x: {
                    ticks: { color: themeColor, font: { size: 12 } },
                    grid: { display: false },
                    border: { display: false },
                },
                y: {
                    ticks: { color: themeColor, font: { size: 12 } },
                    grid: { color: gridColor, drawBorder: false },
                    border: { display: false },
                },
            },
        },
    });
}
