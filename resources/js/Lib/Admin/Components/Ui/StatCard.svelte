<script lang="ts">
    interface Props {
        label: string;
        value: string | number;
        unit?: string;
        icon?: string;
        color?:
            | "indigo"
            | "purple"
            | "blue"
            | "green"
            | "red"
            | "orange"
            | "pink"
            | "teal"
            | "cyan"
            | "amber";
        class?: string;
    }

    let {
        label,
        value,
        unit = "",
        icon = "fa-solid fa-chart-line",
        color = "indigo",
        class: className,
    }: Props = $props();

    const colorClasses = {
        indigo: {
            border: "from-indigo-500 via-purple-500 to-indigo-500",
            glow: "from-indigo-400/20 to-purple-400/20",
            hoverShadow:
                "hover:shadow-indigo-500/20 dark:hover:shadow-indigo-500/10",
            label: "group-hover:text-indigo-500 dark:group-hover:text-indigo-400",
            value: "group-hover:from-indigo-600 group-hover:to-purple-600 dark:group-hover:from-indigo-400 dark:group-hover:to-purple-400",
            unitText:
                "group-hover:text-indigo-400 dark:group-hover:text-purple-300",
            iconBg: "bg-indigo-50 text-indigo-600 dark:bg-slate-800 dark:text-indigo-400",
            iconHover: "group-hover:from-indigo-600 group-hover:to-purple-600",
            iconShadow: "group-hover:shadow-indigo-500/40",
        },
        purple: {
            border: "from-purple-500 via-pink-500 to-purple-500",
            glow: "from-purple-400/20 to-pink-400/20",
            hoverShadow:
                "hover:shadow-purple-500/20 dark:hover:shadow-purple-500/10",
            label: "group-hover:text-purple-500 dark:group-hover:text-purple-400",
            value: "group-hover:from-purple-600 group-hover:to-pink-600 dark:group-hover:from-purple-400 dark:group-hover:to-pink-400",
            unitText:
                "group-hover:text-purple-400 dark:group-hover:text-pink-300",
            iconBg: "bg-purple-50 text-purple-600 dark:bg-slate-800 dark:text-purple-400",
            iconHover: "group-hover:from-purple-600 group-hover:to-pink-600",
            iconShadow: "group-hover:shadow-purple-500/40",
        },
        blue: {
            border: "from-blue-500 via-cyan-500 to-blue-500",
            glow: "from-blue-400/20 to-cyan-400/20",
            hoverShadow:
                "hover:shadow-blue-500/20 dark:hover:shadow-blue-500/10",
            label: "group-hover:text-blue-500 dark:group-hover:text-blue-400",
            value: "group-hover:from-blue-600 group-hover:to-cyan-600 dark:group-hover:from-blue-400 dark:group-hover:to-cyan-400",
            unitText:
                "group-hover:text-blue-400 dark:group-hover:text-cyan-300",
            iconBg: "bg-blue-50 text-blue-600 dark:bg-slate-800 dark:text-blue-400",
            iconHover: "group-hover:from-blue-600 group-hover:to-cyan-600",
            iconShadow: "group-hover:shadow-blue-500/40",
        },
        green: {
            border: "from-green-500 via-emerald-500 to-green-500",
            glow: "from-green-400/20 to-emerald-400/20",
            hoverShadow:
                "hover:shadow-green-500/20 dark:hover:shadow-green-500/10",
            label: "group-hover:text-green-500 dark:group-hover:text-green-400",
            value: "group-hover:from-green-600 group-hover:to-emerald-600 dark:group-hover:from-green-400 dark:group-hover:to-emerald-400",
            unitText:
                "group-hover:text-green-400 dark:group-hover:text-emerald-300",
            iconBg: "bg-green-50 text-green-600 dark:bg-slate-800 dark:text-green-400",
            iconHover: "group-hover:from-green-600 group-hover:to-emerald-600",
            iconShadow: "group-hover:shadow-green-500/40",
        },
        red: {
            border: "from-red-500 via-rose-500 to-red-500",
            glow: "from-red-400/20 to-rose-400/20",
            hoverShadow: "hover:shadow-red-500/20 dark:hover:shadow-red-500/10",
            label: "group-hover:text-red-500 dark:group-hover:text-red-400",
            value: "group-hover:from-red-600 group-hover:to-rose-600 dark:group-hover:from-red-400 dark:group-hover:to-rose-400",
            unitText: "group-hover:text-red-400 dark:group-hover:text-rose-300",
            iconBg: "bg-red-50 text-red-600 dark:bg-slate-800 dark:text-red-400",
            iconHover: "group-hover:from-red-600 group-hover:to-rose-600",
            iconShadow: "group-hover:shadow-red-500/40",
        },
        orange: {
            border: "from-orange-500 via-amber-500 to-orange-500",
            glow: "from-orange-400/20 to-amber-400/20",
            hoverShadow:
                "hover:shadow-orange-500/20 dark:hover:shadow-orange-500/10",
            label: "group-hover:text-orange-500 dark:group-hover:text-orange-400",
            value: "group-hover:from-orange-600 group-hover:to-amber-600 dark:group-hover:from-orange-400 dark:group-hover:to-amber-400",
            unitText:
                "group-hover:text-orange-400 dark:group-hover:text-amber-300",
            iconBg: "bg-orange-50 text-orange-600 dark:bg-slate-800 dark:text-orange-400",
            iconHover: "group-hover:from-orange-600 group-hover:to-amber-600",
            iconShadow: "group-hover:shadow-orange-500/40",
        },
        pink: {
            border: "from-pink-500 via-rose-500 to-pink-500",
            glow: "from-pink-400/20 to-rose-400/20",
            hoverShadow:
                "hover:shadow-pink-500/20 dark:hover:shadow-pink-500/10",
            label: "group-hover:text-pink-500 dark:group-hover:text-pink-400",
            value: "group-hover:from-pink-600 group-hover:to-rose-600 dark:group-hover:from-pink-400 dark:group-hover:to-rose-400",
            unitText:
                "group-hover:text-pink-400 dark:group-hover:text-rose-300",
            iconBg: "bg-pink-50 text-pink-600 dark:bg-slate-800 dark:text-pink-400",
            iconHover: "group-hover:from-pink-600 group-hover:to-rose-600",
            iconShadow: "group-hover:shadow-pink-500/40",
        },
        teal: {
            border: "from-teal-500 via-cyan-500 to-teal-500",
            glow: "from-teal-400/20 to-cyan-400/20",
            hoverShadow:
                "hover:shadow-teal-500/20 dark:hover:shadow-teal-500/10",
            label: "group-hover:text-teal-500 dark:group-hover:text-teal-400",
            value: "group-hover:from-teal-600 group-hover:to-cyan-600 dark:group-hover:from-teal-400 dark:group-hover:to-cyan-400",
            unitText:
                "group-hover:text-teal-400 dark:group-hover:text-cyan-300",
            iconBg: "bg-teal-50 text-teal-600 dark:bg-slate-800 dark:text-teal-400",
            iconHover: "group-hover:from-teal-600 group-hover:to-cyan-600",
            iconShadow: "group-hover:shadow-teal-500/40",
        },
        cyan: {
            border: "from-cyan-500 via-blue-500 to-cyan-500",
            glow: "from-cyan-400/20 to-blue-400/20",
            hoverShadow:
                "hover:shadow-cyan-500/20 dark:hover:shadow-cyan-500/10",
            label: "group-hover:text-cyan-500 dark:group-hover:text-cyan-400",
            value: "group-hover:from-cyan-600 group-hover:to-blue-600 dark:group-hover:from-cyan-400 dark:group-hover:to-blue-400",
            unitText:
                "group-hover:text-cyan-400 dark:group-hover:text-blue-300",
            iconBg: "bg-cyan-50 text-cyan-600 dark:bg-slate-800 dark:text-cyan-400",
            iconHover: "group-hover:from-cyan-600 group-hover:to-blue-600",
            iconShadow: "group-hover:shadow-cyan-500/40",
        },
        amber: {
            border: "from-amber-500 via-yellow-500 to-amber-500",
            glow: "from-amber-400/20 to-yellow-400/20",
            hoverShadow:
                "hover:shadow-amber-500/20 dark:hover:shadow-amber-500/10",
            label: "group-hover:text-amber-500 dark:group-hover:text-amber-400",
            value: "group-hover:from-amber-600 group-hover:to-yellow-600 dark:group-hover:from-amber-400 dark:group-hover:to-yellow-400",
            unitText:
                "group-hover:text-amber-400 dark:group-hover:text-yellow-300",
            iconBg: "bg-amber-50 text-amber-600 dark:bg-slate-800 dark:text-amber-400",
            iconHover: "group-hover:from-amber-600 group-hover:to-yellow-600",
            iconShadow: "group-hover:shadow-amber-500/40",
        },
    };

    const colors = $derived(colorClasses[color]);
</script>

<div
    class="group relative w-full bg-white dark:bg-slate-900 rounded-2xl px-5 py-4 flex items-center justify-between
            shadow-xl {colors.hoverShadow} hover:-translate-y-2
            transition-all duration-500 ease-out overflow-hidden border border-slate-100 dark:border-slate-800 {className ||
        ''}"
>
    <div
        class="absolute left-0 top-0 bottom-0 w-2 bg-linear-to-b {colors.border} h-full rounded-l-2xl"
    ></div>

    <div
        class="absolute -right-16 -top-16 h-40 w-40 bg-linear-to-br {colors.glow} rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-700"
    ></div>

    <div class="flex z-10 flex-col justify-center pl-2">
        <p
            class="text-slate-400 dark:text-slate-500 text-xs font-bold uppercase tracking-widest mb-1
                  {colors.label} transition-colors duration-300"
        >
            {label}
        </p>

        <div class="flex gap-2 items-baseline">
            <h1
                class="text-3xl font-extrabold tracking-tight
                       text-transparent bg-clip-text bg-linear-to-r from-slate-800 to-slate-600
                       dark:from-white dark:to-slate-400
                       {colors.value}
                       transition-all duration-300"
            >
                {value}
            </h1>
            {#if unit}
                <span
                    class="text-base font-medium text-slate-400 dark:text-slate-500
                             {colors.unitText} transition-colors duration-300"
                >
                    {unit}
                </span>
            {/if}
        </div>
    </div>

    <div
        class="size-15 rounded-2xl flex items-center justify-center shadow-sm z-10
                {colors.iconBg}
                group-hover:bg-linear-to-br {colors.iconHover} group-hover:text-white
                group-hover:shadow-lg {colors.iconShadow}
                transition-all duration-500 ease-in-out relative overflow-hidden"
    >
        <div
            class="absolute inset-0 transition-transform duration-500 translate-y-full skew-y-12 bg-white/20 group-hover:translate-y-0"
        ></div>

        <i
            class="{icon} text-2xl transform group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-500 relative z-10"
        ></i>
    </div>
</div>
